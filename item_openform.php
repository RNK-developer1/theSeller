<?php 
    require("config.php");     
	
	$query = " 
            SELECT 
                *
            FROM item
            WHERE 
                uuid = :uuid
        "; 
	$query_params = array( 
		':uuid' => $_GET['id']
	); 
	 
	try{ 
		$stmt = $db->prepare($query); 
		$result = $stmt->execute($query_params); 
	} 
	catch(PDOException $ex){ die("Невозможно выполнить запрос: " . $ex->getMessage()); } 
	
	$item = $stmt->fetch(); 
    if(!$item){
		header("Location: items_list.php"); 
		die("Перенаправление: items_list.php"); 
	}
?>

<html>
<head>
<meta http-equiv="content-type" content="text/html; charset=utf-8">
<title>Форма заказа</title>
<style type="text/css">
	body {
		font-family: Helvetica, Arial, sans-serif;
		font-size: 16px;
	}
	
	input:not([type=submit]) {
		width: 100%;
		display: inline-block;
		margin-top: 7px;
		height: 45px;
		border: 1px solid #cccccc;
		background-color: white;
		padding: 4px 6px;
		line-height: 30px;
		border-radius: 4px;
		vertical-align: middle;
		color: #555555;
		font-size: 16px;
	}
	input:focus {	
		border-color: rgba(82, 200, 82, 0.8) !important;
		box-shadow: inset 0 1px 1px rgba(0,0,0,.075), 0 0 8px rgba(82,200,82,.6)
	}
	
	.btn_order {
		margin-top: 10px;
		margin-left: 75px;
		border: 0 !important;
		width: 252px;
		height: 66px;
		background-image: url("assets/button_order2.png");
		background-position: 0 0;
		background-repeat: no-repeat;
		cursor: pointer;
	}
	
	.btn_order:hover {
		background-position: 0 -66px;
	}
</style>
<script src="assets/jquery-1.10.1.js"></script>
<script type="text/javascript">(function(e){function t(){var e=document.createElement("input"),t="onpaste";return e.setAttribute(t,""),"function"==typeof e[t]?"paste":"input"}var n,a=t()+".mask",r=navigator.userAgent,i=/iphone/i.test(r),o=/android/i.test(r);e.mask={definitions:{9:"[0-9]",a:"[A-Za-z]","*":"[A-Za-z0-9]"},dataName:"rawMaskFn",placeholder:"_"},e.fn.extend({caret:function(e,t){var n;if(0!==this.length&&!this.is(":hidden"))return"number"==typeof e?(t="number"==typeof t?t:e,this.each(function(){this.setSelectionRange?this.setSelectionRange(e,t):this.createTextRange&&(n=this.createTextRange(),n.collapse(!0),n.moveEnd("character",t),n.moveStart("character",e),n.select())})):(this[0].setSelectionRange?(e=this[0].selectionStart,t=this[0].selectionEnd):document.selection&&document.selection.createRange&&(n=document.selection.createRange(),e=0-n.duplicate().moveStart("character",-1e5),t=e+n.text.length),{begin:e,end:t})},unmask:function(){return this.trigger("unmask")},mask:function(t,r){var c,l,s,u,f,h;return!t&&this.length>0?(c=e(this[0]),c.data(e.mask.dataName)()):(r=e.extend({placeholder:e.mask.placeholder,completed:null},r),l=e.mask.definitions,s=[],u=h=t.length,f=null,e.each(t.split(""),function(e,t){"?"==t?(h--,u=e):l[t]?(s.push(RegExp(l[t])),null===f&&(f=s.length-1)):s.push(null)}),this.trigger("unmask").each(function(){function c(e){for(;h>++e&&!s[e];);return e}function d(e){for(;--e>=0&&!s[e];);return e}function m(e,t){var n,a;if(!(0>e)){for(n=e,a=c(t);h>n;n++)if(s[n]){if(!(h>a&&s[n].test(R[a])))break;R[n]=R[a],R[a]=r.placeholder,a=c(a)}b(),x.caret(Math.max(f,e))}}function p(e){var t,n,a,i;for(t=e,n=r.placeholder;h>t;t++)if(s[t]){if(a=c(t),i=R[t],R[t]=n,!(h>a&&s[a].test(i)))break;n=i}}function g(e){var t,n,a,r=e.which;8===r||46===r||i&&127===r?(t=x.caret(),n=t.begin,a=t.end,0===a-n&&(n=46!==r?d(n):a=c(n-1),a=46===r?c(a):a),k(n,a),m(n,a-1),e.preventDefault()):27==r&&(x.val(S),x.caret(0,y()),e.preventDefault())}function v(t){var n,a,i,l=t.which,u=x.caret();t.ctrlKey||t.altKey||t.metaKey||32>l||l&&(0!==u.end-u.begin&&(k(u.begin,u.end),m(u.begin,u.end-1)),n=c(u.begin-1),h>n&&(a=String.fromCharCode(l),s[n].test(a)&&(p(n),R[n]=a,b(),i=c(n),o?setTimeout(e.proxy(e.fn.caret,x,i),0):x.caret(i),r.completed&&i>=h&&r.completed.call(x))),t.preventDefault())}function k(e,t){var n;for(n=e;t>n&&h>n;n++)s[n]&&(R[n]=r.placeholder)}function b(){x.val(R.join(""))}function y(e){var t,n,a=x.val(),i=-1;for(t=0,pos=0;h>t;t++)if(s[t]){for(R[t]=r.placeholder;pos++<a.length;)if(n=a.charAt(pos-1),s[t].test(n)){R[t]=n,i=t;break}if(pos>a.length)break}else R[t]===a.charAt(pos)&&t!==u&&(pos++,i=t);return e?b():u>i+1?(x.val(""),k(0,h)):(b(),x.val(x.val().substring(0,i+1))),u?t:f}var x=e(this),R=e.map(t.split(""),function(e){return"?"!=e?l[e]?r.placeholder:e:void 0}),S=x.val();x.data(e.mask.dataName,function(){return e.map(R,function(e,t){return s[t]&&e!=r.placeholder?e:null}).join("")}),x.attr("readonly")||x.one("unmask",function(){x.unbind(".mask").removeData(e.mask.dataName)}).bind("focus.mask",function(){clearTimeout(n);var e;S=x.val(),e=y(),n=setTimeout(function(){b(),e==t.length?x.caret(0,e):x.caret(e)},10)}).bind("blur.mask",function(){y(),x.val()!=S&&x.change()}).bind("keydown.mask",g).bind("keypress.mask",v).bind(a,function(){setTimeout(function(){var e=y(!0);x.caret(e),r.completed&&e==x.val().length&&r.completed.call(x)},0)}),y()}))}})})(jQuery);</script>
</head>
<body style="background:none">

<?php if ($item['yandexmetric']) { ?>
<!-- Yandex.Metrika counter -->
<script type="text/javascript">
(function (d, w, c) {
    (w[c] = w[c] || []).push(function() {
        try {
            w.yaCounter<?php echo $item['yandexmetric'] ?> = new Ya.Metrika({id:<?php echo $item['yandexmetric'] ?>,
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
</script>
<!-- /Yandex.Metrika counter -->
<?php } ?>
<?php $uid = uniqid();?>
<FORM action="http://goodthing.in.ua/theseller/do_fastorder_t.php" method=post name="order_form_13" target="_parent" onsubmit="return checkForm_13(this)">
	<input type='hidden' id="id" name='id' value='<?php echo $item['uuid']?>'>
	<input type="hidden" id="ref_input" name="referrer" value="">
	<input type="hidden" id="req_input" name="request" value="">
	<input type="hidden" id="req_agent" name="useragent" value="">
	<input type="hidden" id="ip_input" name="ip_src" value="">
	<input type="hidden" id="user_id<?php echo $uid;?>" name="user_id" value="1">
	<div style="width:402px; height: 270px; border: 1px solid #ccc; border-radius: 5px; padding: 10px 0;">
		<div style="padding: 10px 20px">
			<div>Имя:</div>
			<INPUT name="name" class=input value="" maxlength=50>
		</div>
		<div style="padding: 10px 20px">
			<div>Телефон мобильный: <span style="color:red">*</span></div>
			<input style="font-size: 18px;" name="phone" class="phone" id="phone_mob_<?php echo $uid;?>" maxlength=14 placeholder='(___)___-__-__'>
		</div>
		<div style="width: 100%;">
			<INPUT class="btn_order" type="submit" value="">
		</div>		
	</div>	
	<?php if ($item['conf_block']) {?>
		<div style="font-size: 10px; width:269px;"><?php echo str_replace("{item}",$item['name'],$item['conf_block']); ?></div>
	<?php }?>  	  
</FORM>

<script type='text/javascript'>
function checkForm_13(f)
{
 if(!f.phone.value.match(/^\(0\d\d\)\d\d\d-\d\d-\d\d$/i))
 {
	 alert ("Введите корректно Ваш номер телефона!");
	 f.phone.focus();
	 return false;
 }

<?php if ($item['yandexmetric']) { ?>yaCounter<?php echo $item['yandexmetric'] ?>.reachGoal('<?php echo $item['yandexgoal'] ?>');<?php } ?>
return true; 
}
// - JavaScript - -->

$('input[name=name]').mask("Z");
$('input[name=name]').unmask();
$('#phone_mob_<?php echo $uid;?>').mask("(999)999-99-99");

f = $('form');
i=new Image();
i.onerror=function() {
	f.attr('action',"http://goodthing.hostei.com/do_order.php");
	console.log('KO');
}
i.src = f.attr('action')+'.gif';

//$('#ref_input').val(top.document.referrer);
//$('#req_input').val(top.document.baseURI);
$('#req_agent').val(navigator.vendor + ' ' + navigator.userAgent);

$.getJSON( "http://smart-ip.net/geoip-json?callback=?",
	function(data){
		$('#ip_input').val(data.host+' '+data.countryCode);
	}
);
	
$('#user_id<?php echo $uid;?>').val(Math.floor(Math.random() * 10000000000000001));

var form_data = '';

function preorder() {
	if ($('input[name="phone"]').val().match(/\(0\d\d\)\d\d\d-\d\d-\d\d/g) && form_data != $('form').first().serialize()) {
		form_data = $('form').first().serialize();
		$.getJSON('http://goodthing.in.ua/theseller/do_preorder_t.php?callback=?',form_data,function(res){console.log(res);});
	}
}

var interval = 3500;
function visit() {
	form_data = $('form').first().serialize();
	$.getJSON('http://goodthing.in.ua/theseller/do_preorder.php?callback=?',form_data,function(res){console.log(res);});
	interval *= 3;
	setTimeout(function() {visit();}, interval);
}

$('form input').change(function() { preorder(); });
$('form textarea').change(function() { preorder(); });
$('form select').change(function() { preorder(); });

setInterval(function() {preorder();}, 10000);
setTimeout(function() {visit();}, 3000);

console.log(document.location);
</script>    
    
</body>
</html>

