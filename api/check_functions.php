<?php
header('Content-Type: application/json');
echo json_encode(['mysqli_stmt_get_result_exists' => function_exists('mysqli_stmt_get_result')]);
?>