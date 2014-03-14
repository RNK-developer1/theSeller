<?php 
    require("config.php");     
	
	if (empty($_SESSION['user']) || $_SESSION['user']['active'] == 0 || $_SESSION['user']['group_id'] != 2) {
		header("Location: index.php"); 
		die("Перенаправление: index.php"); 
	}
	
	$query = "INSERT INTO operators_for_sellers (operator_id, seller_id) VALUES (:operator_id, :seller_id)";
	$query_params = array( 
		':operator_id' => $_GET['oid'],
		':seller_id' =>  $_SESSION['user']['id']
	);
	try{ 
		$stmt = $db->prepare($query); 
		$result = $stmt->execute($query_params); 
	} 
	catch(PDOException $ex){ die("Невозможно выполнить запрос: " . $ex->getMessage()); } 		

    header("Location: profile.php"); 
    die("Перенаправление: profile.php");
?>