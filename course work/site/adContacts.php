<?php
// Contacts.php - Страница контактов (в стиле админки, доступна только админу)

// Включаем строгую отчетность об ошибках для разработки
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Запускаем сессию (если еще не запущена)
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// --- Проверка аутентификации АДМИНИСТРАТОРА ---
$is_admin_logged_in = isset($_SESSION['is_admin_logged_in']) && $_SESSION['is_admin_logged_in'] === true;

// --- Логика выхода ---
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    session_unset();
    session_destroy();
    // ВАЖНО: Перенаправляем на страницу входа админа, НЕ на admin.php
    header('Location: input.php'); // Или login.php, если вы его используете
    exit;
}

// --- Если НЕ администратор, перенаправляем ---
if (!$is_admin_logged_in) {
    header('Location: input.php'); // Или login.php
    exit;
}

// --- Если админ, продолжаем ---
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Контакты - Админка FinanceExpert</title>

    <!-- ПОДКЛЮЧАЕМ ОРИГИНАЛЬНЫЙ CSS ДЛЯ КОНТЕНТА -->
    <link rel="stylesheet" href="css/Contacts.css">

    <!-- ДОБАВЛЯЕМ СТИЛИ ТОЛЬКО ДЛЯ ХЕДЕРА АДМИНКИ -->
    <style>
        /* Стили для body, чтобы фон соответствовал админке */
        body {
            background: #CDF3BC;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            margin: 0;
            padding: 0;
            font-family: sans-serif;
        }

        /* --- Стили Хедера (копия из admin.php для консистентности) --- */
        header.head {
            background: #00AD00; display: flex; justify-content: space-between;
            align-items: center; padding: 0 2%; z-index: 1000;
            box-shadow: 0 2px 10px rgba(0,0,0,0.2); min-height: 70px;
            flex-wrap: wrap;
            position: relative;
        }
        header.head .logo { font-size: 30px; font-weight: 900; color: #fff; transition: transform 0.5s; margin-right: 20px; text-decoration: none; }
        header.head .logo:hover { transform: scale(1.1); }
        header.head nav { flex-grow: 1; }
        header.head nav ul { display: flex; align-items: center; justify-content: flex-end; flex-wrap: wrap; list-style: none; margin: 0; padding: 0; }
        header.head nav ul li { margin: 5px 0; }
        header.head nav ul li a { padding: 10px 15px; color: #000; font-size: 18px; font-weight: 600;
            display: block; transition: all 0.3s ease; line-height: normal; border-radius: 4px; text-decoration: none; }
         header.head nav ul li a:hover { background: black; color: #fff; }
         /* Стиль для активной ссылки */
         header.head nav ul li a.active-link { background: black; color: #fff; }
         /* Стиль для ссылки выхода */
        header.head nav ul li a.logout-link { background-color: #dc3545; color: white; margin-left: 15px; border-radius: 4px; padding: 8px 15px; font-size: 16px; }
        header.head nav ul li a.logout-link:hover { background-color: #c82333; }

         /* Адаптивность для хедера */
         @media (max-width: 768px) {
            header.head { padding: 10px 2%; min-height: auto; }
            header.head .logo { font-size: 26px; text-align: center; width: 100%; margin-bottom: 10px; margin-right: 0; }
            header.head nav { width: 100%; }
            header.head nav ul { justify-content: center; }
            header.head nav ul li a { font-size: 16px; padding: 8px 12px; }
             header.head nav ul li a.logout-link { margin-left: 10px; }
         }
         @media (max-width: 480px) {
              header.head .logo { font-size: 22px; }
              header.head nav ul li a { padding: 8px 10px; font-size: 14px; }
              header.head nav ul li a.logout-link { font-size: 13px; padding: 6px 10px; }
         }

         /* Класс для основного контента, чтобы можно было добавить отступ сверху при необходимости */
         .content-area {
             flex-grow: 1; /* Занимает оставшееся место по высоте */
             /* padding-top: 20px; Добавьте отступ, если нужно */
         }

    </style>
</head>
<body>
    <!-- Прелоадер (если используется) -->
    <div id="preloader" class="preloader">
        <div class="loding__block">
            <div class="title">Loading...</div>
            <div class="progress"></div>
        </div>
        <div class="preloader__block"></div>
        <div class="preloader__block"></div>
    </div>

    <!-- ХЕДЕР АДМИНКИ С НОВЫМИ ССЫЛКАМИ -->
    <header class="head">
        <!-- Логотип теперь ссылается на adIndex.php (или admin.php, если это главная админки) -->
        <a href="adIndex.php" class="logo">FinanceExpert</a>
        <nav>
            <ul>
                <!-- НОВЫЕ ССЫЛКИ ИЗ ВАШЕГО СПИСКА -->
                <li><a href="adIndex.php">Главная</a></li>
                <li><a href="adAboutUs.php">О нас</a></li>
                <!-- Делаем эту ссылку активной -->
                <li><a href="adContacts.php" class="active-link">Контакты</a></li>
                <li><a href="admin.php">Админка</a></li>
                <!-- ВАЖНО: ссылка выхода должна использовать GET-параметр на ТЕКУЩЕЙ странице или вести на logout-скрипт -->
                <li><a href="Contacts.php?action=logout" class="logout-link">Выйти</a></li>
                <!-- ИЛИ если есть отдельный logout.php:
                <li><a href="logout.php" class="logout-link">Выйти</a></li>
                -->
            </ul>
        </nav>
    </header>

    <!-- ОРИГИНАЛЬНЫЙ КОНТЕНТ СТРАНИЦЫ КОНТАКТОВ -->
    <div class="content-area"> <!-- Обертка для контента -->
        <div class="aboutUs">
            </section>
        </div>
        <section id="contacts" class="contacts">
            <h2>Контакты</h2>
            <p>Свяжитесь с нами через удобные для вас каналы.</p>
            <a href="https://www.youtube.com/watch?v=Cxmvq1MCR3c" target="_blank" class="telegram-button">Написать в Telegram</a>
        </section>
    </div>

    <!-- Подключаем оригинальный JS (если он был) -->
    <script src="js/preloader.js"></script>
</body>
</html>