<?php
// get_user_details.php - Получает данные пользователя по ID для админки

error_reporting(E_ALL);
ini_set('display_errors', 0); // Логировать, не показывать
ini_set('log_errors', 1);

header('Content-Type: application/json');

// --- ПРОВЕРКА АВТОРИЗАЦИИ АДМИНА ---
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
// Используем данные сессии, установленные в auth.php
if (!isset($_SESSION['user']) || !isset($_SESSION['user']['is_admin']) || $_SESSION['user']['is_admin'] !== true) {
    error_log("Unauthorized access attempt to [ИМЯ_ФАЙЛА].php"); // Замените [ИМЯ_ФАЙЛА]
    http_response_code(403); // Forbidden
    // Убедитесь, что Content-Type установлен перед выводом JSON
    // header('Content-Type: application/json'); // Уже должно быть установлено выше
    echo json_encode(['error' => 'Доступ запрещен.']);
    exit;
}
// ------------------------------------

// Проверяем, передан ли userId
if (!isset($_GET['userId']) || !ctype_digit((string)$_GET['userId'])) {
     http_response_code(400); // Bad Request
    echo json_encode(['error' => 'Неверный или отсутствующий ID пользователя.']);
    exit;
}

$userId = (int)$_GET['userId'];

if ($userId <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Неверный ID пользователя.']);
    exit;
}

// --- Подключение к БД ---
require 'db.php'; // Подключает $conn (предполагаем ООП стиль из db.php)

// --- Получение данных пользователя ---
// Выбираем только нужные поля
$stmt = $conn->prepare("SELECT id, username, email, avatar FROM users WHERE id = ?");
if ($stmt === false) {
    error_log("Ошибка подготовки SQL в get_user_details.php: " . $conn->error);
    http_response_code(500); // Internal Server Error
    echo json_encode(['error' => 'Ошибка сервера при подготовке запроса.']);
    exit;
}

$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
    // Отправляем данные пользователя
    echo json_encode(['user' => $user]);
} else {
    // Пользователь не найден
    http_response_code(404); // Not Found
    echo json_encode(['error' => 'Пользователь с указанным ID не найден.']);
}

$stmt->close();
$conn->close();
?>