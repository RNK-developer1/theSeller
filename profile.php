<?php
	require("config.php"); 
	
	if (empty($_SESSION['user']) || $_SESSION['user']['active'] == 0) {
		header("Location: index.php"); 
		die("Перенаправление: index.php"); 
	}
	
	if($_SESSION['user']['group_id'] == 2) {
		$query = " 
				SELECT 
					users.id as 'id', users.username as username, users.email as 'email', users.phone as 'phone'
				FROM
					users
				WHERE 
					users.group_id = 1 AND
					users.active = 1 AND
					users.id NOT IN (
						SELECT 
							users.id as 'id'
						FROM
							operators_for_sellers, users
						WHERE 
							users.group_id = 1 AND
							operators_for_sellers.operator_id = users.id AND
							operators_for_sellers.seller_id = :seller_id AND
							users.active = 1
					)
				ORDER BY
					users.email
			"; 
			
		$query_params = array( 
			':seller_id' => $_SESSION['user']['id']
		);		
		 
		try{ 
			$stmt = $db->prepare($query); 
			$result = $stmt->execute($query_params); 
		} 
		catch(PDOException $ex){ die("Невозможно выполнить запрос: " . $ex->getMessage()); } 
		
		$all_operators = $stmt->fetchAll();
		
		$query = " 
				SELECT 
					users.id as 'id', users.username as username, users.email as 'email', users.phone as 'phone'
				FROM
					operators_for_sellers, users
				WHERE 
					users.group_id = 1 AND
					operators_for_sellers.operator_id = users.id AND
					operators_for_sellers.seller_id = :seller_id AND
					users.active = 1
			"; 
			
		$query_params = array( 
			':seller_id' => $_SESSION['user']['id']
		);		
		 
		try{ 
			$stmt = $db->prepare($query); 
			$result = $stmt->execute($query_params); 
		} 
		catch(PDOException $ex){ die("Невозможно выполнить запрос: " . $ex->getMessage()); } 
		
		$assigned_operators = $stmt->fetchAll();
		
		$query = "SELECT * FROM	cities ORDER BY nameRu"; 		
			 
		try{ 
			$stmt = $db->prepare($query); 
			$result = $stmt->execute(); 
		} 
		catch(PDOException $ex){ die("Невозможно выполнить запрос: " . $ex->getMessage()); } 
		
		$cities = $stmt->fetchAll();
		
		$query = "SELECT * FROM	warehouses ORDER BY addressRu"; 		
		 
		try{ 
			$stmt = $db->prepare($query); 
			$result = $stmt->execute(); 
		} 
		catch(PDOException $ex){ die("Невозможно выполнить запрос: " . $ex->getMessage()); } 
		
		$warehouses = $stmt->fetchAll();
	}
?>

<!doctype html>
<html lang="ru">
<?php include 'header.php' ?>
<body>
<?php include 'top_menu.php' ?>
<div class="container">
	<h3>Личные данные</h3>    
	<table class=''>
	<tr>
	<form action="profile_edit.php" method="post"> 
		<td>
			<label>Фамилия Имя</label><br/>
			<input class="form-control" type="text" name="username" value="<?php echo $_SESSION['user']['username'];?>" /> <br/>
		<td/><td>
			<label>Телефон</label> <br/>
			<input class="form-control"  type="text" name="phone" value="<?php echo $_SESSION['user']['phone'];?>" /> <br/>				
	    </td>
		</tr>
		<?php if($_SESSION['user']['group_id'] == 2) { ?>
		<tr>
		<td>
			<label>API ключ Новой Почты</label> <br/>
			<input class="form-control" type="text" name="newpost_api" value="<?php echo $_SESSION['user']['newpost_api'];?>" /> <br/>				
	    </td>
		<td>
			<label>Карточка Новой Почты</label> <br/>
			<input class="form-control" type="text" name="newpost_id" value="<?php echo $_SESSION['user']['newpost_id'];?>" /> <br/>				
	    </td>
		<td>
			<label>Пароль Новой Почты</label> <br/>
			<input class="form-control" type="text" name="newpost_psw" value="<?php echo $_SESSION['user']['newpost_psw'];?>" /> <br/>				
	    </td>
		</tr>
		<tr><td>
			<label>Alphaname(sms-fly)</label><br/>
			<input class="form-control" type="text" name="alphaname" value="<?php echo $_SESSION['user']['alphaname'];?>" /> <br/>
		<td/>
		</tr>
		<?php } ?>
		<?php if($_SESSION['user']['group_id'] == 2) { ?><tr><td>
			<label>Отделение Новой Почты для отправки грузов</label> <br/>
			<select class="form-control city">												
				<?php 
					if (!$_SESSION['user']['sender_whs_ref']) { 
							$_SESSION['user']['sender_whs_ref'] = '11b440b2-edc9-11e0-b926-0026b97ed48a';
					}
					$t_whs = NULL;
					foreach ($warehouses as $whs) { if ($whs['ref'] == $_SESSION['user']['sender_whs_ref']) {$t_whs = $whs; break;} }
					
					$city_ref = NULL; 
					foreach ($cities as $city) { ?>
					<?php
						$selected_city = '';												
						if ($t_whs and $t_whs['city_ref'] == $city['ref']) { $selected_city = "selected=\"selected\"";}
					?>
					<option <?php echo $selected_city;?> value="<?php echo $city['ref']; ?>"><?php echo $city['nameRu']; ?></option>
				<?php } ?>
			</select>											
			<select class="form-control warehouse whs" name="sender_whs_ref">
				<?php foreach ($warehouses as $whs) { ?>
					<?php 
						$selected_whs = '';
						if ($whs['ref'] == $_SESSION['user']['sender_whs_ref']) { $selected_whs = "selected=\"selected\""; }						
					?>
					<option <?php echo $selected_whs;?> class="<?php echo $whs['city_ref'];?>" value="<?php echo $whs['ref'];?>"><?php echo $whs['addressRu'];?></option>
				<?php } ?>
			</select>
	    </td></tr>
		<tr>
			<td>
				<label>ФИО (на укр.)</label><br/>
				<input class="form-control"  type="text" name="fio_ukr" value="<?php echo $_SESSION['user']['fio_ukr'];?>" /> <br/>				
			</td>
			<td>
				<label>Паспорт серия</label><br/>
				<input class="form-control"  type="text" name="pass_s" value="<?php echo $_SESSION['user']['pass_s'];?>" /> <br/>				
			</td>
			<td>
				<label>Паспорт номер</label><br/>
				<input class="form-control"  type="text" name="pass_n" value="<?php echo $_SESSION['user']['pass_n'];?>" /> <br/>				
			</td>
		</tr>
		<tr>
			<td>
				<label>Паспорт кем выдан (коротко, на укр. - например: Жовтневим РОУМВД)</label><br/>
				<input class="form-control"  type="text" name="pass_issued" value="<?php echo $_SESSION['user']['pass_issued'];?>" /> <br/>				
			</td>
			<td>
				<label>Паспорт когда выдан (дата на укр.)</label><br/>
				<input class="form-control"  type="text" name="pass_i_date" value="<?php echo $_SESSION['user']['pass_i_date'];?>" /> <br/>				
			</td>
		</tr>
		<tr>
			<td>
				<label>Адрес регистрации (прописка)</label><br/>
				<input class="form-control"  type="text" name="adr" value="<?php echo $_SESSION['user']['adr'];?>" /> <br/>				
			</td>
		</tr>
		
		<?php } ?>
		<tr><td colspan=2><input type="submit" class="btn btn-default" value="Сохранить изменения" />		
		</td>
    </form>
	<tr><td>&nbsp;</td></tr>
	<tr><td colspan=2><a class="btn btn-default" href='password_change_request.php'>Запросить изменение пароля</a></td></tr>
	</tr>
	</table>
	<?php if($_SESSION['user']['group_id'] == 2) { ?>
		<h3>Операторы</h3>    
		<table class='table table-hover table-bordered'>
		<tr><th>Состояние</th><th>Оператор</th><th>Телефон оператора</th><th>Действие</th></tr>
		<?php	foreach ($all_operators as $row){ ?>
					<tr>
						<td>Отключен</td>
						<td><?php echo $row['username'].'<br/>'.$row['email'] ?></td>
						<td><?php echo $row['phone'] ?></td>
						<td><a href='operator_assign.php?oid=<?php echo $row['id']?>' class='btn btn-success btn-sm'>Подключить</a></td>
					</tr>
		<?php	}
				foreach ($assigned_operators as $row){ ?>
					<tr>
						<td>Подключен</td>
						<td><?php echo $row['username'].'<br/>'.$row['email'] ?></td>
						<td><?php echo $row['phone'] ?></td>
						<td><a href='operator_unassign.php?oid=<?php echo $row['id']?>' class='btn btn-danger btn-sm'>Отключить</a></td>
					</tr>
		
		<?php	} ?>
		</table>
	<?php } ?>
</div>
<script type='text/javascript'>
	$('.warehouse').chained('.city');
</script>
</body>
</html>