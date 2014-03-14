<?php
	header("Content-Type: application/javascript");
	echo $_GET['callback'] . '(' . "{'status' : 'ok', 'user_id' : '".$_GET['user_id']."'}" . ')';
	
	$clean_headers=true;
	require("config.php");   

	if ($_GET['user_id'] == '1') {
		$_GET['user_id'] = rand(100000, getrandmax());
	}	
	
	$query = "SELECT * FROM item WHERE uuid = :uuid";
	$query_params = array (
		':uuid' => $_GET['id']
	);
	try{ 
		$stmt = $db->prepare($query); 
		$result = $stmt->execute($query_params); 
	} 
	catch(PDOException $ex){ die("Невозможно выполнить запрос: " . $ex->getMessage()); } 
	
	$sel_item = $stmt->fetch();
		
	$query = " 
            INSERT IGNORE INTO orders(id, created_at, referrer, request, ip_src, item_id, item_params, item_price, item_count, city_area, address, fio, phone, email, owner_id, status_step1)
					   VALUES (:user_id, NOW(), :referrer, :request, :ip_src, :item_uuid, :item_params, :item_price, :count, :city_area, :address, :fio, :phone, :email, :owner_id, 100) ON DUPLICATE KEY UPDATE updated_at = NOW(), referrer = :referrer, request = :request, ip_src = :ip_src, item_id = :item_uuid, item_params = :item_params, item_count = :count, city_area = :city_area, address = :address, fio = :fio, phone = :phone, email= :email, item_price = :item_price;								   
        "; 
	$query_params = array( 
		':user_id' => $_GET['user_id'],
		':owner_id' => $sel_item['owner_id'],
		':referrer' => $_GET['referrer'],
		':request' => $_GET['request'],
		':ip_src' => $_GET['ip_src'],
		':item_uuid' => $_GET['id'],
		':item_price' => number_format(floatval($sel_item['price'])*intval($_POST['count'] ? $_POST['count'] : 1),2,'.',''),
		':item_params' => ($_GET['param1'] ? $sel_item['param1_name'].':'.$_GET['param1'] : '').($_GET['param2'] ? ' '.$sel_item['param2_name'].':'.$_GET['param2'] : ''),
		':count' => $_GET['count'] || 1,
		':city_area' => $_GET['city'].' '.$_GET['area'],
		':address' => $_GET['address'], 
		':fio' => $_GET['fam'].' '.$_GET['name'], 
		':phone' => $_GET['phone'], 
		':email' => $_GET['email']
	); 
	 
	try{ 
		$stmt = $db->prepare($query); 
		$result = $stmt->execute($query_params); 
	} 
	catch(PDOException $ex){ die("Невозможно выполнить запрос: " . $ex->getMessage()); } 	
	
	$query = "SELECT COUNT(*) as pre_com FROM orders_audit WHERE comment='ПРЕДЗАКАЗ' AND order_id = :order_id";
	$query_params = array( 
		':order_id' => $_GET['user_id']
	); 
	try{ 
		$stmt = $db->prepare($query); 
		$result = $stmt->execute($query_params); 
	} 
	catch(PDOException $ex){ die("Невозможно выполнить запрос: " . $ex->getMessage()); } 
	
	$pre_com = $stmt->fetch();
	$pre_com = $pre_com['pre_com'];

	if ($pre_com == 0) {
		$comment = '';
		
		$query = "SELECT orders.*, users.username FROM orders, users WHERE orders.id <> :order_id AND orders.owner_id = users.id AND orders.phone = :phone ORDER BY created_at ASC";
		$query_params = array(
			':order_id' => $_GET['user_id'],
			':phone' => $_GET['phone']
		);
		try{ 
			$stmt = $db->prepare($query); 
			$result = $stmt->execute($query_params); 
		} 
		catch(PDOException $ex){ die("Невозможно выполнить запрос: " . $ex->getMessage()); } 		
		$old_orders = $stmt->fetchAll();
		
		$newd = new DateTime();
		$newd = $newd->format('d-m-y H:i:s');
			
		foreach ($old_orders as $old_order) {
			$crd = new DateTime($old_order['created_at']);
			$crd = $crd->format('d-m-y H:i:s');
			if (in_array($old_order['status_step3'], array(20, 301, 310, 311, 312))) {
				$st = "оплачен";
			} else if (in_array($old_order['status_step3'], array(30, 31, 32, 302, 310, 318, 320, 321)) or in_array($old_order['status_step2'], array(220, 225, 240, 241, 242))) {
				$st = "ВОЗВРАТ";
			} else if (in_array($old_order['status_step1'], array(10, 40)) or in_array($old_order['status_step2'], array(10, 230))) {
				$st = "Отменен";
			} else {
				$st = "обрабатывается";
			}
			$comment .= 'АВТО: '.$crd.' '.$old_order['item'].' '.$old_order['username'].' '.$st."<br/>";
			
			$query = "INSERT INTO orders_audit(date, order_id, comment, user_id, activity, details) VALUES
					(	NOW(),
						:order_id,
						:comment,
						:user_id,
						:activity,
						:details)";
		
			$query_params = array( 
				':details' => '',
				':activity' => 'Клиент оформил ещё один заказ',
				':comment' => 'АВТО: '.$newd.' '.$sel_item['name'].' '.$owner['username'].' '.$st,
				':user_id' => $sel_item['owner_id'],
				':order_id' => $old_order['id']
			); 
			
			try{ 
				$stmt = $db->prepare($query); 
				$result = $stmt->execute($query_params); 
			} 
			catch(PDOException $ex){ die("Невозможно выполнить запрос: " . $ex->getMessage()); } 
		}	
			
		if ($comment != '') {
			$query = "INSERT INTO orders_audit(date, order_id, comment, user_id, activity, details) VALUES
						(	NOW(),
							:order_id,
							:comment,
							:user_id,
							:activity,
							:details)";
			
			$query_params = array( 
				':details' => print_r($_GET,true),
				':activity' => 'Оформлен новый заказ - предыдущие заказы',
				':user_id' => $sel_item['owner_id'],
				':order_id' => $_GET['user_id'],
				':comment' => $comment
			); 
			
			try{ 
				$stmt = $db->prepare($query); 
				$result = $stmt->execute($query_params); 
			} 
			catch(PDOException $ex){ die("Невозможно выполнить запрос: " . $ex->getMessage()); } 
		}
		
		$query = "INSERT INTO orders_audit(date, order_id, comment, user_id, activity, details) VALUES
					(	NOW(),
						:order_id,
						:comment,
						:user_id,
						:activity,
						:details)";
		
		$query_params = array( 
			':details' => print_r($_GET,true),
			':activity' => 'Оформлен новый заказ',
			':user_id' => $sel_item['owner_id'],
			':order_id' => $_GET['user_id'],
			':comment' => 'ПРЕДЗАКАЗ'
		); 
		
		try{ 
			$stmt = $db->prepare($query); 
			$result = $stmt->execute($query_params); 
		} 
		catch(PDOException $ex){ die("Невозможно выполнить запрос: " . $ex->getMessage()); }
	}
?>