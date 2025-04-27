<?php
require('Database.php');

use Dcblogdev\PdoWrapper\Database;

define('CALENDAR_TABLE', 'fd_calendar');
define('TRASH_CSV', 'Abfuhrkalender-Rechtmehring-2025');

$dir = './';

$options = [
    'host' => "localhost",
    'database' => "lx_fd",
    'username' => "ferrodom",
    'password' => "kitemurt2"
];
$db = new Database($options);


