<?php 
    require("config.php");     
	
	if (empty($_SESSION['user']) || $_SESSION['user']['active'] == 0) {
		header("Location: index.php"); 
		die("Перенаправление: index.php"); 
	}
	
	$query = " 
            SELECT 
                orders.id,
				orders.comment,
				orders.oper_id
		".($_SESSION['user']['group_id'] == 2 ? 
		"   FROM orders, users as owner
            WHERE 
				orders.owner_id = owner.id AND
				owner.id IN (SELECT subseller_id FROM sellers_for_sellers WHERE sellers_for_sellers.seller_id = :user_id) AND
				orders.id = :order_id" :
		"   FROM orders, operators_for_sellers
            WHERE 
                orders.owner_id = operators_for_sellers.seller_id AND
				operators_for_sellers.operator_id = :user_id AND
				orders.id = :order_id
        "); 
	$query_params = array( 
		':user_id' => $_SESSION['user']['id'],
		':order_id' => $_POST['id']
	); 
	 
	try{ 
		$stmt = $db->prepare($query); 
		$result = $stmt->execute($query_params); 
	} 
	catch(PDOException $ex){ die("Невозможно выполнить запрос: " . $ex->getMessage()); } 
	
	$row = $stmt->fetch(); 
	$alert_spec = NULL;
	
    if($row){	
		$alert_at = ($_POST['alert_at'] AND $_POST['alert_at'] != '' AND $_POST['status_step1'] != '110') ? $_POST['alert_at'] : NULL;
		
		$alert_spec = NULL;
		if ($_POST['status_step1'] == '101') {
			if (date('H') > 16) {
				$alert_spec = '(SELECT CURRENT_DATE() + INTERVAL 1 DAY + INTERVAL 10 HOUR)';
			} else if (date('H') < 14) {
				$alert_spec = "(SELECT CURRENT_DATE() + INTERVAL 16 HOUR)";
			} else {
				$alert_spec = "(SELECT CURRENT_DATE() + INTERVAL 18 HOUR)";
			}
		} else if ($_POST['status_step1'] == '111') {
			$alert_spec = "(SELECT DATE(NOW() + INTERVAL 1 DAY) + INTERVAL 10 HOUR)";
		}
		
		$query = "UPDATE orders SET inwork_userid = NULL, inwork_time = NULL, alert_at = ".(($alert_at OR !$alert_spec) ? ":alert_at" : $alert_spec).", email=:email, weight=:weight, width=:width, height=:height, length=:length, oper_id=:oper_id, address=:address, phone=:phone, item_price=:item_price, item_params=:item_params, item_count=:item_count, status_step1 = :status_step1, whs_ref = :whs_ref".($_POST['status_step1'] == '111' ? ", status_step2 = 201":"").", fio=:fio, item=:item WHERE id = :order_id";			
		
		if ($row['comment'] != $_POST['comment'] and trim($row['comment']) != '') {
			$comment = str_replace($row['comment'],$row['comment']."\n",$_POST['comment']);
		} else {
			$comment = $_POST['comment'];
		}
		
		$oper_id = $row['oper_id'];
		if ($_SESSION['user']['group_id'] == 1 and !$row['oper_id']) {
			$oper_id = $_SESSION['user']['id'];
		}
		
		$query_params = array( 
			':status_step1' => $_POST['status_step1'],
			':fio' => $_POST['fio'],
			':whs_ref' => $_POST['whs_ref'],
			':item' => $_POST['item'],
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
			':order_id' => $_POST['id'],
			':oper_id' => $oper_id
		); 
		
		if ($alert_at OR !$alert_spec) {
			$query_params = array_merge(array(':alert_at' => $alert_at),$query_params);
		}
		
		try{ 
			$stmt = $db->prepare($query); 
			$result = $stmt->execute($query_params); 
		} 
		catch(PDOException $ex){ die("Невозможно выполнить запрос: " . $ex->getMessage()); } 		

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
		
		$query = "INSERT INTO orders_audit(date, order_id, user_id, comment, activity, details) VALUES
					(	NOW(),
						:order_id,
						:user_id,
						:comment,
						:activity,
						:details		)";
		
		$query_params = array( 
			':details' => print_r($_POST,true),
			':activity' => 'Уточнение заказа: '.$status['name'],
			':comment' => trim($_POST['comment']),
			':user_id' => $_SESSION['user']['id'],
			':order_id' => $_POST['id']
		); 
		
		try{ 
			$stmt = $db->prepare($query); 
			$result = $stmt->execute($query_params); 
		} 
		catch(PDOException $ex){ die("Невозможно выполнить запрос: " . $ex->getMessage()); } 	
	}

	$loc = '?r='.uniqid();
	if ($_POST['seller_id'] or $_POST['seller_id'] == '0') {$loc .= '&seller_id='.$_POST['seller_id'];} 
	if ($_POST['item_id'] or $_POST['item_id'] == '0') {$loc .= ($loc!='' ? '&item_id=' : '?item_id=').$_POST['item_id'];}
	if ($_POST['archive']) {$loc .= ($loc!='' ? '&archive=':'?archive=').$_POST['archive'];}
	if ($_POST['page']) {$loc .= ($loc!='' ? '&page=':'?page=').$_POST['page'];}
	if ($_POST['oper']) {$loc .= ($loc!='' ? '&oper=':'?oper=').$_POST['oper'];}
	if ($_POST['status_id']) {$loc .= ($loc!='' ? '&status_id=':'?status_id=').$_POST['status_id'];}
	if ($_POST['order_date']) {$loc .= ($loc!='' ? '&order_date=':'?order_date=').$_POST['order_date'];}	
	if ($_POST['order_date_end']) {$loc .= ($loc!='' ? '&order_date_end=':'?order_date_end=').$_POST['order_date_end'];}		
	
	header("Location: orders_list.php".$loc."#".$_POST['id']); 
	die("Перенаправление: orders_list.php");
?>