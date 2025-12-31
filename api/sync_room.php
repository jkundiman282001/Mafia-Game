<?php
error_reporting(0);
require_once __DIR__ . "/includes/config.php";
require_once __DIR__ . "/includes/session.php";

header('Content-Type: application/json');

if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    echo json_encode(["status" => "error", "message" => "Unauthorized"]);
    exit;
}

if(isset($_GET["room_id"])){
    $room_id = (int)$_GET["room_id"];
    $last_message_id = isset($_GET["last_id"]) ? (int)$_GET["last_id"] : 0;
    
    $response = [
        "status" => "success",
        "room" => null,
        "messages" => [],
        "players" => []
    ];

    // 1. Fetch Room Status
    $sql_room = "SELECT status, phase, round, current_turn, killer_target, doctor_target, winner, phase_start_time, NOW() as current_time, current_players, max_players, creator_id FROM rooms WHERE id = $room_id";
    $res_room = mysqli_query($link, $sql_room);
    
    if($res_room && mysqli_num_rows($res_room) > 0){
        $row = mysqli_fetch_assoc($res_room);
        $time_remaining = 0;
        if($row['phase'] === 'day' && $row['phase_start_time']){
            $start = strtotime($row['phase_start_time']);
            $now = strtotime($row['current_time']);
            $elapsed = $now - $start;
            $time_remaining = max(0, 180 - $elapsed); // 3 minutes = 180s
        }

        $response["room"] = [
            "status" => $row["status"],
            "phase" => $row["phase"],
            "round" => (int)$row["round"],
            "current_turn" => $row["current_turn"],
            "killer_target" => $row["killer_target"],
            "doctor_target" => $row["doctor_target"],
            "winner" => $row["winner"],
            "time_remaining" => $time_remaining,
            "current_players" => $row["current_players"],
            "max_players" => $row["max_players"],
            "creator_id" => (int)$row["creator_id"],
            "current_user_id" => (int)$_SESSION["id"]
        ];
    } else {
        // Log error if needed: mysqli_error($link)
    }

    // 2. Fetch Players & Current User Role
    $user_id = (int)$_SESSION["id"];
    $sql_players = "SELECT u.username, rp.user_id, rp.role, rp.is_alive, rp.vote_count 
                    FROM room_players rp 
                    JOIN users u ON rp.user_id = u.id 
                    WHERE rp.room_id = $room_id";
    $res_players = mysqli_query($link, $sql_players);
    if ($res_players) {
        while($player = mysqli_fetch_assoc($res_players)){
            $response["players"][] = [
                "id" => (int)$player['user_id'],
                "username" => $player['username'],
                "is_alive" => (bool)$player['is_alive'],
                "vote_count" => (int)$player['vote_count']
            ];
            
            if((int)$player['user_id'] === $user_id){
                $response["user_role"] = $player['role'];
                $response["is_alive"] = (bool)$player['is_alive'];
            }
        }
    }

    // 3. Fetch New Messages (only since last_id)
    $sql_messages = "SELECT m.id, m.message, u.username, m.created_at 
                     FROM messages m 
                     JOIN users u ON m.user_id = u.id 
                     WHERE m.room_id = $room_id AND m.id > $last_message_id 
                     ORDER BY m.id ASC";
    $res_messages = mysqli_query($link, $sql_messages);
    while($msg = mysqli_fetch_assoc($res_messages)){
        $response["messages"][] = [
            "id" => (int)$msg["id"],
            "username" => $msg["username"],
            "message" => $msg["message"],
            "time" => date('H:i', strtotime($msg["created_at"]))
        ];
    }

    echo json_encode($response);
} else {
    echo json_encode(["status" => "error", "message" => "Missing room_id"]);
}
?>
