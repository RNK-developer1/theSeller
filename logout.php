<?php 
    require("config.php"); 
    unset($_SESSION['user']);
    header("Location: index.php"); 
    die("Перенаправление: index.php");
?>