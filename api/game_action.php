<?php
require_once __DIR__ . "/includes/session.php";
require_once __DIR__ . "/includes/config.php";

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

    // 2. Update room with action
    $update_sql = "";
    if($action_type == 'kill' && $role == 'Killer'){
        $update_sql = "UPDATE rooms SET killer_target = ?, action_count = action_count + 1 WHERE id = ? AND phase = 'night'";
    } elseif($action_type == 'save' && $role == 'Doctor'){
        $update_sql = "UPDATE rooms SET doctor_target = ?, action_count = action_count + 1 WHERE id = ? AND phase = 'night'";
    } elseif($action_type == 'investigate' && $role == 'Detective'){
        // Investigation is immediate for the player but needs to increment action count
        $update_sql = "UPDATE rooms SET detective_target = ?, action_count = action_count + 1 WHERE id = ? AND phase = 'night'";
    }

    if($update_sql){
        $stmt_u = mysqli_prepare($link, $update_sql);
        mysqli_stmt_bind_param($stmt_u, "ii", $target_id, $room_id);
        if(mysqli_stmt_execute($stmt_u)){
            // If investigation, return the target's role
            $extra = [];
            if($action_type == 'investigate'){
                $sql_target = "SELECT role FROM room_players WHERE room_id = ? AND user_id = ?";
                $stmt_t = mysqli_prepare($link, $sql_target);
                mysqli_stmt_bind_param($stmt_t, "ii", $room_id, $target_id);
                mysqli_stmt_execute($stmt_t);
                $res_t = mysqli_stmt_get_result($stmt_t);
                $target_player = mysqli_fetch_assoc($res_t);
                $extra['role'] = $target_player['role'];
            }
            
            // Check if all actions are done to transition to day
            // For now, let's assume 3 main actions (Killer, Doctor, Detective)
            // We can make this dynamic later based on living roles
            $sql_room = "SELECT action_count FROM rooms WHERE id = ?";
            $stmt_r = mysqli_prepare($link, $sql_room);
            mysqli_stmt_bind_param($stmt_r, "i", $room_id);
            mysqli_stmt_execute($stmt_r);
            $room_res = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_r));
            
            // Trigger phase check/transition if needed (we'll implement this logic in a separate function or endpoint)
            
            echo json_encode(array_merge(["status" => "success", "message" => "Action recorded"], $extra));
        } else {
            echo json_encode(["status" => "error", "message" => "Failed to record action"]);
        }
    } else {
        echo json_encode(["status" => "error", "message" => "Invalid action for your role."]);
    }
}
?>