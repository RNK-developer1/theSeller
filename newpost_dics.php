<?php 
	require 'config.php';

	$i_query = 'TRUNCATE TABLE cities; TRUNCATE TABLE warehouses; ';
	
	$xml = "<?xml version=\"1.0\" encoding=\"utf-8\"?><file><auth>3486c0c0aab8d029e7bc0bbe95002388</auth><city/></file>";

	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, 'http://orders.novaposhta.ua/xml.php');
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_HTTPHEADER, Array("Content-Type: text/xml"));
	curl_setopt($ch, CURLOPT_HEADER, 0);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
	$response = curl_exec($ch);
	$xml_cities = new SimpleXMLElement($response);

	$idx=0;
	while($city = $xml_cities->result->cities->city[$idx]) {
		$i_query .= "INSERT INTO `goodthing_yaw`.`cities` (`ref`, `id`, `nameRu`, `nameUkr`, `saturdayDelivery`, `parentCityRef`, `parentCityId`, `parentCityNameRu`, `parentCityNameUkr`) 
							VALUES ('".$city->ref."', '".$city->id."', ".$db->quote($city->nameRu).", ".$db->quote($city->nameUkr).", '".$city->saturdayDelivery."', '".$city->parentCityRef."', '".$city->parentCityId."', ".$db->quote($city->parentCityNameRu).", ".$db->quote($city->parentCityNameUkr).");";
				
		$idx++;
	}
	
	$xml = "<?xml version=\"1.0\" encoding=\"utf-8\"?><file><auth>3486c0c0aab8d029e7bc0bbe95002388</auth><warenhouse/></file>";	
	curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
	$response = curl_exec($ch);
	curl_close($ch);
	
	$xml_warehouses = new SimpleXMLElement($response);
	$idx=0;
	while($whs = $xml_warehouses->result->whs->warenhouse[$idx]) {
		$i_query .= "INSERT INTO `goodthing_yaw`.`warehouses` (`ref`, `city_ref`, `cityId`, `city`, `cityRu`, `address`, `addressRu`, `number`, `wareId`, `phone`, `weekday_work_hours`, `weekday_reseiving_hours`, `weekday_delivery_hours`, `saturday_work_hours`, `saturday_reseiving_hours`, `saturday_delivery_hours`, `max_weight_allowed`, `x`, `y`)
							VALUES ('".$whs->ref."', '".$whs->city_ref."', '".$whs->cityId."', ".$db->quote($whs->city).", ".$db->quote($whs->cityRu).", ".$db->quote($whs->address).", ".$db->quote($whs->addressRu).", '".$whs->number."', '".$whs->wareId."', '".$whs->phone."', '".$whs->weekday_work_hours."', '".$whs->weekday_reseiving_hours."', '".$whs->weekday_delivery_hours."', '".$whs->saturday_work_hours."', '".$whs->saturday_reseiving_hours."', '".$whs->saturday_delivery_hours."', '".$whs->max_weight_allowed."', '".$whs->x."', '".$whs->y."');";
		
		$idx++;
	}
	
	echo $i_query;
	try {
		$db->query($i_query);
	} 
	catch(PDOException $ex){ die("Невозможно выполнить запрос: " . $ex->getMessage()); }
?>