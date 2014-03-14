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
				orders.comment2,
				orders.status_step1,
				orders.status_step2
		".($_SESSION['user']['group_id'] == 2 ? 
		"   FROM orders, users as owner
            WHERE 
				owner.id IN (SELECT subseller_id FROM sellers_for_sellers WHERE sellers_for_sellers.seller_id = :user_id) AND
				orders.owner_id = owner.id AND orders.id = :order_id" :
		"   FROM orders, operators_for_sellers
            WHERE 
                orders.owner_id = operators_for_sellers.seller_id AND
				operators_for_sellers.operator_id = :user_id AND
				orders.id = :order_id
        "); 
	$query_params = array( 
		':user_id' => $_SESSION['user']['id'],
		':order_id' => $_GET['id']
	); 
	 
	try{ 
		$stmt = $db->prepare($query); 
		$result = $stmt->execute($query_params); 
	} 
	catch(PDOException $ex){ die("Невозможно выполнить запрос: " . $ex->getMessage()); } 
	
	$row = $stmt->fetch(); 
    if($row){
		$query = "UPDATE orders SET status_step3 = :status_step3, alert_at = NULL WHERE id = :order_id";
		
		$query_params = array( 
			':status_step3' => $_GET['status_step3'],
			':order_id' => $_GET['id']
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
			':status' => $_GET['status_step3']
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
			':details' => print_r($_GET,true),
			':activity' => 'Архивация: '.$status['name'],
			':user_id' => $_SESSION['user']['id'],
			':order_id' => $_GET['id']
		); 
		
		try{ 
			$stmt = $db->prepare($query); 
			$result = $stmt->execute($query_params); 
		} 
		catch(PDOException $ex){ die("Невозможно выполнить запрос: " . $ex->getMessage()); } 	
	}

	$loc = '';
	if ($_GET['seller_id']) {$loc .= '?seller_id='.$_GET['seller_id'];}
	if ($_GET['item_id']) {$loc .= ($loc!='' ? '&item_id=' : '?item_id=').$_GET['item_id'];}
	if ($_GET['page']) {$loc .= ($loc!='' ? '&page=' : '?page=').$_GET['page'];}
	if ($_GET['oper']) {$loc .= ($loc!='' ? '&oper=' : '?oper=').$_GET['oper'];}
	if ($_GET['status_id']) {$loc .= ($loc!='' ? '&status_id=':'?status_id=').$_GET['status_id'];}
	if ($_GET['order_date']) {$loc .= ($loc!='' ? '&order_date=':'?order_date=').$_GET['order_date'];}	
	if ($_GET['order_date_end']) {$loc .= ($loc!='' ? '&order_date_end=':'?order_date_end=').$_GET['order_date_end'];}	

	header("Location: orders_list.php".$loc); 
	die("Перенаправление: orders_list.php");
?>