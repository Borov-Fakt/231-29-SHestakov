<?php
// handle_checkout.php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once 'db.php'; // Подключение к БД

error_log("НАЧАЛО handle_checkout.php. Сессия: " . print_r($_SESSION, true)); 

$_SESSION['checkout_form_data'] = $_POST; 
$checkout_redirect_url = "checkout.php";
$checkout_original_get_params_array = [];
if (isset($_POST['original_get_query_string'])) {
    parse_str($_POST['original_get_query_string'], $checkout_original_get_params_array);
}
if (isset($_POST['selected_flight_id'])) {
    $checkout_original_get_params_array['flight_id'] = $_POST['selected_flight_id'];
}
// Передаем скрытые поля, если они есть
$hidden_origin = $_POST['hidden_origin'] ?? null;
$hidden_destination = $_POST['hidden_destination'] ?? null;
$hidden_dep_date = $_POST['hidden_departure_date'] ?? null;
$hidden_pax = $_POST['hidden_pax'] ?? null;

if ($hidden_origin) $checkout_original_get_params_array['origin'] = $hidden_origin;
if ($hidden_destination) $checkout_original_get_params_array['destination'] = $hidden_destination;
if ($hidden_dep_date) $checkout_original_get_params_array['dep_date'] = $hidden_dep_date;
if ($hidden_pax) $checkout_original_get_params_array['pax'] = $hidden_pax;

$checkout_redirect_query_string = !empty($checkout_original_get_params_array) ? '?' . http_build_query($checkout_original_get_params_array) : '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $selected_flight_id = $_POST['selected_flight_id'] ?? null;
    $num_adults = isset($_POST['num_adults']) ? (int)$_POST['num_adults'] : 0;
    $total_price = isset($_POST['total_price_final']) ? filter_var($_POST['total_price_final'], FILTER_VALIDATE_FLOAT) : 0.0;
    $currency = isset($_POST['currency_final']) ? trim(strtoupper(substr($_POST['currency_final'],0,3))) : 'RUB';
    $passengers_input = $_POST['passengers'] ?? [];
    $contact_email = isset($_POST['contact_email']) ? trim($_POST['contact_email']) : '';
    $contact_phone = isset($_POST['contact_phone']) ? trim($_POST['contact_phone']) : '';

    if (!$selected_flight_id || $num_adults <= 0 || $total_price === false || $total_price <= 0 /* ... и т.д. ... */) {
        $_SESSION['checkout_error'] = "Ошибка: не все ключевые данные заказа были переданы.";
        header("Location: " . $checkout_redirect_url . $checkout_redirect_query_string);
        exit();
    }
    // ... (остальная валидация как у вас) ...
    foreach ($passengers_input as $idx => $pax) {
        if (empty($pax['first_name']) || empty($pax['last_name']) /*...*/) {
            $_SESSION['checkout_error'] = "Пожалуйста, заполните все обязательные поля для Пассажира " . ($idx + 1) . ".";
            header("Location: " . $checkout_redirect_url . $checkout_redirect_query_string);
            exit();
        }
        // ... (остальная валидация пассажиров)
    }


    $origin_for_sim = $hidden_origin ?: 'DEFAULT_ORIGIN'; 
    $destination_for_sim = $hidden_destination ?: 'DEFAULT_DEST';
    $departure_date_for_sim = $hidden_dep_date ?: date('Y-m-d');
    
    error_log("Для симуляции рейса используется: Origin='{$origin_for_sim}', Destination='{$destination_for_sim}', DepDate='{$departure_date_for_sim}'");


    $all_simulated_flights_handler = [];
    $iata_origin_sim = strtoupper(substr($origin_for_sim, 0, 3));
    $iata_destination_sim = strtoupper(substr($destination_for_sim, 0, 3));
    
    // Проверим, есть ли эти IATA в БД. Если нет, используем 'DEF' (предполагая, что 'DEF' добавлено в airports)
    $check_iata = function($iata_code, $conn) {
        $stmt_check = $conn->prepare("SELECT iata_code FROM airports WHERE iata_code = ?");
        $stmt_check->bind_param("s", $iata_code);
        $stmt_check->execute();
        $exists = $stmt_check->get_result()->num_rows > 0;
        $stmt_check->close();
        if (!$exists && $iata_code !== 'HUB') { // HUB специальный
             error_log("ПРЕДУПРЕЖДЕНИЕ FK: IATA аэропорта '{$iata_code}' не найден в БД. Будет использован 'DEF' (если есть).");
             return 'DEF'; // Используем 'DEF', если он существует в таблице airports
        }
        return $iata_code;
    };

    $valid_iata_origin = $check_iata($iata_origin_sim, $conn);
    $valid_iata_destination = $check_iata($iata_destination_sim, $conn);
    $valid_hub_iata = 'HUB'; // Предполагаем, HUB всегда есть или будет добавлен


    $all_simulated_flights_handler['fake_flight_1'] = [
        'id' => 'fake_flight_1', 'airline_name' => 'AirGO Simulated', 'airline_iata_code' => 'AG', 
        'segments' => [['flight_number' => '101', 'departure_iata' => $valid_iata_origin, 'departure_datetime_full' => $departure_date_for_sim . 'T09:30:00', 'arrival_iata' => $valid_iata_destination, 'arrival_datetime_full' => $departure_date_for_sim . 'T12:00:00', 'booking_class' => 'Y', 'aircraft_type' => 'B737', 'duration_minutes' => 150]]];
    $all_simulated_flights_handler['fake_flight_2'] = [
        'id' => 'fake_flight_2', 'airline_name' => 'Connect Airways (Simulated)', 'airline_iata_code' => 'CA', 
        'segments' => [['flight_number' => '202', 'departure_iata' => $valid_iata_origin, 'departure_datetime_full' => $departure_date_for_sim . 'T14:00:00', 'arrival_iata' => $valid_hub_iata, 'arrival_datetime_full' => $departure_date_for_sim . 'T16:00:00', 'booking_class' => 'M', 'aircraft_type' => 'A320', 'duration_minutes' => 120], ['flight_number' => '203', 'departure_iata' => $valid_hub_iata, 'departure_datetime_full' => $departure_date_for_sim . 'T17:30:00', 'arrival_iata' => $valid_iata_destination, 'arrival_datetime_full' => $departure_date_for_sim . 'T19:00:00', 'booking_class' => 'M', 'aircraft_type' => 'A320', 'duration_minutes' => 90]]];


    if (isset($all_simulated_flights_handler[$selected_flight_id])) {
        $selected_flight_data_to_save = $all_simulated_flights_handler[$selected_flight_id];
    } else { $_SESSION['checkout_error'] = "Ошибка данных рейса (не найден в симуляции)."; header("Location: " . $checkout_redirect_url . $checkout_redirect_query_string); exit(); }

    $pnr = ''; $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    for ($i = 0; $i < 6; $i++) { $pnr .= $characters[rand(0, strlen($characters) - 1)]; }
    $booking_status = 'confirmed';
    
    $original_user_id = $_SESSION['user_id'] ?? null;
    // ... (сохранение остальных переменных сессии, если нужно)
    session_regenerate_id(true);
    if ($original_user_id) { $_SESSION['user_id'] = $original_user_id; }
    // ... (восстановление остальных)
    $_SESSION['is_logged_in'] = ($original_user_id != null); // Обновляем флаг, если он используется

    error_log("handle_checkout.php ПОСЛЕ regen - User ID: " . ($_SESSION['user_id'] ?? 'НЕТ В СЕССИИ!'));
    $user_id_for_booking = $_SESSION['user_id'] ?? null;
    error_log("handle_checkout.php ДЛЯ ЗАПИСИ В BOOKINGS - user_id_for_booking: " . ($user_id_for_booking ?? 'NULL'));


    $conn->begin_transaction();
    try {
        $stmt_booking = $conn->prepare("INSERT INTO bookings (user_id, booking_reference, status, total_price, currency_code, contact_email, contact_phone, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())");
        if (!$stmt_booking) throw new Exception("DB Error (BKP): " . $conn->error, 101); // Добавил код ошибки
        $stmt_booking->bind_param("issdsss", $user_id_for_booking, $pnr, $booking_status, $total_price, $currency, $contact_email, $contact_phone);
        if (!$stmt_booking->execute()) throw new Exception("DB Error (BKE): " . $stmt_booking->error, 102);
        $new_booking_id = $conn->insert_id;
        $stmt_booking->close();
        error_log("Успешно вставлено в bookings, ID: " . $new_booking_id);

        foreach ($passengers_input as $pax_data) {
            $stmt_passenger = $conn->prepare("INSERT INTO passengers (booking_id, first_name, last_name, date_of_birth, gender, passenger_type, document_type, document_number, nationality_country_code, document_expiry_date, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())");
            if (!$stmt_passenger) throw new Exception("DB Error (PKP): " . $conn->error, 201);
            $pax_type = $pax_data['passenger_type'] ?? 'adult';
            $pax_doc_exp_date = !empty($pax_data['document_expiry_date']) ? $pax_data['document_expiry_date'] : null;
            $stmt_passenger->bind_param("isssssssss", 
                $new_booking_id, $pax_data['first_name'], $pax_data['last_name'], $pax_data['date_of_birth'],
                $pax_data['gender'], $pax_type, $pax_data['document_type'], $pax_data['document_number'], 
                $pax_data['nationality_country_code'], $pax_doc_exp_date
            );
            if (!$stmt_passenger->execute()) throw new Exception("DB Error (PKE): " . $stmt_passenger->error, 202);
            $stmt_passenger->close();
        }
        error_log("Успешно вставлены все пассажиры для booking_id: " . $new_booking_id);
        
        // --- ОТЛАДКА ПЕРЕД ВСТАВКОЙ В booked_segments ---
        error_log("--- ДАННЫЕ ДЛЯ BOOKED_SEGMENTS booking_id: " . $new_booking_id . " ---");
        error_log("Данные рейса для сегментов: " . print_r($selected_flight_data_to_save, true));
        // --- КОНЕЦ ОТЛАДКИ ---

        foreach ($selected_flight_data_to_save['segments'] as $seq_num => $segment_data) {
            $stmt_segment = $conn->prepare("INSERT INTO booked_segments (booking_id, sequence_number, airline_iata_code, flight_number, departure_airport_iata_code, departure_at_utc, arrival_airport_iata_code, arrival_at_utc, booking_class, aircraft_type, duration_minutes, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
            if (!$stmt_segment) throw new Exception("DB Error (BSP " . ($seq_num + 1) . "): " . $conn->error, 301);
            
            $dep_dt_db = str_replace('T', ' ', $segment_data['departure_datetime_full']);
            $arr_dt_db = str_replace('T', ' ', $segment_data['arrival_datetime_full']);
            $actual_seq_num = $seq_num + 1;
            $airline_iata_for_segment = $selected_flight_data_to_save['airline_iata_code'] ?? 'XX'; // Заглушка, если не определена
            
            // Дополнительный лог ПРЯМО ПЕРЕД bind_param для сегментов
            error_log("Segment ".($seq_num + 1)." INSERT: airline='{$airline_iata_for_segment}', dep_ap='{$segment_data['departure_iata']}', arr_ap='{$segment_data['arrival_iata']}'");

            $stmt_segment->bind_param("iissssssssi", 
                $new_booking_id, $actual_seq_num, $airline_iata_for_segment, $segment_data['flight_number'],
                $segment_data['departure_iata'], $dep_dt_db, $segment_data['arrival_iata'], $arr_dt_db,
                $segment_data['booking_class'], $segment_data['aircraft_type'], $segment_data['duration_minutes']
            );
            if (!$stmt_segment->execute()) throw new Exception("DB Error (BSE " . ($seq_num + 1) . "): " . $stmt_segment->error . " | Details: airline='{$airline_iata_for_segment}', dep_ap='{$segment_data['departure_iata']}', arr_ap='{$segment_data['arrival_iata']}'" , 302);
            $stmt_segment->close();
        }
        error_log("Успешно вставлены все сегменты для booking_id: " . $new_booking_id);

        $conn->commit();
        unset($_SESSION['checkout_form_data']); 
        $_SESSION['booking_success_pnr'] = $pnr;
        $_SESSION['booking_success_email'] = $contact_email; 
        header("Location: booking_success.php");
        exit();

    } catch (Exception $e) {
        $conn->rollback(); 
        error_log("Transaction Error in handle_checkout: " . $e->getMessage() . " | Exception Code: " . $e->getCode() . " | SQL Error: " . $conn->error);
        $_SESSION['checkout_error'] = "Произошла критическая ошибка (" . $e->getCode() .") при создании бронирования. Пожалуйста, попробуйте снова или свяжитесь с поддержкой.";
        header("Location: " . $checkout_redirect_url . $checkout_redirect_query_string);
        exit();
    }
} else {
    header("Location: index.php");
    exit();
}
?>