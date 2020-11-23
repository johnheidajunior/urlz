<?php
	$dbHost     = "localhost";
	$dbUsername = "johneqjo_urlzUSR";
	$dbPassword = "-XMLNz]DbLLw";
	$dbName     = "johneqjo_urlz";

	try{
		$dbConnection = new mysqli($dbHost,$dbUsername,$dbPassword,$dbName);
	} catch(mysqli_sql_exception $e) {
		echo "Connection failed: " . $e->getMessage();
	}
?>