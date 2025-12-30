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

if($_SERVER["REQUEST_METHOD"] == "POST"){
    $room_id = (int)$_POST['room_id'];
    $target_id = (int)$_POST['target_id'];
    $action_type = mysqli_real_escape_string($link, $_POST['action_type']); // 'kill', 'save', 'investigate'
    $user_id = $_SESSION['id'];

    // 1. Verify user's role and if they are alive
    $sql = "SELECT role, is_alive FROM room_players WHERE room_id = $room_id AND user_id = $user_id";
    $res = mysqli_query($link, $sql);
    $player = ($res) ? mysqli_fetch_assoc($res) : null;

    if(!$player || !$player['is_alive']){
        echo json_encode(["status" => "error", "message" => "You cannot perform actions."]);
        exit;
    }

    $role = $player['role'];

    // 1.5 Verify if it's the correct turn
    $sql_turn = "SELECT current_turn FROM rooms WHERE id = $room_id AND phase = 'night'";
    $res_turn = mysqli_query($link, $sql_turn);
    $room_turn_data = ($res_turn) ? mysqli_fetch_assoc($res_turn) : null;

    if(!$room_turn_data){
        echo json_encode(["status" => "error", "message" => "It is not night phase."]);
        exit;
    }

    $current_turn = $room_turn_data['current_turn'];
    $role_map = [
        'Killer' => 'Killer',
        'Doctor' => 'Doctor',
        'Detective' => 'Detective'
    ];

    if(!isset($role_map[$role]) || $current_turn != $role_map[$role]){
        echo json_encode(["status" => "error", "message" => "It is not your turn yet."]);
        exit;
    }

    // 2. Update room with action and move to next turn
    $update_sql = "";
    $next_turn = "None";
    if($action_type == 'kill' && $role == 'Killer'){
        $update_sql = "UPDATE rooms SET killer_target = $target_id, action_count = action_count + 1 WHERE id = $room_id AND phase = 'night'";
        $next_turn = 'Doctor';
    } elseif($action_type == 'save' && $role == 'Doctor'){
        $update_sql = "UPDATE rooms SET doctor_target = $target_id, action_count = action_count + 1 WHERE id = $room_id AND phase = 'night'";
        $next_turn = 'Detective';
    } elseif($action_type == 'investigate' && $role == 'Detective'){
        $update_sql = "UPDATE rooms SET detective_target = $target_id, action_count = action_count + 1 WHERE id = $room_id AND phase = 'night'";
        $next_turn = 'None';
    }

    if($update_sql && mysqli_query($link, $update_sql)){
        // Find next alive role
        $current_turn = $next_turn;
        while($current_turn != 'None'){
            $sql_check = "SELECT id FROM room_players WHERE room_id = $room_id AND role = '$current_turn' AND is_alive = 1";
            $res_check = mysqli_query($link, $sql_check);
            $role_exists = ($res_check && mysqli_num_rows($res_check) > 0);

            if(!$role_exists){
                $next_map = [
                    'Killer' => 'Doctor',
                    'Doctor' => 'Detective',
                    'Detective' => 'None'
                ];
                $current_turn = $next_map[$current_turn];
            } else {
                break;
            }
        }

        $sql_update_turn = "UPDATE rooms SET current_turn = '$current_turn' WHERE id = $room_id";
        mysqli_query($link, $sql_update_turn);

        // If investigation, return if the target is Good or Bad
        $extra = [];
        if($action_type == 'investigate'){
            $sql_target = "SELECT role FROM room_players WHERE room_id = $room_id AND user_id = $target_id";
            $res_t = mysqli_query($link, $sql_target);
            $target_player = ($res_t) ? mysqli_fetch_assoc($res_t) : null;
            
            if($target_player){
                if($target_player['role'] == 'Killer'){
                    $extra['result'] = 'Bad';
                } else {
                    $extra['result'] = 'Good';
                }
            } else {
                $extra['result'] = 'Unknown';
            }
        }
        
        echo json_encode(array_merge(["status" => "success", "message" => "Action recorded"], $extra));
    } else {
        echo json_encode(["status" => "error", "message" => "Failed to record action or invalid action."]);
    }
}