<?php
	require("config.php"); 	
	
	if (empty($_SESSION['user']) || $_SESSION['user']['active'] == 0 || $_SESSION['user']['group_id'] != 2) {
		header("Location: index.php"); 
		die("Перенаправление: index.php"); 
	}
	
	$query = " 
				SELECT 
					orders.status_step3 as status_step3,
					orders.item_price as item_price,
					orders.newpost_id as newpost_id,
					orders.newpost_backorder as newpost_backorder,
					orders.newpost_answer as newpost_answer,
					orders.newpost_backorder_answer as newpost_backorder_answer,
					orders.newpost_last_update as newpost_last_update,
					orders.newpost_last_backorder_update as newpost_last_backorder_update,
					owner.username as owner_username					
			   FROM users as owner, orders LEFT OUTER JOIN item ON item.uuid = orders.item_id AND item.owner_id = orders.owner_id
				WHERE
					(orders.status_step1 = 0 OR orders.status_step1 > 50) AND
					(orders.status_step2 = 0 OR orders.status_step2 > 50) AND
					(orders.status_step3 = 0 OR orders.status_step3 > 50) AND
					orders.newpost_id IS NOT NULL AND orders.newpost_id <> '' AND
					(:order_date IS NULL OR :order_date = '' OR DATE(orders.created_at) >= :order_date) AND
					(:order_date_end IS NULL OR :order_date_end = '' OR DATE(orders.created_at) <= :order_date_end) AND
					(:item_id IS NULL OR :item_id = '0' OR orders.item IN (SELECT name FROM item WHERE uuid = :item_id)) AND
					owner.id = orders.owner_id AND
					(:seller_id = '0' OR owner.id = :seller_id) AND
					owner.id IN (SELECT subseller_id FROM sellers_for_sellers WHERE sellers_for_sellers.seller_id = :user_id)					
					ORDER BY newpost_backorder_answer";		
		$query_params =  
			array( 
				':user_id' => $_SESSION['user']['id'],
				':seller_id' => (($_GET['seller_id'] or $_GET['seller_id'] == '0') ? $_GET['seller_id'] : $_SESSION['user']['id']),
				':item_id' => $_GET['item_id'],				
				':order_date' => $_GET['order_date'],
				':order_date_end' => $_GET['order_date_end'] ? $_GET['order_date_end'] : $_GET['order_date']
			); 
	 
	try{ 
		$stmt = $db->prepare($query); 
		$result = $stmt->execute($query_params); 
	} 
	catch(PDOException $ex){ die("Невозможно выполнить запрос: " . $ex->getMessage()); } 
	
	$orders = $stmt->fetchAll();	
?>

<!doctype html>
<html lang="ru">
	<div style="width:200px; padding:10px; background-color: red; border: 1px solid black; border-radius: 5px; ">
		<a target='_new' style="color: white;" href="<?php echo str_replace('money_list','money_list_csv', $_SERVER['REQUEST_URI']); ?>">Экспорт списка в CSV файл</a>
	</div>
	<h3>GlobalMoney</h3>
	<table border=1 cellpadding=4 style='border: 1px solid black; border-collapse: collapse;'>
	<tr><th>Декларация доставки товара</th><th>Статус доставки товара</th><th>Сумма</th><th>Получатель</th></tr>
	<?php 
		$total_price = 0;
		foreach ($orders as $ord) {
			$np_answer = json_decode($ord['newpost_answer'], true); 
			$tdt = new DateTime();
			$tdt = $tdt->format('d.m.Y');
			
			if ($ord['status_step3'] == '312' and $np_answer['transfer_date'] != $tdt) {
			?>
				<tr>
					<td><a target="_new" href="http://novaposhta.ua/frontend/tracking/ru?en=<?php echo $ord['newpost_id'] ?>"><?php echo $ord['newpost_id'] ?></a></td>
					<td <?php echo $ord['newpost_last_update'] ? 'title="обновлено:'.$ord['newpost_last_update'].'"': ''?>><?php echo ($np_answer['msg'] == '' ? ($ord['newpost_id'] ? '<i>обрабатывается</i>' : '') : $np_answer['msg']) ?>
					</td>					
					<td><?php echo $ord['item_price']; $total_price += $ord['item_price']; ?></td>
					<td><?php echo $ord['owner_username']; ?></td>					
				</tr>
			<?php
			}			
		}
	?>
		<tr>
			<td><b>ВСЕГО</b></td>
			<td></td>					
			<td><?php echo number_format($total_price,2); ?></td>
			<td></td>					
		</tr>
	</table>
	<h3>Конверт</h3>
	<table border=1 cellpadding=4 style='border: 1px solid black; border-collapse: collapse;'>
	<tr><th>Декларация получение</th><th>Статус</th><th>Сумма</th><th>Декларация доставки товара</th><th>Статус доставки товара</th><th>Получатель</th></tr>
	<?php 
		$total_price = 0;
		foreach ($orders as $ord) {
			$np_answer = json_decode($ord['newpost_answer'], true); 
			$npb_answer = json_decode($ord['newpost_backorder_answer'], true);			
			if (strpos($npb_answer['msg'],"прибыли")) {
			?>
				<tr>
					<td><a target="_new" href="http://novaposhta.ua/frontend/tracking/ru?en=<?php echo $ord['newpost_backorder'] ?>"><?php echo $ord['newpost_backorder'] ?></a></td>
					<td <?php echo $ord['newpost_last_backorder_update'] ? 'title="обновлено:'.$ord['newpost_last_backorder_update'].'"': ''?>><?php echo ($npb_answer['msg'] == '' ? ($ord['newpost_backorder'] ? '<i>обрабатывается</i>' : '') : $npb_answer['msg']) ?></td>
					<td><?php echo $ord['item_price']; $total_price += $ord['item_price']; ?></td>
					<td><a target="_new" href="http://novaposhta.ua/frontend/tracking/ru?en=<?php echo $ord['newpost_id'] ?>"><?php echo $ord['newpost_id'] ?></a></td>
					<td <?php echo $ord['newpost_last_update'] ? 'title="обновлено:'.$ord['newpost_last_update'].'"': ''?>><?php echo ($np_answer['msg'] == '' ? ($ord['newpost_id'] ? '<i>обрабатывается</i>' : '') : $np_answer['msg']) ?>
					</td>					
					<td><?php echo $ord['owner_username']; ?></td>					
				</tr>
			<?php
			}			
		}		
	?>
		<tr>
			<td><b>ВСЕГО</b></td>
			<td></td>					
			<td><?php echo number_format($total_price,2); ?></td>
			<td></td>					
			<td></td>		
			<td></td>		
		</tr>
	</table>			
</body>
</html>