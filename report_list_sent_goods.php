<?php
	require("config.php"); 	
	
	$dstart = new DateTime($_GET['order_date']);
	$dend = new DateTime((!$_GET['order_date_end'] or $_GET['order_date_end'] == '') ? $_GET['order_date'] : $_GET['order_date_end']);	

	if (empty($_SESSION['user']) || $_SESSION['user']['active'] == 0 || $_SESSION['user']['group_id'] != 2) {
		header("Location: index.php"); 
		die("Перенаправление: index.php"); 
	}

	$query = " 
			SELECT 
				item.uuid as uuid,
				item.name as name
			FROM users as owner, item
			WHERE
				item.owner_id = owner.id AND
				(:seller_id = '0' OR owner.id = :seller_id)
		";		
	$query_params = array( 
			':seller_id' => $_GET['seller_id'] || (!$_GET['seller_id'] && $_GET['seller_id']=='0') ? $_GET['seller_id'] : $_SESSION['user']['id'],
		);		
			 
	try{ 
		$stmt = $db->prepare($query); 
		$result = $stmt->execute($query_params); 
	} 
	catch(PDOException $ex){ die("Невозможно выполнить запрос01: " . $ex->getMessage()); } 
	
	$select_items = $stmt->fetchAll();
	
	$query = " 
				SELECT 
					*
				FROM
					statuses				
				WHERE id <> 0
				ORDER BY id ASC
			"; 
	try{ 
		$stmt = $db->prepare($query); 
		$result = $stmt->execute($query_params); 
	} 
	catch(PDOException $ex){ die("Невозможно выполнить запрос02: " . $ex->getMessage()); } 
	
	$statuses_step1 = $stmt->fetchAll();

	$select_sellers = array();
	$query = " 
				SELECT 
					CONCAT(REPEAT(' -',sellers_for_sellers.depth),users.username) as username,
					users.id as id
				FROM sellers_for_sellers, users
				WHERE
					users.id = sellers_for_sellers.subseller_id
					AND sellers_for_sellers.seller_id = :user_id
			";		
		$query_params = array( 
			':user_id' => $_SESSION['user']['id']
		); 
			 
	try{ 
		$stmt = $db->prepare($query); 
		$result = $stmt->execute($query_params); 
	} 
	catch(PDOException $ex){ die("Невозможно выполнить запрос03: " . $ex->getMessage()); } 
	
	$select_sellers = array_merge($select_sellers,$stmt->fetchAll());	
    
    
	$query = "
				select
                    a1.print_date print_date,
                    u_pr.`username` printed_user,
                    (a2.send_date) date_activity,
                    a2.details detail,
                    a2.decl number_decl,
                    o.`created_at` date_created, o.`id` number_order,u_own.`username` seller,
					u_own.`id` seller_id,
                    COALESCE(i.name, o.item) goods,
                    o.item_params options_goods,
                    o.`newpost_id` declaration_number_orders_table,
                    o.`newpost_answer` status_np,
					o.fio fio,
					sStep1.name step1,
					sStep2.name step2,
					sStep3.name step3
                    from orders o
                    left join (select
                                oa1.`order_id`,
                                max(oa1.date) print_date,
                                oa1.`user_id` printed_user_id
                                from orders_audit oa1
                                where oa1.activity IN ('Создана декларация Новой Почты (ЛК)', 'Создана декларация Новой Почты', 'Передан в списке заказов в Новую Почту')
                                group by oa1.order_id)				as a1 on (a1.order_id = o.id)
                                left join users as u_pr on (u_pr.`id` = a1.printed_user_id)
                                inner join (select
                                           oa2.`order_id`,
                                           max(oa2.date) send_date,
                                           oa2.details,
                                           SUBSTRING(oa2.details, LOCATE('Накладная', oa2.`details`) + 9 + 1, LOCATE('Накладная', oa2.`details`) + (14)) decl
                                           from orders_audit  oa2
                                           where oa2.activity = 'Отслеживание доставки: Уведомили об отправке(SMS,email)'
                                           group by oa2.order_id) as a2 on (a2.order_id = o.id)
                                left join users as u_own on (u_own.`id` = o.`owner_id`)
                                left join item as i on (i.uuid = o.item_id)
				left join statuses as sStep1 on (sStep1.id = o.status_step1)
				left join statuses as sStep2 on (sStep2.id = o.status_step2) 
				left join statuses as sStep3 on (sStep3.id = o.status_step3)  
				WHERE
					(:order_date IS NULL OR :order_date = '' OR DATE(a1.print_date) >= :order_date) AND
					(:order_date_end IS NULL OR :order_date_end = '' OR DATE(a1.print_date) <= :order_date_end) AND
					(:item_id IS NULL OR :item_id = '0' OR o.item
					 IN (SELECT name FROM item WHERE uuid = :item_id)) AND
					(:status_id IS NULL OR :status_id = '0' OR o.status_step1 = :status_id 
					OR o.status_step2 = :status_id OR o.status_step3 = :status_id) AND
					(:seller_id = '0' OR u_own.id = :seller_id)
								group by o.id				order by a1.print_date asc
			";
  
 

		$query_params = array(
				':item_id' => $_GET['item_id'],
				':seller_id' => $_GET['seller_id'],
				':status_id' => $_GET['status_id'],
				':order_date' => $_GET['order_date'],
				':order_date_end' => $_GET['order_date_end'] ? $_GET['order_date_end'] : $_GET['order_date']
			);
 
	try{
		$stmt = $db->prepare($query);
		$result = $stmt->execute($query_params);
	}
	catch(PDOException $ex){ die("Невозможно выполнить запрос3: " . $ex->getMessage()); }
	
	$list_sent_goods = $stmt->fetchAll();

?>

<!doctype html>
<html lang="ru">
<?php include 'header.php' ?>
<link href="assets/main.css" rel="stylesheet" media="screen">
<body>
	<?php include 'top_menu.php' ?>
	<div style="display: none;">
	</div>
	<div class="container">
		<a href="reports.php">&larr; Вернуться в меню отчетов</a>
	<div class="conthead">
		<h2>Cписок товаров отправленных за период <?php if ($_GET['order_date']) {?>с <?php echo $dstart->format('d-m-y'); ?> по <?php echo $dend->format('d-m-y'); }?> </h2>
		
	</div>
	<?php if(count($list_sent_goods)>0):	
			$current_seller = '';
			$current_item = '';
			$idx = 1;
			foreach ($list_sent_goods as $list_sent_good) {
				$np_answer = json_decode($list_sent_good['status_np'], true);
				
				if ($current_seller != $list_sent_good['seller'])  {
					$idx = 1;
					if ($current_seller != '') {
						echo '</table>';
					}
			?>
			<h3><?php echo $list_sent_good['seller']; $current_seller=$list_sent_good['seller']; ?></h3>
				<table border="1">
						<thead>
							<tr>
								<th style="width: 20px;">№ п/п</th>
								<th style="width: 50px;">Дата печати,<br/>печатал</th>
								<th style="width: 50px;">Дата движения</th>
								<th style="width: 50px;">Дата и № заказа</th>
								<th style="width: 30px;">Предприниматель</th>
								<th style="width: 30px;">Покупатель</th>
								<th style="width: 80px;">Товар</th>
								<th style="width: 250px;">Статус шагов</th>
								<th style="width: 200px;">Номер декларации,<br/>статус НП</th>
							</tr>
						</thead>
			<?php } ?>	
							<td><?php echo $idx; $idx++; ?></td>
							<td><?php echo $list_sent_good['print_date']?><br/>
							<?php echo $list_sent_good['printed_user']?></td>
							<td><?php echo $list_sent_good['date_activity']?></td>
							<td><?php echo $list_sent_good['date_created']; ?><br/>
								№<?php echo $list_sent_good['number_order'];?><br/>
								<a href="order_history.php?id=<?php echo $list_sent_good['number_order']?>" data-remote=true data-toggle="modal" data-target="#myModalHistory<?php echo $list_sent_good['number_order'] ?>" class="">история</a>
									<div class="modal fade" id="myModalHistory<?php echo $list_sent_good['number_order'] ?>">
										<div class="modal-dialog">
											<div class="modal-content">
											<!-- loaded by ajax -->
											</div>
										</div>
									</div>
							</td>
							<td><?php echo $list_sent_good['seller']?></td>
							<td><?php echo $list_sent_good['fio']?></td>
							<td><?php echo $list_sent_good['goods']?><br/>
								 <?php echo $list_sent_good['options_goods']?>
							</td>
							<td><?php echo 'Шаг 1: '.$list_sent_good['step1']?><br/>
								<?php echo 'Шаг 2: '.$list_sent_good['step2']?><br/>
								<?php echo 'Шаг 3: '.$list_sent_good['step3']?>
							</td>
						
								<?php $string = $list_sent_good['detail'];
									$pos = strripos($string,'Array');
									if ($pos === false) {
										//preg_match("/\d{14}/", $string,$mathces);

										$num_decl = trim($list_sent_good['number_decl']);
 									}
									elseif($pos == '0')  {?><?php 
										$num_decl = substr($string,strripos($string,'[newpost_id] =>')+16, 14);
									}?>
						  <td> <?php
					  			if($num_decl === $list_sent_good['declaration_number_orders_table'] || $num_decl== '' )
                                {
                                    echo '<a target="_new" href="http://novaposhta.ua/frontend/tracking/ru?en='.$list_sent_good['declaration_number_orders_table'].'">'.$list_sent_good['declaration_number_orders_table'].'</a>';
					     			//echo $list_sent_good['declaration_number_orders_table'];
                                } else {
                                    echo '№ истории &nbsp;– &nbsp;'.$num_decl.'<br/> № заказа &nbsp; &nbsp;&nbsp;– &nbsp;'.$list_sent_good['declaration_number_orders_table'].'<br/>';
						  			echo '<font color="red">Ошибка!</font>';
					 			}
								?>
							<br/>
						
							<?php echo ($np_answer['msg'] == '' ? ($list_sent_good['declaration_number_orders_table'] ? '<i>обрабатывается</i>' : '') : $np_answer['msg']) ?>						
							</td>
						
						</tr>			
			<?php }?>	
					</table>
	<?php else:
		echo '<div style="text-alignt:center;">Нет отправленных товаров за данный период!</div>';
	endif?>
</div>
<script type='text/javascript'>
	$('a[data-remote=true]').on('click', function() {
		$($(this).attr('data-target')+' .modal-content').load(this.href, function(result){});
	});
</script>
</body>
</html>