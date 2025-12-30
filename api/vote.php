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
    $target_id = (int)$_POST['target_id'];
    $voter_id = $_SESSION['id'];

    // 1. Verify if it's Day phase and if the voter is alive
    $sql = "SELECT phase, round FROM rooms WHERE id = $room_id";
    $res_room = mysqli_query($link, $sql);
    $room = ($res_room) ? mysqli_fetch_assoc($res_room) : null;

    if(!$room || $room['phase'] !== 'day'){
        echo json_encode(["status" => "error", "message" => "Voting is only allowed during Day phase."]);
        exit;
    }

    $sql_voter = "SELECT is_alive FROM room_players WHERE room_id = $room_id AND user_id = $voter_id";
    $res_voter = mysqli_query($link, $sql_voter);
    $voter = ($res_voter) ? mysqli_fetch_assoc($res_voter) : null;

    if(!$voter || !$voter['is_alive']){
        echo json_encode(["status" => "error", "message" => "You must be alive to vote."]);
        exit;
    }

    // 2. Cast vote (INSERT or UPDATE)
    $round = $room['round'];
    $sql_vote = "INSERT INTO votes (room_id, voter_id, target_id, round) VALUES ($room_id, $voter_id, $target_id, $round) 
                 ON DUPLICATE KEY UPDATE target_id = VALUES(target_id)";
    
    if(mysqli_query($link, $sql_vote)){
        echo json_encode(["status" => "success", "message" => "Vote cast successfully."]);
    } else {
        echo json_encode(["status" => "error", "message" => "Failed to cast vote: " . mysqli_error($link)]);
    }
}
