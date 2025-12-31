<?php
require_once __DIR__ . '/../includes/config.php';

// The database connection is already established in config.php as $link

// Create tables from database.sql
$sql = file_get_contents(__DIR__ . '/database.sql');
$queries = explode(';', $sql);

foreach ($queries as $q) {
    if (trim($q)) {
        if (mysqli_query($link, $q)) {
            echo "Success: " . substr(trim($q), 0, 50) . "...\n";
        } else {
            echo "Error: " . mysqli_error($link) . "\n";
        }
    }
}

echo "Database setup completed.\n";
?>
