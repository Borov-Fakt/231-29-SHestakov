<?php
// special-offer-details.php
if (session_status() == PHP_SESSION_NONE) session_start();
require_once 'db.php'; // Убедитесь, что путь верный

$offer_id = null;
$offer = null;

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $offer_id = (int)$_GET['id'];
} else {
    $_SESSION['general_message'] = "Запрашиваемое предложение не найдено или указан неверный идентификатор.";
    $_SESSION['general_message_type'] = "error";
    header("Location: index.php");
    exit();
}

$stmt_offer_details = $conn->prepare("SELECT offer_id, title, subtitle, image_path, 
                               details_page_title, details_hero_subtitle, 
                               details_main_description, details_what_to_see, 
                               details_direction, details_price_info, details_departure_from, 
                               details_travel_period, details_flight_class, 
                               search_destination_iata, search_origin_iata, search_trip_type 
                        FROM special_offers 
                        WHERE offer_id = ? AND is_active = TRUE");

if (!$stmt_offer_details) {
    error_log("Error preparing special offer query (details): " . $conn->error);
    $_SESSION['general_message'] = "Произошла ошибка при загрузке предложения (DBP). Попробуйте позже.";
    $_SESSION['general_message_type'] = "error";
    header("Location: index.php"); 
    exit();
}

$stmt_offer_details->bind_param("i", $offer_id);

if (!$stmt_offer_details->execute()) {
    error_log("Error executing special offer query for ID " . $offer_id . " (details): " . $stmt_offer_details->error);
    $_SESSION['general_message'] = "Не удалось загрузить данные предложения (DBE).";
    $_SESSION['general_message_type'] = "error";
    header("Location: index.php");
    exit();
}

$result_offer_details = $stmt_offer_details->get_result();

if ($result_offer_details->num_rows === 1) {
    $offer = $result_offer_details->fetch_assoc();
} else {
    $_SESSION['general_message'] = "Спецпредложение (ID: ".$offer_id.") не найдено или больше неактивно.";
    $_SESSION['general_message_type'] = "info";
    header("Location: index.php"); 
    exit();
}
$stmt_offer_details->close();

$what_to_see_items = [];
if (!empty($offer['details_what_to_see'])) {
    $what_to_see_items = array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $offer['details_what_to_see'])));
}

$target_index_page_for_search = isset($_SESSION['user_id']) ? "index-log.php" : "index.php";
$search_params = [
    'trip_type' => $offer['search_trip_type'],
    'destination' => $offer['search_destination_iata']
];
if (!empty($offer['search_origin_iata'])) {
    $search_params['origin'] = $offer['search_origin_iata'];
}
$search_url = $target_index_page_for_search . "?" . http_build_query($search_params); 

$page_title = htmlspecialchars($offer['details_page_title'] ?: $offer['title']) . " - Спецпредложение - AirGO";
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link rel="stylesheet" href="css/style.css"> 
    <link rel="stylesheet" href="css/offer-style.css"> 
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <header class="header">
        <div class="container header__container">
            <?php 
            $header_logo_link = isset($_SESSION['user_id']) ? "index-log.php" : "index.php";
            ?>
            <a href="<?php echo $header_logo_link; ?>" class="header__logo">AirGO</a>
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

    <main class="offer-page">
        <?php
            $hero_background_css = '';
            if (!empty($offer['image_path']) && file_exists(trim($offer['image_path'], '/'))) {
                $image_filename_hero = basename($offer['image_path']);
                $image_web_path_hero = 'uploads/special_offers/' . $image_filename_hero; 
                $hero_background_css = 'background-image: url(\'' . htmlspecialchars($image_web_path_hero) . '\'); background-size: cover; background-position: center;';
            } else {
                $hero_background_css = 'background: linear-gradient(rgba(40, 167, 69, 0.75), rgba(30, 126, 52, 0.85));';
            }
        ?>
        <section class="offer-hero" style="<?php echo $hero_background_css; ?>">
            <div class="container offer-hero__container">
                <h1><?php echo htmlspecialchars($offer['details_page_title'] ?: $offer['title']); ?></h1>
                <?php if(!empty($offer['details_hero_subtitle'])): ?>
                <p class="offer-hero__subtitle"><?php echo htmlspecialchars($offer['details_hero_subtitle']); ?></p>
                <?php endif; ?>
                 <!-- БЛОК С ИКОНКОЙ УДАЛЕН -->
            </div>
        </section>

        <!-- Остальной HTML-код для .offer-details, .other-offers-section и footer остается БЕЗ ИЗМЕНЕНИЙ -->
        <section class="offer-details">
            <div class="container offer-details__container">
                <div class="offer-description">
                    <h2><?php echo htmlspecialchars($offer['title']); ?> <small>- Погрузитесь в детали</small></h2>
                    <?php echo nl2br(htmlspecialchars($offer['details_main_description'])); ?>
                    <?php if (!empty($what_to_see_items)): ?>
                    <h3 class="what-to-see-title"><i class="fas fa-camera-retro"></i> Что посмотреть:</h3>
                    <ul class="what-to-see-list">
                        <?php foreach ($what_to_see_items as $item): ?>
                            <li><i class="fas fa-check-circle icon-list-item"></i> <?php echo htmlspecialchars($item); ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <?php endif; ?>
                </div>
                <aside class="offer-key-info">
                    <div class="key-info-card">
                        <h2><i class="fas fa-info-circle"></i> Детали предложения</h2>
                        <table class="key-info-table">
                            <tbody>
                                <?php if(!empty($offer['details_direction'])): ?>
                                <tr><th><i class="fas fa-map-marker-alt icon-table"></i> Направление:</th><td><?php echo htmlspecialchars($offer['details_direction']); ?></td></tr>
                                <?php endif; ?>
                                <?php if(!empty($offer['details_price_info'])): ?>
                                <tr><th><i class="fas fa-tags icon-table"></i> Цена:</th><td><strong><?php echo htmlspecialchars($offer['details_price_info']); ?></strong></td></tr>
                                <?php endif; ?>
                                <?php if(!empty($offer['details_departure_from'])): ?>
                                <tr><th><i class="fas fa-plane-departure icon-table"></i> Вылет из:</th><td><?php echo htmlspecialchars($offer['details_departure_from']); ?></td></tr>
                                <?php endif; ?>
                                <?php if(!empty($offer['details_travel_period'])): ?>
                                <tr><th><i class="fas fa-calendar-alt icon-table"></i> Период:</th><td><?php echo htmlspecialchars($offer['details_travel_period']); ?></td></tr>
                                <?php endif; ?>
                                <?php if(!empty($offer['details_flight_class'])): ?>
                                 <tr><th><i class="fas fa-couch icon-table"></i> Класс:</th><td><?php echo htmlspecialchars($offer['details_flight_class']); ?></td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                        <div class="offer-cta">
                            <a href="<?php echo htmlspecialchars($search_url); ?>" class="btn btn--primary"><i class="fas fa-search"></i> Найти рейсы сейчас</a>
                        </div>
                    </div>
                     <?php if (!empty($offer['details_direction'])): ?>
                     <div class="key-info-tip">
                        <p><strong><i class="fas fa-lightbulb"></i> Совет:</strong> Цены на авиабилеты в <?php echo strtok(htmlspecialchars($offer['details_direction']), ','); ?> могут меняться. Бронируйте заранее!</p>
                     </div>
                     <?php endif; ?>
                </aside>
            </div>
        </section>
        
        <?php
            $stmt_other_offers = $conn->prepare(
                "SELECT offer_id, title, price_from, currency_code, image_path 
                 FROM special_offers 
                 WHERE is_active = TRUE AND offer_id != ? 
                 ORDER BY RAND() 
                 LIMIT 3"
            );
            $other_offers_list = [];
            if($stmt_other_offers){
                $stmt_other_offers->bind_param("i", $offer_id); 
                if (!$stmt_other_offers->execute()) {
                    error_log("Error executing other offers query for ID " . $offer_id . ": " . $stmt_other_offers->error);
                } else {
                    $result_other_offers = $stmt_other_offers->get_result();
                    while($row_other = $result_other_offers->fetch_assoc()){
                        $other_offers_list[] = $row_other;
                    }
                }
                $stmt_other_offers->close();
            } else {
                 error_log("Error preparing other offers query: " . $conn->error);
            }
        ?>
        <?php if(!empty($other_offers_list)): ?>
        <section class="other-offers-section">
            <div class="container">
                <h2>Другие интересные предложения</h2>
                <div class="promo-cards">
                     <?php foreach ($other_offers_list as $other_o): ?>
                        <div class="promo-card">
                            <div class="promo-card__placeholder">
                                <?php if (!empty($other_o['image_path']) && file_exists(trim($other_o['image_path'], '/'))): 
                                    $other_image_filename = basename($other_o['image_path']);
                                    $other_image_web_path = 'uploads/special_offers/' . $other_image_filename;
                                ?>
                                    <img src="<?php echo htmlspecialchars($other_image_web_path); ?>" alt="<?php echo htmlspecialchars($other_o['title']); ?>" class="promo-card__image">
                                <?php else: ?>
                                    <i class="fas fa-compass promo-icon-placeholder"></i>
                                <?php endif; ?>
                            </div>
                            <div class="promo-card__content">
                                <div class="promo-card__text-block">
                                    <h3><?php echo htmlspecialchars($other_o['title']); ?></h3>
                                    <p class="promo-card__price"><?php echo 'от ' . htmlspecialchars(number_format((float)$other_o['price_from'], 0, '.', ' ')) . ' ' . htmlspecialchars($other_o['currency_code']); ?></p>
                                </div>
                                <a href="special-offer-details.php?id=<?php echo $other_o['offer_id']; ?>" class="btn btn--secondary">Подробнее</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
        <?php endif; ?>
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