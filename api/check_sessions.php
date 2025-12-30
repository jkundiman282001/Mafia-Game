<?php
require_once "includes/config.php";
$result = mysqli_query($link, "SHOW TABLES LIKE 'sessions'");
header('Content-Type: application/json');
echo json_encode(['sessions_table_exists' => mysqli_num_rows($result) > 0]);
?>