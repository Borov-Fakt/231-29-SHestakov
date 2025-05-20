<?php
// about-us.php

// 1. Запускаем сессию (если еще не запущена)
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// 2. Проверяем, существует ли идентификатор пользователя в сессии
//    Предполагаем, что при успешном входе вы устанавливаете $_SESSION['user_id']
if (!isset($_SESSION['user_id'])) {
    // Если пользователь не авторизован:
    // а) Сохраняем сообщение для страницы входа (опционально)
    $_SESSION['login_error'] = "Для доступа к этой странице необходимо войти в систему.";
    // б) Перенаправляем на страницу входа
    header("Location: login.php");
    // в) Прерываем выполнение текущего скрипта
    exit();
}

// Если пользователь авторизован, скрипт продолжает выполняться и отображает HTML ниже.
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>О нас - AirGO</title>
    <link rel="stylesheet" href="css/about-us-style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
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
                </ul>
            </nav>
        </div>
    </header>

    <main class="about-page-main-content">
        <section class="about-hero">
            <div class="container">
                <h1>О компании AirGO</h1>
                <p>Ваш надежный партнер в мире увлекательных путешествий</p>
            </div>
        </section>

        <section class="about-content">
            <div class="container">
                <div class="about-content__section">
                    <h2>Наша миссия</h2>
                    <p>В AirGO мы верим, что каждое путешествие начинается с мечты. Наша миссия – превратить эту мечту в реальность, сделав процесс поиска и бронирования авиабилетов максимально простым, прозрачным и выгодным. Мы стремимся вдохновлять людей на открытия, помогая им исследовать мир без лишних забот и по лучшим ценам.</p>
                    <p>Мы создаем интуитивно понятный сервис, который объединяет передовые технологии и человеческое отношение, чтобы каждый ваш полет начинался с приятного опыта.</p>
                </div>

                <div class="about-content__section">
                    <h2>Почему выбирают AirGO?</h2>
                    <ul>
                        <li><strong>Лучшие цены:</strong> Мы анализируем предложения от сотен авиакомпаний и агентств, чтобы вы могли найти самый выгодный вариант.</li>
                        <li><strong>Удобный интерфейс:</strong> Наш сайт разработан так, чтобы вы могли легко найти и забронировать билеты всего за несколько кликов.</li>
                        <li><strong>Широкий выбор:</strong> От лоукостеров до премиальных авиалиний – у нас есть рейсы на любой вкус и бюджет.</li>
                        <li><strong>Надежность и безопасность:</strong> Мы гарантируем защиту ваших личных данных и безопасность всех транзакций.</li>
                        <li><strong>Поддержка 24/7:</strong> Наша команда готова ответить на ваши вопросы и помочь в любой ситуации.</li>
                        <li><strong>Вдохновение:</strong> Откройте для себя новые направления и специальные предложения, которые мы регулярно готовим для вас.</li>
                    </ul>
                </div>

                <!-- СЕКЦИЯ "НАША КОМАНДА" ПОЛНОСТЬЮ УДАЛЕНА -->

                 <div class="about-content__section">
                    <h2>Присоединяйтесь к нам!</h2>
                    <p>Хотите стать частью нашей команды или предложить сотрудничество? Свяжитесь с нами через раздел <a href="mailto:borovetf@gmail.com">Контакты</a>.</p>
                    <p>Следите за нашими новостями в социальных сетях, чтобы первыми узнавать о выгодных предложениях и интересных маршрутах!</p>
                </div>

            </div>
        </section>
    </main>

    <footer class="footer">
        <div class="container footer__container">
            <div class="footer__links">
                <a href="about-us-log.php">О нас</a> <!-- В футере ссылка остается -->
                <a href="mailto:borovetf@gmail.com">Контакты</a>
                <a href="https://docs.google.com/document/d/1uUSg0HDIPny75EqESQr0gu2Utg3AtNBaLw0Xk0-TyL0/edit?usp=sharing">Правила и условия</a>
                <a href="https://docs.google.com/document/d/1drFUdo3izJodkkSkofe_e5AcnV0Ahl9jpezZYmJgZlU/edit?usp=sharing">Политика конфиденциальности</a>
            </div>
            <div class="footer__copyright">
                © 2025 AirGO. Все права защищены.
            </div>
        </div>
    </footer>

</body>
</html>