<?php
require_once __DIR__ . "/includes/session.php";
require_once __DIR__ . "/includes/config.php";

ini_set('display_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');

if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    echo json_encode(["status" => "error", "message" => "Unauthorized"]);
    exit;
}

if(isset($_GET["room_id"])){
    $room_id = $_GET["room_id"];
    
    $sql = "SELECT status, phase, round, killer_target, doctor_target, detective_target, action_count, current_turn, phase_start_time, NOW() as current_time FROM rooms WHERE id = ?";
    if($stmt = mysqli_prepare($link, $sql)){
        mysqli_stmt_bind_param($stmt, "i", $room_id);
        if(mysqli_stmt_execute($stmt)){
            $result = mysqli_stmt_get_result($stmt);
            if($row = mysqli_fetch_assoc($result)){
                // Turn skipping logic
                $current_turn = $row["current_turn"];
                $phase = $row["phase"];
                $time_remaining = 0;

                if($phase == 'day' && $row['phase_start_time']){
                    $start_time = strtotime($row['phase_start_time']);
                    $current_time = strtotime($row['current_time']);
                    $elapsed = $current_time - $start_time;
                    $duration = 180; // 3 minutes in seconds
                    $time_remaining = max(0, $duration - $elapsed);
                }
                
                if($phase == 'night' && $current_turn != 'None' && $current_turn != ''){
                    $changed = false;
                    $next_turns = [
                        'Killer' => 'Doctor',
                        'Doctor' => 'Detective',
                        'Detective' => 'None'
                    ];
                    
                    while($current_turn != 'None' && isset($next_turns[$current_turn])){
                        // Check if any alive player has this role
                        $sql_check = "SELECT id FROM room_players WHERE room_id = ? AND role = ? AND is_alive = 1";
                        if($stmt_check = mysqli_prepare($link, $sql_check)){
                            mysqli_stmt_bind_param($stmt_check, "is", $room_id, $current_turn);
                            mysqli_stmt_execute($stmt_check);
                            mysqli_stmt_store_result($stmt_check);
                            $role_exists = mysqli_stmt_num_rows($stmt_check) > 0;
                            mysqli_stmt_close($stmt_check);

                            if(!$role_exists){
                                // Skip turn
                                $current_turn = $next_turns[$current_turn];
                                $changed = true;
                            } else {
                                // Role is alive, stay on this turn
                                break;
                            }
                        } else {
                            break;
                        }
                    }

                    if($changed){
                        $sql_update = "UPDATE rooms SET current_turn = ? WHERE id = ?";
                        $stmt_up = mysqli_prepare($link, $sql_update);
                        mysqli_stmt_bind_param($stmt_up, "si", $current_turn, $room_id);
                        mysqli_stmt_execute($stmt_up);
                        mysqli_stmt_close($stmt_up);
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
                    "current_turn" => $current_turn,
                    "time_remaining" => $time_remaining
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