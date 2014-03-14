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
            SELECT 
				preorder.created_at as created_at,
				owner.id as owner_id,
                owner.username as username,
				preorder.user_id as id,
				preorder.item_uuid as item_uuid,
				item.price*preorder.count as item_price,
				item.name as item,
				preorder.count as item_count,
				preorder.item_params as item_params,
				preorder.city_area as city_area,
				preorder.address as address,
				preorder.fio as fio,
				preorder.phone as phone,
				preorder.email as email,
				preorder.referrer as referrer,
				preorder.request as request,
				preorder.ip_src as ip_src,
				COALESCE(item.weight, 0.1) as weight,
				COALESCE(item.length, 10) as length,				
				COALESCE(item.width, 10) as width,
				COALESCE(item.height, 10) as height
		".($_SESSION['user']['group_id'] == 2 ? 
		"   FROM users as owner, preorder LEFT OUTER JOIN item ON item.uuid = preorder.item_uuid
            WHERE 
				owner.id IN (SELECT subseller_id FROM sellers_for_sellers WHERE sellers_for_sellers.seller_id = :user_id) AND
				item.owner_id = owner.id AND preorder.user_id = :order_id" :
		"   FROM users as owner, operators_for_sellers, preorder LEFT OUTER JOIN item ON item.uuid = preorder.item_uuid
            WHERE 
                item.owner_id = operators_for_sellers.seller_id AND
				operators_for_sellers.operator_id = :user_id AND
				item.owner_id = owner.id AND
				preorder.user_id = :order_id
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
	
	$comment = '';
	
	$query = "SELECT orders.*, users.username FROM orders, users WHERE orders.owner_id = users.id AND orders.phone = :phone ORDER BY created_at ASC";
	$query_params = array(
		':phone' => $ord['phone']
	);
	try{ 
		$stmt = $db->prepare($query); 
		$result = $stmt->execute($query_params); 
	} 
	catch(PDOException $ex){ die("Невозможно выполнить запрос: " . $ex->getMessage()); } 		
	$old_orders = $stmt->fetchAll();
	
	foreach ($old_orders as $old_order) {
		$crd = new DateTime($old_order['created_at']);
		$crd = $crd->format('d-m-y H:i:s');
		if (in_array($old_order['status_step3'], array(20, 301, 310, 311, 312))) {
			$st = "оплачен";
		} else if (in_array($old_order['status_step3'], array(30, 31, 302, 310, 318, 320, 321)) or in_array($old_order['status_step2'], array(220, 225, 240, 241, 242))) {
			$st = "ВОЗВРАТ";
		} else if (in_array($old_order['status_step1'], array(10, 40)) or in_array($old_order['status_step2'], array(10, 230))) {
			$st = "Отменен";
		} else {
			$st = "обрабатывается";
		}
		$comment .= 'АВТО: '.$crd.' '.$old_order['item'].' '.$old_order['username'].' '.$st."\n";
	}
	
	$ord_comments_str = '';
					 
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
	
	foreach ($ord_comments as $t_comment) {
		$tcd = new DateTime($t_comment['date']);
		$tcd = $tcd->format('d.m H:i');
		$ord_comments_str = $ord_comments_str."\n".$tcd.": ".$t_comment['comment'];
	}
						
	if ($ord) {
?>

<div class="modal-header">
  <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
  <h4 class="modal-title">Заказ на основе предзаказа</h4>
</div>
<form action='order_create_bystep1.php?r=1' class='short_form' method='POST'>
	<input type='hidden' name='id' value='<?php echo $ord['id']?>'>
	<?php if ($_GET['seller_id'] or $_GET['seller_id'] == '0') { ?>
		<input type='hidden' name='seller_id' value='<?php echo $_GET['seller_id']?>'>
	<?php }
		if ($_GET['item_id'] or $_GET['item_id'] == '0') { ?>
		<input type='hidden' name='item_id' value='<?php echo $_GET['item_id']?>'>
	<?php } ?>
	<?php if ($_GET['order_date']) { ?>
		<input type='hidden' name='order_date' value='<?php echo $_GET['order_date']?>'>
	<?php } ?>
	<?php if ($_GET['order_date_end']) { ?>
		<input type='hidden' name='order_date_end' value='<?php echo $_GET['order_date_end']?>'>
	<?php } ?>
	<input type='hidden' name='referrer' value='<?php echo $ord['referrer']?>'>
	<input type='hidden' name='request' value='<?php echo $ord['request']?>'>
	<input type='hidden' name='ip_src' value='<?php echo $ord['ip_src']?>'>
	<div class="modal-body">
		<div class="form-group">				
			<label>Статус</label>
			<select class="form-control" name="status_step1">
				<?php foreach ($statuses_step1 as $status) { ?>
					<option value='<?php echo $status['id']; ?>' <?php if (110 == $status['id']) { echo 'selected = selected'; } ?>><?php echo ($status['automatic']?'(АВТО) ':'').$status['name']; ?></option>													
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
			<input class="form-control" type='number' min='1' step='1' name='item_count' value='<?php echo $ord['item_count'] ? $ord['item_count'] : 1 ?>'>					
			<label>Товар</label>
			<input class="form-control" type='hidden' name='item_uuid' value='<?php echo $ord['item_uuid'] ?>'>			
			<input class="form-control" type='hidden' name='owner_id' value='<?php echo $ord['owner_id'] ?>'>			
			<input readonly class="form-control" type='text' name='item' value='<?php echo $ord['item'] ?>'>			
			<label>Параметры</label>
			<input class="form-control" type='text' name='item_params' value='<?php echo $ord['item_params'] ?>'>
			<label>Сумма</label>
			<input class="form-control" type='number' step='any' min='0' name='item_price' value='<?php echo $ord['item_price'] ?>'>
			<label>Вес и габариты</label>
			<table>
				<tr><td>Вес(кг)</td><td>Ширина(см)</td><td>Высота(см)</td><td>Глубина(см)</td></tr>
				<tr><td><input style="width:90px" class="form-control fourth" type='number' step='any' min='0' name='weight' value='<?php echo number_format(floatval($ord['weight'])*floatval($ord['item_count']),2); ?>'></td><td><input class="form-control fourth" type='number' step='1' min='1' name='width' value='<?php echo number_format(floatval(($ord['width'] == '0' ? '1':$ord['width']))*floatval($ord['item_count']),0); ?>'></td><td><input class="form-control fourth" type='number' step='1' min='1' name='height' value='<?php echo $ord['height'] == '0' ? '1':$ord['height'] ?>'></td><td><input class="form-control fourth" type='number' step='1' min='1' name='length' value='<?php echo $ord['length'] == '0' ? '1':$ord['length'] ?>'></td></tr>
			</table>
		</div>
		<div class="form-group">				
			<label>Телефон</label>
			<input class="form-control" type='text' name='phone' value='<?php echo $ord['phone']; ?>'>
			<label>E-mail</label>
			<input class="form-control" type='text' name='email' value='<?php echo $ord['email'] ?>'>
		</div>	
		<div class="form-group">				
			<label>Комментарий</label>
			<textarea class="form-control" name='comment'>На основе предзаказа от <?php echo $ord['created_at']."\n".$comment.$ord_comments_str ?></textarea>
		</div>										
	</div>				
	<div class="modal-footer">
	  <button type="button" class="btn btn-default" data-dismiss="modal">Отмена</button>
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
</script>

<?php } ?>