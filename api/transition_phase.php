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

    // Fetch current room state
    $sql = "SELECT phase, killer_target, doctor_target, current_turn FROM rooms WHERE id = $room_id";
    $res_room = mysqli_query($link, $sql);
    $room = ($res_room) ? mysqli_fetch_assoc($res_room) : null;

    if(!$room){
        echo json_encode(["status" => "error", "message" => "Room not found"]);
        exit;
    }

    if($room['phase'] == 'night'){
        if($room['current_turn'] !== 'None'){
            echo json_encode(["status" => "error", "message" => "Night turns are not completed yet."]);
            exit;
        }
        // Process night results
        $killed_id = $room['killer_target'];
        $saved_id = $room['doctor_target'];
        
        $message = "The night has ended. ";
        $death_event = false;

        if($killed_id && $killed_id != $saved_id){
            // Someone died
            $update_player = "UPDATE room_players SET is_alive = 0 WHERE room_id = $room_id AND user_id = $killed_id";
            mysqli_query($link, $update_player);

            // Get username of the deceased
            $sql_user = "SELECT username FROM users WHERE id = $killed_id";
            $res_user = mysqli_query($link, $sql_user);
            $user_data = ($res_user) ? mysqli_fetch_assoc($res_user) : null;
            $username = $user_data['username'] ?? "Someone";
            
            $message .= "$username was killed during the night.";
            $death_event = true;
        } else {
            $message .= "Fortunately, nobody died tonight.";
        }

        // Add to messages table so everyone sees it in chat
        $safe_message = mysqli_real_escape_string($link, $message);
        $sql_msg = "INSERT INTO messages (room_id, user_id, message) VALUES ($room_id, NULL, '$safe_message')";
        mysqli_query($link, $sql_msg);

        // Check for Win Conditions after night kills
        $sql_win = "SELECT role, COUNT(*) as count FROM room_players WHERE room_id = $room_id AND is_alive = 1 GROUP BY role";
        $res_win = mysqli_query($link, $sql_win);
        
        $alive_roles = [];
        $total_alive = 0;
        if($res_win){
            while($row_win = mysqli_fetch_assoc($res_win)){
                $alive_roles[$row_win['role']] = $row_win['count'];
                $total_alive += $row_win['count'];
            }
        }

        $killer_count = $alive_roles['Killer'] ?? 0;
        $town_count = $total_alive - $killer_count;

        if($killer_count == 0){
            $final_msg = "VICTORY! All killers have been eliminated. The town is safe.";
            $update_game = "UPDATE rooms SET status = 'finished' WHERE id = $room_id";
            mysqli_query($link, $update_game);
            echo json_encode(["status" => "success", "message" => "Game Finished", "news" => $final_msg]);
            exit;
        } elseif($killer_count >= $town_count){
            $final_msg = "DEFEAT! The killer has outnumbered the town. Chaos reigns.";
            $update_game = "UPDATE rooms SET status = 'finished' WHERE id = $room_id";
            mysqli_query($link, $update_game);
            echo json_encode(["status" => "success", "message" => "Game Finished", "news" => $final_msg]);
            exit;
        }

        // Move to Day phase
        $update_room = "UPDATE rooms SET phase = 'day', round = round + 1, killer_target = NULL, doctor_target = NULL, detective_target = NULL, current_turn = 'None', action_count = 0, phase_start_time = NOW() WHERE id = $room_id";
        if(mysqli_query($link, $update_room)){
            echo json_encode(["status" => "success", "message" => "Transitioned to Day", "news" => $message]);
        } else {
            echo json_encode(["status" => "error", "message" => "Failed to update room phase: " . mysqli_error($link)]);
        }
    } else {
        // Transition Day to Night (Processing votes is done in process_voting.php, this just forces transition if needed)
        $update_room = "UPDATE rooms SET phase = 'night', current_turn = 'Killer', action_count = 0, phase_start_time = NOW() WHERE id = $room_id";
        if(mysqli_query($link, $update_room)){
            echo json_encode(["status" => "success", "message" => "Transitioned to Night"]);
        } else {
            echo json_encode(["status" => "error", "message" => "Failed to update room phase: " . mysqli_error($link)]);
        }
    }
}