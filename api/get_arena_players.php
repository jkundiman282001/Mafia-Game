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

if(isset($_GET["room_id"])){
    $room_id = $_GET["room_id"];
    $user_id = $_SESSION["id"];

    // Get current phase and round
    $sql_room = "SELECT phase, round FROM rooms WHERE id = ?";
    $stmt_room = mysqli_prepare($link, $sql_room);
    mysqli_stmt_bind_param($stmt_room, "i", $room_id);
    mysqli_stmt_execute($stmt_room);
    $res_room = mysqli_stmt_get_result($stmt_room);
    $room = $res_room ? mysqli_fetch_assoc($res_room) : null;
    $phase = $room['phase'] ?? 'night';
    $round = $room['round'] ?? 1;

    $sql = "SELECT rp.user_id, u.username, rp.is_alive, rp.role 
            FROM room_players rp 
            JOIN users u ON rp.user_id = u.id 
            WHERE rp.room_id = ?";
            
    if($stmt = mysqli_prepare($link, $sql)){
        mysqli_stmt_bind_param($stmt, "i", $room_id);
        if(mysqli_stmt_execute($stmt)){
            $result = mysqli_stmt_get_result($stmt);
            $players = [];
            
            // Get vote counts if in day phase
            $votes = [];
            $my_vote = null;
            if($phase == 'day'){
                $sql_v = "SELECT target_id, COUNT(*) as count FROM votes WHERE room_id = ? AND round = ? GROUP BY target_id";
                $stmt_v = mysqli_prepare($link, $sql_v);
                mysqli_stmt_bind_param($stmt_v, "ii", $room_id, $round);
                mysqli_stmt_execute($stmt_v);
                $res_v = mysqli_stmt_get_result($stmt_v);
                if($res_v){
                    while($row_v = mysqli_fetch_assoc($res_v)){
                        $votes[$row_v['target_id']] = $row_v['count'];
                    }
                }

                $sql_my = "SELECT target_id FROM votes WHERE room_id = ? AND voter_id = ? AND round = ?";
                $stmt_my = mysqli_prepare($link, $sql_my);
                mysqli_stmt_bind_param($stmt_my, "iii", $room_id, $user_id, $round);
                mysqli_stmt_execute($stmt_my);
                $res_my = mysqli_stmt_get_result($stmt_my);
                if($res_my && $row_my = mysqli_fetch_assoc($res_my)){
                    $my_vote = $row_my['target_id'];
                }
            }

            if($result){
                while($row = mysqli_fetch_assoc($result)){
                    $players[] = [
                        "user_id" => $row["user_id"],
                        "username" => $row["username"],
                        "is_alive" => (bool)$row["is_alive"],
                        "vote_count" => $votes[$row["user_id"]] ?? 0
                    ];
                }
            }
            echo json_encode([
                "status" => "success", 
                "players" => $players,
                "my_vote" => $my_vote
            ]);
        } else {
            echo json_encode(["status" => "error", "message" => "Database error"]);
        }
        mysqli_stmt_close($stmt);
    }
} else {
    echo json_encode(["status" => "error", "message" => "Missing room_id"]);
}