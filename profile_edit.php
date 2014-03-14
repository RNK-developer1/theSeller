<?php 
    require("config.php");     
	
	if (empty($_SESSION['user']) || $_SESSION['user']['active'] == 0) {
		header("Location: index.php"); 
		die("Перенаправление: index.php"); 
	}
	
	$query = "UPDATE users SET alphaname = :alphaname, phone = :phone, username = :username, newpost_api = :newpost_api, sender_whs_ref = :sender_whs_ref, fio_ukr = :fio_ukr, pass_s = :pass_s, pass_n = :pass_n, pass_issued = :pass_issued, pass_i_date = :pass_i_date, adr = :adr WHERE id = :id";
	$query_params = array( 
		':id' => $_SESSION['user']['id'],
		':phone' => $_POST['phone'],
		':alphaname' => $_POST['alphaname'],
		':username' => $_POST['username'],
		':newpost_api' => $_POST['newpost_api'],
		':sender_whs_ref' => $_POST['sender_whs_ref'],
		':fio_ukr' => $_POST['fio_ukr'],
		':pass_s' => $_POST['pass_s'],
		':pass_n' => $_POST['pass_n'],
		':pass_issued' => $_POST['pass_issued'],
		':pass_i_date' => $_POST['pass_i_date'],
		':adr' => $_POST['adr']
	);
	try{ 
		$stmt = $db->prepare($query); 
		$result = $stmt->execute($query_params); 
		$_SESSION['user']['alphaname'] = $_POST['alphaname'];
		$_SESSION['user']['phone'] = $_POST['phone'];
		$_SESSION['user']['username'] = $_POST['username'];
		$_SESSION['user']['newpost_api'] = $_POST['newpost_api'];
		$_SESSION['user']['sender_whs_ref'] = $_POST['sender_whs_ref'];
		$_SESSION['user']['fio_ukr'] = $_POST['fio_ukr'];
		$_SESSION['user']['pass_s'] = $_POST['pass_s'];
		$_SESSION['user']['pass_n'] = $_POST['pass_n'];
		$_SESSION['user']['pass_issued'] = $_POST['pass_issued'];
		$_SESSION['user']['pass_i_date'] = $_POST['pass_i_date'];
		$_SESSION['user']['adr'] = $_POST['adr'];
	} 
	catch(PDOException $ex){ die("Невозможно выполнить запрос: " . $ex->getMessage()); } 		

    header("Location: profile.php"); 
    die("Перенаправление: profile.php");
?>