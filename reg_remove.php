<?php 
    require("config.php");     
	
	if (empty($_SESSION['user']) || $_SESSION['user']['active'] == 0 || $_SESSION['user']['group_id'] != 2) {
		header("Location: index.php"); 
		die("Перенаправление: index.php"); 
	}
	
	$query = " 
            SELECT 
                u1.id
            FROM users as u1, users as p1
            WHERE 
                u1.parent_id = p1.id AND
				p1.email = :parent_email AND
				u1.id = :id
        "; 
	$query_params = array( 
		':parent_email' => $_SESSION['user']['email'],
		':id' => $_GET['id']
	); 
	 
	try{ 
		$stmt = $db->prepare($query); 
		$result = $stmt->execute($query_params); 
	} 
	catch(PDOException $ex){ die("Невозможно выполнить запрос: " . $ex->getMessage()); } 
	
	$row = $stmt->fetch(); 
    if($row){
		$query = "UPDATE users SET active=0 WHERE id = :id";
		$query_params = array( 
			':id' => $_GET['id']
		);
		try{ 
			$stmt = $db->prepare($query); 
			$result = $stmt->execute($query_params); 
		} 
		catch(PDOException $ex){ die("Невозможно выполнить запрос: " . $ex->getMessage()); } 		
	}
	
	$i_query = 'TRUNCATE TABLE sellers_for_sellers; ';

	function get_subsellers($first_id, $id, $depth=0) {
		global $db;
		global $i_query;
		
		$query = "SELECT users.id as id FROM users WHERE active = 1 AND parent_id = :id AND group_id = 2";
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
	
	$query = "SELECT users.id as id FROM users WHERE active = 1 AND group_id = 2";
	try{ 
		$stmt = $db->prepare($query); 
		$result = $stmt->execute(); 
	} 
	catch(PDOException $ex){ die("Невозможно выполнить запрос: " . $ex->getMessage()); } 
		
	while ($row = $stmt->fetch()) {
		get_subsellers($row['id'], $row['id']);
	}
	
	try {
		$db->query($i_query);
	} 
	catch(PDOException $ex){ die("Невозможно выполнить запрос: " . $ex->getMessage()); }

	if (!$_GET['back']) {
		header("Location: reg_requests.php"); 
		die("Перенаправление: reg_requests.php");
	} else {
		header("Location: reg_list.php"); 
		die("Перенаправление: reg_list.php");
	}
?>