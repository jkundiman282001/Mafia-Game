<?php
require_once __DIR__ . "/../includes/config.php";

$sql = "ALTER TABLE messages MODIFY user_id INT NULL";

if(mysqli_query($link, $sql)){
    echo "Messages table updated: user_id is now nullable for system messages.";
} else {
    echo "Error updating table: " . mysqli_error($link);
}
?>