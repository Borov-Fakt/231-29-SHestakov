<?php
// admin_actions/toggle_offer_status.php
if (session_status() == PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    http_response_code(403); 
    // Можно вывести JSON-ответ для AJAX запросов в будущем, или просто exit
    $_SESSION['offers_message'] = "Доступ запрещен.";
    $_SESSION['offers_message_type'] = "error";
    // Если это прямой вызов, а не AJAX, лучше редирект
    if(empty($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) != 'xmlhttprequest') {
        header("Location: ../admin.php?tab=special-offers-content");
    }
    exit();
}
require '../db.php'; // Путь к db.php на уровень выше

// Формируем URL для возврата с учетом пагинации и фильтров
$return_params_array = ['tab' => 'special-offers-content'];
if (isset($_GET['spage'])) $return_params_array['spage'] = $_GET['spage'];
if (isset($_GET['ssearch'])) $return_params_array['ssearch'] = $_GET['ssearch'];
if (isset($_GET['sfilter_active'])) $return_params_array['sfilter_active'] = $_GET['sfilter_active'];
$return_url = "../admin.php?" . http_build_query($return_params_array);

if (isset($_GET['offer_id']) && is_numeric($_GET['offer_id']) && isset($_GET['current_status'])) {
    $offer_id_to_toggle = (int)$_GET['offer_id'];
    $current_status_from_get = $_GET['current_status']; // Может быть строкой '0' или '1'
    
    // Преобразуем к int для надежного сравнения
    $current_status_int = filter_var($current_status_from_get, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 1]]);

    if ($current_status_int === false) { // false если не 0 и не 1, или не число
        $_SESSION['offers_message'] = "Некорректный текущий статус предложения.";
        $_SESSION['offers_message_type'] = "error";
        header("Location: " . $return_url);
        exit();
    }

    $new_status = ($current_status_int == 1) ? 0 : 1;

    $stmt_toggle = $conn->prepare("UPDATE special_offers SET is_active = ? WHERE offer_id = ?");
    if (!$stmt_toggle) { 
        error_log("DB Error (prepare toggle offer status): " . $conn->error);
        $_SESSION['offers_message'] = "Ошибка подготовки запроса к базе данных (TGS)."; 
        $_SESSION['offers_message_type'] = "error"; 
        header("Location: " . $return_url); exit(); 
    }
    $stmt_toggle->bind_param("ii", $new_status, $offer_id_to_toggle);

    if ($stmt_toggle->execute()) {
        $action_text = ($new_status == 1) ? "активировано" : "деактивировано";
        $_SESSION['offers_message'] = "Спецпредложение ID " . $offer_id_to_toggle . " успешно " . $action_text . ".";
        $_SESSION['offers_message_type'] = "success";
    } else {
        error_log("Admin: Error toggling offer status for ID " . $offer_id_to_toggle . ": " . $stmt_toggle->error);
        $_SESSION['offers_message'] = "Ошибка при изменении статуса спецпредложения.";
        $_SESSION['offers_message_type'] = "error";
    }
    $stmt_toggle->close();
} else {
    $_SESSION['offers_message'] = "Недостаточно данных для изменения статуса спецпредложения.";
    $_SESSION['offers_message_type'] = "error";
}
header("Location: " . $return_url);
exit();
?>