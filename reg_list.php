<?php
	require("config.php"); 
	
	if (empty($_SESSION['user']) || $_SESSION['user']['active'] == 0 || $_SESSION['user']['group_id'] != 2) {
		header("Location: index.php"); 
		die("Перенаправление: index.php"); 
	}
	
	$query = " 
            SELECT 
                u1.id, u1.username as 'username', u1.email as 'email', u1.phone as 'phone', groups.name as 'group'
            FROM users as u1, users as p1, groups
            WHERE 
                u1.parent_id = p1.id AND
				p1.email = :parent_email AND
				groups.id = u1.group_id AND
				u1.active = 1
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
	<h3>Подчиненные</h3>	
	<table class='table table-hover table-bordered'>
	<tr><th>ФИО</th><th>Email</th><th>Телефон</th><th>Группа</th><th>Действие</th></tr>
	<?php	foreach ($requests as $row){ ?>
				<tr>
				<td><?php echo $row['username'] ?></td>
				<td><?php echo $row['email'] ?></td>
				<td><?php echo $row['phone'] ?></td>
				<td><?php echo $row['group'] ?></td>
				<td><a href='reg_remove.php?back=list&id=<?php echo $row['id']?>' data-confirm='Удалить пользователя? (учётная запись будет неактивна и отображаться в заявках на регистрацию)' class='btn btn-danger btn-sm'>Удалить</a></td>
				</tr>
	<?php	} ?>
	</table>
</div>

<script type='text/javascript'>
$('a[data-confirm]').click(function(ev) {
	var href = $(this).attr('href');

	if (!$('#dataConfirmModal').length) {
		$('body').append('<div id="dataConfirmModal" class="modal fade" role="dialog" aria-labelledby="dataConfirmLabel" aria-hidden="true"><div class="modal-dialog"><div class="modal-content"><div class="modal-header"><button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button><h3 id="dataConfirmLabel">Подтвердите действие</h3></div><div class="modal-body"></div><div class="modal-footer"><button class="btn" data-dismiss="modal" aria-hidden="true">Отмена</button><a class="btn btn-primary" id="dataConfirmOK">OK</a></div></div></div></div>');
	} 
	$('#dataConfirmModal').find('.modal-body').text($(this).attr('data-confirm'));
	$('#dataConfirmOK').attr('href', href);
	$('#dataConfirmModal').modal({show:true});
	return false;
});
</script>
</body>
</html>