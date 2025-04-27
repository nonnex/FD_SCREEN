<?php
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

    public function log($message) {
        if (LOGGING['enabled']) {
            $logFile = LOGGING['websocket_log'];
            $timestamp = date('Y-m-d H:i:s');
            file_put_contents($logFile, "[$timestamp] $message\n", FILE_APPEND);
        }
    }
}

try {
    $loop = React\EventLoop\Factory::create();
    $server = new OrderUpdateServer();

    $serverConfig = WEBSOCKET_SERVER;
    $socket = new React\Socket\Server("{$serverConfig['host']}:{$serverConfig['port']}", $loop);

    if ($serverConfig['use_ssl']) {
        $secureSocket = new React\Socket\SecureServer($socket, $loop, [
            'local_cert' => $serverConfig['cert_path'],
            'local_pk' => $serverConfig['key_path'],
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
} catch (\Exception $e) {
    $errorMessage = "Failed to start WebSocket server: {$e->getMessage()}";
    file_put_contents(LOGGING['websocket_log'], "[" . date('Y-m-d H:i:s') . "] $errorMessage\n", FILE_APPEND);
    echo $errorMessage . "\n";
    exit(1);
}
?>