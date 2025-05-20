<?php
// admin.php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Проверка аутентификации и прав администратора
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    $_SESSION['login_error'] = "У вас нет прав для доступа к панели администратора.";
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
    } else {
        header("Location: index.php");
    }
    exit();
}

require 'db.php'; 
$admin_id = $_SESSION['user_id']; // ID текущего админа
$admin_email_display = $_SESSION['user_email'] ?? "admin@example.com"; 
$admin_name_display = $_SESSION['user_first_name'] ?? "Администратор";   

// Обновляем $admin_name_display из БД, если оно там актуальнее
$stmt_admin_data_check = $conn->prepare("SELECT first_name FROM users WHERE user_id = ?");
if($stmt_admin_data_check){
    $stmt_admin_data_check->bind_param("i", $admin_id);
    $stmt_admin_data_check->execute();
    $result_admin_data_check = $stmt_admin_data_check->get_result();
    if($current_admin_data_result = $result_admin_data_check->fetch_assoc()){
        if (!empty($current_admin_data_result['first_name'])) {
            $admin_name_display = $current_admin_data_result['first_name'];
        }
    }
    $stmt_admin_data_check->close();
}


// Сообщение об успехе/ошибке для операций профиля администратора
$profile_message = '';
$profile_message_type = ''; 
if (isset($_SESSION['admin_profile_message'])) {
    $profile_message = $_SESSION['admin_profile_message'];
    $profile_message_type = $_SESSION['admin_profile_message_type'] ?? 'info';
    unset($_SESSION['admin_profile_message'], $_SESSION['admin_profile_message_type']);
}

// Определяем активную вкладку
// ДОБАВЛЕНА 'special-offers-content' в валидные вкладки
$valid_tabs = ['profile-content', 'users-content', 'bookings-content', 'special-offers-content', 'stats-content']; 
$active_tab_id = 'profile-content'; // По умолчанию

// Пытаемся получить вкладку из GET-параметра
if (isset($_GET['tab']) && in_array($_GET['tab'], $valid_tabs)) {
    $active_tab_id = $_GET['tab'];
    // Сохраняем в localStorage для JS, чтобы он не переключал обратно при первой загрузке
    // Эта строка будет выполняться только при серверной генерации страницы, что нормально
    echo "<script>if(typeof localStorage !== 'undefined') localStorage.setItem('activeAdminTab', '" . htmlspecialchars($active_tab_id, ENT_QUOTES, 'UTF-8') . "');</script>";
} elseif (isset($_COOKIE['activeAdminTab']) && in_array($_COOKIE['activeAdminTab'], $valid_tabs)) {
    // Если нет GET, пытаемся из Cookie
    $active_tab_id = $_COOKIE['activeAdminTab'];
}
// Примечание: установка cookie `setcookie('activeAdminTab', ...)` здесь может быть избыточной,
// если мы успешно управляем состоянием через GET-параметры и localStorage.

?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Панель Администратора - Air GO</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" integrity="sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46ZmdtAc9eNBvH0H/ZpiBw==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="css/admin-style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="admin-wrapper">
        <aside class="admin-sidebar">
            <div class="sidebar-header">
                <a href="index.php" class="logo">Air GO <span class="admin-tag">Admin</span></a>
            </div>
            <nav class="sidebar-nav">
                <ul>
                    <li class="<?php echo ($active_tab_id == 'profile-content') ? 'active' : ''; ?>">
                        <a href="admin.php?tab=profile-content" data-tab="profile-content"><i class="fas fa-user-shield"></i> Профиль</a>
                    </li>
                    <li class="<?php echo ($active_tab_id == 'users-content') ? 'active' : ''; ?>">
                        <a href="admin.php?tab=users-content" data-tab="users-content"><i class="fas fa-users-cog"></i> Пользователи</a>
                    </li>
                    <li class="<?php echo ($active_tab_id == 'bookings-content') ? 'active' : ''; ?>">
                        <a href="admin.php?tab=bookings-content" data-tab="bookings-content"><i class="fas fa-plane-departure"></i> Бронирования</a>
                    </li>
                    <!-- НОВЫЙ ПУНКТ В НАВИГАЦИИ -->
                    <li class="<?php echo ($active_tab_id == 'special-offers-content') ? 'active' : ''; ?>">
                        <a href="admin.php?tab=special-offers-content" data-tab="special-offers-content"><i class="fas fa-tags"></i> Спецпредложения</a>
                    </li>
                    <li class="<?php echo ($active_tab_id == 'stats-content') ? 'active' : ''; ?>">
                        <a href="admin.php?tab=stats-content" data-tab="stats-content"><i class="fas fa-chart-bar"></i> Статистика</a>
                    </li>
                </ul>
            </nav>
            <div class="sidebar-footer">
                <a href="logout.php" class="logout-link"><i class="fas fa-sign-out-alt"></i> Выход</a>
            </div>
        </aside>

        <main class="admin-main-content">
            <header class="admin-content-header">
                <h1>Панель Управления</h1>
                <div class="admin-user-info">
                    <span><i class="fas fa-user-circle"></i> <?php echo htmlspecialchars($admin_email_display); ?></span>
                </div>
            </header>

            <div class="admin-tabs-content">
                <section id="profile-content" class="tab-pane <?php echo ($active_tab_id == 'profile-content') ? 'active' : ''; ?>">
                    <h2>Профиль Администратора</h2>
                    <?php if ($profile_message && $active_tab_id == 'profile-content'): ?>
                        <div class="admin-message <?php echo htmlspecialchars($profile_message_type); ?>">
                            <?php echo htmlspecialchars($profile_message); ?>
                        </div>
                    <?php endif; ?>
                    <p>Здесь вы можете изменить свое имя и перейти к смене пароля.</p>
                    <form action="admin_actions/handle_edit_admin_profile.php" method="POST" class="profile-form-admin">
                        <div class="form-group">
                            <label for="admin-name">Имя Администратора:</label>
                            <input type="text" id="admin-name" name="admin_first_name" 
                                   value="<?php echo htmlspecialchars($admin_name_display); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="admin-email">Email (нельзя изменить здесь):</label>
                            <input type="email" id="admin-email" name="admin_email_readonly" 
                                   value="<?php echo htmlspecialchars($admin_email_display); ?>" readonly>
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Сохранить имя
                        </button>
                        <a href="admin_actions/change_admin_password.php" class="btn btn-secondary">
                            <i class="fas fa-key"></i> Изменить пароль
                        </a>
                    </form>
                </section>

                <section id="users-content" class="tab-pane <?php echo ($active_tab_id == 'users-content') ? 'active' : ''; ?>">
                    <?php 
                    if ($active_tab_id == 'users-content') {
                        if (file_exists('admin_tabs/users_tab.php')) {
                            include 'admin_tabs/users_tab.php'; 
                        } else {
                            echo "<h2>Управление Пользователями</h2><p>Ошибка: файл отображения пользователей не найден.</p>";
                        }
                    }
                    ?>
                </section>

                <section id="bookings-content" class="tab-pane <?php echo ($active_tab_id == 'bookings-content') ? 'active' : ''; ?>">
                    <h2>Управление Бронированиями</h2>
                    <div class="placeholder-content">
                        <i class="fas fa-suitcase-rolling fa-3x"></i>
                        <p>Список бронирований, поиск и управление.</p>
                         <!-- Это раздел для фактических бронирований, а не для создания "предложений" типа карточек -->
                    </div>
                </section>
                
                <!-- НОВАЯ СЕКЦИЯ ДЛЯ КОНТЕНТА ВКЛАДКИ "СПЕЦПРЕДЛОЖЕНИЯ" -->
                <section id="special-offers-content" class="tab-pane <?php echo ($active_tab_id == 'special-offers-content') ? 'active' : ''; ?>">
                     <?php 
                    if ($active_tab_id == 'special-offers-content') { 
                        // Убедимся, что файл будет существовать в admin_tabs/
                        if (file_exists('admin_tabs/special_offers_tab.php')) {
                            include 'admin_tabs/special_offers_tab.php'; 
                        } else {
                            echo "<h2>Управление Спецпредложениями</h2><p>Ошибка: файл отображения спецпредложений ('admin_tabs/special_offers_tab.php') не найден.</p>";
                        }
                    }
                    ?>
                </section>

                <section id="stats-content" class="tab-pane <?php echo ($active_tab_id == 'stats-content') ? 'active' : ''; ?>">
                    <h2>Статистика и Отчеты</h2>
                     <div class="placeholder-content">
                        <i class="fas fa-chart-line fa-3x"></i>
                        <p>Графики и отчеты будут здесь.</p>
                    </div>
                </section>
            </div>
        </main>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const navLinks = document.querySelectorAll('.sidebar-nav a[data-tab]');
            // const tabPanes = document.querySelectorAll('.admin-tabs-content .tab-pane'); // Не используется для активации здесь
            // const navListItems = document.querySelectorAll('.sidebar-nav li'); // Не используется для активации здесь
            
            navLinks.forEach(link => {
                link.addEventListener('click', (event) => {
                    // event.preventDefault(); // НЕ используем, т.к. ссылки теперь серверные с GET параметром
                    const targetTabId = link.getAttribute('data-tab');
                    if(typeof localStorage !== 'undefined') {
                        localStorage.setItem('activeAdminTab', targetTabId);
                    }
                });
            });
            
            // PHP уже установил активную вкладку. JS может просто убедиться, что localStorage синхронизирован.
            const initialActiveTabFromPHP = "<?php echo $active_tab_id; ?>";
            if (initialActiveTabFromPHP && typeof localStorage !== 'undefined') {
                 localStorage.setItem('activeAdminTab', initialActiveTabFromPHP);
            }
            
            // Логика обработки якоря (#) может быть не нужна или упрощена,
            // т.к. GET параметр 'tab' теперь главный для определения активной вкладки при загрузке.
            // Если после редиректа (например, из формы сохранения) будет якорь в URL,
            // браузер попытается проскроллить к нему, что обычно и нужно.
            // if (window.location.hash) {
            //     const hashTabId = window.location.hash.substring(1); 
            //     const validTabIds = <?php echo json_encode($valid_tabs); ?>;
            //     if (validTabIds.includes(hashTabId) && document.getElementById(hashTabId)) {
            //          // Этот блок теперь может быть не нужен
            //     }
            // }
        });
    </script>
</body>
</html>