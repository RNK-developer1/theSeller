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
				SELECT orders.created_at, orders.id, owner.username AS owner_name, COALESCE( oper.username,  'н/д' ) AS oper_name, statuses3.name AS status3, updated_date.upd as upd, DATEDIFF( NOW( ) , updated_date.upd ) AS ddiff FROM orders LEFT JOIN users AS oper ON orders.oper_id = oper.id LEFT JOIN users AS owner ON orders.owner_id = owner.id
				LEFT JOIN statuses AS statuses3 ON orders.status_step3 = statuses3.id, (
					SELECT MAX( DATE ) AS upd, order_id
					FROM orders_audit
					WHERE activity LIKE '%Подано заявление о возврате%'
					GROUP BY order_id
					) AS updated_date WHERE (orders.status_step1 = 0 OR orders.status_step1 > 50) AND status_step2=221 and status_step3 IN (0,318,320) AND updated_date.order_id = orders.id AND
					(:item_id IS NULL OR :item_id = '0' OR orders.item_id = :item_id) AND
				(:seller_id = '0' OR owner.id = :seller_id) AND
				owner.id IN (SELECT subseller_id FROM sellers_for_sellers WHERE sellers_for_sellers.seller_id = :user_id)
				ORDER BY ddiff DESC, orders.owner_id, status3";
				
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
	<h3>Возвраты оформлены, ещё не получены</h3>
	<table cellpadding='2' border='1' style="table-layout: fixed; border-collapse: collapse; border: 1px solid black;">
	<tr><th>Дата оформления<br/>Прошло дней</th><th>Дата и № заказа</th><th>Предприниматель</th><th>Статус шага 3</th></tr>
	
	<?php $t_oper = ''; 
	
	foreach ($orders_delayed as $t_order) { 
			$crd = new DateTime($t_order['created_at']);
			$crd = $crd->format('d-m-y H:i:s');
			$ud = new DateTime($t_order['upd']);
			$ud = $ud->format('d-m-y H:i:s');
		?>
		<tr><td><?php echo $ud;?><br/><b><?php echo $t_order['ddiff'];?></b></td>
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
		<td><?php echo $t_order['status3'];?></td>
		</tr>
			
	<?php } ?>
	</table>
</div>

<script type='text/javascript'>
	$('a[data-remote=true]').on('click', function() {
		$($(this).attr('data-target')+' .modal-content').load(this.href, function(result){});
	});
</script>
</body>
</html>