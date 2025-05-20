<?php
// admin_tabs/users_tab.php
// Предполагается, что этот файл подключается из admin.php, где $conn уже определен и сессия проверена.
// Также $admin_id доступен из admin.php.

// Пагинация - базовые переменные
$users_per_page = 10;
$current_page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
if ($current_page < 1) $current_page = 1;

// Поиск и фильтрация (базовый пример)
$search_term = isset($_GET['search']) ? trim($_GET['search']) : '';
$filter_active = isset($_GET['filter_active']) ? $_GET['filter_active'] : 'all';

$where_clauses = [];
$params = [];
$types = "";

if (!empty($search_term)) {
    $where_clauses[] = "(users.first_name LIKE ? OR users.last_name LIKE ? OR users.email LIKE ?)"; // Уточнили users.
    $like_term = "%{$search_term}%";
    $params[] = $like_term; $params[] = $like_term; $params[] = $like_term;
    $types .= "sss";
}

if ($filter_active === 'active') {
    $where_clauses[] = "users.is_active = TRUE";
} elseif ($filter_active === 'inactive') {
    $where_clauses[] = "users.is_active = FALSE";
}

$sql_where = "";
if (!empty($where_clauses)) {
    $sql_where = " WHERE " . implode(" AND ", $where_clauses);
}

// Получаем общее количество пользователей для пагинации
$sql_count = "SELECT COUNT(users.user_id) as total FROM users" . $sql_where; // Уточнили COUNT(users.user_id)
$stmt_count = $conn->prepare($sql_count);
$total_users = 0;
if ($stmt_count) {
    if (!empty($types)) { // bind_param только если есть параметры
        $stmt_count->bind_param($types, ...$params);
    }
    $stmt_count->execute();
    $result_count_users = $stmt_count->get_result();
    if($row_count = $result_count_users->fetch_assoc()){
        $total_users = $row_count['total'];
    }
    $stmt_count->close();
} else {
    error_log("Admin Users Tab - Error preparing count query: " . $conn->error);
}
$total_pages = ceil($total_users / $users_per_page);
if ($current_page > $total_pages && $total_pages > 0) $current_page = $total_pages;
$offset = ($current_page - 1) * $users_per_page;
if ($offset < 0) $offset = 0; // Убедимся, что offset не отрицательный


// Получаем пользователей для текущей страницы
$sql_users = "SELECT user_id, first_name, last_name, email, phone_number, is_active, is_admin, created_at 
              FROM users" . $sql_where . " ORDER BY users.last_name, users.first_name LIMIT ? OFFSET ?";
$stmt_users = $conn->prepare($sql_users);

$current_params = $params;
$current_types = $types;

$current_params[] = $users_per_page; $current_types .= "i";
$current_params[] = $offset; $current_types .= "i";

$users = [];
if ($stmt_users) {
    if (!empty($current_types)){
         $stmt_users->bind_param($current_types, ...$current_params);
    }
    $stmt_users->execute();
    $result_users = $stmt_users->get_result();
    while ($row = $result_users->fetch_assoc()) {
        $users[] = $row;
    }
    $stmt_users->close();
} else {
     error_log("Admin Users Tab - Error preparing select users query: " . $conn->error);
}

$user_management_message = $_SESSION['user_management_message'] ?? '';
$user_management_message_type = $_SESSION['user_management_message_type'] ?? 'info';
unset($_SESSION['user_management_message'], $_SESSION['user_management_message_type']);

?>
<h2>Управление Пользователями</h2>

<?php if ($user_management_message): ?>
    <div class="admin-message <?php echo htmlspecialchars($user_management_message_type); ?>">
        <?php echo htmlspecialchars($user_management_message); ?>
    </div>
<?php endif; ?>

<div class="admin-actions-bar">
    <a href="admin_actions/add_user_form.php" class="btn btn-primary"><i class="fas fa-user-plus"></i> Добавить пользователя</a>
    <form action="admin.php" method="GET" class="filter-form">
        <input type="hidden" name="tab" value="users-content">
        <input type="text" name="search" placeholder="Поиск по имени/email..." value="<?php echo htmlspecialchars($search_term); ?>">
        <select name="filter_active">
            <option value="all" <?php echo ($filter_active == 'all') ? 'selected' : ''; ?>>Все статусы</option>
            <option value="active" <?php echo ($filter_active == 'active') ? 'selected' : ''; ?>>Активные</option>
            <option value="inactive" <?php echo ($filter_active == 'inactive') ? 'selected' : ''; ?>>Неактивные</option>
        </select>
        <button type="submit" class="btn btn-secondary"><i class="fas fa-filter"></i> Фильтр</button>
        <?php if(!empty($search_term) || $filter_active !== 'all'): ?>
             <a href="admin.php?tab=users-content" class="btn btn-link" style="margin-left: 0.5rem;">Сбросить</a>
        <?php endif; ?>
    </form>
</div>


<?php if (empty($users) && $total_users > 0 && $current_page > 1): ?>
    <p>Пользователей не найдено на этой странице. <a href="admin.php?tab=users-content&page=1&search=<?php echo urlencode($search_term); ?>&filter_active=<?php echo urlencode($filter_active);?>">Вернуться на первую страницу</a>.</p>
<?php elseif (empty($users)): ?>
    <div class="placeholder-content">
        <i class="fas fa-users fa-3x"></i>
        <p>Пользователи не найдены<?php echo !empty($search_term) || $filter_active != 'all' ? ' по вашему запросу' : ''; ?>.</p>
    </div>
<?php else: ?>
    <div class="table-responsive-wrapper"> <!-- Обертка для адаптивной прокрутки -->
    <table class="admin-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Имя</th>
                <th>Email</th>
                <th>Телефон</th>
                <th>Статус</th>
                <th>Админ</th>
                <th>Зарегистрирован</th>
                <th style="text-align:right;">Действия</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($users as $user): ?>
            <tr>
                <td><?php echo $user['user_id']; ?></td>
                <td><?php echo htmlspecialchars(trim($user['first_name'] . ' ' . $user['last_name'])); ?></td>
                <td><?php echo htmlspecialchars($user['email']); ?></td>
                <td><?php echo htmlspecialchars($user['phone_number'] ?: '-'); ?></td>
                <td>
                    <?php if ($user['is_active']): ?>
                        <span class="status-badge status-active">Активен</span>
                    <?php else: ?>
                        <span class="status-badge status-inactive">Неактивен</span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php echo $user['is_admin'] ? '<span class="status-badge status-admin">Да</span>' : 'Нет'; ?>
                </td>
                <td><?php echo date("d.m.Y H:i", strtotime($user['created_at'])); ?></td>
                <td class="actions-cell">
                    <a href="admin_actions/edit_user_form.php?user_id=<?php echo $user['user_id']; ?>&page=<?php echo $current_page; ?>&search=<?php echo urlencode($search_term); ?>&filter_active=<?php echo urlencode($filter_active);?>" 
                       class="action-icon edit" title="Редактировать"><i class="fas fa-edit"></i></a>
                    <?php if ($user['user_id'] != $admin_id): // Админ не может менять статус/удалять себя через эти кнопки ?>
                        <a href="admin_actions/toggle_user_status.php?user_id=<?php echo $user['user_id']; ?>¤t_status=<?php echo $user['is_active']; ?>&page=<?php echo $current_page; ?>&search=<?php echo urlencode($search_term); ?>&filter_active=<?php echo urlencode($filter_active);?>" 
                           class="action-icon <?php echo $user['is_active'] ? 'disable' : 'enable'; ?>" 
                           title="<?php echo $user['is_active'] ? 'Деактивировать' : 'Активировать'; ?>">
                            <i class="fas <?php echo $user['is_active'] ? 'fa-toggle-on' : 'fa-toggle-off'; ?>"></i>
                        </a>
                        <?php if(!$user['is_admin']): // Простого пользователя можно удалить (админа - нет через эту кнопку) ?>
                        <a href="admin_actions/delete_user.php?user_id=<?php echo $user['user_id']; ?>&page=<?php echo $current_page; ?>&search=<?php echo urlencode($search_term); ?>&filter_active=<?php echo urlencode($filter_active);?>" 
                           class="action-icon delete" 
                           title="Удалить" 
                           onclick="return confirm('Вы уверены, что хотите удалить пользователя <?php echo htmlspecialchars(addslashes($user['email'])); ?>? Это действие необратимо!');">
                            <i class="fas fa-trash-alt"></i>
                        </a>
                        <?php endif; ?>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    </div> <!-- .table-responsive-wrapper -->

    <?php if ($total_pages > 1): ?>
    <nav class="pagination-nav">
        <ul class="pagination">
            <?php if ($current_page > 1): ?>
                <li><a href="admin.php?tab=users-content&page=1&search=<?php echo urlencode($search_term); ?>&filter_active=<?php echo urlencode($filter_active);?>">«</a></li>
                <li><a href="admin.php?tab=users-content&page=<?php echo $current_page - 1; ?>&search=<?php echo urlencode($search_term); ?>&filter_active=<?php echo urlencode($filter_active);?>">‹</a></li>
            <?php endif; ?>

            <?php
            $range = 2; // Сколько ссылок показывать до и после текущей страницы
            $start_loop = max(1, $current_page - $range);
            $end_loop = min($total_pages, $current_page + $range);

            if ($start_loop > 1 && $start_loop - 1 > 1 ) echo "<li><span>...</span></li>"; elseif ($start_loop > 1 && $start_loop -1 == 1) $start_loop = 1;


            for ($i = $start_loop; $i <= $end_loop; $i++): ?>
                <li class="<?php echo ($i == $current_page) ? 'active' : ''; ?>">
                    <a href="admin.php?tab=users-content&page=<?php echo $i; ?>&search=<?php echo urlencode($search_term); ?>&filter_active=<?php echo urlencode($filter_active);?>"><?php echo $i; ?></a>
                </li>
            <?php endfor; ?>

            <?php if ($end_loop < $total_pages && $end_loop + 1 < $total_pages ) echo "<li><span>...</span></li>"; elseif ($end_loop < $total_pages && $end_loop + 1 == $total_pages) $end_loop = $total_pages -1 ;?>
            
            <?php if ($current_page < $total_pages): ?>
                <li><a href="admin.php?tab=users-content&page=<?php echo $current_page + 1; ?>&search=<?php echo urlencode($search_term); ?>&filter_active=<?php echo urlencode($filter_active);?>">›</a></li>
                <li><a href="admin.php?tab=users-content&page=<?php echo $total_pages; ?>&search=<?php echo urlencode($search_term); ?>&filter_active=<?php echo urlencode($filter_active);?>">»</a></li>
            <?php endif; ?>
        </ul>
    </nav>
    <?php endif; ?>

<?php endif; ?>