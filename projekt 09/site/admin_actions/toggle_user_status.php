<?php
// admin_actions/toggle_user_status.php
if (session_status() == PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    http_response_code(403); exit("Доступ запрещен.");
}
require '../db.php';

$user_id_to_toggle = null;
if (isset($_GET['user_id']) && is_numeric($_GET['user_id'])) {
    $user_id_to_toggle = (int)$_GET['user_id'];
} else {
    $_SESSION['user_management_message'] = "Не указан ID пользователя.";
    $_SESSION['user_management_message_type'] = "error";
    header("Location: ../admin.php?tab=users-content" . '&' . http_build_query(array_intersect_key($_GET, array_flip(['page', 'search', 'filter_active']))));
    exit();
}

// Параметры для редиректа
$return_params_array = array_intersect_key($_GET, array_flip(['page', 'search', 'filter_active']));
$return_params_array['tab'] = 'users-content';
$return_params = http_build_query($return_params_array);


// Запрет деактивации себя
if ($user_id_to_toggle == $_SESSION['user_id']) {
    $_SESSION['user_management_message'] = "Вы не можете изменить свой собственный статус активности.";
    $_SESSION['user_management_message_type'] = "error";
    header("Location: ../admin.php?" . $return_params);
    exit();
}

$current_status = isset($_GET['current_status']) ? (int)$_GET['current_status'] : null;
if ($current_status === null || !in_array($current_status, [0,1])) {
     $_SESSION['user_management_message'] = "Неверный текущий статус.";
    $_SESSION['user_management_message_type'] = "error";
    header("Location: ../admin.php?" . $return_params);
    exit();
}
$new_status = $current_status == 1 ? 0 : 1;

$stmt = $conn->prepare("UPDATE users SET is_active = ? WHERE user_id = ?");
if(!$stmt){ /* ... */ $_SESSION['user_management_message'] = "DB Error TUS."; $_SESSION['user_management_message_type'] = "error"; header("Location: ../admin.php?" . $return_params); exit(); }
$stmt->bind_param("ii", $new_status, $user_id_to_toggle);

if ($stmt->execute()) {
    $action_text = $new_status == 1 ? "активирован" : "деактивирован";
    $_SESSION['user_management_message'] = "Пользователь ID " . $user_id_to_toggle . " успешно " . $action_text . ".";
    $_SESSION['user_management_message_type'] = "success";
} else {
    error_log("Ошибка изменения статуса пользователя ID " . $user_id_to_toggle . ": " . $stmt->error);
    $_SESSION['user_management_message'] = "Ошибка при изменении статуса пользователя.";
    $_SESSION['user_management_message_type'] = "error";
}
$stmt->close();
header("Location: ../admin.php?" . $return_params);
exit();
?>