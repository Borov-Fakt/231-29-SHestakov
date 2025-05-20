<?php
// admin_actions/view_booking_details.php
if (session_status() == PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    http_response_code(403); 
    // Если это прямой вызов без параметров (маловероятно, но для защиты)
    $_SESSION['bookings_message'] = "Доступ запрещен или неверные параметры для просмотра бронирования.";
    $_SESSION['bookings_message_type'] = "error";
    header("Location: ../admin.php?tab=bookings-content");
    exit(); 
}
require '../db.php'; // Путь к db.php

$booking_id_to_view = null;
$booking_data = null;
$passengers_data = [];
$segments_data = [];
$payment_data = null; 

// Параметры для кнопки "Назад"
$return_params = [];
if (isset($_GET['bpage'])) $return_params['bpage'] = $_GET['bpage'];
if (isset($_GET['bsearch'])) $return_params['bsearch'] = $_GET['bsearch'];
if (isset($_GET['bstatus'])) $return_params['bstatus'] = $_GET['bstatus'];
$return_params['tab'] = 'bookings-content'; // Всегда возвращаемся на вкладку бронирований
$return_url = "../admin.php?" . http_build_query($return_params);


if (isset($_GET['booking_id']) && is_numeric($_GET['booking_id'])) {
    $booking_id_to_view = (int)$_GET['booking_id'];

    // 1. Получаем основную информацию о бронировании и пользователе (если есть)
    $stmt_booking = $conn->prepare(
        "SELECT b.*, u.first_name as user_first_name, u.last_name as user_last_name, u.email as user_email
         FROM bookings b
         LEFT JOIN users u ON b.user_id = u.user_id
         WHERE b.booking_id = ?"
    );
    if (!$stmt_booking) { 
        error_log("DB Error (prepare booking details): " . $conn->error); 
        $_SESSION['bookings_message']="Ошибка сервера при загрузке деталей бронирования (BD1)."; 
        $_SESSION['bookings_message_type'] = "error"; 
        header("Location: " . $return_url); exit(); 
    }
    $stmt_booking->bind_param("i", $booking_id_to_view);
    $stmt_booking->execute();
    $result_booking = $stmt_booking->get_result();
    if ($result_booking->num_rows === 1) {
        $booking_data = $result_booking->fetch_assoc();
    } else {
        $_SESSION['bookings_message'] = "Бронирование с ID " . $booking_id_to_view . " не найдено.";
        $_SESSION['bookings_message_type'] = "error";
        header("Location: " . $return_url);
        exit();
    }
    $stmt_booking->close();

    // 2. Получаем данные пассажиров для этого бронирования
    $stmt_passengers = $conn->prepare("SELECT * FROM passengers WHERE booking_id = ? ORDER BY passenger_id ASC");
    if (!$stmt_passengers) {  
        error_log("DB Error (prepare passengers for booking " . $booking_id_to_view . "): " . $conn->error); 
        $_SESSION['bookings_message']="Ошибка сервера при загрузке пассажиров (BD2)."; 
        $_SESSION['bookings_message_type'] = "error"; 
        header("Location: " . $return_url); exit(); 
    }
    $stmt_passengers->bind_param("i", $booking_id_to_view);
    $stmt_passengers->execute();
    $result_passengers = $stmt_passengers->get_result();
    while($row_pax = $result_passengers->fetch_assoc()){ // Используем $row_pax, чтобы не конфликтовать с $row для сегментов
        $passengers_data[] = $row_pax;
    }
    $stmt_passengers->close();

    // 3. Получаем данные сегментов перелета для этого бронирования
    $stmt_segments = $conn->prepare(
        "SELECT bs.*, 
                dep_ap.name as departure_airport_name, dep_ap.city as departure_city,
                arr_ap.name as arrival_airport_name, arr_ap.city as arrival_city,
                al.name as airline_name, oal.name as operating_airline_name
         FROM booked_segments bs
         LEFT JOIN airports dep_ap ON bs.departure_airport_iata_code = dep_ap.iata_code
         LEFT JOIN airports arr_ap ON bs.arrival_airport_iata_code = arr_ap.iata_code
         LEFT JOIN airlines al ON bs.airline_iata_code = al.iata_code
         LEFT JOIN airlines oal ON bs.operating_airline_iata_code = oal.iata_code
         WHERE bs.booking_id = ? ORDER BY bs.sequence_number ASC"
    );
    if (!$stmt_segments) { 
        error_log("DB Error (prepare segments for booking " . $booking_id_to_view . "): " . $conn->error); 
        $_SESSION['bookings_message']="Ошибка сервера при загрузке сегментов (BD3)."; 
        $_SESSION['bookings_message_type'] = "error"; 
        header("Location: " . $return_url); exit(); 
    }
    $stmt_segments->bind_param("i", $booking_id_to_view);
    $stmt_segments->execute();
    $result_segments = $stmt_segments->get_result();
     while($row_seg = $result_segments->fetch_assoc()){ // Используем $row_seg
        $segments_data[] = $row_seg;
    }
    $stmt_segments->close();

    // 4. Получаем информацию о платеже (предполагаем один последний успешный или ожидающий платеж)
    $stmt_payment = $conn->prepare("SELECT * FROM payments WHERE booking_id = ? ORDER BY created_at DESC LIMIT 1");
    if ($stmt_payment) {
        $stmt_payment->bind_param("i", $booking_id_to_view);
        $stmt_payment->execute();
        $result_payment = $stmt_payment->get_result();
        if ($result_payment->num_rows >= 1) { // >= 1 если может быть несколько, но мы берем последний
            $payment_data = $result_payment->fetch_assoc();
        }
        $stmt_payment->close();
    } else {
        error_log("DB Error (prepare payment for booking " . $booking_id_to_view . "): " . $conn->error);
        // Не критичная ошибка, можно продолжить без данных о платеже
    }

} else {
    $_SESSION['bookings_message'] = "Не указан ID бронирования для просмотра.";
    $_SESSION['bookings_message_type'] = "error";
    header("Location: " . $return_url); // Возврат на список по умолчанию
    exit();
}

// Массивы для человекочитаемых значений ENUM
$booking_statuses_map_details = [
    'pending_payment' => 'Ожидает оплаты', 'confirmed' => 'Подтверждено', 'ticketed' => 'Билеты выписаны',
    'cancelled_by_user' => 'Отменено клиентом', 'cancelled_by_airline' => 'Отменено авиакомпанией',
    'payment_failed' => 'Ошибка оплаты', 'error' => 'Ошибка системы', 'completed' => 'Завершено'
];
$passenger_types_map_details = ['adult' => 'Взрослый', 'child' => 'Ребенок', 'infant' => 'Младенец'];
$document_types_map_details = [
    'passport_intl' => 'Загранпаспорт', 'passport_national' => 'Нац. паспорт', 
    'id_card' => 'ID-карта', 'birth_certificate' => 'Свид. о рождении'
];
$gender_map_details = ['male' => 'Мужской', 'female' => 'Женский', 'other' => 'Другой', 'undisclosed' => 'Не указан'];
$payment_statuses_map_details = [
    'pending' => 'Ожидает', 'succeeded' => 'Успешно', 'failed' => 'Ошибка', 
    'refunded' => 'Возвращен', 'partially_refunded' => 'Частичный возврат', 'chargeback' => 'Чарджбэк'
];

function format_booking_datetime_utc($datetime_utc_str, $format = 'd.m.Y H:i') { // Переименовал функцию
    if (empty($datetime_utc_str) || $datetime_utc_str == '0000-00-00 00:00:00') return '-';
    try {
        $dt = new DateTime($datetime_utc_str, new DateTimeZone('UTC'));
        // Для админки можно всегда показывать UTC или заданный админский пояс
        // $dt->setTimezone(new DateTimeZone('Europe/Moscow')); 
        return $dt->format($format) . ' <small>(UTC)</small>';
    } catch (Exception $e) {
        return htmlspecialchars($datetime_utc_str); 
    }
}
function format_booking_date($date_str, $format = 'd.m.Y') { // Переименовал функцию
     if (empty($date_str) || $date_str == '0000-00-00') return '-';
    try {
        $dt = new DateTime($date_str);
        return $dt->format($format);
    } catch (Exception $e) {
        return htmlspecialchars($date_str);
    }
}

?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Детали Бронирования PNR: <?php echo htmlspecialchars($booking_data['booking_reference']); ?> - Админ AirGO</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/admin-style.css"> 
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Локальные стили были перенесены в admin-style.css -->
</head>
<body>
    <div class="booking-details-page"> <!-- Обертка для всей страницы деталей -->
        <div class="details-container"> <!-- Основной контейнер с белым фоном -->
            <h1>Детали Бронирования</h1>
            <span class="pnr-sub">PNR: <?php echo htmlspecialchars($booking_data['booking_reference']); ?></span>

            <div class="btn-container-details" style="margin-bottom:2rem;">
                 <a href="<?php echo htmlspecialchars($return_url); ?>" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Назад к списку</a>
                 <!-- <button onclick="window.print()" class="btn btn-primary"><i class="fas fa-print"></i> Печать</button> -->
            </div>

            <section class="detail-section">
                <h2><i class="fas fa-file-invoice-dollar"></i> Основная информация</h2>
                <div class="info-grid-details">
                    <div class="info-item-detail"><label>ID Бронирования:</label><span class="value"><?php echo $booking_data['booking_id']; ?></span></div>
                    <div class="info-item-detail"><label>PNR:</label><span class="value"><?php echo htmlspecialchars($booking_data['booking_reference']); ?></span></div>
                    <div class="info-item-detail">
                        <label>Статус:</label>
                        <span class="value">
                            <span class="status-badge status-booking-<?php echo htmlspecialchars($booking_data['status']); ?>" title="<?php echo htmlspecialchars($booking_data['status']); ?>">
                                <?php echo htmlspecialchars($booking_statuses_map_details[$booking_data['status']] ?? ucfirst(str_replace('_', ' ', $booking_data['status']))); ?>
                            </span>
                        </span>
                    </div>
                    <div class="info-item-detail"><label>Общая стоимость:</label><span class="value"><?php echo htmlspecialchars(number_format((float)$booking_data['total_price'], 2, '.', ' ')) . ' ' . htmlspecialchars($booking_data['currency_code']); ?></span></div>
                    <div class="info-item-detail"><label>Контактный Email:</label><span class="value"><?php echo htmlspecialchars($booking_data['contact_email']); ?></span></div>
                    <div class="info-item-detail"><label>Контактный Телефон:</label><span class="value"><?php echo htmlspecialchars($booking_data['contact_phone']); ?></span></div>
                     <div class="info-item-detail">
                        <label>Клиент:</label>
                        <span class="value">
                            <?php if($booking_data['user_id']): ?>
                                <?php echo htmlspecialchars(trim($booking_data['user_first_name'] . ' ' . $booking_data['user_last_name'])); ?>
                                <small>(Email: <?php echo htmlspecialchars($booking_data['user_email']); ?> | ID: <?php echo $booking_data['user_id']; ?>)</small>
                            <?php else: ?>
                                Гость (не зарегистрирован)
                            <?php endif; ?>
                        </span>
                    </div>
                    <div class="info-item-detail"><label>Дата создания:</label><span class="value"><?php echo format_booking_datetime_utc($booking_data['created_at']); ?></span></div>
                     <div class="info-item-detail"><label>Обновлено:</label><span class="value"><?php echo format_booking_datetime_utc($booking_data['updated_at']); ?></span></div>
                    <?php if(!empty($booking_data['payment_deadline']) && $booking_data['payment_deadline'] != '0000-00-00 00:00:00'): ?>
                    <div class="info-item-detail">
                        <label>Оплатить до:</label>
                        <span class="value" style="color: var(--error-text); font-weight:bold;"><?php echo format_booking_datetime_utc($booking_data['payment_deadline']); ?></span>
                    </div>
                    <?php endif; ?>
                </div>
            </section>

            <?php if ($payment_data): ?>
            <section class="detail-section">
                <h2><i class="fas fa-credit-card"></i> Информация о платеже</h2>
                <div class="info-grid-details">
                    <div class="info-item-detail"><label>ID Платежа:</label><span class="value"><?php echo $payment_data['payment_id']; ?></span></div>
                    <div class="info-item-detail"><label>Сумма:</label><span class="value"><?php echo htmlspecialchars(number_format((float)$payment_data['amount'],2,'.',' ')) . ' ' . $payment_data['currency_code']; ?></span></div>
                    <div class="info-item-detail">
                        <label>Статус платежа:</label>
                        <span class="value">
                             <span class="status-badge status-payment-<?php echo htmlspecialchars($payment_data['status']); ?>" title="<?php echo htmlspecialchars($payment_data['status']); ?>">
                                <?php echo htmlspecialchars($payment_statuses_map_details[$payment_data['status']] ?? ucfirst($payment_data['status'])); ?>
                            </span>
                        </span>
                    </div>
                    <div class="info-item-detail"><label>Платежный шлюз:</label><span class="value"><?php echo htmlspecialchars(ucfirst(str_replace('_',' ',$payment_data['payment_gateway']))); ?></span></div>
                    <div class="info-item-detail"><label>ID транзакции шлюза:</label><span class="value"><?php echo htmlspecialchars($payment_data['gateway_transaction_id'] ?: '-'); ?></span></div>
                    <div class="info-item-detail"><label>Детали метода оплаты:</label><span class="value"><?php echo htmlspecialchars($payment_data['payment_method_details'] ?: '-'); ?></span></div>
                     <div class="info-item-detail"><label>Дата платежа:</label><span class="value"><?php echo format_booking_datetime_utc($payment_data['created_at']); ?></span></div>
                </div>
                <?php if(!empty($payment_data['gateway_response'])): ?>
                    <div class="form-group" style="margin-top:1.5rem;"> <!-- Используем form-group для консистентности отступов -->
                        <label style="font-weight:500; color: var(--dark-gray);">Ответ шлюза (JSON):</label>
                        <div class="json-details"><?php echo htmlspecialchars(json_encode(json_decode($payment_data['gateway_response']), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)); ?></div>
                    </div>
                <?php endif; ?>
            </section>
            <?php endif; ?>


            <section class="detail-section">
                <h2><i class="fas fa-users"></i> Пассажиры (<?php echo count($passengers_data); ?>)</h2>
                <?php if (empty($passengers_data)): ?>
                    <p>Информация о пассажирах отсутствует для этого бронирования.</p>
                <?php else: ?>
                    <?php foreach ($passengers_data as $pax): ?>
                    <div class="passenger-card">
                        <h3>
                            <i class="fas fa-user"></i>
                            <?php echo htmlspecialchars(trim($pax['first_name'] . ' ' . ($pax['middle_name'] ? $pax['middle_name'] . ' ' : '') . $pax['last_name'])); ?>
                            <small>(<?php echo htmlspecialchars($passenger_types_map_details[$pax['passenger_type']] ?? $pax['passenger_type']); ?>)</small>
                        </h3>
                        <div class="passenger-info-grid">
                            <div class="info-pair"><strong>ID Пассажира:</strong> <span><?php echo $pax['passenger_id']; ?></span></div>
                            <div class="info-pair"><strong>Дата рождения:</strong> <span><?php echo format_booking_date($pax['date_of_birth']); ?></span></div>
                            <div class="info-pair"><strong>Пол:</strong> <span><?php echo htmlspecialchars($gender_map_details[$pax['gender']] ?? $pax['gender']); ?></span></div>
                            <div class="info-pair" style="grid-column: span 2;"><strong>Документ:</strong> 
                                <span>
                                    <?php echo htmlspecialchars($document_types_map_details[$pax['document_type']] ?? $pax['document_type']); ?> 
                                    № <?php echo htmlspecialchars($pax['document_number']); ?>
                                </span>
                            </div>
                            <?php if(!empty($pax['document_expiry_date'])): ?>
                            <div class="info-pair"><strong>Срок действия док-та:</strong> <span><?php echo format_booking_date($pax['document_expiry_date']); ?></span></div>
                            <?php endif; ?>
                             <?php if(!empty($pax['document_issuing_country_code'])): ?>
                            <div class="info-pair"><strong>Страна выдачи (док-т):</strong> <span><?php echo htmlspecialchars(strtoupper($pax['document_issuing_country_code'])); ?></span></div>
                            <?php endif; ?>
                            <div class="info-pair"><strong>Гражданство:</strong> <span><?php echo htmlspecialchars(strtoupper($pax['nationality_country_code'])); ?></span></div>
                             <?php if(!empty($pax['ticket_number'])): ?>
                            <div class="info-pair" style="grid-column: span 2; background-color: var(--light-green); padding: 0.3rem 0.5rem; border-radius: 3px;"><strong>Номер билета:</strong> <span style="font-weight:bold; color:var(--primary-green);"><?php echo htmlspecialchars($pax['ticket_number']); ?></span></div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </section>

            <section class="detail-section">
                 <h2><i class="fas fa-route"></i> Сегменты перелета (<?php echo count($segments_data); ?>)</h2>
                 <?php if (empty($segments_data)): ?>
                    <p>Информация о сегментах перелета отсутствует для этого бронирования.</p>
                <?php else: ?>
                    <?php foreach ($segments_data as $idx => $seg): ?>
                    <div class="segment-card">
                        <h3>
                            <i class="fas fa-plane-departure"></i>
                            Сегмент <?php echo $seg['sequence_number']; ?>: 
                            <span class="segment-route">
                                <?php echo htmlspecialchars($seg['departure_airport_iata_code']); ?>
                                <i class="fas fa-long-arrow-alt-right route-arrow-table"></i>
                                <?php echo htmlspecialchars($seg['arrival_airport_iata_code']); ?>
                            </span>
                        </h3>
                        <div class="segment-info-grid">
                            <div class="info-pair"><strong>Рейс:</strong> <span><?php echo htmlspecialchars($seg['airline_iata_code'] . $seg['flight_number']); ?> (<?php echo htmlspecialchars($seg['airline_name'] ?: $seg['airline_iata_code']); ?>)</span></div>
                             <?php if ($seg['operating_airline_iata_code'] && $seg['operating_airline_iata_code'] != $seg['airline_iata_code']): ?>
                            <div class="info-pair"><strong>Выполняет:</strong> <span><?php echo htmlspecialchars($seg['operating_airline_name'] ?: $seg['operating_airline_iata_code']); ?></span></div>
                             <?php endif; ?>
                            <div class="info-pair" style="grid-column: span 2;"><strong>Вылет:</strong> 
                                <span>
                                    <?php echo htmlspecialchars($seg['departure_city'] . ' (' . $seg['departure_airport_name'] . ', ' . $seg['departure_airport_iata_code'] . ')'); ?>
                                    <?php echo $seg['departure_terminal'] ? ', Терминал ' . htmlspecialchars($seg['departure_terminal']) : ''; ?>
                                </span>
                            </div>
                            <div class="info-pair"><strong>Время вылета:</strong> <span><?php echo format_booking_datetime_utc($seg['departure_at_utc']); ?></span></div>
                           
                            <div class="info-pair" style="grid-column: span 2;"><strong>Прилет:</strong> 
                                <span>
                                     <?php echo htmlspecialchars($seg['arrival_city'] . ' (' . $seg['arrival_airport_name'] . ', ' . $seg['arrival_airport_iata_code'] . ')'); ?>
                                    <?php echo $seg['arrival_terminal'] ? ', Терминал ' . htmlspecialchars($seg['arrival_terminal']) : ''; ?>
                                </span>
                            </div>
                             <div class="info-pair"><strong>Время прилета:</strong> <span><?php echo format_booking_datetime_utc($seg['arrival_at_utc']); ?></span></div>
                            <?php if ($seg['duration_minutes']): ?>
                            <div class="info-pair"><strong>Длительность:</strong> <span><?php echo floor($seg['duration_minutes'] / 60) . 'ч ' . ($seg['duration_minutes'] % 60) . 'м'; ?></span></div>
                            <?php endif; ?>
                             <div class="info-pair"><strong>Класс бронирования:</strong> <span><?php echo htmlspecialchars($seg['booking_class'] ?: '-'); ?></span></div>
                             <div class="info-pair"><strong>База тарифа:</strong> <span><?php echo htmlspecialchars($seg['fare_basis'] ?: '-'); ?></span></div>
                             <div class="info-pair"><strong>Тип самолета:</strong> <span><?php echo htmlspecialchars($seg['aircraft_type'] ?: '-'); ?></span></div>
                        </div>
                    </div>
                    <?php 
                        // Отображение времени пересадки
                        if ($idx < count($segments_data) - 1 && isset($segments_data[$idx+1])) {
                            try {
                                $arrival_current_dt = new DateTime($seg['arrival_at_utc'], new DateTimeZone('UTC'));
                                $departure_next_dt = new DateTime($segments_data[$idx+1]['departure_at_utc'], new DateTimeZone('UTC'));
                                if ($departure_next_dt > $arrival_current_dt) { // Убедимся, что следующий вылет после прилета
                                    $layover_interval = $arrival_current_dt->diff($departure_next_dt);
                                    echo '<div class="layover-info"><i class="fas fa-clock"></i> Пересадка: ' . $layover_interval->format('%hч %iм') . ' в ' . htmlspecialchars($seg['arrival_city'] . ' ('. $seg['arrival_airport_iata_code'].')') . '</div>';
                                }
                            } catch (Exception $e) { /* Ошибка парсинга даты */ }
                        }
                    ?>
                    <?php endforeach; ?>
                <?php endif; ?>
            </section>
            
            <!-- Здесь можно добавить секцию для просмотра ancillary_services_booked, если есть -->

            <div class="btn-container-details">
                 <a href="<?php echo htmlspecialchars($return_url); ?>" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Назад к списку</a>
            </div>
        </div>
    </div>
</body>
</html>