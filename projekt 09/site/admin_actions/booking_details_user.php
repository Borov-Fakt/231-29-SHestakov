<?php
// booking_details_user.php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// 1. Проверка, залогинен ли пользователь
if (!isset($_SESSION['user_id'])) {
    $_SESSION['login_error'] = "Пожалуйста, войдите в систему, чтобы просмотреть детали вашего бронирования.";
    $redirect_url = "booking_details_user.php" . (!empty($_SERVER['QUERY_STRING']) ? "?" . $_SERVER['QUERY_STRING'] : '');
    header("Location: login.php?redirect=" . urlencode($redirect_url));
    exit();
}

require_once 'db.php'; // Подключение к БД
$current_user_id = $_SESSION['user_id'];
$booking_id_to_view = null;
$booking_data = null;
$passengers_data = [];
$segments_data = [];
// $payment_data не будем выводить пользователю так подробно, как админу

// Массивы для человекочитаемых значений ENUM (могут быть в отдельном файле helpers.php)
$booking_statuses_user_map = [
    'pending_payment' => 'Ожидает оплаты', 'confirmed' => 'Подтверждено', 'ticketed' => 'Билеты выписаны',
    'cancelled_by_user' => 'Отменено вами', 'cancelled_by_airline' => 'Отменено авиакомпанией',
    'payment_failed' => 'Ошибка оплаты', 'error' => 'Ошибка бронирования', 'completed' => 'Завершено', 'полет состоялся' => 'Полет состоялся'
];
$passenger_types_map_user = ['adult' => 'Взрослый', 'child' => 'Ребенок', 'infant' => 'Младенец'];
$document_types_map_user = [
    'passport_intl' => 'Загранпаспорт', 'passport_national' => 'Нац. паспорт', 
    'id_card' => 'ID-карта', 'birth_certificate' => 'Свид. о рождении'
];
$gender_map_user = ['male' => 'Мужской', 'female' => 'Женский', 'other' => 'Другой', 'undisclosed' => 'Не указан'];

// URL для кнопки "Назад" (ведет на "Мои бронирования")
$back_to_my_bookings_url = "my-bookings-log.php"; // Предполагаем, что пагинации здесь нет или она простая


if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $booking_id_to_view = (int)$_GET['id'];

    // 1. Получаем основную информацию о бронировании, УБЕДИВШИСЬ, ЧТО ОНО ПРИНАДЛЕЖИТ ТЕКУЩЕМУ ПОЛЬЗОВАТЕЛЮ
    $stmt_booking = $conn->prepare(
        "SELECT b.* 
         FROM bookings b
         WHERE b.booking_id = ? AND b.user_id = ?" // Ключевая проверка!
    );
    if (!$stmt_booking) { error_log("DB Error (user booking details prepare): " . $conn->error); $_SESSION['booking_action_message']="Ошибка сервера (UBD1)."; $_SESSION['booking_action_message_type'] = "error"; header("Location: " . $back_to_my_bookings_url); exit(); }
    
    $stmt_booking->bind_param("ii", $booking_id_to_view, $current_user_id);
    if (!$stmt_booking->execute()){ error_log("DB Error (user booking details exec): " . $stmt_booking->error); $_SESSION['booking_action_message']="Ошибка сервера (UBD1e)."; $_SESSION['booking_action_message_type'] = "error"; header("Location: " . $back_to_my_bookings_url); exit(); }
    
    $result_booking = $stmt_booking->get_result();
    if ($result_booking->num_rows === 1) {
        $booking_data = $result_booking->fetch_assoc();
    } else {
        $_SESSION['booking_action_message'] = "Запрашиваемое бронирование не найдено или не принадлежит вам.";
        $_SESSION['booking_action_message_type'] = "error";
        header("Location: " . $back_to_my_bookings_url);
        exit();
    }
    $stmt_booking->close();

    // 2. Получаем данные пассажиров для этого бронирования
    $stmt_passengers = $conn->prepare("SELECT * FROM passengers WHERE booking_id = ? ORDER BY passenger_id ASC");
    if (!$stmt_passengers) { error_log("DB Error (user pax prepare): " . $conn->error); /* обработка */ }
    else {
        $stmt_passengers->bind_param("i", $booking_id_to_view);
        $stmt_passengers->execute();
        $result_passengers = $stmt_passengers->get_result();
        while($row_pax = $result_passengers->fetch_assoc()){
            $passengers_data[] = $row_pax;
        }
        $stmt_passengers->close();
    }


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
    if (!$stmt_segments) { error_log("DB Error (user seg prepare): " . $conn->error); /* ... */ }
    else {
        $stmt_segments->bind_param("i", $booking_id_to_view);
        $stmt_segments->execute();
        $result_segments = $stmt_segments->get_result();
         while($row_seg = $result_segments->fetch_assoc()){
            $segments_data[] = $row_seg;
        }
        $stmt_segments->close();
    }

} else {
    $_SESSION['booking_action_message'] = "Не указан ID бронирования для просмотра.";
    $_SESSION['booking_action_message_type'] = "error";
    header("Location: " . $back_to_my_bookings_url);
    exit();
}

// Функции форматирования дат (можно вынести в helpers.php)
function format_pax_datetime_utc($datetime_utc_str, $format = 'd M Y, H:i') {
    if (empty($datetime_utc_str) || $datetime_utc_str == '0000-00-00 00:00:00') return '-';
    try {
        $dt = new DateTime($datetime_utc_str, new DateTimeZone('UTC'));
        // Конвертируем в локальный часовой пояс пользователя, если он известен,
        // или в часовой пояс сайта. Для примера - просто UTC с пометкой.
        // $user_timezone = $_SESSION['user_timezone'] ?? 'Europe/Moscow'; // Пример
        // $dt->setTimezone(new DateTimeZone($user_timezone));
        return $dt->format($format) . ' <small>(UTC)</small>';
    } catch (Exception $e) { return htmlspecialchars($datetime_utc_str); }
}
function format_pax_date($date_str, $format = 'd.m.Y') {
     if (empty($date_str) || $date_str == '0000-00-00') return '-';
    try {
        $dt = new DateTime($date_str);
        return $dt->format($format);
    } catch (Exception $e) { return htmlspecialchars($date_str); }
}

$page_title_user = "Детали бронирования " . htmlspecialchars($booking_data['booking_reference']) . " - AirGO";
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title_user; ?></title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/my-bookings-style.css"> <!-- Стили для карточек и общие для страницы -->
    <link rel="stylesheet" href="css/booking-details-user-style.css"> <!-- Новые специфичные стили -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <header class="header">
        <div class="container header__container">
            <a href="index-log.php" class="header__logo">AirGO</a>
            <nav class="header__nav">
                <ul>
                    <li><a href="my-bookings-log.php">Мои бронирования</a></li>
                    <li><a href="mailto:borovetf@gmail.com">Помощь</a></li>
                    <li><a href="profile.php">Профиль</a></li>
                    <!-- Кнопка выход должна быть на profile.php -->
                </ul>
            </nav>
        </div>
    </header>

    <main class="main-content-booking-details"> <!-- Новый класс для main -->
        <div class="container page-content-container">
            <div class="booking-detail-header">
                <h1 class="page-title">Детали бронирования</h1>
                <span class="pnr-highlight">PNR: <?php echo htmlspecialchars($booking_data['booking_reference']); ?></span>
            </div>

             <div class="actions-top-bar">
                 <a href="<?php echo $back_to_my_bookings_url; ?>" class="btn btn--outline btn--small"><i class="fas fa-arrow-left"></i> Назад к моим бронированиям</a>
                 <button onclick="window.print();" class="btn btn--secondary btn--small"><i class="fas fa-print"></i> Распечатать</button>
                 <!-- Здесь могут быть другие кнопки: Отменить (если возможно), Запросить изменение и т.д. -->
             </div>


            <div class="booking-main-info-panel">
                <div class="info-row">
                    <span class="info-label"><i class="fas fa-info-circle"></i> Статус:</span>
                    <span class="info-value status-badge-detail status-booking-<?php echo htmlspecialchars($booking_data['status']); ?>">
                        <?php echo htmlspecialchars($booking_statuses_user_map[$booking_data['status']] ?? ucfirst(str_replace('_', ' ', $booking_data['status']))); ?>
                    </span>
                </div>
                <div class="info-row">
                    <span class="info-label"><i class="fas fa-envelope"></i> Контактный Email:</span>
                    <span class="info-value"><?php echo htmlspecialchars($booking_data['contact_email']); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label"><i class="fas fa-phone"></i> Контактный Телефон:</span>
                    <span class="info-value"><?php echo htmlspecialchars($booking_data['contact_phone']); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label"><i class="fas fa-money-bill-wave"></i> Общая стоимость:</span>
                    <span class="info-value price-value"><?php echo htmlspecialchars(number_format((float)$booking_data['total_price'], 2, '.', ' ')) . ' ' . htmlspecialchars($booking_data['currency_code']); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label"><i class="far fa-calendar-check"></i> Дата создания:</span>
                    <span class="info-value"><?php echo format_pax_datetime_utc($booking_data['created_at'], 'd M Y, H:i:s'); ?></span>
                </div>
                <?php if(!empty($booking_data['payment_deadline']) && $booking_data['payment_deadline'] != '0000-00-00 00:00:00' && $booking_data['status'] == 'pending_payment'): ?>
                <div class="info-row payment-deadline-row">
                    <span class="info-label"><i class="fas fa-hourglass-half"></i> Оплатить до:</span>
                    <span class="info-value important-value"><?php echo format_pax_datetime_utc($booking_data['payment_deadline'], 'd M Y, H:i:s'); ?></span>
                </div>
                <?php endif; ?>
            </div>

            <section class="booking-section passengers-section">
                <h2><i class="fas fa-users"></i> Пассажиры</h2>
                <?php if (empty($passengers_data)): ?>
                    <p class="no-data-message">Информация о пассажирах отсутствует.</p>
                <?php else: ?>
                    <?php foreach ($passengers_data as $pax_idx => $pax): ?>
                    <div class="pax-card">
                        <div class="pax-card__header">
                             <i class="fas fa-user-circle pax-icon"></i>
                            <h3><?php echo htmlspecialchars(trim($pax['first_name'] . ' ' . ($pax['middle_name'] ? $pax['middle_name'] . ' ' : '') . $pax['last_name'])); ?></h3>
                            <span class="pax-type-badge"><?php echo htmlspecialchars($passenger_types_map_user[$pax['passenger_type']] ?? $pax['passenger_type']); ?></span>
                        </div>
                        <div class="pax-card__details">
                            <p><strong>Дата рождения:</strong> <?php echo format_pax_date($pax['date_of_birth']); ?></p>
                            <p><strong>Пол:</strong> <?php echo htmlspecialchars($gender_map_user[$pax['gender']] ?? $pax['gender']); ?></p>
                            <p><strong>Документ:</strong> <?php echo htmlspecialchars($document_types_map_user[$pax['document_type']] ?? $pax['document_type']); ?> № <?php echo htmlspecialchars($pax['document_number']); ?></p>
                            <?php if(!empty($pax['document_expiry_date'])): ?>
                                <p><strong>Срок действия до:</strong> <?php echo format_pax_date($pax['document_expiry_date']); ?></p>
                            <?php endif; ?>
                             <?php if(!empty($pax['document_issuing_country_code'])): ?>
                                <p><strong>Страна выдачи:</strong> <?php echo htmlspecialchars(strtoupper($pax['document_issuing_country_code'])); ?></p>
                            <?php endif; ?>
                            <p><strong>Гражданство:</strong> <?php echo htmlspecialchars(strtoupper($pax['nationality_country_code'])); ?></p>
                            <?php if(!empty($pax['ticket_number'])): ?>
                                <p class="ticket-number-pax"><strong><i class="fas fa-ticket-alt"></i> Номер билета:</strong> <span><?php echo htmlspecialchars($pax['ticket_number']); ?></span></p>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </section>

            <section class="booking-section segments-section">
                 <h2><i class="fas fa-route"></i> Маршрут</h2>
                 <?php if (empty($segments_data)): ?>
                    <p class="no-data-message">Информация о сегментах перелета отсутствует.</p>
                <?php else: ?>
                    <?php foreach ($segments_data as $idx => $seg): ?>
                    <div class="segment-item">
                        <div class="segment-item__flight-info">
                            <div class="airline-logo-placeholder"><i class="fas fa-plane-departure"></i></div> <!-- TODO: Logo if available -->
                            <span class="flight-number"><?php echo htmlspecialchars($seg['airline_iata_code'] . $seg['flight_number']); ?></span>
                            <span class="airline-name"><?php echo htmlspecialchars($seg['airline_name'] ?: $seg['airline_iata_code']); ?></span>
                            <?php if ($seg['operating_airline_iata_code'] && $seg['operating_airline_iata_code'] != $seg['airline_iata_code']): ?>
                                <span class="operating-airline-name">(выполняет <?php echo htmlspecialchars($seg['operating_airline_name'] ?: $seg['operating_airline_iata_code']); ?>)</span>
                            <?php endif; ?>
                        </div>
                        <div class="segment-item__details">
                            <div class="segment-point segment-point--departure">
                                <div class="segment-point__time"><?php echo format_pax_datetime_utc($seg['departure_at_utc'], 'H:i'); ?></div>
                                <div class="segment-point__date"><?php echo format_pax_datetime_utc($seg['departure_at_utc'], 'd M Y'); ?></div>
                                <div class="segment-point__city-iata">
                                    <?php echo htmlspecialchars($seg['departure_city']); ?> 
                                    <span class="iata-code">(<?php echo htmlspecialchars($seg['departure_airport_iata_code']); ?>)</span>
                                </div>
                                <div class="segment-point__airport"><?php echo htmlspecialchars($seg['departure_airport_name']); ?></div>
                                <?php echo $seg['departure_terminal'] ? '<div class="segment-point__terminal">Терминал: '.htmlspecialchars($seg['departure_terminal']).'</div>' : ''; ?>
                            </div>
                            <div class="segment-arrow-duration">
                                <i class="fas fa-long-arrow-alt-right"></i>
                                <?php if ($seg['duration_minutes']): ?>
                                <span class="duration">
                                    <i class="far fa-clock"></i> <?php echo floor($seg['duration_minutes'] / 60) . 'ч ' . ($seg['duration_minutes'] % 60) . 'м'; ?>
                                </span>
                                <?php endif; ?>
                            </div>
                            <div class="segment-point segment-point--arrival">
                                <div class="segment-point__time"><?php echo format_pax_datetime_utc($seg['arrival_at_utc'], 'H:i'); ?></div>
                                <div class="segment-point__date"><?php echo format_pax_datetime_utc($seg['arrival_at_utc'], 'd M Y'); ?></div>
                                <div class="segment-point__city-iata">
                                    <?php echo htmlspecialchars($seg['arrival_city']); ?>
                                    <span class="iata-code">(<?php echo htmlspecialchars($seg['arrival_airport_iata_code']); ?>)</span>
                                </div>
                                <div class="segment-point__airport"><?php echo htmlspecialchars($seg['arrival_airport_name']); ?></div>
                                <?php echo $seg['arrival_terminal'] ? '<div class="segment-point__terminal">Терминал: '.htmlspecialchars($seg['arrival_terminal']).'</div>' : ''; ?>
                            </div>
                        </div>
                        <div class="segment-item__additional-info">
                             <span>Класс: <?php echo htmlspecialchars($seg['booking_class'] ?: '-'); ?></span>
                             <span>Самолет: <?php echo htmlspecialchars($seg['aircraft_type'] ?: '-'); ?></span>
                        </div>
                    </div>
                    <?php 
                        if ($idx < count($segments_data) - 1 && isset($segments_data[$idx+1])) {
                            try {
                                $arrival_current_dt = new DateTime($seg['arrival_at_utc'], new DateTimeZone('UTC'));
                                $departure_next_dt = new DateTime($segments_data[$idx+1]['departure_at_utc'], new DateTimeZone('UTC'));
                                if ($departure_next_dt > $arrival_current_dt) { 
                                    $layover_interval = $arrival_current_dt->diff($departure_next_dt);
                                    echo '<div class="layover-info-user"><i class="fas fa-stopwatch"></i> Пересадка: ' . $layover_interval->format('%hч %iм') . ' в ' . htmlspecialchars($seg['arrival_city'] . ' ('. $seg['arrival_airport_iata_code'].')') . '</div>';
                                }
                            } catch (Exception $e) { /* ignore */ }
                        }
                    ?>
                    <?php endforeach; ?>
                <?php endif; ?>
            </section>

             <!-- Опционально: действия с бронированием для пользователя -->
            <section class="booking-user-actions">
                 <!-- <a href="cancel_booking_request.php?id=<?php echo $booking_data['booking_id']; ?>" class="btn btn--danger btn--small" onclick="return confirm('Вы уверены, что хотите запросить отмену этого бронирования?');"><i class="fas fa-times-circle"></i> Запросить отмену</a> -->
                 <!-- <a href="modify_booking_request.php?id=<?php echo $booking_data['booking_id']; ?>" class="btn btn--primary btn--small"><i class="fas fa-exchange-alt"></i> Запросить изменение</a> -->
            </section>
        </div>
    </main>

    <footer class="footer">
         <div class="container footer__container">
            <div class="footer__links">
                <a href="<?php echo isset($_SESSION['user_id']) ? 'about-us-log.php' : 'about-us.php'; ?>">О нас</a>
                <a href="mailto:borovetf@gmail.com">Контакты</a>
                <a href="https://docs.google.com/document/d/1uUSg0HDIPny75EqESQr0gu2Utg3AtNBaLw0Xk0-TyL0/edit?usp=sharing" target="_blank" rel="noopener noreferrer">Правила и условия</a>
                <a href="https://docs.google.com/document/d/1drFUdo3izJodkkSkofe_e5AcnV0Ahl9jpezZYmJgZlU/edit?usp=sharing" target="_blank" rel="noopener noreferrer">Политика конфиденциальности</a>
            </div>
            <div class="footer__copyright">
                © <?php echo date("Y"); ?> AirGO. Все права защищены.
            </div>
        </div>
    </footer>
</body>
</html>