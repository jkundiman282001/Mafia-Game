<?php
require_once "api/includes/config.php";
$tables = ["rooms", "room_players", "users", "messages", "votes", "sessions"];
foreach ($tables as $table) {
    $result = mysqli_query($link, "SHOW TABLES LIKE '$table'");
    if (mysqli_num_rows($result) > 0) {
        echo "Table $table exists.\n";
        $columns = mysqli_query($link, "DESCRIBE $table");
        while ($col = mysqli_fetch_assoc($columns)) {
            echo "  - {$col['Field']} ({$col['Type']})\n";
        }
    } else {
        echo "Table $table DOES NOT exist.\n";
    }
}
