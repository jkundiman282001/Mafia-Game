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
    $target_id = $_POST['target_id'];
    $voter_id = $_SESSION['id'];

    // 1. Verify if it's Day phase and if the voter is alive
    $sql = "SELECT phase, round FROM rooms WHERE id = ?";
    $stmt = mysqli_prepare($link, $sql);
    mysqli_stmt_bind_param($stmt, "i", $room_id);
    mysqli_stmt_execute($stmt);
    $room = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

    if(!$room || $room['phase'] !== 'day'){
        echo json_encode(["status" => "error", "message" => "Voting is only allowed during Day phase."]);
        exit;
    }

    $sql_voter = "SELECT is_alive FROM room_players WHERE room_id = ? AND user_id = ?";
    $stmt_voter = mysqli_prepare($link, $sql_voter);
    mysqli_stmt_bind_param($stmt_voter, "ii", $room_id, $voter_id);
    mysqli_stmt_execute($stmt_voter);
    $voter = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_voter));

    if(!$voter || !$voter['is_alive']){
        echo json_encode(["status" => "error", "message" => "You must be alive to vote."]);
        exit;
    }

    // 2. Cast vote (INSERT or UPDATE)
    $round = $room['round'];
    $sql_vote = "INSERT INTO votes (room_id, voter_id, target_id, round) VALUES (?, ?, ?, ?) 
                 ON DUPLICATE KEY UPDATE target_id = VALUES(target_id)";
    
    if($stmt_vote = mysqli_prepare($link, $sql_vote)){
        mysqli_stmt_bind_param($stmt_vote, "iiii", $room_id, $voter_id, $target_id, $round);
        if(mysqli_stmt_execute($stmt_vote)){
            echo json_encode(["status" => "success", "message" => "Vote cast successfully."]);
        } else {
            echo json_encode(["status" => "error", "message" => "Failed to cast vote: " . mysqli_error($link)]);
        }
    }
}
