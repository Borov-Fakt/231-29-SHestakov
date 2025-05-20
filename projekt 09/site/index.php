<?php
// index.php (НЕАВТОРИЗОВАННЫЙ)
if (session_status() == PHP_SESSION_NONE) session_start();
require_once 'db.php'; 

// --- Получение спецпредложений для отображения (теперь выбираем image_path) ---
$stmt_promo = $conn->prepare(
    "SELECT offer_id, title, subtitle, price_from, currency_code, image_path 
     FROM special_offers 
     WHERE is_active = TRUE 
     ORDER BY sort_order ASC, offer_id DESC 
     LIMIT 6"
);
// ... (остальной PHP код для $promo_offers и автозаполнения формы остается БЕЗ ИЗМЕНЕНИЙ) ...
$promo_offers = [];
if ($stmt_promo) {
    if (!$stmt_promo->execute()) {
        error_log("Error executing promo offers query (index.php): " . $stmt_promo->error);
    } else {
        $result_promo = $stmt_promo->get_result();
        while ($row = $result_promo->fetch_assoc()) {
            $promo_offers[] = $row;
        }
    }
    $stmt_promo->close();
} else {
    error_log("Error preparing promo offers query (index.php): " . $conn->error);
}

$search_origin_value = isset($_GET['origin']) ? htmlspecialchars(trim($_GET['origin'])) : '';
$search_destination_value = isset($_GET['destination']) ? htmlspecialchars(trim($_GET['destination'])) : '';
$search_trip_type_value = isset($_GET['trip_type']) && in_array(trim($_GET['trip_type']), ['round-trip', 'one-way']) ? htmlspecialchars(trim($_GET['trip_type'])) : 'round-trip';
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <!-- ... (ваш <head> без изменений) ... -->
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
    <!-- ... (ваш <header> и <section class="hero"> без изменений) ... -->
    <header class="header">
        <div class="container header__container">
            <a href="index.php" class="header__logo">AirGO</a> 
            <nav class="header__nav">
                <ul>
                    <li><a href="login.php?redirect=my-bookings-log.php">Мои бронирования</a></li> 
                    <li><a href="mailto:borovetf@gmail.com">Помощь</a></li>
                    <li><a href="login.php">Войти</a></li>
                </ul>
            </nav>
        </div>
    </header>
    <main>
        <section class="hero">
            <div class="container hero__container">
                <h1>Найдите лучшие предложения на авиабилеты</h1>
                <p class="hero__subtitle">Путешествуйте легко с AirGO</p>
                <form action="search_results.php" method="GET" class="search-form" id="mainSearchForm">
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
                            <label for="origin">Откуда</label>
                            <input type="text" id="origin" name="origin" placeholder="Город или аэропорт" 
                                   value="<?php echo $search_origin_value; ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="destination">Куда</label>
                            <input type="text" id="destination" name="destination" placeholder="Город или аэропорт"
                                   value="<?php echo $search_destination_value; ?>" required>
                        </div>
                        <div class="form-group form-group--date">
                            <label for="departure-date">Туда</label>
                            <input type="date" id="departure-date" name="departure_date" required>
                        </div>
                        <div class="form-group form-group--date">
                            <label for="return-date">Обратно</label>
                            <input type="date" id="return-date" name="return_date" 
                                   <?php echo ($search_trip_type_value == 'one-way') ? 'disabled' : ''; ?>>
                        </div>
                        <div class="form-group form-group--passengers">
                            <label for="passengers">Пассажиры</label>
                            <select id="passengers" name="passengers"> 
                                <option value="1A">1 Взрослый</option> 
                                <option value="2A">2 Взрослых</option>
                                <option value="1A1C">1 Взрослый, 1 Ребенок</option>
                                <option value="1A1I">1 Взрослый, 1 Младенец</option>
                                <option value="2A1C">2 Взрослых, 1 Ребенок</option>
                            </select>
                        </div>
                         <div class="form-group form-group--cabin">
                            <label for="cabin-class">Класс</label>
                            <select id="cabin-class" name="cabin_class"> 
                                <option value="ECONOMY">Эконом</option> 
                                <option value="BUSINESS">Бизнес</option>
                                <option value="FIRST">Первый</option>
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
                            <!-- ИЗМЕНЕНИЕ ЗДЕСЬ: Отображаем изображение или плейсхолдер -->
                            <div class="promo-card__placeholder">
                                <?php if (!empty($p_offer['image_path']) && file_exists(trim($p_offer['image_path'], '/'))): 
                                    // trim для удаления возможных слешей в начале, если они есть
                                    // pathinfo для защиты от Directory Traversal в имени файла (хотя путь из БД должен быть безопасным)
                                    $image_filename = basename($p_offer['image_path']);
                                    $image_web_path = 'uploads/special_offers/' . $image_filename; 
                                ?>
                                    <img src="<?php echo htmlspecialchars($image_web_path); ?>" alt="<?php echo htmlspecialchars($p_offer['title']); ?>" class="promo-card__image">
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
        <!-- ... (блок "нет спецпредложений" остается без изменений) ... -->
        <section class="promo-section">
            <div class="container">
                 <h2>Специальные предложения</h2>
                 <p style="text-align:center; color: var(--dark-gray);">Актуальных спецпредложений пока нет. Загляните позже!</p>
            </div>
        </section>
        <?php endif; ?>
    </main>
    <!-- ... (ваш <footer> и <script> без изменений) ... -->
    <footer class="footer">
        <div class="container footer__container">
            <div class="footer__links">
                <a href="about-us.php">О нас</a>
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
            const tripTypeRadios = document.querySelectorAll('input[name="trip_type"]');
            const returnDateInput = document.getElementById('return-date');
            const mainSearchForm = document.getElementById('mainSearchForm');

            function toggleReturnDate() {
                const selectedTripType = document.querySelector('input[name="trip_type"]:checked').value;
                if (selectedTripType === 'one-way') {
                    returnDateInput.disabled = true;
                    returnDateInput.value = ''; 
                    returnDateInput.removeAttribute('required');
                } else {
                    returnDateInput.disabled = false;
                    // returnDateInput.setAttribute('required', 'required');
                }
            }

            tripTypeRadios.forEach(radio => {
                radio.addEventListener('change', toggleReturnDate);
            });
            toggleReturnDate();

            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.has('origin') || urlParams.has('destination')) {
                 const searchFormElement = document.querySelector('.search-form');
                 if (searchFormElement) {
                     searchFormElement.scrollIntoView({ behavior: 'smooth', block: 'center' });
                 }
            }
        });
    </script>
</body>
</html>