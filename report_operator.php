<?php
	require("config.php"); 	
	
	$dstart = new DateTime($_GET['order_date']);
	$dend = new DateTime((!$_GET['order_date_end'] or $_GET['order_date_end'] == '') ? $_GET['order_date'] : $_GET['order_date_end']);	

	if (empty($_SESSION['user']) || $_SESSION['user']['active'] == 0 || $_SESSION['user']['group_id'] != 2) {
		header("Location: index.php"); 
		die("Перенаправление: index.php"); 
	}

	$select_sellers = array();
	$query = " 
				SELECT DISTINCT
					users.*
				FROM sellers_for_sellers, operators_for_sellers, users
				WHERE
					users.id = operators_for_sellers.operator_id AND
					operators_for_sellers.seller_id = sellers_for_sellers.subseller_id AND
					sellers_for_sellers.seller_id = :user_id
			";		
		$query_params = array( 
			':user_id' => $selected_seller
		); 
			 
	try{ 
		$stmt = $db->prepare($query); 
		$result = $stmt->execute($query_params); 
	} 
	catch(PDOException $ex){ die("Невозможно выполнить запрос: " . $ex->getMessage()); } 	
	
	$select_sellers = $stmt->fetchAll();

	if ($selected_seller != '0') {
		$query = " 
					SELECT *
					FROM users
					WHERE
						id = :user_id
				";		
			$query_params = array( 
				':user_id' => $selected_seller
			); 
				 
		try{ 
			$stmt = $db->prepare($query); 
			$result = $stmt->execute($query_params); 
		} 
		catch(PDOException $ex){ die("Невозможно выполнить запрос: " . $ex->getMessage()); } 	
		
		$selected_user = $stmt->fetch();	
	}
	
	if ($selected_seller != 0) {
		$query = " 
					SELECT activity, COUNT(*) as number
					FROM orders_audit
					WHERE
						user_id = :user_id AND
						activity <> 'Передан в списке заказов в Новую Почту' AND
						(:order_date IS NULL OR :order_date = '' OR DATE(orders_audit.date) >= :order_date) AND
						(:order_date_end IS NULL OR :order_date_end = '' OR DATE(orders_audit.date) <= :order_date_end)
					GROUP BY activity
					ORDER BY activity ASC
				";		
			$query_params = array( 
				':user_id' => $selected_seller,
				':order_date' => $_GET['order_date'],
				':order_date_end' => $_GET['order_date_end'] ? $_GET['order_date_end'] : $_GET['order_date']
			); 
				 
		try{ 
			$stmt = $db->prepare($query); 
			$result = $stmt->execute($query_params); 
		} 
		catch(PDOException $ex){ die("Невозможно выполнить запрос: " . $ex->getMessage()); } 
		
		$stats = $stmt->fetchAll();			
	}
	
	$statuses_step1 = array(1);
	$statuses_step2 = array(1);
	$statuses_step3 = array(1);
?>

<!doctype html>
<html lang="ru">
<?php include 'header.php' ?>
<body>
<?php include 'top_menu.php' ?>
<div class="container">
<a href="reports.php">&larr; Вернуться в меню отчетов</a>
	<?php 
	if ($selected_seller != 0) {?>
		<h3>Отчет по оператору <?php if ($selected_user) {echo $selected_user['username'].' ';} if ($_GET['order_date']) {?>с <?php echo $dstart->format('d-m-y'); ?> по <?php echo $dend->format('d-m-y'); }?> </h3>
		<table>
		<?php foreach ($stats as $act) { ?>
			<tr><td><?php echo str_replace("<br>","",$act['activity']);?></td><td><?php echo $act['number'];?></td></tr>
		<?php } ?>
		</table>
	<?php } else { ?>
		<h3>Отчет по операторам с <?php echo $_GET['order_date'];?> по <?php echo $_GET['order_date_end'] ? $_GET['order_date_end'] : $_GET['order_date'];?></h3>
		<table class='table table-hover table-bordered table-fixed-header'>
		<thead class="header"><th>Оператор</th><th>Принятые заказы</th><th>Оплаченные заказы</th></thead>
		<?php foreach ($select_sellers as $oper) { ?>		
			
				<tr>
					<td><a href="report_operator.php?seller_id=<?php echo $oper['id'].($_GET['order_date'] ? '&order_date='.$_GET['order_date'] : '').($_GET['order_date_end'] ? '&order_date_end='.$_GET['order_date_end'] : '');?>"><?php echo $oper['username'];?></a></td>
					<td><?php 						
						$query = "  SELECT count(*) as cnt
									FROM
									(SELECT DISTINCT orders_audit.order_id 
									FROM orders_audit
									WHERE
										user_id = :oper_id AND
										order_id IN (SELECT id FROM orders WHERE oper_id=:oper_id) AND
										(:order_date IS NULL OR :order_date = '' OR DATE(orders_audit.date) >= :order_date) AND
										(:order_date_end IS NULL OR :order_date_end = '' OR DATE(orders_audit.date) <= :order_date_end)
									) as orders_a
								";		
							$query_params = array( 
								':oper_id' => $oper['id'],
								':order_date' => $_GET['order_date'],
								':order_date_end' => $_GET['order_date_end'] ? $_GET['order_date_end'] : $_GET['order_date']
							); 
								 
						try{ 
							$stmt = $db->prepare($query); 
							$result = $stmt->execute($query_params); 
						} 
						catch(PDOException $ex){ die("Невозможно выполнить запрос: " . $ex->getMessage()); } 
						
						$oper_orders = $stmt->fetch();
						echo $oper_orders['cnt'];?>
					</td>
					<td><?php 						
						$query = "  SELECT count(*) as cnt
									FROM
									(SELECT DISTINCT orders_audit.order_id 
									FROM orders_audit
									WHERE
										user_id = 1 AND
										activity LIKE '%отправил деньги%' AND
										order_id IN (SELECT id FROM orders WHERE oper_id=:oper_id) AND
										(:order_date IS NULL OR :order_date = '' OR DATE(orders_audit.date) >= :order_date) AND
										(:order_date_end IS NULL OR :order_date_end = '' OR DATE(orders_audit.date) <= :order_date_end)
									) as orders_a
								";			
							$query_params = array( 
								':oper_id' => $oper['id'],
								':order_date' => $_GET['order_date'],
								':order_date_end' => $_GET['order_date_end'] ? $_GET['order_date_end'] : $_GET['order_date']
							); 
								 
						try{ 
							$stmt = $db->prepare($query); 
							$result = $stmt->execute($query_params); 
						} 
						catch(PDOException $ex){ die("Невозможно выполнить запрос: " . $ex->getMessage()); } 
						
						$oper_payed_orders = $stmt->fetch();
						echo $oper_payed_orders['cnt'];
						?>
					</td>
				</tr>				
			
	<?php } ?>
		</table>
	<?php } ?>
</div>
</body>
</html>