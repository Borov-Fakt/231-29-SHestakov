<?php
// admin_actions/handle_save_offer.php
if (session_status() == PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    http_response_code(403); exit("Доступ запрещен.");
}
require '../db.php';

// --- Конфигурация загрузки файлов ---
define('UPLOAD_DIR', '../uploads/special_offers/'); // Путь от текущего скрипта к папке загрузок
define('ALLOWED_MIMES', ['image/jpeg', 'image/png', 'image/gif', 'image/webp']);
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5 MB

// Убедимся, что директория для загрузок существует и доступна для записи
if (!is_dir(UPLOAD_DIR)) {
    if (!mkdir(UPLOAD_DIR, 0775, true)) { // 0775 чтобы веб-сервер мог писать, а другие читать/выполнять
        error_log("Failed to create upload directory: " . UPLOAD_DIR);
        $_SESSION['offer_form_error'] = "Критическая ошибка: не удалось создать директорию для загрузки изображений.";
        $offer_id = isset($_POST['offer_id']) && !empty($_POST['offer_id']) ? (int)$_POST['offer_id'] : null;
        $form_redirect_url_base = $offer_id ? "add_offer_form.php?offer_id=" . $offer_id : "add_offer_form.php";
        header("Location: " . $form_redirect_url_base); exit();
    }
}
if (!is_writable(UPLOAD_DIR)) {
     error_log("Upload directory is not writable: " . UPLOAD_DIR);
    $_SESSION['offer_form_error'] = "Критическая ошибка: директория для загрузки изображений недоступна для записи.";
    $offer_id = isset($_POST['offer_id']) && !empty($_POST['offer_id']) ? (int)$_POST['offer_id'] : null;
    $form_redirect_url_base = $offer_id ? "add_offer_form.php?offer_id=" . $offer_id : "add_offer_form.php";
    header("Location: " . $form_redirect_url_base); exit();
}


if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $_SESSION['offer_form_data'] = $_POST; 
    $offer_id = isset($_POST['offer_id']) && !empty($_POST['offer_id']) ? (int)$_POST['offer_id'] : null;
    $existing_image_path_db = isset($_POST['existing_image_path']) ? $_POST['existing_image_path'] : null; // Путь из БД (uploads/special_offers/...)

    $return_params_array = ['tab' => 'special-offers-content', /* ... остальные параметры для возврата ... */];
    $form_return_params_array = []; // Параметры для возврата на форму в случае ошибки

    if (isset($_POST['return_spage'])) $return_params_array['spage'] = $_POST['return_spage'];
    if (isset($_POST['return_ssearch'])) $return_params_array['ssearch'] = $_POST['return_ssearch'];
    if (isset($_POST['return_sfilter_active'])) $return_params_array['sfilter_active'] = $_POST['return_sfilter_active'];

    if (isset($_POST['return_spage'])) $form_return_params_array['spage'] = $_POST['return_spage'];
    if (isset($_POST['return_ssearch'])) $form_return_params_array['ssearch'] = $_POST['return_ssearch'];
    if (isset($_POST['return_sfilter_active'])) $form_return_params_array['sfilter_active'] = $_POST['return_sfilter_active'];
    
    $return_url_list = "../admin.php?" . http_build_query($return_params_array);
    $form_redirect_url_base = $offer_id ? "add_offer_form.php?offer_id=" . $offer_id : "add_offer_form.php";
    $form_redirect_url = $form_redirect_url_base . (empty($form_return_params_array) ? '' : '&' . http_build_query($form_return_params_array));


    // --- Получение и очистка текстовых данных (как ранее) ---
    $title = trim($_POST['title']);
    // ... (весь блок получения и очистки других POST-данных как в предыдущем handle_save_offer.php)
    $subtitle = isset($_POST['subtitle']) ? trim($_POST['subtitle']) : null;
    $subtitle = $subtitle === '' ? null : $subtitle;
    $price_from_input = $_POST['price_from'];
    $price_from = null;
    if ($price_from_input !== '') {
        $price_from = filter_var($price_from_input, FILTER_VALIDATE_FLOAT);
        if ($price_from === false) {
             $_SESSION['offer_form_error'] = "Некорректное значение для 'Цена от'.";
             header("Location: " . $form_redirect_url); exit();
        }
    }
    $currency_code = isset($_POST['currency_code']) ? strtoupper(trim(substr($_POST['currency_code'], 0, 3))) : 'RUB';
    // $image_placeholder_color больше не нужен

    $details_page_title = trim($_POST['details_page_title']);
    $details_hero_subtitle = isset($_POST['details_hero_subtitle']) ? trim($_POST['details_hero_subtitle']) : null;
    $details_hero_subtitle = $details_hero_subtitle === '' ? null : $details_hero_subtitle;
    $details_main_description = $_POST['details_main_description']; 
    $details_what_to_see = isset($_POST['details_what_to_see']) ? $_POST['details_what_to_see'] : null;
    $details_what_to_see = (is_string($details_what_to_see) && trim($details_what_to_see) === '') ? null : $details_what_to_see;
    $details_direction = isset($_POST['details_direction']) ? trim($_POST['details_direction']) : null;
    $details_direction = $details_direction === '' ? null : $details_direction;
    $details_price_info = isset($_POST['details_price_info']) ? trim($_POST['details_price_info']) : null;
    $details_price_info = $details_price_info === '' ? null : $details_price_info;
    $details_departure_from = isset($_POST['details_departure_from']) ? trim($_POST['details_departure_from']) : null;
    $details_departure_from = $details_departure_from === '' ? null : $details_departure_from;
    $details_travel_period = isset($_POST['details_travel_period']) ? trim($_POST['details_travel_period']) : null;
    $details_travel_period = $details_travel_period === '' ? null : $details_travel_period;
    $details_flight_class = isset($_POST['details_flight_class']) ? trim($_POST['details_flight_class']) : null;
    $details_flight_class = $details_flight_class === '' ? null : $details_flight_class;
    $search_destination_iata = strtoupper(trim($_POST['search_destination_iata']));
    $search_origin_iata = isset($_POST['search_origin_iata']) ? strtoupper(trim($_POST['search_origin_iata'])) : null;
    $search_origin_iata = $search_origin_iata === '' ? null : $search_origin_iata;
    $search_trip_type = in_array($_POST['search_trip_type'], ['round-trip', 'one-way']) ? $_POST['search_trip_type'] : 'round-trip';
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    $sort_order_input = $_POST['sort_order'];
    $sort_order = filter_var($sort_order_input, FILTER_VALIDATE_INT);
     if ($sort_order === false) {
        $sort_order = 100;
    }
    // ... конец блока получения текстовых данных ...

    // --- Валидация обязательных текстовых полей (как ранее) ---
    if (empty($title) || $price_from === null || empty($currency_code) || empty($details_page_title) || empty($details_main_description) || empty($search_destination_iata)) {
        $_SESSION['offer_form_error'] = "Пожалуйста, заполните все обязательные поля (*).";
        header("Location: " . $form_redirect_url); exit();
    }
    // ... (остальная валидация текстовых полей как ранее) ...
    if (strlen($currency_code) !== 3 || !preg_match('/^[A-Z]{3}$/', $currency_code) ) { // Обновил паттерн для валюты
        $_SESSION['offer_form_error'] = "Код валюты должен состоять из 3 заглавных латинских букв (например, RUB).";
        header("Location: " . $form_redirect_url); exit();
    }
    if (strlen($search_destination_iata) > 50 || ($search_origin_iata !== null && strlen($search_origin_iata) > 50) ) {
        $_SESSION['offer_form_error'] = "Коды IATA не должны превышать 50 символов.";
        header("Location: " . $form_redirect_url); exit();
    }


    // --- Обработка загруженного изображения ---
    $new_image_path_db = $existing_image_path_db; // По умолчанию оставляем старый путь
    $delete_existing_image_flag = isset($_POST['delete_existing_image']) && $_POST['delete_existing_image'] == '1';

    if (isset($_FILES['offer_image']) && $_FILES['offer_image']['error'] == UPLOAD_ERR_OK) {
        $file_tmp_path = $_FILES['offer_image']['tmp_name'];
        $file_name_original = $_FILES['offer_image']['name'];
        $file_size = $_FILES['offer_image']['size'];
        $file_mime_type = mime_content_type($file_tmp_path); // Более надежно, чем $_FILES['offer_image']['type']
        $file_extension = strtolower(pathinfo($file_name_original, PATHINFO_EXTENSION));

        if (!in_array($file_mime_type, ALLOWED_MIMES)) {
            $_SESSION['offer_form_error'] = "Недопустимый тип файла. Разрешены только JPG, PNG, GIF, WEBP.";
            header("Location: " . $form_redirect_url); exit();
        }
        if ($file_size > MAX_FILE_SIZE) {
            $_SESSION['offer_form_error'] = "Файл слишком большой. Максимальный размер " . (MAX_FILE_SIZE / 1024 / 1024) . "MB.";
            header("Location: " . $form_redirect_url); exit();
        }

        // Генерируем уникальное имя файла
        $new_file_name = uniqid('offer_', true) . '.' . $file_extension;
        $destination_path_server = UPLOAD_DIR . $new_file_name; // Полный путь на сервере
        
        // Перемещаем файл
        if (move_uploaded_file($file_tmp_path, $destination_path_server)) {
            // УДАЛЯЕМ СТАРЫЙ ФАЙЛ (если он был и если новый успешно загружен)
            if ($existing_image_path_db && file_exists(UPLOAD_DIR . basename($existing_image_path_db))) {
                unlink(UPLOAD_DIR . basename($existing_image_path_db));
            }
            $new_image_path_db = 'uploads/special_offers/' . $new_file_name; // Путь для сохранения в БД (относительно корня сайта)
            $delete_existing_image_flag = false; // Уже удалили старый, если был, при загрузке нового
        } else {
            $_SESSION['offer_form_error'] = "Ошибка при загрузке файла изображения на сервер.";
            header("Location: " . $form_redirect_url); exit();
        }
    } elseif (isset($_FILES['offer_image']) && $_FILES['offer_image']['error'] != UPLOAD_ERR_NO_FILE) {
        // Если была ошибка загрузки, но это не "файл не выбран"
        $_SESSION['offer_form_error'] = "Ошибка загрузки файла (код: " . $_FILES['offer_image']['error'] . ").";
        header("Location: " . $form_redirect_url); exit();
    }

    // Если был отмечен чекбокс "Удалить текущее изображение" и НОВЫЙ ФАЙЛ НЕ ЗАГРУЖАЛСЯ
    if ($delete_existing_image_flag && $existing_image_path_db && (!isset($_FILES['offer_image']) || $_FILES['offer_image']['error'] == UPLOAD_ERR_NO_FILE)) {
        if (file_exists(UPLOAD_DIR . basename($existing_image_path_db))) {
            unlink(UPLOAD_DIR . basename($existing_image_path_db));
        }
        $new_image_path_db = null; // Очищаем путь в БД
    }


    // --- Подготовка SQL запроса (INSERT или UPDATE) ---
    // Убрано image_placeholder_color, добавлено image_path
    if ($offer_id) { 
        $sql = "UPDATE special_offers SET 
                    title=?, subtitle=?, price_from=?, currency_code=?, image_path=?, 
                    details_page_title=?, details_hero_subtitle=?, details_main_description=?, details_what_to_see=?, 
                    details_direction=?, details_price_info=?, details_departure_from=?, details_travel_period=?, details_flight_class=?, 
                    search_destination_iata=?, search_origin_iata=?, search_trip_type=?, 
                    is_active=?, sort_order=? 
                WHERE offer_id=?";
        $stmt = $conn->prepare($sql);
        if (!$stmt) { /* ... */ $_SESSION['offer_form_error'] = "DB Error U1 " . $conn->error; header("Location: " . $form_redirect_url); exit(); }
        $stmt->bind_param("ssdssssssssssssssiii", 
            $title, $subtitle, $price_from, $currency_code, $new_image_path_db, // Изменено поле
            $details_page_title, $details_hero_subtitle, $details_main_description, $details_what_to_see,
            $details_direction, $details_price_info, $details_departure_from, $details_travel_period, $details_flight_class,
            $search_destination_iata, $search_origin_iata, $search_trip_type,
            $is_active, $sort_order,
            $offer_id
        );
        $action_message_verb = "обновлено";
    } else { 
        $sql = "INSERT INTO special_offers (
                    title, subtitle, price_from, currency_code, image_path, 
                    details_page_title, details_hero_subtitle, details_main_description, details_what_to_see, 
                    details_direction, details_price_info, details_departure_from, details_travel_period, details_flight_class, 
                    search_destination_iata, search_origin_iata, search_trip_type, 
                    is_active, sort_order
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        if (!$stmt) { /* ... */ $_SESSION['offer_form_error'] = "DB Error I1 " . $conn->error; header("Location: " . $form_redirect_url); exit(); }
        $stmt->bind_param("ssdssssssssssssssii",
            $title, $subtitle, $price_from, $currency_code, $new_image_path_db, // Изменено поле
            $details_page_title, $details_hero_subtitle, $details_main_description, $details_what_to_see,
            $details_direction, $details_price_info, $details_departure_from, $details_travel_period, $details_flight_class,
            $search_destination_iata, $search_origin_iata, $search_trip_type,
            $is_active, $sort_order
        );
        $action_message_verb = "добавлено";
    }

    // --- Выполнение запроса ---
    if ($stmt->execute()) {
        unset($_SESSION['offer_form_data']);
        $last_id = $offer_id ? $offer_id : $conn->insert_id;
        $_SESSION['offers_message'] = "Спецпредложение '" . htmlspecialchars($title) . "' (ID: ".$last_id.") успешно " . $action_message_verb . ".";
        $_SESSION['offers_message_type'] = "success";
        header("Location: " . $return_url_list);
    } else {
        error_log("Error saving/updating offer ID " . ($offer_id ?? 'NEW') . ": " . $stmt->error);
        // Если была ошибка SQL, а файл был загружен, его нужно удалить
        if (isset($destination_path_server) && file_exists($destination_path_server) && $new_image_path_db !== $existing_image_path_db) {
            unlink($destination_path_server);
        }
        $_SESSION['offer_form_error'] = "Ошибка при сохранении спецпредложения в базе данных: " . $stmt->error;
        header("Location: " . $form_redirect_url);
    }
    $stmt->close();
    exit();

} else {
    header("Location: ../admin.php?tab=special-offers-content");
    exit();
}
?>