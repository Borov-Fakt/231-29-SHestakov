<?php
// handle-edit-profile.php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
require 'db.php';
$user_id = $_SESSION['user_id'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $_SESSION['form_data'] = $_POST; // Сохранить для автозаполнения в случае ошибки

    $first_name = trim($_POST['first_name']);
    $email = trim($_POST['email']);
    $phone_number = trim($_POST['phone_number']) ?: null; // Если пусто, то NULL

    // --- Валидация ---
    if (empty($first_name)) {
        $_SESSION['edit_profile_error'] = "Имя не может быть пустым.";
        header("Location: edit-profile.php");
        exit();
    }
    if (empty($email)) {
        $_SESSION['edit_profile_error'] = "Email не может быть пустым.";
        header("Location: edit-profile.php");
        exit();
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['edit_profile_error'] = "Некорректный формат email.";
        header("Location: edit-profile.php");
        exit();
    }
    // Валидация телефона (опционально, более строгая)
    // if ($phone_number && !preg_match('/^(\+7|8)[\s(]?(\d{3})[\s)]?(\d{3})[\s-]?(\d{2})[\s-]?(\d{2})$/', $phone_number)) {
    //    $_SESSION['edit_profile_error'] = "Некорректный формат телефона.";
    //    header("Location: edit-profile.php");
    //    exit();
    // }


    // Проверка email на уникальность (если email был изменен и он не совпадает с текущим email пользователя)
    $stmt_check_email = $conn->prepare("SELECT user_id FROM users WHERE email = ? AND user_id != ?");
    $stmt_check_email->bind_param("si", $email, $user_id);
    $stmt_check_email->execute();
    $result_check_email = $stmt_check_email->get_result();
    if ($result_check_email->num_rows > 0) {
        $_SESSION['edit_profile_error'] = "Этот email уже используется другим пользователем.";
        $stmt_check_email->close();
        header("Location: edit-profile.php");
        exit();
    }
    $stmt_check_email->close();


    // --- Обновление данных ---
    $stmt_update = $conn->prepare("UPDATE users SET first_name = ?, email = ?, phone_number = ? WHERE user_id = ?");
    if (!$stmt_update) { die("Ошибка подготовки запроса (обновление профиля): " . $conn->error); }
    $stmt_update->bind_param("sssi", $first_name, $email, $phone_number, $user_id);

    if ($stmt_update->execute()) {
        unset($_SESSION['form_data']);
        $_SESSION['profile_message'] = "Личная информация успешно обновлена!";
        $_SESSION['profile_message_type'] = "success";
    } else {
        $_SESSION['profile_message'] = "Ошибка при обновлении информации: " . $stmt_update->error;
        $_SESSION['profile_message_type'] = "error";
    }
    $stmt_update->close();
    header("Location: profile.php");
    exit();

} else {
    header("Location: profile.php");
    exit();
}
?>