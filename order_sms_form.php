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
				orders.phone as phone,
				orders.newpost_answer as newpost_answer,
				orders.newpost_id as newpost_id
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
		$np_answer = json_decode($ord['newpost_answer'], true);
		
		$query_sms = "SELECT 
			date,
			name as status,
			color
		FROM flysms LEFT OUTER JOIN flysms_state ON state = status WHERE order_id = :order_id";
		$query_params = array(':order_id' => $ord['id']);

		try{ 
			$stmt = $db->prepare($query_sms); 
			$result = $stmt->execute($query_params); 
		} 
		catch(PDOException $ex){ die("Невозможно выполнить запрос: " . $ex->getMessage()); } 
		
		$ord_sms = $stmt->fetchAll(); 			
?>

<div class="modal-header">
  <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
  <h4 class="modal-title">SMS для отправки</h4>
</div>
<form action="send_sms.php" method="POST">
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
			<?php if (count($ord_sms) > 0) {
				?><b>Отправленные SMS:</b><table>
				<tr><th>Дата</th><th>Статус</th></tr>
				<?php foreach ($ord_sms as $sms) {
					echo '<tr><td>'.$sms['date'].'</td><td><span style="color: #'.$sms['color'].'">'.$sms['status'].'</span></td></tr>';
				}?>
				</table>
				<?php
			}?>
		</div>
		<div class="form-group">				
			<label>Телефон</label>
			<input readonly class="form-control" name='phone' type='text' value='<?php echo str_replace(array('(',')','-'), "", $ord['phone']) ?>'>
			<?php if ($ord['newpost_id'] AND $np_answer['arrival_date']) { 
				$rexp_sd = '/(\d\d\.\d\d)\.\d\d\d\d/';
				preg_match($rexp_sd, $np_answer['arrival_date'], $short_date); 
				$rexp_adr = '/.*?:([^\(]*)/';
				preg_match($rexp_adr, $np_answer['address'], $short_adr); 
				$short_adr = trim($short_adr[1]);
				$sms_text = 'Накладная '.$ord['newpost_id'].' прибытие '.$short_date[1].' на '.$short_adr; 
				if (70-strlen(utf8_decode($sms_text)) < 0) {
					$sms_text = 'Накладная '.$ord['newpost_id'].' приб.'.$short_date[1].' на '.$short_adr; 
					if (70-strlen(utf8_decode($sms_text)) < 0) {
						$sms_text = 'Накладная '.$ord['newpost_id'].' приб.'.$short_date[1].' на '.str_replace(' ','',$short_adr); 
						if (70-strlen(utf8_decode($sms_text)) < 0) {
							$sms_text = 'Накладная '.$ord['newpost_id'].' приб.'.$short_date[1].':'.str_replace(' ','',$short_adr);
							if (70-strlen(utf8_decode($sms_text)) < 0) {
								$sms_text = 'Накладная '.$ord['newpost_id'].' приб.'.$short_date[1].':'.mb_substr(str_replace("ТЦ\"Барабашова\",Пл.","Барабаш",str_replace("ТЦ«Барабашова»,","Барабашова",str_replace("площадка,место","",str_replace("пгт.","",str_replace("вская","",str_replace("киоск","",str_replace("микрорайон","мкрн.",str_replace("стрелковой","стр.",str_replace(' ','',$short_adr))))))))), 0, 34); 
							}
						}
					}
				}
				?>
				<label>Текст SMS</label>
				<input name='sms_text' onkeyup="$('#sms_len<?php echo $ord['id']; ?>').html(70-$(this).val().length);" class="form-control" type='text' value='<?php echo $sms_text; ?>'>	
				<label>Осталось символов:</label>
				<div id='sms_len<?php echo $ord['id']; ?>'><?php echo 70-strlen(utf8_decode($sms_text)); ?></div>												
			<?php } else { echo "<i>Нет данных от Новой почты или груз уже прибыл</i>"; } ?>											
		</div>										
	</div>				
	<div class="modal-footer">
	  <input type="submit" class="btn btn-danger" value="Отправить SMS">
	  <button type="button" class="btn btn-default" data-dismiss="modal">Закрыть</button>
	</div>
</form>	
<?php } ?>