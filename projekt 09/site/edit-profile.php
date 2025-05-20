<?php
// edit-profile.php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
require 'db.php';
$user_id = $_SESSION['user_id'];

// Получаем текущие данные пользователя для предзаполнения формы
$stmt = $conn->prepare("SELECT first_name, email, phone_number FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if (!$user) {
    $_SESSION['profile_message'] = "Ошибка: пользователь не найден.";
    $_SESSION['profile_message_type'] = "error";
    header("Location: profile.php");
    exit();
}

// Восстановление данных формы при ошибке валидации
$form_first_name = isset($_SESSION['form_data']['first_name']) ? htmlspecialchars($_SESSION['form_data']['first_name']) : htmlspecialchars($user['first_name']);
$form_email = isset($_SESSION['form_data']['email']) ? htmlspecialchars($_SESSION['form_data']['email']) : htmlspecialchars($user['email']);
$form_phone_number = isset($_SESSION['form_data']['phone_number']) ? htmlspecialchars($_SESSION['form_data']['phone_number']) : htmlspecialchars($user['phone_number']);
unset($_SESSION['form_data']); // Очистить после использования
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Редактировать профиль - AirGO</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" integrity="sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46ZmdtAc9eNBvH0H/ZpiBw==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/profile-style.css"> <!-- Можно использовать или создать свой для форм -->
    <link rel="stylesheet" href="css/forms-style.css"> <!-- Специальный CSS для таких форм, если нужен -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
     <style>
        .form-container { max-width: 600px; margin: 2rem auto; padding: 2rem; background: #fff; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .form-container h1 { text-align: center; margin-bottom: 1.5rem; color: var(--dark-green); }
        .form-group { margin-bottom: 1.2rem; }
        .form-group label { display: block; margin-bottom: 0.5rem; font-weight: 500; color: var(--dark-gray); }
        .form-group input[type="text"], .form-group input[type="email"] {
            width: 100%; padding: 0.7rem; border: 1px solid var(--medium-gray); border-radius: 4px;
            font-size: 1rem; font-family: var(--font-family);
        }
        .form-actions { margin-top: 1.5rem; text-align: right; }
        .btn-submit { background-color: var(--primary-green); color:white; }
        .btn-cancel { background-color: var(--light-gray); color: var(--dark-gray); margin-right: 0.5rem; border: 1px solid var(--medium-gray); }

        .message { padding: 10px; margin-bottom: 15px; border-radius: 5px; text-align: center; font-weight: 500; }
        .message.error   { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
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
            <h1>Редактировать личную информацию</h1>

            <?php if (isset($_SESSION['edit_profile_error'])): ?>
            <div class="message error">
                <?php echo htmlspecialchars($_SESSION['edit_profile_error']); ?>
            </div>
            <?php unset($_SESSION['edit_profile_error']); ?>
            <?php endif; ?>

            <form action="handle-edit-profile.php" method="POST">
                <div class="form-group">
                    <label for="first_name">Имя:</label>
                    <input type="text" id="first_name" name="first_name" value="<?php echo $form_first_name; ?>" required>
                </div>
                <div class="form-group">
                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email" value="<?php echo $form_email; ?>" required>
                    <!-- Email менять можно, но нужна будет проверка на уникальность (кроме своего же) -->
                </div>
                <div class="form-group">
                    <label for="phone_number">Телефон:</label>
                    <input type="text" id="phone_number" name="phone_number" value="<?php echo $form_phone_number; ?>" placeholder="+7 (XXX) XXX-XX-XX">
                </div>
                <div class="form-actions">
                    <a href="profile.php" class="btn btn-cancel">Отмена</a>
                    <button type="submit" class="btn btn-submit"><i class="fas fa-save"></i> Сохранить</button>
                </div>
            </form>
        </div>
    </main>

    <footer class="footer">
        <div class="container footer__container">
            <div class="footer__links">
                <a href="about-us-log.php">О нас</a> <!-- В футере ссылка остается -->
                <a href="mailto:borovetf@gmail.com">Контакты</a>
                <a href="https://docs.google.com/document/d/1uUSg0HDIPny75EqESQr0gu2Utg3AtNBaLw0Xk0-TyL0/edit?usp=sharing">Правила и условия</a>
                <a href="https://docs.google.com/document/d/1drFUdo3izJodkkSkofe_e5AcnV0Ahl9jpezZYmJgZlU/edit?usp=sharing">Политика конфиденциальности</a>
            </div>
        </div>
    </footer>
</body>
</html>