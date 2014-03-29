<?php
	require("config.php"); 	
	
	if (empty($_SESSION['user']) || $_SESSION['user']['active'] == 0) {
		header("Location: index.php"); 
		die("Перенаправление: index.php"); 
	}
	
	$page_start = 500*($_GET['page'] ? $_GET['page']-1 : 0);
	
		$query = " 
					SELECT 
						COUNT(distinct orders.id) as cnt "					
				.($_SESSION['user']['group_id'] == 2 ? 
				"   FROM users as owner, orders 
						LEFT JOIN (select order_id, max(date) as max_time from orders_audit group by order_id) as max_times ON orders.id = max_times.order_id  
					WHERE".($_GET['archive'] ? "((orders.status_step1 > 0 AND orders.status_step1 <= 50) OR
							 (orders.status_step2 > 0 AND orders.status_step2 < 50) OR
							 (orders.status_step3 > 0 AND orders.status_step3 < 50)) AND" :
							"(orders.status_step1 = 0 OR orders.status_step1 > 50) AND
							 (orders.status_step2 = 0 OR orders.status_step2 > 50) AND
							 (orders.status_step3 = 0 OR orders.status_step3 > 50) AND")."
						(:status_id IS NULL OR :status_id = '0' OR orders.status_step1 = :status_id OR orders.status_step2 = :status_id OR orders.status_step3 = :status_id) AND
						(:order_date IS NULL OR :order_date = '' OR DATE(orders.created_at) >= :order_date) AND
						(:order_date_end IS NULL OR :order_date_end = '' OR DATE(orders.created_at) <= :order_date_end) AND
						(:item_id IS NULL OR :item_id = '0' OR orders.item IN (SELECT name FROM item WHERE uuid = :item_id)) AND
						owner.id = orders.owner_id AND
						(:seller_id = '0' OR owner.id = :seller_id) AND
						owner.id IN (SELECT subseller_id FROM sellers_for_sellers WHERE sellers_for_sellers.seller_id = :user_id)" . (
						($_GET['count_days'] and $_GET['count_days'] > 0) ? " AND max_times.max_time <= DATE_SUB(NOW(), INTERVAL " . $_GET['count_days'] . " DAY)" : "") 
						:
				"   FROM users as owner, operators_for_sellers, orders  
						LEFT JOIN (select order_id, max(date) as max_time from orders_audit group by order_id) as max_times ON orders.id = max_times.order_id  
					WHERE".($_GET['archive'] ? "((orders.status_step1 > 0 AND orders.status_step1 <= 50) OR
							 (orders.status_step2 > 0 AND orders.status_step2 < 50) OR
							 (orders.status_step3 > 0 AND orders.status_step3 < 50)) AND" :
							"(orders.status_step1 = 0 OR orders.status_step1 > 50) AND
							 (orders.status_step2 = 0 OR orders.status_step2 > 50) AND
							 (orders.status_step3 = 0 OR orders.status_step3 > 50) AND")."						
						(:status_id IS NULL OR :status_id = '0' OR orders.status_step1 = :status_id OR orders.status_step2 = :status_id OR orders.status_step3 = :status_id) AND
						(:order_date IS NULL OR :order_date = '' OR DATE(orders.created_at) >= :order_date) AND
						(:order_date_end IS NULL OR :order_date_end = '' OR DATE(orders.created_at) <= :order_date_end) AND
						owner.id = orders.owner_id AND				
						orders.owner_id = operators_for_sellers.seller_id AND
						operators_for_sellers.operator_id = :user_id AND
						(:seller_id IS NULL OR :seller_id = '0' OR owner.id = :seller_id) AND
						(:item_id IS NULL OR :item_id = '0' OR orders.item IN (SELECT name FROM item WHERE uuid = :item_id))".(
						($_GET['oper'] and $_GET['oper']=='2') ? " AND oper_id IS NULL" : (($_GET['oper'] and $_GET['oper']=='1') ? " AND oper_id = :user_id" : "")) . (
						($_GET['count_days'] and $_GET['count_days'] > 0) ? " AND max_times.max_time <= DATE_SUB(NOW(), INTERVAL " . $_GET['count_days'] . " DAY)" : "")
				);		
			$query_params =  
				array( 
					':user_id' => $_SESSION['user']['id'],
					':seller_id' => (($_GET['seller_id'] or $_GET['seller_id'] == '0') ? $_GET['seller_id'] : $_SESSION['user']['id']),
					':item_id' => $_GET['item_id'],
					':status_id' => $_GET['status_id'],
					':order_date' => $_GET['order_date'],
					':order_date_end' => $_GET['order_date_end'] ? $_GET['order_date_end'] : $_GET['order_date'],
				); 
		 
		try{ 
			$stmt = $db->prepare($query); 
			$result = $stmt->execute($query_params); 
		} 
		catch(PDOException $ex){ die("Невозможно выполнить запрос: " . $ex->getMessage()); } 
		
		$orders_full_count = $stmt->fetch(); 
		
	
		$page_start = 500*($_GET['page'] ? $_GET['page']-1 : 0);
	
		$query = " 
				SELECT 
					COALESCE((orders.alert_at <= NOW()),0) as 'alert',
					orders.id as id,
					orders.created_at as created_at,
					orders.updated_at as updated_at,
					orders.alert_at as alert_at,
					COALESCE(item.name, orders.item) as item,
					orders.item_price as item_price,
					orders.item_count as item_count,
					orders.item_params as item_params,
					orders.city_area as city_area,
					orders.address as address,
					orders.referrer as referrer,
					orders.request as request,
					orders.ip_src as ip_src,
					warehouses.cityRu as np_city_area,
					warehouses.addressRu as np_address,
					orders.fio as fio,
					orders.phone as phone,
					orders.comment as comment,
					orders.comment2 as comment2,
					orders.email as email,
					orders.whs_ref as whs_ref,
					orders.newpost_id as newpost_id,
					orders.newpost_backorder as newpost_backorder,
					orders.newpost_answer as newpost_answer,
					orders.newpost_backorder_answer as newpost_backorder_answer,
					orders.newpost_last_update as newpost_last_update,
					orders.newpost_last_backorder_update as newpost_last_backorder_update,
					orders.status_step1 as status_step1,
					orders.status_step2 as status_step2,
					orders.status_step3 as status_step3,
					statuses1.name as status_step1_name,
					statuses1.act as status_step1_act,
					statuses1.domClass as status_step1_domClass,
					COALESCE(statuses1.row_domClass, statuses2.row_domClass, statuses3.row_domClass) as status_rowClass,
					statuses2.name as status_step2_name,
					statuses2.act as status_step2_act,
					statuses2.domClass as status_step2_domClass,
					statuses3.name as status_step3_name,
					statuses3.act as status_step3_act,
					statuses3.domClass as status_step3_domClass,
					COALESCE(statuses3.priority, statuses2.priority, statuses1.priority) as status_priority,
					owner.email as owner_email,
					owner.username as owner_username,
					owner.phone as owner_phone,
					owner.newpost_api as newpost_api,
					oper.username as oper_username,
					IF(editor.id = :user_id,1,0) as editor_ord,
					editor.username as editor_username,
					TIMESTAMPDIFF(MINUTE, orders.inwork_time, NOW()) as editor_time"					
			.($_SESSION['user']['group_id'] == 2 ? 
			"   FROM statuses as statuses1, statuses as statuses2, statuses as statuses3, users as owner, orders LEFT OUTER JOIN warehouses ON orders.whs_ref = warehouses.ref LEFT OUTER JOIN item ON item.uuid = orders.item_id AND item.owner_id = orders.owner_id LEFT OUTER JOIN users as oper ON oper_id = oper.id LEFT OUTER JOIN users as editor ON editor.id = orders.inwork_userid 
					LEFT JOIN (select order_id, max(date) as max_time from orders_audit group by order_id) as max_times ON orders.id = max_times.order_id  
				WHERE".($_GET['archive'] ? "((orders.status_step1 > 0 AND orders.status_step1 <= 50) OR
						 (orders.status_step2 > 0 AND orders.status_step2 < 50) OR
						 (orders.status_step3 > 0 AND orders.status_step3 < 50)) AND" :
						"(orders.status_step1 = 0 OR orders.status_step1 > 50) AND
						 (orders.status_step2 = 0 OR orders.status_step2 > 50) AND
						 (orders.status_step3 = 0 OR orders.status_step3 > 50) AND")."
					orders.status_step1 = statuses1.id AND
					orders.status_step2 = statuses2.id AND
					orders.status_step3 = statuses3.id AND
					(:status_id IS NULL OR :status_id = '0' OR orders.status_step1 = :status_id OR orders.status_step2 = :status_id OR orders.status_step3 = :status_id) AND
					(:order_date IS NULL OR :order_date = '' OR DATE(orders.created_at) >= :order_date) AND
					(:order_date_end IS NULL OR :order_date_end = '' OR DATE(orders.created_at) <= :order_date_end) AND
					(:item_id IS NULL OR :item_id = '0' OR orders.item_id = :item_id OR orders.item IN (SELECT name FROM item WHERE uuid = :item_id)) AND
					owner.id = orders.owner_id AND
					(:seller_id = '0' OR owner.id = :seller_id) AND
					owner.id IN (SELECT subseller_id FROM sellers_for_sellers WHERE sellers_for_sellers.seller_id = :user_id)" . (
					($_GET['count_days'] and $_GET['count_days'] > 0) ? " AND max_times.max_time <= DATE_SUB(NOW(), INTERVAL " . $_GET['count_days'] . " DAY)" : "") . " 
					GROUP BY orders.id ORDER BY " :
			"   FROM statuses as statuses1,statuses as statuses2, statuses as statuses3, users as owner, operators_for_sellers, orders LEFT OUTER JOIN warehouses ON orders.whs_ref = warehouses.ref LEFT OUTER JOIN item ON item.uuid = orders.item_id AND item.owner_id = orders.owner_id LEFT OUTER JOIN users as oper ON oper_id = oper.id LEFT OUTER JOIN users as editor ON editor.id = orders.inwork_userid 
					LEFT JOIN (select order_id, max(date) as max_time from orders_audit group by order_id) as max_times ON orders.id = max_times.order_id  
				WHERE".($_GET['archive'] ? "((orders.status_step1 > 0 AND orders.status_step1 <= 50) OR
						 (orders.status_step2 > 0 AND orders.status_step2 < 50) OR
						 (orders.status_step3 > 0 AND orders.status_step3 < 50)) AND" :
						"(orders.status_step1 = 0 OR orders.status_step1 > 50) AND
						 (orders.status_step2 = 0 OR orders.status_step2 > 50) AND
						 (orders.status_step3 = 0 OR orders.status_step3 > 50) AND")."
					orders.status_step1 = statuses1.id AND
					orders.status_step2 = statuses2.id AND
					orders.status_step3 = statuses3.id AND
					(:status_id IS NULL OR :status_id = '0' OR orders.status_step1 = :status_id OR orders.status_step2 = :status_id OR orders.status_step3 = :status_id) AND
					(:order_date IS NULL OR :order_date = '' OR DATE(orders.created_at) >= :order_date) AND
					(:order_date_end IS NULL OR :order_date_end = '' OR DATE(orders.created_at) <= :order_date_end) AND
					owner.id = orders.owner_id AND				
					orders.owner_id = operators_for_sellers.seller_id AND
					operators_for_sellers.operator_id = :user_id AND
					(:seller_id IS NULL OR :seller_id = '0' OR owner.id = :seller_id) AND
					(:item_id IS NULL OR :item_id = '0' OR orders.item_id = :item_id OR orders.item IN (SELECT name FROM item WHERE uuid = :item_id))".(
					($_GET['oper'] and $_GET['oper']=='2') ? " AND oper_id IS NULL" : (($_GET['oper'] and $_GET['oper']=='1') ? " AND oper_id = :user_id" : "")) . (
						($_GET['count_days'] and $_GET['count_days'] > 0) ? " AND max_times.max_time <= DATE_SUB(NOW(), INTERVAL " . $_GET['count_days'] . " DAY)" : "") .
					"GROUP BY orders.id ORDER BY editor_ord DESC,"					
			)." alert DESC, status_priority DESC, created_at ".($_GET['archive'] ? 'DESC':'ASC')." LIMIT ".$page_start.",500";	

		$query_params =  
			array( 
				':user_id' => $_SESSION['user']['id'],
				':seller_id' => (($_GET['seller_id'] or $_GET['seller_id'] == '0') ? $_GET['seller_id'] : $_SESSION['user']['id']),
				':item_id' => $_GET['item_id'],
				':status_id' => $_GET['status_id'],
				':order_date' => $_GET['order_date'],
				':order_date_end' => $_GET['order_date_end'] ? $_GET['order_date_end'] : $_GET['order_date']
			); 
	 
	try{ 
		$stmt = $db->prepare($query); 
		$result = $stmt->execute($query_params); 
	} 
	catch(PDOException $ex){ die("Невозможно выполнить запрос: " . $ex->getMessage()); } 
	
	$orders = $stmt->fetchAll();
		
	if ($_SESSION['user']['group_id'] == 2) {
		$select_sellers = array();
		$query = " 
				SELECT 
					CONCAT(REPEAT(' -',sellers_for_sellers.depth),users.username) as username,
					users.id as id
				FROM sellers_for_sellers, users
				WHERE
					users.id = sellers_for_sellers.subseller_id AND
					sellers_for_sellers.seller_id = :user_id
			";		
		$query_params = array( 
			':user_id' => $_SESSION['user']['id']
		); 
			 
		try{ 
			$stmt = $db->prepare($query); 
			$result = $stmt->execute($query_params); 
		} 
		catch(PDOException $ex){ die("Невозможно выполнить запрос: " . $ex->getMessage()); } 
		
		$select_sellers = array_merge($select_sellers,$stmt->fetchAll());			
	} else {
		$query = " 
					SELECT 
						owner.username as username,
						owner.id as id
					FROM users as owner, operators_for_sellers
					WHERE
						owner.id = operators_for_sellers.seller_id AND
						operators_for_sellers.operator_id = :user_id
				";		
			$query_params = array( 
				':user_id' => $_SESSION['user']['id']
			); 
				 
		try{ 
			$stmt = $db->prepare($query); 
			$result = $stmt->execute($query_params); 
		} 
		catch(PDOException $ex){ die("Невозможно выполнить запрос: " . $ex->getMessage()); } 
		
		$select_sellers = $stmt->fetchAll();			
	}
	
	
			$query = " 
					SELECT 
						item.uuid as uuid,
						item.name as name
					FROM users as owner, item
				".(($_SESSION['user']['group_id'] != 2) ? "
					, operators_for_sellers WHERE
						owner.id = operators_for_sellers.seller_id AND
						operators_for_sellers.operator_id = :user_id AND
						(:owner_id = '0' OR owner.id = :owner_id) AND
						item.owner_id = owner.id
				" : 
				" WHERE
						item.owner_id = owner.id AND
						(:seller_id = '0' OR owner.id = :seller_id) AND
						owner.id IN (SELECT subseller_id FROM sellers_for_sellers WHERE sellers_for_sellers.seller_id = :user_id)
				");		
			if ($_SESSION['user']['group_id'] == 2) {
			$query_params = array( 
				':seller_id' => $_GET['seller_id'] || (!$_GET['seller_id'] && $_GET['seller_id']=='0') ? $_GET['seller_id'] : $_SESSION['user']['id'],
				':user_id' => $_SESSION['user']['id']
			); } else { 
			$query_params = array( 
				':owner_id' => $_GET['seller_id'],
				':user_id' => $_SESSION['user']['id']
			); }
			
				 
		try{ 
			$stmt = $db->prepare($query); 
			$result = $stmt->execute($query_params); 
		} 
		catch(PDOException $ex){ die("Невозможно выполнить запрос: " . $ex->getMessage()); } 
		
		$select_items = $stmt->fetchAll();
	
	
	$query = " 
				SELECT 
					COUNT(*) as cnt"	
			.($_SESSION['user']['group_id'] == 2 ? 
			"   FROM orders, users as owner
				WHERE (:status_id IS NULL OR :status_id = '0' OR :status_id = 100) AND 
					orders.status_step1 = 100 AND
					(:order_date IS NULL OR :order_date = '' OR DATE(orders.created_at) >= :order_date) AND
					(:order_date_end IS NULL OR :order_date_end = '' OR DATE(orders.created_at) <= :order_date_end) AND
					(:item_id IS NULL OR :item_id = '0' OR orders.item IN (SELECT name FROM item WHERE uuid = :item_id)) AND
					owner.id = orders.owner_id AND
					(:seller_id = '0' OR owner.id = :seller_id) AND
					owner.id IN (SELECT subseller_id FROM sellers_for_sellers WHERE sellers_for_sellers.seller_id = :user_id)
					" :
			"   FROM orders, users as owner, operators_for_sellers
				WHERE (:status_id IS NULL OR :status_id = '0' OR :status_id = 100) AND 
					orders.status_step1 = 100 AND
					(:order_date IS NULL OR :order_date = '' OR DATE(orders.created_at) >= :order_date) AND
					(:order_date_end IS NULL OR :order_date_end = '' OR DATE(orders.created_at) <= :order_date_end) AND
					owner.id = orders.owner_id AND				
					orders.owner_id = operators_for_sellers.seller_id AND
					operators_for_sellers.operator_id = :user_id AND
					(:seller_id IS NULL OR :seller_id = '0' OR owner.id = :seller_id) AND
					(:item_id IS NULL OR :item_id = '0' OR orders.item IN (SELECT name FROM item WHERE uuid = :item_id))
			");
					
		$query_params = array( 
				':user_id' => $_SESSION['user']['id'],
				':seller_id' => (($_GET['seller_id'] or $_GET['seller_id'] == '0') ? $_GET['seller_id'] : $_SESSION['user']['id']),
				':item_id' => $_GET['item_id'],
				':status_id' => $_GET['status_id'],
				':order_date' => $_GET['order_date'],
				':order_date_end' => $_GET['order_date_end'] ? $_GET['order_date_end'] : $_GET['order_date']
			);	
	 
	try{ 
		$stmt = $db->prepare($query); 
		$result = $stmt->execute($query_params); 
	} 
	catch(PDOException $ex){ die("Невозможно выполнить запрос: " . $ex->getMessage()); } 
	
	$orders_new_count = $stmt->fetch();
	
	$query = " 
				SELECT orders.id, orders.status_step1, orders.status_step2, orders.status_step3 "					
			.($_SESSION['user']['group_id'] == 2 ? 
			"   FROM users as owner, orders LEFT OUTER JOIN item ON item.uuid = orders.item_id AND item.owner_id = orders.owner_id LEFT OUTER JOIN users as oper ON oper_id = oper.id LEFT OUTER JOIN users as editor ON editor.id = orders.inwork_userid
				WHERE".($_GET['archive'] ? "((orders.status_step1 > 0 AND orders.status_step1 <= 50) OR
						 (orders.status_step2 > 0 AND orders.status_step2 < 50) OR
						 (orders.status_step3 > 0 AND orders.status_step3 < 50)) AND" :
						"(orders.status_step1 = 0 OR orders.status_step1 > 50) AND
						 (orders.status_step2 = 0 OR orders.status_step2 > 50) AND
						 (orders.status_step3 = 0 OR orders.status_step3 > 50) AND")."										
					(:order_date IS NULL OR :order_date = '' OR DATE(orders.created_at) >= :order_date) AND
					(:order_date_end IS NULL OR :order_date_end = '' OR DATE(orders.created_at) <= :order_date_end) AND
					(:item_id IS NULL OR :item_id = '0' OR orders.item IN (SELECT name FROM item WHERE uuid = :item_id)) AND
					owner.id = orders.owner_id AND
					(:seller_id = '0' OR owner.id = :seller_id) AND
					owner.id IN (SELECT subseller_id FROM sellers_for_sellers WHERE sellers_for_sellers.seller_id = :user_id)					
					" :
			"   FROM users as owner, operators_for_sellers, orders LEFT OUTER JOIN item ON item.uuid = orders.item_id AND item.owner_id = orders.owner_id LEFT OUTER JOIN users as oper ON oper_id = oper.id LEFT OUTER JOIN users as editor ON editor.id = orders.inwork_userid
				WHERE".($_GET['archive'] ? "((orders.status_step1 > 0 AND orders.status_step1 <= 50) OR
						 (orders.status_step2 > 0 AND orders.status_step2 < 50) OR
						 (orders.status_step3 > 0 AND orders.status_step3 < 50)) AND" :
						"(orders.status_step1 = 0 OR orders.status_step1 > 50) AND
						 (orders.status_step2 = 0 OR orders.status_step2 > 50) AND
						 (orders.status_step3 = 0 OR orders.status_step3 > 50) AND")."					
					(:order_date IS NULL OR :order_date = '' OR DATE(orders.created_at) >= :order_date) AND
					(:order_date_end IS NULL OR :order_date_end = '' OR DATE(orders.created_at) <= :order_date_end) AND
					owner.id = orders.owner_id AND				
					orders.owner_id = operators_for_sellers.seller_id AND
					operators_for_sellers.operator_id = :user_id AND
					(:seller_id IS NULL OR :seller_id = '0' OR owner.id = :seller_id) AND
					(:item_id IS NULL OR :item_id = '0' OR orders.item IN (SELECT name FROM item WHERE uuid = :item_id))".(
					($_GET['oper'] and $_GET['oper']=='2') ? " AND oper_id IS NULL" : (($_GET['oper'] and $_GET['oper']=='1') ? " AND oper_id = :user_id" : ""))					
			);		
		$query_params =  
			array( 
				':user_id' => $_SESSION['user']['id'],
				':seller_id' => (($_GET['seller_id'] or $_GET['seller_id'] == '0') ? $_GET['seller_id'] : $_SESSION['user']['id']),
				':item_id' => $_GET['item_id'],
				':order_date' => $_GET['order_date'],
				':order_date_end' => $_GET['order_date_end'] ? $_GET['order_date_end'] : $_GET['order_date']
			); 
	 
	try{ 
		$stmt = $db->prepare($query); 
		$result = $stmt->execute($query_params); 
	} 
	catch(PDOException $ex){ die("Невозможно выполнить запрос: " . $ex->getMessage()); } 
	
	$orders_status_cnt = $stmt->fetchAll();
	
	$status_cnts = array();
	foreach ($orders_status_cnt as $t_ord) {
		if ($t_ord['status_step1'] and $status_cnts[$t_ord['status_step1']]) {
			$status_cnts[$t_ord['status_step1']] += 1;
		} else if ($t_ord['status_step1']) {
			$status_cnts[$t_ord['status_step1']] = 1;
		}
		if ($t_ord['status_step2'] and $status_cnts[$t_ord['status_step2']]) {
			$status_cnts[$t_ord['status_step2']] += 1;
		} else if ($t_ord['status_step2']) {
			$status_cnts[$t_ord['status_step2']] = 1;
		}
		if ($t_ord['status_step3'] and $status_cnts[$t_ord['status_step3']]) {
			$status_cnts[$t_ord['status_step3']] += 1;
		} else if ($t_ord['status_step3']) {
			$status_cnts[$t_ord['status_step3']] = 1;
		}
	}
	
	$query = " 
				SELECT 
					*
				FROM
					statuses
				WHERE 
					id = 10 OR id = 40 OR (id >= 100 AND id <= 199)
				ORDER BY automatic ASC, id ASC
			"; 
	try{ 
		$stmt = $db->prepare($query); 
		$result = $stmt->execute($query_params); 
	} 
	catch(PDOException $ex){ die("Невозможно выполнить запрос: " . $ex->getMessage()); } 
	
	$statuses_step1 = $stmt->fetchAll();
	
	$query = " 
				SELECT 
					*
				FROM
					statuses
				WHERE 
					id = 0 OR id = 10 OR (id >= 200 AND id <= 299)
				ORDER BY automatic ASC, id ASC	
			"; 
	try{ 
		$stmt = $db->prepare($query); 
		$result = $stmt->execute($query_params); 
	} 
	catch(PDOException $ex){ die("Невозможно выполнить запрос: " . $ex->getMessage()); } 
	
	$statuses_step2 = $stmt->fetchAll();
	
	$query = " 
				SELECT 
					*
				FROM
					statuses
				WHERE 
					id = 0 OR (id >= 300 AND id <= 399)
				ORDER BY automatic ASC, id ASC	
			"; 
	try{ 
		$stmt = $db->prepare($query); 
		$result = $stmt->execute($query_params); 
	} 
	catch(PDOException $ex){ die("Невозможно выполнить запрос: " . $ex->getMessage()); } 
	
	$statuses_step3 = $stmt->fetchAll();		
	
	$query = " 
				SELECT 
					*
				FROM
					statuses
				WHERE 
					id > 0 AND id <= 50
				ORDER BY automatic ASC, id ASC	
			"; 
	try{ 
		$stmt = $db->prepare($query); 
		$result = $stmt->execute($query_params); 
	} 
	catch(PDOException $ex){ die("Невозможно выполнить запрос: " . $ex->getMessage()); } 
	
	$statuses_arch = $stmt->fetchAll();
	
	$loc = '';
	if ($_GET['archive']) {$loc .= '&archive='.$_GET['archive'];}
	if ($_GET['oper']) {$loc .= '&oper='.$_GET['oper'];}
	if ($_GET['seller_id'] or $_GET['seller_id'] == '0') {$loc .= '&seller_id='.$_GET['seller_id'];}
	if ($_GET['item_id']or $_GET['item_id'] == '0') {$loc .= ($loc!='' ? '&item_id=' : '?item_id=').$_GET['item_id'];}
	if ($_GET['status_id']) {$loc .= '&status_id='.$_GET['status_id'];}
	if ($_GET['order_date']) {$loc .= '&order_date='.$_GET['order_date'];}	
	if ($_GET['order_date_end']) {$loc .= '&order_date_end='.$_GET['order_date_end'];}	
	
	
	$myXML 	 = "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n";
	$myXML 	.= "<request>";
	$myXML 	.= "<operation>GETBALANCE</operation>";
	$myXML 	.= "</request>";

	$ch = curl_init();
	curl_setopt($ch, CURLOPT_USERPWD , $smsfly_user.':'.$smsfly_password);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_URL, 'http://sms-fly.com/api/api.php');
	curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: text/xml", "Accept: text/xml"));
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $myXML);
	$response = curl_exec($ch);
	curl_close($ch);
	
	try {
		$balance = new SimpleXMLElement($response);		
		$balance_msg = $balance->balance;
	} catch (Exception $e) {
		$balance_msg = null;
	}
	
?>

<!doctype html>
<html lang="ru">
<?php include 'header.php' ?>
<body>
<?php include 'top_menu.php' ?>
<div style="display: none;">
</div>
<div class="container">
	<?php if ($balance_msg != NULL and $balance_msg < 20) { echo '<div class="btn-danger">Внимание! На счету SMS-fly осталось: '.$balance_msg.' грн</div>';};?>
	<?php
		$q_np = 'SELECT TIMESTAMPDIFF(MINUTE , MAX( newpost_last_update ),  NOW() ) as tdf FROM  `orders`';
		try{ 
			$stmt = $db->prepare($q_np); 
			$result = $stmt->execute(); 
		} 
		catch(PDOException $ex){ die("Невозможно выполнить запрос: " . $ex->getMessage()); } 
		
		$tdf = $stmt->fetch();
		$tdf = intval($tdf['tdf']);
		
		if ($tdf > 23) {
			echo '<div class="btn-danger">Поломка - сообщите администратору! Данные Новой почты не обновлялись: '.$tdf.' минут</div>';
		}
	?>
	<span id='wrk_btns'>
	<?php if (!$_GET['archive']) {?>
	<h3>Заказы<?php echo $orders_new_count ? ' ( новых: '.$orders_new_count['cnt'].', показано '.count($orders).' из '.$orders_full_count['cnt'].')' : '';?></h3>  
	<?php } else {?>
	<h3>Архив заказов<?php echo ' (показано '.count($orders).' из '.$orders_full_count['cnt'].')';?></h3>
	<?php } ?>
	<?php if ($orders_full_count['cnt'] > count($orders)) {
			echo '<h3>Страницы:';
				for ($pg = 1; 500*($pg-1) <= $orders_full_count['cnt']; $pg++) {
					if ($_GET['page'] == $pg or (!$_GET['page'] and $pg==1)) {
						echo "&nbsp;&nbsp;<b>".$pg."</b>";
					} else {
						echo "&nbsp;&nbsp;<a href='orders_list.php?page=".$pg.$loc."'>".$pg."</a>";
					}
				}
			echo '</h3>';
	} 
		if ($_GET['page']) {$loc .= '&page='.$_GET['page'];}
		if (!$_GET['archive']) { ?>
		<a href="newpost_list.php?r=1<?php if ($_GET['oper']) {echo '&oper='.$_GET['oper'];}; if ($_GET['seller_id'] or $_GET['seller_id']=='0') {echo '&seller_id='.$_GET['seller_id']; if ($_GET['item_id']) {echo '&item_id='.$_GET['item_id'];}} ?>" target="_new" class="btn btn-success">Передать список подтвержденных заказов в Н.П</a>
		<a href="newpost_preview.php?r=1<?php if ($_GET['oper']) {echo '&oper='.$_GET['oper'];}; if ($_GET['seller_id'] or $_GET['seller_id']=='0') {echo '&seller_id='.$_GET['seller_id']; if ($_GET['item_id']) {echo '&item_id='.$_GET['item_id'];}} ?>" target="_new" class="btn btn-warning">Просмотр списка на печать</a>
		<a href="newpost_print.php?r=1<?php if ($_GET['oper']) {echo '&oper='.$_GET['oper'];}; if ($_GET['seller_id'] or $_GET['seller_id']=='0') {echo '&seller_id='.$_GET['seller_id']; if ($_GET['item_id']) {echo '&item_id='.$_GET['item_id'];}} ?>" target="_new" class="btn btn-default">Распечатать декларации</a>
		<a href="newpost_print_reset.php?r=1<?php if ($_GET['oper']) {echo '&oper='.$_GET['oper'];}; if ($_GET['seller_id'] or $_GET['seller_id']=='0') {echo '&seller_id='.$_GET['seller_id']; if ($_GET['item_id']) {echo '&item_id='.$_GET['item_id'];}} ?>" class="btn btn-danger">Обнулить неотправленные декларации</a>
		<?php if ($_SESSION['user']['group_id'] == 2) { ?>
			<a href="money_list.php?r=1<?php if ($_GET['seller_id'] or $_GET['seller_id']=='0') {echo '&seller_id='.$_GET['seller_id']; if ($_GET['item_id']) {echo '&item_id='.$_GET['item_id'];}} else {echo '&seller_id='.$_SESSION['user']['id'];} ?>" target="_new" class="btn btn-success">Получение денег</a>
		<?php } ?>
		<a href="newpost_backsent_preview.php?r=1<?php if ($_GET['oper']) {echo '&oper='.$_GET['oper'];}; if ($_GET['seller_id'] or $_GET['seller_id']=='0') {echo '&seller_id='.$_GET['seller_id']; if ($_GET['item_id']) {echo '&item_id='.$_GET['item_id'];}} ?>" target="_new" class="btn btn-warning">Ближайшие возвраты</a>
	<?php } ?>	
	</span>
	<h3 id='inwork_fin' style='display: none;'>Завершите обработку заказа!</h3>
	<table class='table table-hover table-bordered table-fixed-header'>
	<thead class="header"><th>Предпр-ль<br/><small>Оператор</small></th><th>Создан</th><th>Следующее действие</th><th>Реферер, комментарии</th><th>Шаг 1 - оформление</th><th>Шаг 2 - доставка</th><th>Шаг 3 - оплата</th><th>ФИО</th><th>Адрес доставки</th><th>Товар, параметры</th><th>Сумма</th><th>Телефон, E-mail</th><th>Доставка груза</th><th>Деньги/возврат</th></thead>
	<?php foreach ($orders as $ord){ 
			$np_answer = json_decode($ord['newpost_answer'], true); 
			$npb_answer = json_decode($ord['newpost_backorder_answer'], true);
			$in_work = '';
	?>	
				<tr id='order_<?php echo $ord['id']?>' class='<?php echo $ord['status_rowClass'];?>'>
					<?php echo "<td><small>".$ord['owner_username']."<br/><br/><small>".$ord['oper_username']."</small></small></td>"; 
					 $crd = new DateTime($ord['created_at']);
					 $crd = $crd->format('d-m-y H:i:s');
					$ad = '';	
					 if ($ord['alert_at']) {
						 $ad = new DateTime($ord['alert_at']);
						 $ad = $ad->format('d-m-y H:i:s');
					 }
					 
					 $ord_comments_str = '';
					 
						$query = "SELECT date, comment FROM orders_audit WHERE comment IS NOT NULL AND comment <> '' AND order_id = :order_id ORDER BY date ASC"; 
							$query_params = array( 
								':order_id' => $ord['id']
						); 
							 
						try{ 
							$stmt = $db->prepare($query); 
							$result = $stmt->execute($query_params); 
						} 
						catch(PDOException $ex){ die("Невозможно выполнить запрос: " . $ex->getMessage()); } 
						
						$ord_comments = $stmt->fetchAll();
						
						foreach ($ord_comments as $t_comment) {
							$tcd = new DateTime($t_comment['date']);
							$tcd = $tcd->format('d.m H:i');
							$ord_comments_str = $ord_comments_str.'<br/><small>'.$tcd.":</small> ".$t_comment['comment'];
						}
					?>
					<td><?php echo str_replace("-","&#8209;",str_replace(" ","<br/>",$crd)); ?><br/>
						<a href="order_history.php?id=<?php echo $ord['id']?>" data-remote=true data-toggle="modal" data-target="#myModalHistory<?php echo $ord['id'] ?>" class="">история</a><br/><small>№&nbsp;<?php echo $ord['id'];?></small>
							<div class="modal fade" id="myModalHistory<?php echo $ord['id'] ?>">
								<div class="modal-dialog">
									<div class="modal-content">
										<!-- loaded by ajax -->
									</div>
								</div>
							</div>
					</td>
					<td class='<?php if ($ord['alert'] == '1') {echo 'bold_red';} ?>'><?php echo str_replace(" ","<br/>",$ad) ?></td>
					<td><small><div style="width:200px; word-break: break-all; word-wrap: break-word;"><small><?php echo $ord['referrer'] ? $ord['referrer'].'<br/>' : ''; echo $ord['request'] ? $ord['request'].'<br/>' : ''; echo $ord['ip_src'] ? $ord['ip_src'].'<br/>' : '';?></small></span></div><div id='ds<?php echo $ord['id'];?>' style="width:200px; overflow: auto; max-height: 105px"><?php echo $ord_comments_str; ?></div></small></td><script type='text/javascript'>$("#ds<?php echo $ord['id'];?>").scrollTop($("#ds<?php echo $ord['id'];?>")[0].scrollHeight);</script>					
					<td>
						<?php 							
							if ($ord['editor_username']) {
								echo '<p><span class="bold_red">Работает:</span><br/><small>'.$ord['editor_username'].' '.$ord['editor_time'].'&nbsp;мин</small></p>';
								$in_work = ' btn-in_work';
							}
						?>
					<a href="order_step1_form.php?id=<?php echo $ord['id'].$loc?>" data-toggle="modal" data-remote=true data-toggle="modal" data-target="#myModalStep1<?php echo $ord['id'] ?>" class="btn btn-<?php echo ($ord['status_step2'] == '0' ? $ord['status_step1_domClass'] : 'success');?> btn-sm<?php echo $in_work;?>"><?php if ($ord['status_step2'] == '0') { echo $ord['status_step1_act']."<br/>"; } ?><small><?php echo $ord['status_step1_name']; ?></small></a>
						<div class="modal step1 fade withdatepick" data-keyboard="false" data-backdrop="static" id="myModalStep1<?php echo $ord['id'] ?>">
							<div class="modal-dialog">
							  <div class="modal-content">
								<!-- loaded by ajax -->
								 Загрузка...
							  </div>
							</div>
						  </div>						  						
					</td>
					<td>
						<?php 
							if ($ord['status_step1'] == '110' and ($ord['status_step2'] == '205' or $ord['status_step2'] == '209')) {
								echo '<p><span class="bold_red">Несоответствие&nbsp;статуса:<br/>предоплаты не было!</p>';
							}
						?>
						<?php 
							$err_rxp = '~ОШИБКА: (?<err>.*)~';;
							preg_match($err_rxp,$np_answer['msg'],$err_match);
							if (!empty($err_match)) {
								echo '<p><span class="bold_red">Ошибочный статус:<br/>'.$err_match['err'].'</p>';
							}
						?>
					<a href="order_step2_form.php?id=<?php echo $ord['id'].$loc?>" data-toggle="modal" data-remote=true data-toggle="modal" data-target="#myModalStep2<?php echo $ord['id'] ?>" class="btn btn-<?php echo $ord['status_step2_domClass'];?> btn-sm"><?php if ($ord['status_step3'] == '0') { echo $ord['status_step2_act']."<br/>"; } ?><small><?php echo $ord['status_step2_name']; ?></small></a>
					<?php if ($ord['status_step2'] != '0') { ?>
						<div class="modal fade withdatepick"  data-keyboard="false" data-backdrop="static" id="myModalStep2<?php echo $ord['id'] ?>">
							<div class="modal-dialog">
							  <div class="modal-content">
								Загрузка...
							  </div>
							</div>
						  </div>
					<?php } ?>
					</td>
					<td><a href="<?php if ($ord['status_step3'] == '311' OR $ord['status_step3'] == '312' OR $ord['status_step3'] == '321') { echo '#myModalArchive'.$ord['id'];} else { echo '#myModalStep3'.$ord['id'];} ?>" data-toggle="modal" class="btn btn-<?php echo $ord['status_step3_domClass'];?> btn-sm"><?php if ($ord['status_step3'] != '0') { echo $ord['status_step3_act']."<br/>"; } ?><small><?php echo $ord['status_step3_name']; ?></small></a>
						<div class="modal fade" id="myModalArchive<?php echo $ord['id'] ?>">
							<div class="modal-dialog">
							  <div class="modal-content">
								<div class="modal-header">
								  <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
								  <h4 class="modal-title">Архивировать заказ</h4>
								</div>
								<form action='order_archive.php' method='GET'>		
									<input type='hidden' name='id' value='<?php echo $ord['id']?>'>								
									<?php $prop_st3 = (($ord['status_step3'] == '311' or $ord['status_step3'] == '312') ? '20' : ($ord['status_step2'] == '242' ? '31' : '30'));?>
									<?php if ($_GET['seller_id'] or $_GET['seller_id'] == '0') { ?>
										<input type='hidden' name='seller_id' value='<?php echo $_GET['seller_id']?>'>
									<?php }
										if ($_GET['page']) { ?>
										<input type='hidden' name='page' value='<?php echo $_GET['page']?>'>
									<?php }
										if ($_GET['oper']) { ?>
										<input type='hidden' name='oper' value='<?php echo $_GET['oper']?>'>
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
									<div class="modal-body">
										<div class="form-group">				
											<label>Статус для архива</label>
											<select class="form-control" name="status_step3">
												<?php foreach ($statuses_arch as $status) { ?>
													<option value='<?php echo $status['id']; ?>' <?php if ($prop_st3 == $status['id']) { echo 'selected = selected'; } ?>><?php echo $status['name']; ?></option>													
												<?php } ?>
											</select>
										</div>
										<div class="form-group">				
											<label>ФИО: <?php echo $ord['fio']; ?></label>
										</div>	
										<div class="form-group">					
											<label>Декларация Н.П.: <a target="_new" href="http://novaposhta.ua/frontend/tracking/ru?en=<?php echo $ord['newpost_id'] ?>"><?php echo $ord['newpost_id'] ?></a></label>
										</div>	
										<div class="form-group">	
											<label>Обратная декларация: <a target="_new" href="http://novaposhta.ua/frontend/tracking/ru?en=<?php echo $ord['newpost_backorder'] ?>"><?php echo $ord['newpost_backorder'] ?></a></label>
										</div>										
									</div>				
									<div class="modal-footer">
									  <button type="button" class="btn btn-default" data-dismiss="modal">Отмена</button>
									  <input type="submit" class="btn btn-primary" value='Архивировать'><p>
								</form>	  
									 <form action='update_order_step3.php' method='POST'>
											<input type='hidden' name='id' value='<?php echo $ord['id']?>'>
											<?php if ($_GET['archive']) { ?>
												<input type='hidden' name='archive' value='<?php echo $_GET['archive']?>'>
											<?php }
												if ($_GET['page']) { ?>
													<input type='hidden' name='page' value='<?php echo $_GET['page']?>'>
											<?php }
												if ($_GET['oper']) { ?>
												<input type='hidden' name='oper' value='<?php echo $_GET['oper']?>'>
											<?php } ?>											
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
											<input type='hidden' name="status_step3" value="0">
											<input type="submit" class="btn btn-danger" value='Очистить статус шага №3'>
										</form></p>
									</div>								
							  </div>
							</div>
						</div>
						<div class="modal fade" id="myModalStep3<?php echo $ord['id'] ?>">
							<div class="modal-dialog">
							  <div class="modal-content">
								<div class="modal-header">
								  <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
								  <h4 class="modal-title">Обработка заказов. Шаг 3 - Оплата</h4>
								</div>
								<form action='update_order_step3.php' method='POST'>
									<input type='hidden' name='id' value='<?php echo $ord['id']?>'>
									<?php if ($_GET['archive']) { ?>
										<input type='hidden' name='archive' value='<?php echo $_GET['archive']?>'>
									<?php }
										if ($_GET['page']) { ?>
										<input type='hidden' name='page' value='<?php echo $_GET['page']?>'>
									<?php }
										if ($_GET['oper']) { ?>
										<input type='hidden' name='oper' value='<?php echo $_GET['oper']?>'>
									<?php } ?>
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
									<div class="modal-body">
										<div class="form-group">				
											<label>Статус</label>
											<select class="form-control" name="status_step3">
												<?php foreach ($statuses_step3 as $status) { ?>
													<option value='<?php echo $status['id']; ?>' <?php if ($ord['status_step3'] == $status['id']) { echo 'selected = selected'; } ?>><?php echo ($status['automatic']?'(АВТО) ':'').$status['name']; ?></option>													
												<?php } ?>
											</select>
										</div>										
									</div>				
									<div class="modal-footer">
									  <button type="button" class="btn btn-default" data-dismiss="modal">Отмена</button>
									  <input type="submit" class="btn btn-primary" value='Сохранить'>
									</div>
								</form>
							  </div>
							</div>
						</div>
					<td><?php echo $ord['fio'] ?></td>
					<td><?php echo $ord['np_city_area'].' '.$ord['np_address'] ?><br/><small>(<?php echo $ord['city_area'].' '.$ord['address'] ?>)</small></td>
					<td><?php echo $ord['item'].' (<b>'.$ord['item_count'].'</b> шт.)'.($ord['item_params'] ? '<br/><small>'.$ord['item_params'].'</small>' : '') ?></td>
					<td><?php echo number_format(floatval($ord['item_price']),2) ?></td>
					<td><a href="order_sms_form.php?id=<?php echo $ord['id'].$loc?>" data-toggle="modal" data-remote=true data-toggle="modal" data-target="#myModalSMS<?php echo $ord['id'] ?>" class="btn btn-default"><?php echo $ord['phone'].($ord['email'] ? '<br/><small>'.$ord['email'].'</small>' : '') ?></a><?php
						$tq = "SELECT date, name as status, color FROM flysms LEFT OUTER JOIN flysms_state ON state = status WHERE order_id = :order_id AND date = (SELECT MAX(date) FROM flysms WHERE order_id = :order_id)";
						$tq_param = array(':order_id' => $ord['id']);
						try{ 
							$stmttq = $db->prepare($tq); 
							$resulttq = $stmttq->execute($tq_param); 
						} 
						catch(PDOException $ex){ die("Невозможно выполнить запрос: " . $ex->getMessage()); } 
						
						$last_sms = $stmttq->fetchAll();
						if ($last_sms) { $last_sms = end($last_sms); }
						
						if ($last_sms) {
							$sd = new DateTime($last_sms['date']);
							$sd = $sd->format('d-m-y H:i:s');
							echo '<br/><small>'.$sd.'<br/><span style="color: #'.$last_sms['color'].'">'.$last_sms['status'].'</span></small>';
						}
						
					?>
						<div class="modal fade withdatepick" id="myModalSMS<?php echo $ord['id'] ?>">
							<div class="modal-dialog">
							  <div class="modal-content">
								Загрузка...
							  </div>
							</div>
						</div>
					</td>
					<td <?php echo $ord['newpost_last_update'] ? 'title="обновлено:'.$ord['newpost_last_update'].'"': ''?>><a target="_new" href="http://novaposhta.ua/frontend/tracking/ru?en=<?php echo $ord['newpost_id'] ?>"><?php echo $ord['newpost_id'] ?></a><br/><?php echo ($np_answer['msg'] == '' ? ($ord['newpost_id'] ? '<i>обрабатывается</i>' : '') : $np_answer['msg']) ?>
					<?php if($ord['newpost_api'] and $np_answer['msg'] == 'Оформлена декларация') { ?><br><a target="_blank" href="http://orders.novaposhta.ua/pformn.php?o=<?php echo $ord['newpost_id']."&num_copy=4&token=".$ord['newpost_api'];?>">распечатать</a><?php } ?>
					</td>
					<td <?php echo $ord['newpost_last_backorder_update'] ? 'title="обновлено:'.$ord['newpost_last_backorder_update'].'"': ''?>><a target="_new" href="http://novaposhta.ua/frontend/tracking/ru?en=<?php echo $ord['newpost_backorder'] ?>"><?php echo $ord['newpost_backorder'] ?></a><br/><?php echo ($npb_answer['msg'] == '' ? ($ord['newpost_backorder'] ? '<i>обрабатывается</i>' : '') : $npb_answer['msg']) ?></td>					
				</tr>
	<?php
			if ($ord['editor_ord'] == 1 && $_SESSION['user']['group_id'] != 2) {
				?>
					<script type='text/javascript'>
							$('#wrk_btns').hide();
							$('#inwork_fin').show();
					</script>
				<?php
				break;
			}
		} ?>
	</table>
</div>
<script type='text/javascript'>
	$('.withdatepick').on('shown.bs.modal', function () {
	  var modal_id = $('.modal:visible').first().attr('id');
	  
	  $('#'+modal_id+' .datetimepicker').datetimepicker({
		format: 'yyyy-mm-dd hh:ii',
		autoclose: true,
        todayBtn: true,
		startView: 1,
		language: 'ru',
		weekStart: 1
	  });
	  	 
	});
	  
	$('.table-fixed-header').fixedHeader();
	
	if (window.location.hash) {
		if ($('#order_'+window.location.hash.replace('#','')).position()) {
			$(window).scrollTop($('#order_'+window.location.hash.replace('#','')).position().top-160);
		}
	}
	
	$('a[data-remote=true]').on('click', function() {
		$($(this).attr('data-target')+' .modal-content').load(this.href, function(result){});
	});
	
	$('.step1 button').on('click', function() {
		console.log($(this));
	});
	
	window.location.hash = "";
</script>
</body>
</html>