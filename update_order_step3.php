<?php 
    require("config.php");     
	
	if (empty($_SESSION['user']) || $_SESSION['user']['active'] == 0) {
		header("Location: index.php"); 
		die("Перенаправление: index.php"); 
	}
	
	$query = " 
            SELECT 
                orders.id
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
    if($row){
		$query = "UPDATE orders SET status_step3 = :status_step3 WHERE id = :order_id";
		
		$query_params = array( 
			':status_step3' => $_POST['status_step3'],
			':order_id' => $_POST['id']
		); 
		
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
			':status' => $_POST['status_step3']
		); 
		 
		try{ 
			$stmt = $db->prepare($query); 
			$result = $stmt->execute($query_params); 
		} 
		catch(PDOException $ex){ die("Невозможно выполнить запрос: " . $ex->getMessage()); } 
		
		$status = $stmt->fetch();
		
		$query = "INSERT INTO orders_audit(date, order_id, user_id, activity, details) VALUES
					(	NOW(),
						:order_id,
						:user_id,
						:activity,
						:details		)";
		
		$query_params = array( 
			':details' => print_r($_POST,true),
			':activity' => 'Статус оплаты: '.$status['name'],
			':user_id' => $_SESSION['user']['id'],
			':order_id' => $_POST['id']
		); 
		
		try{ 
			$stmt = $db->prepare($query); 
			$result = $stmt->execute($query_params); 
		} 
		catch(PDOException $ex){ die("Невозможно выполнить запрос: " . $ex->getMessage()); } 	
	}

	$loc = '';
	if ($_POST['seller_id'] or $_POST['seller_id'] == '0') {$loc .= '?seller_id='.$_POST['seller_id'];}	
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