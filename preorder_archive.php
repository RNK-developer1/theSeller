<?php 
    require("config.php");     
	
	if (empty($_SESSION['user']) || $_SESSION['user']['active'] == 0) {
		header("Location: index.php"); 
		die("Перенаправление: index.php"); 
	}
	
	$query = " 
            SELECT 
                preorder.user_id
		".($_SESSION['user']['group_id'] == 2 ? 
		"   FROM users as owner, preorder LEFT OUTER JOIN item ON item.uuid = preorder.item_uuid
            WHERE 
				owner.id IN (SELECT subseller_id FROM sellers_for_sellers WHERE sellers_for_sellers.seller_id = :user_id) AND
				item.owner_id = owner.id AND preorder.user_id = :order_id" :
		"   FROM operators_for_sellers, preorder LEFT OUTER JOIN item ON item.uuid = preorder.item_uuid
            WHERE 
                item.owner_id = operators_for_sellers.seller_id AND
				operators_for_sellers.operator_id = :user_id AND
				preorder.user_id = :order_id
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
		$query = "UPDATE preorder SET archived = 1 WHERE user_id = :order_id";
		
		$query_params = array( 
			':order_id' => $_GET['id']
		); 
		
		try{ 
			$stmt = $db->prepare($query); 
			$result = $stmt->execute($query_params); 
		} 
		catch(PDOException $ex){ die("Невозможно выполнить запрос: " . $ex->getMessage()); } 		
	}
	
	$query = "INSERT INTO orders_audit(date, order_id, user_id, comment, activity, details) VALUES
					(	NOW(),
						:order_id,
						:user_id,
						:comment,
						:activity,
						:details		)";
		
	$query_params = array( 
		':details' => '',
		':activity' => 'Комментарий к предзаказу',
		':comment' => trim($_GET['comment']),
		':user_id' => $_SESSION['user']['id'],
		':order_id' => $_GET['id']
	); 
	
	try{ 
		$stmt = $db->prepare($query); 
		$result = $stmt->execute($query_params); 
	} 
	catch(PDOException $ex){ die("Невозможно выполнить запрос: " . $ex->getMessage()); }

	$loc = '';
	if ($_GET['seller_id']) {$loc .= '?seller_id='.$_GET['seller_id'];}
	if ($_GET['item_id']) {$loc .= ($loc!='' ? '&item_id=' : '?item_id=').$_GET['item_id'];}
	if ($_GET['order_date']) {$loc .= ($loc!='' ? '&order_date=':'?order_date=').$_GET['order_date'];}	
	if ($_GET['order_date_end']) {$loc .= ($loc!='' ? '&order_date_end=':'?order_date_end=').$_GET['order_date_end'];}	

	header("Location: preorders_list.php".$loc); 
	die("Перенаправление: preorders_list.php");
?>