<?php
session_start();
require 'db.php'; // Подключение к базе данных

// Перенаправление если пользователь не авторизован
if (!isset($_SESSION['user'])) {
    header("Location: input.php");
    exit();
}

// Получаем актуальные данные пользователя из БД
$user_id = $_SESSION['user']['id'];
$stmt = $conn->prepare("SELECT username, email, avatar FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user_data = $result->fetch_assoc();

// Обновляем данные в сессии
$_SESSION['user']['nickname'] = $user_data['username'];
$_SESSION['user']['email'] = $user_data['email'];
$_SESSION['user']['avatar'] = $user_data['avatar'];

// Обработка сохранения данных
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['save'])) {
        // Обработка формы профиля
        $nickname = trim($_POST['nickname']);
        $email = trim($_POST['email']);
        $password = trim($_POST['password']);
        
        $errors = [];
        
        // Валидация никнейма
        if (empty($nickname) || !preg_match('/^[a-zA-Zа-яА-Я0-9_]{3,16}$/u', $nickname)) {
            $errors['nickname'] = 'Никнейм 3-16 символов (буквы, цифры, _)';
        }
        
        // Валидация email
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Введите корректный email';
        }
        
        // Валидация пароля (если введен)
        if (!empty($password) && !preg_match('/^(?=.*[A-ZА-Я])(?=.*\d).{8,}$/u', $password)) {
            $errors['password'] = 'Пароль: 8+ символов, заглавная и цифра';
        }
        
        if (empty($errors)) {
            try {
                // Обновляем данные пользователя
                if (!empty($password)) {
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $conn->prepare("UPDATE users SET username = ?, email = ?, pass = ? WHERE id = ?");
                    $stmt->bind_param("sssi", $nickname, $email, $hashed_password, $user_id);
                } else {
                    $stmt = $conn->prepare("UPDATE users SET username = ?, email = ? WHERE id = ?");
                    $stmt->bind_param("ssi", $nickname, $email, $user_id);
                }
                
                if ($stmt->execute()) {
                    // Обновляем данные в сессии
                    $_SESSION['user']['nickname'] = $nickname;
                    $_SESSION['user']['email'] = $email;
                    
                    $_SESSION['success'] = 'Данные успешно обновлены!';
                    header("Location: profile.php");
                    exit();
                }
            } catch (mysqli_sql_exception $e) {
                if ($e->getCode() == 1062) {
                    $errors['email'] = 'Этот email уже занят';
                } else {
                    $errors['db'] = 'Ошибка базы данных: ' . $e->getMessage();
                }
            }
        }
        
        $_SESSION['errors'] = $errors;
        $_SESSION['old'] = ['nickname' => $nickname, 'email' => $email];
        header("Location: profile.php");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Профиль пользователя</title>
    <link rel="stylesheet" href="css/profile.css">
    <style>
        
        .error {
            color: red;
            font-size: 0.9rem;
            margin-bottom: 5px;
            display: block; 
            padding: 8px;
            border: 2px solid red;
            background-color: #ffe6e6;
            border-radius: 5px;
            width: calc();
            text-align: center;
        }
        
        .alert {
            padding: 12px;
            margin-bottom: 20px;
            border-radius: 4px;
            font-size: 1rem;
        }
        
        .alert.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .circle {
            width: 200px;
            height: 200px;
            border-radius: 50%;
            background-color: #e0e0e0;
            border: 3px solid #37b24d;
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            <?php if (!empty($_SESSION['user']['avatar'])): ?>
                background-image: url('<?= $_SESSION['user']['avatar'] ?>');
            <?php endif; ?>
        }
        
        input:not([readonly]) {
            background-color: #fff !important;
            cursor: text !important;
            border: 2px solid #37b24d !important;
        }

        
    </style>
</head>
<body>
    <div id="preloader" class="preloader">
        <div class="loding__block">
            <div class="title">
                Loading...
            </div>
            <div class="progress"></div>
        </div>
        <div class="preloader__block"></div>
        <div class="preloader__block"></div>
    </div>
    
    <header class="head">
        <a href="#" class="logo">FinanceExpert</a>
        <div class="bx-menu" id="menu-icon"></div>
        <nav>
            <ul>
                <li><a href="logIndex.php">Главная</a></li>
                <li><a href="logAboutUs.php">О нас</a></li>
                <li><a href="logContacts.php">Контакты</a></li>
                <li><a href="profile.php">Профиль</a></li>
            </ul>
        </nav>
    </header>
    
    <main>
        <div class="profile-container">
            <div class="profile-picture">
                <div class="circle"></div>
                <button class="circle-button" id="changeAvatarBtn">Изменить аватар</button>
                <input type="file" id="avatarInput" accept="image/*" style="display: none;">
            </div>
            
            <div class="profile-details">
                <h2>Личные данные</h2>
                
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert success">
                        <?= $_SESSION['success']; unset($_SESSION['success']); ?>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['errors']['db'])): ?>
                    <div class="alert error">
                        <?= $_SESSION['errors']['db']; unset($_SESSION['errors']['db']); ?>
                    </div>
                <?php endif; ?>
                
                <form id="profileForm" method="POST" action="profile.php">
                    <div class="form-group">
                        <label for="nickname">Ник</label>
                        <input type="text" id="nickname" name="nickname" 
                               value="<?= htmlspecialchars($_SESSION['old']['nickname'] ?? $_SESSION['user']['nickname']) ?>"
                               readonly>
                        <?php if (isset($_SESSION['errors']['nickname'])): ?>
                            <span class="error"><?= $_SESSION['errors']['nickname']; unset($_SESSION['errors']['nickname']); ?></span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" 
                               value="<?= htmlspecialchars($_SESSION['old']['email'] ?? $_SESSION['user']['email']) ?>"
                               readonly>
                        <?php if (isset($_SESSION['errors']['email'])): ?>
                            <span class="error"><?= $_SESSION['errors']['email']; unset($_SESSION['errors']['email']); ?></span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Новый пароль</label>
                        <input type="password" id="password" name="password" placeholder="Оставьте пустым, если не нужно менять" readonly>
                        <?php if (isset($_SESSION['errors']['password'])): ?>
                            <span class="error"><?= $_SESSION['errors']['password']; unset($_SESSION['errors']['password']); ?></span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="buttons">
                        <button type="button" class="edit-button" id="editBtn">Редактировать</button>
                        <button type="submit" name="save" class="save-button" id="saveBtn" style="display: none;">Сохранить</button>
                        <button type="button" class="cancel-button" id="cancelBtn" style="display: none;">Отмена</button>
                    </div>
                </form>
                
                <button type="button" class="exit-button" onclick="window.location.href='logout.php'">Выйти из аккаунта</button>
            </div>
        </div>
    </main>
    
    <script src="js/preloader.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const editBtn = document.getElementById('editBtn');
            const saveBtn = document.getElementById('saveBtn');
            const inputs = document.querySelectorAll('#profileForm input');
            const changeAvatarBtn = document.getElementById('changeAvatarBtn');
            const avatarInput = document.getElementById('avatarInput');
            const cancelBtn = document.getElementById('cancelBtn');
            
            // Включение режима редактирования
            editBtn.addEventListener('click', function() {
                inputs.forEach(input => {
                    input.removeAttribute('readonly');
                });
                
                editBtn.style.display = 'none';
                saveBtn.style.display = 'block';
                cancelBtn.style.display = 'block';
            });
            
            // Обработка отмены редактирования
            cancelBtn.addEventListener('click', function() {
                inputs.forEach(input => {
                    input.setAttribute('readonly', true);
                    // Восстановление исходных значений
                    if (input.name === 'nickname') input.value = '<?= htmlspecialchars($_SESSION['user']['nickname']) ?>';
                    if (input.name === 'email') input.value = '<?= htmlspecialchars($_SESSION['user']['email']) ?>';
                    if (input.name === 'password') input.value = '';
                });
                
                editBtn.style.display = 'block';
                saveBtn.style.display = 'none';
                cancelBtn.style.display = 'none';
                
                // Удаление сообщений об ошибках
                document.querySelectorAll('.error').forEach(el => el.remove());
            });
            
            // Изменение аватара
            changeAvatarBtn.addEventListener('click', function() {
                avatarInput.click();
            });
            
            avatarInput.addEventListener('change', function() {
                if (this.files && this.files[0]) {
                    const formData = new FormData();
                    formData.append('avatar', this.files[0]);
                    
                    fetch('upload_avatar.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            document.querySelector('.circle').style.backgroundImage = `url(${data.path})`;
                            alert('Аватар успешно обновлен!');
                        } else {
                            alert(data.error || 'Ошибка загрузки аватара');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Ошибка загрузки аватара');
                    });
                }
            });
            
            // Валидация формы перед отправкой
            document.getElementById('profileForm').addEventListener('submit', function(e) {
                let isValid = true;
                
                // Валидация никнейма
                const nickname = document.getElementById('nickname');
                if (!/^(?=.*[a-zA-Z])[a-zA-Z0-9_]{3,16}$/u.test(nickname.value)) {
                    showError(nickname, 'Никнейм должен содержать 3-16 символов, только латинские буквы, цифры и не иметь пробелов');
                    isValid = false;
                }
                
                // Валидация email
                const email = document.getElementById('email');
                if (!/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,100}$/.test(email.value)) {
                    showError(email, 'Некорректный email');
                    isValid = false;
                }
                
                // Валидация пароля (если введен)
                const password = document.getElementById('password');
                if (password.value && !/^(?=.*[A-Z])(?=.*[a-z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,50}$/u.test(password.value)) {
                    showError(password, 'Пароль должен содержать минимум 8 символов, одну заглавную букву, одну цифру, один спецсимвол и не содержать пробелы!');
                    isValid = false;
                }
                
                if (!isValid) {
                    e.preventDefault();
                }
            });
            
            function showError(input, message) {
                const formGroup = input.closest('.form-group');
                let errorElement = formGroup.querySelector('.error');
                
                if (!errorElement) {
                    errorElement = document.createElement('span');
                    errorElement.className = 'error';
                    formGroup.appendChild(errorElement);
                }
                
                errorElement.textContent = message;
                input.style.borderColor = '#ff3333';
            }
        });
    </script>
</body>
</html>
