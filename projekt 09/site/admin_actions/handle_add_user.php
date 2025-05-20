<?php
// admin_actions/handle_add_user.php
if (session_status() == PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    http_response_code(403); exit("Доступ запрещен.");
}
require '../db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $_SESSION['add_user_form_data'] = $_POST; // Для автозаполнения

    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']) ?: null;
    $email = trim($_POST['email']);
    $phone_number = trim($_POST['phone_number']) ?: null;
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $is_admin = isset($_POST['is_admin']) ? 1 : 0;
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    // Валидация (базовая)
    if (empty($first_name) || empty($email) || empty($password) || empty($confirm_password)) {
        $_SESSION['add_user_error'] = "Пожалуйста, заполните все обязательные поля (Имя, Email, Пароль, Подтверждение пароля).";
        header("Location: add_user_form.php"); exit();
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['add_user_error'] = "Некорректный формат email.";
        header("Location: add_user_form.php"); exit();
    }
    if ($password !== $confirm_password) {
        $_SESSION['add_user_error'] = "Пароли не совпадают.";
        header("Location: add_user_form.php"); exit();
    }
    if (!preg_match('/^(?=.*[A-Z])(?=.*[a-z])(?=.*\d)(?=.*[!@#$%^&*()_+\-=\[\]{};\':"\\|,.<>\/?~`])[A-Za-z\d!@#$%^&*()_+\-=\[\]{};\':"\\|,.<>\/?~`]{8,50}$/', $password)) {
        $_SESSION['add_user_error'] = "Пароль не соответствует требованиям безопасности.";
        header("Location: add_user_form.php"); exit();
    }

    // Проверка на существующий email
    $stmt_check = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
    if(!$stmt_check){ $_SESSION['add_user_error'] = "Ошибка DB (C)."; header("Location: add_user_form.php"); exit(); }
    $stmt_check->bind_param("s", $email);
    $stmt_check->execute();
    if ($stmt_check->get_result()->num_rows > 0) {
        $_SESSION['add_user_error'] = "Пользователь с таким email уже существует.";
        $stmt_check->close();
        header("Location: add_user_form.php"); exit();
    }
    $stmt_check->close();

    $password_hash = password_hash($password, PASSWORD_BCRYPT);

    $stmt_insert = $conn->prepare("INSERT INTO users (first_name, last_name, email, phone_number, password_hash, is_admin, is_active) VALUES (?, ?, ?, ?, ?, ?, ?)");
     if(!$stmt_insert){ $_SESSION['add_user_error'] = "Ошибка DB (I). " . $conn->error; header("Location: add_user_form.php"); exit(); }
    $stmt_insert->bind_param("sssssii", $first_name, $last_name, $email, $phone_number, $password_hash, $is_admin, $is_active);

    if ($stmt_insert->execute()) {
        unset($_SESSION['add_user_form_data']);
        $_SESSION['user_management_message'] = "Пользователь '" . htmlspecialchars($email) . "' успешно добавлен.";
        $_SESSION['user_management_message_type'] = "success";
        header("Location: ../admin.php?tab=users-content"); exit();
    } else {
        error_log("Ошибка добавления пользователя: " . $stmt_insert->error);
        $_SESSION['add_user_error'] = "Ошибка при добавлении пользователя. " . $stmt_insert->error;
        header("Location: add_user_form.php"); exit();
    }
    $stmt_insert->close();
} else {
    header("Location: add_user_form.php"); exit();
}
?>