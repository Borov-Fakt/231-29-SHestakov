<?php
session_start();
header('Content-Type: application/json');

// Проверяем, есть ли в сессии пользователь
$response = ["loggedIn" => isset($_SESSION['user'])];

echo json_encode($response);
exit;
?>
