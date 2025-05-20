<?php
// admin_actions/add_offer_form.php
if (session_status() == PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    http_response_code(403); exit("Доступ запрещен.");
}
require '../db.php';

$offer_id = null;
$page_action_title = "Добавить";
$submit_button_text = "Добавить спецпредложение";
$offer_data = [
    'title' => '', 'subtitle' => '', 'price_from' => '', 'currency_code' => 'RUB',
    'image_path' => null, // Инициализируем новое поле
    'details_page_title' => '', 'details_hero_subtitle' => '',
    'details_main_description' => '', 'details_what_to_see' => '', 'details_direction' => '',
    'details_price_info' => '', 'details_departure_from' => '', 'details_travel_period' => '',
    'details_flight_class' => 'Эконом', 'search_destination_iata' => '', 'search_origin_iata' => '',
    'search_trip_type' => 'round-trip', 'is_active' => 1, 'sort_order' => 100
];
$current_image_html = ''; // Для отображения текущего изображения при редактировании

if (isset($_GET['offer_id']) && is_numeric($_GET['offer_id'])) {
    $offer_id = (int)$_GET['offer_id'];
    $page_action_title = "Редактировать";
    $submit_button_text = "Сохранить изменения";

    $stmt_get_offer = $conn->prepare("SELECT * FROM special_offers WHERE offer_id = ?");
    if (!$stmt_get_offer) { $_SESSION['offers_message']="DB Err P1"; header("Location: ../admin.php?tab=special-offers-content"); exit(); }
    $stmt_get_offer->bind_param("i", $offer_id);
    $stmt_get_offer->execute();
    $result_get_offer = $stmt_get_offer->get_result();
    if ($result_get_offer->num_rows === 1) {
        $offer_data = $result_get_offer->fetch_assoc();
        if (!empty($offer_data['image_path'])) {
            // Путь к изображению должен быть относительным от корня сайта для тега <img>
            // Предполагаем, что ../uploads/ у нас из admin_actions/
            $image_display_path = '../' . $offer_data['image_path']; // Путь для отображения
            if (file_exists($image_display_path)) {
                 $current_image_html = '<div class="current-image-preview"><label>Текущее изображение:</label><img src="' . htmlspecialchars($image_display_path) . '" alt="Текущее изображение" style="max-width: 200px; max-height: 150px; display: block; margin-top: 5px;"></div>';
            }
        }
    } else {
        $_SESSION['offers_message'] = "Спецпредложение с ID " . $offer_id . " не найдено.";
        $_SESSION['offers_message_type'] = "error";
        header("Location: ../admin.php?tab=special-offers-content");
        exit();
    }
    $stmt_get_offer->close();
}

$form_values = $_SESSION['offer_form_data'] ?? $offer_data;
unset($_SESSION['offer_form_data']);
$error_message_form = $_SESSION['offer_form_error'] ?? '';
unset($_SESSION['offer_form_error']);

$return_params_string = http_build_query(array_intersect_key($_GET, array_flip(['spage', 'ssearch', 'sfilter_active'])));
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_action_title; ?> Спецпредложение - Админ Air GO</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/admin-style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { background-color: var(--content-bg); display: flex; justify-content: center; align-items: flex-start; min-height: 100vh; padding: 2rem; box-sizing: border-box; }
        .form-page-container { background-color: var(--widget-bg); padding: 2rem; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.08); width: 100%; max-width: 750px; margin: auto; }
        .form-page-container h1 { text-align: center; color: var(--dark-green); margin-bottom: 1.5rem; font-size: 1.6rem; }
        .btn-container { margin-top: 2rem; display: flex; justify-content: flex-end; gap: 0.5rem; }
        fieldset { border: 1px solid var(--border-color); padding: 1.5rem; margin-bottom: 1.5rem; border-radius: 6px; }
        legend { padding: 0 0.5em; font-weight: 600; color: var(--dark-gray); font-size: 1.1em; margin-bottom: 1rem; }
        .form-columns { display: grid; grid-template-columns: 1fr; gap: 1.5rem; }
        @media (min-width: 600px) { .form-columns { grid-template-columns: 1fr 1fr; } }
        textarea { min-height: 120px; resize: vertical; }
        label small {font-weight: normal; color: var(--dark-gray); display: block; font-size: 0.8em;}
        .current-image-preview { margin-bottom: 10px; }
        .form-group input[type="file"] { padding: 0.5rem; } /* Небольшой стиль для поля файла */
    </style>
</head>
<body>
    <div class="form-page-container">
        <h1><?php echo $page_action_title; ?> спецпредложение</h1>
        <?php if ($error_message_form): ?>
            <div class="admin-message error"><?php echo htmlspecialchars($error_message_form); ?></div>
        <?php endif; ?>

        <!-- enctype="multipart/form-data" ОБЯЗАТЕЛЕН для загрузки файлов -->
        <form action="handle_save_offer.php" method="POST" enctype="multipart/form-data"> 
            <?php if ($offer_id): ?>
                <input type="hidden" name="offer_id" value="<?php echo $offer_id; ?>">
                <input type="hidden" name="existing_image_path" value="<?php echo htmlspecialchars($offer_data['image_path'] ?? ''); ?>">
            <?php endif; ?>
            <input type="hidden" name="return_spage" value="<?php echo htmlspecialchars($_GET['spage'] ?? '1'); ?>">
            <input type="hidden" name="return_ssearch" value="<?php echo htmlspecialchars($_GET['ssearch'] ?? ''); ?>">
            <input type="hidden" name="return_sfilter_active" value="<?php echo htmlspecialchars($_GET['sfilter_active'] ?? 'all'); ?>">

            <fieldset>
                <legend><i class="fas fa-bullhorn"></i> Основная информация (для карточки)</legend>
                <div class="form-group">
                    <label for="title">Заголовок карточки (напр., "Романтический Париж")*</label>
                    <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($form_values['title']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="subtitle">Подзаголовок карточки (опционально):</label>
                    <input type="text" id="subtitle" name="subtitle" value="<?php echo htmlspecialchars($form_values['subtitle'] ?? ''); ?>">
                </div>
                 <div class="form-columns">
                    <div class="form-group">
                        <label for="price_from">Цена "от"*</label>
                        <input type="number" id="price_from" name="price_from" step="0.01" min="0" value="<?php echo htmlspecialchars($form_values['price_from'] ?? ''); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="currency_code">Валюта (3 буквы, напр. RUB)*</label>
                        <input type="text" id="currency_code" name="currency_code" value="<?php echo htmlspecialchars($form_values['currency_code'] ?? 'RUB'); ?>" maxlength="3" pattern="[A-Za-z]{3}" title="Введите 3 латинские буквы" required>
                    </div>
                </div>
                <!-- ЗАМЕНА ПОЛЯ ЦВЕТА НА ЗАГРУЗКУ ИЗОБРАЖЕНИЯ -->
                <div class="form-group">
                    <label for="offer_image">Изображение для карточки и страницы: <small>(рекомендуемый размер ~600x400px, форматы: JPG, PNG, GIF, WEBP)</small></label>
                    <?php echo $current_image_html; ?>
                    <input type="file" id="offer_image" name="offer_image" accept="image/jpeg, image/png, image/gif, image/webp">
                    <?php if ($offer_id && !empty($offer_data['image_path'])): ?>
                        <label style="margin-top:5px; display:block;">
                            <input type="checkbox" name="delete_existing_image" value="1"> Удалить текущее изображение (если загружается новое, старое удалится автоматически)
                        </label>
                    <?php endif; ?>
                </div>
            </fieldset>
            
            <!-- Остальные fieldset (Детальная страница, Параметры поиска, Настройки отображения) остаются БЕЗ ИЗМЕНЕНИЙ, -->
            <!-- копируйте их из вашего предыдущего кода для add_offer_form.php -->
            <fieldset>
                <legend><i class="fas fa-file-alt"></i> Детальная страница предложения</legend>
                <div class="form-group">
                    <label for="details_page_title">Заголовок детальной страницы (H1)*</label>
                    <input type="text" id="details_page_title" name="details_page_title" value="<?php echo htmlspecialchars($form_values['details_page_title'] ?? ''); ?>" required>
                </div>
                 <div class="form-group">
                    <label for="details_hero_subtitle">Подзаголовок на детальной странице:</label>
                    <input type="text" id="details_hero_subtitle" name="details_hero_subtitle" value="<?php echo htmlspecialchars($form_values['details_hero_subtitle'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label for="details_main_description">Основное описание* <small>(HTML теги разрешены, будьте осторожны)</small></label>
                    <textarea id="details_main_description" name="details_main_description" required><?php echo htmlspecialchars($form_values['details_main_description'] ?? ''); ?></textarea>
                </div>
                 <div class="form-group">
                    <label for="details_what_to_see">Что посмотреть: <small>(каждый пункт с новой строки)</small></label>
                    <textarea id="details_what_to_see" name="details_what_to_see"><?php echo htmlspecialchars($form_values['details_what_to_see'] ?? ''); ?></textarea>
                </div>

                <h4>Информация для таблицы "Детали предложения" на странице:</h4>
                 <div class="form-columns">
                    <div class="form-group">
                        <label for="details_direction">Направление (текст):</label>
                        <input type="text" id="details_direction" name="details_direction" value="<?php echo htmlspecialchars($form_values['details_direction'] ?? ''); ?>" placeholder="Париж, Франция (CDG, ORY)">
                    </div>
                    <div class="form-group">
                        <label for="details_price_info">Цена (текст):</label>
                        <input type="text" id="details_price_info" name="details_price_info" value="<?php echo htmlspecialchars($form_values['details_price_info'] ?? ''); ?>" placeholder="от 15 000 ₽ (туда-обратно)">
                    </div>
                </div>
                <div class="form-columns">
                     <div class="form-group">
                        <label for="details_departure_from">Вылет из:</label>
                        <input type="text" id="details_departure_from" name="details_departure_from" value="<?php echo htmlspecialchars($form_values['details_departure_from'] ?? ''); ?>" placeholder="Москва, Санкт-Петербург">
                    </div>
                     <div class="form-group">
                        <label for="details_travel_period">Период путешествия:</label>
                        <input type="text" id="details_travel_period" name="details_travel_period" value="<?php echo htmlspecialchars($form_values['details_travel_period'] ?? ''); ?>" placeholder="Осень 2024 - Весна 2025">
                    </div>
                </div>
                <div class="form-group">
                    <label for="details_flight_class">Класс перелета:</label>
                    <input type="text" id="details_flight_class" name="details_flight_class" value="<?php echo htmlspecialchars($form_values['details_flight_class'] ?? 'Эконом'); ?>">
                </div>
            </fieldset>

            <fieldset>
                <legend><i class="fas fa-search-location"></i> Параметры для кнопки "Найти рейсы"</legend>
                 <div class="form-columns">
                    <div class="form-group">
                        <label for="search_destination_iata">IATA назначения (напр. CDG, или PAR)* <small>(можно через запятую: CDG,ORY)</small></label>
                        <input type="text" id="search_destination_iata" name="search_destination_iata" value="<?php echo htmlspecialchars($form_values['search_destination_iata'] ?? ''); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="search_origin_iata">IATA отправления (опционально) <small>(также можно через запятую)</small></label>
                        <input type="text" id="search_origin_iata" name="search_origin_iata" value="<?php echo htmlspecialchars($form_values['search_origin_iata'] ?? ''); ?>">
                    </div>
                </div>
                <div class="form-group">
                    <label for="search_trip_type">Тип поездки для поиска:</label>
                    <select id="search_trip_type" name="search_trip_type">
                        <option value="round-trip" <?php echo (isset($form_values['search_trip_type']) && $form_values['search_trip_type'] == 'round-trip') ? 'selected' : ''; ?>>Туда-Обратно</option>
                        <option value="one-way" <?php echo (isset($form_values['search_trip_type']) && $form_values['search_trip_type'] == 'one-way') ? 'selected' : ''; ?>>В одну сторону</option>
                    </select>
                </div>
            </fieldset>

            <fieldset>
                <legend><i class="fas fa-cog"></i> Настройки отображения</legend>
                <div class="form-columns">
                    <div class="form-group">
                        <label for="sort_order">Порядок сортировки: <small>(меньше = выше в списке)</small></label>
                        <input type="number" id="sort_order" name="sort_order" value="<?php echo htmlspecialchars($form_values['sort_order'] ?? '100'); ?>" required>
                    </div>
                    <div class="form-group" style="padding-top: 1.8rem;">
                         <input type="checkbox" id="is_active" name="is_active" value="1" <?php echo (isset($form_values['is_active']) && $form_values['is_active'] == 1) ? 'checked' : ''; ?>>
                         <label for="is_active" style="display:inline; font-weight:normal;"> Активно (отображать на сайте)</label>
                    </div>
                </div>
            </fieldset>

            <div class="btn-container">
                <a href="../admin.php?tab=special-offers-content&<?php echo $return_params_string; ?>" class="btn btn-secondary">Отмена</a>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> <?php echo $submit_button_text; ?></button>
            </div>
        </form>
    </div>
</body>
</html>