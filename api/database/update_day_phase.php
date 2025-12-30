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

addColumn($link, 'rooms', 'phase_start_time', 'DATETIME DEFAULT NULL');

// Also add a table for votes
$sql_votes = "CREATE TABLE IF NOT EXISTS votes (
    id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
    room_id INT NOT NULL,
    voter_id INT NOT NULL,
    target_id INT NOT NULL,
    round INT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (room_id) REFERENCES rooms(id),
    FOREIGN KEY (voter_id) REFERENCES users(id),
    FOREIGN KEY (target_id) REFERENCES users(id),
    UNIQUE KEY (room_id, voter_id, round)
)";

if(mysqli_query($link, $sql_votes)){
    echo "Table 'votes' created successfully or already exists.<br>";
} else {
    echo "Error creating table 'votes': " . mysqli_error($link) . "<br>";
}
