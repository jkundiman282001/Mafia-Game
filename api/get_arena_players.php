<?php
require_once __DIR__ . "/includes/session.php";
require_once __DIR__ . "/includes/config.php";

ini_set('display_errors', 0);
error_reporting(E_ALL);

header('Content-Type: application/json');

if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    echo json_encode(["status" => "error", "message" => "Unauthorized"]);
    exit;
}

if(isset($_GET["room_id"])){
    $room_id = $_GET["room_id"];
    
    $sql = "SELECT rp.user_id, u.username, rp.is_alive, rp.role 
            FROM room_players rp 
            JOIN users u ON rp.user_id = u.id 
            WHERE rp.room_id = ?";
            
    if($stmt = mysqli_prepare($link, $sql)){
        mysqli_stmt_bind_param($stmt, "i", $room_id);
        if(mysqli_stmt_execute($stmt)){
            $result = mysqli_stmt_get_result($stmt);
            $players = [];
            while($row = mysqli_fetch_assoc($result)){
                $players[] = [
                    "user_id" => $row["user_id"],
                    "username" => $row["username"],
                    "is_alive" => (bool)$row["is_alive"]
                ];
            }
            echo json_encode(["status" => "success", "players" => $players]);
        } else {
            echo json_encode(["status" => "error", "message" => "Database error"]);
        }
        mysqli_stmt_close($stmt);
    }
} else {
    echo json_encode(["status" => "error", "message" => "Missing room_id"]);
}
?>