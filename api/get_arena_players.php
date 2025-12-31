<?php
require_once __DIR__ . "/includes/config.php";
require_once __DIR__ . "/includes/session.php";

ini_set('display_errors', 0);
error_reporting(E_ALL);

header('Content-Type: application/json');

if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    echo json_encode(["status" => "error", "message" => "Unauthorized"]);
    exit;
}

if(isset($_GET["room_id"])){
    $room_id = (int)$_GET["room_id"];
    $user_id = $_SESSION["id"];

    // Get current phase and round
    $sql_room = "SELECT phase, round FROM rooms WHERE id = $room_id";
    $res_room = mysqli_query($link, $sql_room);
    $room = ($res_room) ? mysqli_fetch_assoc($res_room) : null;
    $phase = $room['phase'] ?? 'night';
    $round = $room['round'] ?? 1;

    $sql = "SELECT rp.user_id, u.username, rp.is_alive, rp.role 
            FROM room_players rp 
            JOIN users u ON rp.user_id = u.id 
            WHERE rp.room_id = $room_id";
            
    $result = mysqli_query($link, $sql);
    
    if($result){
        $players = [];
        
        // Get vote counts if in day phase
        $votes = [];
        $my_vote = null;
        if($phase == 'day'){
            $sql_v = "SELECT target_id, COUNT(*) as count FROM votes WHERE room_id = $room_id AND round = $round GROUP BY target_id";
            $res_v = mysqli_query($link, $sql_v);
            if($res_v){
                while($row_v = mysqli_fetch_assoc($res_v)){
                    $votes[$row_v['target_id']] = $row_v['count'];
                }
            }

            $sql_my = "SELECT target_id FROM votes WHERE room_id = $room_id AND voter_id = $user_id AND round = $round";
            $res_my = mysqli_query($link, $sql_my);
            if($res_my && $row_my = mysqli_fetch_assoc($res_my)){
                $my_vote = $row_my['target_id'];
            }
        }

        while($row = mysqli_fetch_assoc($result)){
            $players[] = [
                "user_id" => $row["user_id"],
                "username" => $row["username"],
                "is_alive" => (bool)$row["is_alive"],
                "vote_count" => $votes[$row["user_id"]] ?? 0
            ];
        }
        
        echo json_encode([
            "status" => "success", 
            "players" => $players,
            "my_vote" => $my_vote
        ]);
    } else {
        echo json_encode(["status" => "error", "message" => "Database error: " . mysqli_error($link)]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "Missing room_id"]);
}