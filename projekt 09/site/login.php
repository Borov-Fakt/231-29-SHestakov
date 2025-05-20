<!-- login.php -->
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Вход - AirGO</title>
    <link rel="stylesheet" href="css/login-style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="login-container">
        <a href="index.php" class="logo">AirGO</a>
        <h1>Вход в ваш аккаунт</h1>

        <?php
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        if (isset($_SESSION["login_error"])): ?>
            <div class="error-message" style="display:block; background-color: #f8d7da; color: #721c24; padding: 10px; border-radius: 5px; margin-bottom: 15px;">
                <?php echo htmlspecialchars($_SESSION["login_error"]); ?>
            </div>
            <?php unset($_SESSION["login_error"]); // Очищаем ошибку после отображения ?>
        <?php endif; ?>

        <!-- ИЗМЕНЕН action -->
        <form action="handle_login.php" method="POST" class="login-form">
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" placeholder="Введите ваш email" required
                       value="<?php echo isset($_SESSION['form_data']['email']) ? htmlspecialchars($_SESSION['form_data']['email']) : ''; ?>">
            </div>
            <div class="form-group">
                <label for="password">Пароль</label>
                <input type="password" id="password" name="password" placeholder="Введите ваш пароль" required>
            </div>
            <button type="submit" class="btn-login">Войти</button>
        </form>

        <div class="login-links">
            <p class="signup-link">Нет аккаунта? <a href="signup.php">Зарегистрироваться</a></p>
        </div>
    </div>
    <?php unset($_SESSION['form_data']); // Очищаем сохраненные данные формы ?>
</body>
</html>