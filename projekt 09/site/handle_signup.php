<?php
// handle_signup.php

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require 'db.php';

$_SESSION['form_data'] = $_POST; // Сохраняем данные для автозаполнения

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $first_name = trim($_POST["first_name"]);
    $email = trim($_POST["email"]);
    $password = $_POST["password"]; // Не тримим пароль при получении
    $confirm_password = $_POST["confirm_password"];
    $terms_agreed = isset($_POST["terms"]);

    // 1. Валидация имени (серверная)
    if (empty($first_name)) {
        $_SESSION["signup_error"] = "Поле 'Имя' не должно быть пустым.";
        header("Location: signup.php");
        exit();
    }
    // Можно добавить более строгую проверку имени, если нужно, например, на длину или символы.
    // Ваш JS-шаблон: /^(?=.*[a-zA-Zа-яА-ЯёЁ])[a-zA-Zа-яА-ЯёЁ0-9_]{2,50}$/;
    // Если используете его, то:
    // if (!preg_match('/^(?=.*[a-zA-Zа-яА-ЯёЁ])[a-zA-Zа-яА-ЯёЁ0-9_]{2,50}$/u', $first_name)) {
    //     $_SESSION["signup_error"] = "Имя должно содержать 2-50 символов (буквы, возможно цифры, но без спец. символов, кроме '_').";
    //     header("Location: signup.php");
    //     exit();
    // }


    // 2. Валидация Email
    if (empty($email)) {
        $_SESSION["signup_error"] = "Поле 'Email' не должно быть пустым.";
        header("Location: signup.php");
        exit();
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION["signup_error"] = "Некорректный формат email (например, user@example.com).";
        header("Location: signup.php");
        exit();
    }

    // 3. Валидация пароля (серверная, повторяет JS)
    if (empty($password)) {
        $_SESSION["signup_error"] = "Поле 'Пароль' не должно быть пустым.";
        header("Location: signup.php");
        exit();
    }
    // Ваш JS-шаблон: /^(?=.*[A-Z])(?=.*[a-z])(?=.*\d)(?=.*[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?~`])[A-Za-z\d!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?~`]{8,50}$/
    if (!preg_match('/^(?=.*[A-Z])(?=.*[a-z])(?=.*\d)(?=.*[!@#$%^&*()_+\-=\[\]{};\':"\\|,.<>\/?~`])[A-Za-z\d!@#$%^&*()_+\-=\[\]{};\':"\\|,.<>\/?~`]{8,50}$/', $password)) {
        $_SESSION["signup_error"] = "Пароль: 8-50 симв., мин. 1 загл., 1 строч., 1 цифра, 1 спецсимвол.";
        header("Location: signup.php");
        exit();
    }


    // 4. Проверка совпадения паролей
    if (empty($confirm_password)) {
        $_SESSION["signup_error"] = "Поле 'Подтвердите пароль' не должно быть пустым.";
        header("Location: signup.php");
        exit();
    }
    if ($password !== $confirm_password) {
        $_SESSION["signup_error"] = "Пароли не совпадают.";
        header("Location: signup.php");
        exit();
    }

    // 5. Проверка согласия с условиями
    if (!$terms_agreed) {
        $_SESSION["signup_error"] = "Необходимо принять Условия использования и Политику конфиденциальности.";
        header("Location: signup.php");
        exit();
    }

    // Проверка на существующий email (остается как было)
    $stmt_check = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
    if ($stmt_check === false) { /* ... обработка ошибки ... */ $_SESSION["signup_error"]="DB Error C1"; header("Location: signup.php"); exit(); }
    $stmt_check->bind_param("s", $email);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();

    if ($result_check->num_rows > 0) {
        $_SESSION["signup_error"] = "Пользователь с таким email уже зарегистрирован.";
        $stmt_check->close();
        header("Location: signup.php");
        exit();
    }
    $stmt_check->close();

    // Хеширование пароля (остается как было)
    $password_hash = password_hash($password, PASSWORD_BCRYPT);

    // Вставка пользователя (остается как было, first_name, email, password_hash)
    $stmt_insert = $conn->prepare("INSERT INTO users (first_name, email, password_hash) VALUES (?, ?, ?)");
    if ($stmt_insert === false) { /* ... обработка ошибки ... */  $_SESSION["signup_error"]="DB Error I1"; header("Location: signup.php"); exit(); }
    $stmt_insert->bind_param("sss", $first_name, $email, $password_hash);

    if ($stmt_insert->execute()) {
        unset($_SESSION['form_data']);
        $_SESSION["signup_success"] = "Регистрация прошла успешно! Теперь вы можете войти."; // Сообщение для страницы логина
        header("Location: login.php");
        exit();
    } else {
        error_log("Ошибка выполнения (insert_user): " . $stmt_insert->error);
        $_SESSION["signup_error"] = "Произошла ошибка при регистрации. Попробуйте еще раз.";
        header("Location: signup.php");
        exit();
    }

    $stmt_insert->close();

} else {
    header("Location: signup.php");
    exit();
}
?>