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
        $room_code = trim($_POST["room_code"]);
        $sql = "SELECT id FROM rooms WHERE room_code = ?";
        if($stmt = mysqli_prepare($link, $sql)){
            mysqli_stmt_bind_param($stmt, "s", $room_code);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_store_result($stmt);
            if(mysqli_stmt_num_rows($stmt) == 1){
                mysqli_stmt_bind_result($stmt, $fetched_id);
                mysqli_stmt_fetch($stmt);
                $room_id = $fetched_id;
            } else {
                echo "<script>alert('Invalid Room Code.'); window.location.href='join_lobby.php';</script>";
                exit;
            }
            mysqli_stmt_close($stmt);
        }
    } elseif(isset($_POST["room_id"])){
        $room_id = $_POST["room_id"];
    }

    if($room_id){
        $user_id = $_SESSION["id"];
        
        // Check if room exists and is not full
        $sql = "SELECT max_players, current_players, status FROM rooms WHERE id = ?";
        if($stmt = mysqli_prepare($link, $sql)){
            mysqli_stmt_bind_param($stmt, "i", $room_id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_store_result($stmt);
            
            if(mysqli_stmt_num_rows($stmt) == 1){
                mysqli_stmt_bind_result($stmt, $max_players, $current_players, $status);
                mysqli_stmt_fetch($stmt);
                
                if($status != 'waiting'){
                    echo "<script>alert('Game already started or finished.'); window.location.href='join_lobby.php';</script>";
                    exit;
                }
                
                if($current_players >= $max_players){
                    echo "<script>alert('Room is full.'); window.location.href='join_lobby.php';</script>";
                    exit;
                }
                
                // Check if user is already in the room
                $sql_check = "SELECT id FROM room_players WHERE room_id = ? AND user_id = ?";
                if($stmt_check = mysqli_prepare($link, $sql_check)){
                    mysqli_stmt_bind_param($stmt_check, "ii", $room_id, $user_id);
                    mysqli_stmt_execute($stmt_check);
                    mysqli_stmt_store_result($stmt_check);
                    
                    if(mysqli_stmt_num_rows($stmt_check) > 0){
                        // User already in room, just redirect
                        session_write_close();
                        echo "<script>window.location.href='room.php?id=" . $room_id . "';</script>";
                        exit;
                    }
                    mysqli_stmt_close($stmt_check);
                }
                
                // Add user to room_players
                $sql_insert = "INSERT INTO room_players (room_id, user_id) VALUES (?, ?)";
                if($stmt_insert = mysqli_prepare($link, $sql_insert)){
                    mysqli_stmt_bind_param($stmt_insert, "ii", $room_id, $user_id);
                    if(mysqli_stmt_execute($stmt_insert)){
                        // Update current_players count
                        $sql_update = "UPDATE rooms SET current_players = current_players + 1 WHERE id = ?";
                        if($stmt_update = mysqli_prepare($link, $sql_update)){
                            mysqli_stmt_bind_param($stmt_update, "i", $room_id);
                            mysqli_stmt_execute($stmt_update);
                            mysqli_stmt_close($stmt_update);
                        }
                        
                        session_write_close();
                        echo "<script>window.location.href='room.php?id=" . $room_id . "';</script>";
                        exit;
                    } else {
                        echo "Something went wrong. Please try again later.";
                    }
                    mysqli_stmt_close($stmt_insert);
                }
            } else {
                echo "<script>alert('Room not found.'); window.location.href='join_lobby.php';</script>";
                exit;
            }
            mysqli_stmt_close($stmt);
        }
    }
} else {
    // Redirect to join lobby if accessed directly
    echo "<script>window.location.href='join_lobby.php';</script>";
    exit;
}
?>
