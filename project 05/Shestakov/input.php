<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FinanceExpert</title>
    <link rel='stylesheet' href='css/input.css'>
</head>
<body>
    <div class="container">
        <h1>FinanceExpert</h1>
        <h2>Войти</h2>

        <?php session_start(); ?>
        <div id="error-message" class="error-message" style="display: <?php echo isset($_SESSION["error"]) ? 'block' : 'none'; ?>">
            <?php
            if (isset($_SESSION["error"])) {
                echo $_SESSION["error"];
                unset($_SESSION["error"]); // Очищаем сообщение после отображения
            }
            ?>
        </div>

        <form id="login-form" action="login.php" method="post">
            <input type="text" id="username_or_email" name="username_or_email" placeholder="Никнейм/Email" required>

            <div class="password-container">
                <input type="password" id="password" name="pass" placeholder="Пароль" required>
                <span class="toggle-password" onclick="togglePassword()">👁️</span>
            </div>
            <button type="submit" class="login-btn">Вход</button>
            <button type="button" onclick="location.href='Registration.php'" class="small-btn">У меня нет аккаунта</button>
        </form>
    </div> 

    <script src="js/inputPasswordEye.js"></script>

</body>
</html>

 