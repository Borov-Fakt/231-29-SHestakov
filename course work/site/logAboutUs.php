<?php
session_start();
require 'db.php'; // Подключение к базе данных

// Перенаправление если пользователь не авторизован
if (!isset($_SESSION['user'])) {
    header("Location: input.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>О нас - FinanceExpert</title>
    <link rel="stylesheet" href="css/aboutUs.css">
</head>
<body>
    <div id="preloader" class="preloader">
        <div class="loding__block">
            <div class="title">Loading...</div>
            <div class="progress"></div>
        </div>
        <div class="preloader__block"></div>
        <div class="preloader__block"></div>
    </div>
    <header>
        <a href="#" class="logo">FinanceExpert</a>
        <nav>
            <ul>
                <li><a href="logIndex.php">Главная</a></li>
                <li><a href="logAboutUs.php">О нас</a></li>
                <li><a href="logContacts.php">Контакты</a></li>
                <li><a href="profile.php">Профиль</a></li>
            </ul>
        </nav>
    </header>
    <div class="aboutUs">
        <h1>FinanceExpert</h1>
        <p class="slogan">Ваш надежный гид в мире финансов.</p>
        <section class="mission">
            <p>Наша миссия — сделать сложный мир финансов доступным и понятным для каждого.</p>
        </section>
    </div>
    <script src="js/preloader.js"></script>
</body>
</html>