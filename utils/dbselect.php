<?php 
	error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);
	$host = $_ENV['DB_HOST'];
	$user = $_ENV['DB_USERNAME'];
	$password = $_ENV['DB_PASSWORD'];
	$dbname = $_ENV['DB_DATABASE'];
	$port = 3306;
	
/*
	$host = "mysql-container-global";
	$user = "root";
	$password = "GAndola@7A";
	$dbname = "aml";	
	$port = 3306;

	$host = 'localhost';
	$user = 'vittauy_aml';
	$password = 'Gandola@7';
	$dbname = "vittauy_aml";	
	$port = 3306;	
*/
//	$nickname = $_SESSION['username'];		
//	$userloggedid = $_SESSION['userid'];
	$conn = mysqli_connect($host, $user, $password,$dbname,$port) or die($msg_no_connect);
	mysqli_select_db($conn,$dbname) or die(mysqli_error($conn));	
?>
