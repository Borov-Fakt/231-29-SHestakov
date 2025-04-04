<?php
// auth.php - Обработчик входа пользователей (включая админов)

// Всегда начинаем сессию в самом начале
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require 'db.php'; // Подключение к базе (использует $conn из db.php)

// Очищаем предыдущие ошибки при новой попытке входа
unset($_SESSION["error"]); // Хорошая практика

// Проверяем, что запрос был методом POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Проверяем, что поля не пустые (базовая проверка)
    if (empty($_POST["username_or_email"]) || empty($_POST["pass"])) {
        $_SESSION["error"] = "Пожалуйста, заполните все поля.";
        header("Location: input.php"); // Перенаправляем на форму входа
        exit();
    }

    $username_or_email = trim($_POST["username_or_email"]);
    $password = $_POST["pass"]; // Пароль не тримим

    // Готовим запрос, выбирая ВСЕ необходимые поля, включая is_admin
    $stmt = $conn->prepare("SELECT id, username, email, pass, is_admin FROM users WHERE username = ? OR email = ?");

    // Проверка на ошибку подготовки запроса
    if ($stmt === false) {
        error_log("Ошибка подготовки запроса в auth.php: " . $conn->error);
        $_SESSION["error"] = "Произошла ошибка сервера (DB Prepare). Попробуйте позже.";
        header("Location: input.php");
        exit();
    }

    // Привязываем параметры и выполняем запрос
    $stmt->bind_param("ss", $username_or_email, $username_or_email);
    $execute_success = $stmt->execute();

    // Проверка на ошибку выполнения запроса
    if ($execute_success === false) {
        error_log("Ошибка выполнения запроса в auth.php: " . $stmt->error);
        $_SESSION["error"] = "Произошла ошибка сервера (DB Execute). Попробуйте позже.";
        $stmt->close();
        // $conn->close(); // Закрывать соединение лучше в конце или не закрывать, если оно еще нужно
        header("Location: input.php");
        exit();
    }

    $result = $stmt->get_result(); // Получаем результат

    if ($result->num_rows === 1) { // Строго одна строка должна быть найдена
        // Пользователь найден, получаем данные
        $user_data = $result->fetch_assoc();

        // Проверяем пароль с помощью password_verify
        if (password_verify($password, $user_data['pass'])) {
            // Пароль верный! Создаем сессию пользователя.

            // Очищаем предыдущую сессию пользователя (если была) на всякий случай
            unset($_SESSION['user']);
            unset($_SESSION['is_admin_logged_in']); // <-- Очищаем и старый флаг админа

            // Сохраняем основные данные пользователя
            $_SESSION['user'] = [
                'id' => $user_data['id'],
                'nickname' => $user_data['username'],
                'email' => $user_data['email'],
                'is_admin' => (bool)$user_data['is_admin'] // Сохраняем статус админа (true/false)
            ];

            // Очищаем ошибку, если она была установлена ранее
            unset($_SESSION["error"]);

            // --- ПРОВЕРКА РОЛИ И УСТАНОВКА ФЛАГА АДМИНА ---
            if ($_SESSION['user']['is_admin'] === true) {
                // === ВАЖНО: Устанавливаем флаг, который проверяет admin.php ===
                $_SESSION['is_admin_logged_in'] = true;
                // ==========================================================

                // и перенаправляем в админку
                header("Location: admin.php");
                exit(); // ОБЯЗАТЕЛЬНО выходим после редиректа
            } else {
                // Если пользователь - обычный, убеждаемся, что флаг админа не установлен
                // Флаг 'is_admin_logged_in' не устанавливаем или можно явно установить в false
                // $_SESSION['is_admin_logged_in'] = false;
                // и перенаправляем в профиль
                header("Location: profile.php");
                exit(); // ОБЯЗАТЕЛЬНО выходим после редиректа
            }
            // --- КОНЕЦ ПРОВЕРКИ РОЛИ ---

        } else {
            // Пароль неверный
            $_SESSION["error"] = "Неверный пароль!";
            header("Location: input.php");
            exit();
        }
    } else if ($result->num_rows === 0) {
        // Пользователь не найден
        $_SESSION["error"] = "Пользователь с таким именем или email не найден!";
        header("Location: input.php");
        exit();
    } else {
         // Найдено больше одного пользователя - это ошибка данных
         error_log("Критическая ошибка: найдено несколько пользователей с email/username: " . htmlspecialchars($username_or_email));
         $_SESSION["error"] = "Произошла ошибка данных. Обратитесь в поддержку.";
         header("Location: input.php");
         exit();
    }

    // Закрываем результат и подготовленный запрос
    $stmt->close();
    // $conn->close(); // Закрывать соединение здесь необязательно

} else {
    // Если кто-то зашел на auth.php напрямую GET-запросом или другим методом
    $_SESSION["error"] = "Неверный метод запроса."; // Можно добавить сообщение об ошибке
    header("Location: input.php"); // Перенаправить на форму входа
    exit();
}
?>