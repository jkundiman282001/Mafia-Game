<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . "/includes/config.php";
// Skip session for a moment to debug 500 error
session_start(); 

header('Content-Type: application/json');

if (!$link) {
    echo json_encode(["status" => "error", "message" => "Database connection failed in get_room_status.php"]);
    exit;
}

if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    echo json_encode(["status" => "error", "message" => "Unauthorized", "session" => $_SESSION]);
    exit;
}

if(isset($_GET["room_id"])){
    $room_id = (int)$_GET["room_id"];
    
    $sql = "SELECT status, phase, round, killer_target, doctor_target, detective_target, action_count, current_turn, phase_start_time, NOW() as current_time FROM rooms WHERE id = $room_id";
    $res_room_query = mysqli_query($link, $sql);
    
    if($res_room_query && $row = mysqli_fetch_assoc($res_room_query)){
        // Turn skipping logic
        $current_turn = $row["current_turn"];
        $phase = $row["phase"];
        $time_remaining = 0;

        if($phase == 'day' && !empty($row['phase_start_time'])){
            $start_time = strtotime($row['phase_start_time']);
            $curr_time = strtotime($row['current_time']);
            $elapsed = $curr_time - $start_time;
            $duration = 180; // 3 minutes in seconds
            $time_remaining = max(0, $duration - (int)$elapsed);
        }
        
        if($phase == 'night' && $current_turn != 'None' && !empty($current_turn)){
            $changed = false;
            $next_turns = [
                'Killer' => 'Doctor',
                'Doctor' => 'Detective',
                'Detective' => 'None'
            ];
            
            $loop_limit = 5; 
            $loop_count = 0;
            
            while($current_turn != 'None' && isset($next_turns[$current_turn]) && $loop_count < $loop_limit){
                $loop_count++;
                $sql_check = "SELECT id FROM room_players WHERE room_id = $room_id AND role = '$current_turn' AND is_alive = 1";
                $res_check = mysqli_query($link, $sql_check);
                $role_exists = ($res_check && mysqli_num_rows($res_check) > 0);

                if(!$role_exists){
                    $current_turn = $next_turns[$current_turn];
                    $changed = true;
                } else {
                    break;
                }
            }

            if($changed){
                $sql_update = "UPDATE rooms SET current_turn = '$current_turn' WHERE id = $room_id";
                mysqli_query($link, $sql_update);
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
        echo json_encode(["status" => "error", "message" => "Room not found or DB error: " . mysqli_error($link)]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "Missing room_id"]);
}