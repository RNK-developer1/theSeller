<?php
	require("config.php"); 	
	
	$uid = uniqid(md5(rand()), true);
	
	$query = " 
			SELECT * FROM users WHERE email = :email
		";		
		
	$query_params = array(
		':email' => $_SESSION['user'] ? $_SESSION['user']['email'] : $_GET['email']
	);	
	
	try{ 
		$stmt = $db->prepare($query); 
		$result = $stmt->execute($query_params); 
	} 
	catch(PDOException $ex){ die("Невозможно выполнить запрос: " . $ex->getMessage()); } 
	$user = $stmt->fetch();	
	
	if ($user) {
		$aquery = "INSERT INTO password_reset_req(user_id, auth_token) VALUES
					(	:user_id,
						:token		) 
				   ON DUPLICATE KEY UPDATE auth_token=:token;";
		
		$aquery_params = array( 
			':user_id' => $user['id'],
			':token' => $uid
		); 
		
		try{ 
			$astmt = $db->prepare($aquery); 
			$aresult = $astmt->execute($aquery_params); 
		} 
		catch(PDOException $ex){ die("Невозможно выполнить запрос: " . $ex->getMessage()); }		
	
		$mail_headers = "From: theSeller - регистрация <no-reply-reg@goodthing.in.ua>\r\nMIME-Version: 1.0\r\nContent-Type: text/html; charset=utf-8\r\n";
		mail($user['email'], "Изменение пароля theSeller", "Здравствуйте, ".$user['username']."<br/>Если Вы хотите изменить свой пароль - перейдите по ссылке:<br/><a href=\"http://www.goodthing.in.ua/theseller/password_reset.php?id=".$uid."\">"."http://www.goodthing.in.ua/theseller/password_reset.php?id=".$uid."</a>", $mail_headers);	
		
		echo 'Вам отправлено письмо со ссылкой для изменения пароля на адрес: '.$user['email'];
		echo '<br/>Внимание! Проверяйте свою папку "СПАМ"!';
	}
?>