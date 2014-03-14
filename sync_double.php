<?php
	$curl = curl_init();
	curl_setopt($curl, CURLOPT_URL, "http://goodthing.hostei.com/sync.php"); 
	curl_setopt($curl, CURLOPT_TIMEOUT, 60); 
	curl_exec($curl);
?>