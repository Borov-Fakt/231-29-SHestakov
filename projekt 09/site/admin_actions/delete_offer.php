<?php
// admin_actions/delete_offer.php
if (session_status() == PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    http_response_code(403); 
    $_SESSION['offers_message'] = "Доступ запрещен.";
    $_SESSION['offers_message_type'] = "error";
    if(empty($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) != 'xmlhttprequest') {
        header("Location: ../admin.php?tab=special-offers-content");
    }
    exit();
}
require '../db.php';

$return_params_array = ['tab' => 'special-offers-content'];
if (isset($_GET['spage'])) $return_params_array['spage'] = $_GET['spage'];
if (isset($_GET['ssearch'])) $return_params_array['ssearch'] = $_GET['ssearch'];
if (isset($_GET['sfilter_active'])) $return_params_array['sfilter_active'] = $_GET['sfilter_active'];
$return_url = "../admin.php?" . http_build_query($return_params_array);


if (isset($_GET['offer_id']) && is_numeric($_GET['offer_id'])) {
    $offer_id_to_delete = (int)$_GET['offer_id'];

    // ВАЖНО: Здесь не требуется подтверждение типа "Вы уверены?",
    // так как оно делается на стороне клиента (JS onclick="return confirm(...)").
    // Если JS отключен или обойден, удаление произойдет сразу.
    // Для большей безопасности можно добавить серверное подтверждение (дополнительный шаг/форма).

    $stmt_delete = $conn->prepare("DELETE FROM special_offers WHERE offer_id = ?");
    if(!$stmt_delete){ 
        error_log("DB Error (prepare delete offer): " . $conn->error);
        $_SESSION['offers_message'] = "Ошибка подготовки запроса к базе данных (DO)."; 
        $_SESSION['offers_message_type'] = "error"; 
        header("Location: " . $return_url); exit(); 
    }
    $stmt_delete->bind_param("i", $offer_id_to_delete);

    if ($stmt_delete->execute()) {
        if($stmt_delete->affected_rows > 0){
            $_SESSION['offers_message'] = "Спецпредложение ID " . $offer_id_to_delete . " успешно удалено.";
            $_SESSION['offers_message_type'] = "success";
        } else {
            // Это может случиться, если кто-то уже удалил запись между загрузкой страницы и кликом
            $_SESSION['offers_message'] = "Спецпредложение ID " . $offer_id_to_delete . " не найдено или уже было удалено.";
            $_SESSION['offers_message_type'] = "info";
        }
    } else {
        error_log("Admin: Error deleting offer ID " . $offer_id_to_delete . ": " . $stmt_delete->error);
        $_SESSION['offers_message'] = "Ошибка при удалении спецпредложения.";
        $_SESSION['offers_message_type'] = "error";
    }
    $stmt_delete->close();
} else {
    $_SESSION['offers_message'] = "Не указан ID спецпредложения для удаления.";
    $_SESSION['offers_message_type'] = "error";
}
header("Location: " . $return_url);
exit();
?>