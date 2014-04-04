<?php
	require("config.php"); 
	mb_internal_encoding("UTF-8");	
	libxml_use_internal_errors(true); 
	$curl = curl_init();
	curl_setopt($curl, CURLOPT_URL, "http://goodthing.hostei.com/sync.php"); 
	curl_setopt($curl, CURLOPT_TIMEOUT, 60); 
	curl_exec($curl);
	
	function send_sms($text, $rcpt, $order_id, $alpha, $type=1) {	
		global $db;
	
		$text = htmlspecialchars($text);
		$description = htmlspecialchars('Уведомление об отправке');
		$start_time = "AUTO";
		$end_time = "AUTO";
		$rate = 120;
		$livetime = 24;
		$source = ($alpha ? trim($alpha) : 'SMS'); // Alfaname
		$recipient = $rcpt;
		$user = '380971067101';
		$password = '1067101';

		$myXML 	 = "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n";
		$myXML 	.= "<request>";
		$myXML 	.= "<operation>SENDSMS</operation>";
		$myXML 	.= '		<message start_time="'.$start_time.'" end_time="'.$end_time.'" livetime="'.$livetime.'" rate="'.$rate.'" desc="'.$description.'" source="'.$source.'">'."\n";
		$myXML 	.= "		<body>".htmlspecialchars($text, ENT_QUOTES, 'UTF-8')."</body>";
		$myXML 	.= "		<recipient>".$recipient."</recipient>";
		$myXML 	.=  "</message>";
		$myXML 	.= "</request>";
echo $myXML;
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_USERPWD , $user.':'.$password);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_URL, 'http://sms-fly.com/api/api.php');
		curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: text/xml", "Accept: text/xml"));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $myXML);
		$response = curl_exec($ch);
		curl_close($ch);
		
		/*$response = '<?xml version="1.0" encoding="utf-8"?><message><state code="ACCEPT" campaignID="3447610" date="2013-11-08 03:55:08">The campaign has been successfully processed and added to the queue for delivery</state><to recipient="380973390911" status="ACCEPTED"></to></message>';*/
		echo $response;

		try {
			$status = new SimpleXMLElement($response);			
			
			$query1 = "INSERT INTO flysms (status,date,campaignID,recipient,order_id,type) VALUES (:status,:date,:campaignID,:recipient,:order_id,:type)";			
					
			echo print_r($status->state->attributes(),true);	
			echo $status->state->attributes()->campaignID;
					
			$query_params1 = array( 
				':campaignID' => $status->state->attributes()->campaignID,
				':recipient' => $rcpt,
				':order_id' => $order_id,
				':type' => $type,
				':status' => $status->state->attributes()->code,
				':date' => $status->state->attributes()->date
			); 
						
			try{ 
				$stmt1 = $db->prepare($query1); 
				$result = $stmt1->execute($query_params1); 
			} 
			catch(PDOException $ex){ die("Невозможно выполнить запрос: " . $ex->getMessage()); } 		

			
		} catch (Exception $e) {
			echo $response;
		}
		
		if ($alpha and $status->state->attributes()->code == 'ERRALFANAME') {
			send_sms($text, $rcpt, $order_id, null);
		}
	}
?>

<?php
	$query = " 
		SELECT 		
			owner.id as owner_id,
			owner.username as username,
			owner.phone as user_phone,
			owner.alphaname as alphaname,
			orders.id as id,
			COALESCE(item.name, orders.item) as item,
			orders.item_id as item_id,
			orders.item_price as item_price,
			orders.item_params as item_params,
			orders.item_count as item_count,
			orders.city_area as city_area,
			orders.address as address,
			orders.courier_adr as courier_adr,
			orders.fio as fio,
			orders.phone as phone,
			orders.email as email,
			orders.newpost_id as newpost_id,
			orders.newpost_answer as newpost_answer,
			orders.status_step2 as status_step2
		   FROM orders LEFT OUTER JOIN item ON orders.item_id = item.uuid, users as owner
			WHERE
				HOUR(NOW()) >= 9 AND
				HOUR(NOW()) <= 16 AND
				orders.status_step1 > 50 AND
				orders.status_step2 IN (207, 223) AND
				orders.status_step3 = 0 AND
				orders.owner_id = owner.id AND
				(:owner IS NULL OR owner.id = :owner)
		";		
	$query_params = array( ':owner' => $_GET['owner'] ); 
	
	try{ 
		$stmt = $db->prepare($query); 
		$result = $stmt->execute($query_params); 
	} 
	catch(PDOException $ex){ die("Невозможно выполнить запрос: " . $ex->getMessage()); } 
	$orders = $stmt->fetchAll();
	
	foreach ($orders as $ord) {
		$query_sms = "SELECT 
			date,
			status
		FROM flysms WHERE type = 2 AND order_id = :order_id AND status = 'DELIVERED'";
		$query_params = array(':order_id' => $ord['id']);

		try{ 
			$stmt = $db->prepare($query_sms); 
			$result = $stmt->execute($query_params); 
		} 
		catch(PDOException $ex){ die("Невозможно выполнить запрос: " . $ex->getMessage()); } 
		
		$delivered = $stmt->fetch(); 			
	
		if (!$delivered) {
			$query_sms = "SELECT 
				date,
				status
			FROM flysms WHERE type=2 AND order_id = :order_id AND HOUR(TIMEDIFF(NOW(), date)) < 24";
			$query_params = array(':order_id' => $ord['id']);

			try{ 
				$stmt = $db->prepare($query_sms); 
				$result = $stmt->execute($query_params); 
			} 
			catch(PDOException $ex){ die("Невозможно выполнить запрос: " . $ex->getMessage()); } 
			
			$this_day_sent = $stmt->fetch();
		
			if (!$this_day_sent) {
				$short_item = explode(' ',$ord['item']);
				$sms_text = 'Успей забрать '.($ord['status_step2']==223 ? 'сегодня ':'').$short_item[0].' Накл.'.$ord['newpost_id']; 
				echo $ord['phone'].'<br>';
				echo $ord['id'].'<br>';
				echo $sms_text.'<br>';
				
				if ($_GET['view']) { 						
					continue; 
				}
						
				send_sms($sms_text,"38".str_replace(array('(',')','-'), "", $ord['phone']),$ord['id'],(($ord['alphaname'] and $ord['alphaname'] != '') ? $ord['alphaname'] : null),2);					
				
				$query = "INSERT INTO orders_audit(date, order_id, user_id, activity, details) VALUES
							(	NOW(),
								:order_id,
								:user_id,
								:activity,
								:details		)";
				
				$query_params = array( 
					':details' => $sms_text,
					':activity' => 'Отслеживание доставки: отправлено SMS',
					':user_id' => 1,
					':order_id' => $ord['id']
				); 
				
				try{ 
					$stmt = $db->prepare($query); 
					$result = $stmt->execute($query_params); 
				} 
				catch(PDOException $ex){ die("Невозможно выполнить запрос: " . $ex->getMessage()); }
			}
		}
	}
?>	