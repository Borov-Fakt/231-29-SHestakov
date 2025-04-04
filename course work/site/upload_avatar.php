<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user'])) {
    header('HTTP/1.1 403 Forbidden');
    exit();
}

$response = ['success' => false];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['avatar'])) {
    $user_id = $_SESSION['user']['id'];
    $upload_dir = 'uploads/avatars/';

    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    $file = $_FILES['avatar'];
    $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed_ext = ['jpg', 'jpeg', 'png', 'gif'];

    if (!in_array($file_ext, $allowed_ext)) {
        $response['error'] = 'Недопустимый формат файла';
    } elseif ($file['size'] > 2 * 1024 * 1024) { // Ограничение 2MB
        $response['error'] = 'Файл слишком большой (макс. 2MB)';
    } else {
        $file_name = 'avatar_' . $user_id . '_' . time() . '.' . $file_ext;
        $file_path = $upload_dir . $file_name;

        if (move_uploaded_file($file['tmp_name'], $file_path)) {
            // Удаляем старый аватар (если он не стандартный)
            $stmt = $conn->prepare("SELECT avatar FROM users WHERE id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $stmt->bind_result($old_avatar);
            $stmt->fetch();
            $stmt->close();

            if ($old_avatar && $old_avatar !== 'uploads/avatars/default.png' && file_exists($old_avatar)) {
                unlink($old_avatar);
            }

            // Обновляем путь в БД
            $stmt = $conn->prepare("UPDATE users SET avatar = ? WHERE id = ?");
            $stmt->bind_param("si", $file_path, $user_id);
            
            if ($stmt->execute()) {
                $_SESSION['user']['avatar'] = $file_path;
                $response = ['success' => true, 'path' => $file_path];
            } else {
                $response['error'] = 'Ошибка обновления базы данных';
                unlink($file_path);
            }
        } else {
            $response['error'] = 'Ошибка загрузки файла';
        }
    }
}

header('Content-Type: application/json');
echo json_encode($response);
?>
