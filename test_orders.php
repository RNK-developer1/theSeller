<?php
	require("config.php"); 
	
	$query = "SELECT orders.id as id, statuses2.id as status_step2_id, statuses2.name as status_step2_name, statuses3.id as status_step3_id, statuses3.name as status_step3_name, orders.newpost_answer as newpost_answer, orders.newpost_id as newpost_id, orders.newpost_backorder_answer as newpost_backorder_answer, orders.newpost_backorder as newpost_backorder FROM orders, statuses as statuses1, statuses as statuses2, statuses as statuses3 WHERE orders.status_step1 = statuses1.id AND
					orders.status_step2 = statuses2.id AND
					orders.status_step3 = statuses3.id AND NOT newpost_id IS NULL AND newpost_id != '' AND newpost_id != '0' AND (:order_id IS NULL OR orders.id = :order_id) order by status_step2_id"; 
	$query_params = array(':order_id' => $_GET['id']); 
	 
	try{	$stmt = $db->prepare($query); 
			$result = $stmt->execute($query_params); } 
	catch(PDOException $ex){ die("Невозможно выполнить запрос: " . $ex->getMessage()); } 	
	
	echo '<table border=1>';
	while($ord = $stmt->fetch()) {
		$np_answer = json_decode($ord['newpost_answer'],true);
		$npb_answer = json_decode($ord['newpost_backorder_answer'],true);
		echo '<tr><td>'.$ord['id'].'</td><td>'.$ord['status_step2_name'].'</td><td>'.$ord['status_step2_id'].'</td><td>'.$np_answer['status_step2'].'</td><td>'.$np_answer['new_s2'].'</td><td>'.$np_answer['msg'].'</td><td><a target="_new" href="http://novaposhta.ua/frontend/tracking/ru?en='.$ord['newpost_id'].'">'.$ord['newpost_id'].'</a></td><td>'.$ord['status_step3_name'].'</td><td>'.$ord['status_step3_id'].'</td><td>'.$np_answer['status_step3'].'</td><td>'.$np_answer['new_s3'].'</td><td><a target="_new" href="http://novaposhta.ua/frontend/tracking/ru?en='.$ord['newpost_backorder'].'">'.$ord['newpost_backorder'].'</a></td><td>'.$npb_answer['status_step3'].'</td><td>'.$npb_answer['msg'].'</td></tr>';
	}
	echo '</table>';
?>