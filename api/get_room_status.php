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
    
    $sql = "SELECT status, phase, round, killer_target, doctor_target, action_count FROM rooms WHERE id = ?";
    if($stmt = mysqli_prepare($link, $sql)){
        mysqli_stmt_bind_param($stmt, "i", $room_id);
        if(mysqli_stmt_execute($stmt)){
            $result = mysqli_stmt_get_result($stmt);
            if($row = mysqli_fetch_assoc($result)){
                echo json_encode([
                    "status" => "success", 
                    "room_status" => $row["status"],
                    "phase" => $row["phase"],
                    "round" => $row["round"],
                    "killer_target" => $row["killer_target"],
                    "doctor_target" => $row["doctor_target"],
                    "action_count" => $row["action_count"]
                ]);
            } else {
                echo json_encode(["status" => "error", "message" => "Room not found"]);
            }
        } else {
            echo json_encode(["status" => "error", "message" => "Database error"]);
        }
        mysqli_stmt_close($stmt);
    }
} else {
    echo json_encode(["status" => "error", "message" => "Missing room_id"]);
}
?>