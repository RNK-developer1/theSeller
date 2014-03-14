<?php
	require("config.php"); 
	mb_internal_encoding("UTF-8");	
	libxml_use_internal_errors(true); 
	
	if (empty($_SESSION['user']) || $_SESSION['user']['active'] == 0) {
		header("Location: index.php"); 
		die("Перенаправление: index.php"); 
	}
	
	function send_sms($text, $rcpt, $order_id, $alpha) {	
		global $db;
	
		$text = htmlspecialchars($text);
		$description = htmlspecialchars('Отправка сообщения из формы');
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

		try {
			$status = new SimpleXMLElement($response);			
			
			$query1 = "INSERT INTO flysms (status,date,campaignID,recipient,order_id) VALUES (:status,:date,:campaignID,:recipient,:order_id)";			
									
			$query_params1 = array( 
				':campaignID' => $status->state->attributes()->campaignID,
				':recipient' => $rcpt,
				':order_id' => $order_id,
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
	}

	$query = " 
		SELECT 		
			owner.id as owner_id,
			owner.username as username,
			owner.phone as user_phone,
			owner.alphaname as alphaname,
			orders.id as id,
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
		   FROM orders, users as owner
			WHERE
				orders.owner_id = owner.id AND
				orders.id = :order
		";		
	$query_params = array( ':order' => $_POST['id'] ); 
	
	try{ 
		$stmt = $db->prepare($query); 
		$result = $stmt->execute($query_params); 
	} 
	catch(PDOException $ex){ die("Невозможно выполнить запрос: " . $ex->getMessage()); } 
	$order = $stmt->fetch();
	
	send_sms($_POST['sms_text'],"38".str_replace(array('(',')','-'), "", $order['phone']),$order['id'],(($order['alphaname'] and $order['alphaname'] != '') ? $order['alphaname'] : null));
														
	$query = "INSERT INTO orders_audit(date, order_id, user_id, activity, details) VALUES
				(	NOW(),
					:order_id,
					:user_id,
					:activity,
					:details		)";
	
	$query_params = array( 
		':details' => $sms_text,
		':activity' => 'Отслеживание доставки: отправлено SMS',
		':user_id' => $_SESSION['user']['id'],
		':order_id' => $order['id']
	); 
	
	try{ 
		$stmt = $db->prepare($query); 
		$result = $stmt->execute($query_params); 
	} 
	catch(PDOException $ex){ die("Невозможно выполнить запрос: " . $ex->getMessage()); }
	
	$loc = '?r='.uniqid();
	if ($_POST['seller_id'] or $_POST['seller_id'] == '0') {$loc .= '&seller_id='.$_POST['seller_id'];} 
	if ($_POST['item_id'] or $_POST['item_id'] == '0') {$loc .= ($loc!='' ? '&item_id=' : '?item_id=').$_POST['item_id'];}
	if ($_POST['page']) {$loc .= ($loc!='' ? '&page=':'?page=').$_POST['page'];}
	if ($_POST['archive']) {$loc .= ($loc!='' ? '&archive=':'?archive=').$_POST['archive'];}
	if ($_POST['status_id']) {$loc .= ($loc!='' ? '&status_id=':'?status_id=').$_POST['status_id'];}
	if ($_POST['order_date']) {$loc .= ($loc!='' ? '&order_date=':'?order_date=').$_POST['order_date'];}	
	if ($_POST['order_date_end']) {$loc .= ($loc!='' ? '&order_date_end=':'?order_date_end=').$_POST['order_date_end'];}		
	
	header("Location: orders_list.php".$loc."#".$_POST['id']); 
	die("Перенаправление: orders_list.php");
?>	