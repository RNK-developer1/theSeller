<?php
	header("Content-Type: application/javascript");
	echo $_GET['callback'] . '(' . "{'status' : 'ok', 'user_id' : '".$_GET['user_id']."'}" . ')';
	
	$clean_headers=true;
	require("config.php");     
	
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
            INSERT IGNORE INTO preorder(user_id, created_at, referrer, request, ip_src, item_uuid, item_params, count, city_area, address, fio, phone, email)
					   VALUES (:user_id, NOW(), :referrer, :request, :ip_src, :item_uuid, :item_params, :count, :city_area, :address, :fio, :phone, :email) ON DUPLICATE KEY UPDATE updated_at = NOW(), referrer = :referrer, request = :request, ip_src = :ip_src, item_uuid = :item_uuid, item_params = :item_params, count = :count, city_area = :city_area, address = :address, fio = :fio, phone = :phone, email= :email;								   
        "; 
	$query_params = array( 
		':user_id' => $_GET['user_id'],
		':referrer' => $_GET['referrer'],
		':request' => $_GET['request'],
		':ip_src' => $_GET['ip_src'],
		':item_uuid' => $_GET['id'],
		':item_params' => ($_GET['param1'] ? $sel_item['param1_name'].':'.$_GET['param1'] : '').($_GET['param2'] ? ' '.$sel_item['param2_name'].':'.$_GET['param2'] : ''),
		':count' => $_GET['count'],
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

?>