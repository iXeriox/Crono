<?php
global $dAmnPHP, $running, $Handler;

/*
Author: iXeriox
This is the Core file for Crono.
*/

class Crono {
	function confirm_load() {
		console("Loaded the core files. ", 'Core');
	}



	function checkCommand($command, $c, $from, $args) {
		switch ($command) {
			case 'join':
				global $from, $dAmnPHP;
				if (!isset($args[1])) {
					$dAmnPHP->say(deform($c), "$from: please state a room you want me to join! ");
				} else {
					$dAmnPHP->join(deform($args[1]));
				}
				break;

			case 'part':
				global $from, $dAmnPHP;
				if (!isset($args[1])) {
					$dAmnPHP->part(deform($c));
				} else {
					$dAmnPHP->part(deform($args[1]));
				}
				break;

			case 'say':
				global $dAmnPHP;
				if (!isset($args[1])) {
					global $from;
					$dAmnPHP->say(deform($c), "$from: What do you want me to say? ");
					return;
				}
				$say_this = false;
				foreach ($args as $say => $lol) {
					$say_this .= $args[$say] . ' ';
				}
				$dAmnPHP->say(deform($c), $say_this);
				break;

			case 'set':
				global $config, $dAmnPHP;
				$config['bot']['test'] = 'bullshit';
				save_config('bot');
				break;

			case 'get':
				global $config, $dAmnPHP;
				load_config('bot');
				$dAmnPHP->say(deform($c), $config['bot']['test']);
				break;

			case 'about':
				global $dAmnPHP, $config;
				
				$about  = '<b><sub>Project crono (Development)</b><br>';
				$about .= 'Created by :devoxai: and :deviXeriox: <br>';
				$about .= 'Owned by :dev' . $config['owner'] . ':';
				if (isset($args[1]) && $args[1] == 'uptime') {
					global $start;
					$about .= '<br> Crono has been online for: ' . timeify($start - time());
				}
				$dAmnPHP->say(deform($c), $about);
				break;

			default:
				console(" Unknown command! ", "Core");
				break;
		}
	}
	
	function process_it($response) {
		global $userinfo;
		if (strlen($response) == 0)
			return;
		$data = sort_dAmn_packet($response);
		$usen = true;
		$log  = false;
		$save = false;
		$hn   = false;
		$p    = $data['p'];
		switch ($data['event']) {
			case 'connected':
				console('Connected to dAmnServer ' . $p[0] . '.', 'Connection');
				break;

			case 'login':
			global $config;
				if ($p[0] == 'ok') {
					console('Logged in as ' . $config['username'] . '!', 'Connection');
				} else {
					console('Login failed. ' . ucfirst($p[0]) . '.', 'Connection');
				}
				break;

			case 'join':
			case 'part':
				$log = ucfirst($data['event']);
				if ($p[1] == 'ok') {
					$log .= ' successful';
				} else {
					$log .= ' failed';
				}
				$log .= ' for ' . ($save != false ? $save : update($p[0]));
				if ($p[1] != 'ok')
					$log .= ' [' . $p[1] . ']';
				if ($p[1] == 'ok' && $p[2] != false)
					$log .= ' [' . $p[2] . ']';
				console($log, "Core");
				break;

			case 'property':
				$log = 'Got ' . $p[1] . ' for ' . update($p[0]) . '.';
				console($log, "Core");
				break;

			case 'recv_msg':
			case 'recv_action':
				global $dAmnPHP, $config, $from, $message;
				load_config( 'botinfo' );
				$from    = $p[1];
				$message = $p[2];
				$c       = $chatroom = update($p[0]);
				global $args, $c, $from;
				if(strtolower($message)==strtolower($config['username']).': trigcheck'){
				$dAmnPHP->say($p[0], ' Hello '.$from.', My trigger is '.$config['trigger']);
				return;
				}
				$args = explode(' ', $message);
				if (empty($config['trigger'])) {
					$trigger = $config['username'] . ': ';
				} else {
					$trigger = $config['trigger'];
				}
				if (substr($message, 0, strlen($trigger)) == $trigger) {
					$command = substr($message, strlen($config['trigger']));
					$args    = $arguments = explode(" ", $command);
					
					$argsX = array();
					foreach ($args as $n => $arg) {
						$x       = explode(" ", $command, $n + 1);
						$argsX[] = $x[count($x) - 1];
					}
					
					$commandname = strtolower($args[0]);
					unset($args[0]);
					unset($arguments[0]);
					unset($command);
					$argsF = $argumentsF = implode(" ", $arguments);
					$c     = $chatroom;
					$f     = $from . ": ";
					$tr    = $config['trigger'];
					echo($commandname);
					$this->checkCommand($commandname, $c, $from, $args);
				}
				
				console('[' . update($p[0]) . '] ' . (substr($data['event'], 5) == 'msg' ? '<' . $p[1] . '>' : '* ' . parse_tablumps($p[1])) . ' ' . $p[2], 'Core');
				break;

			case 'recv_join':
			case 'recv_part':
				$log = '[' . update($p[0]) . '] ** ' . $p[1] . ' has ' . (substr($data['event'], 5) == 'join' ? 'joined' : 'left') . (($data['event'] == 'recv_part' && $p[2] != false) ? ' [' . $p[2] . ']' : '');
				console($log, "Core");
				break;

			case 'recv_privchg':
			case 'recv_kicked':
				console('[' . update($p[0]) . '] ** ' . $p[1] . ' has been ' . (substr($data['event'], 5) == 'privchg' ? 'made a member of ' . $p[3] . ' by ' . $p[2] . ' *' : 'kicked by ' . $p[2] . ' *' . ($p[3] != false ? ' ' . $p[3] : '')), "Core");
				break;

			case 'recv_admin_create':
			case 'recv_admin_update':
				$log = '[' . update($p[0]) . '] ** privilege class ' . $p[3] . ' has been ' . substr($data['event'], 11) . 'd by ' . $p[2] . ' with: ' . $p[4];
				console($log, "Core");
				break;

			case 'recv_admin_rename':
				$log = '[' . update($p[0]) . '] ** privilege class ' . $p[3] . ' has been renamed to ' . $p[4] . ' by ' . $p[2];
				console($log, "Core");
				break;

			case 'recv_admin_move':
				$log = '[' . update($p[0]) . '] ** all members of ' . $p[3] . ' have been made ' . $p[4] . ' by ' . $p[2] . ' -- ' . $p[5] . ' members were affected';
				console($log, "Core");
				break;

			case 'recv_admin_remove':
				$log = '[' . update($p[0]) . '] ** privilege class ' . $p[3] . ' has been removed by ' . $p[2] . ' -- ' . $p[4] . ' members were affected';
				console($log, "Core");
				break;

			case 'recv_admin_show':
				break;

			case 'recv_admin_privclass':
				$log = '[' . update($p[0]) . '] ** admin ' . $p[1] . ' failed, error: ' . $p[2];
				if ($p[3] !== false)
					$log .= ' (' . $p[3] . ')';
				console($log, "Core");
				break;

			case 'kicked':
				global $dAmnPHP;
				$log = '[' . update($p[0]) . '] ** You have been kicked by ' . $p[1] . ' * | Rejoining..';
				$dAmnPHP->join($p[0]);
				if ($p[2] !== false)
					$log .= ' ' . $p[2];
				console($log, "Core");
				break;

			case 'ping':
				global $dAmnPHP;
				$dAmnPHP->send("pong\n\0");
				break;

			case 'disconnect':
				$log = 'Disconnected from dAmn [' . $p[0] . ']';
				console($log, "Core");
				break;

			case 'send':
			case 'kick':
			case 'get':
			case 'set':
				$log = ' ** ' . ucfirst($data['event']) . ' error: ' . ($p[2] != false ? $p[2] . ' (' . $p[1] . ')' : $p[1]);
				console($log, "Core");
				break;

			case 'kill':
				$log = 'Kill error: ' . $p[1] . ' (' . $p[2] . ')';
				
				console($log, "Core");
				break;

			case 'whois':
				break;

			case '':
				break;

			default:
				$log = 'Received unknown packet.';
				$log .= str_replace("\n", "\n>>", $raw);
				return;
				break;
				console($log, 'Core');
		}
	}

	function bot_() {
		global $dAmnPHP, $running;
		$data = $dAmnPHP->read();
		if (is_array($data)) {
			foreach ($data as $packet)
				$this->process_it($packet);
		}
	}

	function load_userinfo() {
			global $dAmnPHP, $config;
		load_config( 'botinfo' );
		console("Lets attempt to get connected. ", "Connection");
	
		if (!file_exists("./inc/certificate")) {
			$Cookie = $dAmnPHP->getCookie($config['username'], $config['password']);
			global $dAmn;
			$cookie = $Cookie = $dAmn->cookie;
			console("Got Cookie: " . $Cookie . ".", "Core");
			console("Stored Certificate! ", "Core");
			$filename = "./inc/certificate";
			$con      = fopen($filename, 'w');
			fputs($con, $Cookie);
			fclose($con);
		} else {
			$Cookie = @file_get_contents('./inc/certificate', TRUE);
			
			$running = true;
			console("Stored Cookie: " . $Cookie, "Core");
		}
		$num     = 1;
		// Lets get onto dAmn.
		$running = true;
		$dAmnPHP->connect();
		$dAmnPHP->login($config['username'], $Cookie);
			$dAmnPHP->join(deform($config['autojoin']));
		while ($running === true) {
			// While we are running we may as well let dAmnHandler do the real work.
			$this->bot_();
			// It's recommended you sleep for a while in the loop, so you don't use too much CPU.
			usleep(100000);
		}
		die('Disconnected!');
	}
}
$Crono = new Crono;
?>