<?php
// admin_actions/edit_user_form.php
if (session_status() == PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    http_response_code(403); exit("Доступ запрещен.");
}
require '../db.php';

$edit_user_id = null;
$user_to_edit = null;

if (isset($_GET['user_id']) && is_numeric($_GET['user_id'])) {
    $edit_user_id = (int)$_GET['user_id'];

    // Нельзя редактировать себя через эту форму (предполагается, что админ себя редактирует через "Профиль Админа")
    if ($edit_user_id == $_SESSION['user_id']) {
         $_SESSION['user_management_message'] = "Для редактирования своего профиля используйте секцию 'Профиль Администратора'.";
         $_SESSION['user_management_message_type'] = "info";
         header("Location: ../admin.php?tab=users-content");
         exit();
    }


    $stmt = $conn->prepare("SELECT user_id, first_name, last_name, email, phone_number, is_active, is_admin FROM users WHERE user_id = ?");
    if (!$stmt) { $_SESSION['user_management_message'] = "Ошибка DB (P)."; $_SESSION['user_management_message_type'] = "error"; header("Location: ../admin.php?tab=users-content"); exit(); }
    $stmt->bind_param("i", $edit_user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 1) {
        $user_to_edit = $result->fetch_assoc();
    } else {
        $_SESSION['user_management_message'] = "Пользователь с ID " . $edit_user_id . " не найден.";
        $_SESSION['user_management_message_type'] = "error";
        header("Location: ../admin.php?tab=users-content");
        exit();
    }
    $stmt->close();
} else {
    $_SESSION['user_management_message'] = "Не указан ID пользователя для редактирования.";
    $_SESSION['user_management_message_type'] = "error";
    header("Location: ../admin.php?tab=users-content");
    exit();
}

$form_data = $_SESSION['edit_user_form_data'] ?? $user_to_edit; // Данные из сессии при ошибке, иначе из БД
unset($_SESSION['edit_user_form_data']);
$error_message = $_SESSION['edit_user_error'] ?? '';
unset($_SESSION['edit_user_error']);

// Сохраняем параметры пагинации и фильтров для кнопки "Отмена"
$return_params = http_build_query([
    'tab' => 'users-content',
    'page' => $_GET['page'] ?? 1,
    'search' => $_GET['search'] ?? '',
    'filter_active' => $_GET['filter_active'] ?? 'all'
]);

?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Редактировать Пользователя - Админ Air GO</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/admin-style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style> /* Используем стили из handle_add_user для контейнера формы */
        body { background-color: var(--content-bg); display: flex; justify-content: center; align-items: flex-start; min-height: 100vh; padding-top: 2rem; padding-bottom: 2rem; }
        .form-page-container { background-color: var(--widget-bg); padding: 2rem; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.08); width: 100%; max-width: 600px; }
        .form-page-container h1 { text-align: center; color: var(--dark-green); margin-bottom: 1.5rem; font-size: 1.6rem; }
        .btn-container { margin-top: 1.5rem; display: flex; justify-content: flex-end; gap: 0.5rem; }
    </style>
</head>
<body>
    <div class="form-page-container">
        <h1>Редактировать пользователя (ID: <?php echo $user_to_edit['user_id']; ?>)</h1>
        <?php if ($error_message): ?>
            <div class="admin-message error"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>
        <form action="handle_edit_user.php" method="POST">
            <input type="hidden" name="user_id" value="<?php echo $user_to_edit['user_id']; ?>">
            <!-- Передаем параметры для возврата -->
            <input type="hidden" name="return_page" value="<?php echo htmlspecialchars($_GET['page'] ?? '1'); ?>">
            <input type="hidden" name="return_search" value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>">
            <input type="hidden" name="return_filter_active" value="<?php echo htmlspecialchars($_GET['filter_active'] ?? 'all'); ?>">


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
            
            <fieldset style="border:1px solid var(--border-color); padding:1rem; margin-bottom:1.5rem; border-radius:4px;">
                <legend style="padding:0 0.5em; font-weight:500; color: var(--dark-gray);">Статус и Роль</legend>
                <div class="form-group" style="margin-bottom:0.5rem;">
                    <input type="checkbox" id="is_active" name="is_active" value="1" <?php echo (isset($form_data['is_active']) && $form_data['is_active']) ? 'checked' : ''; ?>>
                    <label for="is_active" style="display:inline; font-weight:normal;"> Активен</label>
                </div>
                <div class="form-group">
                    <input type="checkbox" id="is_admin" name="is_admin" value="1" <?php echo (isset($form_data['is_admin']) && $form_data['is_admin']) ? 'checked' : ''; ?>>
                    <label for="is_admin" style="display:inline; font-weight:normal;"> Сделать администратором</label>
                </div>
            </fieldset>
            
            <div class="form-group">
                <label for="new_password">Новый пароль (оставьте пустым, если не меняете):</label>
                <input type="password" id="new_password" name="new_password">
                <small class="form-text text-muted">Если введен, должен соответствовать требованиям безопасности.</small>
            </div>
            <div class="form-group">
                <label for="confirm_new_password">Подтвердите новый пароль:</label>
                <input type="password" id="confirm_new_password" name="confirm_new_password">
            </div>

            <div class="btn-container">
                <a href="../admin.php?<?php echo $return_params; ?>" class="btn btn-secondary">Отмена</a>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Сохранить изменения</button>
            </div>
        </form>
    </div>
</body>
</html>