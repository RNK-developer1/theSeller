<?php
	require("config.php"); 	
	
	if (empty($_SESSION['user']) || $_SESSION['user']['active'] == 0) {
		header("Location: index.php"); 
		die("Перенаправление: index.php"); 
	}

	$query = " 
			SELECT priority, act, name, id FROM `statuses` WHERE 1 ORDER BY priority DESC
		";		
	
	try{ 
		$stmt = $db->prepare($query); 
		$result = $stmt->execute(); 
	} 
	catch(PDOException $ex){ die("Невозможно выполнить запрос: " . $ex->getMessage()); } 
	$st_p = $stmt->fetchAll();	
?>

<!doctype html>
<html lang="ru">
	<table border=1 cellpadding=4 style='border: 1px solid black; border-collapse: collapse;'>
	<tr><th>Приоритет</th><th>Действие</th><th>Статус</th><th>Код</th></tr>
	<?php 
		foreach ($st_p as $st) {
		  echo "<tr><td>".$st['priority']."</td>
					<td>".$st['act']."</td>
					<td>".$st['name']."</td>
					<td>".$st['id']."</td>
				</tr>";
		}
    ?>
	</table>
</body>
</html>