<?php
	$query = " 
            UPDATE orders SET inwork_userid = NULL, inwork_time = NULL WHERE orders.id = :order_id
        "; 
	$query_params = array( 
		':order_id' => $_GET['id']
	); 
	 
	try{ 
		$stmt = $db->prepare($query); 
		$result = $stmt->execute($query_params); 
	} 
	catch(PDOException $ex){ die("Невозможно выполнить запрос: " . $ex->getMessage()); } 
?>