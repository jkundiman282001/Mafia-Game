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
    $room_id = $_POST["room_id"];
    $user_id = $_SESSION["id"];
    $message = trim($_POST["message"]);

    if(!empty($message)){
        $sql = "INSERT INTO messages (room_id, user_id, message) VALUES (?, ?, ?)";
        if($stmt = mysqli_prepare($link, $sql)){
            mysqli_stmt_bind_param($stmt, "iis", $room_id, $user_id, $message);
            if(mysqli_stmt_execute($stmt)){
                echo json_encode(["status" => "success"]);
            } else {
                echo json_encode(["status" => "error", "message" => "Database error: " . mysqli_error($link)]);
            }
            mysqli_stmt_close($stmt);
        } else {
            echo json_encode(["status" => "error", "message" => "Prepare error: " . mysqli_error($link)]);
        }
    } else {
        echo json_encode(["status" => "error", "message" => "Message cannot be empty."]);
    }
}
