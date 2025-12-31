CREATE TABLE IF NOT EXISTS users (
    id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS rooms (
    id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
    room_name VARCHAR(50) NOT NULL,
    room_code VARCHAR(10) NOT NULL UNIQUE,
    creator_id INT NOT NULL,
    max_players INT DEFAULT 10,
    current_players INT DEFAULT 0,
    status ENUM('waiting', 'in_progress', 'finished') DEFAULT 'waiting',
    phase ENUM('lobby', 'night', 'day', 'trial') DEFAULT 'lobby',
    round INT DEFAULT 0,
    current_turn VARCHAR(20) DEFAULT 'None',
    killer_target INT DEFAULT NULL,
    doctor_target INT DEFAULT NULL,
    phase_start_time DATETIME DEFAULT NULL,
    winner VARCHAR(20) DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (creator_id) REFERENCES users(id),
    FOREIGN KEY (killer_target) REFERENCES users(id),
    FOREIGN KEY (doctor_target) REFERENCES users(id)
);

CREATE TABLE IF NOT EXISTS room_players (
    id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
    room_id INT NOT NULL,
    user_id INT NOT NULL,
    role ENUM('Townsfolk', 'Killer', 'Doctor', 'Investigator') DEFAULT 'Townsfolk',
    is_alive TINYINT(1) DEFAULT 1,
    vote_count INT DEFAULT 0,
    voted_for INT DEFAULT NULL,
    joined_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (room_id) REFERENCES rooms(id),
    FOREIGN KEY (user_id) REFERENCES users(id),
    UNIQUE KEY unique_player_room (room_id, user_id)
);

CREATE TABLE IF NOT EXISTS sessions (
    id VARCHAR(128) NOT NULL PRIMARY KEY,
    data BLOB NOT NULL,
    access INT(10) UNSIGNED NOT NULL
);

CREATE TABLE IF NOT EXISTS messages (
    id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
    room_id INT NOT NULL,
    user_id INT NOT NULL,
    message TEXT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (room_id) REFERENCES rooms(id),
    FOREIGN KEY (user_id) REFERENCES users(id)
);
