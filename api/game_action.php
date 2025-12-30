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
    $room_id = $_POST['room_id'];
    $target_id = $_POST['target_id'];
    $action_type = $_POST['action_type']; // 'kill', 'save', 'investigate'
    $user_id = $_SESSION['id'];

    // 1. Verify user's role and if they are alive
    $sql = "SELECT role, is_alive FROM room_players WHERE room_id = ? AND user_id = ?";
    $stmt = mysqli_prepare($link, $sql);
    mysqli_stmt_bind_param($stmt, "ii", $room_id, $user_id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $player = mysqli_fetch_assoc($res);

    if(!$player || !$player['is_alive']){
        echo json_encode(["status" => "error", "message" => "You cannot perform actions."]);
        exit;
    }

    $role = $player['role'];

    // 1.5 Verify if it's the correct turn
    $sql_turn = "SELECT current_turn FROM rooms WHERE id = ? AND phase = 'night'";
    $stmt_turn = mysqli_prepare($link, $sql_turn);
    mysqli_stmt_bind_param($stmt_turn, "i", $room_id);
    mysqli_stmt_execute($stmt_turn);
    $room_turn_data = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_turn));

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

    if($current_turn != $role_map[$role]){
        echo json_encode(["status" => "error", "message" => "It is not your turn yet."]);
        exit;
    }

    // 2. Update room with action and move to next turn
    $update_sql = "";
    $next_turn = "None";
    if($action_type == 'kill' && $role == 'Killer'){
        $update_sql = "UPDATE rooms SET killer_target = ?, action_count = action_count + 1, current_turn = 'Doctor' WHERE id = ? AND phase = 'night'";
    } elseif($action_type == 'save' && $role == 'Doctor'){
        $update_sql = "UPDATE rooms SET doctor_target = ?, action_count = action_count + 1, current_turn = 'Detective' WHERE id = ? AND phase = 'night'";
    } elseif($action_type == 'investigate' && $role == 'Detective'){
        $update_sql = "UPDATE rooms SET detective_target = ?, action_count = action_count + 1, current_turn = 'None' WHERE id = ? AND phase = 'night'";
    }

    if($update_sql){
        $stmt_u = mysqli_prepare($link, $update_sql);
        mysqli_stmt_bind_param($stmt_u, "ii", $target_id, $room_id);
        if(mysqli_stmt_execute($stmt_u)){
            // If investigation, return if the target is Good or Bad
            $extra = [];
            if($action_type == 'investigate'){
                $sql_target = "SELECT role FROM room_players WHERE room_id = ? AND user_id = ?";
                $stmt_t = mysqli_prepare($link, $sql_target);
                mysqli_stmt_bind_param($stmt_t, "ii", $room_id, $target_id);
                mysqli_stmt_execute($stmt_t);
                $res_t = mysqli_stmt_get_result($stmt_t);
                $target_player = mysqli_fetch_assoc($res_t);
                
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
            echo json_encode(["status" => "error", "message" => "Failed to record action: " . mysqli_error($link)]);
        }
    } else {
        echo json_encode(["status" => "error", "message" => "Invalid action for your role or turn."]);
    }
}
?>