<?php
// admin_actions/change_admin_password.php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Проверка аутентификации и прав администратора
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    http_response_code(403);
    echo "Доступ запрещен.";
    exit();
}

$admin_email_display = $_SESSION['user_email'] ?? 'admin@example.com';

// Сообщения
$password_message = '';
$password_message_type = '';
if (isset($_SESSION['admin_password_message'])) {
    $password_message = $_SESSION['admin_password_message'];
    $password_message_type = $_SESSION['admin_password_message_type'] ?? 'info';
    unset($_SESSION['admin_password_message'], $_SESSION['admin_password_message_type']);
}

?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Изменить Пароль Администратора - Air GO</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Пути к CSS должны быть скорректированы, т.к. мы в папке admin_actions -->
    <link rel="stylesheet" href="../css/admin-style.css"> 
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { background-color: var(--content-bg); display: flex; justify-content: center; align-items: center; min-height: 100vh; padding: 20px; }
        .change-password-container { background-color: var(--widget-bg); padding: 2.5rem; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); width: 100%; max-width: 500px; }
        .change-password-container h1 { text-align: center; color: var(--dark-green); margin-bottom: 1.5rem; font-size: 1.6rem;}
        .form-group label { font-weight: 500; }
        .btn-container { margin-top: 1.5rem; display: flex; justify-content: flex-end; gap: 0.5rem; }
        /* Стили admin-message из admin-style.css должны подхватиться */
    </style>
</head>
<body>
    <div class="change-password-container">
        <div style="text-align: center; margin-bottom: 1rem;">
             <a href="../admin.php" class="logo" style="font-size: 1.8rem; color: var(--primary-green); text-decoration:none;">Air GO <span class="admin-tag">Admin</span></a>
        </div>
        <h1>Изменить пароль</h1>

        <?php if ($password_message): ?>
            <div class="admin-message <?php echo $password_message_type; ?>">
                <?php echo htmlspecialchars($password_message); ?>
            </div>
        <?php endif; ?>

        <form action="handle_change_admin_password.php" method="POST">
            <div class="form-group">
                <label for="current_password">Текущий пароль:</label>
                <input type="password" id="current_password" name="current_password" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="new_password">Новый пароль:</label>
                <input type="password" id="new_password" name="new_password" class="form-control" required>
                <small class="form-text text-muted">Минимум 8 символов, 1 заглавная, 1 строчная, 1 цифра, 1 спецсимвол.</small>
            </div>
            <div class="form-group">
                <label for="confirm_new_password">Подтвердите новый пароль:</label>
                <input type="password" id="confirm_new_password" name="confirm_new_password" class="form-control" required>
            </div>
            <div class="btn-container">
                <a href="../admin.php#profile-content" class="btn btn-secondary">Отмена</a>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Сохранить пароль</button>
            </div>
        </form>
    </div>
</body>
</html>