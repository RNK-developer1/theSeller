<?php
	require("config.php"); 	
	
	if (empty($_SESSION['user']) || $_SESSION['user']['active'] == 0) {
		header("Location: index.php"); 
		die("Перенаправление: index.php"); 
	}

	$query = " SELECT 
					orders.id as id,
					orders.newpost_id as newpost_id"					
			.($_SESSION['user']['group_id'] == 2 ? 
			"   FROM users as owner, orders LEFT OUTER JOIN item ON item.uuid = orders.item_id AND item.owner_id = orders.owner_id LEFT OUTER JOIN users as oper ON oper_id = oper.id
				WHERE (orders.status_step1 = 0 OR orders.status_step1 > 50) AND
						 (orders.status_step3 = 0 OR orders.status_step3 > 50) AND					
					orders.status_step2 = 250 AND
					(:order_date IS NULL OR :order_date = '' OR DATE(orders.created_at) >= :order_date) AND
					(:order_date_end IS NULL OR :order_date_end = '' OR DATE(orders.created_at) <= :order_date_end) AND
					(:item_id IS NULL OR :item_id = '0' OR orders.item IN (SELECT name FROM item WHERE uuid = :item_id)) AND
					owner.id = orders.owner_id AND
					(:seller_id = '0' OR owner.id = :seller_id) AND
					owner.id IN (SELECT subseller_id FROM sellers_for_sellers WHERE sellers_for_sellers.seller_id = :user_id)					
					" :
			"   FROM users as owner, operators_for_sellers, orders LEFT OUTER JOIN item ON item.uuid = orders.item_id AND item.owner_id = orders.owner_id LEFT OUTER JOIN users as oper ON oper_id = oper.id LEFT OUTER JOIN users as editor ON editor.id = orders.inwork_userid
				WHERE (orders.status_step1 = 0 OR orders.status_step1 > 50) AND
						 (orders.status_step3 = 0 OR orders.status_step3 > 50) AND
					orders.status_step2 = 250 AND
					(:order_date IS NULL OR :order_date = '' OR DATE(orders.created_at) >= :order_date) AND
					(:order_date_end IS NULL OR :order_date_end = '' OR DATE(orders.created_at) <= :order_date_end) AND
					owner.id = orders.owner_id AND				
					orders.owner_id = operators_for_sellers.seller_id AND
					operators_for_sellers.operator_id = :user_id AND
					(:seller_id IS NULL OR :seller_id = '0' OR owner.id = :seller_id) AND
					(:item_id IS NULL OR :item_id = '0' OR orders.item IN (SELECT name FROM item WHERE uuid = :item_id))".(
					($_GET['oper'] and $_GET['oper']=='2') ? " AND oper_id IS NULL" : (($_GET['oper'] and $_GET['oper']=='1') ? " AND oper_id = :user_id" : ""))					
			);
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
	catch(PDOException $ex){ die("Невозможно выполнить запрос 0: " . $ex->getMessage()); } 
	$orders = $stmt->fetchAll();
	
	foreach ($orders as $ord) {
		//echo $ord['id'];
		
		$query = "INSERT INTO orders_audit(date, order_id, user_id, activity, details) VALUES
								(	NOW(),
									:order_id,
									:user_id,
									:activity,
									:details		)";
					
		$query_params = array( 
			':details' => $ord['newpost_id'],
			':activity' => 'Отменена декларация Новой Почты',
			':user_id' => $_SESSION['user']['id'],
			':order_id' => $ord['id']
		); 
		
		try{ 
			$stmt = $db->prepare($query); 
			$result = $stmt->execute($query_params); 
		} 
		catch(PDOException $ex){ die("Невозможно выполнить запрос 1: " . $ex->getMessage()); } 					
		
		$query = "UPDATE orders SET status_step2 = 0, newpost_id = '', newpost_answer = NULL, newpost_last_update = NULL WHERE id = :order_id";
		
		$query_params = array( 
			':order_id' => $ord['id']
		); 
		
		try{ 
			$stmt = $db->prepare($query); 
			$result = $stmt->execute($query_params); 
		} 
		catch(PDOException $ex){ die("Невозможно выполнить запрос 2: " . $ex->getMessage()); } 				
	}
	
	$loc = '?r='.uniqid();
	if ($_GET['seller_id'] or $_GET['seller_id'] == '0') {$loc .= '&seller_id='.$_GET['seller_id'];} 
	if ($_GET['item_id'] or $_GET['item_id'] == '0') {$loc .= ($loc!='' ? '&item_id=' : '?item_id=').$_GET['item_id'];}
	if ($_GET['oper']) {$loc .= ($loc!='' ? '&oper=':'?oper=').$_GET['oper'];}
	if ($_GET['order_date']) {$loc .= ($loc!='' ? '&order_date=':'?order_date=').$_GET['order_date'];}	
	if ($_GET['order_date_end']) {$loc .= ($loc!='' ? '&order_date_end=':'?order_date_end=').$_GET['order_date_end'];}		
	
	header("Location: orders_list.php".$loc); 
	die("Перенаправление: orders_list.php"); 	
?>