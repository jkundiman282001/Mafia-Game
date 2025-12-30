<?php
define('DB_SERVER', '127.0.0.1');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');

// Connect to MySQL server
$link = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD);

if($link === false){
    die("ERROR: Could not connect. " . mysqli_connect_error());
}

// Create database
$sql = "CREATE DATABASE IF NOT EXISTS mafia_game";
if(mysqli_query($link, $sql)){
    echo "Database created successfully or already exists.<br>";
} else{
    echo "ERROR: Could not execute $sql. " . mysqli_error($link) . "<br>";
}

// Select database
mysqli_select_db($link, 'mafia_game');

// Create table
$sql = "CREATE TABLE IF NOT EXISTS users (
    id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)";

if(mysqli_query($link, $sql)){
    echo "Table 'users' created successfully or already exists.<br>";
} else{
    echo "ERROR: Could not execute $sql. " . mysqli_error($link) . "<br>";
}

// Create rooms table
$sql = "CREATE TABLE IF NOT EXISTS rooms (
    id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
    room_name VARCHAR(50) NOT NULL,
    creator_id INT NOT NULL,
    max_players INT DEFAULT 10,
    current_players INT DEFAULT 0,
    status ENUM('waiting', 'in_progress', 'finished') DEFAULT 'waiting',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (creator_id) REFERENCES users(id)
)";

if(mysqli_query($link, $sql)){
    echo "Table 'rooms' created successfully or already exists.<br>";
} else{
    echo "ERROR: Could not execute $sql. " . mysqli_error($link) . "<br>";
}

// Create room_players table
$sql = "CREATE TABLE IF NOT EXISTS room_players (
    id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
    room_id INT NOT NULL,
    user_id INT NOT NULL,
    joined_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (room_id) REFERENCES rooms(id),
    FOREIGN KEY (user_id) REFERENCES users(id),
    UNIQUE KEY unique_player_room (room_id, user_id)
)";

if(mysqli_query($link, $sql)){
    echo "Table 'room_players' created successfully or already exists.<br>";
} else{
    echo "ERROR: Could not execute $sql. " . mysqli_error($link) . "<br>";
}

// Create sessions table
$sql = "CREATE TABLE IF NOT EXISTS sessions (
    id VARCHAR(128) NOT NULL PRIMARY KEY,
    data BLOB NOT NULL,
    access INT(10) UNSIGNED NOT NULL
)";

if(mysqli_query($link, $sql)){
    echo "Table 'sessions' created successfully or already exists.<br>";
} else{
    echo "ERROR: Could not execute $sql. " . mysqli_error($link) . "<br>";
}

mysqli_close($link);
?>