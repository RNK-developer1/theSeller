<?php
    require("config.php");

    if (empty($_SESSION['user']) || $_SESSION['user']['active'] == 0) {
        header("Location: index.php");
        die("Перенаправление: index.php");
    }

    $page_start = 500*($_GET['page'] ? $_GET['page']-1 : 0);

    $query = "
    SELECT SQL_CALC_FOUND_ROWS fio, phone, email,
        IF (referrer LIKE '%vk.com%', referrer, '') AS vk_url
    FROM orders
    WHERE fio IS NOT NULL AND fio <> ''
        AND owner_id = :seller_id " .
        (($_GET['item_id']) ? " AND item_id = :item_id " : "") .
        (($_GET['order_date']) ? " AND DATE(created_at) >= :order_date " : "") .
        (($_GET['order_date_end']) ? " AND DATE(created_at) <= :order_date_end " : "") .
    " GROUP BY phone
    ORDER BY fio ASC
    LIMIT " . $page_start . ", 500";
//echo $query;
    $query_params =
        array(
            //':user_id' => $_SESSION['user']['id'],
            ':seller_id' => (($_GET['seller_id'] or $_GET['seller_id'] == '0') ? $_GET['seller_id'] : $_SESSION['user']['id']),
        );
    if ($_GET['item_id']) {
        $query_params[':item_id'] = $_GET['item_id'];
    }
    if ($_GET['order_date']) {
        $query_params[':order_date'] = $_GET['order_date'];
        if ($_GET['order_date_end']) $query_params[':order_date_end'] = $_GET['order_date_end'];
        else $query_params['order_date_end'] = $_GET['order_date'];
    }
//var_dump($query_params);
    try {
        $stmt = $db->prepare($query);
        $result = $stmt->execute($query_params);
    } catch (PDOException $ex) {
        die("Невозможно выполнить запрос: " . $ex->getMessage());
    }

    $clients = $stmt->fetchAll();  //echo '<pre>'; var_dump($clients); echo '</pre>';

    // =============================================================================================
    // Определение общего количества уникальных клиентов

    $query = "SELECT FOUND_ROWS() as `cnt`";

    try{
        $stmt = $db->prepare($query);
        $result = $stmt->execute();
    }
    catch(PDOException $ex){ die("Невозможно выполнить запрос: " . $ex->getMessage()); }

    $clients_full_count = $stmt->fetch();

    // =============================================================================================
    //  Фильтр по предпринимателям
    if ($_SESSION['user']['group_id'] == 2) {
        $select_sellers = array();
        $query = "
                SELECT
                    CONCAT(REPEAT(' -',sellers_for_sellers.depth),users.username) as username,
                    users.id as id
                FROM sellers_for_sellers, users
                WHERE
                    users.id = sellers_for_sellers.subseller_id AND
                    sellers_for_sellers.seller_id = :user_id
            ";
        $query_params = array(
            ':user_id' => $_SESSION['user']['id']
        );

        try{
            $stmt = $db->prepare($query);
            $result = $stmt->execute($query_params);
        }
        catch(PDOException $ex){ die("Невозможно выполнить запрос: " . $ex->getMessage()); }

        $select_sellers = array_merge($select_sellers,$stmt->fetchAll());
    } else {
        $query = "
                    SELECT
                        owner.username as username,
                        owner.id as id
                    FROM users as owner, operators_for_sellers
                    WHERE
                        owner.id = operators_for_sellers.seller_id AND
                        operators_for_sellers.operator_id = :user_id
                ";
            $query_params = array(
                ':user_id' => $_SESSION['user']['id']
            );

        try{
            $stmt = $db->prepare($query);
            $result = $stmt->execute($query_params);
        }
        catch(PDOException $ex){ die("Невозможно выполнить запрос: " . $ex->getMessage()); }

        $select_sellers = $stmt->fetchAll();
    }
    // =============================================================================================

    // =============================================================================================
    // Фильтр по товарам
    $query = "
        SELECT
            item.uuid as uuid,
            item.name as name
        FROM users as owner, item
            ".(($_SESSION['user']['group_id'] != 2) ? "
            , operators_for_sellers WHERE
            owner.id = operators_for_sellers.seller_id AND
            operators_for_sellers.operator_id = :user_id AND
            (:owner_id = '0' OR owner.id = :owner_id) AND
            item.owner_id = owner.id
            " :
        " WHERE
            item.owner_id = owner.id AND
            (:seller_id = '0' OR owner.id = :seller_id) AND
            owner.id IN (SELECT subseller_id FROM sellers_for_sellers WHERE sellers_for_sellers.seller_id = :user_id)
            ");
    if ($_SESSION['user']['group_id'] == 2) {
        $query_params = array(
            ':seller_id' => $_GET['seller_id'] || (!$_GET['seller_id'] && $_GET['seller_id']=='0') ? $_GET['seller_id'] : $_SESSION['user']['id'],
                ':user_id' => $_SESSION['user']['id']
            );
    } else {
        $query_params = array(
            ':owner_id' => $_GET['seller_id'],
            ':user_id' => $_SESSION['user']['id']
        );
    }


    try{
        $stmt = $db->prepare($query);
        $result = $stmt->execute($query_params);
    }
    catch(PDOException $ex){ die("Невозможно выполнить запрос: " . $ex->getMessage()); }

    $select_items = $stmt->fetchAll();
    // =============================================================================================
    //
    $query = "
                SELECT
                    *
                FROM
                    statuses
                WHERE id <> 0
                ORDER BY id ASC
            ";
    try{
        $stmt = $db->prepare($query);
        $result = $stmt->execute($query_params);
    }
    catch(PDOException $ex){ die("Невозможно выполнить запрос02: " . $ex->getMessage()); }

    $statuses_step1 = $stmt->fetchAll();

?>
<!doctype html>
<html lang="ru">
<?php include 'header.php' ?>
<body>
<?php include 'top_menu.php' ?>
<div style="display: none;">
</div>
<?php
    $loc = '';
    if ($_GET['seller_id'] or $_GET['seller_id'] == '0') {
        $loc .= '&seller_id='.$_GET['seller_id'];
    }
    if ($_GET['item_id']or $_GET['item_id'] == '0') {
        $loc .= ($loc!='' ? '&item_id=' : '?item_id=').$_GET['item_id'];
    }
    if ($_GET['order_date']) {
        $loc .= '&order_date='.$_GET['order_date'];
    }
    if ($_GET['order_date_end']) {
        $loc .= '&order_date_end='.$_GET['order_date_end'];
    }
?>
<div class="container">
    <h3>Список клиентов<?php echo ', показано '.count($clients).' из '.$clients_full_count['cnt'];?></h3>
    <?php
        if ($clients_full_count['cnt'] > count($orders)) {
            echo '<h3>Страницы:';
                for ($pg = 1; 500*($pg-1) <= $clients_full_count['cnt']; $pg++) {
                    if ($_GET['page'] == $pg or (!$_GET['page'] and $pg==1)) {
                        echo "&nbsp;&nbsp;<b>".$pg."</b>";
                    } else {
                        echo "&nbsp;&nbsp;<a href='clients_list.php?page=".$pg.$loc."'>".$pg."</a>";
                    }
                }
            echo '</h3>';
        }
        if ($_GET['page']) {
            $loc .= '&page='.$_GET['page'];
        }
    ?>
    <table class='table table-hover table-bordered table-fixed-header'>
        <thead class="header">
            <th>ФИО</th>
            <th>Телефон</th>
            <th>Email</th>
            <th>ID вконтакте</th>

        </thead>
    <?php foreach ($clients as $client){ ?>
                <tr id='client'>
                    <td><?php echo $client['fio'] ?></td>
                    <td><?php echo $client['phone'] ?></td>
                    <td><?php echo $client['email'] ?></td>

<?php
    $vk_error = false;
    $vk_id = $client['vk_url'];
    $pattern = '/id=([0-9]+)/';
    if (preg_match($pattern, $vk_id, $matches)) {
        $vk_id = 'id' . $matches[1];
    }
    $pattern = '/im\?.*sel=([a-zA-Z0-9_]+)/';
    if (preg_match($pattern, $vk_id, $matches)) {
        $vk_id = 'id' . $matches[1];
    }
    $pattern = '/(id[0-9]+)/';
    if (preg_match($pattern, $vk_id, $matches)) {
        $vk_id = $matches[0];
    }
    $pattern_away = '/(vk.com\/away)/';
    $pattern_feed = '/(vk.com\/feed)/';
    $pattern_app = '/(vk.com\/app)/';
    $pattern_friends = '/(vk.com\/friends)/';
    $pattern_login = '/(vk.com\/login)/';
    $pattern_settings = '/(vk.com\/settings)/';
    $pattern_vk = '/(vk.com\/vk)/';
    $pattern_im = '/(vk.com\/im)/';
    if (
        //(!preg_match($pattern_app, $vk_id, $matches)) and
        (!preg_match($pattern_friends, $vk_id, $matches)) and
        (!preg_match($pattern_settings, $vk_id, $matches)) and
        (!preg_match($pattern_login, $vk_id, $matches)) and
        (!preg_match($pattern_feed, $vk_id, $matches)) and
        //(!preg_match($pattern_im, $vk_id, $matches)) and
        //(!preg_match($pattern_vk, $vk_id, $matches)) and
        (!preg_match($pattern_away, $vk_id, $matches))) {
        $pattern = '/vk.com\/([a-zA-Z0-9_]+)/';
        if (preg_match($pattern, $vk_id, $matches)) {
            $vk_id = $matches[1];
        }
    } else {
        $vk_id = 'Некорректные данные о профиле ВКонтакте ('. $vk_id .')';
        $vk_error = true;
    }
?>

                    <td><?php echo $vk_id ?></td>
                </tr>
    <?php   } ?>
    </table>
</div>
<script type='text/javascript'>
    $('.table-fixed-header').fixedHeader();
</script>
</body>
</html>