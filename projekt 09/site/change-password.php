<?php
// change-password.php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Проверка, залогинен ли пользователь
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Восстановление данных формы при ошибке валидации
$current_password_val = isset($_SESSION['form_data']['current_password']) ? htmlspecialchars($_SESSION['form_data']['current_password']) : '';
$new_password_val = isset($_SESSION['form_data']['new_password']) ? htmlspecialchars($_SESSION['form_data']['new_password']) : '';
$confirm_new_password_val = isset($_SESSION['form_data']['confirm_new_password']) ? htmlspecialchars($_SESSION['form_data']['confirm_new_password']) : '';
unset($_SESSION['form_data']); // Очистить после использования
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Изменить пароль - AirGO</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" integrity="sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46ZmdtAc9eNBvH0H/ZpiBw==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/profile-style.css"> <!-- Используем общий стиль профиля, или forms-style.css -->
    <!-- Дополнительные стили для этой формы, если нужно, можно поместить сюда или в forms-style.css -->
     <style>
        .form-container { max-width: 500px; margin: 2rem auto; padding: 2rem; background: #fff; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .form-container h1 { text-align: center; margin-bottom: 1.5rem; color: var(--dark-green); }
        .form-group { margin-bottom: 1.2rem; }
        .form-group label { display: block; margin-bottom: 0.5rem; font-weight: 500; color: var(--dark-gray); }
        .form-group input[type="password"] {
            width: 100%; padding: 0.7rem; border: 1px solid var(--medium-gray); border-radius: 4px;
            font-size: 1rem; font-family: var(--font-family);
        }
        .form-actions { margin-top: 1.5rem; text-align: right; }
        .btn-submit { background-color: var(--primary-green); color:white; }
        .btn-cancel { background-color: var(--light-gray); color: var(--dark-gray); margin-right: 0.5rem; border: 1px solid var(--medium-gray); }

        .message { padding: 10px; margin-bottom: 15px; border-radius: 5px; text-align: center; font-weight: 500; }
        .message.error   { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .message.success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }

        .password-requirements { font-size: 0.85em; color: var(--dark-gray); margin-top: 5px; margin-bottom: 10px; padding-left: 5px; }
    </style>
</head>
<body>
    <header class="header">
        <div class="container header__container">
            <a href="index-log.php" class="header__logo">AirGO</a>
            <nav class="header__nav">
                <ul><li><a href="profile.php">Назад в профиль</a></li></ul>
            </nav>
        </div>
    </header>

    <main>
        <div class="form-container">
            <h1><i class="fas fa-key icon-title"></i> Изменить пароль</h1>

            <?php if (isset($_SESSION['change_password_error'])): ?>
            <div class="message error">
                <?php echo htmlspecialchars($_SESSION['change_password_error']); ?>
            </div>
            <?php unset($_SESSION['change_password_error']); ?>
            <?php endif; ?>

            <?php if (isset($_SESSION['change_password_success'])): ?>
            <div class="message success">
                <?php echo htmlspecialchars($_SESSION['change_password_success']); ?>
            </div>
            <?php unset($_SESSION['change_password_success']); ?>
            <?php endif; ?>

            <form action="handle-change-password.php" method="POST">
                <div class="form-group">
                    <label for="current_password">Текущий пароль:</label>
                    <input type="password" id="current_password" name="current_password" value="<?php echo $current_password_val; ?>" required>
                </div>
                <div class="form-group">
                    <label for="new_password">Новый пароль:</label>
                    <input type="password" id="new_password" name="new_password" value="<?php echo $new_password_val; ?>" required>
                     <div class="password-requirements">
                        Минимум 8 символов, 1 заглавная, 1 строчная, 1 цифра, 1 спецсимвол.
                    </div>
                </div>
                <div class="form-group">
                    <label for="confirm_new_password">Подтвердите новый пароль:</label>
                    <input type="password" id="confirm_new_password" name="confirm_new_password" value="<?php echo $confirm_new_password_val; ?>" required>
                </div>
                <div class="form-actions">
                    <a href="profile.php" class="btn btn-cancel">Отмена</a>
                    <button type="submit" class="btn btn-submit"><i class="fas fa-save"></i> Сохранить новый пароль</button>
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