<?php
	require("config.php"); 
	
	$query = " 
            INSERT INTO ctr_log (time, id, shows, clicks, ctr, price, spent, dspent, dclicks, dshows)
			VALUES				(NOW(), :id, :shows, :clicks, :ctr, :price, :spent, :dspent, :dclicks, :dshows)
        "; 
	$query_params = array( 
		':id' => $_GET['id'],
		':shows' => $_GET['shows'],
		':clicks' => $_GET['clicks'],
		':ctr' => $_GET['ctr'],
		':price' => $_GET['price'],
		':spent' => $_GET['spent'],
		':dspent' => $_GET['dspent'],
		':dclicks' => $_GET['dclicks'],
		':dshows' => $_GET['dshows'],
	); 
	 
	try{ 
		$stmt = $db->prepare($query); 
		$result = $stmt->execute($query_params); 
	} 
	catch(PDOException $ex){ die("Невозможно выполнить запрос: " . $ex->getMessage()); } 	
	
	echo print_r($_GET,true);
?>