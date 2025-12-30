<?php
require_once "includes/session.php";

// Check if the user is logged in
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    echo "<script>window.location.href='login.php';</script>";
    exit;
}

if($_SERVER["REQUEST_METHOD"] == "POST"){
    $room_id = null;

    // Check if joining by code
    if(isset($_POST["room_code"])){
        $room_code = mysqli_real_escape_string($link, trim($_POST["room_code"]));
        $sql = "SELECT id FROM rooms WHERE room_code = '$room_code'";
        $result = mysqli_query($link, $sql);
        if($result && mysqli_num_rows($result) == 1){
            $row = mysqli_fetch_assoc($result);
            $room_id = (int)$row['id'];
        } else {
            echo "<script>alert('Invalid Room Code.'); window.location.href='join_lobby.php';</script>";
            exit;
        }
    } elseif(isset($_POST["room_id"])){
        $room_id = (int)$_POST["room_id"];
    }

    if($room_id){
        $user_id = (int)$_SESSION["id"];
        
        // Check if room exists and is not full
        $sql = "SELECT max_players, current_players, status FROM rooms WHERE id = $room_id";
        $result = mysqli_query($link, $sql);
        
        if($result && mysqli_num_rows($result) == 1){
            $row = mysqli_fetch_assoc($result);
            $max_players = (int)$row['max_players'];
            $current_players = (int)$row['current_players'];
            $status = $row['status'];
            
            if($status != 'waiting'){
                echo "<script>alert('Game already started or finished.'); window.location.href='join_lobby.php';</script>";
                exit;
            }
            
            if($current_players >= $max_players){
                echo "<script>alert('Room is full.'); window.location.href='join_lobby.php';</script>";
                exit;
            }
            
            // Check if user is already in the room
            $sql_check = "SELECT id FROM room_players WHERE room_id = $room_id AND user_id = $user_id";
            $res_check = mysqli_query($link, $sql_check);
            
            if($res_check && mysqli_num_rows($res_check) > 0){
                // User already in room, just redirect
                session_write_close();
                echo "<script>window.location.href='room.php?id=" . $room_id . "';</script>";
                exit;
            }
            
            // Add user to room_players
            $sql_insert = "INSERT INTO room_players (room_id, user_id) VALUES ($room_id, $user_id)";
            if(mysqli_query($link, $sql_insert)){
                // Update current_players count
                $sql_update = "UPDATE rooms SET current_players = current_players + 1 WHERE id = $room_id";
                mysqli_query($link, $sql_update);
                
                session_write_close();
                echo "<script>window.location.href='room.php?id=" . $room_id . "';</script>";
                exit;
            } else {
                echo "Something went wrong. Please try again later. " . mysqli_error($link);
            }
        } else {
            echo "<script>alert('Room not found.'); window.location.href='join_lobby.php';</script>";
            exit;
        }
    }
} else {
    // Redirect to join lobby if accessed directly
    echo "<script>window.location.href='join_lobby.php';</script>";
    exit;
}
?>
