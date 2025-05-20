<?php
// index-log.php (АВТОРИЗОВАННЫЙ)
if (session_status() == PHP_SESSION_NONE) session_start();

// Проверка, залогинен ли пользователь. Если нет, перенаправляем на login.php
if (!isset($_SESSION['user_id'])) {
    $_SESSION['login_error'] = "Пожалуйста, войдите, чтобы продолжить."; // Сообщение для страницы входа
    header("Location: login.php?redirect=" . urlencode("index-log.php" . ($_SERVER['QUERY_STRING'] ? '?' . $_SERVER['QUERY_STRING'] : '') ));
    exit();
}
require_once 'db.php'; 

// --- Получение спецпредложений для отображения (выбираем image_path) ---
$stmt_promo = $conn->prepare(
    "SELECT offer_id, title, subtitle, price_from, currency_code, image_path 
     FROM special_offers 
     WHERE is_active = TRUE 
     ORDER BY sort_order ASC, offer_id DESC 
     LIMIT 6" 
);
$promo_offers = [];
if ($stmt_promo) {
    if (!$stmt_promo->execute()) {
        error_log("Error executing promo offers query (index-log.php): " . $stmt_promo->error);
    } else {
        $result_promo = $stmt_promo->get_result();
        while ($row = $result_promo->fetch_assoc()) {
            $promo_offers[] = $row;
        }
    }
    $stmt_promo->close();
} else {
    error_log("Error preparing promo offers query (index-log.php): " . $conn->error);
}

// --- Получаем значения для автозаполнения формы поиска из GET-параметров ---
$search_origin_value = isset($_GET['origin']) ? htmlspecialchars(trim($_GET['origin'])) : '';
$search_destination_value = isset($_GET['destination']) ? htmlspecialchars(trim($_GET['destination'])) : '';
$search_trip_type_value = isset($_GET['trip_type']) && in_array(trim($_GET['trip_type']), ['round-trip', 'one-way']) ? htmlspecialchars(trim($_GET['trip_type'])) : 'round-trip';
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AirGO - Поиск Дешевых Авиабилетов</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
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
                    <!-- Кнопка "Выход" обычно на странице профиля или в выпадающем меню пользователя -->
                </ul>
            </nav>
        </div>
    </header>

    <main>
        <section class="hero">
            <div class="container hero__container">
                <h1>Найдите лучшие предложения на авиабилеты</h1>
                <p class="hero__subtitle">Путешествуйте легко с AirGO</p>

                <form action="search_results.php" method="GET" class="search-form" id="mainSearchFormLog"> <!-- Уникальный ID, если нужно -->
                    <div class="search-form__options">
                        <label>
                            <input type="radio" name="trip_type" value="round-trip" <?php echo ($search_trip_type_value == 'round-trip') ? 'checked' : ''; ?>> Туда-Обратно
                        </label>
                        <label>
                            <input type="radio" name="trip_type" value="one-way" <?php echo ($search_trip_type_value == 'one-way') ? 'checked' : ''; ?>> В одну сторону
                        </label>
                    </div>

                    <div class="search-form__fields">
                        <div class="form-group">
                            <label for="origin-log">Откуда</label> <!-- Уникальные ID для полей, если JS их обрабатывает отдельно -->
                            <input type="text" id="origin-log" name="origin" placeholder="Город или аэропорт" 
                                   value="<?php echo $search_origin_value; ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="destination-log">Куда</label>
                            <input type="text" id="destination-log" name="destination" placeholder="Город или аэропорт"
                                   value="<?php echo $search_destination_value; ?>" required>
                        </div>
                        <div class="form-group form-group--date">
                            <label for="departure-date-log">Туда</label>
                            <input type="date" id="departure-date-log" name="departure_date" required>
                        </div>
                        <div class="form-group form-group--date">
                            <label for="return-date-log">Обратно</label>
                            <input type="date" id="return-date-log" name="return_date" 
                                   <?php echo ($search_trip_type_value == 'one-way') ? 'disabled' : ''; ?>>
                        </div>
                        <div class="form-group form-group--passengers">
                            <label for="passengers-log">Пассажиры</label>
                            <select id="passengers-log" name="passengers">
                                <option value="1a">1 Взрослый</option>
                                <option value="2a">2 Взрослых</option>
                                <option value="1a1c">1 Взрослый, 1 Ребенок</option>
                            </select>
                        </div>
                         <div class="form-group form-group--cabin">
                            <label for="cabin-class-log">Класс</label>
                            <select id="cabin-class-log" name="cabin_class">
                                <option value="economy">Эконом</option>
                                <option value="business">Бизнес</option>
                            </select>
                        </div>
                    </div>
                    <button type="submit" class="search-form__button">Найти билеты</button>
                </form>
            </div>
        </section>

        <?php if (!empty($promo_offers)): ?>
        <section class="promo-section">
            <div class="container">
                <h2>Специальные предложения</h2>
                <div class="promo-cards">
                    <?php foreach ($promo_offers as $p_offer): ?>
                        <div class="promo-card">
                            <div class="promo-card__placeholder">
                                <?php if (!empty($p_offer['image_path']) && file_exists(trim($p_offer['image_path'], '/'))): 
                                    $image_filename = basename($p_offer['image_path']);
                                    // Предполагаем, что $p_offer['image_path'] уже 'uploads/special_offers/...'
                                    // или нужно сформировать полный веб-путь
                                    $image_web_path = htmlspecialchars($p_offer['image_path']);
                                    // Если папка uploads не в корне, а например css/style.css и uploads/ 
                                    // на одном уровне, и index-log.php в корне:
                                    // $image_web_path = htmlspecialchars($p_offer['image_path']); 
                                ?>
                                    <img src="<?php echo $image_web_path; ?>" alt="<?php echo htmlspecialchars($p_offer['title']); ?>" class="promo-card__image">
                                <?php else: ?>
                                    <i class="fas fa-tag promo-icon-placeholder"></i> 
                                <?php endif; ?>
                            </div>
                            <div class="promo-card__content">
                                <div class="promo-card__text-block">
                                    <h3><?php echo htmlspecialchars($p_offer['title']); ?></h3>
                                    <?php if (!empty($p_offer['subtitle'])): ?>
                                        <p class="promo-card__subtitle"><?php echo htmlspecialchars($p_offer['subtitle']); ?></p>
                                    <?php endif; ?>
                                    <p class="promo-card__price"><?php echo 'от ' . htmlspecialchars(number_format((float)$p_offer['price_from'], 0, '.', ' ')) . ' ' . htmlspecialchars($p_offer['currency_code']); ?></p>
                                </div>
                                <a href="special-offer-details.php?id=<?php echo $p_offer['offer_id']; ?>" class="btn btn--secondary">Подробнее</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
        <?php else: ?>
        <section class="promo-section">
            <div class="container">
                 <h2>Специальные предложения</h2>
                 <p style="text-align:center; color: var(--dark-gray);">Актуальных спецпредложений пока нет. Загляните позже!</p>
            </div>
        </section>
        <?php endif; ?>

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
                © <?php echo date("Y"); ?> AirGO. Все права защищены.
            </div>
        </div>
    </footer>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const tripTypeRadiosLog = document.querySelectorAll('#mainSearchFormLog input[name="trip_type"]'); // Используем ID формы
            const returnDateInputLog = document.getElementById('return-date-log');
            
            function toggleReturnDateLog() {
                const selectedTripTypeLog = document.querySelector('#mainSearchFormLog input[name="trip_type"]:checked').value;
                if (selectedTripTypeLog === 'one-way') {
                    returnDateInputLog.disabled = true;
                    returnDateInputLog.value = ''; 
                    returnDateInputLog.removeAttribute('required');
                } else {
                    returnDateInputLog.disabled = false;
                    // returnDateInputLog.setAttribute('required', 'required');
                }
            }

            tripTypeRadiosLog.forEach(radio => {
                radio.addEventListener('change', toggleReturnDateLog);
            });
            toggleReturnDateLog();

            const urlParamsLog = new URLSearchParams(window.location.search);
            if (urlParamsLog.has('origin') || urlParamsLog.has('destination')) {
                 const searchFormElementLog = document.querySelector('#mainSearchFormLog');
                 if (searchFormElementLog) {
                     searchFormElementLog.scrollIntoView({ behavior: 'smooth', block: 'center' });
                 }
            }
        });
    </script>
</body>
</html>