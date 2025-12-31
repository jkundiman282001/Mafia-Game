<?php
// Only display errors if not in an API call or if explicitly requested
if (strpos($_SERVER['REQUEST_URI'], '/api/') === false && !isset($_GET['debug'])) {
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
    error_reporting(0);
} else if (isset($_GET['debug'])) {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
} else {
    // Default for API: no display, but log them
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
    error_reporting(E_ALL); // Log all, but don't display
}

/* Database credentials. Assuming you are running MySQL */

// Use Environment Variables (Railway defaults or custom), fallback to local defaults
$db_server = getenv('MYSQLHOST') ?: getenv('DB_SERVER') ?: 'switchyard.proxy.rlwy.net';
$db_username = getenv('MYSQLUSER') ?: getenv('DB_USERNAME') ?: 'root';
$db_password = getenv('MYSQLPASSWORD') ?: getenv('DB_PASSWORD') ?: 'KMSXVeVzAkhhEVRRVJjJsOSJyaMJwljx';
$db_name = getenv('MYSQLDATABASE') ?: getenv('DB_NAME') ?: 'railway';
$db_port = getenv('MYSQLPORT') ?: getenv('DB_PORT') ?: 45221;

if (!extension_loaded('mysqli')) {
    header('Content-Type: application/json');
    echo json_encode(["status" => "error", "message" => "mysqli extension is not loaded in this PHP environment."]);
    exit;
}

/* Attempt to connect to MySQL database */
if ($db_port) {
    $link = mysqli_connect($db_server, $db_username, $db_password, $db_name, (int)$db_port);
} else {
    $link = mysqli_connect($db_server, $db_username, $db_password, $db_name);
}

// Check connection
if($link === false){
    $error_msg = "ERROR: Could not connect to database. " . mysqli_connect_error();
    // Check if this is an API call (including Vercel routed paths)
    $is_api = (strpos($_SERVER['REQUEST_URI'], '/api/') !== false) || 
              (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) ||
              (isset($_GET['room_id'])); // common for our APIs

    if ($is_api) {
        header('Content-Type: application/json', true, 500);
        echo json_encode(["status" => "error", "message" => $error_msg]);
        exit;
    }
    die($error_msg);
}
