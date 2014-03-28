<?php 
    require("config.php");     
	
	if (empty($_SESSION['user']) || $_SESSION['user']['active'] == 0 || $_SESSION['user']['group_id'] != 2) {
		header("Location: index.php"); 
		die("Перенаправление: index.php"); 
	}
	
	$query = " 
            SELECT 
                uuid,
				owner_id
            FROM item
            WHERE 
                uuid = :uuid AND
				owner_id IN (SELECT subseller_id FROM sellers_for_sellers WHERE sellers_for_sellers.seller_id = :owner_id)
        "; 
	$query_params = array( 
		':owner_id' => $_SESSION['user']['id'],
		':uuid' => $_GET['id']
	); 
	 
	try{ 
		$stmt = $db->prepare($query); 
		$result = $stmt->execute($query_params); 
	} 
	catch(PDOException $ex){ die("Невозможно выполнить запрос: " . $ex->getMessage()); } 
	
	$row = $stmt->fetch(); 
    if($row){
		$query = "UPDATE item SET inactive = :inactive WHERE uuid = :uuid AND owner_id = :owner_id";
		
		$query_params = array( 
			':owner_id' => $row['owner_id'],
			':uuid' => $_GET['id'],
			':inactive' => ($_GET['inactive'] == 'inactive')
		); 
		
		try{ 
			$stmt = $db->prepare($query); 
			$result = $stmt->execute($query_params); 
		} 
		catch(PDOException $ex){ die("Невозможно выполнить запрос: " . $ex->getMessage()); } 		
	}
	
	$loc = '?r='.uniqid();
	if ($_GET['seller_id'] or $_GET['seller_id'] == '0') {$loc .= '&seller_id='.$_GET['seller_id'];} 
	
	header("Location: items_list.php".$loc."#".$_GET['id']); 
	die("Перенаправление: items_list.php");
?>