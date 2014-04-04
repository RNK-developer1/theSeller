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
		
	$query = " 
            INSERT IGNORE INTO orders(id, created_at, referrer, request, ip_src, item_id, item_params, item_price, item_count, city_area, address, fio, phone, email, owner_id, status_step1)
					   VALUES (:user_id, NOW(), :referrer, :request, :ip_src, :item_uuid, :item_params, :item_price, :count, :city_area, :address, :fio, :phone, :email, :owner_id, 100) ON DUPLICATE KEY UPDATE updated_at = NOW(), referrer = :referrer, request = :request, ip_src = :ip_src, item_id = :item_uuid, item_params = :item_params, item_count = :count, city_area = :city_area, address = :address, fio = :fio, phone = :phone, email= :email, item_price = :item_price;								   
        "; 
	$query_params = array( 
		':user_id' => $_POST['user_id'],
		':owner_id' => $sel_item['owner_id'],
		':referrer' => $_POST['referrer'],
		':request' => $_POST['request'],
		':ip_src' => $_POST['ip_src'],
		':item_uuid' => $_POST['id'],
		':item_price' => number_format(floatval($sel_item['price'])*intval($_POST['count'] ? $_POST['count'] : 1),2,'.',''),
		':item_params' => ($_POST['param1'] ? $sel_item['param1_name'].':'.$_POST['param1'] : '').($_POST['param2'] ? ' '.$sel_item['param2_name'].':'.$_POST['param2'] : ''),
		':count' => $_POST['count'] || 1,
		':city_area' => $_POST['city'].' '.$_POST['area'],
		':address' => $_POST['address'], 
		':fio' => $_POST['fam'].' '.$_POST['name'], 
		':phone' => $_POST['phone'], 
		':email' => $_POST['email']
	); 
	 
	try{ 
		$stmt = $db->prepare($query); 
		$result = $stmt->execute($query_params); 
	} 
	catch(PDOException $ex){ die("Невозможно выполнить запрос1: " . $ex->getMessage()); } 	
	
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
		':comment' => ($_POST['comment'] ? $_POST['comment']."<br/>" : '').$comment,
		':user_id' => $sel_item['owner_id'],
		':order_id' => $_POST['user_id']
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
	
	$mail_headers = "From: Интернет-магазин <no-reply@goodthing.in.ua>\r\nMIME-Version: 1.0\r\n";
	mail($owner['email'], 'Заказ '.$_POST['user_id'], 'Товар: '.$sel_item['name'].' Тел.'.$_POST['phone'].' Имя:'.$_POST['name'], $mail_headers);
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Ваш заказ принят!</title>
<link rel="stylesheet" type="text/css" href="assets/style.css">
<script src="http://code.jquery.com/jquery-1.10.1.min.js"></script>
<script type="text/javascript">(function(e){function t(){var e=document.createElement("input"),t="onpaste";return e.setAttribute(t,""),"function"==typeof e[t]?"paste":"input"}var n,a=t()+".mask",r=navigator.userAgent,i=/iphone/i.test(r),o=/android/i.test(r);e.mask={definitions:{9:"[0-9]",a:"[A-Za-z]","*":"[A-Za-z0-9]"},dataName:"rawMaskFn",placeholder:"_"},e.fn.extend({caret:function(e,t){var n;if(0!==this.length&&!this.is(":hidden"))return"number"==typeof e?(t="number"==typeof t?t:e,this.each(function(){this.setSelectionRange?this.setSelectionRange(e,t):this.createTextRange&&(n=this.createTextRange(),n.collapse(!0),n.moveEnd("character",t),n.moveStart("character",e),n.select())})):(this[0].setSelectionRange?(e=this[0].selectionStart,t=this[0].selectionEnd):document.selection&&document.selection.createRange&&(n=document.selection.createRange(),e=0-n.duplicate().moveStart("character",-1e5),t=e+n.text.length),{begin:e,end:t})},unmask:function(){return this.trigger("unmask")},mask:function(t,r){var c,l,s,u,f,h;return!t&&this.length>0?(c=e(this[0]),c.data(e.mask.dataName)()):(r=e.extend({placeholder:e.mask.placeholder,completed:null},r),l=e.mask.definitions,s=[],u=h=t.length,f=null,e.each(t.split(""),function(e,t){"?"==t?(h--,u=e):l[t]?(s.push(RegExp(l[t])),null===f&&(f=s.length-1)):s.push(null)}),this.trigger("unmask").each(function(){function c(e){for(;h>++e&&!s[e];);return e}function d(e){for(;--e>=0&&!s[e];);return e}function m(e,t){var n,a;if(!(0>e)){for(n=e,a=c(t);h>n;n++)if(s[n]){if(!(h>a&&s[n].test(R[a])))break;R[n]=R[a],R[a]=r.placeholder,a=c(a)}b(),x.caret(Math.max(f,e))}}function p(e){var t,n,a,i;for(t=e,n=r.placeholder;h>t;t++)if(s[t]){if(a=c(t),i=R[t],R[t]=n,!(h>a&&s[a].test(i)))break;n=i}}function g(e){var t,n,a,r=e.which;8===r||46===r||i&&127===r?(t=x.caret(),n=t.begin,a=t.end,0===a-n&&(n=46!==r?d(n):a=c(n-1),a=46===r?c(a):a),k(n,a),m(n,a-1),e.preventDefault()):27==r&&(x.val(S),x.caret(0,y()),e.preventDefault())}function v(t){var n,a,i,l=t.which,u=x.caret();t.ctrlKey||t.altKey||t.metaKey||32>l||l&&(0!==u.end-u.begin&&(k(u.begin,u.end),m(u.begin,u.end-1)),n=c(u.begin-1),h>n&&(a=String.fromCharCode(l),s[n].test(a)&&(p(n),R[n]=a,b(),i=c(n),o?setTimeout(e.proxy(e.fn.caret,x,i),0):x.caret(i),r.completed&&i>=h&&r.completed.call(x))),t.preventDefault())}function k(e,t){var n;for(n=e;t>n&&h>n;n++)s[n]&&(R[n]=r.placeholder)}function b(){x.val(R.join(""))}function y(e){var t,n,a=x.val(),i=-1;for(t=0,pos=0;h>t;t++)if(s[t]){for(R[t]=r.placeholder;pos++<a.length;)if(n=a.charAt(pos-1),s[t].test(n)){R[t]=n,i=t;break}if(pos>a.length)break}else R[t]===a.charAt(pos)&&t!==u&&(pos++,i=t);return e?b():u>i+1?(x.val(""),k(0,h)):(b(),x.val(x.val().substring(0,i+1))),u?t:f}var x=e(this),R=e.map(t.split(""),function(e){return"?"!=e?l[e]?r.placeholder:e:void 0}),S=x.val();x.data(e.mask.dataName,function(){return e.map(R,function(e,t){return s[t]&&e!=r.placeholder?e:null}).join("")}),x.attr("readonly")||x.one("unmask",function(){x.unbind(".mask").removeData(e.mask.dataName)}).bind("focus.mask",function(){clearTimeout(n);var e;S=x.val(),e=y(),n=setTimeout(function(){b(),e==t.length?x.caret(0,e):x.caret(e)},10)}).bind("blur.mask",function(){y(),x.val()!=S&&x.change()}).bind("keydown.mask",g).bind("keypress.mask",v).bind(a,function(){setTimeout(function(){var e=y(!0);x.caret(e),r.completed&&e==x.val().length&&r.completed.call(x)},0)}),y()}))}})})(jQuery);</script>
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
		<?php if ($sel_item['finish_screen_fast']) {
			echo str_replace("{name}",$_POST['name'],str_replace("{item}",$sel_item['name'],$sel_item['finish_screen_fast']));
		} else { ?>

			<h2>Благодарим Вас за заказ!</h2>

			<div align="center">
			  <div align="center">

				<p><strong>Ваш заказ принят!</strong></p>

				<p><strong>Форма оплаты: наложенный платёж</strong></p>

				<p align="left"><br />Ваш заказ успешно принят и поставлен в обработку!</p>
				<p align="left">В ближайшее время c Вами свяжутся для уточнения деталей заказа.</p>
				<p align="left">Вы можете ускорить обработку Вашего заказа, если заполните эту форму:</p>
				</div>
			  <p>&nbsp;</p>
			</div>		  
		  <?php } ?>		
		  
		  <FORM action="http://goodthing.in.ua/theseller/do_order_details.php" method=post name="order_form_13" target="_parent" onsubmit="return checkForm_13(this)">
				<input type='hidden' id="id" name='id' value='<?php echo $sel_item['uuid']?>'>
				<input type='hidden' id="order_id" name='order_id' value='<?php echo $_POST['user_id']?>'>				
				<input type="hidden" id="user_id" name="user_id" value="<?php echo $_POST['user_id']?>">
				<TABLE style="FONT-SIZE: 10pt; FONT-FAMILY: Arial" cellSpacing=2 cellPadding=2 width="98%" align=center border=0>
					<TBODY>
					<TR>
						<TD align=right width="50%"><b>Фамилия</b></TD>
						<TD><INPUT name="fam" class=input value="" maxlength=20></TD></TR>
					<TR>
						<TD align=right width="50%"><b>Имя</b></TD>
						<TD><INPUT name="name" class=input value="<?php echo $_POST['name'];?>" maxlength=50></TD>
					</TR>
					<TR>
						<TD align=right valign="top" style="padding-top:5px;"><b>Телефон мобильный*</b> +38</TD>
						<TD>
							<table border="0" cellspacing="0" cellpadding="0">
							<tr>
								<td><input name="phone" readonly=readonly class="phone" id="phone_mob" maxlength=14 value="<?php echo $_POST['phone'];?>"></td>
							</tr>
							</table>
						</TD>
					</TR>
					<TR>
					   <TD height="10" colSpan=2></TD>
					</TR>
					<TR>
						<TD align=right width="50%"><b>Область</b></TD>
						<TD><INPUT name="area" class=input value="" maxlength="100"></TD></TR>
					<TR>
						<TD align=right width="50%"><b>Город</b></TD>
						<TD><INPUT name="city" class=input value="" maxlength="50"></TD></TR>
					<TR>
						<TD align=right width="50%"><b>Отделение Новой Почты или домашний адрес (улица, дом)</b></TD>
						<TD><TEXTAREA class=input name="address" rows="2" cols="20"></TEXTAREA></TD></TR>       
					<TR>
						<TD height="10" colSpan=2></TD>
					</TR>
					<TR>
						<TD align=right width="50%"><b>E-mail</b></TD>
						<TD><INPUT name="email" class=input value="" maxlength=50></TD>
					</TR>		  	
					<TR>
					  <TD height="10" colSpan=2>          </TD>
					</TR>
					<?php if ($sel_item['param1']) { ?>
					<TR>
						<TD align=right width="50%"><b><?php echo $sel_item['param1_name'] ?></b></TD>
						<TD>
							<select name='param1'>
								<?php foreach (split(';',$sel_item['param1']) as $idx => $param_val) { ?>
									<option value='<?php echo $param_val."'"; if ($idx == 0) {echo " selected='selected'";}?>><?php echo $param_val ?></option>
								<?php } ?>	
							</select>
						</TD>
					</TR>
					<?php } ?>
					<?php if ($sel_item['param2']) { ?>
					<TR>
						<TD align=right width="50%"><b><?php echo $item['param2_name'] ?></b></TD>
						<TD>
							<select name='param2'>
								<?php foreach (split(';',$sel_item['param2']) as $idx => $param_val) { ?>
									<option value='<?php echo $param_val."'"; if ($idx == 0) {echo " selected='selected'";}?>><?php echo $param_val ?></option>
								<?php } ?>	
							</select>
						</TD>
					</TR>
					<?php } ?>
					<TR>
					  <TD align=right width="50%"><b>Количество</b></TD>		  
					  <TD><INPUT type="number" min="1" name="count" class=input2 value="1" maxlength=6>
					</TR>
					<TR> 
						<TD>&nbsp;</TD>
						<TD><INPUT class="button" type="submit" value="Уточнить заказ"></TD>
					</TR>					
				  
			</TBODY></TABLE>
				  <CENTER>
			</CENTER></FORM>

			<script type='text/javascript'>
			function checkForm_13(f)
			{
			 if(!f.count.value.match(/^[1-9]{1}[0-9]*$/i))
			 {
				 alert ("Укажите корректное количество заказываемого товара!");
				 f.count.focus();
				 return false;
			 }

			 if(f.email.value!='' && !f.email.value.match(/^[\w]{1}[\w\.\-_]*@[\w]{1}[\w\-_\.]*\.[\w]{2,4}$/i))
			 {
				 alert ("Введите корректно Ваш E-Mail адрес!");
				 f.email.focus();
				 return false;
			 }			  			 

			<?php if ($item['yandexmetric']) { ?>yaCounter<?php echo $item['yandexmetric'] ?>.reachGoal('<?php echo $item['yandexgoal'] ?>');<?php } ?>
			return true; 
			}
			// - JavaScript - -->

			$('#phone_mob').mask("(999)999-99-99");

</script>   

	</div>
	<div class="posts_footer"><span class="ldc"></span><span class="rdc"></span></div>
	<div id="footer">
		№ заказа: <?php echo $_POST['user_id'];?>
	</div>
</div>
</body>
</html>

