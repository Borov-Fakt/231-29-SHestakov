<?php
// delete_user.php - Обрабатывает удаление пользователя админом

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', '/path/to/your/php-error.log'); // УКАЖИТЕ ПРАВИЛЬНЫЙ ПУТЬ

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');
$defaultAvatar = null; // Путь к аватару по умолчанию, если есть

// --- Проверка аутентификации АДМИНИСТРАТОРА ---
if (!isset($_SESSION['is_admin_logged_in']) || $_SESSION['is_admin_logged_in'] !== true) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Доступ запрещен.']);
    exit;
}

// --- Проверка метода запроса ---
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Неверный метод запроса.']);
    exit;
}

// --- Получение ID пользователя ---
$userId = isset($_POST['userId']) ? filter_var($_POST['userId'], FILTER_VALIDATE_INT) : null;
// Получаем флаг, удалять ли аватар (из JS)
$shouldDeleteAvatar = isset($_POST['deleteAvatar']) && $_POST['deleteAvatar'] === 'true';


if ($userId === null || $userId === false || $userId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Неверный ID пользователя.']);
    exit;
}

// --- Подключение к БД ---
require 'db.php';

// --- Получение пути к аватару перед удалением ---
$avatarPath = null;
if ($shouldDeleteAvatar) {
    $stmt_get_avatar = $conn->prepare("SELECT avatar FROM users WHERE id = ?");
    if ($stmt_get_avatar) {
        $stmt_get_avatar->bind_param("i", $userId);
        $stmt_get_avatar->execute();
        $result_avatar = $stmt_get_avatar->get_result();
        if ($row_avatar = $result_avatar->fetch_assoc()) {
            $avatarPath = $row_avatar['avatar'];
        }
        $stmt_get_avatar->close();
    } else {
         error_log("MySQL Prepare Error (get avatar before delete): " . $conn->error);
         // Не прерываем выполнение, просто не сможем удалить файл
    }
}


// --- Удаление пользователя из БД ---
$stmt_delete = $conn->prepare("DELETE FROM users WHERE id = ?");
if (!$stmt_delete) {
    error_log("MySQL Prepare Error (delete user): " . $conn->error);
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Ошибка сервера при подготовке удаления.']);
    $conn->close();
    exit;
}

$stmt_delete->bind_param("i", $userId);

if ($stmt_delete->execute()) {
    if ($stmt_delete->affected_rows > 0) {
        $message = 'Пользователь успешно удален.';

         // Удаляем файл аватара, если он был и не является дефолтным
         if ($shouldDeleteAvatar && !empty($avatarPath) && $avatarPath !== $defaultAvatar && file_exists($avatarPath)) {
             if (@unlink($avatarPath)) {
                 $message .= " Файл аватара удален.";
             } else {
                  error_log("Could not delete avatar file during user deletion: " . $avatarPath);
                  $message .= " Не удалось удалить файл аватара.";
             }
         }

        echo json_encode(['success' => true, 'message' => $message]);
    } else {
        // Пользователь с таким ID не найден (уже удален?)
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Пользователь с указанным ID не найден для удаления.']);
    }
} else {
    error_log("MySQL Execute Error (delete user): " . $stmt_delete->error);
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Ошибка сервера при удалении пользователя.']);
}

$stmt_delete->close();
$conn->close();
?>