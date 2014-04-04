<?php
	$clean_headers=true;
	require("config.php");     
	
	$query = "SELECT idx FROM ab_testing WHERE url = :url"; 
	$query_params = array( 
		':url' => $_GET['url']
	); 
	 
	try{ 
		$stmt = $db->prepare($query); 
		$result = $stmt->execute($query_params); 
	} 
	catch(PDOException $ex){ die("Невозможно выполнить запрос: " . $ex->getMessage()); } 	

	$idx = $stmt->fetch();
	$idx = $idx['idx'];

	$query = "INSERT IGNORE INTO ab_testing(url, idx) VALUES (:url, :idx) ON DUPLICATE KEY UPDATE idx = :idx"; 
	$query_params = array( 
		':url' => $_GET['url'],
		':idx' => (($idx == ($_GET['cnt']-1)) ? 0 : $idx+1)
	); 
	 
	try{ 
		$stmt = $db->prepare($query); 
		$result = $stmt->execute($query_params); 
	} 
	catch(PDOException $ex){ die("Невозможно выполнить запрос: " . $ex->getMessage()); } 	

	header("Content-Type: application/javascript");
	echo $_GET['callback'] . '(' . "{'status' : 'ok', 'idx' : ".$idx."}" . ')';

?>