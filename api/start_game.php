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

        if($row['current_players'] < 2){
            echo json_encode(["status" => "error", "message" => "Need at least 2 players to start the game."]);
            exit;
        }

        // Update room status
        $update_sql = "UPDATE rooms SET status = 'in_progress' WHERE id = $room_id";
        if(mysqli_query($link, $update_sql)){
            echo json_encode(["status" => "success", "message" => "Game started!"]);
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
