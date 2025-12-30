<?php
require_once __DIR__ . "/includes/session.php";
require_once __DIR__ . "/includes/config.php";

ini_set('display_errors', 0);
error_reporting(E_ALL);

header('Content-Type: application/json');

// Check if user is logged in
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    echo json_encode(["status" => "error", "message" => "Unauthorized - Please log in again."]);
    exit;
}

if($_SERVER["REQUEST_METHOD"] == "POST"){
    $room_id = (int)$_POST["room_id"];
    $user_id = (int)$_SESSION["id"];
    $message = trim($_POST["message"]);

    if(!empty($message)){
        $escaped_message = mysqli_real_escape_string($link, $message);
        $sql = "INSERT INTO messages (room_id, user_id, message) VALUES ($room_id, $user_id, '$escaped_message')";
        if(mysqli_query($link, $sql)){
            echo json_encode(["status" => "success"]);
        } else {
            echo json_encode(["status" => "error", "message" => "Database error: " . mysqli_error($link)]);
        }
    } else {
        echo json_encode(["status" => "error", "message" => "Message cannot be empty."]);
    }
}
