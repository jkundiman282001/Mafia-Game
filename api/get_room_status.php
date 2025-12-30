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
    
    $sql = "SELECT status, phase, round, killer_target, doctor_target, detective_target, action_count, current_turn FROM rooms WHERE id = ?";
    if($stmt = mysqli_prepare($link, $sql)){
        mysqli_stmt_bind_param($stmt, "i", $room_id);
        if(mysqli_stmt_execute($stmt)){
            $result = mysqli_stmt_get_result($stmt);
            if($row = mysqli_fetch_assoc($result)){
                // Turn skipping logic
                $current_turn = $row["current_turn"];
                $phase = $row["phase"];
                
                if($phase == 'night' && $current_turn != 'None'){
                    $role_to_check = $current_turn;
                    // Check if any alive player has this role
                    $sql_check = "SELECT id FROM room_players WHERE room_id = ? AND role = ? AND is_alive = 1";
                    $stmt_check = mysqli_prepare($link, $sql_check);
                    mysqli_stmt_bind_param($stmt_check, "is", $room_id, $role_to_check);
                    mysqli_stmt_execute($stmt_check);
                    mysqli_stmt_store_result($stmt_check);
                    
                    if(mysqli_stmt_num_rows($stmt_check) == 0){
                        // Skip turn
                        $next_turns = [
                            'Killer' => 'Doctor',
                            'Doctor' => 'Detective',
                            'Detective' => 'None'
                        ];
                        $new_turn = $next_turns[$current_turn];
                        $sql_update = "UPDATE rooms SET current_turn = ? WHERE id = ?";
                        $stmt_up = mysqli_prepare($link, $sql_update);
                        mysqli_stmt_bind_param($stmt_up, "si", $new_turn, $room_id);
                        mysqli_stmt_execute($stmt_up);
                        $current_turn = $new_turn;
                    }
                }

                echo json_encode([
                    "status" => "success", 
                    "room_status" => $row["status"],
                    "phase" => $row["phase"],
                    "round" => $row["round"],
                    "killer_target" => $row["killer_target"],
                    "doctor_target" => $row["doctor_target"],
                    "detective_target" => $row["detective_target"],
                    "action_count" => $row["action_count"],
                    "current_turn" => $current_turn
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