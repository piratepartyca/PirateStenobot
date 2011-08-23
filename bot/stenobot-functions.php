<?php


  //========================//
 // The Big Four functions //
//========================//


/* CATCH_STD()
Checks stdin for anything of interest: commands to be passed directly to the IRC
server and PHP commands to be eval()'d. What else could std stand for? Dirty
monkey.
*/
function catch_std($rawdata = null) {
	global $config;
	
	if (!$config['readline']) {
		$rawdata = fgets(STDIN);
	} elseif (is_null($rawdata)) {
	    $w = NULL;
	    $e = NULL;
	    $n = stream_select($r = array(STDIN), $w, $e, 0, 0);
	    
	    if ($n && in_array(STDIN, $r)) {
	        // read a character, will call the callback when a newline is entered
	        readline_callback_read_char();
	        return true;
	    } else {
	    	return false;
	    }
	}
	
	if (!$rawdata) return false;	// no data
	
	if (is_numeric($rawdata[0]))	// commands preceded by numerals are interpreted as raw commands for one of the bots
		senddata(substr($rawdata, 2), intval($rawdata[0]));
	else {							// otherwise, they're run as PHP commands, useful for h4xx0ring in real time
		extract($GLOBALS);			// might want to reference some global variables
		eval($rawdata);
	}
	
	if ($config['readline'])
		readline_add_history($rawdata);
	
	return true;
}


/* CATCH_DB()
When the web UI wants attention, it passes a trigger via the database table
`triggers`.
*/
function catch_db() {
	static $lastquery;
	
	// Avoid pummeling the database with queries...
	if (($lastquery + 0.5) < microtime(true))
		$lastquery = microtime(true);
	else
		return false;
	
	$res = mysql_query("SELECT r.eid, r.vid, r.uid, r.username, r.time, r.event_key, r.event_value FROM triggers AS r ORDER BY time ASC LIMIT 1");
	if (!@mysql_num_rows($res)) return false;	// no data
	return false;
	$row = @mysql_fetch_assoc($res);
	mysql_query("DELETE FROM triggers WHERE rid = {$row['r.rid']}");
	
	if ($row['e.vid']) {
		$res = mysql_query("SELECT v.uid, v.username, v.question FROM motions AS v WHERE v.vid = {$row['r.vid']}");
		$row += mysql_fetch_assoc($res);
	}
	
	logdata("Web UI  " . str_replace("\n", " ", var_export($row, true)));
	
	if ($row['event_key'] == 'action') {
		switch ($row['event_value']) {
		case 'discuss':
			$res = mysql_query("INSERT INTO minutes (mid, vid, uid, username, eventtime, event) VALUES (0, {$row['r.vid']}, {$row['r.uid']}, '" . mres($row['r.username']) . "', NOW(), 'discussing')");
		case 'vote':
			if ($row['e.vid']) {	// regular motion
				$event = new motion($row['v.username'], $row['v.question'], false);
			} else {				// motion to adjourn
				$event = new motion($row['e.username'], 'the meeting stand adjourned', true);
			}
			
			break;
		}
	}
}


/* CATCH_IRC()
Otherwise, it's time to grab data sent to the IRC bots. This is a beast.
*/
function catch_irc() {
	global $config, $sockets, $users, $allusers, $stack, $commands;
	
	foreach ($sockets as $bot => $socket)
		if ($rawtext = trim(@fgets($socket)))
			break;
	
	if (!$rawtext) return false;	// no data
	
	// some commands are necessary for all bots in order to ensure a smooth client-server interaction
	
	// Sample:
	// 0 => :MikkelPaulson!~mikkel@irc.pirateparty.ca
	// 1 => PRIVMSG
	// 2 => #test
	// 3 => :What's
	// 4 => up,
	// 5 => folks?
	$rawdata = explode(' ', $rawtext);
	
	// Keep server connection alive.
	if ($rawdata[0] == 'PING') {
		senddata("PONG {$rawdata[1]}", $bot, false);
		return true;
	}
	
	if ($rawdata[0] != ":{$config['server']}")
		logdata($rawtext, "R$bot");
	
	$data = array();
	
	// Messages from people start with :[username]!...
	if ($rawdata[0][0] == ':') {
		list($data['subject']) = explode('!', substr(array_shift($rawdata), 1), 2);
	}
	
	$data['verb'] = array_shift($rawdata);
	$data['object'] = ltrim(array_shift($rawdata), ':');
	
	// Sample:
	// 'subject' => 'MikkelPaulson'
	// 'verb' => 'PRIVMSG'
	// 'object' => '#test'
	
	// some responses, mostly authentication stuff, are common to all bots
	switch ($data['verb']) {
	case '376':		// ":End of /MOTD command". Time to authenticate.
		say("identify {$config['bots'][$bot]['pass']}", "NickServ", $bot, false, false);
		logdata("[authenticated]", "S{$bot}");
		if ($bot == 0) senddata("JOIN {$config['channel']}", $bot);
		return true;
	case '401':		// no such nick/channel - in case someone left and we didn't notice
		unset($users[$rawdata[0]]);
		return true;
	}
	
	switch ($bot) {
	case 0:	// the primary bot, responsible only for in-channel communication
		// some more commands exclusively for in-channel communication
		switch ($data['verb']) {
		case '353':		// = #channel :names
			unset($rawdata[0], $rawdata[1]);
			$rawdata[2] = ltrim($rawdata[2], ':');
			
			foreach ($rawdata as $user) {
				$attributes = array();
				
				while (true) {
					switch ($user[0]) {
					default:
						break 2;
					case '+':	// voice
						$attributes['v'] = true; break;
					case '%':	// hop
						$attributes['h'] = true; break;
					case '@':	// op
					case '~':	// owner
						$attributes['o'] = true; break;
					case '&':	// sop
						$attributes['a'] = true; break;
					}
					
					$user = substr($user, 1);
				}
				
				$users[$user] = array('attributes' => $attributes, 'joined' => time());
			}
			
			break;
		case 'MODE':	// #channel -x user
			if (count($rawdata) == 1) break;
			
			$addmode = ($rawdata[0][0] == '+');
			$modes = substr(array_shift($rawdata), 1);
			
			foreach ($rawdata as $key => $user)
				$users[$user]['attributes'][$modes[$key]] = $addmode;
			
			break;
		case 'JOIN':	// :#channel
			if ($data['subject'] == $config['bots'][0]['nick']) break;
			
			switch (@get_class($stack->peek())) {
			case 'meeting': if ($stack->peek()->counter >= 900) break;
			default: say("Welcome to the Pirate Party of Canada meeting channel. Please visit {$config['baseurl']}/login.php to log in, or type " . chr(2) . "/msg {$config['bots'][1]['nick']} HELP LOGIN" . chr(2) . " for help.", $data['subject'], 1, false);
			case false:
			}
			
			$users[$data['subject']] = array('joined' => time());
			break;
		case 'PART':
		case 'QUIT':
			$allusers[$data['subject']] = $users[$data['subject']];
			$allusers[$data['subject']]['left'] = time();
			
			unset($users[$data['subject']]);
			break;
		case 'KICK':
			if (strtolower($rawdata[0]) == strtolower($config['bots'][0]['nick']))
				senddata("JOIN {$config['channel']}");
			else {
				$allusers[$rawdata[0]] = $users[$rawdata[0]];
				$allusers[$rawdata[0]]['left'] = time();
				
				unset($users[$rawdata[0]]);
			}
			
			break;
		case 'NICK':
			if (isset($users[$data['subject']])) {
				$users[$data['object']] = $users[$data['subject']];
				unset($users[$data['subject']]);
			}
			break;
		case 'PRIVMSG':
			// Bot 0 doesn't care about out-of-channel traffic.
			if ($data['object'][0] != '#')
				return say(ucfirst($config['bots'][0]['nick']) . " doesn't accept direct commands. Please talk to me (".chr(2)."/msg {$config['bots'][1]['nick']}".chr(2).") instead.", $data['subject']) || true;
		case 'NOTICE':
			// we should only log if the person talking was actually allowed to talk
			if ($users[$data['subject']]['attributes']['v'] && $GLOBALS['logging'])
				transcribe($GLOBALS['mid'], $data['subject'], substr(implode(' ', $rawdata), 1), $data['verb'] == 'NOTICE');
		}
		
		break;
	case 1:	// the secondary bot, which speaks directly to users
			// most of this code is contained in stenobot-commands.php
		if ($data['verb'] != 'PRIVMSG') break;
		
		$rawdata[0] = substr($rawdata[0], 1);
		
		// deal with authentication directly
		if (preg_match('/[a-z]/i', $rawdata[0]) && preg_match('/[0-9]/', $rawdata[0]) && (strlen($rawdata[0]) == 3)) {
			irc_login($rawdata[0], $data['subject']);
			break;
		}
		
		irc_command($commands, $data['subject'], implode(' ', $rawdata));
	}
	
	return true;
}


/* TICK()
Responsible for maintaining activity among currently active objects.
*/
function tick() {
	global $stack;
	if (is_object($stack))
		return $stack->tick();
}


/* INITIALIZE()
Responsible for opening socket connections, initializing variables, and such.
*/
function initialize() {
	global $config, $stack, $sockets, $logfile, $commands, $roles, $uptime;
	
	$uptime = time();
	
	register_shutdown_function('shutdown');
	date_default_timezone_set($config['timezone']);
	
	require_once('stenobot-data.php');
	require_once('stenobot-commands.php');
	
	$logfile = fopen($config['logfile'], 'a');
	fputs($logfile, "\n\n------------------------- Launched: " . date('Y-m-d H:i:s T') . " -------------------------\n");
	
	$stack = new stack();
	$sockets = $roles = array();
	
	foreach ($config['bots'] as $id => $bot) {
		$i = 0;
		do {
			if ($i) sleep(5);
			$sockets[$id] = fsockopen(($config['ssl'] ? 'ssl://' : 'tcp://') . $config['server'], $config['port']);
		} while (($sockets[$id] === false) && ($i++ < 10));
		
		stream_set_blocking($sockets[$id], 0);
		senddata("USER {$bot['nick']} {$config['server']} {$bot['nick']} :{$bot['name']}\nNICK {$bot['nick']}", $id);
	}
	
	stream_set_blocking(STDIN, 0);
	
	if ($config['readline']) {
		readline_callback_handler_install('', 'catch_std');
		readline_completion_function('autocomplete');
	}
}



  //======================//
 // Abstracted functions //
//======================//


/* VOICE($target)
Handles voicing/de-voicing users on demand, to keep discussion nice and orderly.

$target		true		voice all privileged users; returns true
			false		de-voice all users; returns true
			[username]	voice only named user; returns true on success and false if username doesn't exist or isn't authenticated
			[array]		runs given voice commands in sequence until one succeeds; returns successful index or null if all fail
			null/unset	repeat last-run voice command; returns command
$test		true		used to determine the results of a voice command without actually changing permissions
			false/unset	commits changes

Returns true on success and false on failure, unless the command is called with an array, in which case it returns the working key on success and null on failure.
*/
function voice($target = null, $test = false) {
	global $users, $config;
	static $oldtarget;
	
	$to_voice = $to_unvoice = array();
	
	if (is_bool($target)) {			// voice all or none
		foreach ($users as $user => $info) {
			if (!$info['uid']) {
				if ($info['attributes']['v']) $to_unvoice[] = $user;
			} elseif ($info['attributes']['v'] != $target) {
				if ($target)	$to_voice[] = $user;
				else			$to_unvoice[] = $user;
		}	}
	} elseif (is_string($target)) {	// voice by username
		if (!$users[$target]['uid']) return false;
		
		foreach ($users as $user => $info) {
			if (!$info['uid']) {
				if ($info['attributes']['v']) $to_unvoice[] = $user;
				continue;
			}
			
			if (($user == $target) && !$info['attributes']['v'])
				$to_voice[] = $user;
			elseif (($user != $target) && $info['attributes']['v'])
				$to_unvoice[] = $user;
		}
	} elseif (is_array($target)) {	// specify a list of commands to try
		foreach ($target as $key => $command)
			if (voice($command, $test))
				return $key;
		
		return null;
	} elseif (is_null($target)) {	// repeat previous voice command
		if (!is_null($oldtarget))
			voice($oldtarget, $test);
		
		return $oldtarget;
	}
	
	$command = '';
	
	$to_voice = array_chunk($to_voice, 10);
	$to_unvoice = array_chunk($to_unvoice, 10);
	
	foreach ($to_voice as $line)
		$command .= "\nMODE {$config['channel']} +" . str_repeat('v', count($line)) . " " . implode(' ', $line);
	
	foreach ($to_unvoice as $line)
		$command .= "\nMODE {$config['channel']} -" . str_repeat('v', count($line)) . " " . implode(' ', $line);
	
	$command = trim($command);
	if ($command && !$test) senddata($command);
	
	if (!$test) $oldtarget = $target;
	
	return true;
}

/* GETUSER($uid)
Searches $users for a given member ID.

$uid	The member ID to search for.

Returns the username with that ID, or false if none found.
*/
function getuser($id) {
	global $users;
	
	foreach ($users as $user => $data)
		if ((is_string($id) && (strtolower($user) == strtolower($id))) ||
				(is_integer($id) && ($data['uid'] == $id)))
			return $user;
	
	return false;
}

/* COUNTUSERS()
Returns the number of signed-in users.
*/
function countusers() {
	global $users;
	
	return count(array_filter($users, '_countusers_callback'));
}
function _countusers_callback($a) { return $a['uid']; }

/* AUTOCOMPLETE($cmd)
Provides CLI tabbed completion with readline_completion_function() if available.

$cmd	The incomplete command to find matches for.

Returns an array of possibilities.
*/
function autocomplete($cmd) {
	global $users;
	$return = array();
	
	$legalchars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789[]{}\|^`-_';
	$rootlen = strspn(strrev($cmd), $legalchars);
	if (!$rootlen) return array();
	
	$root = substr($cmd, 0-$rootlen, $rootlen);
	
	foreach (array_keys($users) as $user)
		if (strtolower(substr($user, 0, $rootlen)) == strtolower($root))
			$return[] = $root . substr($user, $rootlen);
	
	return $return;
}


/* MODERATE($begin)
Enables or disables channel moderation.

$begin	true	Enable moderation
		false	Disable moderation
		null	Return status
*/
function moderate($begin = null) {
	global $config;
	static $moderated;
	
	if (is_null($begin))
		return $moderated;
	
	senddata("MODE {$config['channel']} " . ($begin ? '+m' : '-m'));
	$moderated = $begin;
}


/* RECORD($event)
Tells the clerk about an event (if applicable) and logs it to the CLI. Helpful
in taking minutes.
*/
function record($event) {
	global $roles;
	
	if ($roles['clerk']) say(date('H:i:s: ') . $event, $roles['clerk'], null, false);
	logdata($event, '##');
}

/* TERMCOLOR($text, $color = 'NORMAL')
Colourizes terminal output. Nifty! Credit to Glen Cooper and Joel Dagen.

$text	The text to be colourized
$color	The color we're going to use
*/
function termcolor($text, $color = 'normal', $format = 'normal') {
	$termformats = array(
		'colors'  => array('black'    => 30,  'red'         => 31,  'green'     => 32,  'yellow'     => 33,
		                   'blue'     => 34,  'magenta'     => 35,  'cyan'      => 36,  'gray'       => 37,
		                   'dkgray'   => 90,  'ltred'       => 91,  'ltgreen'   => 92,  'ltyellow'   => 93,
		                   'ltblue'   => 94,  'ltmagenta'   => 95,  'ltcyan'    => 96,  'ltgray'     => 97,
		                   
		                   'bgblack'  => 7,   'bgred'       => 41,  'bggreen'   => 42,  'bgyellow'   => 43,
		                   'bgblue'   => 44,  'bgmagenta'   => 45,  'bgcyan'    => 46,  'bggray'     => 47,
		                   'bgdkgray' => 100, 'bgltred'     => 101, 'bgltgreen' => 102, 'bgltyellow' => 103,
		                   'bgltblue' => 104, 'bgltmagenta' => 105, 'bgltcyan'  => 106, 'bgltgray'   => 107),
		'formats' => array('normal'   => 0,   'bold'        => 1,   'underline' => 4));

	return chr(27)."[{$termformats['formats'][$format]};{$termformats['colors'][$color]}m{$text}".chr(27)."[0m";
}


/* __AUTOLOAD($class)
Called automagically to dynamically load necessary classes. Nifty!

$class	The name of the class to load
*/
function __autoload($class) {
	require_once("stenobot-event.php");		// most classes extend event
	require_once("stenobot-$class.php");
}


/* SHUTDOWN()
Make the shutdown process a bit cleaner if possible by posting a quit message and closing sockets.
*/
function shutdown() {
	global $sockets, $config, $logfile, $stack, $uptime;
	
	logdata('Kill command received. Waiting 3 seconds to allow stack to clear gracefully...');
	
	if (is_object($stack))
		$stack->clear();
	
	sleep(3);
	
	if (is_array($sockets)) {
		foreach ($sockets as $id => $socket) {
			senddata("QUIT :{$config['quitmsg']}", $id);
			@fclose($socket);
		}
	}
	
	@fclose($logfile);
	
	logdata("Uptime: " . duration(time() - $uptime, 3) . ". Way to break that, loser.");
	logdata("Alas! Alack!");
	
//	var_dump($GLOBALS);
}



  //====================================================================//
 // Low-level ubiquitous functions for directly interfacing with stuff //
//====================================================================//


/* SAY($message, $recipient = '', $bot = null, $notice = true)
Sends a message or notice to a specified user, or to the channel at large.

$message	The message to send
$recipient	Who the message is being sent to; this can be a username or a channel
$bot		null	Defaults to 0 if the $recipient is blank or a channel, 1 otherwise
			(int)	The ID of the bot from which to send
$notice		true	Sends the message via NOTICE
			false	Sends the message via PRIVMSG (/msg)
$log		true	Record the action to the log file and the command line
			false	Do not record
*/
function say($message, $recipient = '', $bot = null, $notice = true, $log = true) {
	global $config;
	
	if (!$recipient) $recipient = $config['channel'];
	if (is_null($bot)) $bot = ($recipient[0] == '#') ? 0 : 1;
	
	$message = wordwrap($message, 400, "\n", true); // IRC chokes on long lines
	$prefix = ($notice ? 'NOTICE' : 'PRIVMSG') . ' ' . $recipient . ' :';
	senddata($prefix . str_replace("\n", "\n$prefix", $message), $bot, $log);
}


/* ANNOUNCE
Issues a pretty-looking announcement, typically about important stuff.

$message	The header of the message, or body if $message2 is unset.
$message2	If set, forms the body and $message the head.
$recipient	| See variable notes for
$bot		| the say() function.
*/
function announce($title, $message = '', $recipient = '', $bot = null) {
	if ($message == '') { $message = $title; $title = ''; }
	
	$title = str_repeat('=', max(30 - ceil((strlen($title) + 2) / 2.8), 3)) . ' ' . strtoupper($title) . ' ' . str_repeat('=', max(30 - floor((strlen($title) + 2) / 2.8), 3));
	
	say("$title\n$message\n" . str_repeat('=', 60), $recipient, $bot);
}


/* TRANSCRIBE($mid, $sender, $text)
Write text to the `transcripts` table in the database.

$mid	The ID of the current meeting
$sender	The sender of the message
$text	The text to transcribe
*/
function transcribe($mid, $sender, $text) {
	mysql_query("INSERT INTO transcripts (mid, username, sent, message) VALUES ($mid, '".mres($sender)."', NOW(), '".mres($text)."')");
}


/* SENDDATA($data, $bot = 0)
Sends raw data to the IRC server.

$data	The data to send
$bot	The bot ID to send it from
$log	Should the sent data be logged? Usually this should be true.
*/
function senddata($data, $bot = 0, $log = true) {
	global $sockets;
	$data = trim($data);
	if (@fwrite($sockets[$bot], "$data\n") !== false)
		$prefix = "S$bot";
	else
		$prefix = "F$bot";
	
	if ($log) logdata($data, $prefix);
}


/* LOGDATA($data, $die = false)
Output data to the log and terminal. Triggered via ob_start() in initialize().

$data	The data to log
*/
function logdata($data, $prefix = '  ') {
	global $logfile, $config;
	static $last;
	
	if ($last < floor(time() / 86400)) {
		$last = floor(time() / 86400);
		fwrite($logfile, "------------------------------------- " . date('Y-m-d') . " ------------------------------------\n");
		echo "------------------------------------- " . date('Y-m-d') . " ------------------------------------\n";
	}
	
	$fileprefix = $prefix . date(' H:i:s  ');
	fwrite($logfile, $fileprefix . str_replace("\n", "\n$fileprefix", $data) . "\n");
	
	switch ($prefix[0]) {
	case 'R': $color1 = 'bgltblue';		break; // Received
	case 'S': $color1 = 'bgltgreen';	break; // Sent
	case 'F': $color1 = 'bgred';		break; // Failed
	case '#': $color1 = 'bgltmagenta';	break; // minutes
	default:  $color1 = 'bgblack';		break;
	}
	
	switch ($prefix[1]) {
	case '0': $color2 = 'dkgray';	break; // from bot: Stenobot
	case '1': $color2 = 'black';	break; // from bot: sb
	case '#': $color2 = 'ltmagenta';break; // minutes
	default:  $color2 = 'bgblack';	break;
	}
	
	$termprefix = termcolor($prefix . date(' H:i:s'), $color1);
	$data = explode("\n", trim($data, "\n"));
	foreach ($data as $key => $line) $data[$key] = $termprefix . termcolor(" $line", $color2, ($prefix[1] == '#') ? 'bold' : 'normal');
	$data = implode("\n", $data);
	
	echo "$data\n";
}


/* DURATION($secs)
Converts a number of seconds into a human-readable time representation

$secs	The duration in seconds
$max	The maximum number of periods to show (3 indicates 1 day 2 hours 3 minutes)
*/
function duration($secs, $max = 2) {
	$return = array();
	
	if ($secs >= 86400) {
		$i = floor($secs / 86400);
		$return[] = "$i day".($i==1?'':'s');
		$secs -= $i * 86400;
	}
	if (($secs >= 3600) && (count($return) < $max)) {
		$i = floor($secs / 3600);
		$return[] = "$i hour".($i==1?'':'s');
		$secs -= $i * 3600;
	}
	if (($secs >= 60) && (count($return) < $max)) {
		$i = floor($secs / 60);
		$return[] = "$i minute".($i==1?'':'s');
		$secs -= $i * 60;
	}
	if ($secs && (count($return) < $max))
		$return[] = "$secs second".($secs==1?'':'s');
	
	switch (count($return)) {
	case 0:	return null;
	case 1:	return $return[0];
	case 2:	return "{$return[0]} and {$return[1]}";
	default:
		$returnstr = '';
		
		while (count($return) > 1)
			$returnstr .= array_shift($return) . ', ';
		return $returnstr . "and {$return[0]}";
	}
}


/* MRES($string)
Shorthand for mysql_real_escape_string()

$string	The string to escape
*/
function mres($string) {
	return mysql_real_escape_string($string);
}

?>
