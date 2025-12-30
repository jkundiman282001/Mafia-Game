<?php
/* Database credentials. Assuming you are running MySQL
server with default setting (user 'root' with no password) */

// Use Environment Variables for Vercel/Production, fallback to local defaults
$db_server = getenv('DB_SERVER') ?: 'switchyard.proxy.rlwy.net';
$db_username = getenv('DB_USERNAME') ?: 'root';
$db_password = getenv('DB_PASSWORD') ?: 'KMSXVeVzAkhhEVRRVJjJsOSJyaMJwljx';
$db_name = getenv('DB_NAME') ?: 'railway';
$db_port = getenv('DB_PORT') ?: 45221;

/* Attempt to connect to MySQL database */$db_database = getenv('DB_DATABASE') ?:'';
if ($db_port) {
    $link = mysqli_connect($db_server, $db_username, $db_password, $db_name, (int)$db_port);
} else {
    $link = mysqli_connect($db_server, $db_username, $db_password, $db_name);
}

// Check connection
if($link === false){
    die("ERROR: Could not connect. " . mysqli_connect_error());
}
