<?php
require_once "includes/config.php";
$result = mysqli_query($link, "SHOW COLUMNS FROM rooms");
$rooms_columns = [];
while($row = mysqli_fetch_assoc($result)){
    $rooms_columns[] = $row['Field'];
}

$result2 = mysqli_query($link, "SHOW COLUMNS FROM room_players");
$players_columns = [];
while($row = mysqli_fetch_assoc($result2)){
    $players_columns[] = $row['Field'];
}

header('Content-Type: application/json');
echo json_encode(["rooms" => $rooms_columns, "room_players" => $players_columns]);
?>