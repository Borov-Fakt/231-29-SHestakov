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
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" href="css/style.css">

    <!-- swiperJS-->
    <link
    rel="stylesheet"
    href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css"
/>
</head>
<body>
    <div id="preloader" class="preloader">
        <div class="loding__block">
            <div class="title">
                Loading...
            </div>
            <div class="progress"></div>
        </div>
        <div class="preloader__block"></div>
        <div class="preloader__block"></div>
    </div>
    <header class="head">
        <a href="#" class="logo">FinanceExpert</a>
        <div class="bx-menu" id="menu-icon"></div>
        <nav>
            <ul>
                <li><a href="logIndex.php">Главная</a></li>
                <li><a href="logAboutUs.php">О нас</a></li>
                <li><a href="logContacts.php">Контакты</a></li>
                <li><a href="profile.php">Профиль</a></li>
            </ul>
        </nav>
    </header>
    <section class="home">
        <div class="container swiper">
            <div class="wrapper swiper-wrapper">
                <div class="slide swiper-slide">
                    <div class="img">
                        <img src="img/1731929885580.jpg" alt="">
                        <div class="content">
                            <a href="#" class="btn">Текст</a>
                        </div>
                    </div>
                </div>

                <div class="slide swiper-slide">
                    <div class="img">
                        <img src="img/1731929906555.jpg" alt="">
                        <div class="content">
                            <a href="#" class="btn">Текст</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <script src="js/preloader.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
    <!-- Initialize Swiper -->

  <script>
    var swiper3 = new Swiper(".container", {
      grabCursor: true,
      effect: "creative",
      creativeEffect: {
        prev: {
          shadow: true,
          translate: [0, 0, -500],
        },
        next: {
          translate: ["100%", 0, -500],
        },
      },
      loop: true,
    });
  </script>
</body>
</html>