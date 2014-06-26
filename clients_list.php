<?php
    require("config.php");

    if (empty($_SESSION['user']) || $_SESSION['user']['active'] == 0) {
        header("Location: index.php");
        die("Перенаправление: index.php");
    }

    $page_start = 500*($_GET['page'] ? $_GET['page']-1 : 0);

    $query = "
    SELECT SQL_CALC_FOUND_ROWS fio, phone, email,
        IF (referrer LIKE '%vk.com%', referrer, 'нет данных') AS vk_url
    FROM orders
    WHERE fio IS NOT NULL AND fio <> ''
        AND owner_id = :seller_id " .
        (($_GET['item_id']) ? " AND item_id = :item_id " : "") .
        (($_GET['order_date']) ? " AND DATE(orders.created_at) >= :order_date) " : "") .
        (($_GET['order_date_end']) ? " AND DATE(orders.created_at) <= :order_date_end) " : "") .
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
                    <td><?php echo $client['vk_url'] ?></td>
                </tr>
    <?php   } ?>
    </table>
</div>
<script type='text/javascript'>
    $('.table-fixed-header').fixedHeader();
</script>
</body>
</html>