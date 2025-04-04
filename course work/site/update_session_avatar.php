<?php
session_start();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['user'])) {
    $data = json_decode(file_get_contents('php://input'), true);
    $_SESSION['user']['avatar'] = $data['avatar'];
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false]);
}
?>