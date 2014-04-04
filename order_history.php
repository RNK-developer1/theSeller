<?php 
    require("config.php");     
	
	if (empty($_SESSION['user']) || $_SESSION['user']['active'] == 0) {
		header("Location: index.php"); 
		die("Перенаправление: index.php"); 
	}
	
	$query = " 
            SELECT 
                orders.id
		".($_SESSION['user']['group_id'] == 2 ? 
		"   FROM orders, users as owner
            WHERE 
                owner.id = orders.owner_id AND
				owner.id IN (SELECT subseller_id FROM sellers_for_sellers WHERE sellers_for_sellers.seller_id = :user_id) AND
				orders.id = :order_id" :
		"   FROM orders, operators_for_sellers
            WHERE 
                orders.owner_id = operators_for_sellers.seller_id AND
				operators_for_sellers.operator_id = :user_id AND
				orders.id = :order_id
        "); 
	$query_params = array( 
		':user_id' => $_SESSION['user']['id'],
		':order_id' => $_GET['id']
	); 
	 
	try{ 
		$stmt = $db->prepare($query); 
		$result = $stmt->execute($query_params); 
	} 
	catch(PDOException $ex){ die("Невозможно выполнить запрос: " . $ex->getMessage()); } 
	
	$row = $stmt->fetch(); 
    if(!$row) {
		die("Перенаправление: index.php"); 
	}
?>

<div class="modal-header">
  <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
  <h4 class="modal-title">История заказа №<?php echo $_GET['id'];?></h4>
</div>
	
<table class='table table-striped table-condensed'><tbody><tr><th>Дата</th><th>Автор</th><th>Изменение</th><th>Комментарий</th></tr>										
	<?php 
		$query = " 	SELECT 	date, 
							users.username as 'username',
							users.email as 'user', 
							comment,
							activity,
							details
					FROM orders_audit, users
					WHERE users.id = user_id AND
							order_id = :order_id
					ORDER BY date ASC";
		$query_params = array (
			':order_id' => $_GET['id']
		);
		try{ 
			$stmt = $db->prepare($query); 
			$result = $stmt->execute($query_params); 
		} 
		catch(PDOException $ex){ die("Невозможно выполнить запрос: " . $ex->getMessage()); } 
		
		$history = $stmt->fetchAll();
		
		foreach ($history as $fact) {
			if ($fact['activity'] == 'Создана декларация Новой Почты' or $fact['activity'] == 'Создана декларация Новой Почты (ЛК)') {
				$fact['comment'] = '<a target="_new" href="http://novaposhta.ua/frontend/tracking/ru?en='.$fact['details'].'">'.$fact['details'].'</a>';
			}			
		
			echo "<tr><td>".$fact['date']."</td><td>".$fact['username'].' '.$fact['user']."</td><td title=\"".(str_replace("\"","'",$fact['details']))."\">".$fact['activity']."</td><td>".$fact['comment']."</td><td>".$fact['newpost_id']."</td></tr>";
		}
	?>
</tbody></table>

<div class="modal-footer">	
  <button type="button" class="btn btn-default" data-dismiss="modal">Закрыть</button>									  
</div>							