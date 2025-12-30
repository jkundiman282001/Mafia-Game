<?php
require_once __DIR__ . "/../includes/config.php";

$sql = "ALTER TABLE rooms 
        ADD COLUMN phase ENUM('night', 'day') DEFAULT 'night',
        ADD COLUMN round INT DEFAULT 1,
        ADD COLUMN killer_target INT DEFAULT NULL,
        ADD COLUMN doctor_target INT DEFAULT NULL,
        ADD COLUMN detective_target INT DEFAULT NULL,
        ADD COLUMN action_count INT DEFAULT 0";

if(mysqli_query($link, $sql)){
    echo "Rooms table updated successfully with game state columns.";
} else {
    echo "Error updating table: " . mysqli_error($link);
}
?>