<?php
session_start();
require 'db.php'; // Подключение к базе

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username_or_email = trim($_POST["username_or_email"]);
    $password = $_POST["pass"];

    // Проверяем, есть ли такой пользователь по email или никнейму
    $stmt = $conn->prepare("SELECT id, username, email, pass FROM users WHERE username = ? OR email = ?");
    $stmt->bind_param("ss", $username_or_email, $username_or_email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        // Пользователь найден, получаем данные
        $stmt->bind_result($id, $username, $email, $password_hash);
        $stmt->fetch();

        // Проверяем пароль
        if (password_verify($password, $password_hash)) {
            echo "Вход успешен! ";
        } else {
            $_SESSION["error"] = "Неверный пароль!";
            header("Location: input.php");
            exit();
        }
    } else {
        $_SESSION["error"] = "Пользователь не найден!";
        header("Location: input.php");
        exit();
    }

    $stmt->close();
    $conn->close();
    }
?>