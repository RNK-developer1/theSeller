<?php 
    require("config.php"); 
    $submitted_email = ''; 
    if(!empty($_POST)){ 
        $query = " 
            SELECT 
                *
            FROM users 
            WHERE 
                email = :email 
        "; 
        $query_params = array( 
            ':email' => $_POST['email'] 
        ); 
         
        try{ 
            $stmt = $db->prepare($query); 
            $result = $stmt->execute($query_params); 
        } 
        catch(PDOException $ex){ die("Невозможно выполнить запрос: " . $ex->getMessage()); } 
        $login_ok = false; 
        $row = $stmt->fetch(); 
        if($row){ 
            $check_password = hash('sha256', $_POST['password'] . $row['salt']); 
            for($round = 0; $round < 65536; $round++){
                $check_password = hash('sha256', $check_password . $row['salt']);
            } 
            if($check_password === $row['password']){
                $login_ok = true;
            } 
        } 

        if($login_ok){ 
            unset($row['salt']); 
            unset($row['password']); 
            $_SESSION['user'] = $row;  
			if ($_SESSION['user']['group_id'] != 2) {
				header("Location: orders_list.php?seller_id=0"); 
				die("Перенаправление: orders_list.php"); 
			} else {
				header("Location: index.php"); 
				die("Перенаправление: index.php"); 
			}
        } 
        else{ 
            echo "Неверное имя пользователя или пароль! <a href='password_change_request.php?email=".$_POST['email']."'>Восстановить пароль для ".$_POST['email']."</a>"; 
            $submitted_email = htmlentities($_POST['email'], ENT_QUOTES, 'UTF-8'); 
        } 
    } 
?> 

<!doctype html>
<html lang="ru">
<?php include 'header.php' ?>
<body>
<?php include 'top_menu.php' ?>
<div class="container">
    <h3>Добро пожаловать в систему обработки заказов theSeller</h3><br/>
    <?php if(empty($_SESSION['user'])) { ?>	
		<ul>
			<h3>Войдте в систему</h3>
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
		<br/><br/>
		<ul>
			<li>Или пройдите <a href='register.php'>регистрацию</a></li>
			<li>Ожидайте активации вашей учетной записи администратором системы</li>
		</ul>
	<?php } else if ($_SESSION['user']['active'] == 0) { ?>
		<p>Ожидайте активации вашей учетной записи администратором системы</p>
	<?php } else if ($_SESSION['user']['group_id'] == 2) { ?>
		<p><a href='profile.php' class='btn btn-default'>Профиль пользоваетеля</a></p>
		<p><a href='reg_requests.php' class='btn btn-default'>Заявки на регистрацию</a></p>
		<p><a href='reg_list.php' class='btn btn-default'>Подчиненные</a></p>
		<p><a href='items_list.php' class='btn btn-default'>Справочник товаров</a></p>
		<p><a href='reports.php?seller_id=<?php echo $_SESSION['user']['id']?>&order_date=<?php $cdt = new DateTime(); echo($cdt->format('Y-m-d'));?>' class='btn btn-default'>Отчеты</a></p>		
		<p><a href='orders_list.php' class='btn btn-default'>Текущие заказы</a></p>
		<p><a href='orders_list.php?archive=1' class='btn btn-default'>Архив заказов</a></p>
		<p><a href='preorders_list.php' class='btn btn-default'>Посещения</a></p>
	<?php } else if ($_SESSION['user']['group_id'] == 1) { ?> 
		<p><a href='profile.php' class='btn btn-default'>Профиль пользоваетеля</a></p>
		<p><a href='orders_list.php?seller_id=0&oper=1' class='btn btn-default'>Текущие заказы</a></p>
		<p><a href='orders_list.php?seller_id=0&archive=1' class='btn btn-default'>Архив заказов</a></p>
		<p><a href='preorders_list.php?seller_id=0' class='btn btn-default'>Посещения</a></p>
	<?php } ?>
</div>
</body>
</html>
