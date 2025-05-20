<?php
// my-bookings-log.php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// 1. Проверка, залогинен ли пользователь
if (!isset($_SESSION['user_id'])) {
    $_SESSION['login_error'] = "Пожалуйста, войдите в систему, чтобы просмотреть свои бронирования.";
    $redirect_url = "my-bookings-log.php"; 
    if (!empty($_SERVER['QUERY_STRING'])) {
        $redirect_url .= "?" . $_SERVER['QUERY_STRING'];
    }
    header("Location: login.php?redirect=" . urlencode($redirect_url));
    exit();
}

require_once 'db.php'; 
$current_user_id = $_SESSION['user_id'];

// ... (остальной PHP-код для загрузки бронирований $my_bookings ОСТАЕТСЯ БЕЗ ИЗМЕНЕНИЙ, как в предыдущем ответе) ...
$sql_my_bookings = "SELECT 
                        b.booking_id, b.booking_reference, b.status, b.total_price, b.currency_code, 
                        b.created_at,
                        (SELECT GROUP_CONCAT(DISTINCT CONCAT(p.first_name, ' ', p.last_name) ORDER BY p.passenger_id SEPARATOR ', ') 
                         FROM passengers p WHERE p.booking_id = b.booking_id) as passenger_names_list,
                        (SELECT bs_dep.departure_airport_iata_code 
                         FROM booked_segments bs_dep 
                         WHERE bs_dep.booking_id = b.booking_id 
                         ORDER BY bs_dep.sequence_number ASC LIMIT 1) as first_departure_iata,
                        (SELECT dep_ap.city FROM airports dep_ap JOIN booked_segments bs_dep_city ON dep_ap.iata_code = bs_dep_city.departure_airport_iata_code
                         WHERE bs_dep_city.booking_id = b.booking_id ORDER BY bs_dep_city.sequence_number ASC LIMIT 1) as first_departure_city,
                        (SELECT bs_arr.arrival_airport_iata_code 
                         FROM booked_segments bs_arr 
                         WHERE bs_arr.booking_id = b.booking_id 
                         ORDER BY bs_arr.sequence_number DESC LIMIT 1) as last_arrival_iata,
                        (SELECT arr_ap.city FROM airports arr_ap JOIN booked_segments bs_arr_city ON arr_ap.iata_code = bs_arr_city.arrival_airport_iata_code
                         WHERE bs_arr_city.booking_id = b.booking_id ORDER BY bs_arr_city.sequence_number DESC LIMIT 1) as last_arrival_city,
                        (SELECT MIN(bs_date.departure_at_utc) 
                         FROM booked_segments bs_date 
                         WHERE bs_date.booking_id = b.booking_id) as first_departure_datetime,
                        (SELECT al.name FROM airlines al JOIN booked_segments bs_al ON al.iata_code = bs_al.airline_iata_code
                         WHERE bs_al.booking_id = b.booking_id ORDER BY bs_al.sequence_number ASC LIMIT 1) as first_airline_name,
                        (SELECT bs_fn.flight_number FROM booked_segments bs_fn
                         WHERE bs_fn.booking_id = b.booking_id ORDER BY bs_fn.sequence_number ASC LIMIT 1) as first_flight_number
                      FROM bookings b
                      WHERE b.user_id = ?
                      ORDER BY b.created_at DESC";

$stmt_my_bookings = $conn->prepare($sql_my_bookings);
$my_bookings = [];

if ($stmt_my_bookings) {
    $stmt_my_bookings->bind_param("i", $current_user_id);
    if(!$stmt_my_bookings->execute()){
        error_log("Error executing my_bookings query: " . $stmt_my_bookings->error);
    } else {
        $result_my_bookings = $stmt_my_bookings->get_result();
        while ($row = $result_my_bookings->fetch_assoc()) {
            $my_bookings[] = $row;
        }
    }
    $stmt_my_bookings->close();
} else {
    error_log("Error preparing my_bookings query: " . $conn->error);
}

$booking_statuses_user_map = [
    'pending_payment' => 'Ожидает оплаты', 'confirmed' => 'Подтверждено', 'ticketed' => 'Билеты выписаны',
    'cancelled_by_user' => 'Отменено вами', 'cancelled_by_airline' => 'Отменено авиакомпанией',
    'payment_failed' => 'Ошибка оплаты', 'error' => 'Ошибка бронирования', 'completed' => 'Завершено'
];

function format_user_datetime($datetime_str, $format = 'd M Y, H:i') {
    if (!$datetime_str) return '-';
    try {
        $date = new DateTime($datetime_str); 
        return $date->format($format) . ' <small>(UTC)</small>';
    } catch (Exception $e) {
        return $datetime_str; 
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Мои Бронирования - AirGO</title> <!-- Изменено на AirGO -->
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/my-bookings-style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <header class="header">
        <div class="container header__container">
            <a href="index-log.php" class="header__logo">AirGO</a> <!-- Изменено на AirGO -->
            <nav class="header__nav">
                <ul>
                    <li><a href="my-bookings-log.php" class="active">Мои бронирования</a></li>
                    <li><a href="mailto:borovetf@gmail.com">Помощь</a></li>
                    <li><a href="profile.php">Профиль</a></li>
                    <!-- Кнопка "Выход" удалена из этого меню -->
                </ul>
            </nav>
        </div>
    </header>

    <main class="main-content-my-bookings"> <!-- Переименовал класс для main -->
        <div class="container page-content-container"> <!-- Добавлен общий контейнер для контента -->
            <h1 class="page-title">Мои Бронирования</h1>

            <?php if (isset($_SESSION['booking_action_message'])): ?>
                <div class="action-message <?php echo $_SESSION['booking_action_message_type'] ?? 'info'; ?>">
                    <?php echo htmlspecialchars($_SESSION['booking_action_message']); ?>
                </div>
                <?php unset($_SESSION['booking_action_message'], $_SESSION['booking_action_message_type']); ?>
            <?php endif; ?>

            <?php if (empty($my_bookings)): ?>
                <div class="no-bookings-placeholder">
                    <i class="fas fa-suitcase-rolling empty-icon"></i>
                    <p>У вас пока нет активных или прошлых бронирований.</p>
                    <a href="index-log.php" class="btn btn--primary">Найти билеты</a>
                </div>
            <?php else: ?>
                <div class="bookings-list">
                    <?php foreach ($my_bookings as $booking): ?>
                    <article class="booking-item booking-status-<?php echo htmlspecialchars($booking['status']); ?>">
                        <div class="booking-item__header">
                            <span class="booking-item__pnr">PNR: <strong><?php echo htmlspecialchars($booking['booking_reference']); ?></strong></span>
                            <span class="booking-item__status booking-item__status--<?php echo htmlspecialchars($booking['status']); ?>">
                                <?php echo htmlspecialchars($booking_statuses_user_map[$booking['status']] ?? ucfirst(str_replace('_',' ',$booking['status']))); ?>
                            </span>
                        </div>
                        <div class="booking-item__route">
                            <div class="route-point">
                                <span class="route-point__city-code"><?php echo htmlspecialchars($booking['first_departure_iata'] ?: '???'); ?></span>
                                <span class="route-point__city-name"><?php echo htmlspecialchars($booking['first_departure_city'] ?: 'Город вылета'); ?></span>
                            </div>
                            <div class="route-arrow">
                                <i class="fas fa-plane"></i>
                            </div>
                            <div class="route-point">
                                <span class="route-point__city-code"><?php echo htmlspecialchars($booking['last_arrival_iata'] ?: '???'); ?></span>
                                <span class="route-point__city-name"><?php echo htmlspecialchars($booking['last_arrival_city'] ?: 'Город прилета'); ?></span>
                            </div>
                        </div>
                        <div class="booking-item__info">
                            <div class="info-block">
                                <span class="info-block__label"><i class="far fa-calendar-alt"></i> Дата вылета:</span>
                                <span class="info-block__value"><?php echo format_user_datetime($booking['first_departure_datetime']); ?></span>
                            </div>
                            <div class="info-block">
                                <span class="info-block__label"><i class="fas fa-user-friends"></i> Пассажиры:</span>
                                <span class="info-block__value" title="<?php echo htmlspecialchars($booking['passenger_names_list'] ?: 'Н/Д'); ?>">
                                    <?php 
                                        $pax_list = $booking['passenger_names_list'] ?: 'Не указаны';
                                        echo strlen($pax_list) > 35 ? htmlspecialchars(mb_substr($pax_list, 0, 32)) . '...' : htmlspecialchars($pax_list);
                                    ?>
                                </span>
                            </div>
                            <div class="info-block">
                                <span class="info-block__label"><i class="fas fa-building"></i> Авиакомпания:</span>
                                <span class="info-block__value">
                                    <?php echo htmlspecialchars($booking['first_airline_name'] ?: 'Н/Д'); ?>
                                    <?php if ($booking['first_flight_number']): ?>
                                        (<?php echo htmlspecialchars($booking['first_flight_number']); ?>)
                                    <?php endif; ?>
                                </span>
                            </div>
                             <div class="info-block info-block--price">
                                <span class="info-block__label">Стоимость:</span>
                                <span class="info-block__value"><?php echo htmlspecialchars(number_format((float)$booking['total_price'], 2, '.', ' ')) . ' ' . htmlspecialchars($booking['currency_code']); ?></span>
                            </div>
                        </div>
                        <div class="booking-item__actions">
                            <a href="booking_details_user.php?id=<?php echo $booking['booking_id']; ?>" class="btn btn--secondary btn--small"><i class="fas fa-cog"></i> Управлять</a>
                            <a href="booking_details_user.php?id=<?php echo $booking['booking_id']; ?>" class="btn btn--outline btn--small"><i class="fas fa-info-circle"></i> Подробнее</a>
                        </div>
                         <div class="booking-item__created-date">
                            Забронировано: <?php echo date("d.m.Y H:i", strtotime($booking['created_at'])); ?>
                        </div>
                    </article>
                    <?php endforeach; ?>
                </div>
                <!-- Здесь будет пагинация, если вы ее добавите -->
            <?php endif; ?>
        </div>
    </main>

    <footer class="footer">
        <div class="container footer__container">
            <div class="footer__links">
                <a href="about-us-log.php">О нас</a>
                <a href="mailto:borovetf@gmail.com">Контакты</a>
                <a href="https://docs.google.com/document/d/1uUSg0HDIPny75EqESQr0gu2Utg3AtNBaLw0Xk0-TyL0/edit?usp=sharing" target="_blank" rel="noopener noreferrer">Правила и условия</a>
                <a href="https://docs.google.com/document/d/1drFUdo3izJodkkSkofe_e5AcnV0Ahl9jpezZYmJgZlU/edit?usp=sharing" target="_blank" rel="noopener noreferrer">Политика конфиденциальности</a>
            </div>
            <div class="footer__copyright">
                © <?php echo date("Y"); ?> AirGO. Все права защищены. <!-- Изменено на AirGO -->
            </div>
        </div>
    </footer>
</body>
</html>