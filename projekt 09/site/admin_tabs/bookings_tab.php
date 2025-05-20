<?php
// admin_tabs/bookings_tab.php
// Предполагается, что $conn (объект соединения с БД) и $admin_id (ID текущего администратора)
// уже определены в основном файле admin.php и сессия администратора проверена.

// --- Пагинация для бронирований ---
$bookings_per_page = 15; 
$current_page_bookings = isset($_GET['bpage']) && is_numeric($_GET['bpage']) ? (int)$_GET['bpage'] : 1;
if ($current_page_bookings < 1) $current_page_bookings = 1;

// --- Поиск и фильтрация для бронирований ---
$search_term_bookings = isset($_GET['bsearch']) ? trim($_GET['bsearch']) : ''; // PNR, Email пользователя, Имя/Фамилия клиента
$filter_status_bookings = isset($_GET['bstatus']) ? trim($_GET['bstatus']) : 'all'; 

$where_clauses_bookings = [];
$params_bookings_for_where = []; 
$types_bookings_for_where = "";   

// SQL JOINs для поиска по данным пользователя
$sql_joins_bookings = " LEFT JOIN users u ON b.user_id = u.user_id ";

if (!empty($search_term_bookings)) {
    // Ищем по PNR, контактному email, имени или фамилии зарегистрированного пользователя
    $where_clauses_bookings[] = "(b.booking_reference LIKE ? OR b.contact_email LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ?)";
    $like_term_bookings = "%{$search_term_bookings}%";
    for($i=0; $i<4; $i++){ // 4 плейсхолдера для LIKE
        $params_bookings_for_where[] = $like_term_bookings;
        $types_bookings_for_where .= "s";
    }
}

// Массив для человекочитаемых статусов и ключей в БД
$booking_statuses_map = [
    'pending_payment' => 'Ожидает оплаты',
    'confirmed' => 'Подтверждено',
    'ticketed' => 'Билеты выписаны',
    'cancelled_by_user' => 'Отменено клиентом',
    'cancelled_by_airline' => 'Отменено авиакомпанией',
    'payment_failed' => 'Ошибка оплаты',
    'error' => 'Ошибка системы',
    'completed' => 'Завершено'
    // Убедитесь, что здесь все статусы из вашего ENUM в таблице bookings
];

if ($filter_status_bookings !== 'all' && array_key_exists($filter_status_bookings, $booking_statuses_map)) {
    $where_clauses_bookings[] = "b.status = ?";
    $params_bookings_for_where[] = $filter_status_bookings;
    $types_bookings_for_where .= "s";
}

$sql_where_bookings_str = "";
if (!empty($where_clauses_bookings)) {
    $sql_where_bookings_str = " WHERE " . implode(" AND ", $where_clauses_bookings);
}

// --- Получаем общее количество бронирований для пагинации ---
$sql_count_bookings = "SELECT COUNT(DISTINCT b.booking_id) as total 
                       FROM bookings b 
                       " . $sql_joins_bookings 
                     . $sql_where_bookings_str;
$stmt_count_bookings = $conn->prepare($sql_count_bookings);
$total_bookings = 0;

if ($stmt_count_bookings) {
    if (!empty($types_bookings_for_where)) { 
        $bind_params_count_b = [];
        $bind_params_count_b[] = &$types_bookings_for_where;
        for ($i = 0; $i < count($params_bookings_for_where); $i++) {
            $bind_params_count_b[] = &$params_bookings_for_where[$i];
        }
        call_user_func_array(array($stmt_count_bookings, 'bind_param'), $bind_params_count_b);
    }
    if(!$stmt_count_bookings->execute()){
        error_log("Admin Bookings Tab - Error executing COUNT query: " . $stmt_count_bookings->error);
    } else {
        $result_count_b = $stmt_count_bookings->get_result();
        if($row_count_b_result = $result_count_b->fetch_assoc()){ // Исправлено имя переменной
             $total_bookings = $row_count_b_result['total'];
        }
    }
    $stmt_count_bookings->close();
} else {
    error_log("Admin Bookings Tab - Error preparing COUNT query: " . $conn->error);
}

$total_pages_bookings = $bookings_per_page > 0 ? ceil($total_bookings / $bookings_per_page) : 0;
if ($current_page_bookings > $total_pages_bookings && $total_pages_bookings > 0) {
    $current_page_bookings = $total_pages_bookings;
}
$offset_bookings = ($current_page_bookings - 1) * $bookings_per_page;
if ($offset_bookings < 0) $offset_bookings = 0; 


// --- Получаем бронирования для текущей страницы ---
$sql_bookings_list = "SELECT 
                        b.booking_id, b.booking_reference, b.status, b.total_price, b.currency_code, 
                        b.contact_email, b.created_at,
                        u.user_id as client_user_id, u.first_name as user_first_name, u.last_name as user_last_name,
                        (SELECT GROUP_CONCAT(DISTINCT CONCAT(p.first_name, ' ', p.last_name) ORDER BY p.passenger_id SEPARATOR ', ') 
                         FROM passengers p WHERE p.booking_id = b.booking_id) as passenger_names_list,
                        (SELECT bs_dep.departure_airport_iata_code 
                         FROM booked_segments bs_dep 
                         WHERE bs_dep.booking_id = b.booking_id 
                         ORDER BY bs_dep.sequence_number ASC LIMIT 1) as first_segment_departure_iata,
                        (SELECT bs_arr.arrival_airport_iata_code 
                         FROM booked_segments bs_arr 
                         WHERE bs_arr.booking_id = b.booking_id 
                         ORDER BY bs_arr.sequence_number DESC LIMIT 1) as last_segment_arrival_iata,
                        (SELECT MIN(bs_date.departure_at_utc) 
                         FROM booked_segments bs_date 
                         WHERE bs_date.booking_id = b.booking_id) as first_departure_datetime
                      FROM bookings b 
                      " . $sql_joins_bookings // Применяем тот же JOIN, что и для COUNT
                      . $sql_where_bookings_str . " 
                      ORDER BY b.created_at DESC 
                      LIMIT ? OFFSET ?";

$stmt_bookings_list = $conn->prepare($sql_bookings_list);

$current_params_for_select_b = $params_bookings_for_where; 
$current_types_for_select_b = $types_bookings_for_where;   

$current_params_for_select_b[] = $bookings_per_page; $current_types_for_select_b .= "i"; 
$current_params_for_select_b[] = $offset_bookings; $current_types_for_select_b .= "i";    

$bookings_list_data = [];
if ($stmt_bookings_list) {
    if (!empty($current_types_for_select_b)){ 
        $bind_params_select_b = [];
        $bind_params_select_b[] = &$current_types_for_select_b;
        for ($i = 0; $i < count($current_params_for_select_b); $i++) {
            $bind_params_select_b[] = &$current_params_for_select_b[$i];
        }
        call_user_func_array(array($stmt_bookings_list, 'bind_param'), $bind_params_select_b);
    }
    if (!$stmt_bookings_list->execute()) {
        error_log("Admin Bookings Tab - Error executing SELECT bookings query: " . $stmt_bookings_list->error);
    } else {
        $result_bookings_data = $stmt_bookings_list->get_result(); // Исправлено имя переменной
        while ($row = $result_bookings_data->fetch_assoc()) {
            $bookings_list_data[] = $row;
        }
    }
    $stmt_bookings_list->close();
} else {
     error_log("Admin Bookings Tab - Error preparing SELECT bookings query: " . $conn->error);
}

$bookings_tab_management_message = $_SESSION['bookings_message'] ?? '';
$bookings_tab_management_message_type = $_SESSION['bookings_message_type'] ?? 'info';
unset($_SESSION['bookings_message'], $_SESSION['bookings_message_type']);
?>
<h2>Управление Бронированиями</h2>

<?php if ($bookings_tab_management_message): ?>
    <div class="admin-message <?php echo htmlspecialchars($bookings_tab_management_message_type); ?>">
        <?php echo htmlspecialchars($bookings_tab_management_message); ?>
    </div>
<?php endif; ?>

<div class="admin-actions-bar">
    <!-- <a href="admin_actions/create_booking_form.php" class="btn btn-primary"><i class="fas fa-plus-circle"></i> Создать бронь</a> -->
    <form action="admin.php" method="GET" class="filter-form">
        <input type="hidden" name="tab" value="bookings-content">
        <input type="text" name="bsearch" placeholder="PNR, Email, Имя..." value="<?php echo htmlspecialchars($search_term_bookings); ?>">
        <select name="bstatus">
            <option value="all">Все статусы</option>
            <?php foreach ($booking_statuses_map as $status_key => $status_name): ?>
                <option value="<?php echo $status_key; ?>" <?php echo ($filter_status_bookings == $status_key) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($status_name); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="btn btn-secondary"><i class="fas fa-filter"></i> Фильтр</button>
        <?php if(!empty($search_term_bookings) || $filter_status_bookings !== 'all'): ?>
             <a href="admin.php?tab=bookings-content" class="btn btn-link" style="margin-left: 0.5rem;">Сбросить</a>
        <?php endif; ?>
    </form>
</div>


<?php if (empty($bookings_list_data) && $total_bookings > 0 && $current_page_bookings > 1): ?>
    <p>Бронирования не найдены на этой странице. 
        <a href="admin.php?tab=bookings-content&bpage=1&bsearch=<?php echo urlencode($search_term_bookings); ?>&bstatus=<?php echo urlencode($filter_status_bookings);?>">Вернуться на первую страницу</a>.
    </p>
<?php elseif (empty($bookings_list_data)): ?>
    <div class="placeholder-content">
        <i class="fas fa-suitcase-rolling fa-3x"></i>
        <p>Бронирования не найдены<?php echo !empty($search_term_bookings) || $filter_status_bookings != 'all' ? ' по вашему запросу' : ''; ?>.</p>
    </div>
<?php else: ?>
    <div class="table-responsive-wrapper">
    <table class="admin-table bookings-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>PNR</th>
                <th>Статус</th>
                <th>Клиент (Email)</th>
                <th>Пассажиры</th>
                <th>Маршрут</th>
                <th>1-й Вылет</th>
                <th>Сумма</th>
                <th>Создано</th>
                <th style="text-align:right;">Действия</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($bookings_list_data as $booking_item): ?>
            <tr>
                <td><?php echo $booking_item['booking_id']; ?></td>
                <td><strong><?php echo htmlspecialchars($booking_item['booking_reference']); ?></strong></td>
                <td>
                    <span class="status-badge status-booking-<?php echo htmlspecialchars($booking_item['status']); ?>" 
                          title="<?php echo htmlspecialchars($booking_item['status']); // Показываем системное имя статуса во всплывающей подсказке ?>">
                        <?php echo htmlspecialchars($booking_statuses_map[$booking_item['status']] ?? ucfirst(str_replace('_', ' ', $booking_item['status']))); ?>
                    </span>
                </td>
                <td>
                    <?php echo htmlspecialchars($booking_item['contact_email']); ?>
                    <?php if($booking_item['client_user_id']): // Используем client_user_id, которое мы выбрали из users ?>
                        <br><small title="Зарегистрированный пользователь (ID: <?php echo $booking_item['client_user_id']; ?>)">(<?php echo htmlspecialchars(trim($booking_item['user_first_name'] . ' ' . $booking_item['user_last_name'])); ?>)</small>
                    <?php else: ?>
                        <br><small>(Гость)</small>
                    <?php endif; ?>
                </td>
                <td class="passenger-names-cell" title="<?php echo htmlspecialchars($booking_item['passenger_names_list'] ?: '-'); ?>">
                    <?php 
                        $pax_list = $booking_item['passenger_names_list'] ?: '-';
                        // Обрезаем список пассажиров, если он слишком длинный
                        echo mb_strlen($pax_list) > 35 ? htmlspecialchars(mb_substr($pax_list, 0, 32)) . '...' : htmlspecialchars($pax_list);
                    ?>
                </td>
                <td>
                    <?php if ($booking_item['first_segment_departure_iata'] && $booking_item['last_segment_arrival_iata']): ?>
                        <span class="route-iata" title="<?php echo htmlspecialchars($booking_item['first_segment_departure_iata']); ?>"><?php echo htmlspecialchars($booking_item['first_segment_departure_iata']); ?></span> 
                        <i class="fas fa-long-arrow-alt-right route-arrow-table"></i> 
                        <span class="route-iata" title="<?php echo htmlspecialchars($booking_item['last_segment_arrival_iata']); ?>"><?php echo htmlspecialchars($booking_item['last_segment_arrival_iata']); ?></span>
                    <?php else: echo '-'; endif; ?>
                </td>
                <td>
                    <?php echo $booking_item['first_departure_datetime'] ? date("d.m.y H:i", strtotime($booking_item['first_departure_datetime'])) . ' <small>(UTC)</small>' : '-'; ?>
                </td>
                <td style="white-space: nowrap;"><?php echo htmlspecialchars(number_format((float)$booking_item['total_price'], 2, '.', ' ')) . ' ' . htmlspecialchars($booking_item['currency_code']); ?></td>
                <td style="white-space: nowrap;"><?php echo date("d.m.y H:i", strtotime($booking_item['created_at'])); ?></td>
                <td class="actions-cell">
                    <a href="admin_actions/view_booking_details.php?booking_id=<?php echo $booking_item['booking_id']; ?>&bpage=<?php echo $current_page_bookings; ?>&bsearch=<?php echo urlencode($search_term_bookings); ?>&bstatus=<?php echo urlencode($filter_status_bookings);?>" 
                       class="action-icon view" title="Просмотреть детали"><i class="fas fa-eye"></i></a>
                    
                    <!-- Закомментированные кнопки для будущих действий -->
                    <?php /* if(in_array($booking_item['status'], ['pending_payment', 'confirmed'])): ?>
                         <a href="admin_actions/edit_booking_admin.php?booking_id=<?php echo $booking_item['booking_id']; ?>" class="action-icon edit" title="Редактировать бронь (ограниченно)"><i class="fas fa-pencil-alt"></i></a> 
                    <?php endif; */ ?>
                    <?php /* if($booking_item['status'] == 'confirmed'): ?>
                         <a href="admin_actions/issue_booking_ticket.php?booking_id=<?php echo $booking_item['booking_id']; ?>" class="action-icon ticket" title="Выписать билеты" onclick="return confirm('Действительно выписать билеты для PNR <?php echo htmlspecialchars(addslashes($booking_item['booking_reference'])); ?>?');"><i class="fas fa-receipt"></i></a>
                    <?php endif; */ ?>
                     <?php /* if(!in_array($booking_item['status'], ['cancelled_by_user', 'cancelled_by_airline', 'completed'])): // Нельзя отменять уже отмененные/завершенные ?>
                        <a href="admin_actions/cancel_booking_admin.php?booking_id=<?php echo $booking_item['booking_id']; ?>" class="action-icon cancel" title="Отменить бронирование" onclick="return confirm('Отменить бронирование <?php echo htmlspecialchars(addslashes($booking_item['booking_reference'])); ?>? Это действие может быть необратимым в зависимости от статуса.');"><i class="fas fa-ban"></i></a>
                    <?php endif; */ ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    </div>

    <!-- Пагинация для бронирований -->
    <?php if ($total_pages_bookings > 1): ?>
    <nav class="pagination-nav">
        <ul class="pagination">
            <?php if ($current_page_bookings > 1): ?>
                <li><a href="admin.php?tab=bookings-content&bpage=1&bsearch=<?php echo urlencode($search_term_bookings); ?>&bstatus=<?php echo urlencode($filter_status_bookings);?>">«</a></li>
                <li><a href="admin.php?tab=bookings-content&bpage=<?php echo $current_page_bookings - 1; ?>&bsearch=<?php echo urlencode($search_term_bookings); ?>&bstatus=<?php echo urlencode($filter_status_bookings);?>">‹</a></li>
            <?php endif; ?>

            <?php
            $range_b = 2; 
            $start_loop_b = max(1, $current_page_bookings - $range_b);
            $end_loop_b = min($total_pages_bookings, $current_page_bookings + $range_b);
            if ($start_loop_b > 1 && $start_loop_b -1 > 1 ) echo "<li><span>...</span></li>"; elseif ($start_loop_b > 1 && $start_loop_b -1 == 1) $start_loop_b = 1;

            for ($i = $start_loop_b; $i <= $end_loop_b; $i++): ?>
                <li class="<?php echo ($i == $current_page_bookings) ? 'active' : ''; ?>">
                    <a href="admin.php?tab=bookings-content&bpage=<?php echo $i; ?>&bsearch=<?php echo urlencode($search_term_bookings); ?>&bstatus=<?php echo urlencode($filter_status_bookings);?>"><?php echo $i; ?></a>
                </li>
            <?php endfor; ?>

            <?php if ($end_loop_b < $total_pages_bookings && $end_loop_b +1 < $total_pages_bookings ) echo "<li><span>...</span></li>"; elseif ($end_loop_b < $total_pages_bookings && $end_loop_b +1 == $total_pages_bookings) $end_loop_b = $total_pages_bookings -1 ;?>
            
            <?php if ($current_page_bookings < $total_pages_bookings): ?>
                <li><a href="admin.php?tab=bookings-content&bpage=<?php echo $current_page_bookings + 1; ?>&bsearch=<?php echo urlencode($search_term_bookings); ?>&bstatus=<?php echo urlencode($filter_status_bookings);?>">›</a></li>
                <li><a href="admin.php?tab=bookings-content&bpage=<?php echo $total_pages_bookings; ?>&bsearch=<?php echo urlencode($search_term_bookings); ?>&bstatus=<?php echo urlencode($filter_status_bookings);?>">»</a></li>
            <?php endif; ?>
        </ul>
    </nav>
    <?php endif; ?>

<?php endif; ?>