<?php
// logout.php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Уничтожаем все данные сессии
$_SESSION = array(); // Очистить массив $_SESSION

// Если используется идентификатор сессии в cookies, его тоже нужно удалить
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

session_destroy(); // Уничтожить сессию

header("Location: login.php"); // Перенаправить на страницу входа
exit();
?>