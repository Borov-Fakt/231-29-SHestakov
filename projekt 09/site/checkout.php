<?php
// checkout.php
if (session_status() == PHP_SESSION_NONE) session_start();
require_once 'db.php'; 

// Получаем ID "выбранного" фейкового рейса и другие параметры из GET
$flight_id_selected = $_GET['flight_id'] ?? null;
$departure_date_checkout = $_GET['dep_date'] ?? null; 
$return_date_checkout = $_GET['ret_date'] ?? null; // Сохраняем, но пока не используем активно
$trip_type_checkout = $_GET['trip_type'] ?? 'round-trip';
$passengers_param_checkout = $_GET['pax'] ?? '1a'; 
$cabin_class_checkout = $_GET['cabin'] ?? 'economy'; // Сохраняем, но пока не используем активно
$origin_checkout = $_GET['origin'] ?? null;
$destination_checkout = $_GET['destination'] ?? null;


if (!$flight_id_selected || !$departure_date_checkout || !$origin_checkout || !$destination_checkout) {
    $_SESSION['general_message'] = "Ошибка: не выбраны параметры рейса для оформления.";
    $_SESSION['general_message_type'] = "error";
    header("Location: index.php");
    exit();
}

// --- СИМУЛЯЦИЯ ДАННЫХ ДЛЯ ВЫБРАННОГО РЕЙСА ---
$selected_flight_details = null;
$all_simulated_flights = []; 

$all_simulated_flights['fake_flight_1'] = [
    'id' => 'fake_flight_1',
    'airline_name' => 'AirGO Simulated',
    'airline_iata_code' => 'AG',
    'segments' => [
        [
            'flight_number' => '101',
            'departure_iata' => strtoupper(substr($origin_checkout, 0, 3)),
            'departure_city' => $origin_checkout,
            'departure_datetime_full' => $departure_date_checkout . 'T09:30:00',
            'arrival_iata' => strtoupper(substr($destination_checkout, 0, 3)),
            'arrival_city' => $destination_checkout,
            'arrival_datetime_full' => $departure_date_checkout . 'T12:00:00', 
            'booking_class' => 'Y', 'aircraft_type' => 'B737', 'duration_minutes' => 150,
        ]
    ],
    'stops' => 0,
    'total_duration_minutes' => 150,
    'price' => 7500.00, 
    'currency' => 'RUB'
];
$all_simulated_flights['fake_flight_2'] = [
    'id' => 'fake_flight_2',
    'airline_name' => 'Connect Airways (Simulated)',
    'airline_iata_code' => 'CA',
    'segments' => [
        [
            'flight_number' => '202',
            'departure_iata' => strtoupper(substr($origin_checkout, 0, 3)),
            'departure_city' => $origin_checkout,
            'departure_datetime_full' => $departure_date_checkout . 'T14:00:00',
            'arrival_iata' => 'HUB', 
            'arrival_city' => 'Город-Хаб',
            'arrival_datetime_full' => $departure_date_checkout . 'T16:00:00',
            'booking_class' => 'M', 'aircraft_type' => 'A320', 'duration_minutes' => 120,
        ],
        [
            'flight_number' => '203',
            'departure_iata' => 'HUB',
            'departure_city' => 'Город-Хаб',
            'departure_datetime_full' => $departure_date_checkout . 'T17:30:00', 
            'arrival_iata' => strtoupper(substr($destination_checkout, 0, 3)),
            'arrival_city' => $destination_checkout,
            'arrival_datetime_full' => $departure_date_checkout . 'T19:00:00',
            'booking_class' => 'M', 'aircraft_type' => 'A320', 'duration_minutes' => 90,
        ]
    ],
    'stops' => 1,
    'total_duration_minutes' => (120 + 90 + 90),
    'price' => 6800.00,
    'currency' => 'RUB'
];

if (isset($all_simulated_flights[$flight_id_selected])) {
    $selected_flight_details = $all_simulated_flights[$flight_id_selected];
} else {
    $_SESSION['general_message'] = "Ошибка: выбранный вариант перелета не найден.";
    $_SESSION['general_message_type'] = "error";
    header("Location: index.php"); 
    exit();
}

$num_adults = 0;
if (preg_match('/(\d+)a/i', $passengers_param_checkout, $matches_adults)) {
    $num_adults = (int)$matches_adults[1];
}
if ($num_adults == 0) $num_adults = 1; 
$total_passengers_for_form = $num_adults; 
$total_price_checkout = $selected_flight_details['price'] * $num_adults; 

$form_data_checkout = $_SESSION['checkout_form_data'] ?? [];
unset($_SESSION['checkout_form_data']);

function format_checkout_datetime($datetime_str, $format = 'H:i, d M Y') {
    if (!$datetime_str) return '-';
    try { $date = new DateTime($datetime_str); return $date->format($format); }
    catch (Exception $e) { return $datetime_str; }
}
$page_title_checkout = "Оформление бронирования - AirGO";
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title_checkout; ?></title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/checkout-style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <header class="header">
        <div class="container header__container">
            <?php $header_logo_link_co = isset($_SESSION['user_id']) ? "index-log.php" : "index.php"; ?>
            <a href="<?php echo $header_logo_link_co; ?>" class="header__logo">AirGO</a>
            <nav class="header__nav">
                <ul>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <li><a href="my-bookings-log.php">Мои бронирования</a></li>
                        <li><a href="mailto:borovetf@gmail.com">Помощь</a></li>
                        <li><a href="profile.php">Профиль</a></li>
                    <?php else: ?>
                        <li><a href="login.php?redirect=my-bookings-log.php">Мои бронирования</a></li>
                        <li><a href="mailto:borovetf@gmail.com">Помощь</a></li>
                        <li><a href="login.php">Войти</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    </header>

    <main class="main-checkout">
        <div class="container">
            <h1 class="page-title-checkout">Оформление бронирования</h1>

            <?php if (isset($_SESSION['checkout_error'])): ?>
                <div class="checkout-message error">
                    <?php echo htmlspecialchars($_SESSION['checkout_error']); unset($_SESSION['checkout_error']);?>
                </div>
            <?php endif; ?>

            <div class="checkout-grid">
                <section class="checkout-flight-details">
                    <h2><i class="fas fa-plane-circle-check"></i> Ваш выбор</h2>
                    <div class="selected-flight-summary">
                        <p><strong>Авиакомпания:</strong> <?php echo htmlspecialchars($selected_flight_details['airline_name']); ?></p>
                        <?php foreach ($selected_flight_details['segments'] as $idx => $segment_checkout): ?>
                            <div class="segment-summary">
                                <strong>Сегмент <?php echo $idx + 1; ?>:</strong> 
                                <?php echo htmlspecialchars($segment_checkout['departure_city'] . ' (' . $segment_checkout['departure_iata'] . ')'); ?>
                                <i class="fas fa-long-arrow-alt-right"></i>
                                <?php echo htmlspecialchars($segment_checkout['arrival_city'] . ' (' . $segment_checkout['arrival_iata'] . ')'); ?>
                                <br>
                                <small>
                                    Вылет: <?php echo format_checkout_datetime($segment_checkout['departure_datetime_full']); ?> | 
                                    Прилет: <?php echo format_checkout_datetime($segment_checkout['arrival_datetime_full']); ?>
                                </small>
                            </div>
                        <?php endforeach; ?>
                        <p class="total-price-summary"><strong>Итого к оплате:</strong> <?php echo number_format($total_price_checkout, 2, '.', ' '); ?> <?php echo htmlspecialchars($selected_flight_details['currency']); ?></p>
                    </div>
                </section>

                <section class="checkout-form-section">
                    <form action="handle_checkout.php" method="POST" id="checkoutForm">
                        <!-- Скрытые поля для передачи данных о рейсе и параметрах поиска обработчику -->
                        <input type="hidden" name="selected_flight_id" value="<?php echo htmlspecialchars($flight_id_selected); ?>">
                        <input type="hidden" name="num_adults" value="<?php echo $num_adults; ?>">
                        <input type="hidden" name="total_price_final" value="<?php echo $total_price_checkout; ?>">
                        <input type="hidden" name="currency_final" value="<?php echo htmlspecialchars($selected_flight_details['currency']); ?>">
                        
                        <!-- Добавленные скрытые поля для восстановления GET параметров и симуляции рейса -->
                        <input type="hidden" name="original_get_query_string" value="<?php echo htmlspecialchars($_SERVER['QUERY_STRING'] ?? ''); ?>">
                        <input type="hidden" name="hidden_origin" value="<?php echo htmlspecialchars($origin_checkout ?? ''); ?>">
                        <input type="hidden" name="hidden_destination" value="<?php echo htmlspecialchars($destination_checkout ?? ''); ?>">
                        <input type="hidden" name="hidden_departure_date" value="<?php echo htmlspecialchars($departure_date_checkout ?? ''); ?>">
                        <input type="hidden" name="hidden_pax" value="<?php echo htmlspecialchars($passengers_param_checkout ?? ''); ?>">
                        <!-- Добавьте другие параметры, если они нужны в handle_checkout.php для симуляции рейса -->


                        <h2><i class="fas fa-user-edit"></i> Данные пассажиров</h2>
                        <?php for ($i = 0; $i < $total_passengers_for_form; $i++): ?>
                        <fieldset class="passenger-fieldset">
                            <legend>Пассажир <?php echo $i + 1; ?> (Взрослый)</legend>
                            <div class="form-row">
                                <div class="form-group half-width">
                                    <label for="pax_first_name_<?php echo $i; ?>">Имя (как в загранпаспорте)*</label>
                                    <input type="text" id="pax_first_name_<?php echo $i; ?>" name="passengers[<?php echo $i; ?>][first_name]" 
                                           value="<?php echo htmlspecialchars($form_data_checkout['passengers'][$i]['first_name'] ?? ''); ?>"
                                           pattern="[A-Za-z\s-]+" title="Только латинские буквы, пробелы, дефисы" required>
                                </div>
                                <div class="form-group half-width">
                                    <label for="pax_last_name_<?php echo $i; ?>">Фамилия (как в загранпаспорте)*</label>
                                    <input type="text" id="pax_last_name_<?php echo $i; ?>" name="passengers[<?php echo $i; ?>][last_name]" 
                                           value="<?php echo htmlspecialchars($form_data_checkout['passengers'][$i]['last_name'] ?? ''); ?>"
                                           pattern="[A-Za-z\s-]+" title="Только латинские буквы, пробелы, дефисы" required>
                                </div>
                            </div>
                             <div class="form-row">
                                <div class="form-group half-width">
                                    <label for="pax_date_of_birth_<?php echo $i; ?>">Дата рождения*</label>
                                    <input type="date" id="pax_date_of_birth_<?php echo $i; ?>" name="passengers[<?php echo $i; ?>][date_of_birth]" 
                                           value="<?php echo htmlspecialchars($form_data_checkout['passengers'][$i]['date_of_birth'] ?? ''); ?>" required>
                                </div>
                                 <div class="form-group half-width">
                                    <label for="pax_gender_<?php echo $i; ?>">Пол*</label>
                                    <select id="pax_gender_<?php echo $i; ?>" name="passengers[<?php echo $i; ?>][gender]" required>
                                        <option value="">Выберите...</option>
                                        <option value="male" <?php echo (isset($form_data_checkout['passengers'][$i]['gender']) && $form_data_checkout['passengers'][$i]['gender'] == 'male') ? 'selected' : ''; ?>>Мужской</option>
                                        <option value="female" <?php echo (isset($form_data_checkout['passengers'][$i]['gender']) && $form_data_checkout['passengers'][$i]['gender'] == 'female') ? 'selected' : ''; ?>>Женский</option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group half-width">
                                    <label for="pax_document_type_<?php echo $i; ?>">Тип документа*</label>
                                    <select id="pax_document_type_<?php echo $i; ?>" name="passengers[<?php echo $i; ?>][document_type]" required>
                                        <option value="passport_intl" <?php echo (isset($form_data_checkout['passengers'][$i]['document_type']) && $form_data_checkout['passengers'][$i]['document_type'] == 'passport_intl') ? 'selected' : ''; ?>>Загранпаспорт</option>
                                    </select>
                                </div>
                                <div class="form-group half-width">
                                    <label for="pax_document_number_<?php echo $i; ?>">Номер документа*</label>
                                    <input type="text" id="pax_document_number_<?php echo $i; ?>" name="passengers[<?php echo $i; ?>][document_number]" 
                                           value="<?php echo htmlspecialchars($form_data_checkout['passengers'][$i]['document_number'] ?? ''); ?>" required>
                                </div>
                            </div>
                             <div class="form-row">
                                 <div class="form-group half-width">
                                    <label for="pax_nationality_country_code_<?php echo $i; ?>">Гражданство (код страны, 2 буквы)*</label>
                                    <input type="text" id="pax_nationality_country_code_<?php echo $i; ?>" name="passengers[<?php echo $i; ?>][nationality_country_code]" 
                                           value="<?php echo htmlspecialchars($form_data_checkout['passengers'][$i]['nationality_country_code'] ?? 'RU'); ?>"
                                           maxlength="2" pattern="[A-Za-z]{2}" title="2 латинские буквы кода страны (напр. RU, US)" required>
                                </div>
                                <div class="form-group half-width">
                                    <label for="pax_document_expiry_date_<?php echo $i; ?>">Срок действия документа (если есть)</label>
                                    <input type="date" id="pax_document_expiry_date_<?php echo $i; ?>" 
                                           value="<?php echo htmlspecialchars($form_data_checkout['passengers'][$i]['document_expiry_date'] ?? ''); ?>"
                                           name="passengers[<?php echo $i; ?>][document_expiry_date]">
                                </div>
                            </div>
                             <input type="hidden" name="passengers[<?php echo $i; ?>][passenger_type]" value="adult">
                        </fieldset>
                        <?php endfor; ?>

                        <h2><i class="fas fa-address-card"></i> Контактные данные покупателя</h2>
                        <fieldset>
                             <div class="form-group">
                                <label for="contact_email">Email (для билетов и уведомлений)*</label>
                                <input type="email" id="contact_email" name="contact_email" 
                                       value="<?php echo htmlspecialchars($form_data_checkout['contact_email'] ?? ($_SESSION['user_email'] ?? '')); ?>" 
                                       placeholder="user@example.com" required>
                            </div>
                            <div class="form-group">
                                <label for="contact_phone">Телефон*</label>
                                <input type="tel" id="contact_phone" name="contact_phone" 
                                       value="<?php echo htmlspecialchars($form_data_checkout['contact_phone'] ?? ''); ?>" 
                                       placeholder="+7 XXX XXX XX XX" required>
                            </div>
                        </fieldset>
                        
                        <div class="checkout-actions">
                            <p class="terms-agreement">Нажимая "Забронировать", вы соглашаетесь с <a href="/terms.html" target="_blank">Условиями использования</a> и <a href="/privacy.html" target="_blank">Политикой конфиденциальности</a>.</p>
                            <button type="submit" class="btn btn--primary btn--large-checkout"><i class="fas fa-check-circle"></i> Забронировать и оплатить (Симуляция)</button>
                        </div>
                    </form>
                </section>
            </div>
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
    <script>
        // JS может понадобиться для маски ввода телефона или дополнительной валидации на клиенте
    </script>
</body>
</html>