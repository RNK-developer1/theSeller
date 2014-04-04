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
		$query = "UPDATE item SET name = :name, short_name = :short_name, day_back = :day_back, price = :price, price_min = :price_min, param1_name = :param1_name, param2_name = :param2_name, param1 = :param1, param2 = :param2, url = :url, weight = :weight, width = :width, height = :height, length = :length, yandexmetric = :yandexmetric, yandexgoal = :yandexgoal, yandexgoal2 = :yandexgoal2 WHERE uuid = :uuid AND owner_id = :owner_id";
		
		$query_params = array( 
			':owner_id' => $row['owner_id'],
			':uuid' => $_GET['id'],
			':day_back' => $_GET['day_back'],
			':name' => $_GET['name'],
			':short_name' => $_GET['short_name'],
			':price' => $_GET['price'],
			':price_min' => $_GET['price_min'],
			':param1_name' => $_GET['param1_name'],
			':param2_name' => $_GET['param2_name'],
			':param1' => $_GET['param1'],
			':param2' => $_GET['param2'],
			':url' => $_GET['url'],
			':weight' => $_GET['weight'],
			':width' => $_GET['width'],
			':height' => $_GET['height'],
			':length' => $_GET['length'],
			':yandexmetric' => $_GET['yandexmetric'],
			':yandexgoal' => $_GET['yandexgoal'],
			':yandexgoal2' => $_GET['yandexgoal2']
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