<?php
ob_start();
require_once __DIR__ . "/includes/config.php";
require_once __DIR__ . "/includes/session.php";

ob_clean();
header('Content-Type: application/json');

if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    echo json_encode(["status" => "error", "message" => "Unauthorized"]);
    exit;
}

if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["room_id"]) && isset($_POST["action"])){
    $room_id = (int)$_POST["room_id"];
    $action = $_POST["action"]; // 'kill', 'save', 'investigate', 'trial', 'vote'
    $target_id = isset($_POST["target_id"]) ? (int)$_POST["target_id"] : null;
    $user_id = (int)$_SESSION["id"];

    // 1. Fetch Room and Player Context
    $sql = "SELECT status, phase, round, current_turn, killer_target, doctor_target, creator_id FROM rooms WHERE id = $room_id";
    $room_res = mysqli_query($link, $sql);
    $room = mysqli_fetch_assoc($room_res);

    $sql_player = "SELECT role, is_alive FROM room_players WHERE room_id = $room_id AND user_id = $user_id";
    $player_res = mysqli_query($link, $sql_player);
    $player = mysqli_fetch_assoc($player_res);

    if(!$room || !$player || !$player['is_alive']){
        echo json_encode(["status" => "error", "message" => "Invalid state."]);
        exit;
    }

    // 2. Handle Actions based on Phase and Turn
    if($room['phase'] === 'night'){
        if($action === 'kill' && $player['role'] === 'Killer' && $room['current_turn'] === 'Killer'){
            // Update killer target
            mysqli_query($link, "UPDATE rooms SET killer_target = $target_id WHERE id = $room_id");
            
            // Determine next turn: skip Doctor if dead
            $res_doc = mysqli_query($link, "SELECT id FROM room_players WHERE room_id = $room_id AND role = 'Doctor' AND is_alive = 1");
            if(mysqli_num_rows($res_doc) > 0) {
                mysqli_query($link, "UPDATE rooms SET current_turn = 'Doctor' WHERE id = $room_id");
            } else {
                // Doctor is dead, check Investigator
                $res_inv = mysqli_query($link, "SELECT id FROM room_players WHERE room_id = $room_id AND role = 'Investigator' AND is_alive = 1");
                if(mysqli_num_rows($res_inv) > 0) {
                    mysqli_query($link, "UPDATE rooms SET current_turn = 'Investigator' WHERE id = $room_id");
                } else {
                    // Both dead, end night phase
                    end_night_phase($link, $room_id);
                }
            }
            echo json_encode(["status" => "success", "message" => "Target marked for elimination."]);
            exit;
        } 
        elseif($action === 'save' && $player['role'] === 'Doctor' && $room['current_turn'] === 'Doctor'){
            mysqli_query($link, "UPDATE rooms SET doctor_target = $target_id WHERE id = $room_id");
            
            // Determine next turn: skip Investigator if dead
            $res_inv = mysqli_query($link, "SELECT id FROM room_players WHERE room_id = $room_id AND role = 'Investigator' AND is_alive = 1");
            if(mysqli_num_rows($res_inv) > 0) {
                mysqli_query($link, "UPDATE rooms SET current_turn = 'Investigator' WHERE id = $room_id");
            } else {
                // Investigator is dead, end night phase
                end_night_phase($link, $room_id);
            }
            echo json_encode(["status" => "success", "message" => "Target protected."]);
            exit;
        }
        elseif($action === 'investigate' && $player['role'] === 'Investigator' && $room['current_turn'] === 'Investigator'){
            $sql_target = "SELECT role FROM room_players WHERE room_id = $room_id AND user_id = $target_id";
            $res_target = mysqli_query($link, $sql_target);
            $target = mysqli_fetch_assoc($res_target);
            $result = ($target['role'] === 'Killer') ? "Bad" : "Good";
            
            // End Night Phase
            end_night_phase($link, $room_id);
            
            echo json_encode(["status" => "success", "message" => "Investigation complete: Target is $result.", "investigation_result" => $result]);
            exit;
        }
    } 
    elseif($room['phase'] === 'day' && $action === 'trial' && $user_id == $room['creator_id']){
        mysqli_query($link, "UPDATE rooms SET phase = 'trial', current_turn = 'None' WHERE id = $room_id");
        echo json_encode(["status" => "success", "message" => "Trial phase started."]);
        exit;
    }
    elseif($room['phase'] === 'trial' && $action === 'vote'){
        // ... (existing voting logic)
        $sql_voted = "SELECT voted_for FROM room_players WHERE room_id = $room_id AND user_id = $user_id";
        $res_voted = mysqli_query($link, $sql_voted);
        $voted = mysqli_fetch_assoc($res_voted);
        
        if($voted['voted_for'] === NULL){
            mysqli_query($link, "UPDATE room_players SET voted_for = $target_id WHERE room_id = $room_id AND user_id = $user_id");
            mysqli_query($link, "UPDATE room_players SET vote_count = vote_count + 1 WHERE room_id = $room_id AND user_id = $target_id");
            
            // Check if everyone voted
            $sql_alive = "SELECT COUNT(*) as count FROM room_players WHERE room_id = $room_id AND is_alive = 1";
            $res_alive = mysqli_query($link, $sql_alive);
            $alive_count = mysqli_fetch_assoc($res_alive)['count'];
            
            $sql_votes = "SELECT COUNT(*) as count FROM room_players WHERE room_id = $room_id AND voted_for IS NOT NULL";
            $res_votes = mysqli_query($link, $sql_votes);
            $votes_count = mysqli_fetch_assoc($res_votes)['count'];
            
            if($votes_count >= $alive_count){
                // Process voting results
                $sql_top = "SELECT user_id FROM room_players WHERE room_id = $room_id AND is_alive = 1 ORDER BY vote_count DESC LIMIT 1";
                $res_top = mysqli_query($link, $sql_top);
                $lynched = mysqli_fetch_assoc($res_top)['user_id'];
                
                mysqli_query($link, "UPDATE room_players SET is_alive = 0 WHERE room_id = $room_id AND user_id = $lynched");
                
                // Reset votes and go to next night
                mysqli_query($link, "UPDATE room_players SET vote_count = 0, voted_for = NULL WHERE room_id = $room_id");
                
                check_game_end($link, $room_id);
                
                $sql_status = "SELECT status FROM rooms WHERE id = $room_id";
                $room_status = mysqli_fetch_assoc(mysqli_query($link, $sql_status))['status'];
                
                if($room_status !== 'finished'){
                    mysqli_query($link, "UPDATE rooms SET phase = 'night', round = round + 1, current_turn = 'Killer', phase_start_time = NOW() WHERE id = $room_id");
                }
            }
            
            echo json_encode(["status" => "success", "message" => "Vote cast."]);
            exit;
        } else {
            echo json_encode(["status" => "error", "message" => "You have already voted."]);
            exit;
        }
    } 
    else {
        echo json_encode(["status" => "error", "message" => "Invalid action or phase."]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "Invalid request."]);
}

function end_night_phase($link, $room_id) {
    // Process Results
    $sql = "SELECT killer_target, doctor_target FROM rooms WHERE id = $room_id";
    $res = mysqli_query($link, $sql);
    $room = mysqli_fetch_assoc($res);
    
    $killer_target = $room['killer_target'];
    $doctor_target = $room['doctor_target'];
    
    if($killer_target && $killer_target != $doctor_target){
        mysqli_query($link, "UPDATE room_players SET is_alive = 0 WHERE room_id = $room_id AND user_id = $killer_target");
    }
    
    // Check for game end before moving to day
    check_game_end($link, $room_id);
    
    mysqli_query($link, "UPDATE rooms SET phase = 'day', current_turn = 'None', phase_start_time = NOW(), killer_target = NULL, doctor_target = NULL WHERE id = $room_id");
}

function check_game_end($link, $room_id) {
    // Count alive roles
    $sql = "SELECT role, COUNT(*) as count FROM room_players WHERE room_id = $room_id AND is_alive = 1 GROUP BY role";
    $res = mysqli_query($link, $sql);
    
    $roles = [];
    while($r = mysqli_fetch_assoc($res)){
        $roles[$r['role']] = (int)$r['count'];
    }
    
    $killer_alive = isset($roles['Killer']) && $roles['Killer'] > 0;
    $town_alive = (isset($roles['Townsfolk']) ? $roles['Townsfolk'] : 0) + 
                  (isset($roles['Doctor']) ? $roles['Doctor'] : 0) + 
                  (isset($roles['Investigator']) ? $roles['Investigator'] : 0);
    
    if(!$killer_alive){
        mysqli_query($link, "UPDATE rooms SET status = 'finished', winner = 'Townspeople' WHERE id = $room_id");
    } elseif($killer_alive && $town_alive <= 1){
        mysqli_query($link, "UPDATE rooms SET status = 'finished', winner = 'Killer' WHERE id = $room_id");
    }
}
?>
