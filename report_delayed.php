<?php
	require("config.php"); 	

	if (empty($_SESSION['user']) || $_SESSION['user']['active'] == 0 || $_SESSION['user']['group_id'] != 2) {
		header("Location: index.php"); 
		die("Перенаправление: index.php"); 
	}

	$query = " 
			SELECT 
				item.uuid as uuid,
				item.name as name
			FROM users as owner, item
			WHERE
				item.owner_id = owner.id AND
				(:seller_id = '0' OR owner.id = :seller_id)
		";		
	$query_params = array( 
			':seller_id' => $_GET['seller_id'] || (!$_GET['seller_id'] && $_GET['seller_id']=='0') ? $_GET['seller_id'] : $_SESSION['user']['id'],
		);		
			 
	try{ 
		$stmt = $db->prepare($query); 
		$result = $stmt->execute($query_params); 
	} 
	catch(PDOException $ex){ die("Невозможно выполнить запрос01: " . $ex->getMessage()); } 
	
	$select_items = $stmt->fetchAll();
	
	$query = " 
				SELECT 
					*
				FROM
					statuses				
				WHERE id <> 0
				ORDER BY id ASC
			"; 
	try{ 
		$stmt = $db->prepare($query); 
		$result = $stmt->execute($query_params); 
	} 
	catch(PDOException $ex){ die("Невозможно выполнить запрос02: " . $ex->getMessage()); } 
	
	$statuses_step1 = $stmt->fetchAll();

	$select_sellers = array();
	$query = " 
				SELECT 
					CONCAT(REPEAT(' -',sellers_for_sellers.depth),users.username) as username,
					users.id as id
				FROM sellers_for_sellers, users
				WHERE
					users.id = sellers_for_sellers.subseller_id
					AND sellers_for_sellers.seller_id = :user_id
			";		
		$query_params = array( 
			':user_id' => $_SESSION['user']['id']
		); 
			 
	try{ 
		$stmt = $db->prepare($query); 
		$result = $stmt->execute($query_params); 
	} 
	catch(PDOException $ex){ die("Невозможно выполнить запрос03: " . $ex->getMessage()); } 
	
	$select_sellers = array_merge($select_sellers,$stmt->fetchAll());	
	
	
	
	$query = " 
				SELECT COALESCE( oper.username,  '0' ) AS oper_ord, COALESCE( oper.username,  'н/д' ) AS oper_name, orders.id, owner.username AS owner_name, statuses1.name AS status1, statuses2.name AS status2, orders.created_at, updated_date.upd, DATEDIFF( NOW( ) , COALESCE(orders.alert_at, updated_date.upd) ) AS ddiff
				FROM orders
				LEFT JOIN users AS oper ON orders.oper_id = oper.id
				LEFT JOIN users AS owner ON orders.owner_id = owner.id
				LEFT JOIN statuses AS statuses1 ON orders.status_step1 = statuses1.id
				LEFT JOIN statuses AS statuses2 ON orders.status_step2 = statuses2.id, (
					SELECT MAX( DATE ) AS upd, order_id
					FROM orders_audit
					WHERE activity <> 'Миграция комментариев шага 1'
					GROUP BY order_id
					) AS updated_date
					WHERE (
					orders.status_step1
					IN ( 101, 102 ) 
					OR orders.status_step2
					IN ( 206, 208 )
				)
				AND orders.status_step3 =0
				AND updated_date.order_id = orders.id AND
				(:item_id IS NULL OR :item_id = '0' OR orders.item_id = :item_id) AND
				(:seller_id = '0' OR owner.id = :seller_id) AND
				owner.id IN (SELECT subseller_id FROM sellers_for_sellers WHERE sellers_for_sellers.seller_id = :user_id)
				ORDER BY oper_ord, ddiff DESC , status1, status2";
				
		$query_params = array( 
				':user_id' => $_SESSION['user']['id'],
				':item_id' => $_GET['item_id'],
				':seller_id' => $_GET['seller_id']
			); 
	
	 
	try{ 
		$stmt = $db->prepare($query); 
		$result = $stmt->execute($query_params); 
	} 
	catch(PDOException $ex){ die("Невозможно выполнить запрос3: " . $ex->getMessage()); } 
	
	$orders_delayed = $stmt->fetchAll();
?>

<!doctype html>
<html lang="ru">
<?php include 'header.php' ?>
<body>
<?php include 'top_menu.php' ?>
<div style="display: none;">
</div>
<div class="container">	
<a href="reports.php">&larr; Вернуться в меню отчетов</a>
	<h3>Хвосты операторов</h3>
	
	<?php $t_oper = ''; 
	$t_idx = 1;
	
	foreach ($orders_delayed as $t_order) {
		if (intval($t_order['ddiff']) > 0) {
		if ($t_oper != $t_order['oper_name']) {
			if ($t_oper != '') {
				echo '</table>';
			}
			?>
				<h2><?php echo $t_order['oper_name'];?></h2>
				<table cellpadding='2' border='1' style="table-layout: fixed; border-collapse: collapse; border: 1px solid black;">
				<tr><th>№</th><th>Задержка (дни)</th><th>Дата и № заказа</th><th>Предприниматель</th><th>Статус шага 1</th><th>Статус шага 2</th></tr>
			<?php 
			$t_oper = $t_order['oper_name'];
			$t_idx = 1;
		} 
		$crd = new DateTime($t_order['created_at']);
		$crd = $crd->format('d-m-y H:i:s');
		?>
		<tr><td><?php echo $t_idx; $t_idx++;?></td><td><?php echo $t_order['ddiff'];?></td>
		<td><?php echo str_replace("-","&#8209;",$crd); ?><br/>№<?php echo $t_order['id'];?><br/>
			<a href="order_history.php?id=<?php echo $t_order['id']?>" data-remote=true data-toggle="modal" data-target="#myModalHistory<?php echo $t_order['id'] ?>" class="">история</a>
				<div class="modal fade" id="myModalHistory<?php echo $t_order['id'] ?>">
					<div class="modal-dialog">
						<div class="modal-content">
							<!-- loaded by ajax -->
						</div>
					</div>
				</div>
		</td>
		<td><?php echo $t_order['owner_name'];?></td>
		<td><?php echo $t_order['status1'];?></td>
		<td><?php echo $t_order['status2'];?></td>
		</tr>
			
	<?php } } ?>
	</table>
</div>

<script type='text/javascript'>
	$('a[data-remote=true]').on('click', function() {
		$($(this).attr('data-target')+' .modal-content').load(this.href, function(result){});
	});
</script>
</body>
</html>