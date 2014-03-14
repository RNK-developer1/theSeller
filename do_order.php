<?php
    require("config.php");     

	if (!$_POST['phone'] OR !$_POST['id']) {
		die("Форма заказа заполнена не полностью!");
	}
	
	if ($_POST['user_id'] == '1') {
		$_POST['user_id'] = rand(100000, getrandmax());
	}
	
	$query = "SELECT * FROM item WHERE uuid = :uuid";
	$query_params = array (
		':uuid' => $_POST['id']
	);
	try{ 
		$stmt = $db->prepare($query); 
		$result = $stmt->execute($query_params); 
	} 
	catch(PDOException $ex){ die("Невозможно выполнить запрос: " . $ex->getMessage()); } 
	
	$sel_item = $stmt->fetch();
	
	$query = "SELECT * FROM users WHERE id = :id";
	$query_params = array (
		':id' => $sel_item['owner_id']
	);
	try{ 
		$stmt = $db->prepare($query); 
		$result = $stmt->execute($query_params); 
	} 
	catch(PDOException $ex){ die("Невозможно выполнить запрос: " . $ex->getMessage()); } 
	
	$owner = $stmt->fetch();

	if (!$sel_item) {
		die('Извините, этот товар уже не продаётся');
	}
		
	$comment = '';
	
	$query = "SELECT orders.*, COALESCE(item.name, orders.item) as item_ordered, users.username FROM orders LEFT OUTER JOIN item ON item.uuid = orders.item_id, users WHERE orders.id <> :order_id AND orders.owner_id = users.id AND orders.phone = :phone ORDER BY created_at ASC";
	$query_params = array(
		'order_id' => $_POST['user_id'],
		':phone' => $_POST['phone']
	);
	try{ 
		$stmt = $db->prepare($query); 
		$result = $stmt->execute($query_params); 
	} 
	catch(PDOException $ex){ die("Невозможно выполнить запрос: " . $ex->getMessage()); } 		
	$old_orders = $stmt->fetchAll();
	
	foreach ($old_orders as $old_order) {
		$crd = new DateTime($old_order['created_at']);
		$crd = $crd->format('d-m-y H:i:s');
		if (in_array($old_order['status_step3'], array(20, 301, 310, 311, 312))) {
			$st = "оплачен";
		} else if (in_array($old_order['status_step3'], array(30, 31, 32, 302, 310, 318, 320, 321)) or in_array($old_order['status_step2'], array(220, 225, 240, 241, 242))) {
			$st = "ВОЗВРАТ";
		} else if (in_array($old_order['status_step1'], array(10, 40)) or in_array($old_order['status_step2'], array(10, 230))) {
			$st = "Отменен";
		} else {
			$st = "обрабатывается";
		}
		$comment .= 'АВТО: '.$crd.' '.$old_order['item_ordered'].' '.$old_order['username'].' '.$st."<br/>";
	}	
	
	$query = " 
            INSERT IGNORE INTO orders(id, created_at, referrer, request, ip_src, item, item_id, item_price, item_params, item_count, city_area, address, fio, phone, email, owner_id, status_step1)
					   VALUES (:id, NOW(), :referrer, :request, :ip_src, :item, :item_id, :item_price, :item_params, :item_count, :city_area, :address, :fio, :phone, :email, :owner_id, 100) ON DUPLICATE KEY UPDATE updated_at = NOW(), item_params = :item_params, item_count = :item_count, city_area = :city_area, address = :address, fio = :fio, phone = :phone, email= :email, item_price = :item_price;								   
        "; 
	$query_params = array( 
		':id' => $_POST['user_id'],
		':item' => $sel_item['name'],
		':referrer' => $_POST['referrer'],
		':request' => $_POST['request'],
		':ip_src' => $_POST['ip_src'],
		':item_id' => $_POST['id'],
		':item_price' => number_format(floatval($sel_item['price'])*intval($_POST['count'] ? $_POST['count'] : 1),2,'.',''),
		':item_params' => ($_POST['param1'] ? $sel_item['param1_name'].':'.$_POST['param1'] : '').($_POST['param2'] ? ' '.$sel_item['param2_name'].':'.$_POST['param2'] : ''),		
		':item_count' => $_POST['count'] ? $_POST['count'] : 1,
		':city_area' => $_POST['city'].' '.$_POST['area'],
		':address' => $_POST['address'], 
		':fio' => $_POST['fam'].' '.$_POST['name'], 
		':phone' => $_POST['phone'], 
		':email' => $_POST['email'], 
		':owner_id' => $sel_item['owner_id']
	); 
	 
	try{ 
		$stmt = $db->prepare($query); 
		$result = $stmt->execute($query_params); 
	} 
	catch(PDOException $ex){ die("Невозможно выполнить запрос: " . $ex->getMessage()); } 	
	
	if ($_POST['user_id']) {
		$query = "DELETE FROM preorder WHERE user_id = :user_id";
		$query_params = array(':user_id' => $_POST['user_id']);
	
		try{ 
			$stmt = $db->prepare($query); 
			$result = $stmt->execute($query_params); 
		} 
		catch(PDOException $ex){ die("Невозможно выполнить запрос: " . $ex->getMessage()); } 	
	}
	
	$query = "DELETE FROM orders_audit WHERE comment='ПРЕДЗАКАЗ' AND order_id = :order_id";
	$query_params = array( 
		':order_id' => $_POST['user_id']
	); 
	try{ 
		$stmt = $db->prepare($query); 
		$result = $stmt->execute($query_params); 
	} 
	catch(PDOException $ex){ die("Невозможно выполнить запрос: " . $ex->getMessage()); } 	
	
	$query = "SELECT * FROM orders WHERE phone = :phone AND item = :item ORDER BY id DESC";
	$query_params = array(
		':phone' => $_POST['phone'],
		':item' => $sel_item['name']
	);
	try{ 
		$stmt = $db->prepare($query); 
		$result = $stmt->execute($query_params); 
	} 
	catch(PDOException $ex){ die("Невозможно выполнить запрос: " . $ex->getMessage()); } 		
	$new_order = $stmt->fetch(); 

	$query = "INSERT INTO orders_audit(date, order_id, comment, user_id, activity, details) VALUES
				(	NOW(),
					:order_id,
					:comment,
					:user_id,
					:activity,
					:details)";
	
	$query_params = array( 
		':details' => print_r($new_order,true),
		':activity' => 'Оформлен новый заказ',
		':user_id' => $sel_item['owner_id'],
		':order_id' => $_POST['user_id'],
		':comment' => ($_POST['comment'] ? $_POST['comment']."<br/>" : '').$comment
	); 
	
	try{ 
		$stmt = $db->prepare($query); 
		$result = $stmt->execute($query_params); 
	} 
	catch(PDOException $ex){ die("Невозможно выполнить запрос: " . $ex->getMessage()); } 	
	
	$mail_subject = str_replace("{surname}",$_POST['fam'],str_replace("{name}",$_POST['fam'].' '.$_POST['name'],str_replace("{item}",$sel_item['name'],$sel_item['mail_subject'])));
	$mail_template = str_replace("{surname}",$_POST['fam'],str_replace("{name}",$_POST['fam'].' '.$_POST['name'],str_replace("{item}",$sel_item['name'],$sel_item['mail_template'])));
	
	if ($sel_item['mail_subject'] AND $sel_item['mail_template']) {
		$mail_headers = "From: Интернет-магазин <no-reply@goodthing.in.ua>\r\nReply-To: ".$owner['email']."\r\nMIME-Version: 1.0\r\nContent-Type: text/html; charset=utf-8\r\n";
		mail($_POST['email'], $mail_subject, $mail_template, $mail_headers);
	}
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Ваш заказ принят!</title>
<link rel="stylesheet" type="text/css" href="assets/style.css">
</head>
<body>
<?php if ($sel_item['yandexmetric'] and $sel_item['yandexgoal2']) { ?>
<!-- Yandex.Metrika counter -->
<script type="text/javascript">
(function (d, w, c) {
    (w[c] = w[c] || []).push(function() {
        try {
            w.yaCounter<?php echo $sel_item['yandexmetric'] ?> = new Ya.Metrika({id:<?php echo $sel_item['yandexmetric'] ?>,
                    clickmap:true,
                    trackLinks:true,
                    accurateTrackBounce:true});
        } catch(e) { }
    });

    var n = d.getElementsByTagName("script")[0],
        s = d.createElement("script"),
        f = function () { n.parentNode.insertBefore(s, n); };
    s.type = "text/javascript";
    s.async = true;
    s.src = (d.location.protocol == "https:" ? "https:" : "http:") + "//mc.yandex.ru/metrika/watch.js";

    if (w.opera == "[object Opera]") {
        d.addEventListener("DOMContentLoaded", f, false);
    } else { f(); }
})(document, window, "yandex_metrika_callbacks");

yaCounter<?php echo $sel_item['yandexmetric'] ?>.reachGoal('<?php echo $sel_item['yandexgoal2'] ?>');
</script>
<!-- /Yandex.Metrika counter -->
<?php } ?>
<div align="center">
	<!--Header-->
	
	<div id="posts">
		<span class="ltc"></span><span class="rtc"></span>
		<?php if ($sel_item['finish_screen']) {
			echo str_replace("{surname}",$_POST['fam'],str_replace("{name}",$_POST['name'],str_replace("{item}",$sel_item['name'],$sel_item['finish_screen'])));
		} else { ?>

			<h2>Благодарю Вас за заказ!</h2>

			<div align="center">
			  <div align="center">

				<p><strong>Ваш заказ принят!</strong></p>

				<p><strong>Форма оплаты: наложенный платёж</strong></p>

				<p align="left"><br />Ваш заказ успешно принят и поставлен в обработку!</p>
				<p align="left">В ближайшее время Ваш заказ будет подготовлен и отправлен на указанный Вами адрес наложенным платежом.</p>
				</div>
			  <p>&nbsp;</p>
			</div>		  
		  <?php } ?>		
	</div>
	<div class="posts_footer"><span class="ldc"></span><span class="rdc"></span></div>
	<div id="footer">
	</div>
</div>
</body>
</html>

