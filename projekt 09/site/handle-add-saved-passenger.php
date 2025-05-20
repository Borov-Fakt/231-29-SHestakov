<?php
// handle-add-saved-passenger.php
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
    $_SESSION['form_data_sp'] = $_POST; // Сохраняем данные для автозаполнения при ошибке

    // Получение данных из формы
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

    // --- Валидация ---
    if (empty($first_name) || empty($last_name)) {
        $_SESSION['add_passenger_error'] = "Имя и Фамилия обязательны для заполнения.";
        header("Location: add-saved-passenger.php");
        exit();
    }

    // Дополнительные валидации (примеры, можно расширить)
    if ($date_of_birth) {
        $dob_dt = DateTime::createFromFormat('Y-m-d', $date_of_birth);
        if (!$dob_dt || $dob_dt->format('Y-m-d') !== $date_of_birth) {
            $_SESSION['add_passenger_error'] = "Некорректный формат даты рождения.";
            header("Location: add-saved-passenger.php");
            exit();
        }
        if ($dob_dt > new DateTime()) {
            $_SESSION['add_passenger_error'] = "Дата рождения не может быть в будущем.";
            header("Location: add-saved-passenger.php");
            exit();
        }
    }

    if ($document_expiry_date) {
        $doc_exp_dt = DateTime::createFromFormat('Y-m-d', $document_expiry_date);
        if (!$doc_exp_dt || $doc_exp_dt->format('Y-m-d') !== $document_expiry_date) {
            $_SESSION['add_passenger_error'] = "Некорректный формат срока действия документа.";
            header("Location: add-saved-passenger.php");
            exit();
        }
    }
    // Валидация ENUM значений, если они выбраны
    $valid_genders = ['male', 'female', 'other', 'undisclosed'];
    if ($gender !== null && !in_array($gender, $valid_genders)) {
        $_SESSION['add_passenger_error'] = "Некорректное значение для поля 'Пол'.";
        header("Location: add-saved-passenger.php");
        exit();
    }
     // Добавьте аналогичные проверки для passenger_type и document_type


    // --- Вставка данных в БД ---
    $sql = "INSERT INTO user_saved_passengers (
                user_id, first_name, last_name, middle_name, 
                date_of_birth, gender, passenger_type, nationality_country_code, 
                document_type, document_number, document_issuing_country_code, document_expiry_date
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt_insert = $conn->prepare($sql);
    if (!$stmt_insert) {
        error_log("Ошибка подготовки (insert_saved_passenger): " . $conn->error);
        $_SESSION['add_passenger_error'] = "Ошибка сервера при подготовке запроса. (DBP_SP1)";
        header("Location: add-saved-passenger.php");
        exit();
    }
    
    // "isssssssssss" - типы параметров: i(integer), s(string), d(double), b(blob)
    $stmt_insert->bind_param(
        "isssssssssss", 
        $user_id, $first_name, $last_name, $middle_name, 
        $date_of_birth, $gender, $passenger_type, $nationality_country_code, 
        $document_type, $document_number, $document_issuing_country_code, $document_expiry_date
    );

    if ($stmt_insert->execute()) {
        unset($_SESSION['form_data_sp']);
        $_SESSION['profile_message'] = "Пассажир '".htmlspecialchars($first_name)." ".htmlspecialchars($last_name)."' успешно добавлен!";
        $_SESSION['profile_message_type'] = "success";
        header("Location: profile.php");
        exit();
    } else {
        error_log("Ошибка выполнения (insert_saved_passenger): " . $stmt_insert->error);
        $_SESSION['add_passenger_error'] = "Произошла ошибка при добавлении пассажира. Попробуйте еще раз. (DBE_SP1)";
        header("Location: add-saved-passenger.php");
        exit();
    }
    $stmt_insert->close();

} else {
    header("Location: add-saved-passenger.php");
    exit();
}
?>