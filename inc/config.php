<?php
// Define the application root directory
define('APP_ROOT', dirname(__DIR__)); // Resolves to C:\xampp\htdocs\FD_SCREEN

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

// WebSocket server settings
define('WEBSOCKET_SERVER', [
    'host' => 'localhost',
    'port' => 8080,
    'uri' => APP_MODE === 'online' ? 'wss://localhost:8080' : null
]);

// Middleware settings
define('MIDDLEWARE_SETTINGS', [
    'poll_interval' => 5,
    'log_file' => APP_ROOT . '/logs/middleware.log'
]);
?>