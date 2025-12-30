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

        // Check for Win Conditions after night kills
        $sql_win = "SELECT role, COUNT(*) as count FROM room_players WHERE room_id = ? AND is_alive = 1 GROUP BY role";
        $stmt_win = mysqli_prepare($link, $sql_win);
        mysqli_stmt_bind_param($stmt_win, "i", $room_id);
        mysqli_stmt_execute($stmt_win);
        $res_win = mysqli_stmt_get_result($stmt_win);
        
        $alive_roles = [];
        $total_alive = 0;
        while($row_win = mysqli_fetch_assoc($res_win)){
            $alive_roles[$row_win['role']] = $row_win['count'];
            $total_alive += $row_win['count'];
        }

        $killer_count = $alive_roles['Killer'] ?? 0;
        $town_count = $total_alive - $killer_count;

        if($killer_count == 0){
            $final_msg = "VICTORY! All killers have been eliminated. The town is safe.";
            $update_game = "UPDATE rooms SET status = 'finished' WHERE id = ?";
            $stmt_g = mysqli_prepare($link, $update_game);
            mysqli_stmt_bind_param($stmt_g, "i", $room_id);
            mysqli_stmt_execute($stmt_g);
            echo json_encode(["status" => "success", "message" => "Game Finished", "news" => $final_msg]);
            exit;
        } elseif($killer_count >= $town_count){
            $final_msg = "DEFEAT! The killer has outnumbered the town. Chaos reigns.";
            $update_game = "UPDATE rooms SET status = 'finished' WHERE id = ?";
            $stmt_g = mysqli_prepare($link, $update_game);
            mysqli_stmt_bind_param($stmt_g, "i", $room_id);
            mysqli_stmt_execute($stmt_g);
            echo json_encode(["status" => "success", "message" => "Game Finished", "news" => $final_msg]);
            exit;
        }

        // Transition to Day
        $update_room = "UPDATE rooms SET phase = 'day', action_count = 0, current_turn = 'None', killer_target = NULL, doctor_target = NULL, detective_target = NULL, phase_start_time = NOW() WHERE id = ?";
        $stmt_ur = mysqli_prepare($link, $update_room);
        mysqli_stmt_bind_param($stmt_ur, "i", $room_id);
        mysqli_stmt_execute($stmt_ur);

        echo json_encode(["status" => "success", "message" => "Transitioned to Day phase", "news" => $message]);
    } else {
        // Day to Night transition (voting would happen here, but for now just skip)
        $current_turn = 'Killer';
        while($current_turn != 'None'){
            $sql_check = "SELECT id FROM room_players WHERE room_id = ? AND role = ? AND is_alive = 1";
            $stmt_check = mysqli_prepare($link, $sql_check);
            mysqli_stmt_bind_param($stmt_check, "is", $room_id, $current_turn);
            mysqli_stmt_execute($stmt_check);
            mysqli_stmt_store_result($stmt_check);
            $role_exists = mysqli_stmt_num_rows($stmt_check) > 0;
            mysqli_stmt_close($stmt_check);

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

        $update_room = "UPDATE rooms SET phase = 'night', round = round + 1, action_count = 0, current_turn = ? WHERE id = ?";
        $stmt_ur = mysqli_prepare($link, $update_room);
        mysqli_stmt_bind_param($stmt_ur, "si", $current_turn, $room_id);
        mysqli_stmt_execute($stmt_ur);

        $msg = "Day has ended. Night falls upon the town.";
        $sql_msg = "INSERT INTO messages (room_id, user_id, message) VALUES (?, NULL, ?)";
        $stmt_m = mysqli_prepare($link, $sql_msg);
        mysqli_stmt_bind_param($stmt_m, "is", $room_id, $msg);
        mysqli_stmt_execute($stmt_m);

        echo json_encode(["status" => "success", "message" => "Transitioned to Night phase"]);
    }
}