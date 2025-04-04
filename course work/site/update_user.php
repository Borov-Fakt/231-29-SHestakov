<?php
// update_user.php - Обрабатывает обновление данных пользователя (включая аватар) админом

// Включаем строгую отчетность об ошибках для разработки
error_reporting(E_ALL);
ini_set('display_errors', 0); // В продакшене ВЫКЛЮЧИТЬ отображение ошибок, логировать
ini_set('log_errors', 1);
ini_set('error_log', '/path/to/your/php-error.log'); // УКАЖИТЕ ПРАВИЛЬНЫЙ ПУТЬ К ЛОГ ФАЙЛУ

// Запускаем сессию (если еще не запущена)
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// --- Настройки ---
$uploadDir = 'uploads/avatars/'; // Директория для загрузки аватаров (относительно этого скрипта)
$allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
$maxFileSize = 2 * 1024 * 1024; // 2 MB
$defaultAvatar = null; // Или путь к аватару по умолчанию, если есть

header('Content-Type: application/json'); // Ответ всегда будет JSON

// --- Проверка аутентификации АДМИНИСТРАТОРА ---
if (!isset($_SESSION['is_admin_logged_in']) || $_SESSION['is_admin_logged_in'] !== true) {
    http_response_code(403); // Forbidden
    echo json_encode(['success' => false, 'error' => 'Доступ запрещен. Требуется вход администратора.']);
    exit;
}

// --- Проверка метода запроса ---
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['success' => false, 'error' => 'Неверный метод запроса. Ожидался POST.']);
    exit;
}

// --- Получение и валидация данных ---
$userId = isset($_POST['userId']) ? filter_var($_POST['userId'], FILTER_VALIDATE_INT) : null;
$username = isset($_POST['username']) ? trim($_POST['username']) : null;
$email = isset($_POST['email']) ? trim($_POST['email']) : null;

$errors = [];
$field_errors = []; // Для ошибок конкретных полей

if ($userId === null || $userId === false || $userId <= 0) {
    $errors[] = 'Неверный или отсутствующий ID пользователя.';
}
if (empty($username)) {
    $field_errors['username'] = 'Никнейм обязателен.';
} elseif (strlen($username) < 3) {
    $field_errors['username'] = 'Никнейм должен быть не менее 3 символов.';
} elseif (strlen($username) > 50) {
    $field_errors['username'] = 'Никнейм не должен превышать 50 символов.';
} elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
    $field_errors['username'] = 'Никнейм может содержать только латинские буквы, цифры и знак подчеркивания (_).';
}

if (empty($email)) {
    $field_errors['email'] = 'Email обязателен.';
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $field_errors['email'] = 'Неверный формат Email адреса.';
}

if (!empty($field_errors)) {
     $errors[] = "Пожалуйста, исправьте ошибки в форме.";
}

if (!empty($errors)) {
    http_response_code(400); // Bad Request
    echo json_encode(['success' => false, 'error' => implode(' ', $errors), 'field_errors' => $field_errors]);
    exit;
}

// --- Подключение к БД ---
require 'db.php'; // Подключаем db.php, он создаст объект $conn

// --- Проверка существования пользователя и получение старого аватара ---
$oldAvatarPath = null;
$stmt_check = $conn->prepare("SELECT avatar FROM users WHERE id = ?");
if (!$stmt_check) {
    error_log("MySQL Prepare Error (check user): " . $conn->error);
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Ошибка сервера при проверке пользователя.']);
    exit;
}
$stmt_check->bind_param("i", $userId);
$stmt_check->execute();
$result_check = $stmt_check->get_result();
if ($result_check->num_rows === 0) {
    http_response_code(404); // Not Found
    echo json_encode(['success' => false, 'error' => 'Пользователь с указанным ID не найден.']);
    $stmt_check->close();
    $conn->close();
    exit;
} else {
    $user_row = $result_check->fetch_assoc();
    $oldAvatarPath = $user_row['avatar']; // Сохраняем путь к старому аватару
}
$stmt_check->close();

// --- Обработка загрузки аватара ---
$newAvatarPath = null; // Путь к новому загруженному аватару
$avatarChanged = false;

if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
    $file = $_FILES['avatar'];

    // Проверка размера
    if ($file['size'] > $maxFileSize) {
        $errors[] = 'Файл аватара слишком большой (макс. ' . ($maxFileSize / 1024 / 1024) . ' МБ).';
    }

    // Проверка типа файла (лучше использовать finfo)
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mimeType, $allowedTypes)) {
        $errors[] = 'Неверный тип файла аватара. Разрешены: JPG, PNG, GIF.';
    }

    if (empty($errors)) {
        // Создать директорию, если не существует
        if (!is_dir($uploadDir)) {
            if (!mkdir($uploadDir, 0775, true)) { // 0775 права, рекурсивно
                 error_log("Failed to create upload directory: " . $uploadDir);
                 $errors[] = 'Не удалось создать директорию для загрузки.';
            }
        } elseif (!is_writable($uploadDir)) {
             error_log("Upload directory is not writable: " . $uploadDir);
             $errors[] = 'Директория для загрузки недоступна для записи.';
        }

        // Если ошибок с директорией нет, генерируем имя и перемещаем файл
        if(empty($errors)) {
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            // Генерируем уникальное имя файла, чтобы избежать перезаписи и проблем с кэшем
            $newFileName = uniqid('avatar_' . $userId . '_', true) . '.' . strtolower($extension);
            $destination = $uploadDir . $newFileName;

            if (move_uploaded_file($file['tmp_name'], $destination)) {
                $newAvatarPath = $destination; // Путь для записи в БД
                $avatarChanged = true;
            } else {
                error_log("Failed to move uploaded file: " . $file['tmp_name'] . " to " . $destination);
                $errors[] = 'Ошибка при сохранении файла аватара.';
            }
        }
    }
} elseif (isset($_FILES['avatar']) && $_FILES['avatar']['error'] !== UPLOAD_ERR_NO_FILE) {
    // Была попытка загрузки, но произошла ошибка
    $uploadErrors = [
        UPLOAD_ERR_INI_SIZE => 'Размер файла превысил лимит директивы upload_max_filesize в php.ini.',
        UPLOAD_ERR_FORM_SIZE => 'Размер файла превысил лимит директивы MAX_FILE_SIZE в HTML-форме.',
        UPLOAD_ERR_PARTIAL => 'Файл был загружен только частично.',
        UPLOAD_ERR_NO_TMP_DIR => 'Отсутствует временная папка.',
        UPLOAD_ERR_CANT_WRITE => 'Не удалось записать файл на диск.',
        UPLOAD_ERR_EXTENSION => 'PHP-расширение остановило загрузку файла.',
    ];
    $errorCode = $_FILES['avatar']['error'];
    $errorMessage = $uploadErrors[$errorCode] ?? 'Неизвестная ошибка загрузки файла.';
    error_log("File Upload Error (code $errorCode): $errorMessage");
    $errors[] = 'Ошибка загрузки аватара: ' . $errorMessage;
}


// Если есть ошибки на этапе обработки файла
if (!empty($errors)) {
    http_response_code(400); // Bad Request
    echo json_encode(['success' => false, 'error' => implode(' ', $errors)]);
    $conn->close();
    exit;
}

// --- Обновление данных в БД ---
$sql_update = "UPDATE users SET username = ?, email = ?";
$params = [$username, $email];
$types = "ss";

if ($avatarChanged) {
    $sql_update .= ", avatar = ?";
    $params[] = $newAvatarPath; // Добавляем новый путь к параметрам
    $types .= "s";
}

$sql_update .= " WHERE id = ?";
$params[] = $userId; // Добавляем ID пользователя
$types .= "i";

$stmt_update = $conn->prepare($sql_update);
if (!$stmt_update) {
    error_log("MySQL Prepare Error (update user): " . $conn->error);
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Ошибка сервера при подготовке запроса обновления.']);
    // Удаляем новый аватар, если он был загружен, но БД упала
    if ($newAvatarPath && file_exists($newAvatarPath)) { unlink($newAvatarPath); }
    $conn->close();
    exit;
}

// Привязка параметров динамически
$stmt_update->bind_param($types, ...$params); // Используем splat operator (...)

if ($stmt_update->execute()) {
    // Успешное обновление
    $message = "Данные пользователя успешно обновлены.";

    // Удаляем старый аватар, если был загружен новый и старый существовал
    if ($avatarChanged && !empty($oldAvatarPath) && $oldAvatarPath !== $defaultAvatar && file_exists($oldAvatarPath)) {
        if (@unlink($oldAvatarPath)) { // @ подавляет warning, если файл уже удален
             $message .= " Старый аватар удален.";
        } else {
            error_log("Could not delete old avatar: " . $oldAvatarPath);
             $message .= " Не удалось удалить старый аватар."; // Информационное сообщение
        }
    }

    $responseData = [
        'success' => true,
        'message' => $message,
        'updatedUser' => [ // Возвращаем обновленные данные для JS
             'id' => $userId,
             'username' => $username,
             'email' => $email,
             'avatar' => $avatarChanged ? $newAvatarPath : $oldAvatarPath // Возвращаем актуальный путь
        ]
    ];
     // Если аватар изменился, передаем новый URL явно
     if ($avatarChanged) {
         $responseData['newAvatarUrl'] = $newAvatarPath;
     }

    echo json_encode($responseData);

} else {
    error_log("MySQL Execute Error (update user): " . $stmt_update->error);
    // Проверяем на дубликаты (специфично для MySQL)
    if ($conn->errno == 1062) { // 1062 - код ошибки дубликата ключа
        $error_msg = 'Ошибка: ';
        if (strpos($stmt_update->error, 'username') !== false) {
            $error_msg .= 'Пользователь с таким никнеймом уже существует.';
            $field_errors['username'] = 'Этот никнейм уже занят.';
        } elseif (strpos($stmt_update->error, 'email') !== false) {
            $error_msg .= 'Пользователь с таким Email уже существует.';
            $field_errors['email'] = 'Этот Email уже используется.';
        } else {
            $error_msg .= 'Нарушение уникального ограничения.';
        }
        http_response_code(409); // Conflict
        echo json_encode(['success' => false, 'error' => $error_msg, 'field_errors' => $field_errors]);

    } else {
        http_response_code(500); // Internal Server Error
        echo json_encode(['success' => false, 'error' => 'Ошибка сервера при обновлении данных пользователя.']);
    }
     // Удаляем новый аватар, если он был загружен, но запись в БД не удалась
    if ($newAvatarPath && file_exists($newAvatarPath)) { unlink($newAvatarPath); }
}

$stmt_update->close();
$conn->close();
?>