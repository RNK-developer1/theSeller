<?php 
    require("config.php");     
	
	if (empty($_SESSION['user']) || $_SESSION['user']['active'] == 0) {
		header("Location: index.php"); 
		die("Перенаправление: index.php"); 
	}
	
	$query = "UPDATE users SET sms_catch = :sms_catch, sms_nbt = :sms_nbt WHERE id = :id";
	$query_params = array( 
		':id' => $_SESSION['user']['id'],
		':sms_catch' => $_POST['sms_catch'],
		':sms_nbt' => $_POST['sms_nbt']
	);
	try{ 
		$stmt = $db->prepare($query); 
		$result = $stmt->execute($query_params); 
		$_SESSION['user']['sms_catch'] = $_POST['sms_catch'];
		$_SESSION['user']['sms_nbt'] = $_POST['sms_nbt'];		
	} 
	catch(PDOException $ex){ die("Невозможно выполнить запрос: " . $ex->getMessage()); } 		

    header("Location: items_list.php"); 
    die("Перенаправление: items_list.php");
?>