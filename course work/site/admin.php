<?php
// admin.php - Главная страница панели администратора (требует входа через login.php/auth.php)

// Включаем строгую отчетность об ошибках для разработки
error_reporting(E_ALL);
ini_set('display_errors', 1); // В продакшене лучше 0 и логировать

// Запускаем сессию (если еще не запущена)
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// --- Проверка аутентификации АДМИНИСТРАТОРА ---
$is_admin_logged_in = isset($_SESSION['is_admin_logged_in']) && $_SESSION['is_admin_logged_in'] === true;

// --- Логика выхода ---
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    session_unset();    // Удаляем все переменные сессии
    session_destroy();  // Уничтожаем сессию
    header('Location: input.php'); // ИЛИ login.php, если он есть
    exit;
}

// --- Если НЕ администратор, перенаправляем на страницу входа ---
if (!$is_admin_logged_in) {
    // Перед редиректом можно записать в сессию сообщение об ошибке, если нужно
    // $_SESSION['login_error'] = "Требуется вход администратора";
    header('Location: input.php'); // ИЛИ login.php
    exit;
}

// --- Если вошли в систему как админ, продолжаем ---

// --- Подключение к БД ---
require 'db.php'; // Подключаем db.php, он создаст объект $conn

// --- Получение списка пользователей из БД для сайдбара ---
$users_sidebar = [];
$db_error = null;

// Всегда запрашиваем avatar
$sql_sidebar = "SELECT id, username, email, avatar FROM users ORDER BY username ASC";
$result_sidebar = $conn->query($sql_sidebar);

if ($result_sidebar) {
    while ($row = $result_sidebar->fetch_assoc()) {
        $users_sidebar[] = $row;
    }
    $result_sidebar->free();
} else {
    $db_error = "Ошибка получения списка пользователей: " . $conn->error;
    // Логируем ошибку, чтобы не показывать ее напрямую, если display_errors выключен
    error_log("Ошибка SQL в admin.php (sidebar list): " . $conn->error);
}

// Не закрываем соединение здесь, оно может понадобиться позже (хотя в этом файле вроде нет)
// $conn->close();

?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Админка - FinanceExpert</title>
    <style>
        /* --- Общие стили и стили body --- */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: sans-serif;
            list-style: none;
            text-decoration: none;
        }

        body {
            background: #CDF3BC;
            position: relative;
            overflow-x: hidden;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* --- Стили Хедера --- */
         header.head {
            background: #00AD00; display: flex; justify-content: space-between;
            align-items: center; padding: 0 2%; z-index: 1000;
            box-shadow: 0 2px 10px rgba(0,0,0,0.2); min-height: 70px;
            flex-wrap: wrap;
        }
        header.head .logo { font-size: 30px; font-weight: 900; color: #fff; transition: transform 0.5s; margin-right: 20px; }
        header.head .logo:hover { transform: scale(1.1); }
        header.head nav { flex-grow: 1; }
        header.head nav ul { display: flex; align-items: center; justify-content: flex-end; flex-wrap: wrap; }
        header.head nav ul li { margin: 5px 0; }
        header.head nav ul li a { padding: 10px 15px; color: #000; font-size: 18px; font-weight: 600;
            display: block; transition: all 0.3s ease; line-height: normal; border-radius: 4px; }
         header.head nav ul li a:hover { background: black; color: #fff; }
         header.head nav ul li a.active-link { background: black; color: #fff; }
         /* Стиль для ссылки выхода */
        header.head nav ul li a.logout-link { background-color: #dc3545; color: white; margin-left: 15px; border-radius: 4px; padding: 8px 15px; font-size: 16px; }
        header.head nav ul li a.logout-link:hover { background-color: #c82333; }

        /* --- Стили для разметки админки --- */
        .admin-layout { display: flex; flex-grow: 1; background: #CDF3BC; width: 100%;}
        .admin-sidebar { width: 280px; flex-shrink: 0; background-color: #f8f9fa; border-right: 1px solid #dee2e6;
            padding: 15px; box-sizing: border-box; display: flex; flex-direction: column;
            min-height: 300px; /* Минимальная высота для вида */
             overflow: hidden; /* Чтобы внутренний скролл работал */
             }
        .admin-sidebar h2 { margin-top: 0; margin-bottom: 15px; font-size: 1.4em; color: #333;
            text-align: center; flex-shrink: 0; padding-bottom: 10px; border-bottom: 1px solid #dee2e6; }
        .user-list-container { flex-grow: 1; overflow-y: auto; margin: 0 -15px -15px -15px; /* Для корректного скролла */
            padding: 0 15px 15px 15px; }
        .user-list { list-style: none; padding: 0; margin: 0; }
        .user-list-item { display: flex; align-items: center; padding: 10px 8px; border-bottom: 1px solid #eee;
            cursor: pointer; transition: background-color 0.2s ease; border-radius: 4px; margin-bottom: 5px; }
        .user-list-item:last-child { border-bottom: none; margin-bottom: 0; }
        .user-list-item:hover { background-color: #e9ecef; }
        .user-list-item.active { background-color: #00AD00; color: white; font-weight: bold; }
        .user-list-item.active .user-email { color: #f0f0f0; }
        .user-avatar-placeholder { width: 40px; height: 40px; background-color: #ccc; border-radius: 50%;
            margin-right: 12px; flex-shrink: 0; background-size: cover; background-position: center; border: 1px solid #ddd; }
        .user-info { overflow: hidden; white-space: nowrap; text-overflow: ellipsis; flex-grow: 1; }
        .user-nickname { display: block; font-weight: 600; color: #333; margin-bottom: 2px;
            text-overflow: ellipsis; overflow: hidden; }
         .user-list-item.active .user-nickname { color: white; }
        .user-email { font-size: 0.85em; color: #666; text-overflow: ellipsis; overflow: hidden; display: block; }

        /* --- Стили для основного контента админки --- */
        .admin-content { flex-grow: 1; padding: 25px 30px; background-color: #CDF3BC; box-sizing: border-box;
            overflow-y: auto; /* Скролл для контента, если он не помещается */
           min-height: 400px; /* Минимальная высота */
           }
        .admin-content h1 { margin-top: 0; margin-bottom: 20px; color: #333; border-bottom: 1px solid #ccc;
             padding-bottom: 10px; }
        #initialContent { /* Стили для приветственного сообщения */
             text-align: center; padding: 40px 20px; background-color: #f0fff0; /* Легкий зеленый фон */
             border: 1px dashed #00ad00; border-radius: 8px; color: #333;
        }
         #initialContent p { margin-bottom: 10px; font-size: 1.1em; }

        /* --- Стили для деталей пользователя --- */
        #userDetailsContainer { display: none; /* Скрыто по умолчанию */ justify-content: center; align-items: flex-start;
             flex-wrap: wrap; gap: 30px; padding-top: 20px; padding-bottom: 30px; }
        /* Показывать, только если не пустой */
        #userDetailsContainer:not(:empty) { display: flex; }

        /* --- Стили Аватара и Кнопок (Просмотр и Редактирование) --- */
        .profile-picture-admin { display: flex; flex-direction: column; align-items: center; flex-shrink: 0; width: 200px; margin-bottom: 15px; }
        .circle-admin {
            width: 180px; height: 180px; border-radius: 50%; background-color: #e0e0e0;
            border: 3px solid #37b24d; background-size: cover; background-position: center; margin-bottom: 15px; /* Уменьшен отступ под кнопками */
            position: relative; /* Для позиционирования оверлея */
            overflow: hidden; /* Скрыть части оверлея вне круга */
            cursor: default; /* Стандартный курсор в режиме просмотра */
        }
        .circle-admin.editable { /* Стиль для режима редактирования */
             cursor: pointer;
             border-color: #ffc107; /* Оранжевая рамка при редактировании */
        }
        /* Стили для подсказки при наведении в режиме редактирования */
        .avatar-change-overlay {
            position: absolute; top: 0; left: 0; width: 100%; height: 100%;
            background-color: rgba(0,0,0,0.5); color: white;
            display: flex; justify-content: center; align-items: center;
            text-align: center; font-size: 0.9em; font-weight: bold;
            opacity: 0; /* Скрыто по умолчанию */
            transition: opacity 0.3s ease;
            cursor: pointer; border-radius: 50%; /* Чтобы был круглым */
        }
         .circle-admin.editable:hover .avatar-change-overlay {
            opacity: 1; /* Показать при наведении */
        }
         /* Скрываем поле выбора файла */
        #edit_avatar_input {
            display: none;
        }
        /* Стили для контейнера предпросмотра */
         .avatar-preview {
             margin-bottom: 15px;
             max-width: 180px; /* Ограничиваем размер */
             display: none; /* Скрыт по умолчанию */
             text-align: center;
         }
          .avatar-preview img {
              max-width: 100%; height: auto; border-radius: 50%;
              border: 3px solid #28a745; /* Зеленая рамка для превью */
              display: block; /* Чтобы margin auto работал */
              margin: 0 auto;
          }
         /* Когда показывается превью, скрываем основной круг */
         .circle-admin.preview-mode {
            display: none;
        }

         /* Кнопка "Убрать выбор" (для аватара) */
        #clearPreviewBtn {
            margin-top: -5px; margin-bottom: 10px;
            font-size: 0.8em; background: #6c757d; color: white; border: none;
            padding: 3px 8px; border-radius: 3px; cursor: pointer;
            display: none; /* Скрыта по умолчанию */
         }
         #clearPreviewBtn:hover { background: #5a6268; }

        /* Контейнер для кнопок действий */
        .admin-actions { display: flex; flex-wrap: wrap; justify-content: center; gap: 10px; width: 100%; margin-top: 5px; /* Небольшой отступ сверху */ }
        .admin-actions button { padding: 10px 18px; background-color: #37b24d; color: white; border: none;
            border-radius: 6px; font-size: 0.95rem; cursor: pointer; transition: all 0.3s ease; margin: 0;
            min-width: 110px; text-align: center; }
        .admin-actions button:hover { opacity: 0.9; }
        .admin-actions button.delete { background-color: #dc3545; }
         .admin-actions button.delete:hover { background-color: #c82333; }
         .admin-actions button.save { background-color: #28a745; }
          .admin-actions button.save:hover { background-color: #218838; }
         .admin-actions button.cancel { background-color: #6c757d; }
          .admin-actions button.cancel:hover { background-color: #5a6268; }


        /* --- Стили Блока Деталей и Формы --- */
        .profile-details-admin { max-width: 550px; flex-grow: 1; background-color: #fff; padding: 25px 30px;
            border-radius: 8px; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1); border: 1px solid #e0e0e0; width: 100%; }
        .profile-details-admin h2 { margin-top: 0; margin-bottom: 30px; font-size: 1.7rem; color: #333;
            text-align: center; border-bottom: none; padding-bottom: 0; font-weight: 600; }
        .profile-details-admin .form-group { margin-bottom: 15px; position: relative; padding-bottom: 18px; /* Место для сообщения об ошибке */ }
        .profile-details-admin label { display: block; margin-bottom: 8px; color: #555;
            font-size: 0.95rem; font-weight: 600; }

        /* Стили Полей Ввода (Общие) */
        .profile-details-admin input[type="text"],
        .profile-details-admin input[type="email"] {
             width: 100%; padding: 10px 14px; border: 1px solid #ced4da; border-radius: 5px; font-size: 1rem;
            color: #495057; box-shadow: none; transition: border-color .15s ease-in-out, box-shadow .15s ease-in-out;
        }
        .profile-details-admin input:focus { outline: none; }

        /* Readonly поля (в режиме просмотра) */
        .profile-details-admin input[readonly] { background-color: #e9ecef; cursor: not-allowed; }
         .profile-details-admin input[readonly]:focus { box-shadow: none; border-color: #ced4da; }

        /* Редактируемые поля */
        .profile-details-admin input:not([readonly]) { background-color: #fff; cursor: text; }
         .profile-details-admin input:not([readonly]):focus { border-color: #80bdff; box-shadow: 0 0 0 0.2rem rgba(0,123,255,.25); }

         /* Стили для поля с ошибкой */
         .profile-details-admin .form-group input.is-invalid { border-color: #dc3545; }
          .profile-details-admin .form-group input.is-invalid:focus { border-color: #dc3545; box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25); }

         /* Стили для текста ошибки под полем */
        .profile-details-admin .invalid-feedback { display: none; width: 100%; margin-top: .25rem; font-size: .8em; color: #dc3545; position: absolute; bottom: 0; left: 0; }
        /* Показываем текст, когда у инпута есть класс is-invalid */
        .profile-details-admin input.is-invalid ~ .invalid-feedback { display: block; }

         /* Кнопка "Назад к обзору" в режиме просмотра */
         .profile-details-admin button.back-button { display: block; width: 100%; margin-top: 25px; padding: 10px 15px; background-color: #6c757d; color: white; border: none; border-radius: 6px; font-size: 1rem; cursor: pointer; transition: background-color 0.3s ease; text-align: center; }
          .profile-details-admin button.back-button:hover { background-color: #5a6268; }
         /* Кнопка "Сохранить" внизу формы (в режиме редактирования) */
        .profile-details-admin button[type="submit"].save { width:100%; margin-top: 15px; padding: 10px 18px; background-color: #28a745; color: white; border: none; border-radius: 6px; font-size: 1rem; cursor: pointer; transition: background-color 0.3s ease; }
        .profile-details-admin button[type="submit"].save:hover { background-color: #218838; }
         /* Кнопка "Отмена" внизу формы (в режиме редактирования) */
        .profile-details-admin button.cancel-form-button { display: block; width: 100%; margin-top: 10px; padding: 10px 15px; background-color: #6c757d; color: white; border: none; border-radius: 6px; font-size: 1rem; cursor: pointer; transition: background-color 0.3s ease; text-align: center; }
          .profile-details-admin button.cancel-form-button:hover { background-color: #5a6268; }


        /* --- Стили для общих AJAX сообщений --- */
        #ajaxMessageContainer { margin-top: 15px; min-height: 45px; /* Чтобы контейнер не "прыгал" при появлении сообщения */ }
        .db-error-message { /* Ошибка при загрузке списка юзеров */
            color: #D8000C; background-color: #FFD2D2; border: 1px solid #D8000C;
            padding: 10px 15px; margin: 0 0 15px 0; border-radius: 4px; text-align: center;
        }
        .ajax-message { /* Сообщения об операциях (успех/ошибка/загрузка) */
            padding: 12px 15px; margin-bottom: 15px; /* Добавлен нижний отступ */ border-radius: 4px; text-align: center;
            border: 1px solid;
            font-size: 0.95em;
         }
        .ajax-message.error { color: #D8000C; background-color: #FFD2D2; border-color: #D8000C; }
        .ajax-message.success { color: #155724; background-color: #d4edda; border-color: #c3e6cb; }
        .ajax-message.loading { color: #0c5460; background-color: #d1ecf1; border-color: #bee5eb; }
        .ajax-message.info { color: #004085; background-color: #cce5ff; border-color: #b8daff; }

        /* --- Адаптивность --- */
         @media (max-width: 992px) {
             .admin-layout { flex-direction: column; } /* Столбиком на средних экранах */
             .admin-sidebar {
                 width: 100%; max-height: 40vh; /* Ограничиваем высоту сайдбара */
                 border-right: none; border-bottom: 1px solid #dee2e6;
                 margin-bottom: 20px; /* Отступ снизу */
             }
             .admin-content { width: 100%; padding: 20px; /* Уменьшаем паддинг */}
             #userDetailsContainer { flex-direction: column; align-items: center; gap: 20px;} /* Аватар над деталями */
             .profile-picture-admin { width: auto; max-width: 200px; /* Центрируем блок аватара */}
             .profile-details-admin { max-width: 95%; /* Шире на средних */ padding: 20px; }
             header.head nav ul li a.logout-link { font-size: 14px; padding: 5px 10px; margin-left: 10px; }
         }

        @media (max-width: 768px) {
            header.head { padding: 10px 2%; min-height: auto; }
            header.head .logo { font-size: 26px; text-align: center; width: 100%; margin-bottom: 10px; margin-right: 0; }
            header.head nav { width: 100%; }
            header.head nav ul { justify-content: center; }
            header.head nav ul li a { font-size: 16px; padding: 8px 12px; }
            header.head nav ul li a.logout-link { margin-left: 10px; }

            .admin-sidebar { max-height: 35vh; }
             #userDetailsContainer { gap: 25px; }
             .profile-details-admin h2 { font-size: 1.5rem; margin-bottom: 20px; }
        }

        @media (max-width: 480px) {
            header.head .logo { font-size: 22px; }
            header.head nav ul li a { padding: 8px 10px; font-size: 14px; }
            header.head nav ul li a.logout-link { font-size: 13px; padding: 6px 10px; }

            .admin-sidebar { max-height: 30vh; padding: 10px; }
             .user-list-container { padding: 0 10px 10px 10px; margin: 0 -10px -10px -10px; }
             .user-avatar-placeholder { width: 35px; height: 35px; margin-right: 10px;}

            .admin-content { padding: 15px; }
            #initialContent { padding: 20px 10px; font-size: 1em; }
             .profile-details-admin { padding: 15px 20px; }
             .profile-details-admin input[type="text"],
             .profile-details-admin input[type="email"] { padding: 9px 12px; font-size: 0.95rem;}
             .profile-details-admin label { font-size: 0.9rem; margin-bottom: 6px; }
             .profile-details-admin h2 { font-size: 1.3rem; }

             .circle-admin { width: 150px; height: 150px; }
             .avatar-preview img { width: 150px; height: 150px; } /* И превью */

             .admin-actions button { padding: 9px 15px; font-size: 0.9rem; min-width: 90px; }
              .profile-details-admin .invalid-feedback { font-size: 0.75em; }
              .profile-details-admin .form-group { padding-bottom: 15px; }
        }
    </style>
</head>
<body>
    <!-- Хедер -->
    <header class="head">
        <a href="adIndex.php" class="logo">FinanceExpert</a> <!-- Ссылка на главную для админа -->
        <nav>
            <ul>
                <li><a href="adIndex.php">Главная</a></li>
                <li><a href="adAboutUs.php">О нас</a></li>
                <li><a href="adContacts.php">Контакты</a></li>
                <li><a href="admin.php" class="active-link">Админка</a></li>
                <li><a href="admin.php?action=logout" class="logout-link">Выйти</a></li>
            </ul>
        </nav>
    </header>

    <!-- Разметка админки -->
    <div class="admin-layout">

        <!-- Левый сайдбар со списком пользователей -->
        <aside class="admin-sidebar">
            <h2>Пользователи</h2>
            <?php if ($db_error): ?>
                <div class="db-error-message"><?= htmlspecialchars($db_error) ?></div>
            <?php endif; ?>
            <div class="user-list-container">
                <ul class="user-list" id="userList">
                    <?php if (empty($users_sidebar) && !$db_error): ?>
                        <li style="padding: 15px; color: #666; text-align: center;">Пользователи не найдены.</li>
                    <?php endif; ?>
                    <?php
                    foreach ($users_sidebar as $user):
                        $userIdEscaped = htmlspecialchars((string)$user['id'], ENT_QUOTES, 'UTF-8');
                        $usernameEscaped = htmlspecialchars($user['username'], ENT_QUOTES, 'UTF-8');
                        $emailEscaped = htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8');
                        // Добавляем timestamp к пути аватара для предотвращения кеширования браузером
                        $avatar_path = !empty($user['avatar']) ? htmlspecialchars($user['avatar'], ENT_QUOTES, 'UTF-8') . '?t=' . time() : '';
                        $style_attr = $avatar_path ? 'style="background-image: url(\'' . $avatar_path . '\')"' : 'style="background-color: #ccc;"';
                    ?>
                        <li class="user-list-item" data-userid="<?= $userIdEscaped ?>">
                            <div class="user-avatar-placeholder" <?= $style_attr ?>></div>
                            <div class="user-info">
                                <span class="user-nickname"><?= $usernameEscaped ?></span>
                                <span class="user-email"><?= $emailEscaped ?></span>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </aside>

        <!-- Основной контент админ-панели -->
        <main class="admin-content" id="adminContent">

             <!-- Начальное содержимое (приветствие) -->
             <div id="initialContent">
                 <h1>Панель администратора</h1>
                 <p>Добро пожаловать в панель управления FinanceExpert!</p>
                 <p>Выберите пользователя из списка слева, чтобы просмотреть или изменить его данные.</p>
             </div>

            <!-- Контейнер для динамической загрузки деталей пользователя -->
            <div id="userDetailsContainer">
                <!-- Сюда будут загружаться детали пользователя через AJAX -->
            </div>

            <!-- Контейнер для общих AJAX сообщений (редактирование/удаление/ошибки) -->
            <div id="ajaxMessageContainer">
                <!-- Сюда будут выводиться сообщения об успехе/ошибке операций -->
            </div>

        </main>

    </div> <!-- Конец .admin-layout -->

    <!-- JavaScript -->
    <script>
        // --- Глобальные переменные и кэширование DOM ---
        const userListContainer = document.getElementById('userList');
        const adminContentArea = document.getElementById('adminContent');
        const userDetailsContainer = document.getElementById('userDetailsContainer');
        const initialContent = document.getElementById('initialContent');
        const ajaxMessageContainer = document.getElementById('ajaxMessageContainer');

        let currentUserData = null; // Хранит данные текущего выбранного пользователя
        let activeUserId = null;    // Хранит ID текущего активного пользователя
        let currentAvatarPreviewUrl = null; // Хранит Data URL для предпросмотра аватара

        // --- Утилитарные Функции ---

        // Экранирование HTML (базовое)
        function escapeHtml(unsafe) {
            if (unsafe === null || typeof unsafe === 'undefined') {
                return '';
            }
            const str = String(unsafe);
            const div = document.createElement('div');
            div.textContent = str;
            return div.innerHTML;
        }

        // Показать AJAX сообщение
        function showAjaxMessage(message, type = 'info', container = ajaxMessageContainer) {
            if (!container) return;
            clearAjaxMessage(container); // Очистить предыдущее сообщение
            const messageDiv = document.createElement('div');
            messageDiv.className = `ajax-message ${type}`; // Добавляем класс типа (error, success, loading, info)
            messageDiv.textContent = message;
            container.appendChild(messageDiv);
        }

        // Очистить AJAX сообщения
        function clearAjaxMessage(container = ajaxMessageContainer) {
            if (container) {
                container.innerHTML = '';
            }
        }

        // Показать ошибку для конкретного поля ввода
        function showFieldError(inputElement, message) {
            if (!inputElement) return;
            clearFieldError(inputElement); // Сначала убрать старую ошибку
            inputElement.classList.add('is-invalid');
            const formGroup = inputElement.closest('.form-group');
            if (formGroup) {
                let errorDiv = formGroup.querySelector('.invalid-feedback');
                if (!errorDiv) { // Создаем div для ошибки, если его нет
                    errorDiv = document.createElement('div');
                    errorDiv.className = 'invalid-feedback';
                    // Вставляем после input элемента
                    inputElement.parentNode.insertBefore(errorDiv, inputElement.nextSibling);
                }
                errorDiv.textContent = message;
                errorDiv.style.display = 'block'; // Убедимся, что он виден
            }
        }

        // Убрать ошибку для конкретного поля ввода
        function clearFieldError(inputElement) {
            if (!inputElement) return;
            inputElement.classList.remove('is-invalid');
            const formGroup = inputElement.closest('.form-group');
            if (formGroup) {
                const errorDiv = formGroup.querySelector('.invalid-feedback');
                if (errorDiv) {
                    errorDiv.textContent = '';
                    errorDiv.style.display = 'none'; // Скрываем его
                }
            }
        }

         // Очистить все ошибки валидации в форме
         function clearAllFormErrors(formElement) {
            if (!formElement) return;
            const invalidInputs = formElement.querySelectorAll('.is-invalid');
            invalidInputs.forEach(input => clearFieldError(input));
            // Также очищаем общие AJAX сообщения в основном контейнере
            clearAjaxMessage();
        }

        // --- Основные Функции Панели Администратора ---

        // Сброс основного контента к начальному состоянию
        function resetAdminContent() {
             userDetailsContainer.innerHTML = ''; // Очищаем контейнер деталей
             userDetailsContainer.style.display = 'none'; // Скрываем его
             clearAjaxMessage(); // Очищаем сообщения
             initialContent.style.display = 'block'; // Показываем приветствие
             currentUserData = null; // Сбрасываем данные
             activeUserId = null;    // Сбрасываем активный ID
             currentAvatarPreviewUrl = null; // Сброс превью URL

             // Снимаем выделение активного элемента в сайдбаре
             if(userListContainer) {
                const currentActive = userListContainer.querySelector('.user-list-item.active');
                if (currentActive) {
                    currentActive.classList.remove('active');
                }
             }
        }

        // Удаление пользователя из списка в сайдбаре
        function removeUserFromSidebar(userId) {
            if (!userListContainer) return;
            const itemToRemove = userListContainer.querySelector(`.user-list-item[data-userid="${userId}"]`);
            if (itemToRemove) {
                itemToRemove.remove();
                // Проверяем, остался ли кто-то в списке
                if (!userListContainer.querySelector('.user-list-item')) {
                    // Если список пуст, показываем сообщение
                    userListContainer.innerHTML = '<li style="padding: 15px; color: #666; text-align: center;">Пользователи не найдены.</li>';
                }
            }
        }

        // Обновление аватара пользователя в сайдбаре
        function updateUserSidebarAvatar(userId, avatarUrl) {
             if (!userListContainer || !userId) return;
            const userItem = userListContainer.querySelector(`.user-list-item[data-userid="${userId}"]`);
            if (userItem) {
                const avatarDiv = userItem.querySelector('.user-avatar-placeholder');
                if (avatarDiv) {
                    if (avatarUrl) {
                        // Добавляем timestamp, чтобы обойти кэш браузера
                        const finalUrl = `${escapeHtml(avatarUrl)}?t=${Date.now()}`;
                        avatarDiv.style.backgroundImage = `url('${finalUrl}')`;
                        avatarDiv.style.backgroundColor = ''; // Убираем фоновый цвет placeholder'а
                    } else {
                        // Если аватара нет (удален или не был установлен)
                        avatarDiv.style.backgroundImage = 'none';
                        avatarDiv.style.backgroundColor = '#ccc'; // Цвет placeholder'а
                    }
                }
            }
        }

        // --- Функции Рендеринга Контента ---

        // Рендеринг деталей пользователя в режиме просмотра
        function renderUserDetailsView(user) {
            // Сохраняем актуальные данные
            currentUserData = user;
            activeUserId = user.id;
            // Очищаем возможные ошибки от предыдущих операций
            clearAllFormErrors(document.getElementById('editUserForm')); // На всякий случай
            currentAvatarPreviewUrl = null; // Сбрасываем URL превью при показе режима View

            const userIdEscaped = escapeHtml(String(user.id));
            const usernameEscaped = escapeHtml(user.username);
            const emailEscaped = escapeHtml(user.email);
            // Добавляем timestamp для обхода кэша
            const avatarUrl = user.avatar ? `${escapeHtml(user.avatar)}?t=${Date.now()}` : '';
            const avatarStyle = avatarUrl
                ? `style="background-image: url('${avatarUrl}');"`
                : 'style="background-color: #e0e0e0;"'; // Стиль по умолчанию, если аватара нет

            // Формируем HTML
            const detailsHtml = `
                <div class="profile-picture-admin">
                    <!-- Круг для аватара -->
                    <div class="circle-admin" ${avatarStyle}></div>
                    <!-- Кнопки действий для режима просмотра -->
                    <div class="admin-actions">
                         <button type="button" onclick="enableEditMode('${userIdEscaped}')">Редактировать</button>
                         <button type="button" class="delete" onclick="confirmDeleteUser('${userIdEscaped}')">Удалить</button>
                    </div>
                </div>
                <div class="profile-details-admin">
                    <h2>Личные данные: ${usernameEscaped}</h2>
                    <!-- Используем форму чисто для верстки, без submit -->
                    <form onsubmit="return false;">
                        <div class="form-group">
                            <label for="admin_user_id">ID пользователя</label>
                            <input type="text" id="admin_user_id" value="${userIdEscaped}" readonly>
                        </div>
                        <div class="form-group">
                            <label for="admin_nickname">Никнейм</label>
                            <input type="text" id="admin_nickname" value="${usernameEscaped}" readonly>
                        </div>
                        <div class="form-group">
                            <label for="admin_email">Email</label>
                            <input type="email" id="admin_email" value="${emailEscaped}" readonly>
                        </div>
                         <!-- Кнопка для возврата к общему обзору -->
                         <button type="button" class="back-button" onclick="resetAdminContent()">Назад к обзору</button>
                    </form>
                </div>`;

            // Вставляем HTML в контейнер и показываем его
            userDetailsContainer.innerHTML = detailsHtml;
            userDetailsContainer.style.display = 'flex';
            initialContent.style.display = 'none'; // Скрываем приветствие
            clearAjaxMessage(); // Очищаем сообщения от предыдущих действий
        }

         // Рендеринг деталей пользователя в режиме РЕДАКТИРОВАНИЯ
        function renderUserDetailsEdit(user) {
            // Важно: Сбрасываем URL предпросмотра при каждом входе в режим редактирования
            currentAvatarPreviewUrl = null;

            const userIdEscaped = escapeHtml(String(user.id));
            const usernameEscaped = escapeHtml(user.username);
            const emailEscaped = escapeHtml(user.email);
             // Timestamp для текущего аватара
            const avatarUrl = user.avatar ? `${escapeHtml(user.avatar)}?t=${Date.now()}` : '';
            const avatarStyle = avatarUrl ? `style="background-image: url('${avatarUrl}');"` : 'style="background-color: #e0e0e0;"';

            const detailsHtml = `
                <div class="profile-picture-admin">
                     <!-- Контейнер для предпросмотра НОВОГО аватара -->
                     <div id="avatarPreviewContainer" class="avatar-preview">
                         <!-- Сюда JS вставит <img> для превью -->
                     </div>

                    <!-- ТЕКУЩИЙ аватар, теперь кликабельный для выбора файла -->
                    <div class="circle-admin editable ${currentAvatarPreviewUrl ? 'preview-mode' : ''}" ${avatarStyle}
                         onclick="document.getElementById('edit_avatar_input').click();"
                         title="Нажмите, чтобы сменить аватар">
                        <div class="avatar-change-overlay">Сменить аватар</div>
                    </div>

                    <!-- Скрытое поле для выбора файла -->
                    <input type="file" id="edit_avatar_input" name="avatar" accept="image/jpeg, image/png, image/gif" onchange="showAvatarPreview(this)">

                     <!-- Кнопка для очистки выбранного файла (появляется при превью) -->
                    <button type="button" id="clearPreviewBtn" onclick="clearAvatarPreview()" >Убрать выбор</button>

                     <!-- Кнопки Сохранить/Отмена для всей формы редактирования -->
                     <div class="admin-actions">
                         <button type="button" class="save" onclick="saveUserChanges('${userIdEscaped}')">Сохранить</button>
                         <button type="button" class="cancel" onclick="cancelEditMode('${userIdEscaped}')">Отмена</button>
                    </div>
                </div>

                <div class="profile-details-admin">
                    <h2>Редактирование: ${usernameEscaped}</h2>
                    <!-- ОСНОВНАЯ ФОРМА: enctype важен для файлов! -->
                    <form id="editUserForm" onsubmit="event.preventDefault(); saveUserChanges('${userIdEscaped}');" enctype="multipart/form-data">
                         <!-- Скрытое поле с ID -->
                         <input type="hidden" id="edit_user_id" value="${userIdEscaped}">

                         <!-- Поле Никнейм -->
                        <div class="form-group">
                            <label for="edit_nickname">Никнейм *</label>
                            <input type="text" id="edit_nickname" name="nickname" value="${usernameEscaped}"
                                   required minlength="3" maxlength="50" pattern="^[a-zA-Z0-9_]+$" aria-describedby="nicknameError">
                            <div id="nicknameError" class="invalid-feedback"></div>
                        </div>

                         <!-- Поле Email -->
                        <div class="form-group">
                            <label for="edit_email">Email *</label>
                            <input type="email" id="edit_email" name="email" value="${emailEscaped}"
                                   required aria-describedby="emailError">
                            <div id="emailError" class="invalid-feedback"></div>
                        </div>

                         <!-- Можно добавить отдельные кнопки Submit/Cancel и здесь, если удобно -->
                         <button type="submit" class="save">Сохранить изменения</button>
                         <button type="button" class="cancel-form-button" onclick="cancelEditMode('${userIdEscaped}')">Отмена</button>
                    </form>
                </div>`;

            userDetailsContainer.innerHTML = detailsHtml;
            userDetailsContainer.style.display = 'flex'; // Показать контейнер
            initialContent.style.display = 'none';   // Скрыть приветствие
            clearAjaxMessage(); // Убрать старые сообщения

            // Обновляем видимость кнопки очистки превью
             updateClearPreviewButtonVisibility();
             // Можно установить фокус на первое поле
             // document.getElementById('edit_nickname')?.focus();
        }


        // --- Логика Предпросмотра Аватара ---

         function showAvatarPreview(inputElement) {
            const previewContainer = document.getElementById('avatarPreviewContainer');
            const originalCircle = document.querySelector('.profile-picture-admin .circle-admin');
            const clearBtn = document.getElementById('clearPreviewBtn');

             if (!previewContainer || !originalCircle || !clearBtn) return; // Элементы не найдены

             if (inputElement.files && inputElement.files[0]) {
                 const file = inputElement.files[0];
                 const allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
                 const maxSize = 2 * 1024 * 1024; // 2 MB

                // Проверка типа и размера на клиенте (дополнительно к серверной)
                 if (!allowedTypes.includes(file.type)) {
                     showAjaxMessage('Неверный тип файла. Разрешены JPG, PNG, GIF.', 'error');
                     clearAvatarPreview(); // Очистить, если файл не подходит
                     return;
                 }
                 if (file.size > maxSize) {
                     showAjaxMessage('Файл слишком большой (макс. 2 МБ).', 'error');
                     clearAvatarPreview();
                     return;
                 }

                 // Используем FileReader для показа превью
                const reader = new FileReader();
                 reader.onload = function(e) {
                     // Сохраняем Data URL (хотя он будет пересоздан, если надо)
                    currentAvatarPreviewUrl = e.target.result;

                    // Показываем контейнер превью с изображением
                     previewContainer.innerHTML = `<img src="${e.target.result}" alt="Предпросмотр">`;
                     previewContainer.style.display = 'block';

                    // Скрываем оригинальный круг (добавляем класс)
                     originalCircle.classList.add('preview-mode');

                     // Показываем кнопку "Убрать выбор"
                     clearBtn.style.display = 'inline-block';
                 }
                 reader.readAsDataURL(file);
             } else {
                 // Если файл не выбран (например, нажали отмену в диалоге)
                 clearAvatarPreview();
             }
         }

        // Очистка предпросмотра и сброс поля input[type=file]
         function clearAvatarPreview() {
            const previewContainer = document.getElementById('avatarPreviewContainer');
            const originalCircle = document.querySelector('.profile-picture-admin .circle-admin');
            const fileInput = document.getElementById('edit_avatar_input');
            const clearBtn = document.getElementById('clearPreviewBtn');

             if (previewContainer) {
                 previewContainer.innerHTML = ''; // Очистить HTML превью
                previewContainer.style.display = 'none'; // Скрыть контейнер превью
             }
             if (originalCircle) {
                 originalCircle.classList.remove('preview-mode'); // Показать оригинальный аватар
             }
             if(fileInput) {
                // Сброс значения поля файла - лучший способ зависит от браузера,
                // но присваивание пустой строки часто работает
                 try {
                    fileInput.value = '';
                    // Для некоторых старых браузеров или edge cases:
                    if (fileInput.value) {
                        fileInput.type = "text";
                        fileInput.type = "file";
                    }
                 } catch(e) { console.error("Could not reset file input:", e); }
             }
             if (clearBtn) {
                clearBtn.style.display = 'none'; // Скрыть кнопку очистки
             }

            currentAvatarPreviewUrl = null; // Сбросить URL
            clearAjaxMessage(); // Очистить сообщение об ошибке размера/типа, если было
         }

         // Обновление видимости кнопки "Убрать выбор"
         function updateClearPreviewButtonVisibility() {
            const clearBtn = document.getElementById('clearPreviewBtn');
            if (clearBtn) {
                // Показываем, если есть превью ИЛИ если в поле input type=file что-то выбрано
                const fileInput = document.getElementById('edit_avatar_input');
                clearBtn.style.display = (currentAvatarPreviewUrl || (fileInput && fileInput.files.length > 0)) ? 'inline-block' : 'none';
            }
         }


        // --- Загрузка Данных и Действия с Пользователем ---

        // Загрузка деталей пользователя по ID (AJAX)
        function loadUserDetails(userId) {
            // Подготовка: очистка, показ индикатора загрузки
            clearAjaxMessage();
            userDetailsContainer.innerHTML = '';
            userDetailsContainer.style.display = 'none';
            initialContent.style.display = 'none';
            showAjaxMessage('Загрузка данных пользователя...', 'loading');
            currentUserData = null; // Сбрасываем старые данные

            // Отправка запроса на сервер
            fetch(`get_user_details.php?userId=${encodeURIComponent(userId)}`)
                .then(response => {
                    // Обработка НЕ-успешных HTTP ответов
                     if (!response.ok) {
                         // Попытаемся получить текст ошибки с сервера
                         return response.text().then(text => {
                             let errorMsg = `Ошибка сети ${response.status}: ${response.statusText}.`;
                             try { // Попробуем распарсить JSON, если вдруг он пришел с ошибкой
                                 const data = JSON.parse(text);
                                 if (data && data.error) { errorMsg += ` Сервер: ${data.error}`; }
                                  else { errorMsg += ` Ответ: ${text.substring(0, 200) || '(пусто)'}`; } // Показываем часть ответа

                                  // Если ошибка доступа, перенаправляем на вход
                                  if (data?.error?.includes("Доступ запрещен") || response.status === 401 || response.status === 403) {
                                       window.location.href = 'input.php'; // или login.php
                                  }
                             } catch(e) { // Если не JSON
                                 errorMsg += ` Ответ: ${text.substring(0, 200) || '(пусто)'}`;
                                  if (response.status === 401 || response.status === 403) { window.location.href = 'input.php'; }
                             }
                             throw new Error(errorMsg); // Бросаем ошибку для .catch()
                         });
                     }
                     // Проверка Content-Type, ожидаем JSON
                     const contentType = response.headers.get("content-type");
                     if (!contentType || !contentType.includes("application/json")) {
                         return response.text().then(text => {
                            throw new Error(`Неожиданный тип ответа от сервера (${contentType}). Ответ: ${text.substring(0, 100)}...`);
                         });
                     }
                     // Если ответ OK и тип JSON, парсим его
                     return response.json();
                })
                .then(data => {
                    // Обработка успешно полученных данных
                    if (data && data.error) {
                        // Ошибка пришла в JSON от сервера (например, пользователь не найден)
                        showAjaxMessage(`Ошибка загрузки: ${data.error}`, 'error');
                        if (data.error.includes("Доступ запрещен")) { // Еще раз проверяем на доступ
                             window.location.href = 'input.php';
                        } else {
                             resetAdminContent(); // Если юзер не найден, например, возвращаем стартовый экран
                        }
                    } else if (data && data.user) {
                        // Успех! Рендерим данные пользователя
                        renderUserDetailsView(data.user); // Передаем полученные данные
                    } else {
                        // Неожиданный формат данных
                        throw new Error('Неожиданный формат данных от сервера.');
                    }
                })
                .catch(error => {
                    // Обработка ошибок сети или ошибок, брошенных ранее
                    console.error('Ошибка fetch при загрузке деталей пользователя:', error);
                    showAjaxMessage(`Не удалось загрузить данные: ${error.message}`, 'error');
                    // Можно вернуть начальный экран при серьезной ошибке
                     resetAdminContent();
                });
        }

        // Включить режим редактирования
        function enableEditMode(userId) {
             if (currentUserData && currentUserData.id == userId) {
                  // Если текущие данные совпадают с запрошенным ID, просто рендерим форму редактирования
                 renderUserDetailsEdit(currentUserData);
             } else {
                 // Если данные устарели или ID не совпадает, лучше перезагрузить
                 console.warn("Данные для редактирования устарели или не совпадают. Перезагрузка...");
                 showAjaxMessage("Обновление данных перед редактированием...", "info");
                 // Загружаем свежие данные и после этого НЕ переходим в edit mode автоматически,
                 // т.к. loadUserDetails сам рендерит view mode. Пользователю нужно будет нажать "Редактировать" снова.
                 loadUserDetails(userId);
                 // Или, как альтернатива, можно сделать цепочку .then() в loadUserDetails,
                 // которая вызовет renderUserDetailsEdit после успешной загрузки, но это усложнит код.
             }
        }

        // Отменить режим редактирования (вернуться к просмотру)
        function cancelEditMode(userId) {
             if (currentUserData && currentUserData.id == userId) {
                 // Показываем режим просмотра с текущими (не измененными) данными
                 renderUserDetailsView(currentUserData);
             } else {
                 // Если что-то пошло не так (данные потеряны), просто сбрасываем все
                 console.warn("Не удалось отменить редактирование: данные пользователя отсутствуют. Сброс.");
                 resetAdminContent();
             }
        }

        // Сохранить изменения пользователя (AJAX)
        function saveUserChanges(userId) {
            // Находим элементы формы
            const form = document.getElementById('editUserForm');
            const idInput = document.getElementById('edit_user_id'); // Не используется для отправки, но для проверки ID
            const usernameInput = document.getElementById('edit_nickname');
            const emailInput = document.getElementById('edit_email');
            const avatarInput = document.getElementById('edit_avatar_input'); // Поле выбора файла

            if (!form || !idInput || !usernameInput || !emailInput || !avatarInput) {
                showAjaxMessage('Критическая ошибка: не найдены элементы формы.', 'error');
                return;
            }

            // Очищаем старые ошибки валидации
            clearAllFormErrors(form);
            let isValid = true;

            // Получаем значения полей
            const userIdVal = idInput.value; // Используем для проверки совпадения ID
            const usernameVal = usernameInput.value.trim();
            const emailVal = emailInput.value.trim();

             // --- 1. Frontend Валидация текстовых полей ---
            if (!usernameVal) { showFieldError(usernameInput, 'Никнейм обязателен.'); isValid = false; }
            else if (usernameVal.length < 3) { showFieldError(usernameInput, 'Минимум 3 символа.'); isValid = false; }
            else if (usernameVal.length > 50) { showFieldError(usernameInput, 'Максимум 50 символов.'); isValid = false; }
            else if (!/^[a-zA-Z0-9_]+$/.test(usernameVal)) { showFieldError(usernameInput, 'Латиница, цифры, _.'); isValid = false; }

            if (!emailVal) { showFieldError(emailInput, 'Email обязателен.'); isValid = false; }
            else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(emailVal)) { showFieldError(emailInput, 'Некорректный Email.'); isValid = false; }


            // --- 2. Frontend Валидация файла (если выбран) ---
             const fileSelected = avatarInput.files && avatarInput.files.length > 0;
            if(fileSelected) {
                const file = avatarInput.files[0];
                const allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
                const maxSize = 2 * 1024 * 1024; // 2 MB

                if (!allowedTypes.includes(file.type)) {
                     // Сообщение покажем через showAjaxMessage, т.к. нет прямого поля для ошибки файла
                     showAjaxMessage('Неверный тип файла аватара (JPG, PNG, GIF).', 'error');
                     // Можно добавить класс ошибки к .profile-picture-admin или .circle-admin для визуала
                     isValid = false;
                } else if (file.size > maxSize) {
                    showAjaxMessage('Файл аватара слишком большой (макс. 2 МБ).', 'error');
                     isValid = false;
                }
            }

             // --- 3. Проверка, были ли вообще изменения ---
            // Сравниваем текущие значения полей с исходными данными currentUserData
             const hasTextChanged = currentUserData && (currentUserData.username !== usernameVal || currentUserData.email !== emailVal);
            // Если НИ текст НЕ менялся, НИ новый файл НЕ выбран, то сохранять нечего
            if (!fileSelected && !hasTextChanged && isValid) { // Проверяем isValid, чтобы не выйти, если есть ошибки
                 showAjaxMessage('Нет изменений для сохранения.', 'info');
                 // Возвращаемся в режим просмотра через некоторое время
                 setTimeout(() => {
                    if (currentUserData && activeUserId == currentUserData.id) { // Доп. проверка, что юзер тот же
                       renderUserDetailsView(currentUserData);
                    }
                 }, 1500);
                 return;
            }


            // --- 4. Если валидация не пройдена ---
            if (!isValid) {
                 // Если еще не было показано AJAX сообщение об ошибке файла, покажем общее
                 if (!ajaxMessageContainer.querySelector('.ajax-message.error')) {
                    showAjaxMessage('Пожалуйста, исправьте ошибки в форме.', 'error');
                 }
                // Устанавливаем фокус на первое невалидное текстовое поле
                form.querySelector('.is-invalid')?.focus();
                return; // Прерываем сохранение
            }


            // --- 5. Подготовка и отправка данных (FormData) ---
            showAjaxMessage('Сохранение данных...', 'loading');

            // Создаем FormData для отправки (НЕ из <form> элемента напрямую, т.к. файл добавляем отдельно)
            const formData = new FormData();
            formData.append('userId', userIdVal); // Отправляем ID пользователя
            formData.append('username', usernameVal);
            formData.append('email', emailVal);

            // Добавляем файл аватара, ТОЛЬКО ЕСЛИ он был выбран
            if (fileSelected) {
                 formData.append('avatar', avatarInput.files[0]);
            }

            // Отправляем запрос на сервер
            fetch('update_user.php', {
                method: 'POST',
                body: formData // Отправляем объект FormData
                // ВАЖНО: НЕ УКАЗЫВАТЬ 'Content-Type'. Браузер сам установит
                // правильный 'multipart/form-data' с нужным boundary для файлов.
            })
            .then(response => {
                 // Обработка ответа сервера (аналогично loadUserDetails)
                 if (!response.ok) {
                     return response.text().then(text => {
                         let errorMsg = `Ошибка сети ${response.status}: ${response.statusText}.`;
                         try {
                             const data = JSON.parse(text);
                             if(data && data.error) { errorMsg += ` Сервер: ${data.error}`; } else { errorMsg += ` Ответ: ${text || '(пусто)'}`; }
                             if (data?.error?.includes("Доступ запрещен") || response.status === 401 || response.status === 403) { window.location.href = 'input.php'; }
                         } catch(e) {
                             errorMsg += ` Ответ: ${text || '(пусто)'}`;
                              if (response.status === 401 || response.status === 403) { window.location.href = 'input.php'; }
                         }
                         throw new Error(errorMsg);
                     });
                 }
                 return response.json(); // Ожидаем JSON в ответе
             })
            .then(data => {
                 // Обработка JSON ответа
                if (data.success) {
                     // Успешно сохранено!
                    showAjaxMessage(data.message || 'Данные успешно сохранены!', 'success');

                     // --- Обновление данных на клиенте ---
                     // 1. Обновляем глобальный объект currentUserData
                    const updatedUserDataFromServer = data.updatedUser || {}; // Данные из ответа сервера
                     currentUserData = {
                         ...currentUserData, // Берем старые данные за основу
                         username: usernameVal, // Обновляем ник
                         email: emailVal,       // Обновляем email
                         // Обновляем путь к аватару, если сервер его вернул (даже если он null)
                         // Используем data.newAvatarUrl если оно есть, иначе берем из updatedUser, иначе старое
                         avatar: data.newAvatarUrl !== undefined ? data.newAvatarUrl : (updatedUserDataFromServer.avatar !== undefined ? updatedUserDataFromServer.avatar : currentUserData.avatar)
                     };

                    // 2. Обновляем сайдбар (ник, email и аватар)
                    updateUserSidebarAvatar(userIdVal, currentUserData.avatar); // Обновить аватар
                    if (userListContainer) {
                        const sidebarItem = userListContainer.querySelector(`.user-list-item[data-userid="${userIdVal}"]`);
                        if (sidebarItem) {
                            // Обновляем текст ника и email в сайдбаре
                            sidebarItem.querySelector('.user-nickname').textContent = usernameVal;
                            sidebarItem.querySelector('.user-email').textContent = emailVal;
                        }
                    }

                    // 3. Переключаемся обратно в режим просмотра с обновленными данными
                    renderUserDetailsView(currentUserData);

                } else {
                     // Ошибка при сохранении на сервере
                    showAjaxMessage(`Ошибка сохранения: ${data.error || 'Неизвестная ошибка сервера.'}`, 'error');
                     // Показываем ошибки для конкретных полей, если сервер их вернул
                    if (data.field_errors) {
                        if (data.field_errors.username) showFieldError(usernameInput, data.field_errors.username);
                        if (data.field_errors.email) showFieldError(emailInput, data.field_errors.email);
                        // Фокус на первое поле с ошибкой
                        form.querySelector('.is-invalid')?.focus();
                    }
                     // Проверка на доступ
                    if (data.error && data.error.includes("Доступ запрещен")) { window.location.href = 'input.php'; }
                }
            })
            .catch(error => {
                // Ошибка сети или другая ошибка fetch
                console.error('Ошибка fetch при сохранении данных пользователя:', error);
                showAjaxMessage(`Ошибка сети при сохранении: ${error.message}`, 'error');
                 // Здесь НЕ переключаемся обратно в view mode, оставляем форму с ошибкой
            });
        }


        // Подтверждение и удаление пользователя (AJAX)
        function confirmDeleteUser(userId) {
            // Получаем ник пользователя для сообщения (экранируем!)
            const safeUserId = escapeHtml(String(userId));
            const userItem = userListContainer?.querySelector(`.user-list-item[data-userid="${userId}"]`);
            const username = userItem ? escapeHtml(userItem.querySelector('.user-nickname')?.textContent) : `ID ${safeUserId}`;

            // Запрашиваем подтверждение у администратора
            if (confirm(`Вы уверены, что хотите БЕЗВОЗВРАТНО удалить пользователя "${username}" (ID: ${safeUserId})?\n\nЭто действие НЕЛЬЗЯ будет отменить!`)) {
                // Если подтверждено, показываем индикатор и отправляем запрос
                showAjaxMessage('Удаление пользователя...', 'loading');
                const formData = new FormData();
                formData.append('userId', userId);
                 // Добавляем флаг, чтобы сервер знал, что нужно удалить и файл аватара
                 formData.append('deleteAvatar', 'true');

                fetch('delete_user.php', { method: 'POST', body: formData })
                .then(response => {
                    // Обработка ответа (аналогично другим запросам)
                     if (!response.ok) {
                         return response.text().then(text => {
                             let errorMsg = `Ошибка сети ${response.status}: ${response.statusText}.`;
                             try {
                                 const data = JSON.parse(text);
                                 if(data && data.error) { errorMsg += ` Сервер: ${data.error}`; } else { errorMsg += ` Ответ: ${text || '(пусто)'}`; }
                                 if (data?.error?.includes("Доступ запрещен") || response.status === 401 || response.status === 403) { window.location.href = 'input.php'; }
                             } catch(e) {
                                 errorMsg += ` Ответ: ${text || '(пусто)'}`;
                                  if (response.status === 401 || response.status === 403) { window.location.href = 'input.php'; }
                             }
                             throw new Error(errorMsg);
                         });
                     }
                     return response.json();
                 })
                .then(data => {
                    // Обработка JSON ответа
                    if (data.success) {
                        showAjaxMessage(data.message || 'Пользователь успешно удален.', 'success');
                        // Удаляем пользователя из сайдбара
                        removeUserFromSidebar(userId);
                        // Если был удален текущий активный пользователь, сбрасываем правую панель
                        if (activeUserId == userId) {
                            resetAdminContent();
                        }
                        // Очищаем сообщение об успехе через пару секунд
                        setTimeout(clearAjaxMessage, 3000);
                    } else {
                        // Ошибка при удалении на сервере
                        showAjaxMessage(`Ошибка удаления: ${data.error || 'Неизвестная ошибка сервера.'}`, 'error');
                         if (data.error && data.error.includes("Доступ запрещен")) { window.location.href = 'input.php'; }
                    }
                })
                .catch(error => {
                     // Ошибка сети
                    console.error('Ошибка fetch при удалении пользователя:', error);
                    showAjaxMessage(`Ошибка сети при удалении: ${error.message}`, 'error');
                });
            }
            // Если пользователь нажал "Отмена" в confirm(), ничего не делаем
        }

        // --- Инициализация Панели ---
        function initializeAdminPanel() {
             // Используем делегирование событий для кликов по списку пользователей
             if (userListContainer) {
                 userListContainer.addEventListener('click', (event) => {
                     // Находим ближайший родительский элемент '.user-list-item'
                     const targetItem = event.target.closest('.user-list-item');
                     if (!targetItem) return; // Клик был не по элементу списка

                     const userId = targetItem.getAttribute('data-userid');

                     if (userId) {
                         // Не перезагружаем, если кликнули по уже активному элементу
                         if (activeUserId === userId && userDetailsContainer.style.display !== 'none') {
                             console.log('User already selected:', userId);
                             return;
                         }

                         // Снимаем выделение с предыдущего активного элемента
                         const currentActive = userListContainer.querySelector('.user-list-item.active');
                         if (currentActive) {
                             currentActive.classList.remove('active');
                         }
                         // Выделяем новый элемент
                         targetItem.classList.add('active');

                         // Загружаем детали пользователя
                         loadUserDetails(userId);
                     } else {
                         // На всякий случай, если у элемента нет data-userid
                         console.error('Не удалось получить data-userid из элемента:', targetItem);
                         showAjaxMessage('Внутренняя ошибка: не удалось определить ID пользователя.', 'error');
                         resetAdminContent(); // Сбросить панель
                     }
                 });
             } else {
                 console.error("Элемент userListContainer (#userList) не найден. Не могу добавить обработчик.");
             }

             // Начальная установка при загрузке страницы
             resetAdminContent(); // Показываем приветствие или пустой экран
             console.log("Admin panel initialized.");
        }

        // --- Запуск инициализации после загрузки DOM ---
        document.addEventListener('DOMContentLoaded', initializeAdminPanel);

    </script>

</body>
</html>