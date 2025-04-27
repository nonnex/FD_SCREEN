<?php
// Correct the path to config.php based on the script's location in FD_SCREEN
require_once __DIR__ . '/inc/config.php'; // __DIR__ is C:\xampp\htdocs\FD_SCREEN
require_once APP_ROOT . '/vendor/autoload.php';

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;

class OrderUpdateServer implements MessageComponentInterface {
    protected $clients;

    public function __construct() {
        $this->clients = new \SplObjectStorage();
        $this->log("WebSocket Server started");
    }

    public function onOpen(ConnectionInterface $conn) {
        $this->clients->attach($conn);
        $this->log("New connection: {$conn->resourceId}");
    }

    public function onMessage(ConnectionInterface $from, $msg) {
        $this->log("Message received from {$from->resourceId}: $msg");
    }

    public function onClose(ConnectionInterface $conn) {
        $this->clients->detach($conn);
        $this->log("Connection closed: {$conn->resourceId}");
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        $this->log("An error occurred: {$e->getMessage()}");
        $conn->close();
    }

    public function broadcast($message) {
        foreach ($this->clients as $client) {
            $client->send(json_encode($message));
        }
        $this->log("Broadcasted message: " . json_encode($message));
    }

    private function log($message) {
        $logFile = APP_ROOT . '/logs/websocket.log';
        $timestamp = date('Y-m-d H:i:s');
        file_put_contents($logFile, "[$timestamp] $message\n", FILE_APPEND);
    }
}

$loop = React\EventLoop\Factory::create();
$server = new OrderUpdateServer();

// SSL configuration (self-signed certificate for local use)
$socket = new React\Socket\Server('0.0.0.0:8080', $loop);
$secureSocket = new React\Socket\SecureServer($socket, $loop, [
    'local_cert' => '/path/to/cert.pem',
    'local_pk' => '/path/to/key.pem',
    'verify_peer' => false
]);

$serverSocket = new Ratchet\Server\IoServer(
    new Ratchet\Http\HttpServer(
        new Ratchet\WebSocket\WsServer($server)
    ),
    $secureSocket,
    $loop
);

$loop->run();
?>