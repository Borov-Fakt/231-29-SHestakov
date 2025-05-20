<?php
// handle_login.php

// Всегда начинаем сессию в самом начале
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require 'db.php'; // Подключение к базе (использует $conn из db.php)

// Очищаем предыдущие ошибки (но данные формы пока оставляем, чтобы можно было их вывести снова)
// unset($_SESSION["login_error"]); // Эту строку можно закомментировать, если очистка нужна только при успешном входе
$_SESSION['form_data'] = $_POST; // Сохраняем данные формы для повторного вывода

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    if (empty($_POST["email"]) || empty($_POST["password"])) {
        $_SESSION["login_error"] = "Пожалуйста, заполните все поля.";
        header("Location: login.php");
        exit();
    }

    $email = trim($_POST["email"]);
    $password = $_POST["password"]; // Пароль не тримим

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION["login_error"] = "Некорректный формат email.";
        header("Location: login.php");
        exit();
    }

    // Выбираем все необходимые поля, включая is_admin
    $stmt = $conn->prepare("SELECT user_id, email, password_hash, first_name, is_admin FROM users WHERE email = ? AND is_active = TRUE");

    if ($stmt === false) {
        error_log("Ошибка подготовки запроса в handle_login.php: " . $conn->error);
        $_SESSION["login_error"] = "Произошла ошибка сервера (DBP). Попробуйте позже.";
        header("Location: login.php");
        exit();
    }

    $stmt->bind_param("s", $email);
    $execute_success = $stmt->execute();

    if ($execute_success === false) {
        error_log("Ошибка выполнения запроса в handle_login.php: " . $stmt->error);
        $_SESSION["login_error"] = "Произошла ошибка сервера (DBE). Попробуйте позже.";
        $stmt->close();
        header("Location: login.php");
        exit();
    }

    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user_data = $result->fetch_assoc();

        if (password_verify($password, $user_data['password_hash'])) {
            // Пароль верный

            // Удаляем данные формы из сессии, т.к. вход успешен
            unset($_SESSION['form_data']);
            unset($_SESSION["login_error"]); // Очищаем ошибку логина при успехе

            // Обновляем ID сессии для безопасности
            session_regenerate_id(true);

            $_SESSION['user_id'] = $user_data['user_id'];
            $_SESSION['user_email'] = $user_data['email'];
            $_SESSION['user_first_name'] = $user_data['first_name'];
            $_SESSION['is_logged_in'] = true; // Общий флаг, что пользователь вошел

            // Сохраняем статус админа. Поле is_admin должно быть в таблице users.
            // Оно может быть BOOLEAN или TINYINT(1).
            if (isset($user_data['is_admin'])) {
                // Преобразуем к строгому булеву значению.
                // Если is_admin это 1 (int), (bool)1 -> true.
                // Если is_admin это '1' (string), (bool)'1' -> true.
                // Если is_admin это 0 (int), (bool)0 -> false.
                // Если is_admin это '0' (string), (bool)'0' -> true! (осторожно со строкой '0')
                // Лучше использовать явное сравнение или убедиться, что в БД хранится 0 или 1 (INT)
                $_SESSION['is_admin'] = ($user_data['is_admin'] == 1 || $user_data['is_admin'] === true);
            } else {
                // Если поля is_admin нет или оно NULL, считаем, что пользователь не админ
                $_SESSION['is_admin'] = false;
            }

            // Перенаправление в зависимости от роли
            if ($_SESSION['is_admin'] === true) {
                header("Location: admin.php"); // Перенаправляем администратора
            } else {
                // header("Location: profile.php"); // Обычного пользователя - в профиль
                // Альтернатива: перенаправить на главную страницу или туда, откуда он пришел (если это отслеживается)
                header("Location: index-log.php"); 
            }
            exit();

        } else {
            $_SESSION["login_error"] = "Неверный email или пароль.";
            header("Location: login.php");
            exit();
        }
    } else {
        // Пользователь с таким email не найден или не активен
        $_SESSION["login_error"] = "Неверный email или пароль.";
        header("Location: login.php");
        exit();
    }

    $stmt->close();
    // $conn->close(); // Не закрываем соединение здесь, если оно может понадобиться далее в том же запросе (маловероятно для этого скрипта)

} else {
    // Если не POST запрос, просто перенаправляем на страницу входа
    header("Location: login.php");
    exit();
}
?>