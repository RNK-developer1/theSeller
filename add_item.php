<?php 
    require("config.php");     
	
	if (empty($_SESSION['user']) || $_SESSION['user']['active'] == 0 || $_SESSION['user']['group_id'] != 2) {
		header("Location: index.php"); 
		die("Перенаправление: index.php"); 
	}
	
	$new_id = uniqid();
	
	$query = "SELECT * FROM item WHERE owner_id = :owner_id";
	$query_params = array( 
		':owner_id' => $_SESSION['user']['id']
	);
	
	try{ 
		$stmt = $db->prepare($query); 
		$result = $stmt->execute($query_params); 
	} 
	catch(PDOException $ex){ die("Невозможно выполнить запрос: " . $ex->getMessage()); } 
	
	$items = $stmt->fetchAll();
	$item = end($items);
	
	$query = " 
            INSERT INTO item (owner_id, uuid, name, price, price_min, param1_name, param2_name, param1, param2, day_back, url, weight, width, height, length, yandexmetric, yandexgoal, yandexgoal2, mail_template, mail_subject, npmail_subject, npmail_template, finish_screen, finish_screen_fast, conf_block)
					   VALUES (:owner_id, :uuid, :name, :price, :price_min, :param1_name, :param2_name, :param1, :param2, :day_back, :url, :weight, :width, :height, :length, :yandexmetric, :yandexgoal, :yandexgoal2,  :mail_template, :mail_subject, :npmail_subject, :npmail_template, :finish_screen, :finish_screen_fast, :conf_block)
        "; 
	$query_params = array( 
		':owner_id' => $_SESSION['user']['id'],
		':name' => $_GET['name'],
		':price' => $_GET['price'],
		':price_min' => $_GET['price_min'],
		':uuid' => $new_id,
		':param1_name' => $_GET['param1_name'],
		':param2_name' => $_GET['param2_name'],
		':param1' => $_GET['param1'],
		':param2' => $_GET['param2'],
		':url' => $_GET['url'],
		':weight' => $_GET['weight'],
		':day_back' => $_GET['day_back'],
		':width' => $_GET['width'],
		':height' => $_GET['height'],
		':length' => $_GET['length'],
		':yandexmetric' => $_GET['yandexmetric'],
		':yandexgoal' => $_GET['yandexgoal'],
		':yandexgoal2' => $_GET['yandexgoal2'],
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
	catch(PDOException $ex){ die("Невозможно выполнить запрос: " . $ex->getMessage()); } 
	
	header("Location: items_list.php#".$new_id); 
	die("Перенаправление: items_list.php");
?>