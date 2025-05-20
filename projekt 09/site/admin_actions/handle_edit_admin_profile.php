<?php
// admin_actions/handle_edit_admin_profile.php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Проверка аутентификации и прав администратора
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    http_response_code(403); // Запрещено
    echo "Доступ запрещен.";
    exit();
}

require '../db.php'; // Подключение к БД (путь на один уровень выше)

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $admin_id = $_SESSION['user_id'];
    $new_first_name = trim($_POST['admin_first_name']);

    if (empty($new_first_name)) {
        $_SESSION['admin_profile_message'] = "Имя администратора не может быть пустым.";
        $_SESSION['admin_profile_message_type'] = "error";
        header("Location: ../admin.php#profile-content"); // Возвращаемся на вкладку профиля
        exit();
    }
    
    // Ограничение на длину имени (пример)
    if (mb_strlen($new_first_name) > 100) {
        $_SESSION['admin_profile_message'] = "Имя администратора слишком длинное (максимум 100 символов).";
        $_SESSION['admin_profile_message_type'] = "error";
        header("Location: ../admin.php#profile-content");
        exit();
    }

    // Обновляем имя в базе данных
    $stmt = $conn->prepare("UPDATE users SET first_name = ? WHERE user_id = ?");
    if ($stmt === false) {
        error_log("Admin Profile Update Error (prepare): " . $conn->error);
        $_SESSION['admin_profile_message'] = "Ошибка сервера при подготовке запроса.";
        $_SESSION['admin_profile_message_type'] = "error";
        header("Location: ../admin.php#profile-content");
        exit();
    }

    $stmt->bind_param("si", $new_first_name, $admin_id);
    if ($stmt->execute()) {
        $_SESSION['admin_profile_message'] = "Имя успешно обновлено.";
        $_SESSION['admin_profile_message_type'] = "success";
        // Обновляем имя в сессии для немедленного отображения
        $_SESSION['user_first_name'] = $new_first_name; 
    } else {
        error_log("Admin Profile Update Error (execute): " . $stmt->error);
        $_SESSION['admin_profile_message'] = "Ошибка при обновлении имени.";
        $_SESSION['admin_profile_message_type'] = "error";
    }
    $stmt->close();

    header("Location: ../admin.php#profile-content"); // Возвращаемся на вкладку профиля
    exit();

} else {
    // Если не POST-запрос, просто редирект
    header("Location: ../admin.php");
    exit();
}
?>