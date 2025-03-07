 <?php

require 'db.php'; // Подключаем файл с настройками базы

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST["username"]);
    $email = trim($_POST["email"]);
    $password = $_POST["pass"];
   
    // Хешируем пароль
    $password_hash = password_hash($password, PASSWORD_BCRYPT);

    // Проверяем, существует ли уже такой никнейм или email
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
    $stmt->bind_param("ss", $username, $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        die("Ошибка: Никнейм или Email уже зарегистрированы!");
    }

    $stmt->close();

    // SQL-запрос на добавление пользователя
    $stmt = $conn->prepare("INSERT INTO users (username, email, pass) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $username, $email, $password_hash);

    if ($stmt->execute()) {
        echo "Регистрация успешна!";
    } else {
        echo "Ошибка: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();
}
?>