<?php
require_once __DIR__ . "/includes/session.php";
require_once __DIR__ . "/includes/config.php";

ini_set('display_errors', 0);
error_reporting(E_ALL);

header('Content-Type: application/json');

// Check if user is logged in
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    echo json_encode(["status" => "error", "message" => "Unauthorized"]);
    exit;
}

if(isset($_GET["room_id"])){
    $room_id = $_GET["room_id"];
    
    $sql = "SELECT m.message, m.created_at, u.username, m.user_id 
            FROM messages m 
            LEFT JOIN users u ON m.user_id = u.id 
            WHERE m.room_id = ? 
            ORDER BY m.created_at ASC";
            
    if($stmt = mysqli_prepare($link, $sql)){
        mysqli_stmt_bind_param($stmt, "i", $room_id);
        if(mysqli_stmt_execute($stmt)){
            $result = mysqli_stmt_get_result($stmt);
            
            $messages = [];
            if($result){
                while($row = mysqli_fetch_assoc($result)){
                    if($row['user_id'] === null){
                        $row['username'] = 'SYSTEM';
                    }
                    $messages[] = $row;
                }
            }
            
            echo json_encode(["status" => "success", "messages" => $messages]);
        } else {
            echo json_encode(["status" => "error", "message" => "Database error: " . mysqli_error($link)]);
        }
        mysqli_stmt_close($stmt);
    } else {
        echo json_encode(["status" => "error", "message" => "Prepare error: " . mysqli_error($link)]);
    }
}
