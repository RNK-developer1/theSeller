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
<head>
    <meta charset="utf-8">
    <title>theSeller</title>
    <meta name="description" content="theSeller">
    <meta name="author" content="EugeneL.">

    <script src="http://ajax.googleapis.com/ajax/libs/jquery/2.0.0/jquery.min.js"></script>
</head>
<?php
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
				-- HOUR(NOW()) >= 9 AND
				-- HOUR(NOW()) <= 18 AND
				orders.status_step1 > 50 AND
				orders.status_step2 IN (202, 208, 200, 204, 205, 206, 250) AND
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

	echo '<h2>Список SMS для отправки</h2>';
	echo '<table border=1 cellpadding=4 style="border: 1px solid black; border-collapse: collapse;">';
	echo '<tr><th>ФИО клиента</td><td>Телефон</td><td>Осталось символов</td><td>Текст СМС</td><td></td></tr>';
	foreach ($orders as $ord) {
		$np_answer = json_decode($ord['newpost_answer'], true);
		if ($np_answer['arrival_date']) {
			$query_sms = "SELECT
				date,
				status
			FROM flysms WHERE type = 1 AND order_id = :order_id AND status = 'DELIVERED'";
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
				FROM flysms WHERE order_id = :order_id AND HOUR(TIMEDIFF(NOW(), date)) < 24";
				$query_params = array(':order_id' => $ord['id']);

				try{
					$stmt = $db->prepare($query_sms);
					$result = $stmt->execute($query_params);
				}
				catch(PDOException $ex){ die("Невозможно выполнить запрос: " . $ex->getMessage()); }

				$this_day_sent = $stmt->fetch();

				if (!$this_day_sent and !in_array($ord['status_step2'], array(208,209,210,220,221,225,230,240,241,242))) {
					$rexp_sd = '/(\d\d\.\d\d)\.\d\d\d\d/';
					preg_match($rexp_sd, $np_answer['arrival_date'], $short_date);
					$rexp_adr = '/.*?:([^\(]*)/';
					preg_match($rexp_adr, $np_answer['address'], $short_adr);
					$short_adr = trim($short_adr[1]);
					$sms_text = 'Накладная '.$ord['newpost_id'].' прибытие '.$short_date[1].' на '.$short_adr;
					if (70-strlen(utf8_decode($sms_text)) < 0) {
						$sms_text = 'Накладная '.$ord['newpost_id'].' приб.'.$short_date[1].' на '.$short_adr;
						if (70-strlen(utf8_decode($sms_text)) < 0) {
							$sms_text = 'Накладная '.$ord['newpost_id'].' приб.'.$short_date[1].' на '.str_replace(' ','',$short_adr);
							if (70-strlen(utf8_decode($sms_text)) < 0) {
								$sms_text = 'Накладная '.$ord['newpost_id'].' приб.'.$short_date[1].':'.str_replace(' ','',$short_adr);
								if (70-strlen(utf8_decode($sms_text)) < 0) {
									$sms_text = 'Накладная '.$ord['newpost_id'].' приб.'.$short_date[1].':'.mb_substr(str_replace("ТЦ\"Барабашова\",Пл.","Барабаш",str_replace("ТЦ«Барабашова»,","Барабашова",str_replace("площадка,место","",str_replace("пгт.","",str_replace("вская","",str_replace("киоск","",str_replace("микрорайон","мкрн.",str_replace("стрелковой","стр.",str_replace(' ','',$short_adr))))))))), 0, 34);
								}
							}
						}
					}

					$length = 70-strlen(utf8_decode($sms_text));
					echo '<tr><td>'.$ord['fio'].'</td><td>'.$ord['phone'].'</td><td class="sms_len" id="sms_len'.$ord['id'].'" align=center style="'.($length < 0 ? 'background-color: red' : '').'">'.$length.'</td><td>';

					?>
					<?php if ($_GET['seller_id'] or $_GET['seller_id'] == '0') { ?>
						<input type='hidden' name='seller_id' value='<?php echo $_GET['seller_id']?>'>
					<?php }
						if ($_GET['item_id'] or $_GET['item_id'] == '0') { ?>
						<input type='hidden' name='item_id' value='<?php echo $_GET['item_id']?>'>
					<?php } ?>
					<?php if ($_GET['status_id']) { ?>
						<input type='hidden' name='status_id' value='<?php echo $_GET['status_id']?>'>
					<?php } ?>
					<?php if ($_GET['order_date']) { ?>
						<input type='hidden' name='order_date' value='<?php echo $_GET['order_date']?>'>
					<?php } ?>
					<?php if ($_GET['order_date_end']) { ?>
						<input type='hidden' name='order_date_end' value='<?php echo $_GET['order_date_end']?>'>
					<?php } ?>
					<input name="sms[<?php echo $ord['id'];?>][txt]" onkeyup="$('#sms_len<?php echo $ord['id']; ?>').html(70-$(this).val().length).css('background-color',((70-$(this).val().length < 0) ? 'red':'white'));" style='width:500px' type='text' value='<?php echo $sms_text; ?>'>
					<?php echo '</td><td>'.$ord['alphaname'].'</td></tr>';

					if ($_GET['set']) {
							$sd = new DateTime();
							$sd = $sd->format('Y-m-d H:i:s');

							$query1 = "INSERT INTO flysms (status,date,campaignID,recipient,order_id) VALUES (:status,:date,:campaignID,:recipient,:order_id)";

							$query_params1 = array(
								':campaignID' => 1,
								':recipient' => "38".str_replace(array('(',')','-'), "", $ord['phone']),
								':order_id' => $ord['id'],
								':status' => 'DELIVERED',
								':date' => $sd
							);

							try{
								$stmt1 = $db->prepare($query1);
								$result = $stmt1->execute($query_params1);
							}
							catch(PDOException $ex){ die("Невозможно выполнить запрос: " . $ex->getMessage()); }
					}

					if ($_GET['view']) {
						continue;
					}

					send_sms($sms_text,"38".str_replace(array('(',')','-'), "", $ord['phone']),$ord['id'],(($ord['alphaname'] and $ord['alphaname'] != '') ? $ord['alphaname'] : null));

						$query = "SELECT * from item where uuid = :uuid";

						$query_params = array(
							':uuid' => $ord['item_id']
						);

						try{
							$stmt = $db->prepare($query);
							$result = $stmt->execute($query_params);
						}
						catch(PDOException $ex){ die("Невозможно выполнить запрос: " . $ex->getMessage()); }

						$sel_item = $stmt->fetch();

						if ($sel_item) {
							$mail_subject = str_replace("{surname}",'',str_replace("{name}",$ord['fio'],str_replace("{item}",$sel_item['name'],$sel_item['npmail_subject'])));
							$mail_template = str_replace("{surname}",'',str_replace("{declaration}",$ord['newpost_id'],str_replace("{name}",$ord['fio'],str_replace("{item}",$sel_item['name'],$sel_item['npmail_template']))));

							if ($sel_item['mail_subject'] AND $sel_item['mail_template']) {
								$mail_headers = "From: Интернет-магазин <no-reply@goodthing.in.ua>\r\nReply-To: ".$owner['email']."\r\nMIME-Version: 1.0\r\nContent-Type: text/html; charset=utf-8\r\n";
								mail($ord['email'], $mail_subject, $mail_template, $mail_headers);
							}
						}

					if (in_array($ord['status_step2'], array(200,250))) {
						$query = "UPDATE orders SET status_step2 = :status_step2 WHERE id = :order_id";

						$query_params = array(
							':status_step2' => 202,
							':order_id' => $ord['id']
						);

						try{
							$stmt = $db->prepare($query);
							$result = $stmt->execute($query_params);
						}
						catch(PDOException $ex){ die("Невозможно выполнить запрос: " . $ex->getMessage()); }

						$query = "
								SELECT *
								FROM statuses
								WHERE
									statuses.id = :status
							";
						$query_params = array(
							':status' => 202
						);

						try{
							$stmt = $db->prepare($query);
							$result = $stmt->execute($query_params);
						}
						catch(PDOException $ex){ die("Невозможно выполнить запрос: " . $ex->getMessage()); }

						$status = $stmt->fetch();

						$query = "INSERT INTO orders_audit(date, order_id, user_id, activity, details) VALUES
									(	NOW(),
										:order_id,
										:user_id,
										:activity,
										:details		)";

						$query_params = array(
							':details' => $sms_text,
							':activity' => 'Отслеживание доставки: '.$status['name'],
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
		}
	}
	?>
	</table>
	<table border=1>
<?php/*
	$query = "SELECT * FROM	warehouses ORDER BY number";

	try{
		$stmt = $db->prepare($query);
		$result = $stmt->execute();
	}
	catch(PDOException $ex){ die("Невозможно выполнить запрос: " . $ex->getMessage()); }

	$warehouses = $stmt->fetchAll();

	foreach ($warehouses as $whs) {
		$rexp_adr = '/.*?:([^\(]*)/';
		preg_match($rexp_adr, $whs['addressRu'], $short_adr);
		$short_adr = trim($short_adr[1]);
		$sms_text = 'Накладная '.$ord['newpost_id'].' прибытие '.$short_date[1].' на '.$short_adr;
		if (70-strlen(utf8_decode($sms_text)) < 0) {
			$sms_text = 'Накладная '.$ord['newpost_id'].' приб.'.$short_date[1].' на '.$short_adr;
			if (70-strlen(utf8_decode($sms_text)) < 0) {
				$sms_text = 'Накладная '.$ord['newpost_id'].' приб.'.$short_date[1].' на '.str_replace(' ','',$short_adr);
				if (70-strlen(utf8_decode($sms_text)) < 0) {
					$sms_text = 'Накладная '.$ord['newpost_id'].' приб.'.$short_date[1].':'.str_replace(' ','',$short_adr);
					if (70-strlen(utf8_decode($sms_text)) < 0) {
						echo '<tr><td>'.$sms_text.'</td>';
						$sms_text = 'Накладная '.$ord['newpost_id'].' приб.'.$short_date[1].':'.mb_substr(str_replace("ТЦ\"Барабашова\",Пл.","Барабаш",str_replace("ТЦ«Барабашова»,","Барабашова",str_replace("площадка,место","",str_replace("пгт.","",str_replace("вская","",str_replace("киоск","",str_replace("микрорайон","мкрн.",str_replace("стрелковой","стр.",str_replace(' ','',$short_adr))))))))), 0, 34);
						echo '<td>'.$sms_text.'</td></tr>';
					}
				}
			}
		}
	}
*/?>
	</table>
<?php
	$query = "
		SELECT
			owner.id as owner_id,
			owner.username as username,
			owner.phone as user_phone,
			owner.alphaname as alphaname,
			orders.id as id,
			COALESCE(item.short_name, 'заказ') as item,
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
				$sms_text = 'Успейте забрать '.($ord['status_step2']==223 ? 'сегодня ':'').$ord['item'].' Накл.'.$ord['newpost_id'];
				echo $ord['phone'].'<br>';
				echo $ord['id'].'<br>';
				echo $sms_text.'<br>';

				if ($_GET['view']) {
					continue;
				}

				send_sms($sms_text,"38".str_replace(array('(',')','-'), "", $ord['phone']),$ord['id'],(($ord['alphaname'] and $ord['alphaname'] != '') ? $ord['alphaname'] : null),2);

				$query = "INSERT INTO debug (msg) VALUES (:msg)";

				$query_params = array(
					':msg' => $ord['phone'].': '.$sms_text
				);

				try{
					$stmt = $db->prepare($query);
					$result = $stmt->execute($query_params);
				}
				catch(PDOException $ex){ die("Невозможно выполнить запрос: " . $ex->getMessage()); }


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