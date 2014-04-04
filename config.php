<?php 
    $username = "goodthing_yaw"; 
    $password = "q1w2e3y"; 
    $host = "db13.freehost.com.ua"; 
    $dbname = "goodthing_yaw"; 
	
	$smsfly_user = '380971067101';
	$smsfly_password = '1067101';
    
    $options = array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8'); 
    try { $db = new PDO("mysql:host={$host};dbname={$dbname};charset=utf8", $username, $password, $options); } 
    catch(PDOException $ex){ die("Невозможно подключиться к базе данных: " . $ex->getMessage());} 
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); 
    $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC); 
    if (!$clean_headers) {
		header('Content-Type: text/html; charset=utf-8'); 
		session_start(); 
	}
?>
