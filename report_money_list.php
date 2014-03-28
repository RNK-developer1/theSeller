<?php
	require("config.php"); 	

	if (empty($_SESSION['user']) || $_SESSION['user']['active'] == 0 || $_SESSION['user']['group_id'] != 2) {
		header("Location: index.php"); 
		die("Перенаправление: index.php"); 
	}

	$query = " 
			SELECT 
				item.uuid as uuid,
				item.name as name
			FROM users as owner, item
			WHERE
				item.owner_id = owner.id AND
				(:seller_id = '0' OR owner.id = :seller_id)
		";		
	$query_params = array( 
			':seller_id' => $_GET['seller_id'] || (!$_GET['seller_id'] && $_GET['seller_id']=='0') ? $_GET['seller_id'] : $_SESSION['user']['id'],
		);		
			 
	try{ 
		$stmt = $db->prepare($query); 
		$result = $stmt->execute($query_params); 
	} 
	catch(PDOException $ex){ die("Невозможно выполнить запрос01: " . $ex->getMessage()); } 
	
	$select_items = $stmt->fetchAll();
	
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

	$select_sellers = array();
	$query = " 
				SELECT 
					CONCAT(REPEAT(' -',sellers_for_sellers.depth),users.username) as username,
					users.id as id
				FROM sellers_for_sellers, users
				WHERE
					users.id = sellers_for_sellers.subseller_id
					AND sellers_for_sellers.seller_id = 24
			";		
		$query_params = array( 
		); 
			 
	try{ 
		$stmt = $db->prepare($query); 
		$result = $stmt->execute($query_params); 
	} 
	catch(PDOException $ex){ die("Невозможно выполнить запрос03: " . $ex->getMessage()); } 
	
	$select_sellers = array_merge($select_sellers,$stmt->fetchAll());	
	
	$act_type = 'Статистика оплаты заказов';		
?>

<!doctype html>
<html lang="ru">
<?php include 'header.php' ?>
<body>
<?php include 'top_menu.php' ?>

<!--Load the AJAX API-->
<script type="text/javascript" src="https://www.google.com/jsapi"></script>
<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.8.2/jquery.min.js"></script>

<?php 		
		$query = " 
				SELECT money.order_id, users.username, orders.created_at, orders.fio, orders.phone, DATEDIFF(payment_date, sent_date) as pay_days FROM (SELECT order_id, MAX(date) as payment_date FROM `orders_audit` JOIN orders ON orders.id=orders_audit.order_id
				WHERE 
				(:item_id IS NULL OR :item_id = '0' OR orders.item_id = :item_id) AND
				(:seller_id = '0' OR orders.owner_id = :seller_id) AND
				(:order_date IS NULL OR :order_date = '' OR DATE(date) >= :order_date) AND
				(:order_date_end IS NULL OR :order_date_end = '' OR DATE(date) <= :order_date_end) AND
				activity='Клиент отправил деньги' GROUP BY order_id) as money JOIN (SELECT order_id, MIN(date) as sent_date FROM `orders_audit` WHERE activity='Груз прибыл на склад получателя' GROUP BY order_id) as sent ON sent.order_id = money.order_id JOIN orders ON money.order_id = orders.id JOIN users ON users.id = orders.owner_id
				";		
				
		$query_params = array( 
				':item_id' => $_GET['item_id'],
				':seller_id' => $_GET['seller_id'],
				':order_date' => $_GET['order_date'],
				':order_date_end' => $_GET['order_date_end'] ? $_GET['order_date_end'] : $_GET['order_date']
			); 
	
	 
	try{ 
		$stmt = $db->prepare($query); 
		$result = $stmt->execute($query_params); 
	} 
	catch(PDOException $ex){ die("Невозможно выполнить запрос3: " . $ex->getMessage()); } 	
	
?>

<div class="container">	
<a href="reports.php">&larr; Вернуться в меню отчетов</a>
	<h3>День оплаты заказов: <?php echo $_GET['d'] ?></h3>
	
	<?php	$dstart = new DateTime($_GET['order_date']);
			$dend = new DateTime((!$_GET['order_date_end'] or $_GET['order_date_end'] == '') ? $_GET['order_date'] : $_GET['order_date_end']);	
	?>
	
	<?php if ($_GET['order_date']) { ?>
		<p><?php echo 'Период оплат:<br/>'.$dstart->format('d.m.Y').' - '.$dend->format('d.m.Y'); ?></p>
	<?php } ?>
			
	<table cellpadding='2' border='1' style="table-layout: fixed; border-collapse: collapse; border: 1px solid black;">
	<tr><th>Номер</th><th>Предприниматель</th><th>Дата</th><th>ФИО</th><th>Телефон</th></tr>
		<?php while($r = $stmt->fetch() ) {
			if ($r['pay_days'] == $_GET['d']) {
				$odt = new DateTime($r['created_at']);
				$odt = $odt->format('d-m-y H:i:s');
				echo "<tr><td>".$r['order_id']."</td><td>".$r['username']."</td><td>".$odt."</td><td>".$r['fio']."</td><td>".$r['phone']."</td></tr>";
			}
		} ?>
	</table>
	
</div>
</body>
</html>