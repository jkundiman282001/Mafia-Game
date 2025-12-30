<?php
require_once "includes/config.php";

// Add room_code column to rooms table
$sql = "ALTER TABLE rooms ADD COLUMN room_code VARCHAR(10) NOT NULL UNIQUE AFTER id";

if(mysqli_query($link, $sql)){
    echo "Column 'room_code' added successfully.<br>";
} else{
    echo "ERROR: Could not execute $sql. " . mysqli_error($link) . "<br>";
    // If error is duplicate column, that's fine
}

mysqli_close($link);
?>