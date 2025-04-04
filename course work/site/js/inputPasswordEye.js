function togglePassword() {
    const passwordInput = document.getElementById("password");
    const toggleIcon = document.querySelector(".toggle-password");

    if (passwordInput.type === "password") {
        passwordInput.type = "text";
        toggleIcon.textContent = "🙈"; // Меняем иконку
    } else {
        passwordInput.type = "password";
        toggleIcon.textContent = "👁️"; // Возвращаем глаз
    }
}
