<?php
	$cfg = array(
		'dbh' => 'localhost',
		'dbu' => '#',
		'dbp' => '#',
		'dbn' => '#',
		'service' => false,
		'admins' => array(
			'#',
			'#'
		),
        'app_id' => #,
		'secret' => '#',
		'service' => false,

		'hash_secret' => '5lp83092nb368',
		'date' => date("d.m.Y H:i:s"),

		'vkc_key' => '#',
		'vkc_uid' => #,
		'vkc_course' => 1,
		'comission' => 5,

		'game_time' => 0,
		'minbet' => 0,
		'maxbet' => 0,

		'multipliers' => [
			'primary' => 1.5,
			'success' => 2.5,
			'danger' => 4.5
		],

		'perscents' => [
			0,
			0,
			0
		],

		'ref_bonus' => 1000,
		'ref_perscent' => 3,

		'segments' => [
			[1,3,5,7,9,13], //blue
			[8,2,11,15], //green
			[0,4,6,10,12,14] //red
		]
	);
	$cfg['game_time'] += 5;
	$cfg['dbl'] = mysqli_connect($cfg['dbh'],$cfg['dbu'],$cfg['dbp'],$cfg['dbn']);

	function w_log($data) {
		file_put_contents("logs/".date("Y.m.d")."_log.log", "\n".date("H:i:s")." | ".$data, FILE_APPEND);
	}	
	
	function authcheck($cfg,$uid,$token) {
		$req = "SELECT * FROM `roll_users` WHERE `uid`='".$uid."' AND `token`='".$token."'";
		$data = mysqli_fetch_array(mysqli_query($cfg['dbl'],$req));

		if (!$data || $data['token'] != $token) {
			echo "fail";
			exit();
		}
		
		return($data);
	}

	function hash_gen(){
		$chars="qazxswedcvrlcgbnhyujmkiolp1234567890QAZXSWDKMWFRTGBNHYUJMKIOLP";
		$max=16;
		$size=StrLen($chars)-1;
		$hash=null;
		while($max--) $hash.=$chars[random_int(0,$size)];

		return $hash;
	}

	function gcalc($cfg) {
		$req = "SELECT * FROM `roll_bets` ORDER BY `id` ASC";
		$bets = mysqli_fetch_all(mysqli_query($cfg['dbl'],$req),MYSQLI_ASSOC);
		if ($bets && $bets[0]['date'] == time() - $cfg['game_time'] + 10) {
			$req = "SELECT * FROM `roll_game` ORDER BY `id` DESC";
			$result = mysqli_fetch_array(mysqli_query($cfg['dbl'],$req));
			$req = "UPDATE `roll_game` SET `date`='".time()."' WHERE `id`='".$result['id']."'";
			mysqli_query($cfg['dbl'],$req);
			file_put_contents("result.json",json_encode(['result_date'=>time(),'result'=>$result['segment']]));
			$gr = $result['result'];

			switch ($gr) {
				case 0:
					$gr = 'primary';
					break;
				case 1:
					$gr = 'success';
					break;
				case 2:
					$gr = 'danger';
					break;
			}

			foreach ($bets as $k => $v) {
				if ($v['color'] == $gr) {
					$betsList[] = "ID ".$v['uid']." поставил ".$v['sum']." коинов на ".$v['color']." и выиграл ".($cfg['multipliers'][$v['color']] * $v['sum'])." коинов при выигрышном цвете ".$gr;
					$v['sum'] *= $cfg['multipliers'][$v['color']];
					$req = "UPDATE `roll_users` SET `score`=`score`+'".$v['sum']."',`notify`='Вы выиграли ".$v['sum']." <i class=\"fa fa-vk\"></i>' WHERE `uid`='".$v['uid']."'";
					mysqli_query($cfg['dbl'],$req);
				} else {
					$betsList[] = "ID ".$v['uid']." поставил ".$v['sum']." коинов на ".$v['color']." и проиграл при выигрышном цвете ".$gr;
					$req = "UPDATE `roll_users` SET `notify`='Вы проиграли' WHERE `uid`='".$v['uid']."'";
					mysqli_query($cfg['dbl'],$req);
				}
			}

			w_log("Розыгрыш ставок для игры №".$result['id'].":\n".implode("\n",$betsList));

			$fword = hash_gen();
			$sword = hash_gen();
			$num = random_int(0,100); //54
			if ($num <= $cfg['perscents'][0]) {
				$num = 0;
			} else if ($num <= $cfg['perscents'][0] + $cfg['perscents'][1]) {
				$num = 1;
			} else {
				$num = 2;
			}
			$hash = md5($fword.$num.$sword);
			$req = "INSERT INTO `roll_game`(`hash`,`result`,`fword`,`sword`,`segment`) VALUES ('".$hash."','".$num."','".$fword."','".$sword."','".$cfg['segments'][$num][random_int(0,count($cfg['segments'][$num])-1)]."')";
			mysqli_query($cfg['dbl'],$req);
		}
		if ($bets && $bets[0]['date'] <= time() - $cfg['game_time']) {
			$req = "TRUNCATE `roll_bets`";
			mysqli_query($cfg['dbl'],$req);
		}
	}
?>
