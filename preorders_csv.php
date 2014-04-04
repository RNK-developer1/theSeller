<?php //select referrer, MAX(created_at), count(*) from preorder where phone IS NULL or phone='' AND archived=0 GROUP BY referrer ORDER BY referrer ASC 
	$clean_headers = true;
	require("config.php"); 	
	session_start(); 
		
	if (empty($_SESSION['user']) || $_SESSION['user']['active'] == 0) {
		header("Location: index.php"); 
		die("Перенаправление: index.php"); 
	}
	
	$query = " 
		SELECT 
			MAX(owner.username) as username,
			MAX(item.name) as item_name,
			preorder.referrer as referrer,
			preorder.request as request,
			preorder.ip_src as ip_src,
			MAX(preorder.created_at) as created_at,
			count(*) as visits
		"					
	.($_SESSION['user']['group_id'] == 2 ? 
	"   FROM users as owner, preorder LEFT OUTER JOIN item ON item.uuid = preorder.item_uuid
		WHERE preorder.phone='' AND 
			(:order_date IS NULL OR :order_date = '' OR DATE(preorder.created_at) >= :order_date) AND
			(:order_date_end IS NULL OR :order_date_end = '' OR DATE(preorder.created_at) <= :order_date_end) AND
			(:item_id IS NULL OR :item_id = '0' OR preorder.item_uuid = :item_id) AND
			owner.id = item.owner_id AND
			(:seller_id = '0' OR owner.id = :seller_id) AND
			owner.id IN (SELECT subseller_id FROM sellers_for_sellers WHERE sellers_for_sellers.seller_id = :user_id)					
			" :
	"   FROM users as owner, operators_for_sellers, preorder LEFT OUTER JOIN item ON item.uuid = preorder.item_uuid 
		WHERE preorder.phone='' AND
			(:order_date IS NULL OR :order_date = '' OR DATE(preorder.created_at) >= :order_date) AND
			(:order_date_end IS NULL OR :order_date_end = '' OR DATE(preorder.created_at) <= :order_date_end) AND
			owner.id = item.owner_id AND				
			owner.id = operators_for_sellers.seller_id AND
			operators_for_sellers.operator_id = :user_id AND
			(:seller_id IS NULL OR :seller_id = '0' OR owner.id = :seller_id) AND
			(:item_id IS NULL OR :item_id = '0' OR preorder.item_uuid = :item_id)
	")." GROUP BY referrer ORDER BY created_at ASC";

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
	 
	foreach ($orders as $ord) {
		fputcsv($stream, array($ord['username'], $ord['item_name'], $ord['referrer'], $ord['request'], $ord['ip_src'], $ord['created_at'], $ord['visits']));
	}
	 
	header("Content-Type: application/octet-stream");
	header("Content-Disposition: attachment; filename=visits.csv");
	echo mb_convert_encoding(ob_get_clean(), 'UTF-8', 'UTF-8');

?>