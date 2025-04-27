<?php
// Require config.php (which defines APP_ROOT)
require_once __DIR__ . '/config.php';
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
        $this->broadcast($msg);
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
            $client->send($message);
        }
        $this->log("Broadcasted message: " . $message);
    }

    // Changed to public so it can be called from outside the class
    public function log($message) {
        $logFile = APP_ROOT . '/logs/websocket.log';
        $timestamp = date('Y-m-d H:i:s');
        file_put_contents($logFile, "[$timestamp] $message\n", FILE_APPEND);
    }
}

$loop = React\EventLoop\Factory::create();
$server = new OrderUpdateServer();

// Check for SSL certificates
$certPath = APP_ROOT . '/certs/cert.pem';
$keyPath = APP_ROOT . '/certs/key.pem';
$useSsl = file_exists($certPath) && file_exists($keyPath);

$socket = new React\Socket\Server('0.0.0.0:8080', $loop);

if ($useSsl) {
    $secureSocket = new React\Socket\SecureServer($socket, $loop, [
        'local_cert' => $certPath,
        'local_pk' => $keyPath,
        'verify_peer' => false
    ]);
    $serverSocket = new Ratchet\Server\IoServer(
        new Ratchet\Http\HttpServer(
            new Ratchet\WebSocket\WsServer($server)
        ),
        $secureSocket,
        $loop
    );
    $server->log("Starting WebSocket server with SSL (wss://)");
} else {
    $serverSocket = new Ratchet\Server\IoServer(
        new Ratchet\Http\HttpServer(
            new Ratchet\WebSocket\WsServer($server)
        ),
        $socket,
        $loop
    );
    $server->log("Starting WebSocket server without SSL (ws://)");
}

$loop->run();
?>