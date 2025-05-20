<!-- signup.php -->
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Регистрация - AirGO</title>
    <link rel="stylesheet" href="css/signup-style.css"> <!-- Стили для Air GO -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Дополнительный CSS для сообщений об ошибках JS, если нужно -->
    <style>
        .error-message-js { /* Стили для сообщений об ошибках от JS */
            color: #dc3545; /* Красный цвет */
            font-size: 0.85em;
            margin-top: 5px;
            display: none; /* По умолчанию скрыты */
        }
        .password-container { /* Ваш стиль для контейнера пароля */
            position: relative;
            margin-bottom: 1.2rem; /* Стандартный отступ, как у .form-group */
        }
        .password-container input[type="password"],
        .password-container input[type="text"] { /* Чтобы текстовое поле выглядело так же */
            width: 100%;
            padding: 0.8rem 2.5rem 0.8rem 1rem; /* Доп. место справа для иконки */
            border: 1px solid var(--medium-gray); /* Используем переменные из signup-style.css */
            border-radius: 4px;
            font-size: 1rem;
            font-family: var(--font-family);
        }
        .password-container input[type="password"]:focus,
        .password-container input[type="text"]:focus {
             outline: none;
             border-color: var(--primary-green);
             box-shadow: 0 0 0 3px rgba(40, 167, 69, 0.15);
        }
        .toggle-password, .toggle-password2 { /* Ваши стили для иконки глаза */
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            user-select: none; /* Предотвратить выделение текста иконки */
        }
    </style>
</head>
<body>
    <div class="signup-container">
        <a href="index.php" class="logo">AirGO</a>
        <h1>Создайте аккаунт AirGO</h1>
        <p class="subtitle">Присоединяйтесь к нам и путешествуйте выгодно!</p>

        <?php
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        // Блок для серверных ошибок
        if (isset($_SESSION["signup_error"])): ?>
            <div class="server-error-message" style="display:block; background-color: #f8d7da; color: #721c24; padding: 10px; border-radius: 5px; margin-bottom: 15px;">
                <?php echo htmlspecialchars($_SESSION["signup_error"]); ?>
            </div>
            <?php unset($_SESSION["signup_error"]); ?>
        <?php endif; ?>
        <?php // Блок для серверного успеха (на этой странице маловероятен, т.к. будет редирект)
        if (isset($_SESSION["signup_success"])): ?>
            <div class="server-success-message" style="display:block; background-color: #d4edda; color: #155724; padding: 10px; border-radius: 5px; margin-bottom: 15px;">
                <?php echo htmlspecialchars($_SESSION["signup_success"]); ?>
            </div>
            <?php unset($_SESSION["signup_success"]); ?>
        <?php endif; ?>

        <form action="handle_signup.php" method="POST" class="signup-form" id="signupForm">
            <div class="form-group">
                <label for="first_name">Имя</label>
                <input type="text" id="first_name" name="first_name" placeholder="Ваше имя" required
                       value="<?php echo isset($_SESSION['form_data']['first_name']) ? htmlspecialchars($_SESSION['form_data']['first_name']) : ''; ?>">
                <!-- Ваш JS требует `id="username"`, изменил id на `first_name`, вам нужно будет адаптировать JS или это поле -->
                <!-- Для примера я оставлю здесь first_name, а JS можно доработать, или переименовать поле -->
                 <div id="username-error" class="error-message-js"></div> <!-- Блок для ошибки JS -->
            </div>

            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" placeholder="Введите ваш email" required
                       value="<?php echo isset($_SESSION['form_data']['email']) ? htmlspecialchars($_SESSION['form_data']['email']) : ''; ?>">
                <div id="email-error" class="error-message-js"></div> <!-- Блок для ошибки JS -->
            </div>

            <!-- Используем вашу структуру для полей пароля -->
            <label for="password">Пароль</label>
            <div class="password-container">
                <input type="password" id="password" name="password" placeholder="Придумайте надежный пароль" required>
                <span class="toggle-password" onclick="togglePassword('password')">👁️</span>
            </div>
            <div id="password-error" class="error-message-js"></div> <!-- Блок для ошибки JS -->

            <label for="confirm-password">Подтвердите пароль</label>
            <div class="password-container">
                <input type="password" id="confirm-password" name="confirm_password" placeholder="Повторите пароль" required>
                <span class="toggle-password2" onclick="togglePassword('confirm-password')">👁️</span>
                 <!-- Убедитесь, что ID 'confirm-password' соответствует тому, что ожидает JS для toggle -->
            </div>
            <div id="match-error" class="error-message-js"></div> <!-- Блок для ошибки JS -->


            <div class="form-group form-group--checkbox">
                <input type="checkbox" id="terms" name="terms" class="custom-checkbox" required>
                <!-- Убедитесь, что ваш CSS (`signup-style.css`) имеет стили для `.custom-checkbox`, или используйте стандартный -->
                <label for="terms" class="custom-label">
                    Я согласен с <a href="/terms.html" target="_blank">Условиями использования</a> и <a href="/privacy.html" target="_blank">Политикой конфиденциальности</a> AirGO.
                </label>
            </div>

            <button type="submit" class="btn-signup" id="registerButton" disabled>Зарегистрироваться</button>
        </form>

        <div class="login-link">
            <p>Уже есть аккаунт? <a href="login.php">Войти</a></p>
        </div>
    </div>

    <?php unset($_SESSION['form_data']); ?>

    <script>
        document.addEventListener("DOMContentLoaded", function () {
            const firstNameInput = document.getElementById("first_name"); // Изменено с username на first_name
            const usernameError = document.getElementById("username-error"); // Оставил ваш ID, чтобы было видно
            const emailInput = document.getElementById("email");
            const emailError = document.getElementById("email-error");
            const passwordInput = document.getElementById("password");
            const passwordError = document.getElementById("password-error");
            const repeatPasswordInput = document.getElementById("confirm-password"); // Изменил ID на confirm-password
            const matchError = document.getElementById("match-error");
            const termsCheckbox = document.getElementById("terms");
            const registerButton = document.getElementById("registerButton"); // Изменил ID кнопки

            function updateRegisterButtonState() {
                // Кнопка активна, если все поля прошли базовую JS валидацию И чекбокс отмечен
                // Эта проверка может быть сложнее, если учитывать видимость всех error-message-js
                registerButton.disabled = !termsCheckbox.checked ||
                                           usernameError.style.display === "block" ||
                                           emailError.style.display === "block" ||
                                           passwordError.style.display === "block" ||
                                           matchError.style.display === "block" ||
                                           firstNameInput.value.trim() === '' || // Проверка на пустое имя
                                           emailInput.value.trim() === '' ||
                                           passwordInput.value.trim() === '' ||
                                           repeatPasswordInput.value.trim() === '';
            }

            termsCheckbox.addEventListener("change", updateRegisterButtonState);


            // Ваш JS для валидации имени пользователя (адаптирован для поля 'Имя')
            function validateFirstName() { // Переименована функция
                const nameValue = firstNameInput.value.trim();
                // Простая проверка на непустое имя, вы можете добавить более сложную
                // Ваша старая проверка была для username (никнейм), для простого имени она избыточна.
                // Оставим ее пока так, но можно заменить на /^[а-яА-ЯёЁa-zA-Z\s-]{2,50}$/ для русских/англ. имен
                const namePattern = /^(?=.*[a-zA-Zа-яА-ЯёЁ])[a-zA-Zа-яА-ЯёЁ0-9_]{2,50}$/; // Пока что старый шаблон, но с длиной 2-50

                if (nameValue === "") {
                    usernameError.textContent = "Поле 'Имя' не должно быть пустым.";
                    usernameError.style.display = "block";
                } else if (!namePattern.test(nameValue)) {
                    usernameError.textContent = "Имя должно содержать 2-50 символов и состоять из букв (возможно цифр, но без спец. символов, кроме '_').";
                     // ^^^ Подправьте это сообщение под ваш шаблон, если нужно
                    usernameError.style.display = "block";
                }
                 else {
                    usernameError.style.display = "none";
                }
                updateRegisterButtonState();
            }
            firstNameInput.addEventListener("input", validateFirstName);


            function validateEmail() {
                const email = emailInput.value.trim();
                const emailPattern = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
                if (email === "") {
                    emailError.textContent = "Поле 'Email' не должно быть пустым.";
                    emailError.style.display = "block";
                } else if (!emailPattern.test(email)) {
                    emailError.textContent = "Введите корректный email (например, user@example.com).";
                    emailError.style.display = "block";
                } else {
                    emailError.style.display = "none";
                }
                updateRegisterButtonState();
            }
            emailInput.addEventListener("input", validateEmail);

            function validatePasswords() {
                const password = passwordInput.value; // Не тримим пароль
                const repeatPassword = repeatPasswordInput.value;
                const passwordPattern = /^(?=.*[A-Z])(?=.*[a-z])(?=.*\d)(?=.*[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?~`])[A-Za-z\d!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?~`]{8,50}$/;

                // Проверка основного пароля
                if (password === "") {
                     passwordError.textContent = "Поле 'Пароль' не должно быть пустым.";
                     passwordError.style.display = "block";
                } else if (!passwordPattern.test(password)) {
                    passwordError.textContent = "Пароль: 8-50 симв., мин. 1 загл., 1 строч., 1 цифра, 1 спецсимвол.";
                    passwordError.style.display = "block";
                } else {
                    passwordError.style.display = "none";
                }

                // Проверка подтверждения пароля
                if (repeatPassword === "") {
                    if (password !== "") { // Показываем ошибку, только если основной пароль введен
                         matchError.textContent = "Поле 'Подтвердите пароль' не должно быть пустым.";
                         matchError.style.display = "block";
                    } else {
                         matchError.style.display = "none";
                    }
                } else if (password !== repeatPassword) {
                    matchError.textContent = "Пароли не совпадают!";
                    matchError.style.display = "block";
                } else {
                    matchError.style.display = "none";
                }
                updateRegisterButtonState();
            }
            passwordInput.addEventListener("input", validatePasswords);
            repeatPasswordInput.addEventListener("input", validatePasswords);


            // Ваша функция переключения видимости пароля
            window.togglePassword = function (inputId) {
                const targetInput = document.getElementById(inputId);
                const toggleIcon = targetInput.nextElementSibling; // Иконка должна быть следующим элементом

                if (targetInput) {
                    targetInput.type = targetInput.type === "password" ? "text" : "password";
                    if (toggleIcon) {
                         toggleIcon.textContent = targetInput.type === "password" ? "👁️" : "🙈";
                    }
                }
            };

            // Первоначальная проверка при загрузке (на случай, если браузер запомнил значения)
            validateFirstName();
            validateEmail();
            validatePasswords();
            updateRegisterButtonState(); // Изначально кнопка будет заблокирована, т.к. чекбокс не нажат

        });
    </script>
</body>
</html>