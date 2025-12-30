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
    $user_id = (int)$_SESSION['id'];

    // 1. Verify if requester is the host
    $sql_host = "SELECT creator_id, phase, round FROM rooms WHERE id = $room_id";
    $res_room = mysqli_query($link, $sql_host);
    $room = $res_room ? mysqli_fetch_assoc($res_room) : null;

    if(!$room || $room['creator_id'] != $user_id){
        echo json_encode(["status" => "error", "message" => "Only the host can process voting."]);
        exit;
    }

    if($room['phase'] !== 'day'){
        echo json_encode(["status" => "error", "message" => "Not in day phase."]);
        exit;
    }

    $round = (int)$room['round'];

    // 2. Count votes
    $sql_votes = "SELECT target_id, COUNT(*) as vote_count 
                  FROM votes 
                  WHERE room_id = $room_id AND round = $round 
                  GROUP BY target_id 
                  ORDER BY vote_count DESC";
    $result_v = mysqli_query($link, $sql_votes);
    
    $top_votes = [];
    if($result_v){
        while($row = mysqli_fetch_assoc($result_v)){
            $top_votes[] = $row;
        }
    }

    $message = "The town has finished discussing. ";
    $eliminated_id = null;

    if(count($top_votes) > 0){
        // Check for tie
        if(count($top_votes) > 1 && $top_votes[0]['vote_count'] == $top_votes[1]['vote_count']){
            $message .= "The vote ended in a tie. Nobody was eliminated.";
        } else {
            $eliminated_id = (int)$top_votes[0]['target_id'];
            
            // Eliminate player
            $sql_elim = "UPDATE room_players SET is_alive = 0 WHERE room_id = $room_id AND user_id = $eliminated_id";
            mysqli_query($link, $sql_elim);

            // Get username
            $sql_user = "SELECT username FROM users WHERE id = $eliminated_id";
            $res_u = mysqli_query($link, $sql_user);
            $username = ($res_u && $row_u = mysqli_fetch_assoc($res_u)) ? $row_u['username'] : "A player";
            $message .= "$username has been eliminated by the town.";
        }
    } else {
        $message .= "Nobody voted. Nobody was eliminated.";
    }

    // 3. Add system message
    $escaped_message = mysqli_real_escape_string($link, $message);
    $sql_msg = "INSERT INTO messages (room_id, user_id, message) VALUES ($room_id, NULL, '$escaped_message')";
    mysqli_query($link, $sql_msg);

    // 4. Check for Win Conditions
    $sql_win = "SELECT role, COUNT(*) as count FROM room_players WHERE room_id = $room_id AND is_alive = 1 GROUP BY role";
    $res_win = mysqli_query($link, $sql_win);
    
    $alive_roles = [];
    $total_alive = 0;
    if($res_win){
        while($row_win = mysqli_fetch_assoc($res_win)){
            $alive_roles[$row_win['role']] = (int)$row_win['count'];
            $total_alive += (int)$row_win['count'];
        }
    }

    $killer_count = $alive_roles['Killer'] ?? 0;
    $town_count = $total_alive - $killer_count;

    if($killer_count == 0){
        // Town wins
        $final_msg = "VICTORY! All killers have been eliminated. The town is safe.";
        $escaped_final_msg = mysqli_real_escape_string($link, $final_msg);
        
        mysqli_query($link, "UPDATE rooms SET status = 'finished' WHERE id = $room_id");
        mysqli_query($link, "INSERT INTO messages (room_id, user_id, message) VALUES ($room_id, NULL, '$escaped_final_msg')");
        
        echo json_encode(["status" => "success", "message" => "Game Finished", "news" => $final_msg]);
        exit;
    } elseif($killer_count >= $town_count){
        // Killer wins
        $final_msg = "DEFEAT! The killer has outnumbered the town. Chaos reigns.";
        $escaped_final_msg = mysqli_real_escape_string($link, $final_msg);

        mysqli_query($link, "UPDATE rooms SET status = 'finished' WHERE id = $room_id");
        mysqli_query($link, "INSERT INTO messages (room_id, user_id, message) VALUES ($room_id, NULL, '$escaped_final_msg')");

        echo json_encode(["status" => "success", "message" => "Game Finished", "news" => $final_msg]);
        exit;
    }

    // 5. Transition back to Night
    $roles_to_check = ['Killer', 'Doctor', 'Detective'];
    $final_turn = 'None';
    
    foreach($roles_to_check as $role){
        $escaped_role = mysqli_real_escape_string($link, $role);
        $sql_check = "SELECT user_id FROM room_players WHERE room_id = $room_id AND role = '$escaped_role' AND is_alive = 1";
        $res_check = mysqli_query($link, $sql_check);
        if($res_check && mysqli_num_rows($res_check) > 0){
            $final_turn = $role;
            break;
        }
    }

    $escaped_final_turn = mysqli_real_escape_string($link, $final_turn);
    $update_room = "UPDATE rooms SET phase = 'night', round = round + 1, action_count = 0, current_turn = '$escaped_final_turn', killer_target = NULL, doctor_target = NULL, detective_target = NULL, phase_start_time = NOW() WHERE id = $room_id";
    mysqli_query($link, $update_room);

    echo json_encode(["status" => "success", "message" => "Voting processed", "news" => $message]);
}
}