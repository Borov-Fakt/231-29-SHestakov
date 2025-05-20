<?php
// admin_tabs/special_offers_tab.php
// Предполагается, что $conn (объект соединения с БД) и $admin_id уже определены
// в основном файле admin.php и сессия администратора проверена.

// Пагинация для спецпредложений
$offers_per_page = 10;
$current_page_offers = isset($_GET['spage']) && is_numeric($_GET['spage']) ? (int)$_GET['spage'] : 1;
if ($current_page_offers < 1) $current_page_offers = 1;

// Поиск и фильтрация для спецпредложений
$search_term_offers = isset($_GET['ssearch']) ? trim($_GET['ssearch']) : '';
$filter_active_offers = isset($_GET['sfilter_active']) ? $_GET['sfilter_active'] : 'all';

$where_clauses_offers = [];
$params_offers_for_where = []; // Параметры только для WHERE части
$types_offers_for_where = "";   // Типы только для WHERE части

if (!empty($search_term_offers)) {
    $where_clauses_offers[] = "(title LIKE ? OR details_direction LIKE ?)"; // Уточнил, что title из special_offers
    $like_term_offers = "%{$search_term_offers}%";
    $params_offers_for_where[] = $like_term_offers;
    $params_offers_for_where[] = $like_term_offers;
    $types_offers_for_where .= "ss";
}

if ($filter_active_offers === 'active') {
    $where_clauses_offers[] = "is_active = TRUE";
} elseif ($filter_active_offers === 'inactive') {
    $where_clauses_offers[] = "is_active = FALSE";
}

$sql_where_offers_str = "";
if (!empty($where_clauses_offers)) {
    $sql_where_offers_str = " WHERE " . implode(" AND ", $where_clauses_offers);
}

// Получаем общее количество спецпредложений
$sql_count_offers = "SELECT COUNT(offer_id) as total FROM special_offers" . $sql_where_offers_str; // COUNT(offer_id) для ясности
$stmt_count_offers = $conn->prepare($sql_count_offers);
$total_offers = 0;
if ($stmt_count_offers) {
    if (!empty($types_offers_for_where)) {
        $bind_params_count = [];
        $bind_params_count[] = &$types_offers_for_where; 
        for ($i = 0; $i < count($params_offers_for_where); $i++) {
            $bind_params_count[] = &$params_offers_for_where[$i]; 
        }
        call_user_func_array(array($stmt_count_offers, 'bind_param'), $bind_params_count);
    }
    $stmt_count_offers->execute();
    $result_count_offers = $stmt_count_offers->get_result();
    if($row_count_result = $result_count_offers->fetch_assoc()){ // Исправлено имя переменной
        $total_offers = $row_count_result['total'];
    }
    $stmt_count_offers->close();
} else {
    error_log("Admin Special Offers Tab - Error preparing count query: " . $conn->error);
}

$total_pages_offers = $offers_per_page > 0 ? ceil($total_offers / $offers_per_page) : 0;
if ($current_page_offers > $total_pages_offers && $total_pages_offers > 0) {
    $current_page_offers = $total_pages_offers;
}
$offset_offers = ($current_page_offers - 1) * $offers_per_page;
if ($offset_offers < 0) $offset_offers = 0;


// Получение спецпредложений для текущей страницы
$sql_offers_list = "SELECT offer_id, title, price_from, currency_code, image_path, is_active, sort_order 
                    FROM special_offers" . $sql_where_offers_str . " ORDER BY sort_order ASC, title ASC LIMIT ? OFFSET ?";
$stmt_offers_list = $conn->prepare($sql_offers_list);

$current_params_for_select = $params_offers_for_where; 
$current_types_for_select = $types_offers_for_where;   

$current_params_for_select[] = $offers_per_page; $current_types_for_select .= "i";
$current_params_for_select[] = $offset_offers; $current_types_for_select .= "i";

$offers_list = [];
if ($stmt_offers_list) {
    if (!empty($current_types_for_select)){
        $bind_params_select = [];
        $bind_params_select[] = &$current_types_for_select;
        for ($i = 0; $i < count($current_params_for_select); $i++) {
            $bind_params_select[] = &$current_params_for_select[$i];
        }
        call_user_func_array(array($stmt_offers_list, 'bind_param'), $bind_params_select);
    }
    if (!$stmt_offers_list->execute()) {
        error_log("Admin Special Offers Tab - Error executing SELECT offers query: " . $stmt_offers_list->error);
    } else {
        $result_offers_data = $stmt_offers_list->get_result(); // Исправлено имя переменной
        while ($row = $result_offers_data->fetch_assoc()) {
            $offers_list[] = $row;
        }
    }
    $stmt_offers_list->close();
} else {
     error_log("Admin Special Offers Tab - Error preparing SELECT offers query: " . $conn->error);
}

// Сообщения для этой вкладки (если есть)
$offers_tab_message = $_SESSION['offers_message'] ?? '';
$offers_tab_message_type = $_SESSION['offers_message_type'] ?? 'info';
unset($_SESSION['offers_message'], $_SESSION['offers_message_type']);

?>
<h2>Управление Спецпредложениями</h2>

<?php if ($offers_tab_message): ?>
    <div class="admin-message <?php echo htmlspecialchars($offers_tab_message_type); ?>">
        <?php echo htmlspecialchars($offers_tab_message); ?>
    </div>
<?php endif; ?>

<div class="admin-actions-bar">
    <a href="admin_actions/add_offer_form.php" class="btn btn-primary"><i class="fas fa-plus-circle"></i> Добавить спецпредложение</a>
    <form action="admin.php" method="GET" class="filter-form">
        <input type="hidden" name="tab" value="special-offers-content">
        <input type="text" name="ssearch" placeholder="Поиск по названию..." value="<?php echo htmlspecialchars($search_term_offers); ?>">
        <select name="sfilter_active">
            <option value="all" <?php echo ($filter_active_offers == 'all') ? 'selected' : ''; ?>>Все статусы</option>
            <option value="active" <?php echo ($filter_active_offers == 'active') ? 'selected' : ''; ?>>Активные</option>
            <option value="inactive" <?php echo ($filter_active_offers == 'inactive') ? 'selected' : ''; ?>>Неактивные</option>
        </select>
        <button type="submit" class="btn btn-secondary"><i class="fas fa-filter"></i> Фильтр</button>
        <?php if(!empty($search_term_offers) || $filter_active_offers !== 'all'): ?>
             <a href="admin.php?tab=special-offers-content" class="btn btn-link" style="margin-left: 0.5rem;">Сбросить</a>
        <?php endif; ?>
    </form>
</div>


<?php if (empty($offers_list) && $total_offers > 0 && $current_page_offers > 1): ?>
    <p>Спецпредложения не найдены на этой странице. <a href="admin.php?tab=special-offers-content&spage=1&ssearch=<?php echo urlencode($search_term_offers); ?>&sfilter_active=<?php echo urlencode($filter_active_offers);?>">Вернуться на первую страницу</a>.</p>
<?php elseif (empty($offers_list)): ?>
    <div class="placeholder-content">
        <i class="fas fa-tags fa-3x"></i>
        <p>Спецпредложения еще не созданы<?php echo !empty($search_term_offers) || $filter_active_offers != 'all' ? ' по вашему запросу' : ''; ?>.</p>
    </div>
<?php else: ?>
    <div class="table-responsive-wrapper">
    <table class="admin-table special-offers-table">
        <thead>
            <tr>
                <th>ID</th>
                <th style="width:80px; text-align:center;">Изобр.</th>
                <th>Заголовок</th>
                <th>Цена от</th>
                <th>Статус</th>
                <th>Порядок</th>
                <th style="text-align:right;">Действия</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($offers_list as $offer_item): ?>
            <tr>
                <td><?php echo $offer_item['offer_id']; ?></td>
                <td style="text-align:center;">
                    <?php 
                    // Путь к изображению, который хранится в БД (например, 'uploads/special_offers/image.jpg')
                    // является путем относительно корня сайта.
                    // Файл admin.php, который подключает этот users_tab.php, находится в корне.
                    // Следовательно, для тега <img> мы можем использовать этот путь как есть (без ../)
                    $image_path_from_root = '';
                    if (!empty($offer_item['image_path'])) {
                        // Проверяем существование файла относительно КОРНЕВОЙ ДИРЕКТОРИИ PHP СКРИПТА
                        // Это может потребовать DOCUMENT_ROOT, если include не меняет CWD
                        // Для простоты и если 'uploads' доступен из корня веб-сервера:
                        $path_to_check = trim($offer_item['image_path'], '/');
                        if (file_exists($path_to_check)) { // Проверяем от текущей рабочей директории (корень сайта)
                           $image_path_from_root = htmlspecialchars($path_to_check);
                        }
                    }
                    ?>
                    <?php if (!empty($image_path_from_root)): ?>
                        <img src="<?php echo $image_path_from_root; ?>" alt="<?php echo htmlspecialchars($offer_item['title']); ?>" class="admin-table-image-preview">
                    <?php else: ?>
                        <span class="no-image-placeholder"><i class="fas fa-image"></i></span>
                    <?php endif; ?>
                </td>
                <td><?php echo htmlspecialchars($offer_item['title']); ?></td>
                <td><?php echo htmlspecialchars(number_format((float)$offer_item['price_from'], 0, '.', ' ') . ' ' . $offer_item['currency_code']); ?></td>
                <td>
                    <?php if ($offer_item['is_active']): ?>
                        <span class="status-badge status-active">Активно</span>
                    <?php else: ?>
                        <span class="status-badge status-inactive">Неактивно</span>
                    <?php endif; ?>
                </td>
                <td><?php echo $offer_item['sort_order']; ?></td>
                <td class="actions-cell">
                     <a href="admin_actions/add_offer_form.php?offer_id=<?php echo $offer_item['offer_id']; ?>&spage=<?php echo $current_page_offers; ?>&ssearch=<?php echo urlencode($search_term_offers); ?>&sfilter_active=<?php echo urlencode($filter_active_offers);?>" 
                       class="action-icon edit" title="Редактировать"><i class="fas fa-edit"></i></a>
                    <a href="admin_actions/toggle_offer_status.php?offer_id=<?php echo $offer_item['offer_id']; ?>¤t_status=<?php echo $offer_item['is_active']; ?>&spage=<?php echo $current_page_offers; ?>&ssearch=<?php echo urlencode($search_term_offers); ?>&sfilter_active=<?php echo urlencode($filter_active_offers);?>" 
                       class="action-icon <?php echo $offer_item['is_active'] ? 'disable' : 'enable'; ?>" 
                       title="<?php echo $offer_item['is_active'] ? 'Сделать неактивным' : 'Сделать активным'; ?>">
                        <i class="fas <?php echo $offer_item['is_active'] ? 'fa-toggle-on' : 'fa-toggle-off'; ?>"></i>
                    </a>
                    <a href="admin_actions/delete_offer.php?offer_id=<?php echo $offer_item['offer_id']; ?>&spage=<?php echo $current_page_offers; ?>&ssearch=<?php echo urlencode($search_term_offers); ?>&sfilter_active=<?php echo urlencode($filter_active_offers);?>" 
                       class="action-icon delete" title="Удалить" 
                       onclick="return confirm('Вы уверены, что хотите удалить спецпредложение \'<?php echo htmlspecialchars(addslashes($offer_item['title'])); ?>\'?');">
                        <i class="fas fa-trash-alt"></i>
                    </a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    </div>

    <!-- Пагинация для спецпредложений -->
    <?php if ($total_pages_offers > 1): ?>
    <nav class="pagination-nav">
        <ul class="pagination">
            <?php if ($current_page_offers > 1): ?>
                <li><a href="admin.php?tab=special-offers-content&spage=1&ssearch=<?php echo urlencode($search_term_offers); ?>&sfilter_active=<?php echo urlencode($filter_active_offers);?>">«</a></li>
                <li><a href="admin.php?tab=special-offers-content&spage=<?php echo $current_page_offers - 1; ?>&ssearch=<?php echo urlencode($search_term_offers); ?>&sfilter_active=<?php echo urlencode($filter_active_offers);?>">‹</a></li>
            <?php endif; ?>
            <?php
            $range_offers = 2; 
            $start_loop_offers = max(1, $current_page_offers - $range_offers);
            $end_loop_offers = min($total_pages_offers, $current_page_offers + $range_offers);
            if ($start_loop_offers > 1 && $start_loop_offers -1 > 1 ) echo "<li><span>...</span></li>"; elseif ($start_loop_offers > 1 && $start_loop_offers -1 == 1) $start_loop_offers = 1;
            for ($i = $start_loop_offers; $i <= $end_loop_offers; $i++): ?>
                <li class="<?php echo ($i == $current_page_offers) ? 'active' : ''; ?>">
                    <a href="admin.php?tab=special-offers-content&spage=<?php echo $i; ?>&ssearch=<?php echo urlencode($search_term_offers); ?>&sfilter_active=<?php echo urlencode($filter_active_offers);?>"><?php echo $i; ?></a>
                </li>
            <?php endfor; ?>
            <?php if ($end_loop_offers < $total_pages_offers && $end_loop_offers + 1 < $total_pages_offers ) echo "<li><span>...</span></li>"; elseif ($end_loop_offers < $total_pages_offers && $end_loop_offers + 1 == $total_pages_offers) $end_loop_offers = $total_pages_offers -1 ;?>
            <?php if ($current_page_offers < $total_pages_offers): ?>
                <li><a href="admin.php?tab=special-offers-content&spage=<?php echo $current_page_offers + 1; ?>&ssearch=<?php echo urlencode($search_term_offers); ?>&sfilter_active=<?php echo urlencode($filter_active_offers);?>">›</a></li>
                <li><a href="admin.php?tab=special-offers-content&spage=<?php echo $total_pages_offers; ?>&ssearch=<?php echo urlencode($search_term_offers); ?>&sfilter_active=<?php echo urlencode($filter_active_offers);?>">»</a></li>
            <?php endif; ?>
        </ul>
    </nav>
    <?php endif; ?>
<?php endif; ?>