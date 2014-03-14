<?php
	require("config.php"); 
	
	if (empty($_SESSION['user']) || $_SESSION['user']['active'] == 0 || $_SESSION['user']['group_id'] != 2) {
		header("Location: index.php"); 
		die("Перенаправление: index.php"); 
	}
	
	$query = " 
            SELECT 
                u1.id, u1.email as 'email', u1.phone as 'phone', groups.name as 'group'
            FROM users as u1, users as p1, groups
            WHERE 
                u1.parent_id = p1.id AND
				p1.email = :parent_email AND
				groups.id = u1.group_id AND
				u1.active = 0
        "; 
	$query_params = array( 
		':parent_email' => $_SESSION['user']['email']
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
<?php include 'top_menu.php' ?>
<div class="container">
	<h3>Заявки на регистрацию</h3>	
	<table class='table table-hover table-bordered'>
	<tr><th>Email</th><th>Телефон</th><th>Группа</th><th>Действие</th></tr>
	<?php	foreach ($requests as $row){ ?>
				<tr>
				<td><?php echo $row['email'] ?></td>
				<td><?php echo $row['phone'] ?></td>
				<td><?php echo $row['group'] ?></td>
				<td><a href='reg_approve.php?id=<?php echo $row['id']?>' class='btn btn-success btn-sm'>Принять</a></td>
				</tr>
	<?php	} ?>
	</table>
</div>
</body>
</html>