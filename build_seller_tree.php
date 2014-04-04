<?php
	require("config.php");
	
	$i_query = 'TRUNCATE TABLE sellers_for_sellers; ';

	function get_subsellers($first_id, $id, $depth=0) {
		global $db;
		global $i_query;
		
		$query = "SELECT users.id as id FROM users WHERE parent_id = :id AND group_id = 2";
		$query_params = array( ':id' => $id );
		
		try{ 
			$stmt = $db->prepare($query); 
			$result = $stmt->execute($query_params); 
		} 
		catch(PDOException $ex){ die("Невозможно выполнить запрос: " . $ex->getMessage()); } 
		
		$i_query .= "INSERT INTO sellers_for_sellers (seller_id, subseller_id, depth) VALUES (".$first_id.",".$id.",".$depth."); ";
		
		while ($row = $stmt->fetch()) {
			get_subsellers($first_id, $row['id'], $depth+1);
		}		
	}
	
	$query = "SELECT users.id as id FROM users WHERE group_id = 2";
	try{ 
		$stmt = $db->prepare($query); 
		$result = $stmt->execute(); 
	} 
	catch(PDOException $ex){ die("Невозможно выполнить запрос: " . $ex->getMessage()); } 
		
	while ($row = $stmt->fetch()) {
		get_subsellers($row['id'], $row['id']);
	}
	echo $i_query;
	
	try {
		$db->query($i_query);
	} 
	catch(PDOException $ex){ die("Невозможно выполнить запрос: " . $ex->getMessage()); } 
?>