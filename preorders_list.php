<?php
	require("config.php"); 	
	
	if (empty($_SESSION['user']) || $_SESSION['user']['active'] == 0) {
		header("Location: index.php"); 
		die("Перенаправление: index.php"); 
	}
	
	$page_start = 1000*($_GET['page'] ? $_GET['page']-1 : 0);
	
		$query = " 
				SELECT 
					preorder.user_id as id,
					preorder.referrer as referrer,
					preorder.request as request,
					preorder.ip_src as ip_src,
					preorder.created_at as created_at,
					preorder.updated_at as updated_at,
					item.name as item,
					item.uuid as item_id,					
					owner.username as owner_username
				"					
			.($_SESSION['user']['group_id'] == 2 ? 
			"   FROM users as owner, preorder LEFT OUTER JOIN item ON item.uuid = preorder.item_uuid
				WHERE preorder.phone='' AND ".($_GET['archive'] ? "archived": "NOT archived")." AND 
					(:order_date IS NULL OR :order_date = '' OR DATE(preorder.created_at) >= :order_date) AND
					(:order_date_end IS NULL OR :order_date_end = '' OR DATE(preorder.created_at) <= :order_date_end) AND
					(:item_id IS NULL OR :item_id = '0' OR preorder.item_uuid = :item_id) AND
					owner.id = item.owner_id AND
					(:seller_id = '0' OR owner.id = :seller_id) AND
					owner.id IN (SELECT subseller_id FROM sellers_for_sellers WHERE sellers_for_sellers.seller_id = :user_id)					
					" :
			"   FROM users as owner, operators_for_sellers, preorder LEFT OUTER JOIN item ON item.uuid = preorder.item_uuid 
				WHERE preorder.phone='' AND ".($_GET['archive'] ? "archived": "NOT archived")." AND
					(:order_date IS NULL OR :order_date = '' OR DATE(preorder.created_at) >= :order_date) AND
					(:order_date_end IS NULL OR :order_date_end = '' OR DATE(preorder.created_at) <= :order_date_end) AND
					owner.id = item.owner_id AND				
					owner.id = operators_for_sellers.seller_id AND
					operators_for_sellers.operator_id = :user_id AND
					(:seller_id IS NULL OR :seller_id = '0' OR owner.id = :seller_id) AND
					(:item_id IS NULL OR :item_id = '0' OR preorder.item_uuid = :item_id)
			")." ORDER BY created_at DESC LIMIT ".$page_start.",1000";		
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
	
	$orders = $stmt->fetchAll();
	
	$query = " 
				SELECT 
					COUNT(*) as cnt "					
			.($_SESSION['user']['group_id'] == 2 ? 
			"   FROM users as owner, preorder LEFT OUTER JOIN item ON item.uuid = preorder.item_uuid
				WHERE preorder.phone ='' AND ".($_GET['archive'] ? "archived": "NOT archived")." AND 
					(:order_date IS NULL OR :order_date = '' OR DATE(preorder.created_at) >= :order_date) AND
					(:order_date_end IS NULL OR :order_date_end = '' OR DATE(preorder.created_at) <= :order_date_end) AND
					(:item_id IS NULL OR :item_id = '0' OR preorder.item_uuid = :item_id) AND
					owner.id = item.owner_id AND
					(:seller_id = '0' OR owner.id = :seller_id) AND
					owner.id IN (SELECT subseller_id FROM sellers_for_sellers WHERE sellers_for_sellers.seller_id = :user_id)					
					" :
			"   FROM users as owner, operators_for_sellers, preorder LEFT OUTER JOIN item ON item.uuid = preorder.item_uuid 
				WHERE preorder.phone ='' AND ".($_GET['archive'] ? "archived": "NOT archived")." AND
					(:order_date IS NULL OR :order_date = '' OR DATE(preorder.created_at) >= :order_date) AND
					(:order_date_end IS NULL OR :order_date_end = '' OR DATE(preorder.created_at) <= :order_date_end) AND
					owner.id = item.owner_id AND				
					owner.id = operators_for_sellers.seller_id AND
					operators_for_sellers.operator_id = :user_id AND
					(:seller_id IS NULL OR :seller_id = '0' OR owner.id = :seller_id) AND
					(:item_id IS NULL OR :item_id = '0' OR preorder.item_uuid = :item_id)
			");		
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

	$orders_full_count = $stmt->fetch();
		
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
	
	/*$query = " 
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
	
	$statuses_step3 = $stmt->fetchAll();		*/
?>

<!doctype html>
<html lang="ru">
<?php include 'header.php' ?>
<body>
<?php include 'top_menu.php' ?>
<div style="display: none;">
</div>
<?php $loc = '';
						if ($_GET['seller_id'] or $_GET['seller_id'] == '0') {$loc .= '&seller_id='.$_GET['seller_id'];}
						if ($_GET['item_id']or $_GET['item_id'] == '0') {$loc .= ($loc!='' ? '&item_id=' : '?item_id=').$_GET['item_id'];}						
						if ($_GET['order_date']) {$loc .= '&order_date='.$_GET['order_date'];}	
						if ($_GET['order_date_end']) {$loc .= '&order_date_end='.$_GET['order_date_end'];}	
					?>
<div class="container">
	<?php if (!$_GET['archive']) {?>
	<h3>Посещения<?php echo ', показано '.count($orders).' из '.$orders_full_count['cnt'];?>
	<?php } else {?>
	<h3>Архив посещений<?php echo ', показано '.count($orders).' из '.$orders_full_count['cnt'];?>
	<?php } ?>
	</h3>
	<?php if (!$_GET['archive']) { ?>
		<a href="preorders_list.php?archive=1" class="btn btn-default">Архив посещений</a>
	<?php } else { ?>
		<a href="preorders_list.php?r=0" class="btn btn-default">Текущие посещения</a>
	<?php } ?>
	<?php if ($_GET['visit'] and !$_GET['archive']) {?>
		<a href="preorders_csv.php?r=0<?php echo $loc;?>" class="btn btn-danger">Экспорт в CSV</a>	
	<?php } ?>
	<?php 
		if ($_GET['visit']) {$loc .= '&visit='.$_GET['visit'];}
		if ($orders_full_count['cnt'] > count($orders)) {
			echo '<h3>Страницы:';
				for ($pg = 1; 1000*($pg-1) <= $orders_full_count['cnt']; $pg++) {
					if ($_GET['page'] == $pg or (!$_GET['page'] and $pg==1)) {
						echo "&nbsp;&nbsp;<b>".$pg."</b>";
					} else {
						echo "&nbsp;&nbsp;<a href='preorders_list.php?page=".$pg.$loc."'>".$pg."</a>";
					}
				}
			echo '</h3>';
			
			if ($_GET['page']) {$loc .= '&page='.$_GET['page'];}
	} ?>
	<table class='table table-hover table-bordered table-fixed-header'>
	<thead class="header"><th>Предпр-ль</th><th>Создан, время на сайте</th><th>Реферрер</th><th>Запрос</th><th>IP</th><th>Товар</th></thead>
	<?php foreach ($orders as $ord){ ?>	
				<tr id='order_<?php echo $ord['id']?>'>
					<?php echo "<td><small>".str_replace(" ","<br/>",$ord['owner_username'])."</small></td>"; 
					 $crd = new DateTime($ord['created_at']);
					 $crd = $crd->format('d-m-y H:i:s');
					$ad = '';	
					 if ($ord['updated_at']) {
						 $sdt = new DateTime($ord['created_at']);
						 $ddif = $sdt->diff(new DateTime($ord['updated_at']));
						 $ad = $ddif->format('%i&nbsp;мин&nbsp;%s&nbsp;сек');
					 }
					?>
					<td title="№ <?php echo $ord['id'];?>"><?php echo str_replace("-","&#8209;",str_replace(" ","<br/>",$crd)); ?> -<br/><?php echo str_replace("-","&#8209;",str_replace(" ","<br/>",$ad)); ?>
					</td>					
					<td><small><?php echo $ord['referrer'];?></small></td><td><small><?php echo $ord['request'];?></small></td><td><small><?php echo $ord['ip_src'];?></small></td>
					</td>					
					<td><?php echo $ord['item'] ?></td>
				</tr>
	<?php	} ?>
	</table>
</div>
<script type='text/javascript'>
	$('.table-fixed-header').fixedHeader();
	
	if (window.location.hash) {
		if ($('#order_'+window.location.hash.replace('#','')).position()) {
			$(window).scrollTop($('#order_'+window.location.hash.replace('#','')).position().top-160);
		}
	}
	
	$('a[data-remote=true]').on('click', function() {
		$($(this).attr('data-target')+' .modal-content').load(this.href, function(result){});
	});
	
	window.location.hash = "";
</script>
</body>
</html>