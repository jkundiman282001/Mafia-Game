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

    // Fetch current room state
    $sql = "SELECT phase, killer_target, doctor_target FROM rooms WHERE id = ?";
    $stmt = mysqli_prepare($link, $sql);
    mysqli_stmt_bind_param($stmt, "i", $room_id);
    mysqli_stmt_execute($stmt);
    $room = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

    if($room['phase'] == 'night'){
        // Process night results
        $killed_id = $room['killer_target'];
        $saved_id = $room['doctor_target'];
        
        $message = "The night has ended. ";
        $death_event = false;

        if($killed_id && $killed_id != $saved_id){
            // Someone died
            $update_player = "UPDATE room_players SET is_alive = 0 WHERE room_id = ? AND user_id = ?";
            $stmt_up = mysqli_prepare($link, $update_player);
            mysqli_stmt_bind_param($stmt_up, "ii", $room_id, $killed_id);
            mysqli_stmt_execute($stmt_up);

            // Get username of the deceased
            $sql_user = "SELECT username FROM users WHERE id = ?";
            $stmt_u = mysqli_prepare($link, $sql_user);
            mysqli_stmt_bind_param($stmt_u, "i", $killed_id);
            mysqli_stmt_execute($stmt_u);
            $user_data = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_u));
            $username = $user_data['username'];
            
            $message .= "$username was killed during the night.";
            $death_event = true;
        } else {
            $message .= "Fortunately, nobody died tonight.";
        }

        // Add to messages table so everyone sees it in chat
        $sql_msg = "INSERT INTO messages (room_id, user_id, message) VALUES (?, NULL, ?)";
        $stmt_m = mysqli_prepare($link, $sql_msg);
        mysqli_stmt_bind_param($stmt_m, "is", $room_id, $message);
        mysqli_stmt_execute($stmt_m);

        // Transition to Day
        $update_room = "UPDATE rooms SET phase = 'day', action_count = 0, current_turn = 'None', killer_target = NULL, doctor_target = NULL, detective_target = NULL WHERE id = ?";
        $stmt_ur = mysqli_prepare($link, $update_room);
        mysqli_stmt_bind_param($stmt_ur, "i", $room_id);
        mysqli_stmt_execute($stmt_ur);

        echo json_encode(["status" => "success", "message" => "Transitioned to Day phase", "news" => $message]);
    } else {
        // Day to Night transition (voting would happen here, but for now just skip)
        $update_room = "UPDATE rooms SET phase = 'night', round = round + 1, action_count = 0, current_turn = 'Killer' WHERE id = ?";
        $stmt_ur = mysqli_prepare($link, $update_room);
        mysqli_stmt_bind_param($stmt_ur, "i", $room_id);
        mysqli_stmt_execute($stmt_ur);

        $msg = "Day has ended. Night falls upon the town.";
        $sql_msg = "INSERT INTO messages (room_id, user_id, message) VALUES (?, NULL, ?)";
        $stmt_m = mysqli_prepare($link, $sql_msg);
        mysqli_stmt_bind_param($stmt_m, "is", $room_id, $msg);
        mysqli_stmt_execute($stmt_m);

        echo json_encode(["status" => "success", "message" => "Transitioned to Night phase"]);
    }
}
?>