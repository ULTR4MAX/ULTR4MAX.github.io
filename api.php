<?php
	// ini_set('display_errors', 1);
	// ini_set('display_startup_errors', 1);
	// error_reporting(E_ALL);

	include('cfg.php');
	$api = $_GET;

	if (!preg_match("/^[\d]+$/",$api['uid']) && $cfg['service']) {
		echo "fail";
		exit();
	}

	$data = authcheck($cfg,$api['uid'],$api['token']);

	if ($data) {
		switch($api['method']) {
			case 'load':
				if ($data['uname'] != $api['uname'] || $data['icon'] != $api['uicon']) {
					$req = "UPDATE `roll_users` SET `uname`='".$api['uname']."',`icon`='".$api['uicon']."',`sync`='".time()."',`notify`=NULL WHERE `uid`='".$api['uid']."'";
					mysqli_query($cfg['dbl'],$req);
				} else {
					$req = "UPDATE `roll_users` SET `sync`='".time()."',`notify`=NULL WHERE `uid`='".$api['uid']."'";
					mysqli_query($cfg['dbl'],$req);
				}

				$req = "SELECT * FROM `roll_bets` ORDER BY `id` DESC";
				$bets = mysqli_fetch_all(mysqli_query($cfg['dbl'],$req),MYSQLI_ASSOC);

				$req = "SELECT * FROM `roll_game` ORDER BY `id` DESC LIMIT 2";
				$results = mysqli_fetch_all(mysqli_query($cfg['dbl'],$req),MYSQLI_ASSOC);

				$req = "SELECT COUNT(`uid`) FROM `roll_users` WHERE `sync`>'".(time()-2)."'";
				$online = mysqli_fetch_array(mysqli_query($cfg['dbl'],$req))[0];

				$resp['round_time'] = $cfg['game_time'];
				$resp['hash'] = $results[0]['hash'];
				$resp['score'] = $data['score'];
				$resp['timer'] = $bets ? end($bets)['date'] + $cfg['game_time'] - time() : $cfg['game_time'];
				$resp['online'] = $online;
				$resp['bets'] = $bets;
				if ($data['notify'] != NULL) {
					$resp['notify'] = $data['notify'];
				}

				// if (end($bets) + $cfg['game_time'] - time() <= 5) {
					$resp['result'] = $results[1]['segment'];
					$resp['result_date'] = $results[1]['date'];
				// }
				break;

			case 'bet':
				if (!preg_match("/^[\d]+$/",$api['sum']) || $api['sum'] > $data['score'] || $api['sum'] < $cfg['minbet'] || $api['sum'] > $cfg['maxbet']) {
					$error = "fail_sum";
					if ($api['sum'] < $cfg['minbet']) {
						$error .= "_".$cfg['minbet'];
					} else if ($api['sum'] > $cfg['maxbet']) {
						$error .= "_".$cfg['maxbet'];
					}
					echo $error;
					exit();
				} else {
					$req = "SELECT * FROM `roll_bets` ORDER BY `id` ASC LIMIT 1";
					$firstbet = mysqli_fetch_array(mysqli_query($cfg['dbl'],$req));
					if (!$firstbet || time() - $cfg['game_time'] + 10 < $firstbet['date']) {
						if ($data['ref']) {
							$req = "UPDATE `roll_users` SET `score`=`score`+'".($api['sum'] * 0.01)."' WHERE `uid`='".$data['ref']."'";
							mysqli_query($cfg['dbl'],$req);
						}
						$req = "SELECT * FROM `roll_bets` WHERE `uid`='".$api['uid']."'";
						$bet = mysqli_fetch_array(mysqli_query($cfg['dbl'],$req));
						if ($bet && $api['color'] != $bet['color']) {
							echo "fail_color";
							exit();
						}

						if (!$bet) {
							$req = "INSERT INTO `roll_bets`(`uid`, `uname`, `uicon`, `color`, `sum`, `date`) VALUES ('".$data['uid']."','".$data['uname']."','".$data['icon']."','".$api['color']."','".$api['sum']."','".(time() - 1)."')";
						} else {
							$req = "UPDATE `roll_bets` SET `sum`=`sum`+'".$api['sum']."' WHERE `uid`='".$api['uid']."' AND `color`='".$api['color']."'";
						}
						mysqli_query($cfg['dbl'],$req);
						$req = "UPDATE `roll_users` SET `score`=`score`-'".$api['sum']."' WHERE `uid`='".$api['uid']."'";
						mysqli_query($cfg['dbl'],$req);
						echo "ok";
					} else {
						echo "fail_time";
						exit();
					}
				}
				exit();
				break;

			case 'payout':
				if (!preg_match("/^[\d]+$/",$api['sum']) || $api['sum'] > $data['score'] || $api['sum'] < 1) {
					echo 'fail';
					exit();
				} else {
					$posum = number_format($api['sum'] - ($cfg['comission'] / 100 * $api['sum']),0,'','') * 1000;
					$datas['merchantId'] = $cfg['vkc_uid'];
					$datas['key'] = $cfg['vkc_key'];
					$datas['toId'] = $api['uid'];
					$datas['amount'] = $posum;
					$datas['markAsMerchant'] = true;
					$datas = json_encode($datas);
					$ch = curl_init();
					curl_setopt_array($ch, [
						CURLOPT_URL => "https://coin-without-bugs.vkforms.ru/merchant/send/",
						CURLOPT_POST => true,
						CURLOPT_RETURNTRANSFER => true,
						CURLOPT_HTTPHEADER => array('Content-Type: application/json'),
						CURLOPT_POSTFIELDS => $datas
					]);
					$response = curl_exec($ch);
					$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
					curl_close($ch);

					if ($code == 200) {
						w_log("ID ".$api['uid']." запросил вывод ".$api['sum']." коинов; VKC response code: ".$code);
						$req = "UPDATE `roll_users` SET `score`=`score`-'".$api['sum']."' WHERE `uid`='".$api['uid']."'";
						mysqli_query($cfg['dbl'],$req);
					}
					exit();
				}
				break;

			case 'overlay':
				switch ($api['type']) {
					case 'score':
						echo '|<a target="_blank" href="https://vk.com/coin#x'.$cfg['vkc_uid'].'_1000_'.rand(-2000000000,2000000000).'_1" class="my-3 col-12 btn btn-primary btn-lg" role="button">Пополнить</a><div class="input-group input-group-lg"><input pattern="[0-9]*" id="posum" type="number" class="form-control input-lg" placeholder="Сумма вывода"><div class="input-group-append"><button id="po-btn" class="btn btn-lg btn-success" type="button" onclick="payout();">Вывести</button></div></div>';
						exit();
						break;

					case 'hash':
						$req = "SELECT * FROM `roll_game` ORDER BY `id` DESC LIMIT 3";
						$data = mysqli_fetch_all(mysqli_query($cfg['dbl'],$req),MYSQLI_ASSOC);
						if ($data[1]['date'] <= time() - 10) {
							$i = 1;
						} else {
							$i = 2;
						}
						$data = $data[$i];
						echo 'Проверка хэша|
						<p class="p-0 text-center h5 m-0 mt-1 col-12">Данные прошлого раунда</p>
						<p class="p-0 font-weight-bold m-0 mt-1 col-12">Хэш:</p>
						<p class="p-0 m-0 mb-1 col-12">'.$data['hash'].'</p>
						<p class="p-0 font-weight-bold m-0 mt-1 col-12">Первое секретное слово:</p>
						<p class="p-0 m-0 mb-1 col-12">'.$data['fword'].'</p>
						<p class="p-0 font-weight-bold m-0 mt-1 col-12">Второе секретное слово:</p>
						<p class="p-0 m-0 mb-1 col-12">'.$data['sword'].'</p>
						<p class="p-0 font-weight-bold m-0 mt-1 col-12">Число выигрышного цвета:</p>
						<p class="p-0 m-0 mb-3 col-12">'.$data['result'].'</p>
						<p class="p-0 font-weight-bold m-0 col-12">Как получился данный хэш:</p>
						<p class="p-0 m-0 col-12">1) Перед раундом определяется число выигрышного цвета от 0 до 2, где 0 - синий, 1 - зеленый, 2 - красный</p>
						<p class="p-0 m-0 col-12">2) Число помещается между двумя секретными словами так, что бы получилась такая строка: (слово)(число)(слово) [Пример с числом 2: слово2слово]</p>
						<p class="p-0 text-left m-0 mb-3 col-12">3) Получившаяся строка кодируется в md5 хэш</p>
						<p class="p-0 font-weight-bold m-0 col-12">Как проверить хэш:</p>
						<p class="p-0 m-0 col-12">1) Составьте строку, используя данные выше</p>
						<p class="p-0 m-0 col-12">2) Закодируйте строку в md5 хэш, к примеру тут: <a href="https://www.md5hashgenerator.com/" target="_blank">https://www.md5hashgenerator.com/</a></p>
						<p class="p-0 m-0 col-12">3) Сравните хэши, если они совпадают, значит, игра была честная</p>';
						exit();
						break;

					case 'faq':
						$req = "SELECT * FROM `roll_game` ORDER BY `id` DESC LIMIT 3";
						$data = mysqli_fetch_all(mysqli_query($cfg['dbl'],$req),MYSQLI_ASSOC);
						if ($data[1]['date'] <= time() - 10) {
							$i = 1;
						} else {
							$i = 2;
						}
						$data = $data[$i];
						echo 'F.A.Q|
						<p class="p-0 h6 m-0 mt-1 col-12">Lucky Spin - это Игра, построенная на системе VK Coin</p>
						<p class="p-0 m-0 border-top border-secondary pt-2 mt-2 font-weight-bold col-12">Правила игры:</p>
						<p class="p-0 m-0 col-12">Есть 3 цвета, у каждого свой множитель:</p>
						<p class="p-0 m-0 col-12">Синее х'.$cfg['multipliers']['primary'].'</p>
						<p class="p-0 m-0 col-12">Зеленое х'.$cfg['multipliers']['success'].'</p>
						<p class="p-0 m-0 col-12">Красное х'.$cfg['multipliers']['danger'].'</p>
						<p class="p-0 m-0 mt-1 col-12">Выбираешь цвет и после окончания раунда забираешь приз, если твоя ставка оказалась верной!</p>
						<p class="p-0 m-0 border-top border-secondary pt-2 mt-2 font-weight-bold col-12">Пополнение и Вывод:</p>
						<p class="p-0 m-0 mt-1 col-12">Для того что бы пополнить баланс нажмите на само слово "Баланс" в появившемся окне выберите пополнить или вывести</p>
						<p class="p-0 m-0 mt-1 col-12">Комиссия за вывод составляет 5% от суммы вывода
						<p class="p-0 m-0 border-top border-secondary pt-2 mt-2 font-weight-bold col-12">Предупреждение:</p>
						<p class="p-0 m-0 mt-1 col-12">Игра несёт развлекательный характер! Не вкладывайте в игру больше, чем можете позволить себе потерять!!!</p>';
						exit();
						break;

					case 'ref':
						$req = "SELECT * FROM `roll_users` WHERE `ref`='".$data['uid']."'";
						$rdata = mysqli_fetch_all(mysqli_query($cfg['dbl'],$req),MYSQLI_ASSOC);
						if ($rdata) {
							foreach ($rdata as $k => $v) {
								$online = $v['sync'] > time() - 2 ? '<i class="fa fa-circle text-success"></i> Online' : '<i class="fa fa-circle-o"></i> Offline';
								$list .= '<a href="https://vk.com/id'.$v['uid'].'" target="_blank" class="row m-0 mt-3 py-3 px-2 border border-primary rounded col-12"><div class="col-3 text-center p-0"><img class="rounded-circle" style="width:50px;" src="'.$v['icon'].'"></div><div class="col-9 p-0"><p class="text-dark m-0 font-weight-bold">'.$v['uname'].'</p><p class="text-dark m-0 font-weight-bold">'.$online.'</p></div></a>';
							}
						} else {
							$list = '<p class="h6 mt-5 col-12 text-center">Тут пока никого нет😢</p><img class="col-12" src="dist/img/hueta.png" style="width: 100%;">Приглашайте друзей и получайте 3% с их ставок! Список всех приглашённых вами пользователей, появится тут же.';
						}
						echo 'Рефералы|
						<p class="h6 m-0 mt-1 p-0 pb-1 col-12">Ваша реферальная ссылка:</p>
						<input class="form-control" type="text" value="https://vk.com/app'.$cfg['app_id'].'#r'.$api['uid'].'" readonly>
						<p class="h6 m-0 mt-3 p-0 pt-2 col-12 border-top border-secondary"></p>'.$list;
						exit();
						break;
					
					default:
						if (preg_match("/^[r][\d]+$/",$api['type'])) {
							$rid = explode('r',$api['type'])[1];
							if (!$data['ref'] && $data['uid'] != $rid) {
								echo 'Рефералка|<p class="h5 py-2 m-0 col-12 text-center"></p>
								<p class="col-12">Вы вошли в приложение используя реферальную ссылку другого пользователя</p>
								<p class="h5 py-2 m-0 col-12 text-center">Что получит владелец ссылки?</p>
								<p class="col-12">Владелец ссылки будет получать '.$cfg['ref_perscent'].'% с каждой вашей ставки</p>';

								$req = "UPDATE `roll_users` SET `score`='".($data['score']+$cfg['ref_bonus'])."',`ref`='".$rid."' WHERE `uid`='".$api['uid']."'";
								mysqli_query($cfg['dbl'],$req);
							} else if ($data['uid'] == $rid) {
								echo 'Рефералка|<p class="h5 py-2 m-0 col-12 text-center"></p>
								<p class="col-12">Вы перешли по своей реферальной ссылки!</p>';
							} else if ($data['ref']) {
								echo 'Рефералка|<p class="h5 py-5 my-5 col-12 text-center">Вы уже чей-то реферал!</p>';
							}
						} else {
							echo 'Ошибка|<p class="h5 py-5 my-5 col-12 text-center">Этот раздел недоступен</p>';
						}
						exit();
						break;
				}
				break;

			default:
				echo 'fail';
				break;
		}
		echo json_encode($resp);
	}
?>
