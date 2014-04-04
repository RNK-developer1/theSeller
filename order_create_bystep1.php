<?php 
    require("config.php");     
	
	if (empty($_SESSION['user']) || $_SESSION['user']['active'] == 0) {
		header("Location: index.php"); 
		die("Перенаправление: index.php"); 
	}
	
		$alert_at = ($_POST['alert_at'] AND $_POST['alert_at'] != '' AND $_POST['status_step1'] != '110') ? $_POST['alert_at'] : NULL;
		
		$alert_spec = NULL;
		if ($_POST['status_step1'] == '101') {
			$alert_spec = "(SELECT NOW()+INTERVAL 1 HOUR)";
		} else if ($_POST['status_step1'] == '111') {
			$alert_spec = "(SELECT DATE(NOW() + INTERVAL 1 DAY) + INTERVAL 10 HOUR)";
		}
		
		$query = "INSERT INTO orders (created_at, referrer, request, ip_src, oper_id, owner_id, alert_at, email, weight, width, height, length, address, phone, item_price, item_params, item_count, status_step1, whs_ref".($_POST['status_step1'] == '111' ? ", status_step2":"").",fio,item_id) VALUES (NOW(), :referrer, :request, :ip_src, :oper_id, :owner_id, ".(($alert_at OR !$alert_spec) ? ":alert_at" : $alert_spec).", :email, :weight, :width, :height, :length, :address, :phone, :item_price, :item_params, :item_count, :status_step1, :whs_ref".($_POST['status_step1'] == '111' ? ", 201":"").", :fio, :item)";			
		
		$query_params = array( 
			':status_step1' => $_POST['status_step1'],
			':oper_id' => $_SESSION['user']['group_id'] == 1 ? $_SESSION['user']['id'] : NULL,
			':owner_id' => $_POST['owner_id'],			
			':fio' => $_POST['fio'],
			':whs_ref' => $_POST['whs_ref'],
			':item' => $_POST['item_uuid'],
			':item_count' => $_POST['item_count'],
			':item_params' => $_POST['item_params'],
			':item_price' => $_POST['item_price'],
			':phone' => $_POST['phone'],
			':weight' => $_POST['weight'],
			':width' => $_POST['width'],
			':height' => $_POST['height'],
			':length' => $_POST['length'],
			':address' => $_POST['address'],
			':email' => $_POST['email'],
			':referrer' => $_POST['referrer'],
			':request' => $_POST['request'],
			':ip_src' => $_POST['ip_src']
		); 
		
		if ($alert_at OR !$alert_spec) {
			$query_params = array_merge(array(':alert_at' => $alert_at),$query_params);
		}
		
		try{ 
			$stmt = $db->prepare($query); 
			$result = $stmt->execute($query_params); 
		} 
		catch(PDOException $ex){ die("Невозможно выполнить запрос insert: " . $ex->getMessage()); } 		

		$query = " 
				SELECT *
				FROM statuses
				WHERE 
					statuses.id = :status
			"; 
		$query_params = array( 
			':status' => $_POST['status_step1']
		); 
		 
		try{ 
			$stmt = $db->prepare($query); 
			$result = $stmt->execute($query_params); 
		} 
		catch(PDOException $ex){ die("Невозможно выполнить запрос: " . $ex->getMessage()); } 
		
		$status = $stmt->fetch();
		
		$query = "SELECT * FROM orders WHERE phone = :phone AND item_id = :item ORDER BY id DESC";
		$query_params = array(
			':phone' => $_POST['phone'],
			':item' => $_POST['item_uuid']
		);
		try{ 
			$stmt = $db->prepare($query); 
			$result = $stmt->execute($query_params); 
		} 
		catch(PDOException $ex){ die("Невозможно выполнить запрос: " . $ex->getMessage()); } 		
		$new_order = $stmt->fetch();
		
		$query = "INSERT INTO orders_audit(date, order_id, comment, user_id, activity, details) VALUES
					(	NOW(),
						:order_id,
						:comment,
						:user_id,
						:activity,
						:details		)";
		
		$query_params = array( 
			':details' => print_r($_POST,true),
			':activity' => 'Заказ по предзаказу: '.$status['name'],
			':comment' => $_POST['comment'],
			':user_id' => $_SESSION['user']['id'],
			':order_id' => $new_order['id']
		); 
		
		try{ 
			$stmt = $db->prepare($query); 
			$result = $stmt->execute($query_params); 
		} 
		catch(PDOException $ex){ die("Невозможно выполнить запрос: " . $ex->getMessage()); } 	
		
		$query = "DELETE FROM preorder WHERE user_id = :user_id";
		$query_params = array(':user_id' => $_POST['id']);
	
		try{ 
			$stmt = $db->prepare($query); 
			$result = $stmt->execute($query_params); 
		} 
		catch(PDOException $ex){ die("Невозможно выполнить запрос: " . $ex->getMessage()); }
	
	$loc = '?r='.uniqid();
	if ($_POST['seller_id'] or $_POST['seller_id'] == '0') {$loc .= '&seller_id='.$_POST['seller_id'];} 
	if ($_POST['item_id'] or $_POST['item_id'] == '0') {$loc .= ($loc!='' ? '&item_id=' : '?item_id=').$_POST['item_id'];}
	if ($_POST['order_date']) {$loc .= ($loc!='' ? '&order_date=':'?order_date=').$_POST['order_date'];}	
	if ($_POST['order_date_end']) {$loc .= ($loc!='' ? '&order_date_end=':'?order_date_end=').$_POST['order_date_end'];}		
	
	header("Location: preorders_list.php".$loc."#".$_POST['id']); 
	die("Перенаправление: preorders_list.php");
?>