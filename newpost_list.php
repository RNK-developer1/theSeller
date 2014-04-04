<?php
	require("config.php"); 	
	
	if (empty($_SESSION['user']) || $_SESSION['user']['active'] == 0) {
		header("Location: index.php"); 
		die("Перенаправление: index.php"); 
	}

	$query = " 
		SELECT 			
			owner.id as owner_id,
			owner.username as username,
			owner.newpost_api as newpost_api,
			orders.id as id,
			COALESCE(item.name, orders.item) as item,
			CASE orders.status_step1 WHEN 110 THEN orders.item_price WHEN 114 THEN orders.item_price ELSE 0 END as item_price,
			orders.item_params as item_params,
			orders.item_count as item_count,
			warehouses.cityRu as city_area,
			warehouses.addressRu as address,
			orders.city_area as old_city_area,
			orders.address as old_address,
			orders.fio as fio,
			orders.phone as phone,
			orders.email as email,
			orders.status_step1 as status_step1"					
		.($_SESSION['user']['group_id'] == 2 ? 
		"   FROM users as owner, orders LEFT OUTER JOIN warehouses ON orders.whs_ref = warehouses.ref LEFT OUTER JOIN item ON item.uuid = orders.item_id AND item.owner_id = orders.owner_id
			WHERE 					
				(orders.status_step2 = 0 OR orders.status_step2 > 50) AND
				(orders.status_step3 = 0 OR orders.status_step3 > 50) AND
				orders.owner_id = owner.id AND
				(:seller_id = '0' OR owner.id = :seller_id) AND
				owner.id IN (SELECT subseller_id FROM sellers_for_sellers WHERE sellers_for_sellers.seller_id = :user_id) AND
				(orders.status_step1 = 110 OR orders.status_step1 = 112 OR orders.status_step1 = 114 OR orders.status_step1 = 115) AND
				(:item_id IS NULL OR :item_id = '0' OR orders.item IN (SELECT name FROM item WHERE uuid = :item_id)) AND
				orders.newpost_id = ''" :
		"   FROM users as owner, operators_for_sellers, orders LEFT OUTER JOIN warehouses ON orders.whs_ref = warehouses.ref LEFT OUTER JOIN item ON item.uuid = orders.item_id AND item.owner_id = orders.owner_id
			WHERE				
				(orders.status_step2 = 0 OR orders.status_step2 > 50) AND
				(orders.status_step3 = 0 OR orders.status_step3 > 50) AND
				orders.owner_id = operators_for_sellers.seller_id AND
				operators_for_sellers.operator_id = :user_id AND
				(orders.status_step1 = 110 OR orders.status_step1 = 112 OR orders.status_step1 = 114 OR orders.status_step1 = 115) AND
				orders.newpost_id = '' AND
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
	
	$query = " 
		UPDATE orders SET status_step2 = 200, alert_at = NOW() WHERE id IN (".$ids.")";		
	$query_params = array( 
		':user_id' => $_SESSION['user']['id']
	); 
	
	try{ 
		$stmt = $db->prepare($query); 
		$result = $stmt->execute($query_params); 
	} 
	catch(PDOException $ex){ die("Невозможно выполнить запрос: " . $ex->getMessage()); } 
	
	foreach ($orders as $ord) {
		$query = "INSERT INTO orders_audit(date, order_id, user_id, activity, details) VALUES
					(	NOW(),
						:order_id,
						:user_id,
						:activity,
						:details		)";
		
		$query_params = array( 
			':details' => print_r($ord,true),
			':activity' => 'Передан в списке заказов в Новую Почту',
			':user_id' => $_SESSION['user']['id'],
			':order_id' => $ord['id']
		); 
		
		try{ 
			$stmt = $db->prepare($query); 
			$result = $stmt->execute($query_params); 
		} 
		catch(PDOException $ex){ die("Невозможно выполнить запрос: " . $ex->getMessage()); } 	
	}
?>

<!doctype html>
<html lang="ru">
	<table border=1 cellpadding=4 style='border: 1px solid black; border-collapse: collapse;'>
	<tr><th>№</th><th>Город, область</th><th>Телефон</th><th>ФИО</th><th>Адрес, отделение НП</th><th>Стоимость</th><th>Описание</th><th>Параметры</th></tr>
	<?php 
		$tlist = "";
		foreach ($orders as $idx=>$ord) {
		$tlist .= "<tr><td>".($idx+1)."</td>
					<td>".($ord['city_area'] ? $ord['city_area'] : $ord['old_city_area'])."</td>
					<td>".str_replace(array('(',')','-'), "", $ord['phone'])."</td>
					<td>".$ord['fio']."</td>
					<td>".(($ord['address'] and $ord['status_step1'] != 114 and $ord['status_step1'] != 115) ? $ord['address']: $ord['old_address'])."</td>
					<td>".(($ord['status_step1'] == 112 or $ord['status_step1'] == 115) ? 'предоплата' : $ord['item_price'])."</td>
					<td>".$ord['item']." (".$ord['item_count']." шт)</td>
					<td>".$ord['item_params']."</td>
				</tr>";
		}
		
		echo $tlist;
		
		/*if ($tlist AND $tlist != '') { 		
			$query = "INSERT INTO newpost_lists_history(date, author_id, list) VALUES
						(	NOW(),
							:user_id,
							:list )";
			
			$query_params = array( 
				':user_id' => $_SESSION['user']['id'],
				':list' => $tlist
			); 
			
			try{ 
				$stmt = $db->prepare($query); 
				$result = $stmt->execute($query_params); 
			} 
			catch(PDOException $ex){ die("Невозможно выполнить запрос: " . $ex->getMessage()); } 	
		}*/
	 ?>
	</table>
</body>
</html>