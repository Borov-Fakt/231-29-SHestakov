<?php
// handle-change-password.php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Проверка, залогинен ли пользователь
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require 'db.php'; // Подключение к БД
$user_id = $_SESSION['user_id'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $_SESSION['form_data'] = $_POST; // Сохраняем данные для автозаполнения при ошибке

    $current_password = $_POST['current_password']; // Не тримим пароли
    $new_password = $_POST['new_password'];
    $confirm_new_password = $_POST['confirm_new_password'];

    // --- Валидация на пустоту ---
    if (empty($current_password) || empty($new_password) || empty($confirm_new_password)) {
        $_SESSION['change_password_error'] = "Все поля обязательны для заполнения.";
        header("Location: change-password.php");
        exit();
    }

    // 1. Получить текущий хеш пароля пользователя из БД
    $stmt_get_pass = $conn->prepare("SELECT password_hash FROM users WHERE user_id = ?");
    if (!$stmt_get_pass) {
        error_log("Ошибка подготовки (get_pass): " . $conn->error);
        $_SESSION['change_password_error'] = "Ошибка сервера. Попробуйте позже. (DBP1)";
        header("Location: change-password.php");
        exit();
    }
    $stmt_get_pass->bind_param("i", $user_id);
    $stmt_get_pass->execute();
    $result_get_pass = $stmt_get_pass->get_result();
    $user_data = $result_get_pass->fetch_assoc();
    $stmt_get_pass->close();

    if (!$user_data) {
        // Это не должно случиться, если пользователь залогинен
        $_SESSION['change_password_error'] = "Ошибка: пользователь не найден.";
        header("Location: change-password.php"); // Или login.php, разлогинив его
        exit();
    }
    $current_password_hash_from_db = $user_data['password_hash'];

    // 2. Проверить текущий введенный пароль
    if (!password_verify($current_password, $current_password_hash_from_db)) {
        $_SESSION['change_password_error'] = "Текущий пароль введен неверно.";
        header("Location: change-password.php");
        exit();
    }

    // 3. Валидация нового пароля (сложность)
    // Такой же шаблон, как при регистрации
    $password_pattern = '/^(?=.*[A-Z])(?=.*[a-z])(?=.*\d)(?=.*[!@#$%^&*()_+\-=\[\]{};\':"\\|,.<>\/?~`])[A-Za-z\d!@#$%^&*()_+\-=\[\]{};\':"\\|,.<>\/?~`]{8,50}$/';
    if (!preg_match($password_pattern, $new_password)) {
        $_SESSION['change_password_error'] = "Новый пароль: 8-50 симв., мин. 1 загл., 1 строч., 1 цифра, 1 спецсимвол.";
        header("Location: change-password.php");
        exit();
    }

    // 4. Проверить совпадение нового пароля и подтверждения
    if ($new_password !== $confirm_new_password) {
        $_SESSION['change_password_error'] = "Новые пароли не совпадают.";
        header("Location: change-password.php");
        exit();
    }

    // 5. Проверить, не совпадает ли новый пароль с текущим
    if (password_verify($new_password, $current_password_hash_from_db)) {
        $_SESSION['change_password_error'] = "Новый пароль не должен совпадать с текущим.";
        header("Location: change-password.php");
        exit();
    }

    // 6. Если все проверки пройдены, хешировать новый пароль и обновить в БД
    $new_password_hash = password_hash($new_password, PASSWORD_BCRYPT);

    $stmt_update_pass = $conn->prepare("UPDATE users SET password_hash = ? WHERE user_id = ?");
    if (!$stmt_update_pass) {
        error_log("Ошибка подготовки (update_pass): " . $conn->error);
        $_SESSION['change_password_error'] = "Ошибка сервера. Попробуйте позже. (DBP2)";
        header("Location: change-password.php");
        exit();
    }
    $stmt_update_pass->bind_param("si", $new_password_hash, $user_id);

    if ($stmt_update_pass->execute()) {
        unset($_SESSION['form_data']); // Очищаем сохраненные данные формы
        $_SESSION['change_password_success'] = "Пароль успешно изменен!"; // Сообщение для этой же страницы
        // Опционально: перенаправить в профиль с сообщением об успехе там
        // $_SESSION['profile_message'] = "Пароль успешно изменен!";
        // $_SESSION['profile_message_type'] = "success";
        // header("Location: profile.php");
        // exit();
    } else {
        error_log("Ошибка выполнения (update_pass): " . $stmt_update_pass->error);
        $_SESSION['change_password_error'] = "Произошла ошибка при изменении пароля. Попробуйте еще раз.";
    }
    $stmt_update_pass->close();
    header("Location: change-password.php"); // Остаемся на странице для отображения сообщения
    exit();

} else {
    // Если доступ не через POST, перенаправить
    header("Location: profile.php");
    exit();
}
?>