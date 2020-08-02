//vars
var uri = 'api.php';
var loaded = false;
var uname = '';
var uicon = '';
var timer = 500;
var last_result = [0,0];
var new_result = false;
var score = 0;
var hash = 0;
var bets = [];
var inter = 0;
var gtimer = 0;
var ntf = false;
var online = 0;
var defpos = 0;

//funcs
function notify(i,t,d){
	let n = Math.round((Math.random() * 1000000));
	$("#notify").prepend('<div class="my-2 bg-dark" id="notify-'+n+'" onclick="$(this).toggle(500,function(){$(this).remove();});"><div class="row p-2"><div class="col-3 text-center"><img class="c-rounded" width="100%" src="'+i+'"></div><div class="col-9 p-0"><p class="mb-0 text-white font-weight-bold">'+t+'</p><p class="mb-0 text-white">'+d+'</p></div></div></div>'
	);
	$("#notify-"+n).toggle(500);
	let s = setTimeout(function(){
		$("#notify-"+n).toggle(500,function(){
			$("#notify-"+n).remove();
		});
		clearTimeout(s);
	},2000);
}
function overlay_toggle(page = false){
	if (page == false) {
		$("#overlay").toggle(200);
		$("#overlay-inner").html('');
		$("#overlay-title").text('');
	} else {
		$.ajax({
			url: uri,
			cache: false,
			data: {
				token: token,
				uid: uid,
				type: page,
				method: 'overlay'
			},
			success: function(html){
				if (html != 'fail') {
					$("#overlay-title").text(html.split('|')[0]);
					$("#overlay-inner").html(html.split('|')[1]);
					$("#overlay").toggle(200);
				} else {
					notify("dist/img/logo-icon.jpg","Уведомление","Ошибка доступа");
				}
			}
		});
	}
}
$('#overlay').click(function(){
	overlay_toggle();
}).children().click(function(e){
	e.stopPropagation();	
});


function load(){
	$.ajax({
		url: uri,
		cache: false,
		data: {
			token: token,
			uid: uid,
			uicon: uicon,
			uname: uname,
			method: 'load'
		},
		success: function(html){
			if (html != 'fail') {
				let data = JSON.parse(html);
				timer = data.timer;
				gtimer = data.round_time;
				online = data.online;
				new_result = [data.result_date,data.result];
				if (typeof data.notify != 'undefined') { ntf = data.notify; }
				if (timer > 9) {
					bets = data.bets;
					hash = data.hash;
					score = data.score;
				}
			} else {
				$('body').children().remove();
			}
		},
		error: function(html){
			notify("dist/img/logo-icon.jpg","Уведомление","Ошибка подключения");
			$('body').children().remove();
		}
	});
}

function bet(color){
	let sum = $('#betsum').val();
	if (timer >= 11) {
		$.ajax({
			url: uri,
			cache: false,
			data: {
				token: token,
				uid: uid,
				color: color,
				sum: sum,
				method: 'bet'
			},
			success: function(html){
				if (html == 'fail') {
					notify("dist/img/logo-icon.jpg","Ошибка","Ошибка доступа");
				} else if (html == 'fail_sum') {
					notify("dist/img/logo-icon.jpg","Ошибка","Укажите другую сумму");
				} else if (html == 'fail_time') {
					notify("dist/img/logo-icon.jpg","Ошибка","Ставки больше не принимаются");
				} else if (html == 'fail_color') {
					notify("dist/img/logo-icon.jpg","Ошибка","Вы уже поставили на другой цвет");
				} else if (html == 'ok') {
					notify("dist/img/logo-icon.jpg","Уведомление","Ставка принята");
				} else {
					let limsum = html.split('_')[2];
					if (sum < +limsum) {
						notify("dist/img/logo-icon.jpg","Ошибка","Минимальная ставка "+limsum+" <br>коинов");
					} else {
						notify("dist/img/logo-icon.jpg","Ошибка","Максимальная ставка "+limsum+" <br>коинов");
					}
				}
				load();
			}
			,error: function(){
				notify("dist/img/logo-icon.jpg","Ошибка","Ошибка подключения");
			}
		});
	} else {
		notify("dist/img/logo-icon.jpg","Ошибка","Ставки больше не принимаются");
	}
}

function get_result(){
	$.getJSON("result.json",function(data){
		draw(data.result);
		last_result = data;
	});
}

function draw(segment = false){
	let state;
	if (timer <= 5) {
		state = 'Розыгрыш';
	} else if (timer == gtimer) {
		state = 'Ожидание';
	} else {
		let m = Math.floor((timer - 5) / 60);
		let s = (timer - 5) % 60;
		m = m < 10 ? '0'+m : m;
		s = s < 10 ? '0'+s : s;
		state = m+':'+s;
	}

	//Draw
	let pertime = 100 / ((gtimer - 5) / (timer - 5));
	pertime = pertime < 0 ? 0 : pertime;
	pertime = pertime > 100 ? 100 : pertime;
	$('#timer').children('p').text(state);
	$('#timer').children('div').animate({
		width: pertime+"%"
	},500);

	//Animation
	if (segment) {
		let bgpos = defpos - 5040 - (segment * 70);
		$("#spin-cnt").animate({
			"background-position-x": bgpos
		},5000,$.bez(0,1,0,1),function(){
			$('.score').html('Баланс: '+score+' <i class="fa fa-vk"></i>');
			$('#hash').text('Хэш игры: '+hash);
			$("#spin-cnt").css('background-position-x',(bgpos + 5600) + 'px');
			if (ntf) {notify("dist/img/logo-icon.jpg","Уведомление",ntf); ntf = false;}
		});
	} else {
		if (timer > 10) {
			$('.score').html('Баланс: '+score+' <i class="fa fa-vk"></i>');
			$('#hash').text('Хэш игры: '+hash);
			if (ntf) {notify("dist/img/logo-icon.jpg","Уведомление",ntf); ntf = false;}
		}
	}

	$("#online").text("Online: "+online);

	//Bets list
	if (bets.length > 0) {
		let list = '';
		$.each(bets,function(i,v){
			list += '<a href="https://vk.com/id'+v.uid+'" target="_blank" class="row m-0 mt-3 py-3 px-2 bg-'+v.color+' border border-'+v.color+' rounded">'+
						'<div class="col-3 text-center p-0">'+
							'<img class="rounded-circle" style="width:50px;" src="'+v.uicon+'">'+
						'</div>'+
						'<div class="col-9 p-0">'+
							'<p class="text-white m-0 font-weight-bold">'+v.uname+'</p>'+
							'<p class="text-white m-0 font-weight-bold">'+v.sum+' <i class="fa fa-vk"></i></p>'+
						'</div>'+
					'</a>';
		});
		list = '<p class="col-12 pb-2 text-center text-white h4 border-bottom border-white">Ставки</p>'+list;
		if (list != $('#bets').html()) {
			$('#bets').html(list);
		}
	} else {
		$('#bets').html('<p class="col-12 pb-2 text-center text-white h4 border-bottom border-white">Ставки</p><p class="col-12 py-4 text-center text-white m-0">Ожидаем ставки</p>');
	}
}

function payout(){
	$('#po-btn').attr('disabled','');
	let sum = $('#posum').val();
	$.ajax({
		url: uri,
		cache: false,
		data: {
			token: token,
			uid: uid,
			sum: sum,
			method: 'payout'
		},
		success: function(html){
			if (html != 'fail') {
				overlay_toggle();
				notify("dist/img/logo-icon.jpg","Уведомление","Ваши коины уже в пути");
				load();
			} else {
				$('#po-btn').removeAttr('disabled');
				notify("dist/img/logo-icon.jpg","Уведомление","Укажите другую сумму");
			}
		},
		error: function(){
			overlay_toggle();
			notify("dist/img/logo-icon.jpg","Уведомление","Ошибка подключения");
		}
	});
}

window.onload = function(){
	send('VKWebAppInit', {});
	send("VKWebAppGetUserInfo", {});
	send("VKWebAppSetViewSettings", {
		"status_bar_style": "light",
		"action_bar_color": "#000"
	});

	$('#timer').children('div').height($('#timer').children('p').height());
	
	setTimeout(function(){
		defpos = -(595 - ($('#spin-cnt').width() / 2));
		$('#spin-cnt').css("background-position-x", defpos+'px');
		send("VKWebAppJoinGroup", {"group_id": 191184159});
	},100);

	setInterval(function(){
		if (timer == 5) {
			get_result();
		} else if (timer > 5) {
			draw();
		}

		if (bets.length > 0) {
			timer--;
		}
		load();
	},1000);

	//page script
	subscribe((e) => {
		if (e.detail.type === "VKWebAppGetUserInfoResult") {
			uname = e.detail.data.first_name+' '+e.detail.data.last_name;
			uicon = e.detail.data.photo_100;
			load();
		}
	});

	if (linkAction) {
		overlay_toggle(linkAction);
	}
}
