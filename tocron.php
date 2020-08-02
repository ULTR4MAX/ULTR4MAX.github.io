<?php
	include('cfg.php');
	// ini_set('display_errors', 1);
	// ini_set('display_startup_errors', 1);
	// error_reporting(E_ALL);

	if ($_GET['secret'] == 'syjTcKWkLWfuWTYgEAqZ6T3eHBUNxBrp') {
		$i = 60;
		while($i--){
			gcalc($cfg);
			sleep(1);
		}
	} else {
		http_response_code(403);
	}
?>