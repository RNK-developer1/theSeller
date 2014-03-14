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
					id = 10 OR id = 40 OR (id >= 100 AND id <= 199)
				ORDER BY automatic ASC, id ASC
			"; 
	try{ 
		$stmt = $db->prepare($query); 
		$result = $stmt->execute($query_params); 
	} 
	catch(PDOException $ex){ die("Невозможно выполнить запрос: " . $ex->getMessage()); } 
	
	$statuses_step1 = $stmt->fetchAll();
	
	$query = "SELECT * FROM	cities ORDER BY nameRu"; 		
		 
	try{ 
		$stmt = $db->prepare($query); 
		$result = $stmt->execute(); 
	} 
	catch(PDOException $ex){ die("Невозможно выполнить запрос: " . $ex->getMessage()); } 
	
	$cities = $stmt->fetchAll();
	
	$query = "SELECT * FROM	warehouses ORDER BY number"; 		
	 
	try{ 
		$stmt = $db->prepare($query); 
		$result = $stmt->execute(); 
	} 
	catch(PDOException $ex){ die("Невозможно выполнить запрос: " . $ex->getMessage()); } 
	
	$warehouses = $stmt->fetchAll();
	
	$query = " 
            UPDATE orders SET inwork_userid = :user_id, inwork_time = NOW() WHERE orders.id = :order_id
        "; 
	$query_params = array( 
		':user_id' => $_SESSION['user']['id'],
		':order_id' => $_GET['id']
	); 
	 
	try{ 
		$stmt = $db->prepare($query); 
		$result = $stmt->execute($query_params); 
	} 
	catch(PDOException $ex){ die("Невозможно выполнить запрос: " . $ex->getMessage()); } 
	
	$query = " 
            SELECT 
				owner.username as username,
				orders.id as id,
				orders.item_id as item_id,
				item.price as i_price,
				orders.item as old_item,
				orders.item_price as item_price,
				item.name as item,
				item.url as item_url,
				item.price_min as item_price_min,
				orders.item_count as item_count,
				orders.item_params as item_params,
				orders.city_area as city_area,
				orders.address as address,
				orders.fio as fio,
				orders.phone as phone,
				orders.comment as comment,
				orders.email as email,
				orders.whs_ref as whs_ref,
				orders.status_step1 as status_step1,
				orders.weight as weight,
				COALESCE(item.weight, 0.1) as i_weight,
				COALESCE(orders.length, item.length, 10) as length,
				orders.width as width,
				COALESCE(item.width, 10) as i_width,
				COALESCE(orders.height, item.height, 10) as height,
				orders.courier_adr as courier_adr
		".($_SESSION['user']['group_id'] == 2 ? 
		"   FROM orders LEFT OUTER JOIN item ON item.uuid = orders.item_id AND item.owner_id = orders.owner_id, users as owner
            WHERE 
				orders.owner_id = owner.id AND
				owner.id IN (SELECT subseller_id FROM sellers_for_sellers WHERE sellers_for_sellers.seller_id = :user_id) AND
				orders.id = :order_id" :
		"   FROM orders LEFT OUTER JOIN item ON item.uuid = orders.item_id AND item.owner_id = orders.owner_id, operators_for_sellers, users as owner
            WHERE 
				orders.owner_id = owner.id AND
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
  <h4 class="modal-title">Обработка заказов. Шаг 1 - уточнение данных</h4>
</div>
<form action='update_order_step1.php?r=1' class='short_form' method='POST'>
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
	<input type='hidden' name='i_price' value='<?php echo $ord['i_price']?>'>
	<input type='hidden' name='i_weight' value='<?php echo $ord['i_weight'] == '0' ? '0.1' : $ord['i_weight']?>'>
	<input type='hidden' name='i_width' value='<?php echo $ord['i_width'] == '0' ? 1 : $ord['i_width']?>'>
	<div class="modal-body">
		<div class="form-group">				
			<label>Статус</label>
			<select class="form-control" name="status_step1">
				<?php foreach ($statuses_step1 as $status) { ?>
					<option value='<?php echo $status['id']; ?>' <?php if ($ord['status_step1'] == $status['id']) { echo 'selected = selected'; } ?>><?php echo ($status['automatic']?'(АВТО) ':'').$status['name']; ?></option>													
				<?php } ?>
			</select>
		</div>
		<div class="form-group">				
			<label>Перезвонить</label>
			<input class="form-control datetimepicker" type='text' name='alert_at'>
		</div>										
		<div class="form-group">				
			<label>ФИО</label>
			<input class="form-control" type='text' name='fio' value='<?php echo $ord['fio'] ?>'>
		</div>
		<div class="form-group">				
			<label>Адрес указанный при заказе</label>
			<input readonly class="form-control" type='text' name='city_area' value='<?php echo $ord['city_area'] ?>'>
			<label>(также для курьера)</label>
			<textarea class="form-control" type='text' name='address'><?php echo $ord['address'] ?></textarea>						
			<label>Отделение Новой почты (<a target='_new' href='http://novaposhta.ua/frontend/nearest/ru'>найти</a>)</label>
			<select class="form-control city">												
				<?php $city_ref = NULL; 
					$client_city = explode(' ',trim($ord['city_area'])); 
					$client_city = $client_city ? $client_city[0] : NULL; 
					echo "<!--".trim(mb_convert_case($client_city, MB_CASE_UPPER, "UTF-8"))."-->";
					
					$t_whs = NULL;
					foreach ($warehouses as $whs) { if ($whs['ref'] == $ord['whs_ref']) {$t_whs = $whs; break;} }
					
					foreach ($cities as $city) { ?>
					<?php
						$selected_city = '';
						if ($t_whs) {							
							if ($t_whs['city_ref'] == $city['ref']) { $city_ref = $city['ref']; $selected_city = "selected=\"selected\"";}
						} else if ($client_city) {
							if (trim(mb_convert_case($city['nameRu'], MB_CASE_UPPER, "UTF-8")) == trim(mb_convert_case($client_city, MB_CASE_UPPER, "UTF-8"))) { $city_ref = $city['ref']; $selected_city = "selected=\"selected\"";}
						}
					?>
					<option <?php echo $selected_city;?> value="<?php echo $city['ref']; ?>"><?php echo $city['nameRu']; ?></option>
				<?php } ?>
			</select>											
			<select class="form-control warehouse whs" name="whs_ref">
				<?php foreach ($warehouses as $whs) { ?>
					<?php 
						$selected_whs = '';
						if ($ord['whs_ref']) {
							if ($whs['ref'] == $ord['whs_ref']) { $selected_whs = "selected=\"selected\""; }
						} else if($whs['city_ref'] == $city_ref){
							$match_num = '';
							preg_match("~[№#]??(\d+)~",$ord['address'],$match_num);
							
							if (!empty($match_num) and $whs['number'] == $match_num[1]) {
								$selected_whs = "selected=\"selected\"";
							}
						}
					?>
					<option <?php echo $selected_whs;?> class="<?php echo $whs['city_ref'];?>" value="<?php echo $whs['ref'];?>"><?php echo $whs['addressRu'];?></option>
				<?php } ?>
			</select>
		</div>
		<div class="form-group">				
			<label>Количество</label>
			<input class="form-control" type='number' min='1' step='1' name='item_count' value='<?php echo $ord['item_count'] ?>'>		
			<?php if (!$ord['item_id'] or !$ord['item']) { ?>
				<label class="bold_red">Ошибка!</label><label><small>У предпринимателя <?php echo $ord['username']; ?> нет товара"<?php echo $ord['old_item']; ?>"</small></label>
			<?php }?>
			<label>Товар <?php if ($ord['item_url']) { echo " (<a target='_new' href='".$ord['item_url']."'>сайт&nbsp;товара</a>)";}?></label>
			<input readonly class="form-control" type='text' name='item' value='<?php echo $ord['item'] ?>'>			
			<label>Параметры</label>
			<input class="form-control" type='text' name='item_params' value='<?php echo $ord['item_params'] ?>'>
			<label>Сумма</label>
			<input class="form-control" type='number' step='any' min='0' name='item_price' value='<?php echo $ord['item_price'] ?>'>
			<?php if ($ord['item_price_min']) { ?><p>Минимальная цена за единицу товара: <?php echo number_format($ord['item_price_min'],2);?></p><?php } ?>
			<label>Вес и габариты</label>
			<table>
				<tr><td>Вес(кг)</td><td>Ширина(см)</td><td>Высота(см)</td><td>Глубина(см)</td></tr>
				<tr><td><input style="width:90px" class="form-control fourth" type='number' step='any' min='0' name='weight' value='<?php echo $ord['weight'] ? $ord['weight'] : number_format(floatval($ord['i_weight'])*floatval($ord['item_count']),2); ?>'></td><td><input style="width:90px" class="form-control fourth" type='number' step='1' min='1' name='width' value='<?php echo $ord['width'] ? ($ord['width'] == '0' ? '1':$ord['width']) : number_format(floatval(($ord['i_width'] == '0' ? '1':$ord['i_width']))*floatval($ord['item_count']),0); ?>'></td><td><input style="width:90px" class="form-control fourth" type='number' step='1' min='1' name='height' value='<?php echo $ord['height'] == '0' ? '1':$ord['height'] ?>'></td><td><input style="width:90px" class="form-control fourth" type='number' step='1' min='1' name='length' value='<?php echo $ord['length'] == '0' ? '1':$ord['length'] ?>'></td></tr>
			</table>
		</div>
		<div class="form-group">				
			<label>Телефон</label>
			<input class="form-control" type='text' name='phone' value='<?php echo $ord['phone']; ?>'>
			<label>E-mail</label>
			<input class="form-control" type='text' name='email' value='<?php echo $ord['email'] ?>'>
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
			<input <?php if ($_SESSION['user']['group_id'] != 2) { ?>required=required <?php } ?> type='text' style='width: 500px;' class="form-control" name='comment' value=''>
		</div>										
	</div>				
	<div class="modal-footer">
	  <?php if ($_SESSION['user']['group_id'] == 2) { ?><button type="button" class="btn btn-default" data-dismiss="modal">Закрыть</button><?php } ?>
	  <input type="submit" class="btn btn-primary" value='Сохранить'>	  
	</div>
</form>

<script type='text/javascript'>
	$('#myModalStep1<?php echo $ord['id'];?> .datetimepicker').datetimepicker({
		format: 'yyyy-mm-dd hh:ii',
		autoclose: true,
        todayBtn: true,
		startView: 1,
		language: 'ru',
		weekStart: 1
	  });
	  
	$('#myModalStep1<?php echo $ord['id'];?> .warehouse').chained('#myModalStep1<?php echo $ord['id'];?> .city');  
	
	$('input[name="phone"]').mask("(999)999-99-99");
	
	$('input[name="item_count"]').on('input', function() {
		tcnt = parseInt($(this).val());
		if (tcnt) {
			tprice = tcnt*parseFloat($(this).parents('form').find('input[name=i_price]').val());
			tweight = tcnt*parseFloat($(this).parents('form').find('input[name=i_weight]').val());
			twidth = tcnt*parseInt($(this).parents('form').find('input[name=i_width]').val());
						
			$(this).parents('.form-group').find('input[name=item_price]').val(tprice.toFixed(2));
			$(this).parents('.form-group').find('input[name=weight]').val(tweight.toFixed(1));
			$(this).parents('.form-group').find('input[name=width]').val(twidth);
		}
	});
	
	$('select[name="status_step1"]').change(function() {
		if ( $(this).val() == 102) {
			$(this).parent().parent().find('input[name=alert_at]').attr('required','required');
		} else {
			$(this).parent().parent().find('input[name=alert_at]').attr('required',false);
		}
 	});
</script>

<?php } ?>