<?php
require_once "includes/config.php";
$result = mysqli_query($link, "SHOW COLUMNS FROM rooms");
$columns = [];
while($row = mysqli_fetch_assoc($result)){
    $columns[] = $row['Field'];
}
header('Content-Type: application/json');
echo json_encode($columns);
?>