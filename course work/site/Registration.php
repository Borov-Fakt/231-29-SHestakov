<!DOCTYPE html> 
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Регистрация - FinanceExpert</title>
    <link rel='stylesheet' href='css/Registration.css'>
</head>

<body>

    <div class="container">
        <h1>FinanceExpert</h1>
        <h2>Регистрация</h2>

        <?php session_start(); ?>
        <div id="error-message" class="error-message" style="display: <?php echo isset($_SESSION["error"]) ? 'block' : 'none'; ?>">
            <?php
            if (isset($_SESSION["error"])) {
                echo $_SESSION["error"];
                unset($_SESSION["error"]); // Очищаем сообщение после отображения
            }
            ?>
        </div>

        <form action="register.php" action="register.php" method="post">
            <input type="text" id="username" placeholder="Никнейм" name="username" oninput="validateUsername()" required>
            <div id="username-error" class="error-message"></div> 

            <input type="email" id="email" placeholder="Почта" name="email" onclick="validateEmail()" required>
            <div id="email-error" class="error-message"></div>

            <div class="password-container">
                <input type="password" id="password" placeholder="Пароль" name="pass" oninput="validatePassword()" required>
                <span class="toggle-password" onclick="togglePassword('password')">👁️</span>
            </div>
            <div id="password-error" class="error-message"></div>

            <div class="password-container">
                <input type="password" id="repeatPassword" placeholder="Подтвердите пароль" name="repeatpass" oninput= "checkPasswordMatch()" required>
                <span class="toggle-password2" onclick="togglePassword('repeatPassword')">👁️</span>
            </div>
            <div id="match-error" class="error-message"></div>

            <input type="checkbox" id="checkbox1" name="terms" class="custom-checkbox">
            <label for="checkbox1" class="custom-label">
                Я принимаю все&nbsp;<a href="https://docs.google.com/document/d/1OrWTHqaBTPGb3D-hhJVhyOPvQafcp_1pXU_okWMQ9EY/edit?usp=drivesdk" target="_blank"> условия пользования</a> 
            </label>    

            <button type="submit" id="register" class="register-btn" disabled>Зарегистрироваться</button>

            <div>
                <button type="button" onclick="location.href='input.php'" class="small-btn">У меня есть аккаунт</button>
            </div>
        </form>
    </div>
    <script src="js/Registration.js"></script>

</body>
</html>
