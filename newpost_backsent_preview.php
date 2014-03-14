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
				item.owner_id = orders.owner_id AND
				(orders.status_step1 = 0 OR orders.status_step1 > 50) AND
				(orders.status_step3 = 0 OR orders.status_step3 > 50) AND
				orders.owner_id = owner.id AND
				(:seller_id = '0' OR owner.id = :seller_id) AND
				owner.id IN (SELECT subseller_id FROM sellers_for_sellers WHERE sellers_for_sellers.seller_id = :user_id) AND
				orders.status_step2 IN (220, 225) AND
				(:item_id IS NULL OR :item_id = '0' OR orders.item IN (SELECT name FROM item WHERE uuid = :item_id))" :
		"   FROM orders LEFT OUTER JOIN warehouses ON warehouses.ref = orders.whs_ref LEFT OUTER JOIN item ON item.uuid = orders.item_id AND item.owner_id = orders.owner_id, users as owner, operators_for_sellers
			WHERE
				item.owner_id = orders.owner_id AND
				(orders.status_step1 = 0 OR orders.status_step1 > 50) AND
				(orders.status_step3 = 0 OR orders.status_step3 > 50) AND
				orders.owner_id = operators_for_sellers.seller_id AND
				operators_for_sellers.operator_id = :user_id AND
				orders.status_step2 IN (220, 225) AND				
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
	
?>

<!doctype html>
<html lang="ru">
	<h2>Возвраты (заявления будут оформлены в 15-00)</h2>
	<table border=1 cellpadding=4 style='border: 1px solid black; border-collapse: collapse;'>
	<tr><th>№</th><th>Предпр-ль</th><th>Декларация НП</th><th>Город, область</th><th>Телефон</th><th>ФИО</th><th>Адрес, отделение НП</th><th>Стоимость</th><th>Описание</th><th>Параметры</th></tr>
	<?php 
		$tlist = "";
		foreach ($orders as $idx=>$ord) {
			if ($ord['fio_ukr'] and $ord['pass_s'] and $ord['pass_n'] and $ord['pass_issued'] and $ord['pass_i_date'] and $ord['adr']) {
				$passdt = '';
			} else {
				$passdt = '<br/>НЕТ ПАСПОРТНЫХ ДАННЫХ!';
			}
			echo "<tr><td>".($idx+1)."</td><td>".$ord['username']." <span style='color:red;'>".$passdt."</span></td>
					<td>".$ord['newpost_id']."</td>
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