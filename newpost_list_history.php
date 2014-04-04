<?php
	require("config.php"); 	
	
	if (empty($_SESSION['user']) || $_SESSION['user']['active'] == 0) {
		header("Location: index.php"); 
		die("Перенаправление: index.php"); 
	}

	$query = " 
		SELECT 
			newpost_lists_history.list as list,
			newpost_lists_history.date as date,
			users.email as user
		FROM newpost_lists_history, users
		WHERE 				
			users.id = newpost_lists_history.author_id
		ORDER BY date DESC
		";		
	$query_params = array( 		
	); 
	
	try{ 
		$stmt = $db->prepare($query); 
		$result = $stmt->execute($query_params); 
	} 
	catch(PDOException $ex){ die("Невозможно выполнить запрос: " . $ex->getMessage()); } 
	$lists = $stmt->fetchAll();
?>

<!doctype html>
<html lang="ru">
	<table border=1 cellpadding=4 style='border: 1px solid black; border-collapse: collapse;'>
	<tr><th>№</th><th>Город, область</th><th>Телефон</th><th>ФИО</th><th>Адрес, отделение НП</th><th>Стоимость</th><th>Описание</th><th>Параметры</th></tr>
	<?php foreach ($lists as $list) {
		echo "<TR><td colspan=8>".$list['date']." ".$list['user']."</td></TR>";
		echo $list['list'];
	} ?>
	</table>
</body>
</html>