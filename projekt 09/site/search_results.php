<?php
// search_results.php
if (session_status() == PHP_SESSION_NONE) session_start();
require_once 'db.php'; // Для возможных запросов к справочникам в будущем

// Получаем параметры поиска из GET
$origin_query = isset($_GET['origin']) ? trim(htmlspecialchars($_GET['origin'])) : null;
$destination_query = isset($_GET['destination']) ? trim(htmlspecialchars($_GET['destination'])) : null;
$departure_date_query = isset($_GET['departure_date']) ? trim(htmlspecialchars($_GET['departure_date'])) : null;
$return_date_query = isset($_GET['return_date']) ? trim(htmlspecialchars($_GET['return_date'])) : null;
$trip_type_query = isset($_GET['trip_type']) ? trim(htmlspecialchars($_GET['trip_type'])) : 'round-trip';
$passengers_query = isset($_GET['passengers']) ? trim(htmlspecialchars($_GET['passengers'])) : '1a'; // Например, "1a"
$cabin_class_query = isset($_GET['cabin_class']) ? trim(htmlspecialchars($_GET['cabin_class'])) : 'economy';

$page_title = "Результаты поиска: " . ($origin_query ?: "Откуда") . " - " . ($destination_query ?: "Куда");

// --- СИМУЛЯЦИЯ НАЙДЕННЫХ РЕЙСОВ ---
// В реальном приложении здесь был бы запрос к API GDS/агрегатора
// Мы создадим несколько фейковых вариантов
$simulated_flights = [];

if ($origin_query && $destination_query && $departure_date_query) {
    // Вариант 1: Прямой рейс
    $simulated_flights[] = [
        'id' => 'fake_flight_1', // Уникальный ID для этого варианта
        'airline_name' => 'AirGO Simulated',
        'airline_logo' => 'img/logo-placeholder.png', // Заглушка для лого
        'segments' => [
            [
                'flight_number' => 'AG101',
                'departure_iata' => strtoupper(substr($origin_query, 0, 3)), // Берем первые 3 буквы или реальный IATA, если есть
                'departure_city' => $origin_query,
                'departure_datetime' => $departure_date_query . 'T09:30:00',
                'arrival_iata' => strtoupper(substr($destination_query, 0, 3)),
                'arrival_city' => $destination_query,
                'arrival_datetime' => $departure_date_query . 'T12:00:00', // Предположим 2.5 часа полета
                'duration_minutes' => 150,
            ]
        ],
        'stops' => 0,
        'total_duration_minutes' => 150,
        'price' => 7500.00,
        'currency' => 'RUB'
    ];

    // Вариант 2: Рейс с одной пересадкой
    $simulated_flights[] = [
        'id' => 'fake_flight_2',
        'airline_name' => 'Connect Airways (Simulated)',
        'airline_logo' => 'img/logo-placeholder.png',
        'segments' => [
            [
                'flight_number' => 'CA202',
                'departure_iata' => strtoupper(substr($origin_query, 0, 3)),
                'departure_city' => $origin_query,
                'departure_datetime' => $departure_date_query . 'T14:00:00',
                'arrival_iata' => 'HUB', // Фейковый пересадочный узел
                'arrival_city' => 'Город-Хаб',
                'arrival_datetime' => $departure_date_query . 'T16:00:00',
                'duration_minutes' => 120,
            ],
            [
                'flight_number' => 'CA203',
                'departure_iata' => 'HUB',
                'departure_city' => 'Город-Хаб',
                'departure_datetime' => $departure_date_query . 'T17:30:00', // 1.5 часа пересадка
                'arrival_iata' => strtoupper(substr($destination_query, 0, 3)),
                'arrival_city' => $destination_query,
                'arrival_datetime' => $departure_date_query . 'T19:00:00',
                'duration_minutes' => 90,
            ]
        ],
        'stops' => 1,
        'total_duration_minutes' => (120 + 90 + 90), // полеты + пересадка
        'price' => 6800.00,
        'currency' => 'RUB'
    ];
    
    // Если 'туда-обратно', нужно симулировать и обратные рейсы, 
    // но для простоты пока на странице оформления будем оформлять только "туда".
    // Логика для round-trip будет значительно сложнее.
}

function format_flight_datetime($datetime_str, $format = 'H:i, d M Y') {
    if (!$datetime_str) return '-';
    try { $date = new DateTime($datetime_str); return $date->format($format); }
    catch (Exception $e) { return $datetime_str; }
}
function format_duration($minutes) {
    return floor($minutes / 60) . 'ч ' . ($minutes % 60) . 'м';
}

?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?> - AirGO</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/search-results-style.css"> <!-- Новый CSS -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <header class="header">
        <div class="container header__container">
            <?php $header_logo_link_sr = isset($_SESSION['user_id']) ? "index-log.php" : "index.php"; ?>
            <a href="<?php echo $header_logo_link_sr; ?>" class="header__logo">AirGO</a>
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

    <main class="main-search-results">
        <div class="container">
            <div class="search-results-header">
                <h1>Результаты поиска</h1>
                <p class="search-query-display">
                    <i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($origin_query); ?> 
                    <i class="fas fa-long-arrow-alt-right"></i> <?php echo htmlspecialchars($destination_query); ?>
                    <br><i class="far fa-calendar-alt"></i> <?php echo format_flight_datetime($departure_date_query . 'T00:00', 'd M Y'); ?>
                    <?php if ($trip_type_query == 'round-trip' && $return_date_query): ?>
                        - <?php echo format_flight_datetime($return_date_query . 'T00:00', 'd M Y'); ?>
                    <?php endif; ?>
                    | <i class="fas fa-users"></i> <?php echo htmlspecialchars($passengers_query); // TODO: Расшифровать кол-во пассажиров ?>
                    | <i class="fas fa-couch"></i> <?php echo ucfirst(htmlspecialchars($cabin_class_query)); ?>
                </p>
                <a href="<?php echo $header_logo_link_sr; ?>#mainSearchForm" class="btn btn--outline btn--small change-search-btn">
                    <i class="fas fa-edit"></i> Изменить поиск
                </a>
            </div>

            <?php if (empty($simulated_flights)): ?>
                <div class="no-flights-found">
                    <i class="fas fa-plane-slash fa-3x"></i>
                    <p>К сожалению, по вашему запросу рейсов не найдено.</p>
                    <p>Попробуйте изменить параметры поиска или даты.</p>
                </div>
            <?php else: ?>
                <div class="flight-results-list">
                    <?php foreach ($simulated_flights as $flight_option): ?>
                    <article class="flight-option-card">
                        <div class="flight-option__main-info">
                            <div class="airline-info">
                                <?php /* <img src="<?php echo htmlspecialchars($flight_option['airline_logo']); ?>" alt="<?php echo htmlspecialchars($flight_option['airline_name']); ?>" class="airline-logo-small"> */ ?>
                                <i class="fas fa-plane-departure airline-logo-placeholder-small"></i>
                                <span class="airline-name"><?php echo htmlspecialchars($flight_option['airline_name']); ?></span>
                            </div>
                            <div class="flight-option__segments">
                                <?php foreach ($flight_option['segments'] as $s_idx => $segment): ?>
                                <div class="segment-leg">
                                    <div class="time-location">
                                        <span class="time"><?php echo format_flight_datetime($segment['departure_datetime'], 'H:i'); ?></span>
                                        <span class="iata-code"><?php echo htmlspecialchars($segment['departure_iata']); ?></span>
                                        <span class="city-name-small"><?php echo htmlspecialchars($segment['departure_city']); ?></span>
                                    </div>
                                    <div class="flight-duration-arrow">
                                        <span class="duration-leg"><?php echo format_duration($segment['duration_minutes']); ?></span>
                                        <div class="arrow-line"></div>
                                        <span class="stops-info-leg">
                                            <?php echo ($s_idx == 0 && $flight_option['stops'] == 0) ? 'Прямой рейс' : (($s_idx < count($flight_option['segments']) -1 ) ? 'Пересадка' : '');?>
                                        </span>
                                    </div>
                                    <div class="time-location">
                                        <span class="time"><?php echo format_flight_datetime($segment['arrival_datetime'], 'H:i'); ?></span>
                                        <span class="iata-code"><?php echo htmlspecialchars($segment['arrival_iata']); ?></span>
                                        <span class="city-name-small"><?php echo htmlspecialchars($segment['arrival_city']); ?></span>
                                    </div>
                                </div>
                                <?php if ($s_idx < count($flight_option['segments']) - 1): 
                                        // Симуляция времени пересадки
                                        try {
                                            $arr_dt = new DateTime($segment['arrival_datetime']);
                                            $dep_next_dt = new DateTime($flight_option['segments'][$s_idx+1]['departure_datetime']);
                                            $layover_s = $dep_next_dt->getTimestamp() - $arr_dt->getTimestamp();
                                            $layover_minutes = floor($layover_s / 60);
                                        } catch (Exception $e) {$layover_minutes = 0;}
                                ?>
                                    <div class="layover-details-results">
                                        <i class="far fa-clock"></i> Пересадка в <?php echo htmlspecialchars($segment['arrival_city']); ?> (<?php echo htmlspecialchars($segment['arrival_iata']); ?>) - <?php echo format_duration($layover_minutes); ?>
                                    </div>
                                <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <div class="flight-option__price-action">
                            <div class="price-info">
                                <span class="price"><?php echo htmlspecialchars(number_format($flight_option['price'], 0, '.', ' ')); ?> <?php echo htmlspecialchars($flight_option['currency']); ?></span>
                                <span class="price-per-person">за 1 взрослого</span>
                            </div>
                            <!-- Передаем ID "фейкового" рейса на страницу оформления -->
                            <a href="checkout.php?flight_id=<?php echo htmlspecialchars($flight_option['id']); ?>&dep_date=<?php echo urlencode($departure_date_query); ?>&ret_date=<?php echo urlencode($return_date_query); ?>&trip_type=<?php echo urlencode($trip_type_query); ?>&pax=<?php echo urlencode($passengers_query); ?>&cabin=<?php echo urlencode($cabin_class_query); ?>&origin=<?php echo urlencode($origin_query);?>&destination=<?php echo urlencode($destination_query);?>" class="btn btn--primary btn--select-flight">Выбрать</a>
                        </div>
                    </article>
                    <?php endforeach; ?>
                </div>
                 <!-- Здесь можно добавить пагинацию для результатов поиска, если их много -->
            <?php endif; ?>
        </div>
    </main>

    <footer class="footer">
        <!-- ... (Ваш футер без изменений) ... -->
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
        // Опциональный JS, если понадобится (например, для фильтров на клиенте, если их будет много)
    </script>
</body>
</html>