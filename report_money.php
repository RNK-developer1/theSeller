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
	
	$act_type = 'Статистика оплаты заказов';		
?>

<!doctype html>
<html lang="ru">
<?php include 'header.php' ?>
<body>
<?php include 'top_menu.php' ?>

<!--Load the AJAX API-->
<script type="text/javascript" src="https://www.google.com/jsapi"></script>
<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.8.2/jquery.min.js"></script>
<script type="text/javascript">

// Load the Visualization API and the piechart package.
google.load('visualization', '1', {'packages':['ColumnChart']});

// Set a callback to run when the Google Visualization API is loaded.
google.setOnLoadCallback(drawChart);

function drawChart() {

  // Create our data table out of JSON data loaded from server.
  <?php 
  /*
	SELECT COUNT(money.order_id) ord_cnt, DATEDIFF(payment_date, sent_date) as pay_days FROM (SELECT order_id, MAX(date) as payment_date FROM `orders_audit` WHERE DATE(date) = '2013-12-23' AND activity='Клиент отправил деньги' GROUP BY order_id) as money JOIN (SELECT order_id, MIN(date) as sent_date FROM `orders_audit` WHERE activity='Груз прибыл на склад получателя' GROUP BY order_id) as sent ON sent.order_id = money.order_id GROUP BY pay_days ORDER BY pay_days ASC
*/		
	
	$query = " 
				SELECT COUNT(money.order_id) ord_cnt FROM (SELECT order_id, MAX(date) as payment_date FROM `orders_audit` JOIN orders ON orders.id=orders_audit.order_id
				WHERE 
				(:item_id IS NULL OR :item_id = '0' OR orders.item_id = :item_id) AND
				(:seller_id = '0' OR orders.owner_id = :seller_id) AND
				(:order_date IS NULL OR :order_date = '' OR DATE(date) >= :order_date) AND
				(:order_date_end IS NULL OR :order_date_end = '' OR DATE(date) <= :order_date_end) AND
				activity='Клиент отправил деньги' GROUP BY order_id) as money JOIN (SELECT order_id, MIN(date) as sent_date FROM `orders_audit` WHERE activity='Груз прибыл на склад получателя' GROUP BY order_id) as sent ON sent.order_id = money.order_id";		
				
		$query_params = array( 
				':item_id' => $_GET['item_id'],
				':seller_id' => $_GET['seller_id'],
				':order_date' => $_GET['order_date'],
				':order_date_end' => $_GET['order_date_end'] ? $_GET['order_date_end'] : $_GET['order_date']
			); 
	
	 
	try{ 
		$stmt = $db->prepare($query); 
		$result = $stmt->execute($query_params); 
	} 
	catch(PDOException $ex){ die("Невозможно выполнить запрос3: " . $ex->getMessage()); } 
	
	$orders_count_sum = $stmt->fetch();
	$orders_count_sum = $orders_count_sum['ord_cnt'];
	
		$query = " 
				SELECT COUNT(money.order_id) ord_cnt, DATEDIFF(payment_date, sent_date)+1 as pay_days FROM (SELECT order_id, MAX(date) as payment_date FROM `orders_audit` JOIN orders ON orders.id=orders_audit.order_id
				WHERE 
				(:item_id IS NULL OR :item_id = '0' OR orders.item_id = :item_id) AND
				(:seller_id = '0' OR orders.owner_id = :seller_id) AND
				(:order_date IS NULL OR :order_date = '' OR DATE(date) >= :order_date) AND
				(:order_date_end IS NULL OR :order_date_end = '' OR DATE(date) <= :order_date_end) AND
				activity='Клиент отправил деньги' GROUP BY order_id) as money JOIN (SELECT order_id, MIN(date) as sent_date FROM `orders_audit` WHERE activity='Груз прибыл на склад получателя' GROUP BY order_id) as sent ON sent.order_id = money.order_id GROUP BY pay_days ORDER BY pay_days ASC
				";		
				
		$query_params = array( 
				':item_id' => $_GET['item_id'],
				':seller_id' => $_GET['seller_id'],
				':order_date' => $_GET['order_date'],
				':order_date_end' => $_GET['order_date_end'] ? $_GET['order_date_end'] : $_GET['order_date']
			); 
	
	 
	try{ 
		$stmt = $db->prepare($query); 
		$result = $stmt->execute($query_params); 
	} 
	catch(PDOException $ex){ die("Невозможно выполнить запрос3: " . $ex->getMessage()); } 
	
	$table['cols'] = array(
			array('label' => 'Дни', 'type' => 'string'),
			array('label' => 'Заказы', 'type' => 'number'),
		);
		
	$rows = array();

	$tbl_r = '';
	$last_days = 0;
	$cur_sum = 0;
	
	while($r = $stmt->fetch() ) {			
		while (intval($r['pay_days']) > $last_days+1) {
			$last_days++;
			if ($last_days <= 30) { $rows[] = "['{$last_days}',0]";	}		
			$tbl_r.="<tr><td>".$last_days."</td><td>0</td><td><td><td></tr>";
		}
		if ($r['pay_days'] <= 30) { $rows[] = "['{$r['pay_days']}',{$r['ord_cnt']}]"; }
		$cur_sum += $r['ord_cnt'];
		$tbl_r.="<tr><td>".$r['pay_days']."</td><td>".$r['ord_cnt']."</td><td>".number_format(100*$r['ord_cnt']/$orders_count_sum,1)."</td><td>".number_format(100*$cur_sum/$orders_count_sum,1)."</td><td><a target='_blank' href='".str_replace('report_money.php','report_money_list.php',$_SERVER['REQUEST_URI'])."&d=".$r['pay_days']."'>список</a></td></tr>";
		$last_days = $r['pay_days'];		
	}
	while ($last_days <= 30) {
		$last_days++;
		if ($last_days <= 30) { $rows[] = "['{$last_days}',0]";	}		
	}
	
	$rowsString = implode(',',$rows);

	$jsonTable = json_encode($table);
	
	?>
	
	var data = new google.visualization.DataTable(<?=$jsonTable?>);

	var json = [<?php echo $rowsString; ?>];

	data.addRows(json);
	
	var options = {
		legend: 'none'
	};

  // Instantiate and draw our chart, passing in some options.
  //do not forget to check ur div ID
  var chart = new google.visualization.ColumnChart(document.getElementById('chart_div'));
  chart.draw(data, options);
  	
}

</script>

<div class="container">	
<a href="reports.php">&larr; Вернуться в меню отчетов</a>
	<h3>День оплаты заказов</h3>
	
	<?php	$dstart = new DateTime($_GET['order_date']);
			$dend = new DateTime((!$_GET['order_date_end'] or $_GET['order_date_end'] == '') ? $_GET['order_date'] : $_GET['order_date_end']);	
	?>
	
	<?php if ($_GET['order_date']) { ?>
		<p><?php echo 'Период оплат:<br/>'.$dstart->format('d.m.Y').' - '.$dend->format('d.m.Y'); ?></p>
	<?php } ?>
		
	<div id="chart_div" style="width:950px; height:250px;"></div>	
		
	<br/><br/>	
	
	<table cellpadding='2' border='1' style="table-layout: fixed; border-collapse: collapse; border: 1px solid black;">
	<tr><th style="width: 70px;">День оплаты заказа</th><th>Кол-во заказов</th><th>%</th><th>&Sigma;,%</th><th>Заказы</th></tr>
		<?php echo $tbl_r; ?>
		<tr><td><b>Всего</b></td><td><?php echo $orders_count_sum; ?></td><td><td><td>
	</table>
	
</div>
</body>
</html>