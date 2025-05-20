<?php
// admin_actions/add_user_form.php
if (session_status() == PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    http_response_code(403); exit("Доступ запрещен.");
}

$form_data = $_SESSION['add_user_form_data'] ?? [];
unset($_SESSION['add_user_form_data']);
$error_message = $_SESSION['add_user_error'] ?? '';
unset($_SESSION['add_user_error']);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Добавить Пользователя - Админ Air GO</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/admin-style.css"> <!-- Путь на уровень выше -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
     <style> /* Простые стили для контейнера формы, чтобы не загромождать admin-style.css */
        body { background-color: var(--content-bg); display: flex; justify-content: center; align-items: flex-start; min-height: 100vh; padding-top: 2rem; }
        .form-page-container { background-color: var(--widget-bg); padding: 2rem; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.08); width: 100%; max-width: 600px; }
        .form-page-container h1 { text-align: center; color: var(--dark-green); margin-bottom: 1.5rem; font-size: 1.6rem; }
        .btn-container { margin-top: 1.5rem; display: flex; justify-content: flex-end; gap: 0.5rem; }
    </style>
</head>
<body>
    <div class="form-page-container">
        <h1>Добавить нового пользователя</h1>
        <?php if ($error_message): ?>
            <div class="admin-message error"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>
        <form action="handle_add_user.php" method="POST">
            <div class="form-group">
                <label for="first_name">Имя:</label>
                <input type="text" id="first_name" name="first_name" value="<?php echo htmlspecialchars($form_data['first_name'] ?? ''); ?>" required>
            </div>
            <div class="form-group">
                <label for="last_name">Фамилия (необязательно):</label>
                <input type="text" id="last_name" name="last_name" value="<?php echo htmlspecialchars($form_data['last_name'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($form_data['email'] ?? ''); ?>" required>
            </div>
            <div class="form-group">
                <label for="phone_number">Телефон (необязательно):</label>
                <input type="text" id="phone_number" name="phone_number" value="<?php echo htmlspecialchars($form_data['phone_number'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label for="password">Пароль:</label>
                <input type="password" id="password" name="password" required>
                 <small class="form-text text-muted">Минимум 8 символов, 1 заглавная, 1 строчная, 1 цифра, 1 спецсимвол.</small>
            </div>
            <div class="form-group">
                <label for="confirm_password">Подтвердите пароль:</label>
                <input type="password" id="confirm_password" name="confirm_password" required>
            </div>
            <div class="form-group">
                <input type="checkbox" id="is_admin" name="is_admin" value="1" <?php echo isset($form_data['is_admin']) && $form_data['is_admin'] == '1' ? 'checked' : ''; ?>>
                <label for="is_admin" style="display:inline; font-weight:normal;"> Сделать администратором</label>
            </div>
             <div class="form-group">
                <input type="checkbox" id="is_active" name="is_active" value="1" <?php echo (isset($form_data['is_active']) && $form_data['is_active'] == '1') || !isset($form_data['is_active']) ? 'checked' : ''; ?>> <!-- По умолчанию активен -->
                <label for="is_active" style="display:inline; font-weight:normal;"> Активен</label>
            </div>

            <div class="btn-container">
                <a href="../admin.php?tab=users-content" class="btn btn-secondary">Отмена</a>
                <button type="submit" class="btn btn-primary"><i class="fas fa-plus"></i> Добавить</button>
            </div>
        </form>
    </div>
</body>
</html>