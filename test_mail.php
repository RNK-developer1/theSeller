<?php
    require("config.php");     

	$mail_headers = "From: Интернет-магазин <no-reply@goodthing.in.ua>\r\nMIME-Version: 1.0\r\nContent-Type: text/html; charset=utf-8\r\n";

	mail($_SESSION['user']['email'], 'Заказ '.$_GET['user_id'], 'Товар: '.$_GET['name'].' Тел.'.$_GET['phone'].' Имя:'.$_GET['name'], $mail_headers);
?>