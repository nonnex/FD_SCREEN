<?php
require_once __DIR__ . '/inc/config.php';
require_once APP_ROOT . '/vendor/autoload.php';

use Ratchet\Client\WebSocket as WebSocketClient;
use React\EventLoop\Factory as EventLoopFactory;

// Initialize logging
function logMessage($message) {
    $logFile = MIDDLEWARE_SETTINGS['log_file'];
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$timestamp] $message\n", FILE_APPEND);
}

// Database connection handler
class DatabaseConnection {
    private $dbConfig;
    private $conn;

    public function __construct($dbConfig) {
        $this->dbConfig = $dbConfig;
    }

    public function connect() {
        if (!$this->dbConfig['enabled']) {
            logMessage("Database {$this->dbConfig['type']} is disabled (APP_MODE: " . APP_MODE . ")");
            return null;
        }

        try {
            if ($this->dbConfig['type'] === 'sybase') {
                $this->conn = sqlanywhere_connect($this->dbConfig['dsn']);
                if (!$this->conn) {
                    throw new Exception('Failed to connect to Sybase: ' . sqlanywhere_error());
                }
            } elseif ($this->dbConfig['type'] === 'mysql') {
                $this->conn = new PDO(
                    $this->dbConfig['dsn'],
                    $this->dbConfig['username'],
                    $this->dbConfig['password']
                );
                $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            }
            logMessage("Connected to {$this->dbConfig['type']} database");
            return $this->conn;
        } catch (Exception $e) {
            logMessage("Database connection error ({$this->dbConfig['type']}): " . $e->getMessage());
            return null;
        }
    }

    public function query($sql, $params = []) {
        if (!$this->conn) return [];

        try {
            if ($this->dbConfig['type'] === 'sybase') {
                $stmt = sqlanywhere_prepare($this->conn, $sql);
                if (!$stmt) {
                    throw new Exception('Query prepare failed: ' . sqlanywhere_error($this->conn));
                }
                foreach ($params as $key => $value) {
                    sqlanywhere_bind_param($stmt, $key + 1, $value);
                }
                if (!sqlanywhere_execute($stmt)) {
                    throw new Exception('Query execution failed: ' . sqlanywhere_error($this->conn));
                }
                $result = [];
                while ($row = sqlanywhere_fetch_array($stmt)) {
                    $result[] = $row;
                }
                sqlanywhere_free_stmt($stmt);
                return $result;
            } elseif ($this->dbConfig['type'] === 'mysql') {
                $stmt = $this->conn->prepare($sql);
                $stmt->execute($params);
                return $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
        } catch (Exception $e) {
            logMessage("Query error ({$this->dbConfig['type']}): " . $e->getMessage());
            return [];
        }
        return [];
    }

    public function close() {
        if ($this->conn) {
            if ($this->dbConfig['type'] === 'sybase') {
                sqlanywhere_close($this->conn);
            } elseif ($this->dbConfig['type'] === 'mysql') {
                $this->conn = null;
            }
            logMessage("Disconnected from {$this->dbConfig['type']} database");
        }
    }
}

// Change detector for trigger-based strategy (online mode)
class TriggerChangeDetector {
    private $db;
    private $lastChangeId = 0;

    public function __construct($db) {
        $this->db = $db;
    }

    public function detectChanges() {
        if (!$this->db->connect()) {
            return [];
        }

        $changesTable = $this->db->dbConfig['changes_table'];
        $sql = "SELECT id, table_name, operation, record_id, change_data 
                FROM $changesTable 
                WHERE id > ? 
                ORDER BY id ASC";
        $changes = $this->db->query($sql, [$this->lastChangeId]);

        if (!empty($changes)) {
            $this->lastChangeId = end($changes)['id'];
        }

        return array_map(function ($change) {
            return [
                'type' => $this->mapOperationToType($change['operation']),
                'data' => json_decode($change['change_data'], true)
            ];
        }, $changes);
    }

    private function mapOperationToType($operation) {
        switch (strtoupper($operation)) {
            case 'INSERT':
                return 'order_created';
            case 'UPDATE':
                return 'order_updated';
            case 'DELETE':
                return 'order_deleted';
            default:
                return 'unknown';
        }
    }
}

// Change detector for polling-based strategy (online mode)
class PollingChangeDetector {
    private $db;
    private $lastPollTime;

    public function __construct($db) {
        $this->db = $db;
        $this->lastPollTime = date('Y-m-d H:i:s', strtotime('-1 hour'));
    }

    public function detectChanges() {
        if (!$this->db->connect()) {
            return [];
        }

        $sql = "SELECT AuftragId, AuftragsNr, AuftragsKennung, Datum_Erfassung, BestellNr, 
                       Liefertermin, KundenNr, KundenMatchcode, Status 
                FROM orders 
                WHERE last_updated > ?";
        $orders = $this->db->query($sql, [$this->lastPollTime]);

        $this->lastPollTime = date('Y-m-d H:i:s');

        return array_map(function ($order) {
            return [
                'type' => 'order_updated',
                'data' => [
                    'AuftragId' => $order['AuftragId'],
                    'AuftragsNr' => $order['AuftragsNr'],
                    'AuftragsKennung' => $order['AuftragsKennung'],
                    'Datum_Erfassung' => $order['Datum_Erfassung'],
                    'BestellNr' => $order['BestellNr'],
                    'Liefertermin' => $order['Liefertermin'],
                    'KundenNr' => $order['KundenNr'],
                    'KundenMatchcode' => $order['KundenMatchcode'],
                    'Status' => $order['Status'],
                    'ShowPos' => 1,
                    'Tags' => []
                ]
            ];
        }, $orders);
    }
}

// Change detector for mock data (offline mode)
class MockChangeDetector {
    private $mockFiles = [
        APP_ROOT . '/inc/mock_orders.php',
        APP_ROOT . '/inc/mock_orders_virtual.php'
    ];
    private $lastModifiedTimes = [];
    private $previousData = [];

    public function __construct() {
        // Initialize last modified times and previous data
        foreach ($this->mockFiles as $file) {
            $this->lastModifiedTimes[$file] = 0;
            $this->previousData[$file] = [];
        }
    }

    public function detectChanges() {
        $changes = [];

        foreach ($this->mockFiles as $file) {
            if (!file_exists($file)) {
                logMessage("Mock file not found: $file");
                continue;
            }

            $currentModifiedTime = filemtime($file);
            if ($currentModifiedTime === false) {
                logMessage("Failed to get modification time for $file");
                continue;
            }

            // Check if the file has been modified since the last check
            if ($currentModifiedTime > $this->lastModifiedTimes[$file]) {
                logMessage("Detected change in mock file: $file");

                // Load the mock data
                $currentData = include $file;
                if (!is_array($currentData)) {
                    logMessage("Invalid mock data in $file: Not an array");
                    continue;
                }

                // Compare with previous data to detect changes
                $previousData = $this->previousData[$file];
                $newChanges = $this->compareMockData($previousData, $currentData);
                $changes = array_merge($changes, $newChanges);

                // Update state
                $this->lastModifiedTimes[$file] = $currentModifiedTime;
                $this->previousData[$file] = $currentData;
            }
        }

        return $changes;
    }

    private function compareMockData($previousData, $currentData) {
        $changes = [];

        // Convert arrays to use AuftragId as the key for easier comparison
        $previousMap = [];
        foreach ($previousData as $order) {
            if (isset($order['AuftragId'])) {
                $previousMap[$order['AuftragId']] = $order;
            }
        }

        $currentMap = [];
        foreach ($currentData as $order) {
            if (isset($order['AuftragId'])) {
                $currentMap[$order['AuftragId']] = $order;
            }
        }

        // Detect new or updated orders
        foreach ($currentMap as $auftragId => $currentOrder) {
            if (!isset($previousMap[$auftragId])) {
                // New order (INSERT)
                $changes[] = [
                    'type' => 'order_created',
                    'data' => $this->formatOrder($currentOrder)
                ];
            } else {
                // Check for updates by comparing fields
                $previousOrder = $previousMap[$auftragId];
                if ($this->orderHasChanged($previousOrder, $currentOrder)) {
                    $changes[] = [
                        'type' => 'order_updated',
                        'data' => $this->formatOrder($currentOrder)
                    ];
                }
            }
        }

        // Detect deleted orders
        foreach ($previousMap as $auftragId => $previousOrder) {
            if (!isset($currentMap[$auftragId])) {
                $changes[] = [
                    'type' => 'order_deleted',
                    'data' => ['AuftragId' => $auftragId]
                ];
            }
        }

        return $changes;
    }

    private function orderHasChanged($previousOrder, $currentOrder) {
        $fieldsToCompare = [
            'AuftragsNr', 'AuftragsKennung', 'Datum_Erfassung', 'BestellNr',
            'Liefertermin', 'KundenNr', 'KundenMatchcode', 'Status'
        ];

        foreach ($fieldsToCompare as $field) {
            $previousValue = $previousOrder[$field] ?? null;
            $currentValue = $currentOrder[$field] ?? null;
            if ($previousValue !== $currentValue) {
                return true;
            }
        }
        return false;
    }

    private function formatOrder($order) {
        return [
            'AuftragId' => $order['AuftragId'],
            'AuftragsNr' => $order['AuftragsNr'],
            'AuftragsKennung' => $order['AuftragsKennung'],
            'Datum_Erfassung' => $order['Datum_Erfassung'],
            'BestellNr' => $order['BestellNr'],
            'Liefertermin' => $order['Liefertermin'],
            'KundenNr' => $order['KundenNr'],
            'KundenMatchcode' => $order['KundenMatchcode'],
            'Status' => $order['Status'],
            'ShowPos' => $order['ShowPos'] ?? 1,
            'Tags' => $order['Tags'] ?? []
        ];
    }
}

// Middleware to detect changes and push to WebSocket
class ChangeDetectorMiddleware {
    private $loop;
    private $wsClient;
    private $detectors = [];

    public function __construct($loop) {
        $this->loop = $loop;
        $this->initializeDetectors();
        $this->connectToWebSocket();
    }

    private function initializeDetectors() {
        if (APP_MODE === 'offline') {
            // In offline mode, use the mock change detector
            $this->detectors['mock'] = new MockChangeDetector();
            logMessage("Initialized mock change detector for offline mode");
        } else {
            // In online mode, use database detectors
            foreach (DATABASES as $dbName => $dbConfig) {
                $db = new DatabaseConnection($dbConfig);
                if ($dbConfig['strategy'] === 'trigger') {
                    $this->detectors[$dbName] = new TriggerChangeDetector($db);
                } elseif ($dbConfig['strategy'] === 'polling') {
                    $this->detectors[$dbName] = new PollingChangeDetector($db);
                }
                logMessage("Initialized {$dbConfig['strategy']} detector for $dbName");
            }
        }
    }

    private function connectToWebSocket() {
        if (!WEBSOCKET_SERVER['uri']) {
            logMessage("WebSocket connection disabled (APP_MODE: " . APP_MODE . ")");
            return;
        }

        \Ratchet\Client\connect(WEBSOCKET_SERVER['uri'], [], [], $this->loop)
            ->then(function (WebSocketClient $conn) {
                $this->wsClient = $conn;
                logMessage("Connected to WebSocket server at " . WEBSOCKET_SERVER['uri']);

                $conn->on('close', function () {
                    logMessage("WebSocket connection closed. Reconnecting...");
                    $this->connectToWebSocket();
                });
            }, function ($e) {
                logMessage("WebSocket connection failed: " . $e->getMessage());
                $this->loop->addTimer(5, [$this, 'connectToWebSocket']);
            });
    }

    public function start() {
        $this->loop->addPeriodicTimer(MIDDLEWARE_SETTINGS['poll_interval'], function () {
            foreach ($this->detectors as $dbName => $detector) {
                $changes = $detector->detectChanges();
                foreach ($changes as $change) {
                    if ($this->wsClient) {
                        $this->wsClient->send(json_encode($change));
                        logMessage("Sent change to WebSocket: " . json_encode($change));
                    } else {
                        logMessage("No WebSocket connection. Change not sent: " . json_encode($change));
                    }
                }
            }
        });
    }
}

// Create log directory if it doesn't exist
$logDir = dirname(MIDDLEWARE_SETTINGS['log_file']);
if (!is_dir($logDir)) {
    mkdir($logDir, 0777, true);
}

// Start the middleware
$loop = EventLoopFactory::create();
$middleware = new ChangeDetectorMiddleware($loop);
$middleware->start();

logMessage("Change Detector Middleware started");
$loop->run();
?>