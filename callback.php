<?php
	include("cfg.php");
	http_response_code(200);
	if ($_GET['secret'] == 'LrCYHw3k2e38rs4ffSGjUGsZ5mk4lfNM') {
		$data = json_decode(file_get_contents("php://input"),true);
		if ($data['fromId'] == $data['from_id'] && $data['toId'] == $data['to_id'] && (time() - 5) <= $data['created_at']) {
			$req = "UPDATE `roll_users` SET `score`=`score`+'".($data['amount'] / 1000 / $cfg['vkc_course'])."' WHERE `uid`='".$data['from_id']."'";
			mysqli_query($cfg['dbl'],$req);
			w_log("ID ".$data['from_id']." пополнил баланс на ".$data['amount']." коинов");
			echo "ok";
		}
	}
?>