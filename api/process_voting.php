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
    $user_id = $_SESSION['id'];

    // 1. Verify if requester is the host
    $sql_host = "SELECT creator_id, phase, round FROM rooms WHERE id = ?";
    $stmt_host = mysqli_prepare($link, $sql_host);
    mysqli_stmt_bind_param($stmt_host, "i", $room_id);
    mysqli_stmt_execute($stmt_host);
    $res_room = mysqli_stmt_get_result($stmt_host);
    $room = $res_room ? mysqli_fetch_assoc($res_room) : null;

    if(!$room || $room['creator_id'] != $user_id){
        echo json_encode(["status" => "error", "message" => "Only the host can process voting."]);
        exit;
    }

    if($room['phase'] !== 'day'){
        echo json_encode(["status" => "error", "message" => "Not in day phase."]);
        exit;
    }

    $round = $room['round'];

    // 2. Count votes
    $sql_votes = "SELECT target_id, COUNT(*) as vote_count 
                  FROM votes 
                  WHERE room_id = ? AND round = ? 
                  GROUP BY target_id 
                  ORDER BY vote_count DESC";
    $stmt_v = mysqli_prepare($link, $sql_votes);
    mysqli_stmt_bind_param($stmt_v, "ii", $room_id, $round);
    mysqli_stmt_execute($stmt_v);
    $result_v = mysqli_stmt_get_result($stmt_v);
    
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
            $eliminated_id = $top_votes[0]['target_id'];
            
            // Eliminate player
            $sql_elim = "UPDATE room_players SET is_alive = 0 WHERE room_id = ? AND user_id = ?";
            $stmt_e = mysqli_prepare($link, $sql_elim);
            if($stmt_e){
                mysqli_stmt_bind_param($stmt_e, "ii", $room_id, $eliminated_id);
                mysqli_stmt_execute($stmt_e);
            }

            // Get username
            $sql_user = "SELECT username FROM users WHERE id = ?";
            $stmt_u = mysqli_prepare($link, $sql_user);
            if($stmt_u){
                mysqli_stmt_bind_param($stmt_u, "i", $eliminated_id);
                mysqli_stmt_execute($stmt_u);
                $res_u = mysqli_stmt_get_result($stmt_u);
                $username = ($res_u && $row_u = mysqli_fetch_assoc($res_u)) ? $row_u['username'] : "A player";
                $message .= "$username has been eliminated by the town.";
            }
        }
    } else {
        $message .= "Nobody voted. Nobody was eliminated.";
    }

    // 3. Add system message
    $sql_msg = "INSERT INTO messages (room_id, user_id, message) VALUES (?, NULL, ?)";
    $stmt_m = mysqli_prepare($link, $sql_msg);
    if($stmt_m){
        mysqli_stmt_bind_param($stmt_m, "is", $room_id, $message);
        mysqli_stmt_execute($stmt_m);
    }

    // 4. Check for Win Conditions
    $sql_win = "SELECT role, COUNT(*) as count FROM room_players WHERE room_id = ? AND is_alive = 1 GROUP BY role";
    $stmt_win = mysqli_prepare($link, $sql_win);
    if($stmt_win){
        mysqli_stmt_bind_param($stmt_win, "i", $room_id);
        mysqli_stmt_execute($stmt_win);
        $res_win = mysqli_stmt_get_result($stmt_win);
        
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
        // Town wins
        $final_msg = "VICTORY! All killers have been eliminated. The town is safe.";
        $update_game = "UPDATE rooms SET status = 'finished' WHERE id = ?";
        $stmt_g = mysqli_prepare($link, $update_game);
        mysqli_stmt_bind_param($stmt_g, "i", $room_id);
        mysqli_stmt_execute($stmt_g);
        
        $sql_msg = "INSERT INTO messages (room_id, user_id, message) VALUES (?, NULL, ?)";
        $stmt_m = mysqli_prepare($link, $sql_msg);
        mysqli_stmt_bind_param($stmt_m, "is", $room_id, $final_msg);
        mysqli_stmt_execute($stmt_m);
        
        echo json_encode(["status" => "success", "message" => "Game Finished", "news" => $final_msg]);
        exit;
    } elseif($killer_count >= $town_count){
        // Killer wins
        $final_msg = "DEFEAT! The killer has outnumbered the town. Chaos reigns.";
        $update_game = "UPDATE rooms SET status = 'finished' WHERE id = ?";
        $stmt_g = mysqli_prepare($link, $update_game);
        mysqli_stmt_bind_param($stmt_g, "i", $room_id);
        mysqli_stmt_execute($stmt_g);

        $sql_msg = "INSERT INTO messages (room_id, user_id, message) VALUES (?, NULL, ?)";
        $stmt_m = mysqli_prepare($link, $sql_msg);
        mysqli_stmt_bind_param($stmt_m, "is", $room_id, $final_msg);
        mysqli_stmt_execute($stmt_m);

        echo json_encode(["status" => "success", "message" => "Game Finished", "news" => $final_msg]);
        exit;
    }

    // 5. Transition back to Night
    $current_turn = 'Killer';
    $roles_to_check = ['Killer', 'Doctor', 'Detective'];
    $final_turn = 'None';
    
    foreach($roles_to_check as $role){
        $sql_check = "SELECT id FROM room_players WHERE room_id = ? AND role = ? AND is_alive = 1";
        $stmt_check = mysqli_prepare($link, $sql_check);
        mysqli_stmt_bind_param($stmt_check, "is", $room_id, $role);
        mysqli_stmt_execute($stmt_check);
        mysqli_stmt_store_result($stmt_check);
        if(mysqli_stmt_num_rows($stmt_check) > 0){
            $final_turn = $role;
            mysqli_stmt_close($stmt_check);
            break;
        }
        mysqli_stmt_close($stmt_check);
    }

    $update_room = "UPDATE rooms SET phase = 'night', round = round + 1, action_count = 0, current_turn = ?, killer_target = NULL, doctor_target = NULL, detective_target = NULL, phase_start_time = NULL WHERE id = ?";
    $stmt_ur = mysqli_prepare($link, $update_room);
    mysqli_stmt_bind_param($stmt_ur, "si", $final_turn, $room_id);
    mysqli_stmt_execute($stmt_ur);

    echo json_encode(["status" => "success", "message" => "Voting processed", "news" => $message]);
}
}