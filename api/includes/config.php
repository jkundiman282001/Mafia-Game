<?php
/* Database credentials. Assuming you are running MySQL
server with default setting (user 'root' with no password) */

ini_set('display_errors', 0);
error_reporting(E_ALL);

// Use Environment Variables (Railway defaults or custom), fallback to local defaults
$db_server = getenv('MYSQLHOST') ?: getenv('DB_SERVER') ?: 'switchyard.proxy.rlwy.net';
$db_username = getenv('MYSQLUSER') ?: getenv('DB_USERNAME') ?: 'root';
$db_password = getenv('MYSQLPASSWORD') ?: getenv('DB_PASSWORD') ?: 'KMSXVeVzAkhhEVRRVJjJsOSJyaMJwljx';
$db_name = getenv('MYSQLDATABASE') ?: getenv('DB_NAME') ?: 'railway';
$db_port = getenv('MYSQLPORT') ?: getenv('DB_PORT') ?: 45221;

/* Attempt to connect to MySQL database */
if ($db_port) {
    $link = mysqli_connect($db_server, $db_username, $db_password, $db_name, (int)$db_port);
} else {
    $link = mysqli_connect($db_server, $db_username, $db_password, $db_name);
}

// Check connection
if($link === false){
    $error_msg = "ERROR: Could not connect. " . mysqli_connect_error();
    // Check if this is an API call
    if (strpos($_SERVER['REQUEST_URI'], '/api/') !== false) {
        header('Content-Type: application/json');
        echo json_encode(["status" => "error", "message" => $error_msg]);
        exit;
    }
    die($error_msg);
}
