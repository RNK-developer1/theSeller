<?php
	require 'config.php';

	libxml_use_internal_errors(true); 
		
	$body = '';
	$fh   = @fopen('php://input', 'r');
	if ($fh)
	{
	  while (!feof($fh))
	  {
		$s = fread($fh, 1024);
		if (is_string($s))
		{
		  $body .= $s;
		}
	  }
	  fclose($fh);
	}
	try {
		$query = "INSERT INTO debug (msg) VALUES (:msg)";			
				
		$query_params = array( 
			':msg' => $body
		); 
		
		try{ 
			$stmt = $db->prepare($query); 
			$result = $stmt->execute($query_params); 
		} 
		catch(PDOException $ex){ die("Невозможно выполнить запрос: " . $ex->getMessage()); } 		

		$status = new SimpleXMLElement($body);
		
		$query = "INSERT INTO debug (msg) VALUES (:msg)";			
				
		$query_params = array( 
			':msg' => print_r($status->state->attributes(),true)
		); 
		
		try{ 
			$stmt = $db->prepare($query); 
			$result = $stmt->execute($query_params); 
		} 
		catch(PDOException $ex){ die("Невозможно выполнить запрос: " . $ex->getMessage()); } 		

		$query = "UPDATE flysms SET status = :status, date=:date WHERE campaignID = :campaignID AND recipient = :recipient";			
				
		$query_params = array( 
			':campaignID' => $status->state->attributes()->campaignID,
			':recipient' => $status->state->attributes()->recipient,
			':status' => $status->state->attributes()->status,
			':date' => $status->state->attributes()->date
		); 
		
		try{ 
			$stmt = $db->prepare($query); 
			$result = $stmt->execute($query_params); 
		} 
		catch(PDOException $ex){ die("Невозможно выполнить запрос: " . $ex->getMessage()); } 		

		
	} catch (Exception $e) {
		echo $body;
	}

	echo "OK";
?>