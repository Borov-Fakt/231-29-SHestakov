<?php
// admin_actions/handle_change_admin_password.php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Проверка аутентификации и прав администратора
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    http_response_code(403);
    echo "Доступ запрещен.";
    exit();
}

require '../db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $admin_id = $_SESSION['user_id'];
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_new_password = $_POST['confirm_new_password'];

    // 1. Валидация введенных данных
    if (empty($current_password) || empty($new_password) || empty($confirm_new_password)) {
        $_SESSION['admin_password_message'] = "Все поля для смены пароля должны быть заполнены.";
        $_SESSION['admin_password_message_type'] = "error";
        header("Location: change_admin_password.php");
        exit();
    }

    if ($new_password !== $confirm_new_password) {
        $_SESSION['admin_password_message'] = "Новый пароль и его подтверждение не совпадают.";
        $_SESSION['admin_password_message_type'] = "error";
        header("Location: change_admin_password.php");
        exit();
    }

    // Требования к сложности нового пароля (аналогично регистрации)
    if (!preg_match('/^(?=.*[A-Z])(?=.*[a-z])(?=.*\d)(?=.*[!@#$%^&*()_+\-=\[\]{};\':"\\|,.<>\/?~`])[A-Za-z\d!@#$%^&*()_+\-=\[\]{};\':"\\|,.<>\/?~`]{8,50}$/', $new_password)) {
        $_SESSION['admin_password_message'] = "Новый пароль не соответствует требованиям: 8-50 символов, минимум 1 заглавная, 1 строчная, 1 цифра, 1 спецсимвол.";
        $_SESSION['admin_password_message_type'] = "error";
        header("Location: change_admin_password.php");
        exit();
    }

    // 2. Проверка текущего пароля
    $stmt_check = $conn->prepare("SELECT password_hash FROM users WHERE user_id = ?");
    if (!$stmt_check) { /* обработка ошибки */ $_SESSION['admin_password_message'] = "Ошибка сервера P1."; $_SESSION['admin_password_message_type'] = "error"; header("Location: change_admin_password.php"); exit(); }
    $stmt_check->bind_param("i", $admin_id);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();

    if ($result_check->num_rows === 1) {
        $user_data = $result_check->fetch_assoc();
        if (!password_verify($current_password, $user_data['password_hash'])) {
            $_SESSION['admin_password_message'] = "Текущий пароль введен неверно.";
            $_SESSION['admin_password_message_type'] = "error";
            $stmt_check->close();
            header("Location: change_admin_password.php");
            exit();
        }
    } else {
        // Маловероятно, если сессия активна
        $_SESSION['admin_password_message'] = "Ошибка: пользователь не найден.";
        $_SESSION['admin_password_message_type'] = "error";
        $stmt_check->close();
        header("Location: change_admin_password.php");
        exit();
    }
    $stmt_check->close();

    // 3. Хеширование и обновление нового пароля
    $new_password_hash = password_hash($new_password, PASSWORD_BCRYPT);
    $stmt_update = $conn->prepare("UPDATE users SET password_hash = ? WHERE user_id = ?");
    if (!$stmt_update) { /* обработка ошибки */ $_SESSION['admin_password_message'] = "Ошибка сервера P2."; $_SESSION['admin_password_message_type'] = "error"; header("Location: change_admin_password.php"); exit(); }

    $stmt_update->bind_param("si", $new_password_hash, $admin_id);
    if ($stmt_update->execute()) {
        $_SESSION['admin_profile_message'] = "Пароль успешно изменен."; // Сообщение для основной страницы админки
        $_SESSION['admin_profile_message_type'] = "success";
        // Можно добавить принудительный выход после смены пароля для повышения безопасности
        // session_destroy();
        // header("Location: ../login.php?message=password_changed"); exit(); 
        header("Location: ../admin.php#profile-content");
    } else {
        error_log("Admin Password Update Error (execute): " . $stmt_update->error);
        $_SESSION['admin_password_message'] = "Ошибка при обновлении пароля.";
        $_SESSION['admin_password_message_type'] = "error";
        header("Location: change_admin_password.php");
    }
    $stmt_update->close();
    exit();

} else {
    header("Location: change_admin_password.php");
    exit();
}
?>