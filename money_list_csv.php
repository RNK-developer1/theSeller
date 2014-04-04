<?php
	$clean_headers = true;
	require("config.php"); 	
	session_start(); 
	
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

	ob_start();
	header('Content-Type: text/plain;charset=UTF-8');
	$stream = fopen('php://output', 'w');
	 
	fputcsv($stream, array('GlobalMoney', '', '', ''));
	$total_price = 0;
	foreach ($orders as $ord) {
		$tdt = new DateTime();
		$tdt = $tdt->format('d.m.Y');
		$np_answer = json_decode($ord['newpost_answer'], true); 
		
		if ($ord['status_step3'] == '312' and $np_answer['transfer_date'] != $tdt) {
			fputcsv($stream, array($ord['newpost_id'], str_replace(array('<br/>','<i>','</i>'),'',($np_answer['msg'] == '' ? ($ord['newpost_id'] ? 'обрабатывается' : '') : $np_answer['msg'])), $ord['item_price'], $ord['owner_username']));
			$total_price += $ord['item_price'];
		}
	}
	fputcsv($stream, array('ВСЕГО', number_format($total_price,2), '', ''));
	
	fputcsv($stream, array('', '', '', ''));
	fputcsv($stream, array('Конверты', '', '', ''));
	
	$total_price = 0;
	foreach ($orders as $ord) {
		$np_answer = json_decode($ord['newpost_answer'], true); 
		$npb_answer = json_decode($ord['newpost_backorder_answer'], true);			
		if (strpos($npb_answer['msg'],"прибыли")) {
			fputcsv($stream, array($ord['newpost_backorder'], str_replace(array('<br/>','<i>','</i>'),'',($npb_answer['msg'] == '' ? ($ord['newpost_backorder'] ? 'обрабатывается' : '') : $npb_answer['msg'])), $ord['item_price'], $ord['owner_username']));
			$total_price += $ord['item_price'];
		}
	}
	fputcsv($stream, array('ВСЕГО', number_format($total_price,2), '', ''));
	 
	header("Content-Type: application/octet-stream");
	header("Content-Disposition: attachment; filename=money.csv");
	echo mb_convert_encoding(ob_get_clean(), 'UTF-8', 'UTF-8');
?>