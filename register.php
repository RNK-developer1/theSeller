<?php 
    require("config.php");
    if(!empty($_POST)) 
    { 
        // Ensure that the user fills out fields 
        if(empty($_POST['email'])) 
        { die("Введите Email"); } 
        if(empty($_POST['password'])) 
        { die("Введите пароль"); } 
        if(!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) 
        { die("Плохой E-Mail адрес"); } 
		if(!filter_var($_POST['parent_email'], FILTER_VALIDATE_EMAIL)) 
        { die("Плохой E-Mail адрес руководителя"); } 
         
        $query = " 
            SELECT 
                1 
            FROM users 
            WHERE 
                email = :email 
        "; 
        $query_params = array( 
            ':email' => $_POST['email'] 
        ); 
        try { 
            $stmt = $db->prepare($query); 
            $result = $stmt->execute($query_params); 
        } 
        catch(PDOException $ex){ die("Невозможно выполнить запрос: " . $ex->getMessage());} 
        $row = $stmt->fetch(); 
        if($row){ die("Этот E-Mail уже используется"); } 
		
		$query = " 
            SELECT 
                id 
            FROM users 
            WHERE 
                email = :parent_email 
        "; 
        $query_params = array( 
            ':parent_email' => $_POST['parent_email'] 
        ); 
        try { 
            $stmt = $db->prepare($query); 
            $result = $stmt->execute($query_params); 
        } 
        catch(PDOException $ex){ die("Невозможно выполнить запрос: " . $ex->getMessage());} 
        $row = $stmt->fetch(); 
		$parent_id = NULL;
        if (!$row)
			{ die("Нет руководителя с таким E-Mail"); }
		else 
			{ $parent_id = $row['id']; }
		        
        // Add row to database 
        $query = " 
            INSERT INTO users ( 
				username,
                password, 
                salt,
				phone,
				group_id,
				parent_id,
				email 
            ) VALUES ( 
				:username,
                :password, 
                :salt,
				:phone,
				:group_id,
				:parent_id,
				:email 
            ) 
        "; 
         
        // Security measures
        $salt = dechex(mt_rand(0, 2147483647)) . dechex(mt_rand(0, 2147483647)); 
        $password = hash('sha256', $_POST['password'] . $salt); 
		$name_email = explode('@',$_POST['email']);
        for($round = 0; $round < 65536; $round++){ $password = hash('sha256', $password . $salt); } 
        $query_params = array( 
			':username' => ($_POST['username'] ? $_POST['username'] : $name_email[0]),
            ':password' => $password, 
            ':salt' => $salt, 
			':phone' => $_POST['phone'],			
			':group_id' => $_POST['group'],			
			':parent_id' => $parent_id,
            ':email' => $_POST['email']
        ); 
        try {  
            $stmt = $db->prepare($query); 
			$result = $stmt->execute($query_params); 
        } 
        catch(PDOException $ex){ die("Невозможно выполнить запрос: " . $ex->getMessage()); } 
        header("Location: index.php"); 
        die("Перенаправление: index.php"); 
    } 
?>

<!doctype html>
<html lang="ru">
<?php include 'header.php' ?>
<body>
<?php include 'top_menu.php' ?>

<div class="container">   
<h3>Регистрация</h3>
    <form action="register.php" method="post"> 
		<div class='row'>
			<div class="col-lg-2">
				<label>Логин (Email)</label> 
				<input type="text" name="email" value="" /> 
				<label>Пароль</label>
				<input type="password" name="password" value="" /> <br /><br />
			</div>
			<div class="col-lg-2">		    
				<label>Фамилия Имя</label><br/>
				<input type="text" name="username" value="" /> <br/>
				<label>Телефон</label> <br/>
				<input type="text" name="phone" value="" /> <br/>
				<label>Группа</label><br/>
				<select name="group">
					<option value='1'>Оператор</option>
					<option value='2' selected='selected'>Предприниматель</option>
				</select>
				<label>Email руководителя</label> <br/>
				<input type="text" name="parent_email" value="" /> 				
			</div>
		</div>		
	    <input type="submit" class="btn btn-default" value="Зарегистрироваться" /> 
    </form>
</div>

</body>
</html>
