<?php
require_once __DIR__ . "/includes/session.php";
require_once __DIR__ . "/includes/config.php";

ini_set('display_errors', 0);
error_reporting(E_ALL);

// Check if user is logged in
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: login.php");
    exit;
}

if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["room_id"])){
    $room_id = (int)$_POST["room_id"];
    $user_id = $_SESSION["id"];

    // 1. Verify the user is the creator of the room
    $sql = "SELECT creator_id, current_players FROM rooms WHERE id = $room_id";
    $result = mysqli_query($link, $sql);
    $room = ($result) ? mysqli_fetch_assoc($result) : null;
    
    if(!$room || $room['creator_id'] != $user_id){
        echo "<script>alert('Unauthorized: Only the room creator can start the game.'); window.location.href='room.php?id=$room_id';</script>";
        exit;
    }
    
    if($room['current_players'] < 4){
        echo "<script>alert('Error: Need at least 4 players to start.'); window.location.href='room.php?id=$room_id';</script>";
        exit;
    }

    // 2. Fetch all players in the room
    $players = [];
    $sql_players = "SELECT user_id FROM room_players WHERE room_id = $room_id";
    $res_p = mysqli_query($link, $sql_players);
    if($res_p){
        while($p = mysqli_fetch_assoc($res_p)){
            $players[] = $p['user_id'];
        }
    }

    // 3. Assign Roles
    shuffle($players);
    $num_players = count($players);
    
    // Roles Logic:
    // 1-2 Killers, 1 Detective, 1 Doctor, the rest Townsfolk
    $roles = [];
    $roles[] = 'Killer';
    if($num_players >= 7) $roles[] = 'Killer';
    $roles[] = 'Detective';
    $roles[] = 'Doctor';
    
    while(count($roles) < $num_players){
        $roles[] = 'Townsfolk';
    }
    shuffle($roles);

    // 4. Update Database with roles and set room status to 'in_progress'
    mysqli_begin_transaction($link);
    try {
        // Update Room Status and set initial turn
        $sql_room = "UPDATE rooms SET status = 'in_progress', phase = 'night', current_turn = 'Killer', round = 1, action_count = 0, phase_start_time = NOW() WHERE id = $room_id";
        mysqli_query($link, $sql_room);

        // Update each player's role
        foreach($players as $index => $p_id){
            $role = $roles[$index];
            $sql_role = "UPDATE room_players SET role = '$role', is_alive = 1 WHERE room_id = $room_id AND user_id = $p_id";
            mysqli_query($link, $sql_role);
        }

        mysqli_commit($link);
        
        // Save session and redirect to the arena
        session_write_close();
        header("location: arena.php?id=" . $room_id);
        exit;
        
    } catch (Exception $e) {
        mysqli_rollback($link);
        echo "Error starting game: " . $e->getMessage();
    }
}
?>
