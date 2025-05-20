<?php
// profile.php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Проверка, залогинен ли пользователь
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php"); // Перенаправить на страницу входа, если не залогинен
    exit();
}

require 'db.php'; // Подключение к БД

$user_id = $_SESSION['user_id'];

// 1. Получение информации о пользователе
$stmt_user = $conn->prepare("SELECT first_name, email, phone_number FROM users WHERE user_id = ?");
if (!$stmt_user) { die("Ошибка подготовки запроса (пользователь): " . $conn->error); }
$stmt_user->bind_param("i", $user_id);
$stmt_user->execute();
$result_user = $stmt_user->get_result();
$user = $result_user->fetch_assoc();
$stmt_user->close();

if (!$user) {
    // Если пользователь не найден в БД (маловероятно, но возможно)
    unset($_SESSION['user_id']); // Разлогинить
    $_SESSION['login_error'] = "Ошибка: пользователь не найден. Пожалуйста, войдите снова.";
    header("Location: login.php");
    exit();
}

// 2. Получение сохраненных пассажиров
$stmt_passengers = $conn->prepare("SELECT saved_passenger_id, first_name, last_name, middle_name, date_of_birth, gender, passenger_type, document_type, document_number, document_expiry_date, nationality_country_code FROM user_saved_passengers WHERE user_id = ? ORDER BY last_name, first_name");
if (!$stmt_passengers) { die("Ошибка подготовки запроса (пассажиры): " . $conn->error); }
$stmt_passengers->bind_param("i", $user_id);
$stmt_passengers->execute();
$result_passengers = $stmt_passengers->get_result();
$saved_passengers = [];
while ($row = $result_passengers->fetch_assoc()) {
    $saved_passengers[] = $row;
}
$stmt_passengers->close();

// Для удобства вывода и конвертации ENUM в читаемые значения
function getDocumentTypeName($doc_type) {
    $types = [
        'passport_intl' => 'Загранпаспорт',
        'passport_national' => 'Паспорт РФ', // Предположим
        'id_card' => 'ID-карта',
        'birth_certificate' => 'Свидетельство о рождении'
    ];
    return $types[$doc_type] ?? $doc_type;
}
function formatDate($date_str, $format = 'd.m.Y') {
    if (empty($date_str)) return '';
    $date = new DateTime($date_str);
    return $date->format($format);
}

?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Профиль - AirGO</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" integrity="sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46ZmdtAc9eNBvH0H/ZpiBw==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/profile-style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* Стили для сообщений об успехе/ошибке */
        .message { padding: 15px; margin-bottom: 20px; border-radius: 5px; text-align: center; font-weight: 500; }
        .message.success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .message.error   { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
    </style>
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

    <main class="profile-page-container">
        <h1>Ваш Профиль</h1>

        <?php if (isset($_SESSION['profile_message'])): ?>
            <div class="message <?php echo $_SESSION['profile_message_type']; ?>">
                <?php echo htmlspecialchars($_SESSION['profile_message']); ?>
            </div>
            <?php unset($_SESSION['profile_message'], $_SESSION['profile_message_type']); ?>
        <?php endif; ?>


        <section class="profile-section">
            <h2><i class="fas fa-user-circle icon-title"></i>Личная информация</h2>
            <div class="info-grid">
                <div class="info-item">
                    <label>Имя:</label>
                    <span class="value"><?php echo htmlspecialchars($user['first_name'] ?: 'Не указано'); ?></span>
                </div>
                <div class="info-item">
                    <label>Email:</label>
                    <span class="value"><?php echo htmlspecialchars($user['email']); ?></span>
                </div>
                <div class="info-item">
                    <label>Телефон:</label>
                    <span class="value"><?php echo htmlspecialchars($user['phone_number'] ?: 'Не указан'); ?></span>
                </div>
            </div>
            <a href="edit-profile.php" class="btn btn--edit"> <!-- Ссылка на страницу редактирования -->
                <i class="fas fa-pencil-alt"></i> Редактировать
            </a>
        </section>

        <section class="profile-section">
            <h2><i class="fas fa-users icon-title"></i>Сохраненные Пассажиры</h2>
            <p class="section-description">Добавьте данные пассажиров для быстрого бронирования.</p>
            <div class="saved-travelers-list">
                <?php if (empty($saved_passengers)): ?>
                    <div class="no-travelers">
                        <p>Вы еще не добавили ни одного пассажира.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($saved_passengers as $sp): ?>
                        <div class="traveler-card">
                            <div class="traveler-info">
                                <strong class="traveler-name">
                                    <?php echo htmlspecialchars(trim($sp['last_name'] . ' ' . $sp['first_name'] . ' ' . $sp['middle_name'])); ?>
                                </strong>
                                <?php if (!empty($sp['date_of_birth'])): ?>
                                <span><i class="fas fa-birthday-cake icon-text"></i><?php echo formatDate($sp['date_of_birth']); ?></span>
                                <?php endif; ?>
                                <?php if (!empty($sp['document_type']) && !empty($sp['document_number'])): ?>
                                <span>
                                    <i class="fas fa-passport icon-text"></i>
                                    <?php echo getDocumentTypeName($sp['document_type']) . ' ' . htmlspecialchars($sp['document_number']); ?>
                                    <?php if (!empty($sp['document_expiry_date'])): ?>
                                        (до <?php echo formatDate($sp['document_expiry_date']); ?>)
                                    <?php endif; ?>
                                </span>
                                <?php endif; ?>
                            </div>
                            <div class="traveler-actions">
                                <a href="edit-saved-passenger.php?id=<?php echo $sp['saved_passenger_id']; ?>" class="action-link action-link--edit" title="Редактировать"><i class="fas fa-edit"></i></a>
                                <a href="delete-saved-passenger.php?id=<?php echo $sp['saved_passenger_id']; ?>" class="action-link action-link--delete" title="Удалить" onclick="return confirm('Вы уверены, что хотите удалить этого пассажира?');"><i class="fas fa-trash"></i></a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <a href="add-saved-passenger.php" class="btn btn--primary btn--add-traveler"> <!-- Ссылка на страницу добавления -->
                <i class="fas fa-user-plus"></i> Добавить пассажира
            </a>
        </section>

        <section class="profile-section">
            <h2><i class="fas fa-shield-alt icon-title"></i>Безопасность</h2>
            <div class="security-actions">
                 <a href="change-password.php" class="btn btn--secondary"> <!-- Ссылка на страницу смены пароля -->
                     <i class="fas fa-key"></i> Изменить пароль
                 </a>
                 <a href="logout.php" class="btn btn--secondary"> <!-- Ссылка на скрипт выхода -->
                     <i class="fas fa-sign-out-alt"></i> Выход
                 </a>
                 <a href="delete-account.php" class="btn btn--danger" onclick="return confirm('ВНИМАНИЕ! Вы уверены, что хотите безвозвратно удалить свой аккаунт и все связанные с ним данные? Это действие нельзя будет отменить.');"> <!-- Ссылка на скрипт удаления -->
                     <i class="fas fa-user-slash"></i> Удалить аккаунт
                 </a>
            </div>
        </section>
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