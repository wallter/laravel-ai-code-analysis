<?php

use Illuminate\Http\Request;

// Disable displaying errors to the user
ini_set('display_errors', 'Off');
ini_set('display_startup_errors', 'Off');
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);

// Define the start time of the application
define('LARAVEL_START', microtime(true));

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require __DIR__.'/../vendor/autoload.php';

// Bootstrap Laravel and handle the request...
(require_once __DIR__.'/../bootstrap/app.php')
    ->handleRequest(Request::capture());
