<?php
	include("cfg.php");

	if ($_GET['vk_platform'] == 'web' || !$_GET['vk_platform']) {
		exit();
	}
	$_GET['uid'] = $_GET['vk_user_id'];

	if (!in_array($_GET['uid'],$cfg['admins']) && $cfg['service']) {
		exit();
	}

	//check sign
	$base_url = ( isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']=='on' ? 'https' : 'http' ) . '://' .  $_SERVER['HTTP_HOST'];
 	$url = $base_url . $_SERVER["REQUEST_URI"];
	$client_secret = $cfg['secret'];

	$query_params = []; 
	parse_str(parse_url($url, PHP_URL_QUERY), $query_params);
	$sign_params = []; 
	foreach ($query_params as $name => $value) { 
		if (strpos($name, 'vk_') !== 0) {
			continue;
		}
		$sign_params[$name] = $value;
	} 

	ksort($sign_params);
	$sign_params_query = http_build_query($sign_params);
	$sign = rtrim(strtr(base64_encode(hash_hmac('sha256', $sign_params_query, $client_secret, true)), '+/', '-_'), '=');
	$status = $sign === $query_params['sign'];
	if (!$status) {
		echo "Authentification Error";
		exit();
	}
	$_GET['auth_key'] = $_GET['sign'];


	if ( $_GET['uid'] && preg_match("/^[\d]+$/",$_GET['uid']) ) {
		//check user
		$req = "SELECT * FROM `roll_users` WHERE `uid`='".$_GET['uid']."'";
		$data = mysqli_fetch_array(mysqli_query($cfg['dbl'],$req));
		
		if (!$data && $_GET['uid']) {
			//create new user
			$req = "INSERT INTO `roll_users`(`uid`,`sync`,`token`) VALUES (".$_GET['uid'].",'".time()."','".$_GET['auth_key']."')";
			mysqli_query($cfg['dbl'],$req);
		} else if ($data && $_GET['uid'] && $data['uid'] == $_GET['uid'] && $_GET['auth_key']) {
			$req = "UPDATE `roll_users` SET `sync`='".time()."',`token`='".$_GET['auth_key']."' WHERE `uid`='".$_GET['uid']."'";
			mysqli_query($cfg['dbl'],$req);
		} else if (!$data && !$_GET['uid']) {
			echo "Authentification Error";
			exit();
		}
	}

	$adapt = $_GET['vk_platform'] == 'mobile_iphone' ? ' pt-4' : '';

	$css = '<link rel="stylesheet" href="dist/css/main.css?'.rand(0,51651655616561).'">';
	$js = '<script src="dist/js/main.js?'.rand(0,51651655616561).'"></script>';

	// if (!in_array($_GET['uid'],$cfg['admins'])) {exit();}
?>
<!DOCTYPE html>
<html>
	<head>
		<title>Game</title>
		<!-- Required meta tags -->
		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no, user-scalable=no, viewport-fit=cover">
		<meta name="theme-color" content="#ffffff">
		<meta content="IE=Edge" http-equiv="X-UA-Compatible">
		<!-- Bootstrap CSS -->
		<link rel="stylesheet" href="dist/css/bootstrap.min.css?1">
		<!-- Font Obossan Kak Opossum-->
		<link rel="stylesheet" href="dist/fa/css/font-awesome.min.css">
		<!-- Custom CSS -->
		<?=$css?>
		<!-- JQuery JS -->
		<script src="dist/js/jquery.min.js"></script>
		<script src="dist/js/jquery.bez.min.js"></script>
		<!-- VK Connect-->
		<script type="text/javascript" type="module" src="dist/js/vkconnect.min.js"></script>
	</head>
	<body class="bg-dark">
		<div id="notify" class="container-fluid p-2 pt-2 position-absolute"></div>

		<div class="cnt pt-3 <?=$adapt?>">
			<div class="col-12 mb-3">
				<div class="col-12 p-3 block">
					<div class="col-12 btn-group p-0">
						<button type="button" class="col-6 btn btn-outline-light" onclick="overlay_toggle('faq')">F.A.Q</button>
						<button type="button" class="col-6 btn btn-outline-light" onclick="overlay_toggle('ref')">Рефералы</button>
					</div>
				</div>
			</div>

			<div class="col-12 mb-3">
				<div class="col-12 p-3 block">
					<!-- <a target="_blank" href="https://vk.com/doublevkc" class="position-absolute">
						<i style="font-size: 1.5em;" class="fa fa-vk text-white"></i>
					</a> -->
					<div class="mb-3 p-0 text-center text-white rounded bg-dark position-relative" id="online"></div>
					<div class="mb-3 p-0 text-center text-white rounded bg-dark position-relative" id="timer">
						<div class="bg-danger rounded"></div>
						<p class="text-white text-center position-absolute col-12 m-0" style="top:0;">Ожидание</p>
					</div>
					<div id="spin-cnt" class="col-12 border border-white rounded"><div id="spin-arrow"></div></div>
					<p class="m-0 mt-3 py-1 text-center text-white rounded bg-dark" style="font-size: 0.7rem;" id="hash" onclick="overlay_toggle('hash')"></p>
				</div>
			</div>

			<div class="col-12 mb-3">
				<div class="col-12 p-3 block">
					<div class="score col-12 mb-3 rounded text-center text-white bg-dark" onclick="overlay_toggle('score')"></div>

					<div class="input-group mb-3">
						<input id="betsum" type="number" pattern="[0-9]*" class="form-control" placeholder="Сумма ставки">
						<div class="input-group-append">
							<button class="btn btn-dark c-btn-dark" type="button" onclick="$('#betsum').val( Math.floor( $('#betsum').val() / 2 ) );">1/2</button>
							<button class="btn btn-dark c-btn-dark" type="button" onclick="$('#betsum').val( $('#betsum').val() * 2 );">x2</button>
						</div>
					</div>

					<div class="col-12 btn-group p-0">
						<button type="button" class="btn btn-lg btn-primary" onclick="bet('primary')">x<?=$cfg['multipliers']['primary']?></button>
						<button type="button" class="btn btn-lg btn-success" onclick="bet('success')">x<?=$cfg['multipliers']['success']?></button>
						<button type="button" class="btn btn-lg btn-danger" onclick="bet('danger')">x<?=$cfg['multipliers']['danger']?></button>
					</div>
				</div>
			</div>

			<div class="col-12 mb-3">
				<div class="col-12 p-3 block" id="bets"></div>
			</div>
		</div>

		<div id="overlay" style="display: none;">
			<div id="overlay-block" class="position-absolute container-fluid">
				<div class="col-12">
					<p id="overlay-title" class="text-center font-weight-bold pt-1 mb-1"></p>
					<div id="overlay-inner" class="row pb-3"></div>
				</div>
			</div>
		</div>
		
		<?=$js?>
		<script type="text/javascript">
			const token = "<?=$_GET['auth_key']?>";
			const uid = <?=$_GET['uid']?>;
			var linkAction = window.location.href.split('#')[1];
		</script>
	</body>
</html>