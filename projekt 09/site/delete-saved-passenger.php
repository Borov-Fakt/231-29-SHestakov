<?php
// delete-saved-passenger.php
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

if (isset($_GET['id']) && filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
    $saved_passenger_id = (int)$_GET['id'];

    // Проверка, принадлежит ли этот пассажир текущему пользователю (очень важно для безопасности!)
    $stmt_check = $conn->prepare("SELECT user_id FROM user_saved_passengers WHERE saved_passenger_id = ?");
    if (!$stmt_check) {
        error_log("Ошибка подготовки (check_passenger_owner_delete): " . $conn->error);
        $_SESSION['profile_message'] = "Ошибка сервера при проверке данных. (DBP_DP1)";
        $_SESSION['profile_message_type'] = "error";
        header("Location: profile.php");
        exit();
    }
    $stmt_check->bind_param("i", $saved_passenger_id);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();
    $passenger_owner = $result_check->fetch_assoc();
    $stmt_check->close();

    if ($passenger_owner && $passenger_owner['user_id'] == $user_id) {
        // Пассажир принадлежит пользователю, можно удалять
        $stmt_delete = $conn->prepare("DELETE FROM user_saved_passengers WHERE saved_passenger_id = ? AND user_id = ?");
        if (!$stmt_delete) {
            error_log("Ошибка подготовки (delete_passenger): " . $conn->error);
            $_SESSION['profile_message'] = "Ошибка сервера при удалении пассажира. (DBP_DP2)";
            $_SESSION['profile_message_type'] = "error";
        } else {
            $stmt_delete->bind_param("ii", $saved_passenger_id, $user_id); // Дополнительно user_id для двойной проверки
            if ($stmt_delete->execute()) {
                if ($stmt_delete->affected_rows > 0) {
                    $_SESSION['profile_message'] = "Пассажир успешно удален.";
                    $_SESSION['profile_message_type'] = "success";
                } else {
                    $_SESSION['profile_message'] = "Не удалось удалить пассажира (возможно, он уже был удален).";
                    $_SESSION['profile_message_type'] = "error"; // Или warning
                }
            } else {
                error_log("Ошибка выполнения (delete_passenger): " . $stmt_delete->error);
                $_SESSION['profile_message'] = "Произошла ошибка при удалении пассажира. (DBE_DP1)";
                $_SESSION['profile_message_type'] = "error";
            }
            $stmt_delete->close();
        }
    } else {
        // Попытка удалить чужого пассажира или не существующего
        $_SESSION['profile_message'] = "Ошибка: пассажир не найден или у вас нет прав на его удаление.";
        $_SESSION['profile_message_type'] = "error";
    }
} else {
    // Некорректный ID
    $_SESSION['profile_message'] = "Некорректный идентификатор пассажира для удаления.";
    $_SESSION['profile_message_type'] = "error";
}

header("Location: profile.php");
exit();
?>