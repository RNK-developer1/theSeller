<?php 
	require 'config.php';

	function html_to_utf($input) {
		return preg_replace_callback("/(&#[0-9]+;)/", function($m) { return mb_convert_encoding($m[1], "UTF-8", "HTML-ENTITIES"); }, $input);
	}
		
	if (empty($_SESSION['user']) || $_SESSION['user']['active'] == 0) {
		header("Location: index.php"); 
		die("Перенаправление: index.php"); 
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
			owner.newpost_id as newpost_id,
			owner.newpost_psw as newpost_psw,
			owner.sender_whs_ref as sender_whs_ref,
			swr.city as sender_city,
			swr.address as sender_address,
			orders.id as id,
			COALESCE(item.name, orders.item) as item,
			orders.item_price as item_price,
			orders.item_params as item_params,
			orders.item_count as item_count,
			orders.city_area as city_area,
			orders.address as address,
			orders.courier_adr as courier_adr,
			orders.fio as fio,
			orders.phone as phone,
			orders.email as email,
			orders.whs_ref as whs_ref,
			COALESCE(cities_fix.fixed_name, warehouses.city) as rec_city,
			warehouses.address as rec_address,			
			warehouses.number as rec_number,
			orders.status_step1 as status_step1"					
		.($_SESSION['user']['group_id'] == 2 ? 
		"   FROM orders LEFT OUTER JOIN warehouses ON orders.whs_ref = warehouses.ref LEFT OUTER JOIN cities_fix ON warehouses.city_ref = cities_fix.ref LEFT OUTER JOIN item ON item.uuid = orders.item_id AND item.owner_id = orders.owner_id, users as owner, warehouses as swr
			WHERE 					
				owner.sender_whs_ref = swr.ref AND
				(orders.status_step2 = 0 OR orders.status_step2 > 50) AND
				(orders.status_step3 = 0 OR orders.status_step3 > 50) AND
				orders.owner_id = owner.id AND
				(:seller_id = '0' OR owner.id = :seller_id) AND
				owner.id IN (SELECT subseller_id FROM sellers_for_sellers WHERE sellers_for_sellers.seller_id = :user_id) AND
				(orders.status_step1 = 110 OR orders.status_step1 = 112) AND
				(:item_id IS NULL OR :item_id = '0' OR orders.item IN (SELECT name FROM item WHERE uuid = :item_id)) AND
				(orders.newpost_id = '' OR orders.newpost_id IS NULL)" :
		"   FROM orders LEFT OUTER JOIN warehouses ON orders.whs_ref = warehouses.ref LEFT OUTER JOIN cities_fix ON warehouses.city_ref = cities_fix.ref LEFT OUTER JOIN item ON item.uuid = orders.item_id AND item.owner_id = orders.owner_id, users as owner, warehouses as swr, operators_for_sellers
			WHERE
				owner.sender_whs_ref = swr.ref AND
				(orders.status_step2 = 0 OR orders.status_step2 > 50) AND
				(orders.status_step3 = 0 OR orders.status_step3 > 50) AND
				orders.owner_id = operators_for_sellers.seller_id AND
				operators_for_sellers.operator_id = :user_id AND
				(orders.status_step1 = 110 OR orders.status_step1 = 112) AND
				(orders.newpost_id = '' OR orders.newpost_id IS NULL) AND
				owner.id = orders.owner_id AND
				(:seller_id IS NULL OR :seller_id = '0' OR owner.id = :seller_id) AND
				(:item_id IS NULL OR :item_id = '0' OR orders.item IN (SELECT name FROM item WHERE uuid = :item_id))".(
				($_GET['oper'] and $_GET['oper']=='2') ? " AND oper_id IS NULL" : (($_GET['oper'] and $_GET['oper']=='1') ? " AND oper_id = :user_id" : ""))
		);		
	$query_params = ($_SESSION['user']['group_id'] == 2 ? 
			array( 
				':user_id' => $_SESSION['user']['id'],
				':seller_id' => $_GET['seller_id'] ? $_GET['seller_id'] : ($_GET['seller_id']=='0' ? 0 : $_SESSION['user']['id']),
				':item_id' => $_GET['item_id']
			) : 
			array( 
				':user_id' => $_SESSION['user']['id'],
				':seller_id' => $_GET['seller_id'],
				':item_id' => $_GET['item_id']
			)); 
	
	try{ 
		$stmt = $db->prepare($query); 
		$result = $stmt->execute($query_params); 
	} 
	catch(PDOException $ex){ die("Невозможно выполнить запрос: " . $ex->getMessage()); } 
	$orders = $stmt->fetchAll();
	
	$ids = "";
	foreach ($orders as $ord) {
		$ids .= $ord['id'].',';
	}
	$ids .= '0';
		
	if (empty($orders))	{ echo "Нечего печатать!"; }
		
	foreach ($orders as $ord) {
		if ($ord and $ord['whs_ref'] and $ord['newpost_id'] and $ord['newpost_psw']) {
			$ch = curl_init();
			$newpost_id = null;
			$snd_city_id = null;
			$snd_orgs = null;				
			$snd_org = null;
			$snd_whss = null;				
			$snd_whs = null;
			$snd_contacts = null;
			$snd_contact = null;				
			$rec_city_id = null;
			$rec_orgs = null;				
			$rec_org = null;
			$rec_whss = null;				
			$rec_whs = null;
			$snd_contacts = null;				
			$rec_contact = null;
			
			try	{
				//curl "http://orders.novaposhta.ua/account/login/" 
				//-H "Cookie: lang=ukr; lang=ukr; _ga=GA1.2.1142740224.1389576051; PHPSESSID=7allb6tp4anuda1hkfeadjh0v5; __utma=72278028.1142740224.1389576051.1394661164.1395023604.9; __utmb=72278028.2.10.1395023604; __utmc=72278028; __utmz=72278028.1395023604.9.9.utmcsr=novaposhta.com.ua|utmccn=(referral)|utmcmd=referral|utmcct=/; _ym_visorc_11387281=w; _ym_visorc_9411196=b" 
				//-H "Origin: http://orders.novaposhta.ua" -H "Accept-Encoding: gzip,deflate,sdch" 
				//-H "Accept-Language: ru-RU,ru;q=0.8,en-US;q=0.6,en;q=0.4" 
				//-H "User-Agent: Mozilla/5.0 (Windows NT 6.3) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/33.0.1750.146 Safari/537.36" 
				//-H "Content-Type: application/x-www-form-urlencoded" 
				//-H "Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8" 
				//-H "Cache-Control: max-age=0" -H "Referer: http://orders.novaposhta.ua/account/login/"
				//-H "Connection: keep-alive" --data "a=09717972&n=JGKGZ7d" --compressed
				curl_setopt($ch, CURLOPT_URL, 'http://orders.novaposhta.ua/account/login/');
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt($ch, CURLOPT_HTTPHEADER, Array("Content-Type: application/x-www-form-urlencoded"));
				curl_setopt($ch, CURLOPT_POSTFIELDS, "a=".$ord['newpost_id']."&n=".$ord['newpost_psw']);
				curl_setopt($ch, CURLOPT_POST, 1);
				//curl_setopt($ch, CURLOPT_COOKIESESSION, TRUE);
				curl_setopt($ch, CURLOPT_COOKIEJAR, dirname(__FILE__).'cookie_seller.txt');
				$response = curl_exec($ch);
				
				//curl "http://orders.novaposhta.ua/loyalty/ajax/citiesGetListAjax.php?q="%"D0"%"BA"%"D0"%"B8&allowed_cities=true" 
				//-H "Cookie: _ga=GA1.2.1142740224.1389576051; PHPSESSID=7allb6tp4anuda1hkfeadjh0v5; vip_id=Mjk1MTMx; vip_user_id=Mjc4OTgz; _ym_visorc_11387281=w; _ym_visorc_9411196=b; lang=ukr; __utma=72278028.1142740224.1389576051.1394661164.1395023604.9; __utmb=72278028.7.10.1395023604; __utmc=72278028; __utmz=72278028.1395023604.9.9.utmcsr=novaposhta.com.ua|utmccn=(referral)|utmcmd=referral|utmcct=/; PHPSESSID=5149qhiibkq1k39n1pughvna66" 
				//-H "Accept-Encoding: gzip,deflate,sdch" -H "Accept-Language: ru-RU,ru;q=0.8,en-US;q=0.6,en;q=0.4"
				//-H "User-Agent: Mozilla/5.0 (Windows NT 6.3) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/33.0.1750.146 Safari/537.36" 
				//-H "Accept: */*" -H "Referer: http://orders.novaposhta.ua/neworder_loyalty.php" 
				//-H "X-Requested-With: XMLHttpRequest" -H "Connection: keep-alive" --compressed
				
				curl_setopt($ch, CURLOPT_URL, 'http://orders.novaposhta.ua/loyalty/ajax/citiesGetListAjax.php?q='.urlencode($ord['sender_city']).'&allowed_cities=true');
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt($ch, CURLOPT_HTTPHEADER, Array("X-Requested-With: XMLHttpRequest"));
				curl_setopt($ch, CURLOPT_COOKIEJAR, dirname(__FILE__).'cookie_seller.txt');
				$response = curl_exec($ch);
				$response = explode("\n", $response);
				$snd_city_id = explode('|',$response[0]); $snd_city_id = trim($snd_city_id[1]);							
				
				//curl "http://orders.novaposhta.ua/loyalty/ajax/organisationGetListAjax.php?id=3"
				//-H "Cookie: _ga=GA1.2.1142740224.1389576051; PHPSESSID=7allb6tp4anuda1hkfeadjh0v5; vip_id=Mjk1MTMx; vip_user_id=Mjc4OTgz; _ym_visorc_11387281=w; _ym_visorc_9411196=b; lang=ukr; __utma=72278028.1142740224.1389576051.1394661164.1395023604.9; __utmb=72278028.7.10.1395023604; __utmc=72278028; __utmz=72278028.1395023604.9.9.utmcsr=novaposhta.com.ua|utmccn=(referral)|utmcmd=referral|utmcct=/; PHPSESSID=5149qhiibkq1k39n1pughvna66"
				//-H "Accept-Encoding: gzip,deflate,sdch" 
				//-H "Accept-Language: ru-RU,ru;q=0.8,en-US;q=0.6,en;q=0.4" 
				//-H "User-Agent: Mozilla/5.0 (Windows NT 6.3) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/33.0.1750.146 Safari/537.36" 
				//-H "Accept: application/json, text/javascript, */*; q=0.01" 
				//-H "Referer: http://orders.novaposhta.ua/neworder_loyalty.php" 
				//-H "X-Requested-With: XMLHttpRequest" -H "Connection: keep-alive" --compressed
				
				curl_setopt($ch, CURLOPT_URL, 'http://orders.novaposhta.ua/loyalty/ajax/organisationGetListAjax.php?id='.$snd_city_id);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt($ch, CURLOPT_HTTPHEADER, Array("X-Requested-With: XMLHttpRequest"));
				curl_setopt($ch, CURLOPT_COOKIEJAR, dirname(__FILE__).'cookie_seller.txt');
				$response = curl_exec($ch);
				$snd_orgs = json_decode($response, true); $snd_orgs = $snd_orgs['opts'];

				$snd_org = null;
				foreach ($snd_orgs as $org) {
					if (html_to_utf($org['title']) == $ord['username']) {
						$snd_org = $org['id'];
						//break;
					}
				}

				if (!$snd_org) {		
					// curl "http://orders.novaposhta.ua/loyalty/ajax/edit_partner.php" 
					// -H "Cookie: _ga=GA1.2.1142740224.1389576051; PHPSESSID=7allb6tp4anuda1hkfeadjh0v5; vip_id=Mjk1MTMx; vip_user_id=Mjc4OTgz; lang=ukr; __utma=72278028.1142740224.1389576051.1394661164.1395023604.9; __utmb=72278028.7.10.1395023604; __utmc=72278028; __utmz=72278028.1395023604.9.9.utmcsr=novaposhta.com.ua|utmccn=(referral)|utmcmd=referral|utmcct=/; PHPSESSID=5149qhiibkq1k39n1pughvna66" 
					// -H "Origin: http://orders.novaposhta.ua" -H "Accept-Encoding: gzip,deflate,sdch"
					// -H "Accept-Language: ru-RU,ru;q=0.8,en-US;q=0.6,en;q=0.4" 
					// -H "User-Agent: Mozilla/5.0 (Windows NT 6.3) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/33.0.1750.146 Safari/537.36" 
					// -H "Content-Type: application/x-www-form-urlencoded; charset=UTF-8" -H "Accept: text/plain, */*; q=0.01" 
					// -H "Referer: http://orders.novaposhta.ua/neworder_loyalty.php" -H "X-Requested-With: XMLHttpRequest" 
					// -H "Connection: keep-alive" 
					// --data "partner_id=&city_id=3&partnerType=sender&edrpou=&returnType=json&name="%"D0"%"A2"%"D0"%"B5"%"D1"%"81"%"D1"%"82+"%"D0"%"BE"%"D1"%"80"%"D0"%"B32" --compressed
					
					curl_setopt($ch, CURLOPT_URL, 'http://orders.novaposhta.ua/loyalty/ajax/edit_partner.php');
					curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
					curl_setopt($ch, CURLOPT_HTTPHEADER, Array("Content-Type: application/x-www-form-urlencoded; charset=UTF-8"));
					curl_setopt($ch, CURLOPT_POSTFIELDS, "partner_id=&city_id=".$snd_city_id."&partnerType=sender&edrpou=&returnType=json&name=".urlencode($ord['username']));
					curl_setopt($ch, CURLOPT_POST, 1);
					curl_setopt($ch, CURLOPT_COOKIEJAR, dirname(__FILE__).'cookie_seller.txt');
					$response = curl_exec($ch);
					preg_match('/\'(\d*)\'/',$response,$matches);
					$snd_org = $matches[1];
				}
								
				// curl "http://orders.novaposhta.ua/loyalty/ajax/contactGetListAjax.php?id=8265616&waren=true"
				//-H "Cookie: _ga=GA1.2.1142740224.1389576051; PHPSESSID=7allb6tp4anuda1hkfeadjh0v5; PHPSESSID=5149qhiibkq1k39n1pughvna66; vip_id=Mjk1MTMx; vip_user_id=Mjc4OTgz; _ym_visorc_11387281=w; _ym_visorc_9411196=b; lang=ukr; __utma=72278028.1142740224.1389576051.1395026745.1395029999.11; __utmb=72278028.9.10.1395029999; __utmc=72278028; __utmz=72278028.1395023604.9.9.utmcsr=novaposhta.com.ua|utmccn=(referral)|utmcmd=referral|utmcct=/" 
				//-H "Accept-Encoding: gzip,deflate,sdch" 
				//-H "Accept-Language: ru-RU,ru;q=0.8,en-US;q=0.6,en;q=0.4" 
				//-H "User-Agent: Mozilla/5.0 (Windows NT 6.3) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/33.0.1750.146 Safari/537.36" 
				//-H "Accept: application/json, text/javascript, */*; q=0.01" 
				//-H "Referer: http://orders.novaposhta.ua/neworder_loyalty.php" 
				//-H "X-Requested-With: XMLHttpRequest" -H "Connection: keep-alive" --compressed
				
				curl_setopt($ch, CURLOPT_URL, "http://orders.novaposhta.ua/loyalty/ajax/contactGetListAjax.php?id=".$snd_org."&waren=true");
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt($ch, CURLOPT_HTTPHEADER, Array("X-Requested-With: XMLHttpRequest"));
				curl_setopt($ch, CURLOPT_COOKIEJAR, dirname(__FILE__).'cookie_seller.txt');
				$response = curl_exec($ch);
				$snd_whss = json_decode($response, true); $snd_whss = $snd_whss['opts'];
								
				$snd_whs = null;
				foreach ($snd_whss as $whs) {
					if (html_to_utf($whs['title']) == str_replace('І','I',str_replace('є','е',str_replace('ї','i',str_replace('і','i',str_replace('№','N',$ord['sender_address'])))))) {
						$snd_whs = $whs['id'];
						break;
					}
				}
								
				// curl "http://orders.novaposhta.ua/loyalty/ajax/contactDetailsGetListAjax.php?id=8265607" 
				// -H "Cookie: _ga=GA1.2.1142740224.1389576051; PHPSESSID=7allb6tp4anuda1hkfeadjh0v5; PHPSESSID=5149qhiibkq1k39n1pughvna66; vip_id=Mjk1MTMx; vip_user_id=Mjc4OTgz; _ym_visorc_11387281=w; _ym_visorc_9411196=b; lang=ukr; __utma=72278028.1142740224.1389576051.1395029999.1395036604.12; __utmb=72278028.9.10.1395036604; __utmc=72278028; __utmz=72278028.1395023604.9.9.utmcsr=novaposhta.com.ua|utmccn=(referral)|utmcmd=referral|utmcct=/"
				// -H "Accept-Encoding: gzip,deflate,sdch"
				// -H "Accept-Language: ru-RU,ru;q=0.8,en-US;q=0.6,en;q=0.4"
				// -H "User-Agent: Mozilla/5.0 (Windows NT 6.3) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/33.0.1750.146 Safari/537.36"
				// -H "Accept: application/json, text/javascript, */*; q=0.01"
				// -H "Referer: http://orders.novaposhta.ua/neworder_loyalty.php" 
				// -H "X-Requested-With: XMLHttpRequest" -H "Connection: keep-alive" --compressed				
				curl_setopt($ch, CURLOPT_URL, "http://orders.novaposhta.ua/loyalty/ajax/contactDetailsGetListAjax.php?id=".$snd_org."&waren=true");
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt($ch, CURLOPT_HTTPHEADER, Array("X-Requested-With: XMLHttpRequest"));
				curl_setopt($ch, CURLOPT_COOKIEJAR, dirname(__FILE__).'cookie_seller.txt');
				$response = curl_exec($ch);
				$snd_contacts = json_decode($response, true); $snd_contacts = $snd_contacts['opts'];
								
				$snd_contact = null;
				foreach ($snd_contacts as $contact) {
					if (html_to_utf(trim($contact['title'])) == $ord['username']) {
						$snd_contact = $contact['id'];
						break;
					}
				}
 				
				if (!$snd_contact) {					
					// curl "http://orders.novaposhta.ua/loyalty/ajax/personEditor.php" 
					// -H "Cookie: _ga=GA1.2.1142740224.1389576051; PHPSESSID=7allb6tp4anuda1hkfeadjh0v5; PHPSESSID=5149qhiibkq1k39n1pughvna66; lang=ukr; __utma=72278028.1142740224.1389576051.1395026745.1395029999.11; __utmc=72278028; __utmz=72278028.1395023604.9.9.utmcsr=novaposhta.com.ua|utmccn=(referral)|utmcmd=referral|utmcct=/" 
					// -H "Origin: http://orders.novaposhta.ua" -H "Accept-Encoding: gzip,deflate,sdch" 
					// -H "Accept-Language: ru-RU,ru;q=0.8,en-US;q=0.6,en;q=0.4" 
					// -H "User-Agent: Mozilla/5.0 (Windows NT 6.3) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/33.0.1750.146 Safari/537.36" 
					// -H "Content-Type: application/x-www-form-urlencoded; charset=UTF-8" 
					// -H "Accept: text/plain, */*; q=0.01" -H "Referer: http://orders.novaposhta.ua/neworder_loyalty.php" 
					// -H "X-Requested-With: XMLHttpRequest" 
					// -H "Connection: keep-alive" 
					// --data "id=&fio="%"D0"%"A2"%"D0"%"B5"%"D1"%"81"%"D1"%"82+"%"D1"%"82"%"D0"%"B5"%"D1"%"81"%"D1"%"82&phone=0754040404&post=&personType=sender&partnerId=8265616" --compressed
					
					curl_setopt($ch, CURLOPT_URL, "http://orders.novaposhta.ua/loyalty/ajax/personEditor.php" );
					curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
					curl_setopt($ch, CURLOPT_HTTPHEADER, Array("Content-Type: application/x-www-form-urlencoded; charset=UTF-8"));
					curl_setopt($ch, CURLOPT_POSTFIELDS, "id=&fio=".urlencode($ord['username'])."&phone=".urlencode(str_replace(array('(',')','-'), "", $ord['user_phone']))."&post=&personType=sender&partnerId=".$snd_org);
					curl_setopt($ch, CURLOPT_POST, 1);
					curl_setopt($ch, CURLOPT_COOKIEJAR, dirname(__FILE__).'cookie_seller.txt');
					$response = curl_exec($ch);
					
					preg_match('/personId\":(\d*),/',$response,$matches);
					$snd_contact = $matches[1];
				}
								
				// receiver
				
				curl_setopt($ch, CURLOPT_URL, 'http://orders.novaposhta.ua/loyalty/ajax/citiesGetListAjax.php?q='.urlencode($ord['rec_city']).'&allowed_cities=true');
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt($ch, CURLOPT_HTTPHEADER, Array("X-Requested-With: XMLHttpRequest"));
				curl_setopt($ch, CURLOPT_COOKIEJAR, dirname(__FILE__).'cookie_seller.txt');
				$response = curl_exec($ch);
				$response = explode("\n", $response);
				$rec_city_id = explode('|',$response[0]); $rec_city_id = trim($rec_city_id[1]);								
				
				curl_setopt($ch, CURLOPT_URL, 'http://orders.novaposhta.ua/loyalty/ajax/organisationGetListAjax.php?id='.$rec_city_id);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt($ch, CURLOPT_HTTPHEADER, Array("X-Requested-With: XMLHttpRequest"));
				curl_setopt($ch, CURLOPT_COOKIEJAR, dirname(__FILE__).'cookie_seller.txt');
				$response = curl_exec($ch);
				$rec_orgs = json_decode($response, true); $rec_orgs = $rec_orgs['opts'];
								
				$rec_org = null;
				foreach ($rec_orgs as $org) {
					if (html_to_utf(trim($org['title'])) == trim($ord['fio'])) {
						$rec_org = $org['id'];
						break;
					}
				}
 				
				if (!$rec_org) {					
					curl_setopt($ch, CURLOPT_URL, 'http://orders.novaposhta.ua/loyalty/ajax/edit_partner.php');
					curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
					curl_setopt($ch, CURLOPT_HTTPHEADER, Array("Content-Type: application/x-www-form-urlencoded; charset=UTF-8"));
					curl_setopt($ch, CURLOPT_POSTFIELDS, "partner_id=&city_id=".$rec_city_id."&partnerType=recipient&edrpou=&returnType=json&name=".urlencode($ord['fio']));
					curl_setopt($ch, CURLOPT_POST, 1);
					curl_setopt($ch, CURLOPT_COOKIEJAR, dirname(__FILE__).'cookie_seller.txt');
					$response = curl_exec($ch);
					preg_match('/\'(\d*)\'/',$response,$matches);
					$rec_org = $matches[1];
				}
												
				curl_setopt($ch, CURLOPT_URL, "http://orders.novaposhta.ua/loyalty/ajax/contactGetListAjax.php?id=".$rec_org."&waren=true");
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt($ch, CURLOPT_HTTPHEADER, Array("X-Requested-With: XMLHttpRequest"));
				curl_setopt($ch, CURLOPT_COOKIEJAR, dirname(__FILE__).'cookie_seller.txt');
				$response = curl_exec($ch);
				$rec_whss = json_decode($response, true); $rec_whss = $rec_whss['opts'];
								
				$rec_whs = null;
				foreach ($rec_whss as $whs) {
					if (html_to_utf($whs['title']) == str_replace('І','I',str_replace('є','е',str_replace('ї','i',str_replace('і','i',str_replace('№','N',$ord['rec_address'])))))) {
						$rec_whs = $whs['id'];
						break;
					}
				}	

				if (!$rec_whs) {
					foreach ($rec_whss as $whs) {
						$matches=array();
						preg_match('~N(\d*)~',$whs['title'],$matches);
						if ($matches[1] == $ord['rec_number']) {
							$rec_whs = $whs['id'];
							break;	
						}
					}
				}

				curl_setopt($ch, CURLOPT_URL, "http://orders.novaposhta.ua/loyalty/ajax/contactDetailsGetListAjax.php?id=".$rec_org."&waren=true");
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt($ch, CURLOPT_HTTPHEADER, Array("X-Requested-With: XMLHttpRequest"));
				curl_setopt($ch, CURLOPT_COOKIEJAR, dirname(__FILE__).'cookie_seller.txt');
				$response = curl_exec($ch);
				$rec_contacts = json_decode($response, true); $rec_contacts = $rec_contacts['opts'];
				
				$rec_contact = null;
				foreach ($rec_contacts as $contact) {
					if (html_to_utf(trim($contact['title'])) == trim($ord['fio'])) {
						$rec_contact = $contact['id'];
						break;
					}
				}
 				
				if (!$rec_contact) {					
					curl_setopt($ch, CURLOPT_URL, "http://orders.novaposhta.ua/loyalty/ajax/personEditor.php" );
					curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
					curl_setopt($ch, CURLOPT_HTTPHEADER, Array("Content-Type: application/x-www-form-urlencoded; charset=UTF-8"));
					curl_setopt($ch, CURLOPT_POSTFIELDS, "id=&fio=".urlencode(trim($ord['fio']))."&phone=".urlencode(str_replace(array('(',')','-'), "", $ord['phone']))."&post=&personType=recipient&partnerId=".$rec_org);
					curl_setopt($ch, CURLOPT_POST, 1);
					curl_setopt($ch, CURLOPT_COOKIEJAR, dirname(__FILE__).'cookie_seller.txt');
					$response = curl_exec($ch);
					
					preg_match('/personId\":(\d*),/',$response,$matches);
					$rec_contact = $matches[1];
				}
				
				curl_setopt($ch, CURLOPT_URL, "http://orders.novaposhta.ua/loyalty/ajax/saveAddMoreOption.php" );
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt($ch, CURLOPT_HTTPHEADER, Array("Content-Type: application/x-www-form-urlencoded; charset=UTF-8"));
				curl_setopt($ch, CURLOPT_POSTFIELDS, "addMore=true");
				curl_setopt($ch, CURLOPT_POST, 1);
				curl_setopt($ch, CURLOPT_COOKIEJAR, dirname(__FILE__).'cookie_seller.txt');
				$response = curl_exec($ch);
				
				
				// curl "http://orders.novaposhta.ua/neworder_loyalty.php"
				// -H "Cookie: _ga=GA1.2.1142740224.1389576051; PHPSESSID=7allb6tp4anuda1hkfeadjh0v5; PHPSESSID=5149qhiibkq1k39n1pughvna66; vip_id=Mjk1MTMx; vip_user_id=Mjc4OTgz; _ym_visorc_11387281=w; _ym_visorc_9411196=b; lang=ukr; __utma=72278028.1142740224.1389576051.1395029999.1395036604.12; __utmb=72278028.9.10.1395036604; __utmc=72278028; __utmz=72278028.1395023604.9.9.utmcsr=novaposhta.com.ua|utmccn=(referral)|utmcmd=referral|utmcct=/" 
				// -H "Origin: http://orders.novaposhta.ua" -H "Accept-Encoding: gzip,deflate,sdch" 
				// -H "Accept-Language: ru-RU,ru;q=0.8,en-US;q=0.6,en;q=0.4" 
				// -H "User-Agent: Mozilla/5.0 (Windows NT 6.3) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/33.0.1750.146 Safari/537.36" -H "Content-Type: application/x-www-form-urlencoded" 
				// -H "Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8" 
				// -H "Cache-Control: max-age=0" -H "Referer: http://orders.novaposhta.ua/neworder_loyalty.php" 
				// -H "Connection: keep-alive" 
				// --data "cargoSpec=&cargoSpecType=1&order_old_date=17.03.2014&cargo_type_id=&cde=&sender_warenhouse_id=107261624&recipient_warenhouse_id=107264531&redelivery_type_id_palleta_type=0&additional_redelivery_type_id_palleta_type=0&redelivery_type_id_palleta_count=0&additional_redelivery_type_id_palleta_count=0&dt=17.03.2014&sender_city_input="%"C7"%"E0"%"EF"%"EE"%"F0"%"B3"%"E6"%"E6"%"FF&sender_city=3&sender_partner=8265616&sender_address=107261624&sender_warenhouse=1&sender_person=14502477&recipient_city_input="%"CA"%"E8"%"BF"%"E2&recipient_city=4&recipient_partner=8265773&recipient_title=&recipient_address=107264531&recipient_warenhouse=1&recipient_person=14502539&load_type_id=0&k=&l="%"D2"%"EE"%"E2"%"E0"%"F0+"%"B91&n=1&m=2&x=10&y=10&z=10&v=150&abc=0&zdf=on&redelivery_type_id=2&zd=150&additional_redelivery_type_id=&redeliveryPayer=2&redeliveryPayerCityId=4&documents=&additionaldata=&delivery_type=4&payer=2&paymentType=1&third_city_input=&third_city=&document_template_name=&transaction_key=&okbtn="%"D1"%"F2"%"E2"%"EE"%"F0"%"E8"%"F2"%"E8+"%"E7"%"E0"%"EC"%"EE"%"E2"%"EB"%"E5"%"ED"%"ED"%"FF" --compressed
				
				//curl "http://orders.novaposhta.ua/neworder_loyalty.php" -H "Cookie: _ga=GA1.2.1142740224.1389576051; PHPSESSID=h8vrpkvnj0l0nrh3trgje1c286; PHPSESSID=oi8cr7lafvqtdame7qe2gsoc13; vip_id=Mjk1MTMx; vip_user_id=Mjc4OTgz; _ym_visorc_11387281=w; _ym_visorc_9411196=b; lang=ukr; __utma=72278028.1142740224.1389576051.1395344891.1395354241.15; __utmb=72278028.7.10.1395354241; __utmc=72278028; __utmz=72278028.1395344891.14.11.utmcsr=novaposhta.com.ua|utmccn=(referral)|utmcmd=referral|utmcct=/" -H "Origin: http://orders.novaposhta.ua" -H "Accept-Encoding: gzip,deflate,sdch" -H "Accept-Language: ru-RU,ru;q=0.8,en-US;q=0.6,en;q=0.4" -H "User-Agent: Mozilla/5.0 (Windows NT 6.3) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/33.0.1750.154 Safari/537.36" -H "Content-Type: application/x-www-form-urlencoded" -H "Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8" -H "Cache-Control: max-age=0" -H "Referer: http://orders.novaposhta.ua/neworder_loyalty.php" -H "Connection: keep-alive" --data "cargoSpec=&cargoSpecType=1&order_old_date=21.03.2014&cargo_type_id=&cde=&sender_warenhouse_id=107261690&recipient_warenhouse_id=108661734&redelivery_type_id_palleta_type=0&additional_redelivery_type_id_palleta_type=0&redelivery_type_id_palleta_count=0&additional_redelivery_type_id_palleta_count=0&dt=21.03.2014&sender_city_input="%"C7"%"E0"%"EF"%"EE"%"F0"%"B3"%"E6"%"E6"%"FF&sender_city=3&sender_partner=8265619&sender_address=107261690&sender_warenhouse=1&sender_person=14599549&recipient_city_input="%"CA"%"E8"%"BF"%"E2&recipient_city=4&recipient_partner=8346141&recipient_title=&recipient_address=108661739&recipient_warenhouse=1&recipient_person=14629291&load_type_id=0&k=&l="%"D2"%"EE"%"E2"%"E0"%"F0+"%"B91&n=1&m=0.1&x=10&y=10&z=10&v=1900&abc=0&zdf=on&redelivery_type_id=2&zd=1900&additional_redelivery_type_id=&redeliveryPayer=2&redeliveryPayerCityId=4&documents=&additionaldata=&delivery_type=4&payer=2&paymentType=1&third_city_input=&third_city=&addMore=on&document_template_name=&transaction_key=&okbtn="%"D1"%"F2"%"E2"%"EE"%"F0"%"E8"%"F2"%"E8+"%"E7"%"E0"%"EC"%"EE"%"E2"%"EB"%"E5"%"ED"%"ED"%"FF" --compressed
				curl_setopt($ch, CURLOPT_URL, "http://orders.novaposhta.ua/neworder_loyalty.php" );
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt($ch, CURLOPT_HTTPHEADER, Array("Content-Type: application/x-www-form-urlencoded; charset=UTF-8"));
				$crd = new DateTime();
				$crd = $crd->format('d.m.Y');
				curl_setopt($ch, CURLOPT_POSTFIELDS, 'cargoSpec='.
				'&cargoSpecType=1'.
				'&order_old_date='.$crd.
				'&cargo_type_id=&cde='.
				'&sender_warenhouse_id='.$snd_whs.
				'&recipient_warenhouse_id='.$rec_whs.
				'&redelivery_type_id_palleta_type=0'.
				'&additional_redelivery_type_id_palleta_type=0'.
				'&redelivery_type_id_palleta_count=0'.
				'&additional_redelivery_type_id_palleta_count=0'.
				'&dt='.$crd.
				'&sender_city_input='.urlencode($ord['sender_city']).
				'&sender_city='.$snd_city_id.
				'&sender_partner='.$snd_org.
				'&sender_address='.$snd_whs.
				'&sender_warenhouse=1'.
				'&sender_person='.$snd_contact.
				'&recipient_city_input='.urlencode(trim($ord['rec_city'])).
				'&recipient_city='.$rec_city_id.
				'&recipient_partner='.$rec_org.
				'&recipient_title='.
				'&recipient_address='.$rec_whs.
				'&recipient_warenhouse=1'.
				'&recipient_person='.$rec_contact.
				'&load_type_id=0'.
				'&k='.
				'&l='.urlencode(trim(iconv("utf-8", "windows-1251", $ord['item']." (".$ord['item_count']." шт)"))).
				'&n=1'.
				'&m='.($ord['weight'] ? $ord['weight'] : number_format(floatval($ord['i_weight'])*floatval($ord['item_count']),2)).
				'&x='.($ord['width'] ? $ord['width'] : number_format(floatval($ord['i_width'])*floatval($ord['item_count']),0)).
				'&y='.$ord['height'].
				'&z='.$ord['length'].
				'&v='.$ord['item_price'].
				($ord['status_step1'] == 110 ? '&abc=0' : '').
				($ord['status_step1'] == 110 ? '&zdf=on' : '').
				'&redelivery_type_id='.($ord['status_step1'] == 110 ? '2' : '').
				($ord['status_step1'] == 110 ? ('&zd='.$ord['item_price']) : '').
				'&additional_redelivery_type_id='.
				'&redeliveryPayer='.($ord['status_step1'] == 110 ? '2' : '').
				'&redeliveryPayerCityId='.$rec_city_id.
				'&documents='.
				'&additionaldata='.
				'&delivery_type=4'.
				'&payer=2'.
				'&paymentType=1'.
				'&third_city_input=&third_city=&document_template_name=&addMore=on'.
				'&transaction_key='.
				'&okbtn="%"D1"%"F2"%"E2"%"EE"%"F0"%"E8"%"F2"%"E8+"%"E7"%"E0"%"EC"%"EE"%"E2"%"EB"%"E5"%"ED"%"ED"%"FF');
				curl_setopt($ch, CURLOPT_POST, 1);
				curl_setopt($ch, CURLOPT_COOKIEJAR, dirname(__FILE__).'cookie_seller.txt');
								
				$response = curl_exec($ch);

				$output = str_replace(array("\r\n", "\r"), "\n", $response);
				$lines = explode("\n", $output);
				$new_lines = array();

				foreach ($lines as $i => $line) {
					if(!empty($line))
						$new_lines[] = trim($line);
				}
				$html = implode($new_lines);

				$match = array();
				preg_match('~openMessageDialog\([^\d]*(\d*)~',$html,$match);
				
				$newpost_id = $match[1];
				
				//echo $newpost_id;
			} catch (Exception $e) {
				$newpost_id = null;
			}					
			if ($newpost_id) {
		?>			
				<div style="page-break-after: always">
				<iframe width=760 height=1080 frameborder="0" allowtransparency="true" scrolling="no" src="newpost_ttn.php?newpost_id=<?php echo $newpost_id;?>&id=<?php echo $ord['owner_id'];?>"></iframe>
				</div>
				<div style="page-break-after: always">
				<iframe width=760 height=1080 frameborder="0" allowtransparency="true" scrolling="no" src="newpost_ttn.php?newpost_id=<?php echo $newpost_id;?>&id=<?php echo $ord['owner_id'];?>"></iframe>
				</div>
		<?php		
				$query = "INSERT INTO orders_audit(date, order_id, user_id, activity, details) VALUES
							(	NOW(),
								:order_id,
								:user_id,
								:activity,
								:details		)";
				
				$query_params = array( 
					':details' => $newpost_id,
					':activity' => 'Создана декларация Новой Почты (ЛК)',
					':user_id' => $_SESSION['user']['id'],
					':order_id' => $ord['id']
				); 
				
				try{ 
					$stmt = $db->prepare($query); 
					$result = $stmt->execute($query_params); 
				} 
				catch(PDOException $ex){ die("Невозможно выполнить запрос: " . $ex->getMessage()); } 	
				
				$query = " 
					UPDATE orders SET status_step2 = 250, newpost_id = :newpost_id, newpost_answer = NULL WHERE id = :order_id";		
				$query_params = array( 			
					':order_id' => $ord['id'],
					':newpost_id' => $newpost_id
				); 
				
				try{ 
					$stmt = $db->prepare($query); 
					$result = $stmt->execute($query_params); 
				} 
				catch(PDOException $ex){ die("Невозможно выполнить запрос: " . $ex->getMessage()); }
				
			} else {
				?>
					<div style="page-break-after: always; border: 5px solid red;">
						<p><?php print_r($ord); ?></p>
						<p><?php
							echo $snd_city_id.'<br>';
							//echo print_r($snd_orgs, true).'<br>';				
							echo $snd_org.'<br>';
							//echo print_r($snd_whss, true).'<br>';				
							echo $snd_whs.'<br>';
							//echo print_r($snd_contacts, true).'<br>';
							echo $snd_contact.'<br>';				
							echo $rec_city_id.'<br>';
							//echo print_r($rec_orgs, true).'<br>';				
							echo $rec_org.'<br>';
							//echo print_r($rec_whss, true).'<br>';				
							echo $rec_whs.'<br>';
							//echo print_r($snd_contacts, true).'<br>';				
							echo $rec_contact.'<br>';						
						?></p>
					</div>
				<?php
			}
					
		}
	}
?>
