<?php
/**
 ***************************************************************************
 * 原版UI：雪落 https://xueluo.cn/ 2022-08-09
 * JS和PHP重写：夏仁南 https://xuxi.net/ 2025-05-13
 ***************************************************************************
 * 数据库
 * db-sitename:备忘录
 * db-password:08594e140bcc046e345325435218f67a85c38c63de6443b197b544d70ee62f26
 * db-note:[{"time":"1747236147817","title":"备忘录","content":"这是一个简单的基于文本数据的备忘录。"},{"time":"1747236158305","title":"中华人民共和国","content":"中华人民共和国是一个位于东亚的社会主义国家，首都为北京市，国土面积约为960万平方千米。全国共划分为23个省、5个自治区、4个直辖市和2个特别行政区共34个省级行政区，国土面积位居世界第三。"}]
***************************************************************************
*/
header('Content-Type: text/html; charset=utf-8');
session_start();
define('LOGIN', isset($_SESSION['login'])?$_SESSION['login']:0);
function msg($data) {
	$code = [1 => "ok",
		2 => "数据库连接失败",
		3 => "数据库操作失败",
		4 => "删除数据失败，ID 不存在"];
	return $code[$data];
}
function save($table, $data) {
	$fileContent = file_get_contents(__FILE__);
	$fileContent = preg_replace('/\s\*\sdb-'.$table.':.*/', ' * '.'db-'.$table.':'.$data, $fileContent);
	$put = file_put_contents(__FILE__, $fileContent, LOCK_EX);
	if ($put === false) {
		return false;
	} else {
		return true;
	}
}
function conn($field) {
	$file = file_get_contents(__FILE__);
	if ($file) {
		preg_match_all('/\s\*\sdb-(.*?):(.*)/', $file, $matches);
		$db = [];
		if (isset($matches[1]) && isset($matches[2])) {
			foreach ($matches[1] as $k => $v) {
				$db[$v] = trim($matches[2][$k]);
			}
		}
		return $db[$field];
	} else {
		return msg(2);
	}
}
function update($time, $title, $content, $table = "note") {
	if (empty(conn($table))) {
		$id = 0;
	} else {
		$table = json_decode(conn($table), true);
		$id = array_search($time, array_column($table, "time"));
		if ($id === false) {
			$id = count($table);
		}
	}
	$table[$id]["time"] = $time;
	$table[$id]["title"] = $title;
	$table[$id]["content"] = htmlspecialchars($content);
	array_multisort(array_column($table,'time'), SORT_ASC, $table);
	$table = json_encode($table, JSON_UNESCAPED_UNICODE);
	if (save('note', $table) === true) {
		return msg(1);
	} else {
		return msg(3);
	}
}
function delete($time, $table = "note") {
	$table = json_decode(conn($table), true);
	$id = array_search($time, array_column($table, "time"));
	if ($id === false) {
		return msg(4);
	}
	unset($table[$id]);
	array_values($table);
	if (save('note', json_encode($table, JSON_UNESCAPED_UNICODE)) === true) {
		return msg(1);
	} else {
		return msg(3);
	}
}
function get($key = false) {
	if ($key === false) return $_GET;
	$key = $_GET[$key];
	if (empty($key)) $key = "";
	return $key;
}
function post($key = false) {
	if ($key === false) return $_POST;
	$json = file_get_contents('php://input');
	$data = json_decode($json, true);
	$key = $data[$key];
	if (empty($key)) $key = "";
	return $key;
}
$page = get("page");
if ($page) {
	header('Content-Type: application/javascript; charset=utf-8');
	if ($page == 'login') {
		if (openssl_digest(get('password'), "sm3", false) === conn("password")) {
			$_SESSION['login'] = 1;
			exit("ok");
		}
		exit("密码不正确");
	}
	elseif ($page == 'logout') {
		unset($_SESSION['login']);
		unset($_SESSION);
		session_destroy();
		exit("ok");
	}
	else {
		if (!LOGIN) {
			exit("请登录");
		}
		if ($page == 'setting') {
			$sitename = post("sitename");
			$password = post("password");
			if($sitename) save("sitename",$sitename);
			if($password) save("password",openssl_digest($password, "sm3", false));
			exit("ok");
		}
		if ($page == 'noteget') {
			exit(conn("note"));
		}
		if ($page == 'noteupdate') {
			$time = post("time");
			$title = post("title");
			$content = post("content");
			$msg = update($time, $title, $content);
			if ($msg === "ok") {
				exit("ok");
			}
			exit($msg);
		}
		if ($page == 'notedelete') {
			$time = get("time");
			$msg = delete($time);
			if ($msg === "ok") {
				exit("ok");
			}
			exit($msg);
		}
	}
	exit("参数不存在");
}
?>
<!DOCTYPE html>
<html lang="zh-Hans">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<meta name="viewport" content="width=device-width,initial-scale=1.0,minimum-scale=1.0,maximum-scale=1.0" />
	<title><?=conn("sitename")?></title>
	<style>*{margin:0;padding:0;font-family:Tahoma,Arial,sans-serif,'Microsoft YaHei';font-size:14px;box-sizing:border-box}body{color:#373e4e;background:#f5f5f5;margin:50px 30px 150px}::selection{color:#fff;background-color:#4f9552}input::-webkit-input-placeholder,textarea::-webkit-input-placeholder{color:#c5c5c5}input{color:#525d76;resize:none;outline:0;height:25px;line-height:25px;padding:0 10px;box-sizing:border-box;border:1px solid #9f9f9f;vertical-align:middle;border-radius:2px}a{color:#373e4e}a:hover{color:#57af4c;text-decoration:underline}.btn{color:#fff;background-color:#373e4e;padding:0 25px;border:none;height:25px;line-height:25px;margin:10px 0;display:inline-block;text-decoration:none!important;cursor:pointer;vertical-align:middle;border-radius:2px}.btn:hover{color:#fff;background-color:#5e6779}.title{text-align:center;font-size:27px;margin-bottom:20px;}.prompt{margin-top:10px}.login{text-align:center;background:#fff;width:500px;margin:0 auto;padding:30px;border-radius:4px}.note{width:40%;margin:auto}.tip{background:#fbfbfb;margin:10px;margin-bottom:30px;border-radius:4px;padding:0 10px;position:relative}.tip-title{padding:15px 20px 10px 20px;font-size:16px;border-bottom:1px solid #e1e1e1;margin-bottom:15px;height:50px;display:flex;align-items:center;font-weight:700;outline:0;}.tip-title a{margin-left:10px;text-decoration:none;font-weight:400;}.tip-title a:first-child{margin-left:auto}.tip-close{position:absolute;right:20px;top:15px;cursor:pointer;visibility:hidden}.tip-content{padding:0 20px 20px;color:#444;outline:0;white-space:pre-wrap;word-break:break-all}.tip-content p{margin:3px 0}.tool{position:fixed;top:120px;left:calc(70% + 20px)}.tool-btn{background:#4caf50;color:#fff;height:50px;width:50px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:14px;font-family:initial;font-weight:300;cursor:pointer;margin-bottom:10px}.tool-btn:hover{background:#318334}#logout,#setting,{background:#a2a8ba}#logout:hover,#setting:hover{background:#858ca1}.form{margin:10px 0;padding:0 10px;display:flex}.key{display:inline-block;text-align:right;padding-right:10px;vertical-align:top;line-height:25px}.value{flex:1;line-height:25px}.value input[type=number],.value input[type=password],.value input[type=text]{width:100%}.setting,.add{text-align:center;position:fixed;top:0;left:0;right:0;bottom:0;margin:auto;width:400px;height:200px;background:#fff;padding:15px;box-shadow:0 0 15px rgb(0 0 0 / 18%);border-radius:4px;display:none}.setting-close,.add-close{position:absolute;top:15px;right:15px;cursor:pointer}.setting-title{text-align:left;font-weight:700;margin-bottom:20px}.add{width:600px;height:300px;}.add-title{margin-top:5px;padding-bottom:10px;
width:100%;border:none;border-bottom:1px solid #e1e1e1;}.add-content{text-align:left;padding:10px;color:#444;height:210px;overflow:auto;outline:0;white-space:pre-wrap;word-break:break-all}.add-content p{margin:3px 0}.tool{position:fixed;top:120px;left:calc(70% + 20px)}@media (max-width:768px){body{margin:20px 0 150px}.note{width:100%;margin:auto}.tool{top:initial;left:initial;bottom:50px;right:30px}}</style>
	<script>
		function get(url, callback) {
			fetch(url, {method: "GET",})
			.then(response => response.text())
			.then(data => {callback(data);});
		}

		function post(url, data, callback) {
			fetch(url, {
				method: "POST",
				headers: {
					"Content-Type": "application/json",
				},
				body: JSON.stringify(data),
			})
			.then(response => response.text())
			.then(data => {callback(data);});
		}
	</script>
</head>
<body>
	<div class="title"><?=conn("sitename")?></div>
	<?php if (!LOGIN) { ?>
		<div class="login">
			<input type="password" name="password" placeholder="密码登录" />
			<input type="submit" class="btn" value="确认" onclick="login()" />
			<div id="prompt" class="prompt">请输入密码</div>
		</div>
		<script>
			function login() {
				password = document.getElementsByName('password')[0].value;
				get("?page=login&password="+password, callback)
				function callback(data) {
					if (data === "ok") {
						location.reload();
					} else {
						document.getElementById("prompt").innerHTML = data;
					}
				}
			}
		</script>
		<?php } else { ?>
		<div class="note">
			<div id="note-list"></div>
		</div>
		<div class="tool">
			<div class="tool-btn" id="note-add">添加</div>
			<div class="tool-btn" id="note-del" title="显示删除按钮（刷新后恢复隐藏，防止误删除。）">删除</div>
			<div class="tool-btn" id="setting">设置</div>
			<div class="tool-btn" id="logout">退出</div>
		</div>
		
		<div class="add">
			<div class="add-close" onclick='this.parentNode.style.display="none"'>✕</div>
			<div class="add-time" style="display:none;"></div>
			<input type="text" name="add-title" class="add-title" maxlength="35" onblur="if(this.value==='') this.value='未命名'" onfocus="if(this.value==='未命名') this.value=''" value="未命名">
			<div class="add-content" contenteditable="plaintext-only"></div>
			<div class="btn" onclick="into()">确定</div>
		</div>
		
		<div class="setting">
			<div class="setting-title">设置</div>
			<div class="setting-close">✕</div>
			<div class="form">
				<div class="key">网站标题</div>
				<div class="value">
					<input type="text" name="sitename" placeholder="网站标题" value="<?=conn("sitename")?>" />
				</div>
			</div>
			<div class="form">
				<div class="key">登录密码</div>
				<div class="value">
					<input type="password" name="password" placeholder="留空不修改密码" value="" />
				</div>
			</div>
			<div class="setting-msg"></div>
			<div class="btn" name="setting-post">确定</div>
		</div>
		<script>
			var data = <?php if (empty(conn("note"))){echo "[]";}else{echo conn("note");}; ?>;
			data.sort((a, b) => b.time - a.time); //按 time 降序排列
			if (Array.isArray(data)) {
				var html = '';
				data.forEach((v, k)=> {
					var time = FormatTime("yyyy-MM-dd hh:mm:ss", Number(v.time));
					html += `<div class="tip" data-time="${v.time}"><div class="tip-close"><svg width="24" height="24" xmlns="http://www.w3.org/2000/svg"><path d="M15.5 5.5V5a.5.5 0 0 0-.5-.5H9a.5.5 0 0 0-.5.5v.5h7zm-10 1h-2a.5.5 0 0 1 0-1h4V5A1.5 1.5 0 0 1 9 3.5h6A1.5 1.5 0 0 1 16.5 5v.5h4a.5.5 0 1 1 0 1h-2V19a1.5 1.5 0 0 1-1.5 1.5H7A1.5 1.5 0 0 1 5.5 19V6.5zm1 0V19a.5.5 0 0 0 .5.5h10a.5.5 0 0 0 .5-.5V6.5h-11zM10 9.293a.5.5 0 0 1 .5.5v6.414a.5.5 0 0 1-1 0V9.793a.5.5 0 0 1 .5-.5zm4 0a.5.5 0 0 1 .5.5v6.414a.5.5 0 0 1-1 0V9.793a.5.5 0 0 1 .5-.5z" fill="#979797"/></svg></div><div class="tip-title" contenteditable="plaintext-only" title="${time}">${v.title}</div><div class="tip-content" contenteditable="plaintext-only">${v.content}</div></div>`;
				})
				document.getElementById("note-list").innerHTML = html;
				noteInit();
			} else {
				alert(data);
			}
			var timeout = 0;
			function noteInit() {
				var element_title = document.getElementsByClassName('tip-title');
				Array.from(element_title).forEach(element => {
					element.oninput = function() {
						var time = this.parentNode.getAttribute("data-time"); 
						var title = this.innerText;
						var content = element.nextElementSibling.innerText;
						clearTimeout(timeout);
						timeout = setTimeout(function(){
							post(window.location.href+"?page=noteupdate",{
								"time": time, "title": title, "content": content
							}, function(data) {
								if (data !== "ok") alert(data);
							});
				        },500);
					}
				});
				var element_content = document.getElementsByClassName('tip-content');
				Array.from(element_content).forEach(element => {
					element.oninput = function() {
						var time = this.parentNode.getAttribute("data-time");
						var title = element.previousElementSibling.innerText;
						var content = this.innerText;
						clearTimeout(timeout);
						timeout = setTimeout(function(){
							post(window.location.href+"?page=noteupdate", {
								"time": time, "title": title, "content": content
							}, function(data) {
								if (data !== "ok") alert(data);
							});
						},500);
					}
				});
				var element_close = document.getElementsByClassName('tip-close');
				Array.from(element_close).forEach(element => {
					element.addEventListener('click', function() {
						if (confirm("确定删除？")) {
							var time = this.parentNode.getAttribute("data-time");
							get("?page=notedelete&time="+time, function(data) {
								if (data === "ok") {
									element.parentNode.parentNode.removeChild(element.parentNode);
								} else {
									alert(data);
								}
							});
						}
					});
				});
			}
			function into() {
				var timeout = 0;
				const time = document.getElementsByClassName('add-time')[0].innerText;
				const title = document.getElementsByName('add-title')[0].value;
				const content = document.getElementsByClassName('add-content')[0].innerText;
				if (title === "未命名" || title === "") return alert("没写标题");
				if (content === "") return alert("没写内容");
				clearTimeout(timeout);
				timeout = setTimeout(function(){
					post(window.location.href+"?page=noteupdate", {
						"time": time, "title": title, "content": content
					}, function(data) {
						if (data === "ok") {
							location.reload();
						} else {
							alert(data);
						}
					});
				},500);
			}
			document.getElementById('note-add').addEventListener('click', function() {
				document.getElementsByClassName('add')[0].style.display = "initial";
				document.getElementsByClassName('add-time')[0].innerText = Date.now();
			});
			document.getElementById('note-del').addEventListener('click', function() {
					const elements = document.getElementsByClassName('tip-close');
					Array.from(elements).forEach(element => {
						if (window.getComputedStyle(element).getPropertyValue("visibility") === "hidden") {
							element.style.visibility = "visible";
						} else {
							element.style.visibility = "hidden";
						}
					});
			});
			document.getElementById('setting').addEventListener('click', function() {
				document.getElementsByClassName('setting')[0].style.display = "initial";
			});
			document.getElementsByClassName('setting-close')[0].addEventListener('click', function() {
				document.getElementsByClassName('setting')[0].style.display = "none";
			});
			document.getElementsByName('setting-post')[0].addEventListener('click', function() {
				const sitename = document.getElementsByName('sitename')[0].value;
				const password = document.getElementsByName('password')[0].value;
				post(window.location.href+"?page=setting", {
					"sitename": sitename, "password": password
				}, function(data) {
					document.getElementsByName('password')[0].value = "";
					if (data === "ok") {
						alert("设置修改成功");
						location.reload();
					} else {
						alert(data);
					}
				});
			});
			document.getElementById('logout').addEventListener('click', function() {
				get("?page=logout", function(data) {
					if (data === "ok") {
						location.reload();
					} else {
						alert(data);
					}
				});
			});
			function FormatTime(t, date) {
				var date = new Date(date);
				var o = {
					"M+": date.getMonth()+1,//月份
					"d+": date.getDate(),//日
					"h+": date.getHours(),//小时
					"m+": date.getMinutes(),//分
					"s+": date.getSeconds(),//秒
					"q+": Math.floor((date.getMonth()+3)/3),//季度
					"S": date.getMilliseconds() //毫秒
				};
				if (/(y+)/.test(t)) {
					t = t.replace(RegExp.$1, (date.getFullYear()+"").substr(4-RegExp.$1.length));
				};
				for (var k in o) {
					if (new RegExp("("+ k +")").test(t)) {
						t = t.replace(RegExp.$1, (RegExp.$1.length == 1)?(o[k]): (("00"+ o[k]).substr((""+o[k]).length)));
					};
				}
				return t;
			};
		</script>
		<?php } ?>
</body>
</html>
