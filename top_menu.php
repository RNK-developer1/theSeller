<div class="navbar navbar-fixed-top">
  <div class="container">
    <button type="button" class="navbar-toggle" data-toggle="collapse" data-target=".nav-collapse">
      <span class="icon-bar"></span>
      <span class="icon-bar"></span>
      <span class="icon-bar"></span>
    </button>
    <!--a class="navbar-brand" href="<?php if(!empty($_SESSION['user'])) {echo '/theseller';} else { echo '/theseller';} ?>">theSeller<?php if(!empty($_SESSION['user'])) { echo ': '.htmlentities($_SESSION['user']['email'], ENT_QUOTES, 'UTF-8'); } ?></a-->
	<div class="nav-collapse collapse">
		<ul class="nav navbar-nav">
			<li class="dropdown">
				<a href="#" class="dropdown-toggle" data-toggle="dropdown"><?php if(!empty($_SESSION['user'])) { echo htmlentities($_SESSION['user']['email'], ENT_QUOTES, 'UTF-8'); } else { echo "theSeller";} ?> <b class="caret"></b></a>
				<ul class="dropdown-menu" style="padding: 20px">
					<?php if (!empty($_SESSION['user']) and $_SESSION['user']['group_id'] == 2) { ?>
						<p><a href='profile.php' class='btn btn-default'>Профиль пользователя</a></p>
						<p><a href='reg_requests.php' class='btn btn-default'>Заявки на регистрацию</a></p>
						<p><a href='reg_list.php' class='btn btn-default'>Подчиненные</a></p>
						<p><a href='items_list.php' class='btn btn-default'>Справочник товаров</a></p>		
						<p><a href='reports.php?seller_id=<?php echo $_SESSION['user']['id']?>&order_date=<?php $cdt = new DateTime(); echo($cdt->format('Y-m-d'));?>' class='btn btn-default'>Отчеты</a></p>								
						<p><a href='orders_list.php?filter=1' class='btn btn-default'>Текущие заказы</a></p>
						<p><a href='orders_list.php?archive=1&filter=1' class='btn btn-default'>Архив заказов</a></p>
						<p><a href='preorders_list.php' class='btn btn-default'>Посещения</a></p>
					<?php } else if (!empty($_SESSION['user']) and $_SESSION['user']['group_id'] == 1) { ?> 
						<p><a href='profile.php' class='btn btn-default'>Профиль пользователя</a></p>
						<p><a href='orders_list.php?seller_id=0&oper=1&filter=1' class='btn btn-default'>Текущие заказы</a></p>
						<p><a href='orders_list.php?seller_id=0&archive=1&filter=1' class='btn btn-default'>Архив заказов</a></p>
						<p><a href='preorders_list.php?seller_id=0' class='btn btn-default'>Посещения</a></p>
					<?php } ?>
				</ul>
			</li>
		</ul>
	</div>
    <div class="nav-collapse collapse">
		<?php if(empty($_SESSION['user'])) { ?>
			<ul class="nav navbar-nav pull-right">
				<li class="dropdown">
					<a href="#" class="dropdown-toggle" data-toggle="dropdown">Войти <b class="caret"></b></a>
					<ul class="dropdown-menu" style="padding: 20px">
					<form action="index.php" method="post"> 
						Email:<br /> 
						<input type="text" name="email" value="<?php echo $submitted_email; ?>" /> 
						<br /><br /> 
						Пароль:<br /> 
						<input type="password" name="password" value="" /> 
						<br /><br /> 
						<input type="submit" class="btn btn-default" value="Войти" /> 
					</form>
					</ul>
				</li>
			</ul>
			<ul class="nav navbar-nav pull-right">
				<li><a href="register.php" class="pull-right">Зарегистрироваться</a></li>
			</ul>
		<?php } else { 
					if ($select_sellers OR $select_items) { ?>					
					<ul class="nav navbar-nav">
						<li>
							<form class="form-inline" role="form">
								<?php if ($_GET['type']) { ?>
									<input type='hidden' name='type' value='<?php echo $_GET['type']?>'>
								<?php } ?>
								<?php if ($_GET['archive']) { ?>
									<input type='hidden' name='archive' value='1'>
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
								<?php if ($_SESSION['user']['group_id'] == 1) { ?>
									<div class="form-group">	
										<select name="oper" class="form-control" id="oper" style="max-width: 80px" onchange="$(this).closest('form').trigger('submit');">
											<option value='0' <?php echo (!$_GET['oper'] OR $_GET['oper'] == '0') ? 'selected=selected' : ''?>>Все</option>
											<option value='1' <?php echo ($_GET['oper'] == '1') ? 'selected=selected' : ''?>>Мои</option>
											<option value='2' <?php echo ($_GET['oper'] == '2') ? 'selected=selected' : ''?>>Ничьи</option>											
										</select>
									</div>
								<?php } ?>	
								<?php if ($select_sellers) { ?>
									<div class="form-group">
										<label for="seller_id">От:</label>
									</div>
									<div class="form-group">
										<select name="seller_id" class="form-control" id="seller_id" style="max-width: 180px" onchange="$(this).closest('form').trigger('submit');">
											<option value='0' <?php echo ($_GET['seller_id'] == '0') ? 'selected=selected' : ''?>>Всех</option>
											<?php
											foreach ($select_sellers as $seller) {
												echo "<option ".(($_GET['seller_id'] == $seller['id'] or (!$_GET['seller_id'] and $_GET['seller_id'] != '0' and $_SESSION['user']['group_id']==2 and $seller['id'] == $_SESSION['user']['id'])) ? 'selected=selected' : '')." value=".$seller['id'].">".$seller['username']."</option>";
											}
											?>
										</select>
									</div>
								<?php } ?>
								<div class="form-group">	
									<?php if ((!$_GET['seller_id'] OR $_GET['seller_id'] != '0' OR $_SESSION['user']['group_id']==2) AND $select_items) { ?>
										<select name="item_id" class="form-control" id="item_id" style="max-width: 180px" onchange="$(this).closest('form').trigger('submit');">
											<option value='0' <?php echo (!$_GET['item_id'] OR $_GET['item_id'] == '0') ? 'selected=selected' : ''?>>Все товары</option>
											<?php
											foreach ($select_items as $item) {
												echo "<option ".($_GET['item_id'] == $item['uuid'] ? 'selected=selected' : '')." value=".$item['uuid'].">".$item['name']."</option>";
											}
											?>
										</select>
									<?php } ?>
								</div>
								<div class="form-group">	
									<?php if (($_GET['filter'] AND $_GET['filter'] == '1') OR ($_GET['count_days'] OR $_GET['count_days'] == '0')) { ?>
										<select name="count_days" class="form-control" id="item_id" style="max-width: 180px" onchange="$(this).closest('form').trigger('submit');">
		<option value='0' <?php echo (!$_GET['count_days'] OR $_GET['count_days'] == '0') ? 'selected=selected' : ''?>>Без фильтра</option>
									<option value='0' <?php echo (!$_GET['count_days'] OR $_GET['count_days'] == '0') ? 'selected=selected' : ''?>>Без фильтра</option>
											<option value='2' <?php echo ($_GET['count_days'] == '2') ? 'selected=selected' : ''?>>Застывшие (более 2 дней) заказы</option>";
											<option value='3' <?php echo ($_GET['count_days'] == '3') ? 'selected=selected' : ''?>>Отправлены более 3 дней назад</option>";
											<option value='7' <?php echo ($_GET['count_days'] == '7') ? 'selected=selected' : ''?>>Отправлены более 7 дней назад</option>";										</select>
									<?php } ?>
								</div>								
								<a class="btn btn-default" href="javascript:window.location.reload();">Обновить</a>
							</form>
						</li>
					</ul>
			<?php	} ?>
			<div class="nav-collapse collapse">
				<ul class="nav navbar-nav pull-right">
					<li><a href="logout.php">Выйти</a></li>
				</ul>
			</div>
			<?php if ($statuses_step1) { ?>
				<div class="nav-collapse collapse">
					<ul class="nav navbar-nav pull-right">
						<li class="dropdown">
							<a href="#" class="dropdown-toggle" data-toggle="dropdown"><?php if (($_GET['status_id'] and $_GET['status_id'] != '0') or $_GET['order_date']) {echo '<b style="color:red;">Фильтр</b>';} else {echo "Фильтр";} ?><b class="caret"></b></a>
							<ul class="dropdown-menu" style="padding: 20px">
							<form role="form">
									<?php if ($_GET['type']) { ?>
										<input type='hidden' name='type' value='<?php echo $_GET['type']?>'>
									<?php } ?>
									<?php if ($_GET['archive']) { ?>
										<input type='hidden' name='archive' value='1'>
									<?php } ?>
									<?php if ($_GET['seller_id'] or $_GET['seller_id']=='0') { ?>
										<input type='hidden' name='seller_id' value='<?php echo $_GET['seller_id']?>'>
									<?php } ?>
									<?php if ($_GET['item_id']) { ?>
										<input type='hidden' name='item_id' value='<?php echo $_GET['item_id'] ?>'>
									<?php } ?>								
									<div class="form-group">
										<label for="order_date">Дата заказа (начало периода):</label>
										<input type='text' class="form-control datepick_ctrl" name='order_date' value='<?php echo $_GET['order_date'] ?>'>
										<label for="order_date">Конец периода:</label>
										<input type='text' class="form-control datepick_ctrl" name='order_date_end' value='<?php echo $_GET['order_date_end'] ?>'>
										<label for="status_id">Статус:</label>									
										<select name="status_id" class="form-control" id="status_id" style="width:200px">
											<option value='0' <?php echo (!$_GET['status_id'] OR $_GET['status_id'] == '0') ? 'selected=selected' : ''?>>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Все статусы</option>
											<?php
												$t_states = '';
												foreach ($statuses_step1 as $status) {
													if ($status['id'] == 110) {
														$t_states = "<option></option><option style='".($status_cnts ? $status_cnts[$status['id']] ? '' : 'color: lightgray' : '')."'".($_GET['status_id'] == $status['id'] ? 'selected=selected' : '')." value=".$status['id'].">".($status_cnts ? $status_cnts[$status['id']] ? '('.str_pad($status_cnts[$status['id']], 2, '0', STR_PAD_LEFT).') ' : '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' : '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;').'1 / '.($status['automatic']?'(АВТО) ':'').$status['act'].' / '.$status['name']."</option><option></option>".$t_states;
													} else {
														$d = $status['act'] ? ' / ' : '';
														$t_states .= "<option style='".($status_cnts ? $status_cnts[$status['id']] ? '' : 'color: lightgray' : '')."'".($_GET['status_id'] == $status['id'] ? 'selected=selected' : '')." value=".$status['id'].">".($status_cnts ? $status_cnts[$status['id']] ? '('.str_pad($status_cnts[$status['id']], 2, '0', STR_PAD_LEFT).') ' : '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' : '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;').($status['act']?'1 / ':'').($status['automatic']?'(АВТО) ':'').$status['act'].$d.$status['name']."</option>";
													}
												}
												echo $t_states;
												if ($statuses_step2) {
													foreach ($statuses_step2 as $status) {
														if ($status['id'] != 10) {
															$d = $status['act'] ? ' / ' : '';
															echo "<option style='".($status_cnts ? $status_cnts[$status['id']] ? '' : 'color: lightgray' : '')."'".($_GET['status_id'] == $status['id'] ? 'selected=selected' : '')." value=".$status['id'].">".($status_cnts ? $status_cnts[$status['id']] ? '('.str_pad($status_cnts[$status['id']], 2, '0', STR_PAD_LEFT).') ' : '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' : '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;').($status['act']?'2 / ':'').($status['automatic']?'(АВТО) ':'').$status['act'].$d.$status['name']."</option>";
														}
													}
												}
												if ($statuses_step3) {
													foreach ($statuses_step3 as $status) {
														$d = $status['act'] ? ' / ' : '';
														echo "<option style='".($status_cnts ? $status_cnts[$status['id']] ? '' : 'color: lightgray' : '')."'".($_GET['status_id'] == $status['id'] ? 'selected=selected' : '')." value=".$status['id'].">".($status_cnts ? $status_cnts[$status['id']] ? '('.str_pad($status_cnts[$status['id']], 2, '0', STR_PAD_LEFT).') ' : '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' : '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;').($status['act']?'3 / ':'').($status['automatic']?'(АВТО) ':'').$status['act'].$d.$status['name']."</option>";
													}	
												}													
											?>
										</select>									
									</div>					  
								  <button type="submit" class="btn btn-default">Показать</button>
								</form>
							</ul>
						</li>
					</ul>
				</div>
			<?php } ?>
			<?php if ($statuses_step1) { ?>
				<div class="nav-collapse collapse">
					<ul class="nav navbar-nav pull-right">
						<li class="dropdown">
							<a href="#" class="dropdown-toggle" data-toggle="dropdown"><?php if (($_GET['search_key'] and $_GET['search_key'] != '0') or $_GET['search_text']) {echo '<b style="color:red;">Поиск</b>';} else {echo "Поиск";} ?><b class="caret"></b></a>
							<ul class="dropdown-menu" style="padding: 20px">
							<form role="form">
									<?php if ($_GET['type']) { ?>
										<input type='hidden' name='type' value='<?php echo $_GET['type']?>'>
									<?php } ?>
									<?php if ($_GET['archive']) { ?>
										<input type='hidden' name='archive' value='1'>
									<?php } ?>
									<?php if ($_GET['seller_id'] or $_GET['seller_id']=='0') { ?>
										<input type='hidden' name='seller_id' value='<?php echo $_GET['seller_id']?>'>
									<?php } ?>
									<?php if ($_GET['item_id']) { ?>
										<input type='hidden' name='item_id' value='<?php echo $_GET['item_id'] ?>'>
									<?php } ?>								
									<div class="form-group">
										<label for="search_text">Текст для поиска:</label>
										<input type='text' class="form-control" name='search_text' value='<?php echo ($_GET['search_text']) ? $_GET['search_text'] : 'Введите текст...' ?>' 
											 onfocus="if(this.value == 'Введите текст...') this.value = ''" onblur="this.value = if(!this.value || this.value.lehgth === 0) 'Введите текст...'" />
										<label for="search_key">Ключ поиска:</label>									
										<select name="search_key" class="form-control" id="search_key" style="width:200px">
											<option value='0' <?php echo (!$_GET['search_key'] OR $_GET['search_key'] == '0') ? 'selected=selected' : ''?>>Выберите поисковый ключ</option>
											<option value ='1' <?php echo (!$_GET['search_key'] OR $_GET['search_key'] == '1') ? 'selected=selected' : ''?>>Телефон</option>
											<option value ='2' <?php echo (!$_GET['search_key'] OR $_GET['search_key'] == '2') ? 'selected=selected' : ''?>>Номер декларации</option>
											<option value ='3' <?php echo (!$_GET['search_key'] OR $_GET['search_key'] == '3') ? 'selected=selected' : ''?>>ФИО покупателя</option>
										</select>									
									</div>					  
								  <button type="submit" class="btn btn-default">Показать</button>
								</form>
							</ul>
						</li>
					</ul>
				</div>
			<?php } ?>	
		<?php }?>
    </div>
  </div>
</div>

<script type='text/javascript'>
	  $('.datepick_ctrl').datetimepicker({
		format: 'yyyy-mm-dd',
		autoclose: true,
        todayBtn: true,
		startView: 2,
		minView: 2,
		language: 'ru',
		weekStart: 1
	  });
</script>