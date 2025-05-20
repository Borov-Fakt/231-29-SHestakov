
<?php
$servername = "localhost"; // или ваш хост
$username_db = "root";      // ваш пользователь БД
$password_db = "";          // ваш пароль БД
$dbname = "bd";       // ваше имя БД

// Создаем соединение
$conn = new mysqli($servername, $username_db, $password_db, $dbname);

// Проверяем соединение
if ($conn->connect_error) {
    // Не выводите ошибку напрямую пользователю в продакшене
    error_log("Ошибка соединения: " . $conn->connect_error);
    die("Ошибка соединения с базой данных. Пожалуйста, попробуйте позже.");
}

// Устанавливаем кодировку (рекомендуется)
$conn->set_charset("utf8mb4");
?>