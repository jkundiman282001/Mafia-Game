<?php
require_once "includes/session.php";
require_once "includes/config.php";

// Check if user is logged in
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    echo json_encode(["status" => "error", "message" => "Unauthorized"]);
    exit;
}

if(isset($_GET["room_id"])){
    $room_id = $_GET["room_id"];
    
    $sql = "SELECT m.message, m.created_at, u.username 
            FROM messages m 
            JOIN users u ON m.user_id = u.id 
            WHERE m.room_id = ? 
            ORDER BY m.created_at ASC";
            
    if($stmt = mysqli_prepare($link, $sql)){
        mysqli_stmt_bind_param($stmt, "i", $room_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        $messages = [];
        while($row = mysqli_fetch_assoc($result)){
            $messages[] = $row;
        }
        
        echo json_encode(["status" => "success", "messages" => $messages]);
        mysqli_stmt_close($stmt);
    }
}
?>
