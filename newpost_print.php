<?php 
	require 'config.php';

	libxml_use_internal_errors(true); 

		
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
			owner.sender_whs_ref as sender_whs_ref,
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
			orders.status_step1 as status_step1"					
		.($_SESSION['user']['group_id'] == 2 ? 
		"   FROM orders LEFT OUTER JOIN item ON item.uuid = orders.item_id AND item.owner_id = orders.owner_id, users as owner
			WHERE 					
				(orders.status_step2 = 0 OR orders.status_step2 > 50) AND
				(orders.status_step3 = 0 OR orders.status_step3 > 50) AND
				orders.owner_id = owner.id AND
				(:seller_id = '0' OR owner.id = :seller_id) AND
				owner.id IN (SELECT subseller_id FROM sellers_for_sellers WHERE sellers_for_sellers.seller_id = :user_id) AND
				(orders.status_step1 = 110 OR orders.status_step1 = 112) AND
				(:item_id IS NULL OR :item_id = '0' OR orders.item IN (SELECT name FROM item WHERE uuid = :item_id)) AND
				(orders.newpost_id = '' OR orders.newpost_id IS NULL)" :
		"   FROM orders LEFT OUTER JOIN item ON item.uuid = orders.item_id AND item.owner_id = orders.owner_id, users as owner, operators_for_sellers
			WHERE
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
	
			if ($ord and $ord['whs_ref'] and $ord['newpost_api']) {
				$sender_whs = $ord['sender_whs_ref'] ? $ord['sender_whs_ref'] : '11b440b2-edc9-11e0-b926-0026b97ed48a';
				$rcpt_whs = $ord['whs_ref'];		
			
				$xml = "<?xml version=\"1.0\" encoding=\"utf-8\"?><file><auth>".$ord['newpost_api']."</auth><orderNew 
					order_id=\"".$ord['id']."\"
					sender_company=\"".htmlspecialchars($ord['username'], ENT_QUOTES, 'UTF-8')."\"
					sender_contact=\"".htmlspecialchars($ord['username'], ENT_QUOTES, 'UTF-8')."\"
					sender_phone=\"".str_replace(array('(',')','-'), "", $ord['user_phone'])."\"
					sender_warehouse_ref=\"".$sender_whs."\"
					rcpt_name=\"".htmlspecialchars($ord['fio'], ENT_QUOTES, 'UTF-8')."\"
					rcpt_contact=\"".htmlspecialchars($ord['fio'], ENT_QUOTES, 'UTF-8')."\"
					rcpt_phone_num=\"".str_replace(array('(',')','-'), "", $ord['phone'])."\"
					rcpt_warehouse_ref=\"".$rcpt_whs."\"
					pack_type=\"\"
					cost=\"".($ord['item_price'] < 600 ? 600 : $ord['item_price'])."\"".(
					$ord['status_step1'] == 110 ? "			
					redelivery_type=\"2\"
					redelivery_payment_payer=\"2\"
					delivery_in_out=\"".$ord['item_price']."\"" : "")."
					weight=\"".($ord['weight'] ? $ord['weight'] : number_format(floatval($ord['i_weight'])*floatval($ord['item_count']),2))."\"
					length=\"".$ord['length']."\"
					width=\"".($ord['width'] ? $ord['width'] : number_format(floatval($ord['i_width'])*floatval($ord['item_count']),0))."\"
					height=\"".$ord['height']."\"
					description=\"".htmlspecialchars($ord['item'], ENT_QUOTES, 'UTF-8')." (".$ord['item_count']." шт)\"
					pay_type=\"1\"			
					payer=\"0\">
				<order_cont
					cont_description=\"".htmlspecialchars($ord['item'], ENT_QUOTES, 'UTF-8')." (".$ord['item_count']." шт)\" />
				</orderNew></file>";
				
				$xml_o = new SimpleXMLElement($xml);

				$ch = curl_init();
				curl_setopt($ch, CURLOPT_URL, 'http://orders.novaposhta.ua/xml.php');
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt($ch, CURLOPT_HTTPHEADER, Array("Content-Type: text/xml"));
				curl_setopt($ch, CURLOPT_HEADER, 0);
				curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
				curl_setopt($ch, CURLOPT_POST, 1);
				curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
				$response = curl_exec($ch);
				
				try {
					$declarations = new SimpleXMLElement($response);
					
					if ($declarations->orderNew->attributes()->np_id == 0) {					
						echo 'Заказ №'.$declarations->orderNew->attributes()->id.'<br/>';
						echo $declarations->orderNew->attributes()->error;
						exit;
					}
				?>	
					<div style="page-break-after: always">
					<iframe width=760 height=1080 frameborder="0" allowtransparency="true" scrolling="no" src="<?php echo "http://orders.novaposhta.ua/pformn.php?o=".$declarations->orderNew->attributes()->np_id."&num_copy=2&token=".$ord['newpost_api'];?>");></iframe>
					</div>
					<div style="page-break-after: always">
					<iframe width=760 height=1080 frameborder="0" allowtransparency="true" scrolling="no" src="<?php echo "http://orders.novaposhta.ua/pformn.php?o=".$declarations->orderNew->attributes()->np_id."&num_copy=2&token=".$ord['newpost_api'];?>");></iframe>
					</div>
					
				<?php		
					$query = "INSERT INTO orders_audit(date, order_id, user_id, activity, details) VALUES
								(	NOW(),
									:order_id,
									:user_id,
									:activity,
									:details		)";
					
					$query_params = array( 
						':details' => $declarations->orderNew->attributes()->np_id,
						':activity' => 'Создана декларация Новой Почты',
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
						':newpost_id' => $declarations->orderNew->attributes()->np_id
					); 
					
					try{ 
						$stmt = $db->prepare($query); 
						$result = $stmt->execute($query_params); 
					} 
					catch(PDOException $ex){ die("Невозможно выполнить запрос: " . $ex->getMessage()); }		
				} catch (Exception $e) {
					echo $response;
				}									 		
			}
	}
?>
