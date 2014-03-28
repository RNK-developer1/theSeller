<?php 
    require("config.php");     
	
	if (empty($_SESSION['user']) || $_SESSION['user']['active'] == 0) {
		header("Location: index.php"); 
		die("Перенаправление: index.php"); 
	}
	
	$query = " 
				SELECT 
					*
				FROM
					users
				WHERE 
					id = :id
			"; 
	$query_params = array(
		':id' => $_GET['id']
	);
	try{ 
		$stmt = $db->prepare($query); 
		$result = $stmt->execute($query_params); 
	} 
	catch(PDOException $ex){ die("Невозможно выполнить запрос: " . $ex->getMessage()); } 
	
	$sel_user = $stmt->fetch();

	$ch = curl_init();
	
	curl_setopt($ch, CURLOPT_URL, 'http://orders.novaposhta.ua/account/login/');
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_HTTPHEADER, Array("Content-Type: application/x-www-form-urlencoded"));
	curl_setopt($ch, CURLOPT_POSTFIELDS, "a=".$sel_user['newpost_id']."&n=".$sel_user['newpost_psw']);
	curl_setopt($ch, CURLOPT_POST, 1);
	//curl_setopt($ch, CURLOPT_COOKIESESSION, TRUE);
	curl_setopt($ch, CURLOPT_COOKIEJAR, dirname(__FILE__).'cookie_seller.txt');
	$response = curl_exec($ch);
	
	curl_setopt($ch, CURLOPT_URL, "http://print.novaposhta.ua/index.php?r=site/ttn&id=".$_GET['newpost_id']."&num_copy=2");
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);				
	curl_setopt($ch, CURLOPT_COOKIEJAR, dirname(__FILE__).'cookie_seller.txt');
	curl_exec($ch); 
	$response = curl_exec($ch);
	
	$output = str_replace(array("\r\n", "\r"), "\n", $response);
	$lines = explode("\n", $output);
	$new_lines = array();

	foreach ($lines as $i => $line) {
		if(!empty($line))
			$new_lines[] = str_replace('image/','http://print.novaposhta.ua/image/',str_replace('/index.php','http://print.novaposhta.ua/index.php',str_replace('/css/','http://print.novaposhta.ua/css/',str_replace('/js/','http://print.novaposhta.ua/js/',trim($line)))));
	}
	$html = implode($new_lines);

	echo $html;
?>