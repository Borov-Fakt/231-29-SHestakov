<?php
// admin_actions/handle_edit_user.php
if (session_status() == PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    http_response_code(403); exit("Доступ запрещен.");
}
require '../db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_id_to_edit = (int)$_POST['user_id'];

    // Параметры для редиректа
    $return_params = http_build_query([
        'tab' => 'users-content',
        'page' => $_POST['return_page'] ?? 1,
        'search' => $_POST['return_search'] ?? '',
        'filter_active' => $_POST['return_filter_active'] ?? 'all'
    ]);

    // Запрет редактирования себя через этот обработчик (только через профиль админа)
    if ($user_id_to_edit == $_SESSION['user_id']) {
        $_SESSION['user_management_message'] = "Нельзя редактировать свой профиль через эту форму.";
        $_SESSION['user_management_message_type'] = "error";
        header("Location: ../admin.php?" . $return_params);
        exit();
    }
    
    $_SESSION['edit_user_form_data'] = $_POST; // Для автозаполнения в случае ошибки

    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']) ?: null;
    $email = trim($_POST['email']);
    $phone_number = trim($_POST['phone_number']) ?: null;
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    $is_admin = isset($_POST['is_admin']) ? 1 : 0;
    $new_password = $_POST['new_password'];
    $confirm_new_password = $_POST['confirm_new_password'];

    // Валидация
    if (empty($first_name) || empty($email)) {
        $_SESSION['edit_user_error'] = "Имя и Email обязательны для заполнения.";
        header("Location: edit_user_form.php?user_id=" . $user_id_to_edit . "&" . str_replace("tab=users-content&", "", $return_params)); exit();
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['edit_user_error'] = "Некорректный формат email.";
        header("Location: edit_user_form.php?user_id=" . $user_id_to_edit . "&" . str_replace("tab=users-content&", "", $return_params)); exit();
    }

    // Проверка уникальности email (если изменился)
    $stmt_check_email = $conn->prepare("SELECT user_id FROM users WHERE email = ? AND user_id != ?");
    if (!$stmt_check_email) { /* ... */ $_SESSION['edit_user_error'] = "DB Error CE."; header("Location: edit_user_form.php?user_id=" . $user_id_to_edit . "&" . str_replace("tab=users-content&", "", $return_params)); exit(); }
    $stmt_check_email->bind_param("si", $email, $user_id_to_edit);
    $stmt_check_email->execute();
    if ($stmt_check_email->get_result()->num_rows > 0) {
        $_SESSION['edit_user_error'] = "Пользователь с таким email уже существует.";
        $stmt_check_email->close();
        header("Location: edit_user_form.php?user_id=" . $user_id_to_edit . "&" . str_replace("tab=users-content&", "", $return_params)); exit();
    }
    $stmt_check_email->close();

    $password_sql_part = "";
    $params_update = [$first_name, $last_name, $email, $phone_number, $is_active, $is_admin];
    $types_update = "ssssii";

    if (!empty($new_password)) {
        if ($new_password !== $confirm_new_password) {
            $_SESSION['edit_user_error'] = "Новые пароли не совпадают.";
            header("Location: edit_user_form.php?user_id=" . $user_id_to_edit . "&" . str_replace("tab=users-content&", "", $return_params)); exit();
        }
        if (!preg_match('/^(?=.*[A-Z])(?=.*[a-z])(?=.*\d)(?=.*[!@#$%^&*()_+\-=\[\]{};\':"\\|,.<>\/?~`])[A-Za-z\d!@#$%^&*()_+\-=\[\]{};\':"\\|,.<>\/?~`]{8,50}$/', $new_password)) {
            $_SESSION['edit_user_error'] = "Новый пароль не соответствует требованиям безопасности.";
            header("Location: edit_user_form.php?user_id=" . $user_id_to_edit . "&" . str_replace("tab=users-content&", "", $return_params)); exit();
        }
        $password_hash = password_hash($new_password, PASSWORD_BCRYPT);
        $password_sql_part = ", password_hash = ?";
        $params_update[] = $password_hash;
        $types_update .= "s";
    }
    
    $params_update[] = $user_id_to_edit;
    $types_update .= "i";

    $sql_update = "UPDATE users SET first_name = ?, last_name = ?, email = ?, phone_number = ?, is_active = ?, is_admin = ? {$password_sql_part} WHERE user_id = ?";
    $stmt_update = $conn->prepare($sql_update);
    if (!$stmt_update) { /* ... */ $_SESSION['edit_user_error'] = "DB Error U." . $conn->error; header("Location: edit_user_form.php?user_id=" . $user_id_to_edit . "&" . str_replace("tab=users-content&", "", $return_params)); exit(); }
    
    $stmt_update->bind_param($types_update, ...$params_update);

    if ($stmt_update->execute()) {
        unset($_SESSION['edit_user_form_data']);
        $_SESSION['user_management_message'] = "Данные пользователя ID " . $user_id_to_edit . " успешно обновлены.";
        $_SESSION['user_management_message_type'] = "success";
    } else {
        error_log("Ошибка обновления пользователя ID " . $user_id_to_edit . ": " . $stmt_update->error);
        $_SESSION['user_management_message'] = "Ошибка при обновлении данных пользователя. " . $stmt_update->error;
        $_SESSION['user_management_message_type'] = "error";
    }
    $stmt_update->close();
    header("Location: ../admin.php?" . $return_params); 
    exit();

} else {
    header("Location: ../admin.php?tab=users-content"); 
    exit();
}
?>