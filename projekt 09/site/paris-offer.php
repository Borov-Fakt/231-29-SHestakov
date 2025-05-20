<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Романтический Париж - Специальное предложение - AirGO</title>
    <!-- Подключаем ОСНОВНОЙ файл стилей (тот же, что и для главной) -->
    <link rel="stylesheet" href="css/style.css">
    <!-- Шрифты и иконки подключаются так же, как на главной -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css"> -->

    <!-- Дополнительные стили для этой страницы (или встроены в основной style.css) -->
    <link rel="stylesheet" href="css/offer-style.css"> <!-- Или добавьте CSS ниже прямо в <style> теги -->
</head>
<body>

    <header class="header">
        <div class="container header__container">
            <a href="index.php" class="header__logo">AirGO</a> <!-- Название изменено, ссылка на главную -->
            <nav class="header__nav">
                <ul>
                    <li><a href="login.php">Мои бронирования</a></li>
                    <li><a href="mailto:borovetf@gmail.com">Помощь</a></li>
                    <li><a href="login.php">Войти</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <main class="offer-page">
        <!-- Секция-хедер для конкретного предложения -->
        <section class="offer-hero">
            <div class="container offer-hero__container">
                <h1>Романтический Париж ждет вас!</h1>
                <p class="offer-hero__subtitle">Откройте для себя город любви по специальной цене</p>
                 <!-- Блок-заглушка вместо основного изображения -->
                 <div class="offer-hero__placeholder">
                     <!-- Можно добавить SVG иконку Эйфелевой башни или сердца -->
                     <!-- <i class="fas fa-heart"></i> -->
                 </div>
            </div>
        </section>

        <!-- Основной контент страницы предложения -->
        <section class="offer-details">
            <div class="container offer-details__container">

                <div class="offer-description">
                    <h2>Погрузитесь в атмосферу романтики</h2>
                    <p>Париж – это не просто город, это мечта. Прогуляйтесь по Монмартру, взявшись за руки, полюбуйтесь мерцанием Эйфелевой башни ночью, насладитесь круассаном с кофе в уютном кафе на берегу Сены. AirGO предлагает вам сделать эту мечту реальностью.</p>
                    <p>Наше специальное предложение включает перелет по выгодной цене, позволяя вам сэкономить на путешествии и потратить больше на впечатления. Идеально подходит для романтического уикенда, предложения руки и сердца или просто незабываемого отпуска вдвоем.</p>
                    <h3>Что посмотреть:</h3>
                    <ul>
                        <li>Эйфелева башня и Марсово поле</li>
                        <li>Лувр и сад Тюильри</li>
                        <li>Собор Парижской Богоматери (Нотр-Дам де Пари)</li>
                        <li>Монмартр и базилика Сакре-Кёр</li>
                        <li>Елисейские Поля и Триумфальная арка</li>
                        <li>Романтическая прогулка на кораблике по Сене</li>
                    </ul>
                </div>

                <aside class="offer-key-info">
                    <div class="key-info-card">
                        <h2>Детали предложения</h2>
                        <table class="key-info-table">
                            <tbody>
                                <tr>
                                    <th>Направление:</th>
                                    <td>Париж, Франция (CDG, ORY)</td>
                                </tr>
                                <tr>
                                    <th>Цена:</th>
                                    <td><strong>от 15 000 ₽</strong> (туда-обратно)</td>
                                </tr>
                                <tr>
                                    <th>Вылет из:</th>
                                    <td>Москва, Санкт-Петербург (возможны другие города)</td>
                                </tr>
                                <tr>
                                    <th>Период путешествия:</th>
                                    <td>Осень 2023 - Весна 2024 (уточняйте даты)</td>
                                </tr>
                                 <tr>
                                    <th>Класс:</th>
                                    <td>Эконом (возможно повышение класса)</td>
                                </tr>
                            </tbody>
                        </table>
                        <div class="offer-cta">
                            <a href="/search-results?destination=PAR&trip-type=round-trip" class="btn btn--primary">Найти рейсы в Париж</a>
                            <!-- Ссылка выше - пример, как можно передать параметры на страницу поиска -->
                        </div>
                    </div>
                     <div class="key-info-tip">
                        <p><strong>Совет:</strong> Бронируйте заранее, чтобы получить лучшие цены и удобные рейсы!</p>
                    </div>
                </aside>

            </div>
        </section>

        <!-- Можно добавить секцию с другими предложениями -->

    </main>

    <footer class="footer">
         <!-- Футер точно такой же, как на главной -->
        <div class="container footer__container">
            <div class="footer__links">
                <a href="about-us.php">О нас</a> <!-- В футере ссылка остается -->
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