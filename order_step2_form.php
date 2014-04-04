<?php 
    require("config.php");     
	
	if (empty($_SESSION['user']) || $_SESSION['user']['active'] == 0) {
		header("Location: index.php"); 
		die("Перенаправление: index.php"); 
	}
	
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
				orders.id as id,
				orders.newpost_id as newpost_id,
				orders.newpost_backorder as newpost_backorder,
				orders.comment2 as comment2,
				orders.status_step2 as status_step2
		".($_SESSION['user']['group_id'] == 2 ? 
		"   FROM orders, users as owner
            WHERE 
				orders.owner_id = owner.id AND
				owner.id IN (SELECT subseller_id FROM sellers_for_sellers WHERE sellers_for_sellers.seller_id = :user_id) AND
				orders.id = :order_id" :
		"   FROM orders, operators_for_sellers
            WHERE 
                orders.owner_id = operators_for_sellers.seller_id AND
				operators_for_sellers.operator_id = :user_id AND
				orders.id = :order_id
        "); 
	$query_params = array( 
		':user_id' => $_SESSION['user']['id'],
		':order_id' => $_GET['id']
	); 
	 
	try{ 
		$stmt = $db->prepare($query); 
		$result = $stmt->execute($query_params); 
	} 
	catch(PDOException $ex){ die("Невозможно выполнить запрос: " . $ex->getMessage()); } 
	
	$ord = $stmt->fetch(); 
	
	if ($ord) {
		$query = "SELECT date, comment FROM orders_audit WHERE comment IS NOT NULL AND comment <> '' AND order_id = :order_id ORDER BY date ASC"; 
		$query_params = array( 
			':order_id' => $_GET['id']
		); 
		 
		try{ 
			$stmt = $db->prepare($query); 
			$result = $stmt->execute($query_params); 
		} 
		catch(PDOException $ex){ die("Невозможно выполнить запрос: " . $ex->getMessage()); } 
		
		$ord_comments = $stmt->fetchAll(); 
?>

<div class="modal-header">
	<?php if ($_SESSION['user']['group_id'] == 2) { ?><button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button><?php } ?>
  <h4 class="modal-title">Обработка заказов. Шаг 2 - Доставка товара</h4>
</div>
<form action='update_order_step2.php?r=1' method='POST'>
	<input type='hidden' name='id' value='<?php echo $ord['id']?>'>
	<?php if ($_GET['page']) { ?>
		<input type='hidden' name='page' value='<?php echo $_GET['page']?>'>
	<?php } ?>
	<?php if ($_GET['oper']) { ?>
		<input type='hidden' name='oper' value='<?php echo $_GET['oper']?>'>
	<?php } ?>
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
	<div class="modal-body">
		<div class="form-group">				
			<label>Статус</label>
			<select class="form-control" name="status_step2">
				<?php foreach ($statuses_step2 as $status) { ?>
					<option value='<?php echo $status['id']; ?>' <?php if ($ord['status_step2'] == $status['id']) { echo 'selected = selected'; } ?>><?php echo ($status['automatic']?'(АВТО) ':'').$status['name']; ?></option>													
				<?php } ?>
			</select>
		</div>
		<div class="form-group">				
			<label>Напомнить об этом заказе</label>
			<input class="form-control datetimepicker" <?php if ($ord['status_step2'] == 222) { echo "required='required'";}?> type='text' name='alert_at'>
		</div>										
		<div class="form-group">				
			<label>Номер декларации Новой почты</label>
			<input class="form-control" type='text' name='newpost_id' value='<?php echo $ord['newpost_id'] ?>'>
		</div>
		<div class="form-group">				
			<label>ВОЗВРАТ. Номер декларации Новой почты</label>
			<input class="form-control" type='text' name='newpost_backorder' value='<?php echo $ord['newpost_backorder'] ?>'>
		</div>
		<div class="form-group">				
			<?php if (!empty($ord_comments)) {?>
				<label>Комментарии</label>
				<div>
					<ul style="max-height: 100px; overflow: auto;">
						<?php foreach($ord_comments as $t_comment) { 
							$tcd = new DateTime($t_comment['date']);
							$tcd = $tcd->format('d-m-y H:i:s'); ?>						
							<li><?php echo $tcd.": ".$t_comment['comment'];?></li>
						<?php } ?>
					</ul>
				</div>
			<?php } ?>
			<label>Добавить комментарий *</label>
			<input <?php if ($_SESSION['user']['group_id'] != 2) { ?>required=required <?php } ?> type='text' style='width: 500px;' class="form-control" name='comment2' value=''>
		</div>										
	</div>				
	<div class="modal-footer">
		<?php if ($_SESSION['user']['group_id'] == 2) { ?><button type="button" class="btn btn-default" data-dismiss="modal">Закрыть</button><?php } ?>
	  <input type="submit" class="btn btn-primary" value='Сохранить'>
	</div>
</form>

<script type='text/javascript'>
	$('#myModalStep2<?php echo $ord['id'];?> .datetimepicker').datetimepicker({
		format: 'yyyy-mm-dd hh:ii',
		autoclose: true,
        todayBtn: true,
		startView: 1,
		language: 'ru',
		weekStart: 1
	  });

	$('select[name="status_step2"]').change(function() {
		if ( $(this).val() == 222) {
			$(this).parent().parent().find('input[name=alert_at]').attr('required','required');
		} else {
			$(this).parent().parent().find('input[name=alert_at]').attr('required',false);
		}
 	});	  
</script>

<?php } ?>