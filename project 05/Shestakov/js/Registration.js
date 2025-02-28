document.addEventListener("DOMContentLoaded", function () {
    const passwordInput = document.getElementById("password");
    const repeatPasswordInput = document.getElementById("repeatPassword");
    const termsCheckbox = document.getElementById("checkbox1");
    const registerButton = document.getElementById("register");
    const passwordError = document.getElementById("password-error");
    const matchError = document.getElementById("match-error");
    const usernameInput = document.getElementById("username");
    const usernameError = document.getElementById("username-error");
    const emailInput = document.getElementById("email");
    const emailError = document.getElementById("email-error");

    // функция проверки email
    function validateEmail() {
        const email = emailInput.value;
        const emailPattern = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
        // формат: username@domain.com

        if (!emailPattern.test(email)) {
            emailError.textContent = "Введите корректный email!";
            emailError.style.display = "block";
        } else {
            emailError.style.display = "none";
        }
    }



    // функция проверки никнейма
    function validateUsername() {
        const username = usernameInput.value;
        const usernamePattern = /^(?=.*[a-zA-Z])[a-zA-Z0-9_]{3,16}$/; 

        if (!usernamePattern.test(username)) {
            usernameError.textContent = "Никнейм должен содержать 3-16 символов, только латинские буквы, цифры и не иметь пробелов";
            usernameError.style.display = "block";
        } else {
            usernameError.style.display = "none";
        }
    }

    // Блокируем/разблокируем кнопку только по чекбоксу
    termsCheckbox.addEventListener("change", function () {
        registerButton.disabled = !this.checked;
    });

    // Функция валидации пароля (только для отображения ошибок)
    function validatePassword() {
        const password = passwordInput.value;
        const repeatPassword = repeatPasswordInput.value;

        // Регулярное выражение для проверки пароля
        const passwordPattern = /^(?=.*[A-Z])(?=.*[a-z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/;

        if (!passwordPattern.test(password)) {
            passwordError.textContent = "Пароль должен содержать минимум 8 символов, одну заглавную букву, одну цифру, один спецсимвол и не содержать пробелы!";
            passwordError.style.display = "block";
        } else {
            passwordError.style.display = "none";
        }

        // Проверка совпадения пароля
        if (repeatPassword !== "" && password !== repeatPassword) {
            matchError.textContent = "Пароли не совпадают!";
            matchError.style.display = "block";
        } else {
            matchError.style.display = "none";
        }
    }

    passwordInput.addEventListener("input", validatePassword);
    repeatPasswordInput.addEventListener("input", validatePassword);
    usernameInput.addEventListener("input", validateUsername);
    emailInput.addEventListener("input", validateEmail);

    // Функция переключения видимости пароля
    window.togglePassword = function (inputId) {
        const passwordInput = document.getElementById(inputId);
        const toggleIcon = passwordInput.nextElementSibling;

        passwordInput.type = passwordInput.type === "password" ? "text" : "password";
        toggleIcon.textContent = passwordInput.type === "password" ? "👁️" : "🙈";
    };
});