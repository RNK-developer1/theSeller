<?php
	require("config.php"); 	
	
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
			warehouses.cityRu as city_area,
			warehouses.addressRu as address,
			orders.courier_adr as courier_adr,
			orders.fio as fio,
			orders.phone as phone,
			orders.email as email,
			orders.whs_ref as whs_ref,
			orders.status_step1 as status_step1"					
		.($_SESSION['user']['group_id'] == 2 ? 
		"   FROM orders LEFT OUTER JOIN warehouses ON warehouses.ref = orders.whs_ref LEFT OUTER JOIN item ON item.uuid = orders.item_id AND item.owner_id = orders.owner_id, users as owner
			WHERE 		
				orders.item = item.name AND
				item.owner_id = orders.owner_id AND
				(orders.status_step2 = 0 OR orders.status_step2 > 50) AND
				(orders.status_step3 = 0 OR orders.status_step3 > 50) AND
				orders.owner_id = owner.id AND
				(:seller_id = '0' OR owner.id = :seller_id) AND
				owner.id IN (SELECT subseller_id FROM sellers_for_sellers WHERE sellers_for_sellers.seller_id = :user_id) AND
				(orders.status_step1 = 110 OR orders.status_step1 = 112) AND
				(:item_id IS NULL OR :item_id = '0' OR orders.item IN (SELECT name FROM item WHERE uuid = :item_id)) AND
				(orders.newpost_id = '' OR orders.newpost_id IS NULL) AND
				owner.newpost_id IS NOT NULL AND
				owner.newpost_psw IS NOT NULL AND
				orders.whs_ref IS NOT NULL" :
		"   FROM orders LEFT OUTER JOIN warehouses ON warehouses.ref = orders.whs_ref LEFT OUTER JOIN item ON item.uuid = orders.item_id AND item.owner_id = orders.owner_id, users as owner, operators_for_sellers
			WHERE
				orders.item = item.name AND
				item.owner_id = orders.owner_id AND
				(orders.status_step2 = 0 OR orders.status_step2 > 50) AND
				(orders.status_step3 = 0 OR orders.status_step3 > 50) AND
				orders.owner_id = operators_for_sellers.seller_id AND
				operators_for_sellers.operator_id = :user_id AND
				(orders.status_step1 = 110 OR orders.status_step1 = 112) AND
				(orders.newpost_id = '' OR orders.newpost_id IS NULL) AND
				owner.id = orders.owner_id AND
				owner.newpost_id IS NOT NULL AND
				owner.newpost_psw IS NOT NULL AND
				orders.whs_ref IS NOT NULL AND
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
	
?>

<!doctype html>
<html lang="ru">
	<h2>Заказы готовые к печати деклараций</h2>
	<table border=1 cellpadding=4 style='border: 1px solid black; border-collapse: collapse;'>
	<tr><th>№</th><th>Предпр-ль</th><th>Город, область</th><th>Телефон</th><th>ФИО</th><th>Адрес, отделение НП</th><th>Стоимость</th><th>Описание</th><th>Параметры</th></tr>
	<?php 
		$tlist = "";
		foreach ($orders as $idx=>$ord) {
		echo "<tr><td>".($idx+1)."</td><td>".$ord['username']."</td>
					<td>".($ord['city_area'] ? $ord['city_area'] : $ord['old_city_area'])."</td>
					<td>".$ord['phone']."</td>
					<td>".$ord['fio']."</td>
					<td>".$ord['address']."</td>
					<td>".(($ord['status_step1'] == 112) ? 'предоплата' : $ord['item_price'])."</td>
					<td>".$ord['item']." (".$ord['item_count']." шт)</td>
					<td>".$ord['item_params']."</td>
				</tr>";
		}		
	 ?>
	</table>
</body>
</html>