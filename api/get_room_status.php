<?php
require_once __DIR__ . "/includes/config.php";
require_once __DIR__ . "/includes/session.php"; 

header('Content-Type: application/json');

if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    echo json_encode(["status" => "error", "message" => "Unauthorized"]);
    exit;
}

if(isset($_GET["room_id"])){
    $room_id = (int)$_GET["room_id"];
    
    $sql = "SELECT status, current_players, max_players, creator_id FROM rooms WHERE id = $room_id";
    $res_room = mysqli_query($link, $sql);
    
    if($res_room && $row = mysqli_fetch_assoc($res_room)){
        // Fetch players in the room
        $players = [];
        $sql_players = "SELECT u.username FROM room_players rp JOIN users u ON rp.user_id = u.id WHERE rp.room_id = $room_id";
        $res_players = mysqli_query($link, $sql_players);
        while($player = mysqli_fetch_assoc($res_players)){
            $players[] = $player['username'];
        }

        echo json_encode([
            "status" => "success", 
            "room_status" => $row["status"],
            "current_players" => $row["current_players"],
            "max_players" => $row["max_players"],
            "creator_id" => (int)$row["creator_id"],
            "current_user_id" => (int)$_SESSION["id"],
            "players" => $players
        ]);
    } else {
        echo json_encode(["status" => "error", "message" => "Room not found"]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "Missing room_id"]);
}
?>
