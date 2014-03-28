<?php
	require("config.php"); 
	
	if (empty($_SESSION['user']) || $_SESSION['user']['active'] == 0 || $_SESSION['user']['group_id'] != 2) {
		header("Location: index.php"); 
		die("Перенаправление: index.php"); 
	}
	
	$select_sellers = array();
	$query = " 
			SELECT 
				CONCAT(REPEAT(' -',sellers_for_sellers.depth),users.username) as username,
				users.id as id
			FROM sellers_for_sellers, users
			WHERE
				users.id = sellers_for_sellers.subseller_id AND
				sellers_for_sellers.seller_id = :user_id
		";		
	$query_params = array( 
		':user_id' => $_SESSION['user']['id']
	); 
		 
	try{ 
		$stmt = $db->prepare($query); 
		$result = $stmt->execute($query_params); 
	} 
	catch(PDOException $ex){ die("Невозможно выполнить запрос: " . $ex->getMessage()); } 
	
	$select_sellers = array_merge($select_sellers,$stmt->fetchAll());
	
	$query = " 
            SELECT 
				item.*,
				users.username as username
            FROM
				item, users
            WHERE 
				item.is_deleted IS NULL AND
				users.id = owner_id AND
				(:owner_id = '0' OR owner_id = :owner_id) AND
				owner_id IN (SELECT subseller_id FROM sellers_for_sellers WHERE sellers_for_sellers.seller_id = :user_id)
        "; 
	$query_params = array( 
		':user_id' => $_SESSION['user']['id'],
		':owner_id' => (($_GET['seller_id'] or $_GET['seller_id'] == '0') ? $_GET['seller_id'] : $_SESSION['user']['id']),	
	); 
	 
	try{ 
		$stmt = $db->prepare($query); 
		$result = $stmt->execute($query_params); 
	} 
	catch(PDOException $ex){ die("Невозможно выполнить запрос: " . $ex->getMessage()); } 
	
	$requests = $stmt->fetchAll();
?>

<!doctype html>
<html lang="ru">
<?php include 'header.php' ?>
<body>
<script src="assets/tinymce/tinymce.min.js"></script>

<?php include 'top_menu.php' ?>
<div class="container">
	<h3>Товары</h3>
    
	<p><a data-toggle="modal" href="#myModal" class="btn btn-primary btn-lg">Добавить товар</a></p>
	  <div class="modal fade" id="myModal">
		<div class="modal-dialog">
		  <div class="modal-content">
			<div class="modal-header">
			  <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
			  <h4 class="modal-title">Новый товар</h4>
			</div>
			<form action='add_item.php'>
				<div class="modal-body">
					<div class="form-group">				
						<label>Название</label>
						<input class="form-control" type='text' name='name'>
						<label>Cайт (полная ссылка с http://)</label>
						<input class="form-control" type='text' name='url'>
					</div>
					<div class="form-group">				
						<label>Параметр 1</label>
						<input class="form-control" type='text' name='param1_name' value='<?php echo $row['param1_name'] ?>'>
						<label>Возможные значения параметра 1 (через ; )</label>
						<input class="form-control" type='text' name='param1' value='<?php echo $row['param1'] ?>'>
					</div>
					<div class="form-group">				
						<label>Параметр 2</label>
						<input class="form-control" type='text' name='param2_name' value='<?php echo $row['param2_name'] ?>'>
						<label>Возможные значения параметра 2 (через ; )</label>
						<input class="form-control" type='text' name='param2' value='<?php echo $row['param2'] ?>'>
					</div>
					<div class="form-group">				
						<label>Yandex метрика идентификатор</label>
						<input class="form-control" type='text' name='yandexmetric' value='<?php echo $row['yandexmetric'] ?>'>
						<label>Yandex метрика цель</label>
						<input class="form-control" type='text' name='yandexgoal' value='<?php echo $row['yandexgoal'] ?>'>
						<label>Yandex метрика цель для завершающего экрана</label>
						<input class="form-control" type='text' name='yandexgoal2' value='<?php echo $row['yandexgoal2'] ?>'>
					</div>
					<div class="form-group">
						<label>Возврат по прошествии (дней)</label>
						<input class="form-control" type='number' step='1' min='2' name='day_back' value='5'>
					</div>
					<div class="form-group">
						<label>Цена</label>
						<input class="form-control" type='number' step='any' min='0' name='price' value='99'>
						<label>Минимальная цена</label>
						<input class="form-control" type='number' step='any' min='0' name='price_min' value='77'>
					</div>
					<div class="form-group">
						<label>Вес (кг)</label>
						<input class="form-control" type='number' step='any' min='0' name='weight' value='0.1'>
						<label>Ширина (см)</label>
						<input class="form-control" type='number' step='any' min='0' name='width' value='10'>
						<label>Высота (см)</label>
						<input class="form-control" type='number' step='any' min='0' name='height' value='10'>
						<label>Глубина (см)</label>
						<input class="form-control" type='number' step='any' min='0' name='length' value='10'>					
					</div>
				</div>				
				<div class="modal-footer">
				  <button type="button" class="btn btn-default" data-dismiss="modal">Отмена</button>
				  <input type="submit" class="btn btn-primary" value='Добавить'>
				</div>
			</form>
		  </div>
		</div>
	  </div>
	  
	<table class='table table-hover table-bordered table-fixed-header'>
	<thead class="header"><th>Код товара</th><th>Название</th><th>Цена</th><th>Письмо покупателю</th><th>Письмо с декларацией</th><th></th><th>Действие</th></thead>
	<?php	foreach ($requests as $row){ ?>
				<tr id="item_<?php echo $row['uuid']?>">
				<td><?php echo $row['uuid']; if ($_GET['seller_id'] == '0') {echo '<br/><small>'.$row['username'].'</small>';} ?></td>
				<td><?php if ( $row['url'] ) { echo "<a target=\"_blank\" href=\"".$row['url']."\">".$row['name']."</a>"; } else { echo $row['name']; } ?></td>
				<td><?php echo number_format($row['price'],2) ?><br/>min: <?php echo $row['price_min'] ? number_format($row['price_min'],2) : 'н/д';?></td>
				<td>					
						<a data-toggle="modal" href="#myModalMail<?php echo $row['uuid'] ?>" class="btn btn-default btn-sm">Письмо</a>
						  <div class="modal fade" id="myModalMail<?php echo $row['uuid'] ?>">
							<div class="modal-dialog">
							  <div class="modal-content">
								<div class="modal-header">
								  <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
								  <h4 class="modal-title">Письмо покупателю</h4>
								</div>
								<form action='update_item_mail.php' method='POST'>
									<?php if ($_GET['seller_id'] or $_GET['seller_id'] == '0') { ?>
										<input type='hidden' name='seller_id' value='<?php echo $_GET['seller_id']?>'>
									<?php } ?>
									<input type='hidden' name='id' value='<?php echo $row['uuid']?>'>
									<div class="modal-body">
										<div class="form-group">				
											<label>Тема письма</label>
											<input class="form-control" type='text' name='mail_subject' value='<?php echo $row['mail_subject'] ?>'>
										</div>
										<div class="form-group">				
											<label>Текст письма</label>
											<textarea class="form-control mail_templates" name="mail_template"><?php echo htmlspecialchars($row['mail_template']) ?></textarea>											
										</div>
										<div><i>
											<p>{item} будет заменено на название товара</p>
											<p>{surname} будет заменено на фамилию покупателя</p>
											<p>{name} будет заменено на имя покупателя</p>
										</i>
										</div>
									</div>													
									<div class="modal-footer">
									  <button type="button" class="btn btn-default" data-dismiss="modal">Отмена</button>
									  <input type="submit" class="btn btn-primary" value='Сохранить'>
									</div>
								</form>
							  </div>
							</div>
						  </div>
					<br/><small><?php echo $row['mail_subject'] ? 'Определено' : 'Не определено';?></small>	  
				</td>
				<td>					
						<a data-toggle="modal" href="#myModalNPMail<?php echo $row['uuid'] ?>" class="btn btn-default btn-sm">Письмо</a>
						  <div class="modal fade" id="myModalNPMail<?php echo $row['uuid'] ?>">
							<div class="modal-dialog">
							  <div class="modal-content">
								<div class="modal-header">
								  <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
								  <h4 class="modal-title">Письмо покупателю с номером декларации</h4>
								</div>
								<form action='update_item_npmail.php' method='POST'>
									<?php if ($_GET['seller_id'] or $_GET['seller_id'] == '0') { ?>
										<input type='hidden' name='seller_id' value='<?php echo $_GET['seller_id']?>'>
									<?php } ?>						
									<input type='hidden' name='id' value='<?php echo $row['uuid']?>'>
									<div class="modal-body">
										<div class="form-group">				
											<label>Тема письма</label>
											<input class="form-control" type='text' name='npmail_subject' value='<?php echo $row['npmail_subject'] ?>'>
										</div>
										<div class="form-group">				
											<label>Текст письма</label>
											<textarea class="form-control mail_templates" name="npmail_template"><?php echo htmlspecialchars($row['npmail_template']) ?></textarea>											
										</div>
										<div><i>
											<p>{item} будет заменено на название товара</p>											
											<p>{name} будет заменено на фамилию-имя покупателя</p>
											<p>{declaration} будет заменено на номер декларации</p>
										</i>
										</div>
									</div>				
									<div class="modal-footer">
									  <button type="button" class="btn btn-default" data-dismiss="modal">Отмена</button>
									  <input type="submit" class="btn btn-primary" value='Сохранить'>
									</div>
								</form>
							  </div>
							</div>
						  </div>
					<br/><small><?php echo $row['npmail_subject'] ? 'Определено' : 'Не определено'; ?></small>	  
				</td>
				<td>	
					<table>
					<tr><td>
						<a data-toggle="modal" href="#myModalFS<?php echo $row['uuid'] ?>" class="btn btn-default btn-sm">Завершение<br/>покупки</a>
						  <div class="modal fade" id="myModalFS<?php echo $row['uuid'] ?>">
							<div class="modal-dialog">
							  <div class="modal-content">
								<div class="modal-header">
								  <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
								  <h4 class="modal-title">Сообщение о завершении покупки</h4>
								</div>
								<form action='update_item_fs.php' method='POST'>
									<?php if ($_GET['seller_id'] or $_GET['seller_id'] == '0') { ?>
										<input type='hidden' name='seller_id' value='<?php echo $_GET['seller_id']?>'>
									<?php } ?>
								
									<input type='hidden' name='id' value='<?php echo $row['uuid']?>'>
									<div class="modal-body">
										<div class="form-group">				
											<textarea class="form-control mail_templates" name="finish_screen"><?php echo htmlspecialchars($row['finish_screen']) ?></textarea>											
										</div>
										<div><i>
											<p>{item} будет заменено на название товара</p>
											<p>{surname} будет заменено на фамилию покупателя</p>
											<p>{name} будет заменено на имя покупателя</p>
										</i>
										</div>
									</div>				
									<div class="modal-footer">
									  <button type="button" class="btn btn-default" data-dismiss="modal">Отмена</button>
									  <input type="submit" class="btn btn-primary" value='Сохранить'>
									</div>
								</form>
							  </div>
							</div>
						  </div>
					</td><td>
						<a data-toggle="modal" href="#myModalFS_fast<?php echo $row['uuid'] ?>" class="btn btn-default btn-sm">Закрытая<br/>форма</a>
						  <div class="modal fade" id="myModalFS_fast<?php echo $row['uuid'] ?>">
							<div class="modal-dialog">
							  <div class="modal-content">
								<div class="modal-header">
								  <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
								  <h4 class="modal-title">Шапка закрытой формы</h4>
								</div>
								<form action='update_item_fs_fast.php' method='POST'>
									<?php if ($_GET['seller_id'] or $_GET['seller_id'] == '0') { ?>
										<input type='hidden' name='seller_id' value='<?php echo $_GET['seller_id']?>'>
									<?php } ?>
								
									<input type='hidden' name='id' value='<?php echo $row['uuid']?>'>
									<div class="modal-body">
										<div class="form-group">				
											<textarea class="form-control mail_templates" name="finish_screen_fast"><?php echo htmlspecialchars($row['finish_screen_fast']) ?></textarea>											
										</div>
										<div><i>
											<p>{item} будет заменено на название товара</p>
											<p>{name} будет заменено на имя покупателя</p>
										</i>
										</div>
									</div>				
									<div class="modal-footer">
									  <button type="button" class="btn btn-default" data-dismiss="modal">Отмена</button>
									  <input type="submit" class="btn btn-primary" value='Сохранить'>
									</div>
								</form>
							  </div>
							</div>
						  </div>
					</td>
					<td>
						<a data-toggle="modal" href="#myModalCB<?php echo $row['uuid'] ?>" class="btn btn-default btn-sm">Политика<br/>конф-сти</a>
						  <div class="modal fade" id="myModalCB<?php echo $row['uuid'] ?>">
							<div class="modal-dialog">
							  <div class="modal-content">
								<div class="modal-header">
								  <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
								  <h4 class="modal-title">Политика конфиденциальности</h4>
								</div>
								<form action='update_item_cb.php' method='POST'>
									<?php if ($_GET['seller_id'] or $_GET['seller_id'] == '0') { ?>
										<input type='hidden' name='seller_id' value='<?php echo $_GET['seller_id']?>'>
									<?php } ?>
							
									<input type='hidden' name='id' value='<?php echo $row['uuid']?>'>
									<div class="modal-body">
										<div class="form-group">				
											<textarea class="form-control mail_templates" name="conf_block"><?php echo htmlspecialchars($row['conf_block']) ?></textarea>											
										</div>
										<div><i>
											<p>{item} будет заменено на название товара</p>
										</i>
										</div>
									</div>				
									<div class="modal-footer">
									  <button type="button" class="btn btn-default" data-dismiss="modal">Отмена</button>
									  <input type="submit" class="btn btn-primary" value='Сохранить'>
									</div>
								</form>
							  </div>
							</div>
						  </div>
					</td>
					</tr>
					<tr>
						<td><small><?php echo $row['finish_screen'] ? 'Особое' : 'Стандартное' ?></small></td>
						<td><small><?php echo $row['finish_screen_fast'] ? 'Особая' : 'Стандартная' ?></small></td>
						<td><small><?php echo $row['conf_block'] ? 'Определена' : 'Отсутствует' ?></small></td>				
					</tr>
					</table>
					<td>
					<div style="width: 310px">
						<a data-toggle="modal" href="#myModalEdit<?php echo $row['uuid'] ?>" class="btn btn-success btn-sm">Изменить</a>
						  <div class="modal fade" id="myModalEdit<?php echo $row['uuid'] ?>">
							<div class="modal-dialog">
							  <div class="modal-content">
								<div class="modal-header">
								  <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
								  <h4 class="modal-title">Изменить товар</h4>
								</div>
								<form action='update_item.php'>
									<?php if ($_GET['seller_id'] or $_GET['seller_id'] == '0') { ?>
										<input type='hidden' name='seller_id' value='<?php echo $_GET['seller_id']?>'>
									<?php } ?>
								
									<input type='hidden' name='id' value='<?php echo $row['uuid']?>'>
									<div class="modal-body">
										<div class="form-group">				
											<label>Название</label>
											<input class="form-control" type='text' name='name' value='<?php echo $row['name'] ?>'>
											<label>Cайт (полная ссылка с http://)</label>
											<input class="form-control" type='text' name='url' value='<?php echo $row['url'] ?>'>
										</div>
										<div class="form-group">				
											<label>Параметр 1</label>
											<input class="form-control" type='text' name='param1_name' value='<?php echo $row['param1_name'] ?>'>
											<label>Возможные значения параметра 1 (через ; )</label>
											<input class="form-control" type='text' name='param1' value='<?php echo $row['param1'] ?>'>
										</div>
										<div class="form-group">				
											<label>Параметр 2</label>
											<input class="form-control" type='text' name='param2_name' value='<?php echo $row['param2_name'] ?>'>
											<label>Возможные значения параметра 2 (через ; )</label>
											<input class="form-control" type='text' name='param2' value='<?php echo $row['param2'] ?>'>
										</div>
										<div class="form-group">				
											<label>Yandex метрика идентификатор</label>
											<input class="form-control" type='text' name='yandexmetric' value='<?php echo $row['yandexmetric'] ?>'>
											<label>Yandex метрика цель</label>
											<input class="form-control" type='text' name='yandexgoal' value='<?php echo $row['yandexgoal'] ?>'>
											<label>Yandex метрика цель для завершающего экрана</label>
											<input class="form-control" type='text' name='yandexgoal2' value='<?php echo $row['yandexgoal2'] ?>'>
										</div>
										<div class="form-group">
											<label>Возврат по прошествии (дней)</label>
											<input class="form-control" type='number' step='1' min='2' name='day_back' value='<?php echo $row['day_back']; ?>'>
										</div>
										<div class="form-group">
											<label>Цена</label>
											<input class="form-control" type='number' step='any' min='0' name='price' value='<?php echo number_format($row['price'],2) ?>'>
											<label>Минимальная цена</label>
											<input class="form-control" type='number' step='any' min='0' name='price_min' value='<?php echo number_format($row['price_min'],2) ?>'>
										</div>
										<div class="form-group">
											<label>Вес (кг)</label>
											<input class="form-control" type='number' step='any' min='0' name='weight' value='<?php echo $row['weight'] ?>'>
											<label>Ширина (см)</label>
											<input class="form-control" type='number' step='any' min='0' name='width' value='<?php echo $row['width'] ?>'>
											<label>Высота (см)</label>
											<input class="form-control" type='number' step='any' min='0' name='height' value='<?php echo $row['height'] ?>'>
											<label>Глубина (см)</label>
											<input class="form-control" type='number' step='any' min='0' name='length' value='<?php echo $row['length'] ?>'>				
										</div>
									</div>				
									<div class="modal-footer">
									  <button type="button" class="btn btn-default" data-dismiss="modal">Отмена</button>
									  <input type="submit" class="btn btn-primary" value='Сохранить'>
									</div>
								</form>
							  </div>
							</div>
						  </div>						
						<a href='item_double.php?id=<?php echo $row['uuid']?>' class='btn btn-default btn-sm'>Дублировать</a> <a href='item_remove.php?id=<?php echo $row['uuid']?>' data-confirm='Товар <?php echo $row['name']?> будет удалён' class='btn btn-danger btn-sm'>Удалить</a><br/><br/><a target='_blank' href='item_form.php?id=<?php echo $row['uuid']?>' class='btn btn-info btn-sm'>Форма</a> <a target='_blank' href='item_openform.php?id=<?php echo $row['uuid']?>' class='btn btn-success btn-sm'>Открытая форма</a> 
						</div>
					</td>
				</tr>
	<?php	} ?>
	</table>
</div>

<script type="text/javascript">
	$('.modal').on('shown.bs.modal', function () {
	  var modal_id = $('.modal:visible').first().attr('id');
	  
	  tinymce.init({
		selector: '#'+modal_id+" textarea.mail_templates",
		height: 250,
		menubar: false,
		resize: false,
		statusbar: false,
		plugins: "link",
		plugins: 'link image code',
		language: 'ru_RU'
		});
	});	
 
 $('.table-fixed-header').fixedHeader();
 
 if (window.location.hash) {
	$(window).scrollTop($('#item_'+window.location.hash.replace('#','')).position().top-160);
 }
 
$('a[data-confirm]').click(function(ev) {
	var href = $(this).attr('href');

	if (!$('#dataConfirmModal').length) {
		$('body').append('<div id="dataConfirmModal" class="modal fade" role="dialog" aria-labelledby="dataConfirmLabel" aria-hidden="true"><div class="modal-dialog"><div class="modal-content"><div class="modal-header"><button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button><h3 id="dataConfirmLabel">Подтвердите действие</h3></div><div class="modal-body"></div><div class="modal-footer"><button class="btn" data-dismiss="modal" aria-hidden="true">Отмена</button><a class="btn btn-primary" id="dataConfirmOK">OK</a></div></div></div></div>');
	} 
	$('#dataConfirmModal').find('.modal-body').text($(this).attr('data-confirm'));
	$('#dataConfirmOK').attr('href', href);
	$('#dataConfirmModal').modal({show:true});
	return false;
});
</script>

</body>
</html>