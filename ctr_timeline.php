<?php
header('Content-Type: text/html; charset=utf-8');
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT"); 
header("Cache-Control: no-store, no-cache, must-revalidate"); 
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

require("config.php"); 

$rows = array();
$table = array();

$filter = '';
if ($_GET['id']) {
	$filter = "WHERE id IN (:id)";
	$query_params = array(
		':id' => $_GET['id']
	);
}

		$query = "SELECT time, ROUND(UNIX_TIMESTAMP(time)/(5 * 60)) AS timekey, UNIX_TIMESTAMP(min(time)) as period_start, SUM(dspent) as spent, SUM(dclicks) as clicks, SUM(dshows)/1000 as shows, shows as fshows, AVG(price)*10 AS price, price as fprice, AVG(ctr)*1000 AS ctr, ctr as fctr FROM ctr_log ".$filter." GROUP BY timekey ORDER BY timekey DESC"; 
		$table['cols'] = array(
			array('label' => 'Начало периода', 'type' => 'datetime'),
			array('label' => 'Потрачено', 'type' => 'number'),
			array('label' => 'Переходы', 'type' => 'number'),
			array('label' => 'Показы (тыс)', 'type' => 'number'),
			array('label' => 'Цена за клик (1/10)', 'type' => 'number'),
			array('label' => 'CTR (1/1000)', 'type' => 'number')
		);
		try{ 
			$stmt = $db->prepare($query); 
			$result = $stmt->execute($query_params); 
		} 
		catch(PDOException $ex){ die("Невозможно выполнить запрос: " . $ex->getMessage()); } 			
		
		$rows = array();
		$res_tab = "";
		while($r = $stmt->fetch() ) {
			$rows[] = "[".$r['period_start'].",{$r['spent']},{$r['clicks']},{$r['shows']},{$r['price']},{$r['ctr']}]";
			$res_tab .= "<tr><td>".$r['time']."</td><td>".number_format($r['spent'],2,'.','')."</td><td>".$r['clicks']."</td><td>".$r['fshows']."</td><td>".number_format($r['fprice'],2,'.','')."</td><td>".$r['fctr']."</td></tr>";
		}

$rowsString = implode(',',$rows);

$jsonTable = json_encode($table);

$act_type = 'Vkontakte реклама';	
?>

<html>
  <head>
    <!--Load the AJAX API-->
    <script type="text/javascript" src="https://www.google.com/jsapi"></script>
    <script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.8.2/jquery.min.js"></script>
    <script type="text/javascript">

    // Load the Visualization API and the piechart package.
    google.load('visualization', '1', {'packages':['AnnotatedTimeLine']});

    // Set a callback to run when the Google Visualization API is loaded.
    google.setOnLoadCallback(drawChart);

    function drawChart() {

      // Create our data table out of JSON data loaded from server.
      var data = new google.visualization.DataTable(<?=$jsonTable?>);

      var json = [<?php echo $rowsString; ?>]
      for ( var i = 0; i < json.length; i++ ) {
          json[i][0] = new Date( json[i][0] * 1000 );
      }
		data.addRows(json);

      var options = {
        };

      // Instantiate and draw our chart, passing in some options.
      //do not forget to check ur div ID
      var chart = new google.visualization.AnnotatedTimeLine(document.getElementById('chart_div'));
      chart.draw(data, options);
    }

    </script>
  </head>

  <body>
    <h3><?php echo $act_type; if ($_GET['id']) { echo ' по id '.$_GET['id']; }?></h3>
    <form action='ctr_timeline.php'>
		<select name='id'>
			<?php $query = "SELECT DISTINCT id FROM ctr_log";
			try{ 
				$stmt = $db->prepare($query); 
				$result = $stmt->execute(); 
			} 
			catch(PDOException $ex){ die("Невозможно выполнить запрос: " . $ex->getMessage()); } 			
			
			$rows = array();
			while($r = $stmt->fetch() ) { ?>
				<option value='<?php echo $r['id'] ?>'><?php echo $r['id']?></option>
			<?php } ?>
		</select>
		<input type='submit' value='показать'>
	</form>
	<!--Div that will hold the pie chart-->
    <div id="chart_div" style="width:1200px; height:600px;"></div>
	
	<table border=1 style="border-collapse: collapse; border: 1px solid black;">
	<tr><th>Начало периода</th><th>Потрачено</th><th>Переходы</th><th>Показы (тыс)</th><th>Цена за клик (1/10)</th><th>CTR (1/1000)</th>
		<?php echo $res_tab;?>
	</table>
  </body>
</html>	
