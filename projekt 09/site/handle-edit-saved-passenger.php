<?php
// handle-edit-saved-passenger.php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Проверка, залогинен ли пользователь
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require 'db.php';
$user_id = $_SESSION['user_id'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Сохраняем данные для автозаполнения при ошибке на странице редактирования
    $_SESSION['form_data_esp'] = $_POST; 
    
    if (!isset($_POST['saved_passenger_id']) || !filter_var($_POST['saved_passenger_id'], FILTER_VALIDATE_INT)) {
        $_SESSION['profile_message'] = "Ошибка: неверный ID пассажира для обновления.";
        $_SESSION['profile_message_type'] = "error";
        header("Location: profile.php");
        exit();
    }
    $saved_passenger_id = (int)$_POST['saved_passenger_id'];

    // Получение данных из формы (аналогично handle-add-saved-passenger.php)
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $middle_name = trim($_POST['middle_name']) ?: null;
    $date_of_birth = !empty(trim($_POST['date_of_birth'])) ? trim($_POST['date_of_birth']) : null;
    $gender = !empty(trim($_POST['gender'])) ? trim($_POST['gender']) : null;
    $passenger_type = !empty(trim($_POST['passenger_type'])) ? trim($_POST['passenger_type']) : null;
    $nationality_country_code = !empty(trim($_POST['nationality_country_code'])) ? trim($_POST['nationality_country_code']) : null;
    
    $document_type = !empty(trim($_POST['document_type'])) ? trim($_POST['document_type']) : null;
    $document_number = !empty(trim($_POST['document_number'])) ? trim($_POST['document_number']) : null;
    $document_issuing_country_code = !empty(trim($_POST['document_issuing_country_code'])) ? trim($_POST['document_issuing_country_code']) : null;
    $document_expiry_date = !empty(trim($_POST['document_expiry_date'])) ? trim($_POST['document_expiry_date']) : null;

    // --- Валидация (такая же, как при добавлении) ---
    if (empty($first_name) || empty($last_name)) {
        $_SESSION['edit_passenger_error'] = "Имя и Фамилия обязательны для заполнения.";
        // Редирект на страницу редактирования с ID
        header("Location: edit-saved-passenger.php?id=" . $saved_passenger_id); 
        exit();
    }
    // ... (Добавьте сюда ВСЕ валидации из handle-add-saved-passenger.php,
    //      но при ошибке делайте редирект на edit-saved-passenger.php?id=... )
    // ... например:
    if ($date_of_birth) {
        $dob_dt = DateTime::createFromFormat('Y-m-d', $date_of_birth);
        if (!$dob_dt || $dob_dt->format('Y-m-d') !== $date_of_birth || $dob_dt > new DateTime()) {
            $_SESSION['edit_passenger_error'] = "Некорректная дата рождения.";
            header("Location: edit-saved-passenger.php?id=" . $saved_passenger_id);
            exit();
        }
    }


    // --- Проверка, что пользователь редактирует своего пассажира ---
    // Это уже было сделано при загрузке edit-saved-passenger.php, но двойная проверка не помешает
    $stmt_check_owner = $conn->prepare("SELECT user_id FROM user_saved_passengers WHERE saved_passenger_id = ?");
    if (!$stmt_check_owner) { /* ... обработка ошибки ... */ $_SESSION['edit_passenger_error']="DB Error CO1"; header("Location: edit-saved-passenger.php?id=" . $saved_passenger_id); exit(); }
    $stmt_check_owner->bind_param("i", $saved_passenger_id);
    $stmt_check_owner->execute();
    $result_check_owner = $stmt_check_owner->get_result();
    $owner_data = $result_check_owner->fetch_assoc();
    $stmt_check_owner->close();

    if (!$owner_data || $owner_data['user_id'] != $user_id) {
        $_SESSION['profile_message'] = "Ошибка: вы не можете редактировать этого пассажира.";
        $_SESSION['profile_message_type'] = "error";
        header("Location: profile.php");
        exit();
    }

    // --- Обновление данных в БД ---
    $sql = "UPDATE user_saved_passengers SET 
                first_name = ?, last_name = ?, middle_name = ?, 
                date_of_birth = ?, gender = ?, passenger_type = ?, nationality_country_code = ?, 
                document_type = ?, document_number = ?, document_issuing_country_code = ?, document_expiry_date = ?,
                updated_at = CURRENT_TIMESTAMP
            WHERE saved_passenger_id = ? AND user_id = ?"; // И user_id для доп. безопасности
    
    $stmt_update = $conn->prepare($sql);
    if (!$stmt_update) {
        error_log("Ошибка подготовки (update_saved_passenger): " . $conn->error);
        $_SESSION['edit_passenger_error'] = "Ошибка сервера при подготовке запроса. (DBP_ESP2)";
        header("Location: edit-saved-passenger.php?id=" . $saved_passenger_id);
        exit();
    }
    
    $stmt_update->bind_param(
        "sssssssssssii", // 11 строк + 2 integer в конце
        $first_name, $last_name, $middle_name, 
        $date_of_birth, $gender, $passenger_type, $nationality_country_code, 
        $document_type, $document_number, $document_issuing_country_code, $document_expiry_date,
        $saved_passenger_id, $user_id
    );

    if ($stmt_update->execute()) {
        unset($_SESSION['form_data_esp']);
        $_SESSION['profile_message'] = "Данные пассажира '".htmlspecialchars($first_name)." ".htmlspecialchars($last_name)."' успешно обновлены!";
        $_SESSION['profile_message_type'] = "success";
        header("Location: profile.php");
        exit();
    } else {
        error_log("Ошибка выполнения (update_saved_passenger): " . $stmt_update->error);
        $_SESSION['edit_passenger_error'] = "Произошла ошибка при обновлении данных пассажира. Попробуйте еще раз. (DBE_ESP1)";
        header("Location: edit-saved-passenger.php?id=" . $saved_passenger_id);
        exit();
    }
    $stmt_update->close();

} else {
    header("Location: profile.php");
    exit();
}
?>