<?php
/**
 * Created by PhpStorm.
 * User: Андрей
 * Date: 25.02.14
 * Time: 19:57
 */
require("config.php");

if (empty($_SESSION['user']) || $_SESSION['user']['active'] == 0 || $_SESSION['user']['group_id'] != 2) {
    header("Location: index.php");
    die("Перенаправление: index.php");
}

$dstart = new DateTime($_GET['order_date']);
$dend = new DateTime((!$_GET['order_date_end'] or $_GET['order_date_end'] == '') ? $_GET['order_date'] : $_GET['order_date_end']);	
if ($selected_seller AND $selected_seller != '0') {
	$query = " 
				SELECT *
				FROM users
				WHERE
					id = :user_id
			";		
		$query_params = array( 
			':user_id' => $selected_seller
		); 
			 
	try{ 
		$stmt = $db->prepare($query); 
		$result = $stmt->execute($query_params); 
	} 
	catch(PDOException $ex){ die("Невозможно выполнить запрос: " . $ex->getMessage()); } 	
	
	$selected_user = $stmt->fetch();	
}

$out_table = array();
$statuses_step1 = true;
$where = '';
$and_where = '';
$query_param = array();
if (isset($_GET['order_date']) && isset($_GET['order_date_end']) && $_GET['order_date_end'] && $_GET['order_date']){
    $where = 'where created_at between :date and :date_end ';
    $and_where = ' and created_at between :date and :date_end ';
    $query_param[':date'] = $_GET['order_date'];
    $query_param[':date_end'] = $_GET['order_date_end'];
}
$item_id = '';
if (isset($_GET['item_id']) && $_GET['item_id']){
    if (strlen($where) > 1){
        $where .= ' and %s = :item_id';
        $and_where .= ' and %s = :item_id';
    }else{
        $where = ' where %s = :item_id';
        $and_where = ' and  %s = :item_id';
    }
    $query_param[':item_id'] = $_GET['item_id'];
}
$preorder = false;
if (isset($selected_seller) && $selected_seller){
    $preorder = ' select count(preorder.item_uuid) click from item join preorder on item.uuid = preorder.item_uuid where item.owner_id = :seller_id '.$and_where;
    if ((strlen($where) > 1)){
        $where .= ' and owner_id = :seller_id ';
        $and_where .= '  and owner_id = :seller_id';
    }else{
        $where = ' where owner_id = :seller_id ';
        $and_where = ' and owner_id = :seller_id ';
    }
//    if (isset($_GET['item_id']) && $_GET['item_id']){
//        $preorder = ' select count(preorder.item_uuid) click from item join preorder on item.uuid = preorder.item_uuid where item.owner_id = :seller_id and item.uuid = :item_id ';
//    }else{
//        $preorder = ' select count(preorder.item_uuid) click from item join preorder on item.uuid = preorder.item_uuid where item.owner_id = :seller_id';
//    }

    $query_param[':seller_id'] = $selected_seller;
}

//выбор товаров
$query = "SELECT
            item.uuid as uuid,
            item.name as name
          FROM item";
try{
    $stmt = $db->prepare($query);
    $result = $stmt->execute();
}catch(PDOException $ex){ die("Невозможно выполнить запрос: " . $ex->getMessage()); }

$select_items = $stmt->fetchAll();

//выбор пользователей
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
}catch(PDOException $ex){ die("Невозможно выполнить запрос: " . $ex->getMessage()); }

$select_sellers = $stmt->fetchAll();

//выбор кликов - 0
$count = 0;

$query = ($preorder) ? sprintf($preorder, 'item.uuid') : "select count(*) as click from preorder as p ".sprintf($where, 'item.uuid').' ';

try{
    $stmt = $db->prepare($query);
    $result = $stmt->execute($query_param);
    $res = $stmt->fetchAll();
}catch(PDOException $ex){ die("Невозможно выполнить запрос: " . $ex->getMessage()); }

array_push($out_table, $res[0]['click']);
//- сколько было оформлено заказов (таблица orders) - 1

$query = "select count(*) as click
          from orders as o  ".sprintf($where, 'item_id');

try{
    $stmt = $db->prepare($query);
    $result = $stmt->execute($query_param);
}catch(PDOException $ex){ die("Невозможно выполнить запрос: " . $ex->getMessage()); }

$res = $stmt->fetchAll();
array_push($out_table,$res[0]['click']);
//- % заказов относительно кликов - 2
array_push($out_table, (round(($out_table[1]/$out_table[0]), 4))*100 . '%');
//- сколько заказов отправлено (status_step2 не пустой) - 3
$query = "select count(*) as click
          from orders as o where status_step2 <> 0 " . sprintf($and_where, 'item_id');

try{
    $stmt = $db->prepare($query);
    $result = $stmt->execute($query_param);
}catch(PDOException $ex){ die("Невозможно выполнить запрос: " . $ex->getMessage()); }

$res = $stmt->fetchAll();
array_push($out_table, $res[0]['click']);
//- % отправленных относительно кол-ва заказов - 4
array_push($out_table, (round(($out_table[3]/$out_table[1]), 4))*100 . '%');
//- сколько незавершенных заказов (не отправлены и не отменены) - 5
$query = "select count(*) as click
          from orders as o where  status_step2 = 0 and status_step1 not in(10, 40) ".sprintf($and_where, 'item_id');

try{
    $stmt = $db->prepare($query);
    $result = $stmt->execute($query_param);
}catch(PDOException $ex){ die("Невозможно выполнить запрос: " . $ex->getMessage()); }

$res = $stmt->fetchAll();
array_push($out_table,$res[0]['click']);
//- % незавершенных относительно кол-ва заказов - 6
array_push($out_table, (round(($out_table[5]/$out_table[3]), 4))*100 . '%');
//- сколько заказов оплачено (status_step3 соответствующий - смотреть по таблице statuses) - 7
$query = "select count(*) as click
          from orders as o where status_step3  in(311, 20, 301, 310, 312)  " . sprintf($and_where, 'item_id');

try{
    $stmt = $db->prepare($query);
    $result = $stmt->execute($query_param);
}catch(PDOException $ex){ die("Невозможно выполнить запрос: " . $ex->getMessage()); }

$res = $stmt->fetchAll();
array_push($out_table, $res[0]['click']);
//- % оплаченных заказов относительно отправленных - 8
array_push($out_table, (round(($out_table[7]/$out_table[3]), 4))*100 . '%');
//- сколько заказов ещё не определены (лежат на Н.П. - status_step3 - пустой) - 9
$query = "select count(*) as click
          from orders as o where status_step3 = 0 and status_step2 not in(0, 10, 40) " . sprintf($and_where, 'item_id');

try{
    $stmt = $db->prepare($query);
    $result = $stmt->execute($query_param);
}catch(PDOException $ex){ die("Невозможно выполнить запрос: " . $ex->getMessage()); }

$res = $stmt->fetchAll();
array_push($out_table, $res[0]['click']);
//- % неопределённых от отправленных - 10
array_push($out_table, (round(($out_table[9]/$out_table[3]), 4))*100 . '%');
?>
<!doctype html>
<html lang="ru">
<?php include 'header.php' ?>
<body>
<?php include 'top_menu.php' ?>
<a href="reports.php">&larr; Вернуться в меню отчетов</a>
<h3 style="color: black;">Конверсия (<?php echo $selected_user ? $selected_user['username'] : 'все'; if ($_GET['order_date']) {?>, с <?php echo $dstart->format('d-m-y'); ?> по <?php echo $dend->format('d-m-y'); }?>)</h3>		
<table class="table_center_50 table-bordered table_text report" border="1px">
    <tr>
        <td>Клики: <span><?php echo $out_table[0] ?></span></td>
        <td>Заказы: <span><?php echo $out_table[1] ?></span></td>
        <td>% заказов: <span><?php echo $out_table[2] ?></span></td>
        <td></td>
    </tr>
    <tr>
        <td>Заказы: <span><?php echo $out_table[1] ?></span></td>
        <td>Отправок: <span><?php echo $out_table[3] ?></span></td>
        <td>% отправок: <span><?php echo $out_table[4] ?></span></td>
        <td>Незавершенные заказы: <span><?php echo $out_table[5] ?></span></td>
    </tr>
    <tr>
        <td>Отправок: <span><?php echo $out_table[3] ?></span></td>
        <td>Оплат: <span><?php echo $out_table[7] ?></span></td>
        <td>% оплат: <span><?php echo $out_table[8] ?></span></td>
        <td>Неопределенные заказы: <span><?php echo $out_table[9] ?></span></td>
    </tr>
</table>

</body>
</html>