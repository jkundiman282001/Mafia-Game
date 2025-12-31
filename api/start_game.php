<?php
require_once __DIR__ . "/includes/config.php";
require_once __DIR__ . "/includes/session.php";

header('Content-Type: application/json');

if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    echo json_encode(["status" => "error", "message" => "Unauthorized"]);
    exit;
}

if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["room_id"])){
    $room_id = (int)$_POST["room_id"];
    $user_id = (int)$_SESSION["id"];

    // Check if user is the creator
    $sql = "SELECT creator_id, current_players, status FROM rooms WHERE id = $room_id";
    $result = mysqli_query($link, $sql);

    if($result && $row = mysqli_fetch_assoc($result)){
        if($row['creator_id'] != $user_id){
            echo json_encode(["status" => "error", "message" => "Only the room creator can start the game."]);
            exit;
        }

        if($row['status'] != 'waiting'){
            echo json_encode(["status" => "error", "message" => "Game has already started or finished."]);
            exit;
        }

        if($row['current_players'] < 4){
            echo json_encode(["status" => "error", "message" => "Need at least 4 players to assign all roles (Killer, Doctor, Investigator, Townsfolk)."]);
            exit;
        }

        // Get all players in the room
        $players = [];
        $sql_players = "SELECT user_id FROM room_players WHERE room_id = $room_id";
        $res_players = mysqli_query($link, $sql_players);
        while($p = mysqli_fetch_assoc($res_players)){
            $players[] = $p['user_id'];
        }

        // Shuffle and assign roles
        shuffle($players);
        
        $killer = array_pop($players);
        $doctor = array_pop($players);
        $investigator = array_pop($players);
        $townsfolk = $players; // Remaining players

        // Update Killer
        mysqli_query($link, "UPDATE room_players SET role = 'Killer' WHERE room_id = $room_id AND user_id = $killer");
        // Update Doctor
        mysqli_query($link, "UPDATE room_players SET role = 'Doctor' WHERE room_id = $room_id AND user_id = $doctor");
        // Update Investigator
        mysqli_query($link, "UPDATE room_players SET role = 'Investigator' WHERE room_id = $room_id AND user_id = $investigator");
        // Update Townsfolk
        if(!empty($townsfolk)){
            $towns_ids = implode(',', $townsfolk);
            mysqli_query($link, "UPDATE room_players SET role = 'Townsfolk' WHERE room_id = $room_id AND user_id IN ($towns_ids)");
        }

        // Update room status and phase
        $update_sql = "UPDATE rooms SET status = 'in_progress', phase = 'night', round = 1, current_turn = 'Killer', phase_start_time = NOW() WHERE id = $room_id";
        if(mysqli_query($link, $update_sql)){
            echo json_encode(["status" => "success", "message" => "Game started! Night phase begins."]);
        } else {
            echo json_encode(["status" => "error", "message" => "Failed to start game: " . mysqli_error($link)]);
        }
    } else {
        echo json_encode(["status" => "error", "message" => "Room not found."]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "Invalid request."]);
}
?>
