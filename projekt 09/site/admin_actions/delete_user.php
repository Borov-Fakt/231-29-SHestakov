<?php
// admin_actions/delete_user.php
if (session_status() == PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    http_response_code(403); exit("Доступ запрещен.");
}
require '../db.php';

$user_id_to_delete = null;
if (isset($_GET['user_id']) && is_numeric($_GET['user_id'])) {
    $user_id_to_delete = (int)$_GET['user_id'];
} else {
    $_SESSION['user_management_message'] = "Не указан ID пользователя для удаления.";
    $_SESSION['user_management_message_type'] = "error";
    header("Location: ../admin.php?tab=users-content" . '&' . http_build_query(array_intersect_key($_GET, array_flip(['page', 'search', 'filter_active']))));
    exit();
}

$return_params_array = array_intersect_key($_GET, array_flip(['page', 'search', 'filter_active']));
$return_params_array['tab'] = 'users-content';
$return_params = http_build_query($return_params_array);

// Запрет удаления себя
if ($user_id_to_delete == $_SESSION['user_id']) {
    $_SESSION['user_management_message'] = "Вы не можете удалить свой собственный аккаунт.";
    $_SESSION['user_management_message_type'] = "error";
    header("Location: ../admin.php?" . $return_params);
    exit();
}

// Запрет удаления других администраторов (простая проверка, можно усложнить)
$stmt_check_admin = $conn->prepare("SELECT is_admin FROM users WHERE user_id = ?");
if(!$stmt_check_admin){ /* ... */ $_SESSION['user_management_message'] = "DB Error CDA."; $_SESSION['user_management_message_type'] = "error"; header("Location: ../admin.php?" . $return_params); exit(); }
$stmt_check_admin->bind_param("i", $user_id_to_delete);
$stmt_check_admin->execute();
$result_check_admin = $stmt_check_admin->get_result();
if($user_data_to_delete = $result_check_admin->fetch_assoc()){
    if($user_data_to_delete['is_admin']){
         $_SESSION['user_management_message'] = "Вы не можете удалить другого администратора через эту функцию.";
         $_SESSION['user_management_message_type'] = "error";
         $stmt_check_admin->close();
         header("Location: ../admin.php?" . $return_params);
         exit();
    }
}
$stmt_check_admin->close();


// Перед удалением, рассмотрите, что делать со связанными данными
// Например, бронирования (ON DELETE SET NULL для user_id в bookings или архивация)

$stmt_delete = $conn->prepare("DELETE FROM users WHERE user_id = ?");
if(!$stmt_delete){ /* ... */ $_SESSION['user_management_message'] = "DB Error DU."; $_SESSION['user_management_message_type'] = "error"; header("Location: ../admin.php?" . $return_params); exit(); }
$stmt_delete->bind_param("i", $user_id_to_delete);

if ($stmt_delete->execute()) {
    if($stmt_delete->affected_rows > 0){
        $_SESSION['user_management_message'] = "Пользователь ID " . $user_id_to_delete . " успешно удален.";
        $_SESSION['user_management_message_type'] = "success";
    } else {
        $_SESSION['user_management_message'] = "Пользователь ID " . $user_id_to_delete . " не найден или уже был удален.";
        $_SESSION['user_management_message_type'] = "info";
    }
} else {
    error_log("Ошибка удаления пользователя ID " . $user_id_to_delete . ": " . $stmt_delete->error);
    // Если есть FOREIGN KEY, которые не позволяют удалить, будет ошибка
    $_SESSION['user_management_message'] = "Ошибка при удалении пользователя. Возможно, есть связанные данные.";
    $_SESSION['user_management_message_type'] = "error";
}
$stmt_delete->close();
header("Location: ../admin.php?" . $return_params);
exit();
?>