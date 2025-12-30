<?php
require_once __DIR__ . "/../includes/config.php";

// Function to safely add a column if it doesn't exist
function addColumn($link, $table, $column, $definition) {
    $result = mysqli_query($link, "SHOW COLUMNS FROM `$table` LIKE '$column'");
    if (mysqli_num_rows($result) == 0) {
        $sql = "ALTER TABLE `$table` ADD COLUMN `$column` $definition";
        if (mysqli_query($link, $sql)) {
            echo "Column '$column' added to '$table'.<br>";
        } else {
            echo "Error adding column '$column': " . mysqli_error($link) . "<br>";
        }
    } else {
        echo "Column '$column' already exists in '$table'.<br>";
    }
}

addColumn($link, 'rooms', 'detective_target', 'INT DEFAULT NULL');
addColumn($link, 'rooms', 'current_turn', "ENUM('Killer', 'Doctor', 'Detective', 'None') DEFAULT 'Killer'");
?>