<?php
	require("config.php"); 	
	
	$query = "SELECT users.* FROM users, password_reset_req WHERE users.id = password_reset_req.user_id AND password_reset_req.auth_token = :id";
	$query_params = array(':id' => $_GET['id']);
	
	try{ 
		$stmt = $db->prepare($query); 
		$result = $stmt->execute($query_params); 
	} 
	catch(PDOException $ex){ die("Невозможно выполнить запрос: " . $ex->getMessage()); } 
	
	$user = $stmt->fetch();	
	
	if ($user) {
		if ($_GET['new_password']) {
			$salt = dechex(mt_rand(0, 2147483647)) . dechex(mt_rand(0, 2147483647)); 
			$password = hash('sha256', $_GET['new_password'] . $salt); 
			for($round = 0; $round < 65536; $round++){ $password = hash('sha256', $password . $salt); }        
		
			$query = "UPDATE users SET password = :password, salt = :salt WHERE id = :id";
			$query_params = array(
				':id' => $user['id'],
				':password' => $password,
				':salt' => $salt
			);
			
			try{ 
				$stmt = $db->prepare($query); 
				$result = $stmt->execute($query_params); 
			} 
			catch(PDOException $ex){ die("Невозможно выполнить запрос: " . $ex->getMessage()); } 			
			
			$query = "DELETE FROM password_reset_req WHERE auth_token = :id";
			$query_params = array(
				':id' => $_GET['id']
			);
			
			try{ 
				$stmt = $db->prepare($query); 
				$result = $stmt->execute($query_params); 
			} 
			catch(PDOException $ex){ die("Невозможно выполнить запрос: " . $ex->getMessage()); } 			
			
			unset($_SESSION['user']);
			
			header("Location: index.php"); 
			die("Перенаправление: index.php"); 
		}
		else {
		echo "Здравствуйте, ".$user['username']."!<br/><br/>";
	?>	
		<form action='password_reset.php' method='get'>
			<input type='hidden' name='id' value='<?php echo $_GET['id'];?>'>
			<p>Введите новый пароль</p>
			<p><input type='text' name='new_password'></p>
			<p><input type='submit' value='Изменить пароль'></p>
		</form>
<?php }
	} else { echo "Неверная ссылка (или старая). Попробуйте ещё раз запросить изменение пароля"; }?>