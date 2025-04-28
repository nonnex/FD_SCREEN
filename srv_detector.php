<?php
// Require config.php (which defines APP_ROOT)
require_once __DIR__ . '/config.php';
require_once APP_ROOT . '/vendor/autoload.php';
require_once APP_ROOT . '/inc/lx_orders.php'; // Lx_Orders einbinden
require_once APP_ROOT . '/inc/db/db_lx.php'; // DB_LX einbinden
require_once APP_ROOT . '/inc/db/db_fd.php'; // DB_FD einbinden

use WebSocket\Client;
use WebSocket\Connection;
use WebSocket\Message\Message;
use WebSocket\Middleware\CloseHandler;
use WebSocket\Middleware\PingResponder;

class ChangeDetectorMiddleware {
    private $loop;
    private $wsClient;
    private $dummyOrders;
    private $dummyEvents;
    private $isConnected;
    private $lxOrders; // Lx_Orders Instanz

    public function __construct($loop) {
        $this->loop = $loop;
        $this->isConnected = false;
        $this->lxOrders = new Lx_Orders(); // Lx_Orders initialisieren
        $this->dummyOrders = $this->loadDummyOrders();
        $this->dummyEvents = $this->loadDummyEvents();
        $this->connectToWebSocket();
        $this->schedulePolling();
    }

    private function loadDummyOrders() {
        return [
            [
                'AuftragId' => '1',
                'AuftragsNr' => '1001',
                'AuftragsKennung' => 1,
                'Datum_Erfassung' => '2025-04-27',
                'BestellNr' => 'B001',
                'Liefertermin' => '2025-05-01',
                'KundenNr' => 'K001',
                'KundenMatchcode' => 'Kunde1',
                'Status' => 1,
                'ShowPos' => 1,
                'Tags' => [['lTagId' => 4, 'szName' => 'Neu']],
                'Positionen' => [
                    ['PosNr' => 1, 'ArtikelId' => 'A001', 'ArtikelNr' => 'A001', 'Artikel_Bezeichnung' => 'Produkt A', 'Artikel_Menge' => 10, 'Artikel_LagerId' => 'L001'],
                    ['PosNr' => 2, 'ArtikelId' => 'A002', 'ArtikelNr' => 'A002', 'Artikel_Bezeichnung' => 'Produkt B', 'Artikel_Menge' => 5, 'Artikel_LagerId' => 'L002']
                ]
            ],
            [
                'AuftragId' => '2',
                'AuftragsNr' => '1002',
                'AuftragsKennung' => 1,
                'Datum_Erfassung' => '2025-04-27',
                'BestellNr' => 'B002',
                'Liefertermin' => '2025-05-02',
                'KundenNr' => 'K002',
                'KundenMatchcode' => 'Kunde2',
                'Status' => 2,
                'ShowPos' => 1,
                'Tags' => [['lTagId' => 2, 'szName' => 'Produktion']],
                'Positionen' => [
                    ['PosNr' => 1, 'ArtikelId' => 'A003', 'ArtikelNr' => 'A003', 'Artikel_Bezeichnung' => 'Produkt C', 'Artikel_Menge' => 8, 'Artikel_LagerId' => 'L003']
                ]
            ]
        ];
    }

    private function loadDummyEvents() {
        return [
            [
                'AuftragId' => 'E_1',
                'AuftragsNr' => 'EVT001',
                'AuftragsKennung' => 1,
                'Datum_Erfassung' => '2025-04-27',
                'BestellNr' => 'N/A',
                'Liefertermin' => '2025-04-28',
                'KundenNr' => 'N/A',
                'KundenMatchcode' => 'Event',
                'Status' => 1,
                'ShowPos' => 1,
                'Tags' => [['lTagId' => 4, 'szName' => 'Neu']],
                'Positionen' => [
                    ['PosNr' => 1, 'ArtikelId' => 'EVT_A', 'ArtikelNr' => 'EVT_A', 'Artikel_Bezeichnung' => 'Event Detail', 'Artikel_Menge' => 1, 'Artikel_LagerId' => 'N/A']
                ]
            ]
        ];
    }

    private function loadInitialState() {
        if (APP_MODE === 'online') {
            $this->log("Loading initial state from database (online mode)");
            try {
                // Daten aus der Datenbank laden
                $ordersData = $this->lxOrders->GetAllOpenOrdersFromLX();
                $orders = array_values($ordersData); // Array umformatieren

                // Mindestbestand-Auftrag hinzufügen (falls nötig)
                $minOrder = $this->lxOrders->CreateMindestbestandOrder();
                if (!empty($minOrder['Positionen'])) {
                    $orders[] = $minOrder;
                }

                // Events (z. B. Mindestbestand-Events) könnten hier ebenfalls geladen werden
                $events = $this->dummyEvents; // Platzhalter, da keine Event-Daten aus der Datenbank geladen werden

                // In das erwartete Format umwandeln
                $formattedOrders = array_map(function ($order) {
                    return ['type' => 'order_created', 'data' => $order];
                }, $orders);
                $formattedEvents = array_map(function ($event) {
                    return ['type' => 'event_created', 'data' => $event];
                }, $events);

                return [
                    'orders' => $formattedOrders,
                    'events' => $formattedEvents
                ];
            } catch (\Exception $e) {
                $this->log("Failed to load data from database: {$e->getMessage()}");
                return ['orders' => [], 'events' => []]; // Fallback bei Fehler
            }
        } else {
            $this->log("Loading initial state from dummy data (offline mode)");
            return [
                'orders' => array_map(function ($order) {
                    return ['type' => 'order_created', 'data' => $order];
                }, $this->dummyOrders),
                'events' => array_map(function ($event) {
                    return ['type' => 'event_created', 'data' => $event];
                }, $this->dummyEvents)
            ];
        }
    }

    private function connectToWebSocket() {
        try {
            $this->wsClient = new Client(WEBSOCKET_SERVER['uri']);
            $this->wsClient
                ->addMiddleware(new CloseHandler())
                ->addMiddleware(new PingResponder())
                ->onConnect(function (Client $client, Connection $connection) {
                    $this->isConnected = true;
                    $this->log("Connected to WebSocket server at " . WEBSOCKET_SERVER['uri']);

                    // Initial state basierend auf APP_MODE laden
                    $initialState = [
                        'type' => 'initial_state',
                        'data' => $this->loadInitialState()
                    ];
                    $jsonMessage = json_encode($initialState);
                    $this->log("Sending initial state: " . $jsonMessage);
                    $connection->text($jsonMessage);
                    $this->log("Sent initial state successfully");
                })
                ->onText(function (Client $client, Connection $connection, Message $message) {
                    $this->log("Received from WebSocket: " . $message->getContent());
                })
                ->onClose(function (Client $client, Connection $connection) {
                    $this->isConnected = false;
                    $this->log("WebSocket connection closed. Reconnecting...");
                    $this->wsClient = null;
                    $this->loop->addTimer(5, [$this, 'connectToWebSocket']);
                })
                ->onError(function (Client $client, \Throwable $e) {
                    $this->isConnected = false;
                    $this->log("WebSocket error: {$e->getMessage()}. Retrying in 5 seconds...");
                    $this->loop->addTimer(5, [$this, 'connectToWebSocket']);
                });

            $this->wsClient->start();
        } catch (\Exception $e) {
            $this->isConnected = false;
            $this->log("Failed to connect to WebSocket: {$e->getMessage()}. Retrying in 5 seconds...");
            $this->loop->addTimer(5, [$this, 'connectToWebSocket']);
        }
    }

    private function schedulePolling() {
        $this->loop->addPeriodicTimer(MIDDLEWARE_SETTINGS['poll_interval'], function () {
            $this->checkForChanges();
        });
    }

    private function checkForChanges() {
        if (APP_MODE === 'offline') {
            $this->simulateChange();
        } else {
            // Hier könntest du Logik hinzufügen, um Änderungen in der Datenbank zu erkennen
            // Zum Beispiel durch Prüfen der "changes"-Tabelle (definiert in DATABASES)
            $this->log("Checking for database changes (online mode)");
            // Beispiel: Daten neu laden und vergleichen
            // Für dieses Beispiel lassen wir es vorerst weg, da es komplexer ist
        }
    }

    private function simulateChange() {
        if (!empty($this->dummyOrders)) {
            $order = &$this->dummyOrders[0];
            $newStatus = min($order['Status'] + 1, 4);
            if ($newStatus !== $order['Status']) {
                $order['Status'] = $newStatus;
                $order['Tags'] = [['lTagId' => $newStatus === 1 ? 4 : ($newStatus === 2 ? 2 : ($newStatus === 3 ? 5 : 1)), 'szName' => $newStatus === 1 ? 'Neu' : ($newStatus === 2 ? 'Produktion' : ($newStatus === 3 ? 'Versandbereit' : 'Versendet'))]];
                $message = [
                    'type' => 'order_updated',
                    'data' => $order
                ];
                $this->sendToWebSocket($message);
                $this->log("Simulated order update: " . json_encode($message));
            }
        }
    }

    private function sendToWebSocket($message) {
        if ($this->isConnected && $this->wsClient) {
            try {
                $this->wsClient->text(json_encode($message));
                $this->log("Sent to WebSocket: " . json_encode($message));
            } catch (\Exception $e) {
                $this->isConnected = false;
                $this->log("Failed to send to WebSocket: {$e->getMessage()}");
            }
        } else {
            $this->log("WebSocket client not connected. Cannot send message: " . json_encode($message));
        }
    }

    private function log($message) {
        $logFile = MIDDLEWARE_SETTINGS['log_file'];
        $timestamp = date('Y-m-d H:i:s');
        file_put_contents($logFile, "[$timestamp] $message\n", FILE_APPEND);
    }
}

$loop = React\EventLoop\Factory::create();
$middleware = new ChangeDetectorMiddleware($loop);
$loop->run();
?>