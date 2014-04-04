<?php
	require("config.php"); 	
	
	function mail_attachment($message, $attach) {
		$crd = new DateTime();
		$crd = $crd->format('d-m-y');
					 
	  $filename = 'Возвраты'.$crd.'.doc';
	  $to = "call.eugene@gmail.com,mashulenka0708@gmail.com"; #kolyada.v@novaposhta.ua,
	  $from = 'admin@goodthing.in.ua';
	  $message = "Добрый день!<br/><br/>Во вложении - документ с заявлениями на возврат<br/><br/>Это Письмо сгенерировано автоматически, просьба на него не отвечать<br/><br/><br/><span style='color:lightgray; font-size:small;'>".$message."</span>";
	  $subject = 'Заявления на возврат от '.$crd;
	  
	  $content = chunk_split(base64_encode($attach)); 
	  $uid = md5(uniqid(time()));
	  $from = str_replace(array("\r", "\n"), '', $from);
	  $header = "From: ".$from."\r\n"
		  ."MIME-Version: 1.0\r\n"
		  ."Content-Type: multipart/mixed; boundary=\"".$uid."\"\r\n\r\n"
		  ."This is a multi-part message in MIME format.\r\n" 
		  ."--".$uid."\r\n"
		  ."Content-type:text/html; charset=utf-8\r\n"
		  ."Content-Transfer-Encoding: 7bit\r\n\r\n"
		  .$message."\r\n\r\n"
		  ."--".$uid."\r\n"
		  ."Content-Type: application/octet-stream; name=\"".$filename."\"\r\n"
		  ."Content-Transfer-Encoding: base64\r\n"
		  ."Content-Disposition: attachment; filename=\"".$filename."\"\r\n\r\n"
		  .$content."\r\n\r\n"
		  ."--".$uid."--"; 
	  return mail($to, $subject, "", $header);
	 }

	$query = " 
		SELECT 		
			orders.weight as weight,
			COALESCE(item.weight, 0.1) as i_weight,
			orders.width as width,
			COALESCE(item.width, 10) as i_width,
			COALESCE(orders.length, item.length, 10) as length,
			COALESCE(orders.height, item.height, 10) as height,
			owner.id as owner_id,
			owner.username as username,
			owner.phone as user_phone,
			owner.newpost_api as newpost_api,
			owner.sender_whs_ref as sender_whs_ref,
			owner.fio_ukr,
			owner.pass_s,
			owner.pass_n,
			owner.pass_issued,
			owner.pass_i_date,
			owner.adr,
			orders.id as id,
			orders.newpost_id as newpost_id,
			COALESCE(item.name, orders.item) as item,
			orders.item_price as item_price,
			orders.item_params as item_params,
			orders.item_count as item_count,
			warehouses.city as city_area,
			warehouses.address as address,
			orders.courier_adr as courier_adr,
			orders.fio as fio,
			orders.phone as phone,
			orders.email as email,
			orders.whs_ref as whs_ref,
			orders.status_step1 as status_step1
			FROM orders LEFT OUTER JOIN warehouses ON warehouses.ref = orders.whs_ref LEFT OUTER JOIN item ON item.uuid = orders.item_id AND item.owner_id = orders.owner_id, users as owner
			WHERE 		
				orders.item = item.name AND
				item.owner_id = orders.owner_id AND
				(orders.status_step1 = 0 OR orders.status_step1 > 50) AND
				(orders.status_step3 = 0 OR orders.status_step3 > 50) AND
				orders.owner_id = owner.id AND				
				orders.status_step2 IN (220, 225)";		
	
	try{ 
		$stmt = $db->prepare($query); 
		$result = $stmt->execute(); 
	} 
	catch(PDOException $ex){ die("Невозможно выполнить запрос: " . $ex->getMessage()); } 
	$orders = $stmt->fetchAll();
	
?>

Возвраты (письмо отправлено)<br>
<?php 
	$tmsg = "";
	$ta = "";
	$crd = new DateTime();
	$crd = $crd->format('d.m.y');
	foreach ($orders as $idx=>$ord) {
		if ($ord['fio_ukr'] and $ord['pass_s'] and $ord['pass_n'] and $ord['pass_issued'] and $ord['pass_i_date'] and $ord['adr']) {
			$ord['fio_ukr'] = iconv ('utf-8', 'windows-1251', $ord['fio_ukr']);
			$ord['pass_s'] = iconv ('utf-8', 'windows-1251', $ord['pass_s']);
			$ord['pass_n'] = iconv ('utf-8', 'windows-1251', $ord['pass_n']);
			$ord['pass_issued'] = iconv ('utf-8', 'windows-1251', $ord['pass_issued']);
			$ord['pass_i_date'] = iconv ('utf-8', 'windows-1251', $ord['pass_i_date']);
			$ord['adr'] = iconv ('utf-8', 'windows-1251', $ord['adr']);
			$ord['city_area'] = iconv ('utf-8', 'windows-1251', $ord['city_area']);
			$tmsg .= ($idx+1).". ".$ord['newpost_id']." ".$ord['username']."<br>";
			$is_1 = split(' ',$ord['pass_issued']);
			$is_1 = $is_1[0];
			$is_2 = split(' ',$ord['pass_issued'],2);
			$is_2 = $is_2[1];
			include 'newpost_backsent_template.php';
			$ta .= $doc_block;
		}
		
		$query = "UPDATE orders SET status_step2 = 224 WHERE id = :order_id";
		$query_params = array(
			':order_id' => $ord['id']
		);
		
		try{ 
			$stmt = $db->prepare($query); 
			$result = $stmt->execute($query_params); 
		} 
		catch(PDOException $ex){ die("Невозможно выполнить запрос: " . $ex->getMessage()); }
	}	
	echo $tmsg;
	mail_attachment($tmsg, $doc_h.$ta.$doc_f);
?>