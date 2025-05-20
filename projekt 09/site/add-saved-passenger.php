<?php
// add-saved-passenger.php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Проверка, залогинен ли пользователь
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Восстановление данных формы при ошибке валидации
$form_data = $_SESSION['form_data_sp'] ?? []; // 'sp' для saved passenger
unset($_SESSION['form_data_sp']);

// Массивы для ENUM полей (чтобы не хардкодить в HTML)
$genders = [
    'male' => 'Мужской',
    'female' => 'Женский',
    'other' => 'Другой',
    'undisclosed' => 'Не указан'
];
$passenger_types = [
    'adult' => 'Взрослый (12+ лет)',
    'child' => 'Ребенок (2-11 лет)',
    'infant' => 'Младенец (до 2 лет, без места)'
];
$document_types = [
    'passport_intl' => 'Загранпаспорт',
    'passport_national' => 'Паспорт национальный (внутренний)',
    'id_card' => 'ID-карта (для стран ЕС/СНГ)',
    'birth_certificate' => 'Свидетельство о рождении (для детей)'
];

// Примерный список стран. В реальном приложении его лучше загружать из БД или более полного источника.
$countries = [
    'AF' => 'Афганистан', 'AL' => 'Албания', 'DZ' => 'Алжир', 'AD' => 'Андорра', 'AO' => 'Ангола', 
    'AR' => 'Аргентина', 'AM' => 'Армения', 'AU' => 'Австралия', 'AT' => 'Австрия', 'AZ' => 'Азербайджан', 
    'BY' => 'Беларусь', 'BE' => 'Бельгия', 'BR' => 'Бразилия', 'BG' => 'Болгария', 'CA' => 'Канада', 
    'CN' => 'Китай', 'HR' => 'Хорватия', 'CY' => 'Кипр', 'CZ' => 'Чехия', 'DK' => 'Дания', 
    'EG' => 'Египет', 'EE' => 'Эстония', 'FI' => 'Финляндия', 'FR' => 'Франция', 'GE' => 'Грузия', 
    'DE' => 'Германия', 'GR' => 'Греция', 'HU' => 'Венгрия', 'IS' => 'Исландия', 'IN' => 'Индия', 
    'ID' => 'Индонезия', 'IR' => 'Иран', 'IE' => 'Ирландия', 'IL' => 'Израиль', 'IT' => 'Италия', 
    'JP' => 'Япония', 'KZ' => 'Казахстан', 'KE' => 'Кения', 'KG' => 'Кыргызстан', 'LV' => 'Латвия', 
    'LT' => 'Литва', 'LU' => 'Люксембург', 'MY' => 'Малайзия', 'MX' => 'Мексика', 'MD' => 'Молдова', 
    'MN' => 'Монголия', 'ME' => 'Черногория', 'MA' => 'Марокко', 'NL' => 'Нидерланды', 'NZ' => 'Новая Зеландия', 
    'NG' => 'Нигерия', 'NO' => 'Норвегия', 'PK' => 'Пакистан', 'PL' => 'Польша', 'PT' => 'Португалия', 
    'QA' => 'Катар', 'RO' => 'Румыния', 'RU' => 'Россия', 'SA' => 'Саудовская Аравия', 'RS' => 'Сербия', 
    'SG' => 'Сингапур', 'SK' => 'Словакия', 'SI' => 'Словения', 'ZA' => 'ЮАР', 'KR' => 'Южная Корея', 
    'ES' => 'Испания', 'SE' => 'Швеция', 'CH' => 'Швейцария', 'TJ' => 'Таджикистан', 'TH' => 'Таиланд', 
    'TR' => 'Турция', 'TM' => 'Туркменистан', 'UA' => 'Украина', 'AE' => 'ОАЭ', 'GB' => 'Великобритания', 
    'US' => 'США', 'UZ' => 'Узбекистан', 'VN' => 'Вьетнам'
    // ... и т.д.
];
asort($countries); // Сортировка стран по названию

?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Добавить пассажира - AirGO</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" integrity="sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46ZmdtAc9eNBvH0H/ZpiBw==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <!-- 1. ОБЩИЙ файл стилей (для header, footer, base) -->
    <link rel="stylesheet" href="css/style.css">
    <!-- 2. Можно подключить profile-style.css, если формы там стилизованы -->
    <!-- <link rel="stylesheet" href="css/profile-style.css">  -->
    
    <!-- Опционально: Шрифт Poppins -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        /* ==========================================================================
           Встроенные стили для формы добавления пассажира
           В идеале, их нужно вынести в отдельный forms-style.css
           ========================================================================== */

        /* Используем CSS переменные из style.css (предполагается, что он подключен ДО этого) */
        /* :root { --primary-green: #28a745; --dark-green: #1e7e34; ... и т.д. } */

        body {
            background-color: var(--light-gray); /* Фон страницы, если отличается от общего */
        }

        .form-container {
            max-width: 780px; /* Увеличена ширина контейнера */
            margin: 2rem auto;
            padding: 2rem;
            background: var(--white);
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .form-container h1 {
            text-align: center;
            margin-bottom: 2rem;
            color: var(--dark-green);
            font-size: 1.8rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5em;
        }
        .form-container h1 .icon-title { /* Для иконки в заголовке */
             opacity: 0.8;
        }


        .form-container fieldset {
            border: 1px solid var(--medium-gray);
            padding: 1rem 1.5rem 1.5rem 1.5rem; /* top | horizontal | bottom | (left = horizontal) */
            border-radius: 8px; /* Немного другое скругление */
            margin-bottom: 2rem;
        }
        .form-container fieldset:last-of-type {
             margin-bottom: 1.5rem; /* Уменьшить отступ у последнего fieldset */
        }


        .form-container legend {
            font-size: 1.25rem; /* Немного крупнее */
            font-weight: 600;
            color: var(--primary-green);
            padding: 0 0.75em;
            margin-left: 10px; /* Чтобы отбить от левого края fieldset */
        }

        .form-row {
            display: flex;
            flex-wrap: wrap;
            gap: 1.5rem; /* Основной отступ между элементами в ряду */
            margin-bottom: 1rem; /* Отступ у каждого ряда, КРОМЕ ПОСЛЕДНЕГО в fieldset */
        }
         .form-container fieldset .form-row:last-child {
            margin-bottom: 0; /* Убираем отступ у последнего ряда в fieldset */
        }


        .form-group {
            display: flex;
            flex-direction: column;
            flex-grow: 1;
            min-width: 150px; /* Минимальная ширина */
        }

        .form-group.half-width {
            flex-basis: calc(50% - 0.75rem); /* 0.75rem = 1.5rem (gap) / 2 */
        }

        .form-group.third-width {
            flex-basis: calc(33.333% - 1rem); /* 1rem = (1.5rem * 2 [gap'а]) / 3 */
        }
        

        .form-group label {
            display: block;
            margin-bottom: 0.5rem; /* Отступ лейбла */
            font-weight: 500;
            color: var(--dark-gray);
            font-size: 0.9rem;
            line-height: 1.3;
        }

        .form-group input[type="text"],
        .form-group input[type="date"],
        .form-group select {
            width: 100%;
            padding: 0.75rem 1rem; /* Немного скорректированы паддинги */
            border: 1px solid var(--medium-gray);
            border-radius: 6px;
            font-size: 1rem;
            font-family: var(--font-family, sans-serif); /* Добавлен fallback шрифт */
            background-color: var(--white);
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
            line-height: 1.4; /* Улучшен line-height */
            color: var(--text-color); /* Цвет текста в поле */
        }

        .form-group input[type="date"] {
            color: var(--dark-gray); /* Изначальный цвет (плейсхолдер) */
            min-height: calc(0.75rem * 2 + 1rem * 1.4 + 2px); /* Попытка выровнять высоту с select/text */
        }
         .form-group input[type="date"]::-webkit-calendar-picker-indicator {
            cursor: pointer;
            opacity: 0.6;
        }
        .form-group input[type="date"]:valid,
        .form-group input[type="date"]:focus { /* Когда дата выбрана или поле в фокусе */
            color: var(--text-color);
        }
        

        .form-group input[type="text"]:focus,
        .form-group input[type="date"]:focus,
        .form-group select:focus {
            outline: none;
            border-color: var(--primary-green);
            box-shadow: 0 0 0 2.5px rgba(40, 167, 69, 0.25); /* Немного другой вид тени */
        }

        .form-group select {
            appearance: none; /* Убираем стандартную стрелку для кастомной */
            -webkit-appearance: none;
            -moz-appearance: none;
            background-image: url('data:image/svg+xml;charset=US-ASCII,%3Csvg%20width%3D%2220%22%20height%3D%2220%22%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%3E%3Cpath%20d%3D%22M5%208%20l5%205%205-5%22%20stroke%3D%22%236c757d%22%20stroke-width%3D%221.5%22%20fill%3D%22none%22%20stroke-linecap%3D%22round%22%20stroke-linejoin%3D%22round%22%2F%3E%3C%2Fsvg%3E');
            background-repeat: no-repeat;
            background-position: right 0.75rem center;
            background-size: 1em;
            padding-right: 2.5rem; /* Место для кастомной стрелки */
        }
        .form-group select:focus {
             background-image: url('data:image/svg+xml;charset=US-ASCII,%3Csvg%20width%3D%2220%22%20height%3D%2220%22%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%3E%3Cpath%20d%3D%22M5%208%20l5%205%205-5%22%20stroke%3D%22%2328a745%22%20stroke-width%3D%221.5%22%20fill%3D%22none%22%20stroke-linecap%3D%22round%22%20stroke-linejoin%3D%22round%22%2F%3E%3C%2Fsvg%3E'); /* Зеленая стрелка при фокусе */
        }

        /* Сообщения об ошибках PHP */
        .message {
            padding: 12px 15px; /* Немного другие паддинги */
            margin: 0 0 1.5rem 0; /* Отступ только снизу */
            border-radius: 6px;
            text-align: left; /* Текст ошибки слева */
            font-weight: 500;
            font-size: 0.95rem;
            line-height: 1.4;
        }
        .message.error {
            background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb;
        }


        .form-actions {
            margin-top: 1.5rem; /* Уменьшен отступ */
            padding-top: 1.5rem;
            border-top: 1px solid var(--light-gray);
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
        }
        
        /* Предполагается, что .btn стили есть в style.css или profile-style.css */
        /* Если нужно специфично: */
        .form-actions .btn {
            padding: 0.7em 1.5em; /* Используем em для кнопок */
            font-size: 1rem;
            min-width: 120px; /* Минимальная ширина кнопок */
        }
         .btn-submit { /* Переопределяем, если нужно */
            /* background: linear-gradient(135deg, var(--accent-green), var(--primary-green)); */
            /* color: var(--white); */
        }
        .btn-cancel {
            /* background-color: var(--medium-gray); */
            /* color: var(--dark-gray); */
            /* border: none; */
        }
        /* Кнопки наследуют стили от .btn, .btn--primary, .btn--secondary из style.css */


        @media (max-width: 768px) {
            .form-container { padding: 1.5rem; }
            .form-group.half-width,
            .form-group.third-width {
                flex-basis: 100%;
                /* margin-bottom не нужен, т.к. есть gap у form-row */
            }
            .form-row {
                 /* Если в одну колонку, gap будет вертикальным */
                 /* Для строгого друг под другом без отступа у ряда:
                    flex-direction: column;
                    gap: 0;
                    margin-bottom: 0; (убрать отступ у ряда)
                    и добавить .form-group { margin-bottom: 1.2rem; } (для отступа между элементами в колонке)
                 */
            }
            .form-container legend { font-size: 1.15rem;}
            .form-actions { flex-direction: column-reverse; align-items: stretch; }
            .form-actions .btn { width: 100%; margin: 0.3rem 0; }
        }

        @media (max-width: 576px) {
             .form-container { padding: 1.5rem 1rem; margin: 1.5rem auto;}
             .form-container h1 { font-size: 1.6rem; }
             .form-container legend { font-size: 1.1rem; }

             .form-group input[type="text"],
             .form-group input[type="date"],
             .form-group select {
                padding: 0.7rem 0.8rem;
                font-size: 0.95rem;
            }
             .form-actions .btn { font-size: 0.95rem; }
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="container header__container">
            <a href="index-log.php" class="header__logo">AirGO</a>
            <nav class="header__nav">
                <ul><li><a href="profile.php"><i class="fas fa-arrow-left"></i> Назад в профиль</a></li></ul>
            </nav>
        </div>
    </header>

    <main>
        <div class="form-container">
            <h1><i class="fas fa-user-plus icon-title"></i> Добавить нового пассажира</h1>

            <?php if (isset($_SESSION['add_passenger_error'])): ?>
            <div class="message error">
                <?php echo htmlspecialchars($_SESSION['add_passenger_error']); ?>
            </div>
            <?php unset($_SESSION['add_passenger_error']); ?>
            <?php endif; ?>

            <form action="handle-add-saved-passenger.php" method="POST">
                <fieldset>
                    <legend>Личные данные</legend>
                    <div class="form-row">
                        <div class="form-group third-width">
                            <label for="last_name">Фамилия (как в документе, латиницей):</label>
                            <input type="text" id="last_name" name="last_name" value="<?php echo htmlspecialchars($form_data['last_name'] ?? ''); ?>" required pattern="[a-zA-Z\s-]+" title="Только латинские буквы, пробелы и дефисы">
                        </div>
                        <div class="form-group third-width">
                            <label for="first_name">Имя (как в документе, латиницей):</label>
                            <input type="text" id="first_name" name="first_name" value="<?php echo htmlspecialchars($form_data['first_name'] ?? ''); ?>" required pattern="[a-zA-Z\s-]+" title="Только латинские буквы, пробелы и дефисы">
                        </div>
                        <div class="form-group third-width">
                            <label for="middle_name">Отчество (если есть, латиницей):</label>
                            <input type="text" id="middle_name" name="middle_name" value="<?php echo htmlspecialchars($form_data['middle_name'] ?? ''); ?>" pattern="[a-zA-Z\s-]*" title="Только латинские буквы, пробелы и дефисы">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group half-width">
                            <label for="date_of_birth">Дата рождения:</label>
                            <input type="date" id="date_of_birth" name="date_of_birth" value="<?php echo htmlspecialchars($form_data['date_of_birth'] ?? ''); ?>" required>
                        </div>
                        <div class="form-group half-width">
                            <label for="gender">Пол:</label>
                            <select id="gender" name="gender" required>
                                <option value="" disabled <?php echo (!isset($form_data['gender']) || $form_data['gender'] === '') ? 'selected' : ''; ?>>-- Выберите --</option>
                                <?php foreach($genders as $key => $value): ?>
                                <option value="<?php echo $key; ?>" <?php echo (isset($form_data['gender']) && $form_data['gender'] == $key) ? 'selected' : ''; ?>><?php echo $value; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-row">
                         <div class="form-group half-width">
                            <label for="passenger_type">Тип пассажира:</label>
                            <select id="passenger_type" name="passenger_type" required>
                                <option value="" disabled <?php echo (!isset($form_data['passenger_type']) || $form_data['passenger_type'] === '') ? 'selected' : ''; ?>>-- Выберите --</option>
                                 <?php foreach($passenger_types as $key => $value): ?>
                                <option value="<?php echo $key; ?>" <?php echo (isset($form_data['passenger_type']) && $form_data['passenger_type'] == $key) ? 'selected' : ''; ?>><?php echo $value; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                         <div class="form-group half-width">
                            <label for="nationality_country_code">Гражданство:</label>
                            <select id="nationality_country_code" name="nationality_country_code" required>
                                <option value="" disabled <?php echo (!isset($form_data['nationality_country_code']) || $form_data['nationality_country_code'] === '') ? 'selected' : ''; ?>>-- Выберите страну --</option>
                                <?php foreach($countries as $code => $name): ?>
                                <option value="<?php echo $code; ?>" <?php echo (isset($form_data['nationality_country_code']) && $form_data['nationality_country_code'] == $code) ? 'selected' : ''; ?>><?php echo $name; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </fieldset>

                <fieldset>
                    <legend>Данные документа</legend>
                     <div class="form-row">
                        <div class="form-group half-width">
                            <label for="document_type">Тип документа:</label>
                             <select id="document_type" name="document_type" required>
                                <option value="" disabled <?php echo (!isset($form_data['document_type']) || $form_data['document_type'] === '') ? 'selected' : ''; ?>>-- Выберите тип --</option>
                                <?php foreach($document_types as $key => $value): ?>
                                <option value="<?php echo $key; ?>" <?php echo (isset($form_data['document_type']) && $form_data['document_type'] == $key) ? 'selected' : ''; ?>><?php echo $value; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                         <div class="form-group half-width">
                            <label for="document_number">Номер документа (без серии):</label>
                            <input type="text" id="document_number" name="document_number" value="<?php echo htmlspecialchars($form_data['document_number'] ?? ''); ?>" required pattern="[a-zA-Z0-9]+" title="Только латинские буквы и цифры">
                        </div>
                    </div>
                     <div class="form-row">
                        <div class="form-group half-width">
                            <label for="document_issuing_country_code">Страна выдачи документа:</label>
                            <select id="document_issuing_country_code" name="document_issuing_country_code" required>
                                 <option value="" disabled <?php echo (!isset($form_data['document_issuing_country_code']) || $form_data['document_issuing_country_code'] === '') ? 'selected' : ''; ?>>-- Выберите страну --</option>
                                <?php foreach($countries as $code => $name): ?>
                                <option value="<?php echo $code; ?>" <?php echo (isset($form_data['document_issuing_country_code']) && $form_data['document_issuing_country_code'] == $code) ? 'selected' : ''; ?>><?php echo $name; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                         <div class="form-group half-width">
                            <label for="document_expiry_date">Срок действия (если применимо):</label>
                            <input type="date" id="document_expiry_date" name="document_expiry_date" value="<?php echo htmlspecialchars($form_data['document_expiry_date'] ?? ''); ?>">
                        </div>
                    </div>
                </fieldset>

                <div class="form-actions">
                    <a href="profile.php" class="btn btn-cancel">Отмена</a>
                    <button type="submit" class="btn btn-submit btn--primary"><i class="fas fa-plus"></i> Добавить</button>
                </div>
            </form>
        </div>
    </main>

    <footer class="footer">
         <div class="container footer__container">
            <div class="footer__links">
                <a href="about-us-log.php">О нас</a>
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