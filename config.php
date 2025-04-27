<?php
// Define the application root directory
define('APP_ROOT', __DIR__);

// Application mode: 'online' for database, 'offline' for dummy data
define('APP_MODE', 'offline');

// Database configurations
define('DATABASES', [
    'sybase_erp' => [
        'type' => 'sybase',
        'strategy' => 'trigger',
        'dsn' => 'sqlanywhere:uid=your_user;pwd=your_password;DatabaseName=erp_db',
        'changes_table' => 'changes',
        'enabled' => APP_MODE === 'online'
    ],
    'mysql_erp' => [
        'type' => 'mysql',
        'strategy' => 'trigger',
        'dsn' => 'mysql:host=localhost;dbname=erp_db',
        'username' => 'your_mysql_user',
        'password' => 'your_mysql_password',
        'changes_table' => 'changes',
        'enabled' => APP_MODE === 'online'
    ]
]);

// Check for SSL certificates
$certPath = APP_ROOT . '/certs/cert.pem';
$keyPath = APP_ROOT . '/certs/key.pem';
$useSsl = file_exists($certPath) && file_exists($keyPath);

// WebSocket server settings (for srv_websocket.php)
define('WEBSOCKET_SERVER', [
    'host' => '127.0.0.1', // Changed from 'localhost' to '127.0.0.1'
    'port' => 8081,
    'uri' => $useSsl ? 'wss://127.0.0.1:8081' : 'ws://127.0.0.1:8081', // Updated URI
    'use_ssl' => $useSsl,
    'cert_path' => $certPath,
    'key_path' => $keyPath
]);

// Middleware settings (for srv_detector.php)
define('MIDDLEWARE_SETTINGS', [
    'poll_interval' => 10, // Seconds between polling for changes
    'log_file' => APP_ROOT . '/logs/middleware.log'
]);

// Logging settings
define('LOGGING', [
    'enabled' => true,
    'websocket_log' => APP_ROOT . '/logs/websocket.log',
    'middleware_log' => APP_ROOT . '/logs/middleware.log'
]);
?>