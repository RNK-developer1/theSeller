<?php
	require("config.php"); 	
	$zet = 0;
	$count_z = 0;
	$order_count = array();
	$max_cnt = NULL;
	$tbl = '';
	$report_type = 1;
	if($_GET['report_type'] == 2){$report_type = 2;}
	if($_GET['report_type'] == 3){$report_type = 3;}
	
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
					AND sellers_for_sellers.seller_id = 24
			";		
		$query_params = array( 
		); 
			 
	try{ 
		$stmt = $db->prepare($query); 
		$result = $stmt->execute($query_params); 
	} 
	catch(PDOException $ex){ die("Невозможно выполнить запрос03: " . $ex->getMessage()); } 
	
	$select_sellers = array_merge($select_sellers,$stmt->fetchAll());	
	
	$act_type = 'Статистика заказов';		
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
  <?php if ($_GET['order_date'] and $_GET['order_date'] != '') {
			$dstart = new DateTime($_GET['order_date']);
			$dend = new DateTime((!$_GET['order_date_end'] or $_GET['order_date_end'] == '') ? $_GET['order_date'] : $_GET['order_date_end']);
			
			
			if (($_GET['report_type'] == 1)||(!($_GET['report_type']))) {
				$query = " 
					SELECT MAX(orders_count) as max_c FROM ( SELECT 
						DATE(orders.created_at) AS created_date,
						DATE_FORMAT(created_at, '%H') AS created_hour,
						".($_GET['type']=='items' ? "SUM(orders.item_count)" : "COUNT(*)")." AS orders_count
					FROM orders, users as owner
					WHERE 
						(:order_date IS NULL OR :order_date = '' OR DATE(orders.created_at) >= :order_date) AND
						(:order_date_end IS NULL OR :order_date_end = '' OR DATE(orders.created_at) <= :order_date_end) AND
						(:item_id IS NULL OR :item_id = '0' OR orders.item IN (SELECT name FROM item WHERE uuid = :item_id)) AND
						(:status_id IS NULL OR :status_id = '0' OR orders.status_step1 = :status_id OR orders.status_step2 = :status_id OR orders.status_step3 = :status_id) AND
						(orders.status_step1 <> 40 AND orders.status_step2 <> 40 AND orders.status_step3 <> 40) AND
						owner.id = orders.owner_id AND
						(:seller_id = '0' OR owner.id = :seller_id)
					GROUP BY created_date, created_hour ) as src";
				}
				elseif ($_GET['report_type'] == 2) {
					$query = " 
					SELECT MAX(orders_count) as max_c FROM ( SELECT 
						DATE(orders.created_at) AS created_date,
						DATE_FORMAT(created_at, '%W') AS created_hour,
						".($_GET['type']=='items' ? "SUM(orders.item_count)" : "COUNT(*)")." AS orders_count
					FROM orders, users as owner
					WHERE 
						(:order_date IS NULL OR :order_date = '' OR DATE(orders.created_at) >= :order_date) AND
						(:order_date_end IS NULL OR :order_date_end = '' OR DATE(orders.created_at) <= :order_date_end) AND
						(:item_id IS NULL OR :item_id = '0' OR orders.item IN (SELECT name FROM item WHERE uuid = :item_id)) AND
						(:status_id IS NULL OR :status_id = '0' OR orders.status_step1 = :status_id OR orders.status_step2 = :status_id OR orders.status_step3 = :status_id) AND
						(orders.status_step1 <> 40 AND orders.status_step2 <> 40 AND orders.status_step3 <> 40) AND
						owner.id = orders.owner_id AND
						(:seller_id = '0' OR owner.id = :seller_id)
					GROUP BY created_date, created_hour ) as src";
				}
				else {
					$query = " 
					SELECT MAX(orders_count) as max_c FROM ( SELECT 
						DATE(orders.created_at) AS created_date,
						DATE_FORMAT(created_at, '%H') AS created_hour,
						".($_GET['type']=='items' ? "SUM(orders.item_count)" : "COUNT(*)")." AS orders_count
					FROM orders, users as owner
					WHERE 
						(:order_date IS NULL OR :order_date = '' OR DATE(orders.created_at) >= :order_date) AND
						(:order_date_end IS NULL OR :order_date_end = '' OR DATE(orders.created_at) <= :order_date_end) AND
						(:item_id IS NULL OR :item_id = '0' OR orders.item IN (SELECT name FROM item WHERE uuid = :item_id)) AND
						(:status_id IS NULL OR :status_id = '0' OR orders.status_step1 = :status_id OR orders.status_step2 = :status_id OR orders.status_step3 = :status_id) AND
						(orders.status_step1 <> 40 AND orders.status_step2 <> 40 AND orders.status_step3 <> 40) AND
						owner.id = orders.owner_id AND
						(:seller_id = '0' OR owner.id = :seller_id)
					GROUP BY created_date, created_hour ) as src";
				}


			
				
		$query_params = array( 
				':item_id' => $_GET['item_id'],
				':seller_id' => $_GET['seller_id'],
				':status_id' => $_GET['status_id'],
				':order_date' => $_GET['order_date'],
				':order_date_end' => $_GET['order_date_end'] ? $_GET['order_date_end'] : $_GET['order_date']
			); 
	
	 
	try{ 
		$stmt = $db->prepare($query); 
		$result = $stmt->execute($query_params); 
	} 
	catch(PDOException $ex){ die("Невозможно выполнить запрос1: " . $ex->getMessage()); } 

	$max_cnt = $stmt->fetchAll();	
	
	if (!empty($max_cnt)) {
		$max_cnt = $max_cnt[0]['max_c'];
	} else {
		$max_cnt = 0;
	}

	?>
	
	<?php $idx = 0; 
	
	if($report_type == 1){
	while ($dend >= $dstart) { 
	
			$query = " 
				SELECT 
					DATE_FORMAT(created_at, '%H') AS created_hour,
					item,
					".($_GET['type']=='items' ? "SUM(orders.item_count)" : "COUNT(*)")." AS orders_count
				FROM orders, users as owner
				WHERE 
					(:order_date IS NULL OR :order_date = '' OR DATE(orders.created_at) = :order_date) AND
					(:item_id IS NULL OR :item_id = '0' OR orders.item IN (SELECT name FROM item WHERE uuid = :item_id)) AND
					(:status_id IS NULL OR :status_id = '0' OR orders.status_step1 = :status_id OR orders.status_step2 = :status_id OR orders.status_step3 = :status_id) AND
					(orders.status_step1 <> 40 AND orders.status_step2 <> 40 AND orders.status_step3 <> 40) AND
					owner.id = orders.owner_id AND
					(:seller_id = '0' OR owner.id = :seller_id)					
				GROUP BY created_hour".($_GET['item_id'] ? ", item" : "");	
     		
			$query_params = array( 
				':item_id' => $_GET['item_id'],
				':seller_id' => $_GET['seller_id'],
				':status_id' => $_GET['status_id'],
				':order_date' => $dstart->format('Y-m-d'),
			); 
		
				
		
	
	 
	try{ 
		$stmt = $db->prepare($query); 
		$result = $stmt->execute($query_params); 
	} 
	catch(PDOException $ex){ die("Невозможно выполнить запрос2: " . $ex->getMessage()); } 
	
	$table['cols'] = array(
			array('label' => 'Время', 'type' => 'string'),
			array('label' => 'Заказы', 'type' => 'number'),
			array('label' => 'max', 'type' => 'number')
		);
		
	$rows = array();
	$res_item = "";
	$last_hour = -1;
	$order_count[$idx] = 0;	
	$tbl.="<tr><td>".$dstart->format('d.m.Y')."</td>";
	if($report_type == 1){
	while($r = $stmt->fetch() ) {	
		while (intval($r['created_hour']) > $last_hour+1) {
			$last_hour++;
			$rows[] = "['{$last_hour}',0,0]";			
			$tbl.="<td></td>";
		}
		$rows[] = "['{$r['created_hour']}',{$r['orders_count']},0]";
		$tbl.="<td>".$r['orders_count']."</td>";
		$order_count[$idx] += $r['orders_count'];
		$res_item = $r['item'];		
		$last_hour = $r['created_hour'];
	}
	while ($last_hour < 23) {
		$last_hour++;
		$rows[] = "['{$last_hour}',0,0]";		
		$tbl.="<td></td>";		
	}
	$tbl.="<td>".$order_count[$idx]."</td></tr>";
	$rows[] = "['',0,0]";	
    }	

	
	$rowsString = implode(',',$rows);

	$jsonTable = json_encode($table);
	
	?>		
			
  var data = new google.visualization.DataTable(<?=$jsonTable?>);

  var json = [<?php echo $rowsString; ?>];

	data.addRows(json);

  var options = {
		legend: 'none',
		colors: ['blue','white']
	};

  // Instantiate and draw our chart, passing in some options.
  //do not forget to check ur div ID
  var chart = new google.visualization.ColumnChart(document.getElementById('chart_div<?php echo $idx;?>'));
  chart.draw(data, options);
  
  <?php	$idx++; 
			$dstart = new DateTime($_GET['order_date'].' + '.$idx.' days'); ?>
	<?php } 
	}
	
	if($report_type == 2){
	$div_id = array();
	$orders_of_days = array(
	'Monday' => 0,
	'Tuesday' => 0,
	'Wednesday' => 0,
	'Thursday' => 0,
	'Friday' => 0,
	'Saturday' => 0,
	'Sunday' => 0,
	'print_table' => 0,
	);
	$tbl_r = '';
	$tbl_ra = '';
	$start_week = 0;
	$end_week = 0;
	$cnt_orders = 0;
	$order_count = array();
	while ($dend >= $dstart) { 

		if($_GET['item_id']){
		$query = " 
				SELECT 

					".($_GET['type']=='items' ? "SUM(orders.item_count)" : "COUNT(*)")." AS orders_count
				FROM orders, users as owner
				WHERE 
				(:order_date IS NULL OR :order_date = '' OR DATE(orders.created_at) = :order_date) AND
					orders.item_id = :item_id AND
					(:status_id IS NULL OR :status_id = '0' OR orders.status_step1 = :status_id OR orders.status_step2 = :status_id OR orders.status_step3 = :status_id) AND
					(orders.status_step1 <> 40 AND orders.status_step2 <> 40 AND orders.status_step3 <> 40) AND
					owner.id = orders.owner_id AND
					(:seller_id = '0' OR owner.id = :seller_id)";
			$query_params = array( 
				':item_id' => $_GET['item_id'],
				':seller_id' => $_GET['seller_id'],
				':status_id' => $_GET['status_id'],
				':order_date' => $dstart->format('Y-m-d'),
			); 
		}
		else{
			$query = " 
				SELECT 
					DATE_FORMAT(created_at, '%e') AS created_day,
					DATE_FORMAT(created_at, '%W') AS created_day_week,			
					item,
					".($_GET['type']=='items' ? "SUM(orders.item_count)" : "COUNT(*)")." AS orders_count
				FROM orders, users as owner
				WHERE 
					(:order_date IS NULL OR :order_date = '' OR DATE(orders.created_at) = :order_date) AND
					(:item_id IS NULL OR :item_id = '0' OR orders.item IN (SELECT name FROM item WHERE uuid = :item_id)) AND
					(:status_id IS NULL OR :status_id = '0' OR orders.status_step1 = :status_id OR orders.status_step2 = :status_id OR orders.status_step3 = :status_id) AND
					(orders.status_step1 <> 40 AND orders.status_step2 <> 40 AND orders.status_step3 <> 40) AND
					owner.id = orders.owner_id AND
					(:seller_id = '0' OR owner.id = :seller_id)					
				GROUP BY created_day".($_GET['item_id'] ? ", item" : "");	
			
			$query_params = array( 
				':item_id' => $_GET['item_id'],
				':seller_id' => $_GET['seller_id'],
				':status_id' => $_GET['status_id'],
				':order_date' => $dstart->format('Y-m-d'),
			); 
			}
			
	 
	try{ 
		$stmt = $db->prepare($query); 
		$result = $stmt->execute($query_params); 
	} 
	catch(PDOException $ex){ die("Невозможно выполнить запрос2: " . $ex->getMessage()); } 
	
	$table['cols'] = array(
			array('label' => 'Время', 'type' => 'string'),
			array('label' => 'Заказы', 'type' => 'number'),
			array('label' => 'max', 'type' => 'number')
		);
		
	$rows = array();
	$res_item = "";

	
	$cnt_result = 0;
	while($r = $stmt->fetch() ) {
		$cnt_result = 1;
        $cur_day = $dstart->format('l');	
		$orders_of_days[$cur_day] = $r['orders_count']?$r['orders_count']:0;
		
		$order_count[$zet] += $r['orders_count']?$r['orders_count']:0;
		$cnt_orders += $r['orders_count']; 
		
	}
	$cur_day = $dstart->format('l');
	if($cnt_result==0){
		$orders_of_days[$cur_day]= 0;
	}
	$orders_of_days['print_table'] = 1;

    if($start_week==0){
			$start_week = $dstart->format('j');
		}
		$end_week = $dstart->format('j');
		if($cur_day == 'Sunday'){
		
		$rows[] = "['Пн',{$orders_of_days['Monday']},0]";
		
		$rows[] = "['Вт',{$orders_of_days['Tuesday']},0]";

		$rows[] = "['Ср',{$orders_of_days['Wednesday']},0]";
		
		$rows[] = "['Чт',{$orders_of_days['Thursday']},0]";

        $rows[] = "['Пт',{$orders_of_days['Friday']},0]";
					
		$rows[] = "['Сб',{$orders_of_days['Saturday']},0]";
		
    	$rows[] = "['Вс',{$orders_of_days['Sunday']},0]";
		
		$rows[] = "['',0,0]";
		
		$tbl_r .= "<tr><td>".$start_week."-".$end_week.".".$dstart->format('m')."</td>";
        $tbl_r .= "<td>".$orders_of_days['Monday']."</td>";
		$tbl_r .= "<td>".$orders_of_days['Tuesday']."</td>";
		$tbl_r .= "<td>".$orders_of_days['Wednesday']."</td>";
		$tbl_r .= "<td>".$orders_of_days['Thursday']."</td>";
		$tbl_r .= "<td>".$orders_of_days['Friday']."</td>";
		$tbl_r .= "<td>".$orders_of_days['Saturday']."</td>";
		$tbl_r .= "<td>".$orders_of_days['Sunday']."</td>";
		$tbl_r .= "<td><strong>".$cnt_orders."</strong></td></tr>";
		$start_week=0;
		$cnt_orders = 0; 
		$orders_of_days = array(
			'Monday' => 0,
			'Tuesday' => 0,
			'Wednesday' => 0,
			'Thursday' => 0,
			'Friday' => 0,
			'Saturday' => 0,
			'Sunday' => 0,
			'print_table' => 0,
		);
	$rowsString = implode(',',$rows);
	$jsonTable = json_encode($table);
    $rows = array();	
				
			
  echo "
  var data = new google.visualization.DataTable(".$jsonTable.");";
  echo "
  var json = [".$rowsString."];";
  echo "
  data.addRows(json);";
  echo "
  var options = {
		legend: 'none',
		colors: ['blue','white']
	};";
  echo "
  var chart = new google.visualization.ColumnChart(document.getElementById('chart_div".$zet."'));";	
  echo "
  chart.draw(data, options);";
  $zet++;
  }
  ?>

			
	 <?php	$idx++; 
			$dstart = new DateTime($_GET['order_date'].' + '.$idx.' days'); ?>		
     <?php
	}
    if($orders_of_days['print_table']==1){
		$rows[] = "['Пн',{$orders_of_days['Monday']},0]";
		
		$rows[] = "['Вт',{$orders_of_days['Tuesday']},0]";

		$rows[] = "['Ср',{$orders_of_days['Wednesday']},0]";
		
		$rows[] = "['Чт',{$orders_of_days['Thursday']},0]";

        $rows[] = "['Пт',{$orders_of_days['Friday']},0]";
					
		$rows[] = "['Сб',{$orders_of_days['Saturday']},0]";
		
    	$rows[] = "['Вс',{$orders_of_days['Sunday']},0]";
		
		$rows[] = "['',0,0]";
		$tbl_r .= "<tr><td>".$start_week."-".$end_week.".".$dstart->format('m')."</td>";
		$tbl_r .= "<td>".$orders_of_days['Monday']."</td>";
		$tbl_r .= "<td>".$orders_of_days['Tuesday']."</td>";
		$tbl_r .= "<td>".$orders_of_days['Wednesday']."</td>";
		$tbl_r .= "<td>".$orders_of_days['Thursday']."</td>";
		$tbl_r .= "<td>".$orders_of_days['Friday']."</td>";
		$tbl_r .= "<td>".$orders_of_days['Saturday']."</td>";
		$tbl_r .= "<td>".$orders_of_days['Sunday']."</td>";
		$tbl_r .= "<td><strong>".$cnt_orders."</strong></td></tr>";
		
	    $rowsString = implode(',',$rows);
	$jsonTable = json_encode($table);
    $rows = array();	
				
			
  echo "
  var data = new google.visualization.DataTable(".$jsonTable.");";
  echo "
  var json = [".$rowsString."];";
  echo "
  data.addRows(json);";
  echo "
  var options = {
		legend: 'none',
		colors: ['blue','white']
	};";
  echo "
  var chart = new google.visualization.ColumnChart(document.getElementById('chart_div".$zet."'));";	
  echo "
  chart.draw(data, options);";
  $zet++;
	?>		

	
    
 
	<?php  
	}
	
	
	}
	if($report_type == 3){
	$tbl_r = '';
	$tbl_ra = '';
	$div_id = array();
	$print_table = 0;
	$orders_of_days = array();
	for($i=1;$i<32;$i++){
		$orders_of_days[$i] = 0;
	}
	$start_week=0;
	$cnt_orders = 0; 
	while ($dend >= $dstart) { 
	
		if($_GET['item_id']){
		$query = " 
				SELECT 

					".($_GET['type']=='items' ? "SUM(orders.item_count)" : "COUNT(*)")." AS orders_count
				FROM orders, users as owner
				WHERE 
				(:order_date IS NULL OR :order_date = '' OR DATE(orders.created_at) = :order_date) AND
					orders.item_id = :item_id AND
					(:status_id IS NULL OR :status_id = '0' OR orders.status_step1 = :status_id OR orders.status_step2 = :status_id OR orders.status_step3 = :status_id) AND
					(orders.status_step1 <> 40 AND orders.status_step2 <> 40 AND orders.status_step3 <> 40) AND
					owner.id = orders.owner_id AND
					(:seller_id = '0' OR owner.id = :seller_id)";
			$query_params = array( 
				':item_id' => $_GET['item_id'],
				':seller_id' => $_GET['seller_id'],
				':status_id' => $_GET['status_id'],
				':order_date' => $dstart->format('Y-m-d'),
			); 
		}
		else{
			$query = " 
				SELECT 
					DATE_FORMAT(created_at, '%e') AS created_day,
					DATE_FORMAT(created_at, '%W') AS created_day_week,			
					item,
					".($_GET['type']=='items' ? "SUM(orders.item_count)" : "COUNT(*)")." AS orders_count
				FROM orders, users as owner
				WHERE 
					(:order_date IS NULL OR :order_date = '' OR DATE(orders.created_at) = :order_date) AND
					(:item_id IS NULL OR :item_id = '0' OR orders.item IN (SELECT name FROM item WHERE uuid = :item_id)) AND
					(:status_id IS NULL OR :status_id = '0' OR orders.status_step1 = :status_id OR orders.status_step2 = :status_id OR orders.status_step3 = :status_id) AND
					(orders.status_step1 <> 40 AND orders.status_step2 <> 40 AND orders.status_step3 <> 40) AND
					owner.id = orders.owner_id AND
					(:seller_id = '0' OR owner.id = :seller_id)					
				GROUP BY created_day".($_GET['item_id'] ? ", item" : "");	
			
			$query_params = array( 
				':item_id' => $_GET['item_id'],
				':seller_id' => $_GET['seller_id'],
				':status_id' => $_GET['status_id'],
				':order_date' => $dstart->format('Y-m-d'),
			); 
			}
			
	 
	try{ 
		$stmt = $db->prepare($query); 
		$result = $stmt->execute($query_params); 
	} 
	catch(PDOException $ex){ die("Невозможно выполнить запрос2: " . $ex->getMessage()); } 
	
	$table['cols'] = array(
			array('label' => 'Время', 'type' => 'string'),
			array('label' => 'Заказы', 'type' => 'number'),
			array('label' => 'max', 'type' => 'number')
		);
		
	$rows = array();
	$res_item = "";

	$tbl.="<tr><td>".$dstart->format('d.m.Y')."</td>";
    $cnt_result = 0;
	while($r = $stmt->fetch() ) {
		$cnt_result = 1;
		$cnt_orders += $r['orders_count']; 
        $cur_day = $dstart->format('j');		
		$orders_of_days[$cur_day] = $r['orders_count'];
	
		$order_count[$zet] += $r['orders_count'];
		
	}
	$cur_day = $dstart->format('j');
	if($cnt_result==0){
		$orders_of_days[$cur_day]= 0;
	}
	$current_month = $dstart->format('n Y');
	$day_in_month = $dstart->format('t');
	$print_table = 1;
	
	
	
if($cur_day == $day_in_month){
		
		$tbl_r .= '<tr><td>'.$current_month.'</td>';
		for($i=1;$i<=$day_in_month;$i++){
			$cnt_day = $orders_of_days[$i];
			$rows[] = "['{$i}',{$cnt_day},0]";
			$tbl_r .= '<td>'.$orders_of_days[$i].'</td>';
	    }
		if($day_in_month<31){
			$blank_col = 31-$day_in_month;
			for($i=0;$i<$blank_col;$i++){
				$tbl_r .= '<td></td>';
			}
		}
		
		$tbl_r .= '<td><strong>'.$cnt_orders.'</strong></td></tr>';
		
		$rows[] = "['',0,0]";
		$cnt_orders = 0;
		$print_table = 0;
        
		
		
		
		$orders_of_days = array();
	for($i=1;$i<32;$i++){
		$orders_of_days[$i] = 0;
	}
	$rowsString = implode(',',$rows);
	$jsonTable = json_encode($table);
    $rows = array();	
				
			
  echo "
  var data = new google.visualization.DataTable(".$jsonTable.");";
  echo "
  var json = [".$rowsString."];";
  echo "
  data.addRows(json);";
  echo "
  var options = {
		legend: 'none',
		colors: ['blue','white']
	};";
  echo "
  var chart = new google.visualization.ColumnChart(document.getElementById('chart_div".$zet."'));";	
  echo "
  chart.draw(data, options);";
  $zet++;
  ?>

	


    <?php
		
		
		
		}

	
		?>		
			
	 <?php	$idx++; 
			$dstart = new DateTime($_GET['order_date'].' + '.$idx.' days'); ?>		
     <?php
	}
    if($print_table==1){
		$tbl_r .= '<tr><td>'.$current_month.'</td>';
		for($i=1;$i<=$day_in_month;$i++){
			$cnt_day = $orders_of_days[$i];
			$rows[] = "['{$i}',{$cnt_day},0]";
			$tbl_r .= '<td>'.$orders_of_days[$i].'</td>';
	    }
		if($day_in_month<31){
			$blank_col = 31-$day_in_month;
			for($i=0;$i<$blank_col;$i++){
				$tbl_r .= '<td></td>';
			}
		}
		$rows[] = "['',0,0]";
		$tbl_r .= '<td><strong>'.$cnt_orders.'</strong></td></tr>';
		$print_table = 0;
		

	    $rowsString = implode(',',$rows);
	$jsonTable = json_encode($table);
    $rows = array();	
				
			
  echo "
  var data = new google.visualization.DataTable(".$jsonTable.");";
  echo "
  var json = [".$rowsString."];";
  echo "
  data.addRows(json);";
  echo "
  var options = {
		legend: 'none',
		colors: ['blue','white']
	};";
  echo "
  var chart = new google.visualization.ColumnChart(document.getElementById('chart_div".$zet."'));";	
  echo "
  chart.draw(data, options);";
  $zet++;
	?>		

	
    
 
	<?php  
	}
	
	
	}
	}
	$orders_count_sum = 0;
	if (($_GET['report_type'] == 1) || (!($_GET['report_type']))) {
		$query = " 
				SELECT 
					DATE_FORMAT(created_at, '%H') AS created_hour,
					".($_GET['item_id'] ? "item, " : "")."
					".($_GET['type']=='items' ? "SUM(orders.item_count)" : "COUNT(*)")." AS orders_count
				FROM orders, users as owner
				WHERE 
					(:order_date IS NULL OR :order_date = '' OR DATE(orders.created_at) >= :order_date) AND
					(:order_date_end IS NULL OR :order_date_end = '' OR DATE(orders.created_at) <= :order_date_end) AND
					(:item_id IS NULL OR :item_id = '0' OR orders.item_id = :item_id) AND
					(:status_id IS NULL OR :status_id = '0' OR orders.status_step1 = :status_id OR orders.status_step2 = :status_id OR orders.status_step3 = :status_id) AND
					(orders.status_step1 <> 40 AND orders.status_step2 <> 40 AND orders.status_step3 <> 40) AND
					owner.id = orders.owner_id AND
					(:seller_id = '0' OR owner.id = :seller_id)					
				GROUP BY created_hour".($_GET['item_id'] ? ", item" : "");	
				//echo $query;	
		}
		elseif ($_GET['report_type'] == 2) {
			$query = "SELECT 
					DATE_FORMAT(created_at, '%W') AS created_hour,
					".($_GET['item_id'] ? "item, " : "")."
					".($_GET['type']=='items' ? "SUM(orders.item_count)" : "COUNT(*)")." AS orders_count
				FROM orders, users as owner
				WHERE 
					(:order_date IS NULL OR :order_date = '' OR DATE(orders.created_at) >= :order_date) AND
					(:order_date_end IS NULL OR :order_date_end = '' OR DATE(orders.created_at) <= :order_date_end) AND
					(:item_id IS NULL OR :item_id = '0' OR orders.item_id = :item_id) AND
					(:status_id IS NULL OR :status_id = '0' OR orders.status_step1 = :status_id OR orders.status_step2 = :status_id OR orders.status_step3 = :status_id) AND
					(orders.status_step1 <> 40 AND orders.status_step2 <> 40 AND orders.status_step3 <> 40) AND
					owner.id = orders.owner_id AND
					(:seller_id = '0' OR owner.id = :seller_id)					
				GROUP BY created_hour".($_GET['item_id'] ? ", item" : "");

		}
		elseif ($_GET['report_type'] == 3) {
			$query = " 
				SELECT 
					DATE_FORMAT(created_at, '%e') AS created_hour,
					".($_GET['item_id'] ? "item, " : "")."
					".($_GET['type']=='items' ? "SUM(orders.item_count)" : "COUNT(*)")." AS orders_count
				FROM orders, users as owner
				WHERE 
					(:order_date IS NULL OR :order_date = '' OR DATE(orders.created_at) >= :order_date) AND
					(:order_date_end IS NULL OR :order_date_end = '' OR DATE(orders.created_at) <= :order_date_end) AND
					(:item_id IS NULL OR :item_id = '0' OR orders.item_id = :item_id) AND
					(:status_id IS NULL OR :status_id = '0' OR orders.status_step1 = :status_id OR orders.status_step2 = :status_id OR orders.status_step3 = :status_id) AND
					(orders.status_step1 <> 40 AND orders.status_step2 <> 40 AND orders.status_step3 <> 40) AND
					owner.id = orders.owner_id AND
					(:seller_id = '0' OR owner.id = :seller_id)					
				GROUP BY created_hour".($_GET['item_id'] ? ", item" : "");	
		}


		$query_params = array( 
				':item_id' => $_GET['item_id'],
				':seller_id' => $_GET['seller_id'],
				':status_id' => $_GET['status_id'],
				':order_date' => $_GET['order_date'],
				':order_date_end' => $_GET['order_date_end'] ? $_GET['order_date_end'] : $_GET['order_date']
			); 
	
	 
	try{ 
		$stmt = $db->prepare($query); 
		$result = $stmt->execute($query_params); 
	} 
	catch(PDOException $ex){ die("Невозможно выполнить запрос3: " . $ex->getMessage()); } 
	
	$table['cols'] = array(
			array('label' => 'Время', 'type' => 'string'),
			array('label' => 'Заказы', 'type' => 'number'),
			array('label' => 'max', 'type' => 'number')
		);
		
	$rows = array();
	$rows_avg = array();
	
	
	if($report_type == 1){
	$res_item = "";
	$last_hour = -1;
	$tbl_r = '<tr><td>Всего</td>';
	$tbl_ra = '<tr><td>Среднее</td>';
	while($r = $stmt->fetch() ) {	
        
		while (intval($r['created_hour']) > $last_hour+1) {
			$last_hour++;
			$rows[] = "['{$last_hour}',0,0]";			
			$rows_avg[] = "['{$last_hour}',0,0]";		
			$tbl_r.="<td></td>";
			$tbl_ra.="<td></td>";
		}
		$rows[] = "['{$r['created_hour']}',{$r['orders_count']},0]";
		$tbl_r.="<td>".$r['orders_count']."</td>";
		$tcnt = 1;
		if(count($order_count) > 0) {$tcnt = number_format($r['orders_count']/count($order_count),2);}
		$rows_avg[] = "['{$r['created_hour']}',{$tcnt},0]";
		$tbl_ra.="<td>".$tcnt."</td>";
		$orders_count_sum += $r['orders_count'];
		$res_item = $r['item'];		
		$last_hour = $r['created_hour'];
	}
	while ($last_hour < 23) {
		$last_hour++;
		$rows[] = "['{$last_hour}',0,0]";	
		$rows_avg[] = "['{$last_hour}',0,0]";					
		$tbl_r.="<td></td>";
		$tbl_ra.="<td></td>";
	}
    $tbl_r.="<td>".$orders_count_sum."</td></tr>";
	$tbl_ra.="<td>".(count($order_count)>0 ? number_format($orders_count_sum/count($order_count),2) : '')."</td></tr>";
	
	$rows[] = "['',0,0]";
	$rows_avg[] = "['',0,0]";

    }
	
	
	
	if($report_type == 2){

	$orders_days = array(
	'Monday' => 0,
	'Tuesday' => 0,
	'Wednesday' => 0,
	'Thursday' => 0,
	'Friday' => 0,
	'Saturday' => 0,
	'Sunday' => 0,
	);
        while($r = $stmt->fetch()) {
			$orders_days[$r['created_hour']] += $r['orders_count'];
			$orders_count_sum += $r['orders_count'];
		}
       
		
		foreach ($orders_days as $day_key => $count){
				$table_day.="<td>{$count}</td>";
			}
		
		$rows[] = "['Пн',{$orders_days['Monday']},0]";

		
		$rows[] = "['Вт',{$orders_days['Tuesday']},0]";

		
		$rows[] = "['Ср',{$orders_days['Wednesday']},0]";

		
		$rows[] = "['Чт',{$orders_days['Thursday']},0]";


        $rows[] = "['Пт',{$orders_days['Friday']},0]";
		
			
		$rows[] = "['Сб',{$orders_days['Saturday']},0]";

		
    	$rows[] = "['Вс',{$orders_days['Sunday']},0]";

		
		$rows[] = "['',0,0]";

		

			

		$res_item = "";
		
	}

	if($report_type == 3){
	$table_day = "";
	$table_head = "";
	$orders_days = array();
        while($r = $stmt->fetch()) {
			$orders_days[$r['created_hour']] += $r['orders_count'];
			$orders_count_sum += $r['orders_count'];
		}
		
      
		
		for($i=1;$i<32;$i++){
		  $day_type = $orders_days[$i]>0?$orders_days[$i]:0;
		  $rows[] .= "['$i',$day_type,0]";
		  $table_head.="<th style='width: 30px;'>$i</th>";
		  $table_day.="<td>$orders_days[$i]</td>";
		}

		
		$rows[] = "['',0,0]";
	
		$res_item = "";
		
	}

	$rowsString = implode(',',$rows);
	$rowsString_avg = implode(',',$rows_avg);
	$jsonTable = json_encode($table);
	
	?>
	
	var data = new google.visualization.DataTable(<?=$jsonTable?>);
	var data_avg = new google.visualization.DataTable(<?=$jsonTable?>);

  var json = [<?php echo $rowsString; ?>];
  var json_avg = [<?php echo $rowsString_avg; ?>];

	data.addRows(json);
	data_avg.addRows(json_avg);
	
	var options = {
		legend: 'none'
	};

  // Instantiate and draw our chart, passing in some options.
  //do not forget to check ur div ID
  var chart = new google.visualization.ColumnChart(document.getElementById('chart_div'));
  chart.draw(data, options);
  
  var chart_avg = new google.visualization.ColumnChart(document.getElementById('chart_div_avg'));
  chart_avg.draw(data_avg, options);
	
}

</script>

<?php
if (!$_GET['item_id']) {
		$query = " 
				SELECT 
					COALESCE(item.name, orders.item) as item_name,
					owner.username as username,
					".($_GET['type']=='items' ? "SUM(orders.item_count)" : "COUNT(*)")." AS orders_count
				FROM orders LEFT OUTER JOIN item ON item_id = item.uuid, users as owner
				WHERE 
					(:order_date IS NULL OR :order_date = '' OR DATE(orders.created_at) >= :order_date) AND
					(:order_date_end IS NULL OR :order_date_end = '' OR DATE(orders.created_at) <= :order_date_end) AND
					(:status_id IS NULL OR :status_id = '0' OR orders.status_step1 = :status_id OR orders.status_step2 = :status_id OR orders.status_step3 = :status_id) AND
					(orders.status_step1 <> 40 AND orders.status_step2 <> 40 AND orders.status_step3 <> 40) AND
					owner.id = orders.owner_id AND
					(:seller_id = '0' OR owner.id = :seller_id)					
				GROUP BY item_name, owner.username ORDER BY orders_count DESC";		
				
		$query_params = array( 
				':seller_id' => $_GET['seller_id'],
				':status_id' => $_GET['status_id'],
				':order_date' => $_GET['order_date'],
				':order_date_end' => $_GET['order_date_end'] ? $_GET['order_date_end'] : $_GET['order_date']
			); 
	
	 
	try{ 
		$stmt = $db->prepare($query); 
		$result = $stmt->execute($query_params); 
	} 
	catch(PDOException $ex){ die("Невозможно выполнить запрос4: " . $ex->getMessage()); } 
	
	$tbl_items = '';
	while($r = $stmt->fetch() ) {	
		$tbl_items.="<tr><td>".$r['item_name']."</td><td>".$r['username']."</td><td>".$r['orders_count']."</td><tr>";
	}
	  	
}
?>

<div class="container">
	<a href="reports.php">&larr; Вернуться в меню отчетов</a>
	<form action="report_orders.php">
		<?php if ($_GET['archive']) { ?>
			<input type='hidden' name='archive' value='<?php echo $_GET['archive']?>'>
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
		
		<select class="form-control" name="type" style="width:35%" onchange="$(this).closest('form').trigger('submit');">
			<option value="orders">Подсчет количества заказов</option>
			<option <?php if($_GET['type']=="items") {echo "selected=\"selected\"";} ?> value="items">Подсчет единиц товара</option>
		</select>

		<select class="form-control" name="report_type" style="width:35%" onChange="$(this).closest('form').trigger('submit');">
			<option value=1>По часам</option>
			<option value=2 <?php if($_GET['report_type']==2) {echo "selected=\"selected\"";} ?> value="items">По дням недели</option>
			<option value=3 <?php if($_GET['report_type']==3) {echo "selected=\"selected\"";} ?> value="items">По числам месяца</option>
		</select>
	</form>

	<h3><?php echo $act_type; if ($_GET['item_id']) { echo ' по '.$res_item; }?></h3>
	<table>
	
	<?php	$dstart = new DateTime($_GET['order_date']);
			$dend = new DateTime((!$_GET['order_date_end'] or $_GET['order_date_end'] == '') ? $_GET['order_date'] : $_GET['order_date_end']);	
	?>
	
	<?php if (count($order_count) > 1) { ?>
	<tr><td><?php echo 'За период:<br/>'.$dstart->format('d.m.Y').' -<br/>'.$dend->format('d.m.Y'); ?></td>
	<td><div id="chart_div" style="width:650px; height:120px;"></div></td><td><?php echo 'Всего:<br/>'.$orders_count_sum; ?></td></tr>
	<?php } 
	if (count($order_count)==0) { ?>
	<tr><td><?php echo 'За все время'; ?></td>	
	<td><div id="chart_div" style="width:650px; height:120px;"></div></td><td><?php echo 'Всего:<br/>'.$orders_count_sum; ?></td></tr>
	<?php } ?>
	
	<?php if (count($order_count) > 1) { 
		$orders_count_sum_f = 0;
		$order_count_f = 0;
		foreach ($order_count as $idx_o=>$ord1) {
			if ($idx_o != (count($order_count)-1)) {
				$orders_count_sum_f += $ord1;
				$order_count_f += 1;
			}
		}
		$dnow = new DateTime();
	if($report_type == 1){
	?>
	<tr><td><?php echo 'В среднем'; ?></td>
	<td><div id="chart_div_avg" style="width:650px; height:120px;"></div></td><td><?php if ($dend->format('d-m-y') == $dnow->format('d-m-y')) {echo 'Среднее за сутки (без неполного дня):<br/>'.number_format($orders_count_sum_f/$order_count_f,2).'<br/>'; } ?><?php echo 'Среднее за сутки (общее):<br/>'.number_format($orders_count_sum/count($order_count),2); ?></td></tr>
	<tr><td>&nbsp;</td></tr>
	<?php }}

	if ($_GET['order_date'] and $_GET['order_date'] != '') {
	$start_week = 0;
	$end_week = 0;
	$start_month = 0; 
	$end_month = 0; 
	$idx = 0;
	$print_month = 0;
	while ($dend >= $dstart) { 
	if($report_type == 1){
	?>		
			<tr><td><?php echo str_replace(" ","<br/>",$dstart->format('d.m.Y (D)')); ?></td>
			<td><div id="chart_div<?php echo $idx?>" style="width:650px; height:120px;"></div></td><td><?php echo 'Всего:<br/>'.$order_count[$idx];?></td>
	<?php	$idx++;
			$dstart = new DateTime($_GET['order_date'].' + '.$idx.' days'); }?>
	<?php  
	
	if($report_type == 2){
    
	$print_week = 0;
			$days_in_month = $dstart->format('t');
			$day_week = $dstart->format('w');
			$day_month = $dstart->format('j');
            $current_month = $dstart->format('m');
			
			if($start_week==0){
				$start_week = $dstart->format('d');
				$start_month = $dstart->format('m');
			}


				$end_week = $dstart->format('d.m');

			if($day_week==0){

				if($start_month == $current_month){
					$print_week = $start_week."-".$end_week.".".$dstart->format('Y');
				}
				else {
					$print_week = $start_week.".".$start_month."-".$end_week.".".$dstart->format('Y');
				}
				$start_week = 0;
	            $end_week = 0;
			}
			 
	        if($print_week != 0){	
	
	?>

	
			<tr><td><?php echo $print_week; ?></td>
			<td><div id="chart_div<?php echo $count_z;?>" style="width:650px; height:120px;"></div></td><td><?php  if($order_count[$count_z]>0){echo 'Всего:<br/>'.$order_count[$count_z];}else echo 'Всего:<br/>0';$count_z++;?></td>
	<?php
	    }
		
        $idx++;
		$dstart = new DateTime($_GET['order_date'].' + '.$idx.' days');}
		//3 start
		if($report_type == 3){
    
	        $print_month = 0;
			$days_in_month = $dstart->format('t');
            $current_day = $dstart->format('j');
			$current_month = $dstart->format('m');
			
			if($start_week==0){

				$start_month = $dstart->format('d');
			}


				$end_month = $dstart->format('d.m');

			if($current_day == $days_in_month){

			
				$print_month = $current_month.$dstart->format('.Y');

				$start_month = 0;
	            $end_month = 0;
			}
			 
	        if($print_month != 0){	
	
	?>

	
			<tr><td><?php echo $print_month; ?></td>
			<td><div id="chart_div<?php echo $count_z;?>" style="width:650px; height:120px;"></div></td><td><?php if($order_count[$count_z]>0){echo 'Всего:<br/>'.$order_count[$count_z];}else echo 'Всего:<br/>0';$count_z++;?></td>
	<?php
	    }
		
        $idx++;
		$dstart = new DateTime($_GET['order_date'].' + '.$idx.' days');}
		//3 end
		 }
		?>
	<?php 
	if($report_type == 2){
	  if($start_week!=0){
		?><tr><td><?php echo $start_week."-".$end_week.".".$dstart->format('Y'); ?></td>
		<td><div id="chart_div<?php echo $count_z;?>" style="width:650px; height:120px;"></div></td><td><?php if($order_count[$count_z]>0){echo 'Всего:<br/>'.$order_count[$count_z];}else echo 'Всего:<br/>0';$count_z++;?></td>
		
		<?php 
		
	  }
	}
	if($report_type == 3){
	  if($start_month!=0){
		?><tr><td><?php echo $dstart->format('m.Y'); ?></td>
		<td><div id="chart_div<?php echo $count_z;?>" style="width:650px; height:120px;"></div></td><td><?php echo 'Всего:<br/>'.$order_count[$count_z];?></td>
		
		<?php 
		
	  }
	}
	
	
	}
	 ?>
	 
	</table>	
	<br/><br/>		
	<table cellpadding='2' border='1' style="table-layout: fixed; border-collapse: collapse; border: 1px solid black;">
	<?php 
	if($report_type == 1){
	echo "<tr><th style='width: 70px;'>Часы</th>";
	for ($hr=0; $hr < 24; $hr++) { echo '<th  style="width: 30px;">'.$hr.'</th>';}}
	if($report_type == 2){
	echo "<tr><th style='width: 70px;'>День</th>";
	echo '<th  style="width: 30px;">Пн</th><th  style="width: 30px;">Вт</th><th  style="width: 30px;">Ср</th><th  style="width: 30px;">Чт</th><th  style="width: 30px;">Пн</th><th  style="width: 30px;">Сб</th><th  style="width: 30px;">Вс</th>';
	echo "<th>Всего</th></tr><th></th>".$table_day."<td><strong>".$orders_count_sum."</strong></td></table>";
	}
	if($report_type == 3){
	echo "<tr><th style='width: 70px;'>День</th>";
	echo $table_head;
	echo "<th>Всего</th></tr><td>Всего</td>".$table_day."<td><strong>".$orders_count_sum."</strong></td></table>";
	}
	?>
	
	
		<?php if($report_type == 1){echo "<th>Всего</th></tr>".((count($order_count) != 1) ? $tbl_r : '').((count($order_count) > 1) ? $tbl_ra : '').$tbl."</table>";} 
		if($report_type == 2){?>
		<br><table cellpadding='2' border='1' style="border-collapse: collapse; border: 1px solid black; width: 500px;">
		<?php
		echo "<tr><th style='width:120px;'>Дата</th><th>Пн</th><th>Вт</th><th>Ср</th><th>Чт</th><th>Пт</th><th>Сб</th><th>Вс</th><th>Всего</th></tr>".$tbl_r."</table>";
		}
		
		if($report_type == 3){?>
		<br><table cellpadding='2' border='1' style="border-collapse: collapse; border: 1px solid black; width: 1000px;">
		<?php
		echo "<tr><th style='width:120px;'>Дата</th>";
		for($i=1;$i<32;$i++){
		  echo "<th style='width:40px;'>".$i."</th>";
		}
		echo "<th>Всего</th></tr>".$tbl_r."</table>";
		}
		?>
		
		
	<br/>
	<table cellpadding='2' border='1' style="border-collapse: collapse; border: 1px solid black;">
		<?php echo $tbl_items;?>
	</table>

</div>
</body>
</html>