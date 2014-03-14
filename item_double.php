<?php 
    require("config.php");     
	
	if (empty($_SESSION['user']) || $_SESSION['user']['active'] == 0 || $_SESSION['user']['group_id'] != 2) {
		header("Location: index.php"); 
		die("Перенаправление: index.php"); 
	}
	
	$new_id = uniqid();
	
	$query = "SELECT * FROM item WHERE uuid = :uuid";
	$query_params = array( 
		':uuid' => $_GET['id']
	);
	
	try{ 
		$stmt = $db->prepare($query); 
		$result = $stmt->execute($query_params); 
	} 
	catch(PDOException $ex){ die("Невозможно выполнить запрос: " . $ex->getMessage()); } 
	
	$item = $stmt->fetch();
	
	$query = " 
            INSERT INTO item (day_back, owner_id, uuid, name, price, price_min, param1_name, param2_name, param1, param2, url, weight, width, height, length, yandexmetric, yandexgoal, yandexgoal2, mail_template, mail_subject, npmail_subject, npmail_template, finish_screen, finish_screen_fast, conf_block)
					   VALUES (:day_back, :owner_id, :uuid, :name, :price, :price_min, :param1_name, :param2_name, :param1, :param2, :url, :weight, :width, :height, :length, :yandexmetric, :yandexgoal, :yandexgoal2,  :mail_template, :mail_subject, :npmail_subject, :npmail_template, :finish_screen, :finish_screen_fast, :conf_block)
        "; 
	$query_params = array( 
		':owner_id' => $item['owner_id'],
		':name' => $item['name'],
		':price' => $item['price'],
		':price_min' => $item['price_min'],
		':day_back' => $item['day_back'] ? $item['day_back'] : 5,
		':uuid' => $new_id,
		':param1_name' => $item['param1_name'],
		':param2_name' => $item['param2_name'],
		':param1' => $item['param1'],
		':param2' => $item['param2'],
		':url' => $item['url'],
		':weight' => $item['weight'],
		':width' => $item['width'],
		':height' => $item['height'],
		':length' => $item['length'],
		':yandexmetric' => $item['yandexmetric'],
		':yandexgoal' => $item['yandexgoal'],
		':yandexgoal2' => $item['yandexgoal2'],
		':mail_template' => $item['mail_template'],
		':mail_subject' => $item['mail_subject'],
		':npmail_subject' => $item['npmail_subject'],
		':npmail_template' => $item['npmail_template'],
		':finish_screen' => $item['finish_screen'],
		':finish_screen_fast' => $item['finish_screen_fast'],
		':conf_block' => $item['conf_block']
	); 
	 
	try{ 
		$stmt = $db->prepare($query); 
		$result = $stmt->execute($query_params); 
	} 
	catch(PDOException $ex){ die("Невозможно выполнить запрос1: " . $ex->getMessage()); } 
	
	header("Location: items_list.php#".$new_id); 
	die("Перенаправление: items_list.php");
?>