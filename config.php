<?php
// Define the application root directory
define('APP_ROOT', __DIR__);

// Application mode: 'online' for database, 'offline' for dummy data
define('APP_MODE', 'offline');

// Database configurations for SQL Anywhere (db_lx) and MySQL (db_fd)
//   function __construct($FD_HST="127.0.0.1", $FD_UID="ferrodom", $FD_PWD="kitemurt2", $FD_DB="lx_fd", $FD_PORT=3306, $charset = 'utf8') {
define('DATABASES', [
    'sybase_erp' => [
        'type' => 'sybase',
        'strategy' => 'trigger',
        'dsn' => 'Host=FERROSRV;ServerName=LXDBSRV;DBN=F2;UID=U0;PWD=ef41959cd6c24908',
        'changes_table' => 'changes',
        'enabled' => APP_MODE === 'online'
    ],
    'mysql_erp' => [
        'type' => 'mysql',
        'strategy' => 'trigger',
        'dsn' => 'Host=FERROSRV;DBN=lx_fd;Port=3306',
        'username' => 'ferrodom',
        'password' => 'kitemurt2',
        'charset' => 'utf8',
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