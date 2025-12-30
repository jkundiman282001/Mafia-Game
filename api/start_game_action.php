<?php
require_once __DIR__ . "/includes/session.php";
require_once __DIR__ . "/includes/config.php";

// Check if user is logged in
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: login.php");
    exit;
}

if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["room_id"])){
    $room_id = $_POST["room_id"];
    $user_id = $_SESSION["id"];

    // 1. Verify the user is the creator of the room
    $sql = "SELECT creator_id, current_players FROM rooms WHERE id = ?";
    if($stmt = mysqli_prepare($link, $sql)){
        mysqli_stmt_bind_param($stmt, "i", $room_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $room = mysqli_fetch_assoc($result);
        
        if(!$room || $room['creator_id'] != $user_id){
            echo "<script>alert('Unauthorized: Only the room creator can start the game.'); window.location.href='room.php?id=$room_id';</script>";
            exit;
        }
        
        if($room['current_players'] < 4){
            echo "<script>alert('Error: Need at least 4 players to start.'); window.location.href='room.php?id=$room_id';</script>";
            exit;
        }
        mysqli_stmt_close($stmt);
    }

    // 2. Fetch all players in the room
    $players = [];
    $sql_players = "SELECT user_id FROM room_players WHERE room_id = ?";
    if($stmt_p = mysqli_prepare($link, $sql_players)){
        mysqli_stmt_bind_param($stmt_p, "i", $room_id);
        mysqli_stmt_execute($stmt_p);
        $res_p = mysqli_stmt_get_result($stmt_p);
        while($p = mysqli_fetch_assoc($res_p)){
            $players[] = $p['user_id'];
        }
        mysqli_stmt_close($stmt_p);
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
        $sql_room = "UPDATE rooms SET status = 'in_progress', phase = 'night', current_turn = 'Killer', round = 1, action_count = 0 WHERE id = ?";
        $stmt_r = mysqli_prepare($link, $sql_room);
        mysqli_stmt_bind_param($stmt_r, "i", $room_id);
        mysqli_stmt_execute($stmt_r);

        // Update each player's role
        $sql_role = "UPDATE room_players SET role = ?, is_alive = 1 WHERE room_id = ? AND user_id = ?";
        $stmt_role = mysqli_prepare($link, $sql_role);
        
        foreach($players as $index => $p_id){
            mysqli_stmt_bind_param($stmt_role, "sii", $roles[$index], $room_id, $p_id);
            mysqli_stmt_execute($stmt_role);
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
