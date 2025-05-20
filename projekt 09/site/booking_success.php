<?php
// booking_success.php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Проверяем, есть ли данные об успешном бронировании в сессии
// Эти переменные должны были быть установлены в handle_checkout.php
$pnr = $_SESSION['booking_success_pnr'] ?? null;
$email = $_SESSION['booking_success_email'] ?? null;

if (!$pnr || !$email) {
    // Если данных нет, возможно, пользователь попал сюда случайно
    // или произошла ошибка перед редиректом.
    // Перенаправляем на главную или на страницу "Мои бронирования", если залогинен.
    $redirect_url = isset($_SESSION['user_id']) ? "my-bookings-log.php" : "index.php";
    if (!isset($_SESSION['user_id']) && !isset($_SESSION['general_message'])) { // Добавим сообщение, если его еще нет
        $_SESSION['general_message'] = "Информация о бронировании не найдена.";
        $_SESSION['general_message_type'] = "info";
    }
    header("Location: " . $redirect_url);
    exit();
}

// Очищаем данные из сессии после отображения, чтобы они не показывались снова при обновлении
unset($_SESSION['booking_success_pnr']);
unset($_SESSION['booking_success_email']);
// Также можно очистить $_SESSION['checkout_form_data'], если она больше не нужна
unset($_SESSION['checkout_form_data']);

$page_title_success = "Бронирование успешно создано! - AirGO";

// Опционально: можно здесь еще раз сделать запрос к БД по PNR,
// чтобы получить самую свежую информацию для отображения,
// но для простоты мы используем данные из сессии.
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title_success; ?></title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/booking-success-style.css"> <!-- Новый CSS-файл -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <header class="header">
        <!-- Шапка как на других пользовательских страницах -->
        <div class="container header__container">
            <?php $header_logo_link_bs = isset($_SESSION['user_id']) ? "index-log.php" : "index.php"; ?>
            <a href="<?php echo $header_logo_link_bs; ?>" class="header__logo">AirGO</a>
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

    <main class="main-booking-success">
        <div class="container">
            <div class="success-confirmation-box">
                <div class="success-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <h1>Бронирование успешно оформлено!</h1>
                <p class="pnr-info">Ваш номер бронирования (PNR): <strong><?php echo htmlspecialchars($pnr); ?></strong></p>
                <p class="confirmation-email-info">
                    Подтверждение бронирования и детали вашего перелета были отправлены на электронную почту: 
                    <strong><?php echo htmlspecialchars($email); ?></strong>.
                </p>
                <p class="next-steps-info">
                    Пожалуйста, проверьте вашу почту (включая папку "Спам"). <br>
                    Вы также можете просмотреть детали этого бронирования в разделе 
                    <a href="my-bookings-log.php">"Мои бронирования"</a><?php echo isset($_SESSION['user_id']) ? '' : ' после входа в систему'; ?>.
                </p>
                <div class="success-actions">
                    <a href="<?php echo $header_logo_link_bs; ?>" class="btn btn--primary"><i class="fas fa-home"></i> На главную</a>
                    <?php if(isset($_SESSION['user_id'])): ?>
                    <a href="my-bookings-log.php" class="btn btn--secondary"><i class="fas fa-suitcase"></i> Мои бронирования</a>
                    <?php else: ?>
                    <a href="login.php?redirect=my-bookings-log.php" class="btn btn--secondary"><i class="fas fa-sign-in-alt"></i> Войти и посмотреть</a>
                    <?php endif; ?>
                </div>
            </div>
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
</body>
</html>