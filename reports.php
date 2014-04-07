<?php
	require("config.php"); 	

	$order_count = array();
	$max_cnt = NULL;
	$tbl = '';
	
	if (empty($_SESSION['user']) || $_SESSION['user']['active'] == 0 || $_SESSION['user']['group_id'] != 2) {
		header("Location: index.php"); 
		die("Перенаправление: index.php"); 
	}
?>	

<!doctype html>
<html lang="ru">
<?php include 'header.php' ?>
<body>
<?php include 'top_menu.php' ?>

<!--Load the AJAX API-->
<script type="text/javascript" src="https://www.google.com/jsapi"></script>
<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.8.2/jquery.min.js"></script>

<div class="container">

	<h3>Отчёты</h3><br/>
	<p><a href='report_operator.php?order_date=<?php $cdt = new DateTime(); echo($cdt->format('Y-m-d')); // echo date("Y-m-d", strtotime('last week last sunday + 1 day')).'&order_date_end='.date("Y-m-d", strtotime('last week last sunday + 7 day'));?>' class='btn btn-default'>Отчёт по работе операторов</a></p>
	<p><a href='report_orders.php?order_date=<?php $cdt = new DateTime(); echo($cdt->format('Y-m-d'));?>' class='btn btn-default'>Отчёт по времени оформления заказа и товарам</a></p>
	<p><a class="btn btn-default" href="report_money.php">Отчёт по периоду оплаты заказов</a></p>
	<p><a class="btn btn-default" href="report_backsent.php">Отчёт по оформленным возвратам</a></p>
	<p><a class="btn btn-default" href="report_delayed.php">Отчёт по хвостам операторов</a></p>
	<p><a class="btn btn-default" href="report_unpaid_sent.php">Отчёт по неоплаченным отправленным</a></p>
    <p><a href='report_list_sent_goods.php?order_date=<?php $cdt = new DateTime(); echo($cdt->format('Y-m-d'));?>' class='btn btn-default'>Отчет по отправленным товарам</a></p>
	<p><a href='report_convers.php?order_date=<?php echo date("Y-m-d", strtotime('last month')).'&order_date_end='.date("Y-m-d", strtotime('last month + 30 day')); ?>' class='btn btn-default'>Конверсия</a></p>
</div>
</body>
</html>