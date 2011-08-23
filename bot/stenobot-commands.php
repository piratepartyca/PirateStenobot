<?php

/*

This file contains the functions that are used for interfacing with the bot via
the IRC interface. See catch_irc() and initialize() in stenobot-functions.php.

*/

function irc_command ($cmds, $user, $data, $depth = 0) {
	global $roles, $config;
	
	$command = explode(' ', $data, $depth + 2);
	
	foreach ($cmds as $cmd => $params) {
		if (($cmd == strtoupper($command[$depth])) || ($cmd == '')) {
			if (isset($params['children'])) {
				// recurse for multi-word commands
				return irc_command($params['children'], $user, $data, $depth + 1);
			} else {
				// see if the user can run this command at all
				if (!irc_permission_check($user, $params['allow']))
					return irc_permission_denied($user);
				
				// now check if the parameters are valid
				if (!isset($params['args'])) $params['args'] = array('');
				foreach ($params['args'] as $args) {
					// see if we have permissions to deal with
					if (strpos($args, ':') === false)
						$perms = '';
					else
						list($perms, $args) = explode(':', $args);
					
					// see if provided arguments match expectations
					logdata("Matching '$args'");
					if (($args == '') xor (count($command) < ($depth + 2))) continue;
					
					$testdata = $command[$depth+1] ? explode(' ', $command[$depth+1], strlen($args)) : array();
					
					if (($testdata[0] == '') && ($args != '')) { logdata('foo'); continue; }
					if (count($testdata) != strlen(rtrim($args))) continue;
					
					for ($i=0;$i<strlen($args);$i++) {
						switch ($args[$i]) {
						case '#': continue is_numeric($testdata[$i]) ? 2 : 3;
						case '&': if (($testdata[$i] = strtotime($testdata[$i])) === false) continue 3;
					}	}
					
					// check if the user is allowed to do this, if permissions are set
					if ($perms)
						if (!irc_permission_check($user, $perms))
							return irc_permission_denied($user, !irc_permission_check($user, $params['allow']), strtolower(implode(' ', array_slice($command, 0, $depth + 1))));
					
					// if execution gets this far, we have a winner!
					if ($cmd == '')
						$testdata[0] = $command[$depth] . ($testdata[0] ? " {$testdata[0]}" : '');
					
					return call_user_func($params['fn'], strtolower(implode(' ', array_slice($command, 0, $depth + (($cmd=='')?0:1)))), $testdata, $user);
				}
				
				say("Invalid arguments. For more information, type \"".chr(2)."/msg {$config['bots'][1]['nick']} HELP " . strtoupper(implode(' ', array_slice($command, 0, $depth + 1))).chr(2)."\".", $user);
				return;
}	}	}	}

function irc_permission_check($user, $perms) {
	global $users, $roles;
	
	if (!$perms) $perms = '*';
	$user = getuser($user);
	$log = "Permissions: $perms, matches for $user: ";
	$matches = "";
	$perms = str_split($perms);
	$allow = false;
	
	foreach ($perms as $perm) {
		$grant = ($perm == strtolower($perm));
		
		switch (strtolower($perm)) {
		case '*':	// all
			$allow = true;
			$matches .= "*";
			break;
		case 'a':	// authenticated
			if ($users[$user]['uid']) {
				$matches .= $perm;
				if ($grant)
					$allow = true;
				else
					return (bool) logdata($log . ($matches ? $matches : "none"));
			}
			break;
		case 'v':	// voice
		case 'h':	// hop
		case 'o':	// op
			if ($users[$user]['attributes'][strtolower($perm)]) {
				$matches .= $perm;
				if ($grant)
					$allow = true;
				else
					return (bool) logdata($log . ($allow ? '' : "none"));
			}
			break;
		case 'c':	// chair
		case 'l':	// clerk
		case 's':	// currently speaking
			if ($roles[(strtolower($perm)=='c')?'chair':((strtolower($perm)=='l')?'clerk':'speaking')] == $user) {
				$matches .= $perm;
				if ($grant)
					$allow = true;
				else
					return (bool) logdata($log . ($matches ? $matches : "none"));
			}
			break;
	}	}
	
	logdata($log . ($matches ? $matches : "none"));
	return $allow;
}

function irc_permission_denied($user, $argsonly = false, $command = '') {
	global $config;
	
	if ($argsonly)
		say("You don't have permission to run that command with the specified arguments. Type ".chr(2)."/msg {$config['bots'][1]['nick']} HELP $command".chr(2)." for documentation.", $user);
	else
		say("You don't have permission to do that. Type ".chr(2)."/msg {$config['bots'][1]['nick']} HELP $command".chr(2)." for documentation.", $user);
}


/*

All of the following functions, with the exception of irc_login, are called by
the same code, and so share the following arguments:

$cmd	the full text of the command run
$args	the arguments following the command
$user	the name of the user running the command

For example, the arguments will be called as follows for these commands:

:User1!User1@the.hostname.net PRIVMSG sb :SPEAKERS ADD User2 1
$cmd  == 'speakers add'
$args == array('User2', 1)
$user == 'User1'

:User3!User3@some.other.hostname.com PRIVMSG sb :Motion ADD User4 that some stuff happen
$cmd == 'motion add'
$args == array('User4', 'that some stuff happen')
$user == 'User3'

Permissions and fields as specified in stenobot-data.php are checked in
irc_command(), so minimal validation should be necessary in most cases.

*/

/* irc_meeting (...)
commands:	meeting start [time]
			meeting end
*/
function irc_meeting ($cmd, $args, $user) {
	global $stack, $users, $config;
	
	$start = $args[0] ? intval($args[0]) : time();
	
	if ($start < time())
		return say("Please enter a time in the future, or type ".chr(2)."/msg {$config['bots'][1]['nick']} MEETING START".chr(2)." to start right away.", $user);
	
	switch ($cmd) {
	case 'meeting start':
		if ($stack->size())
			return say("A meeting is already in progress.", $user);
		else
			return $stack->push(new meeting(array('start' => $start)));
	case 'meeting end':
		switch ($size = $stack->size()) {
		case 0:  return say("No meeting is currently in progress.", $user);
		case 1:  return $stack->clear();
		default: return say("All motions and votes must be concluded first. " . ($size-1) . " remain" . (($size==2) ? 's.' : '.'), $user);
}	}	}

/* irc_roles (...)
commands:	chair [username]
			clerk [username]
*/
function irc_roles ($cmd, $args, $user) {
	global $config, $roles, $stack, $users;
	
	if (!$args || (strtolower($args[0]) == strtolower($user))) {	// user self-designates
		if ($roles[$cmd] == $user) return say("You are already $cmd!", $user);
		if ($roles[$cmd]) say("You are no longer $cmd. $user has assumed the position.", $roles[$cmd]);
		say("You are now $cmd.", $user);
		if ($stack->size()) say("New $cmd: $user");
		$roles[$cmd] = $user;
	} else {	// user designates someone else
		if (strtolower($args[0]) == strtolower($config['bots'][0]['nick']))
			return say("I'm flattered, but I don't think I'd make a very good $cmd. Find someone human.", $user);
		
		$new = getuser($args[0]);
		if ($new === false) return say("I couldn't find a user named \"{$args[0]}\"!", $user);
		if ($roles[$cmd] == $new) return say("$new is already $cmd!", $user);
		
		// time to inform everyone what happened
		say("You have been appointed $cmd by $user.", $new);
		say("$new has been appointed $cmd.", $user);
		if ($roles[$cmd]) say("You are no longer $cmd. $user has appointed $new to the position.", $roles[$cmd]);
		if ($stack->size()) say("New $cmd: $new.");
		
		// chair needs ops and clerk needs hops
		if (($cmd == 'chair') && !$value['attributes']['o'])
			senddata("MODE {$config['channel']} +o $key");
		elseif (!$value['attributes']['h'] && !$value['attributes']['o'])
			senddata("MODE {$config['channel']} +h $key");
		
		$roles[$cmd] = $new;
	}
	
	record("$roles[$cmd] became $cmd.");
}

/* irc_list (...)
commands:	motion list
			speakers list
*/
function irc_list ($cmd, $args, $user) {
	global $speakers, $stack, $roles;
	
	switch ($cmd) {
	case 'motion list':
		$index = $stack->index();
		$say = array();
		
		foreach ($index as $item)
			if ($item['type'] == 'motion')
				$say[] = "{$item['owner']} moved {$item['name']}";
		
		if ($say) {
			$say[count($say)-1] = chr(2).$say[count($say)-1]." (active)".chr(2);
			say(chr(2)."Current motions (".count($say).")".chr(2)."\n".implode("\n", $say), $user);
		} else {
			say("There are no active motions.", $user);
		}
		
		break;
	case 'speakers list':
		$say = array();
		
		if ($roles['speaking'])
			$say[] = chr(2)."0  {$roles['speaking']} (currently speaking)".chr(2);
		if (is_array($speakers))
			foreach ($speakers as $pos => $speaker)
				$say[] = ($pos+1) . "  $speaker";
		
		if ($say)
			say(chr(2)."Current speakers list (".count($say).")".chr(2)."\n".implode("\n", $say), $user);
		else
			say("The speakers list is empty.", $user);
		
		break;
	}
}

/* irc_motion_add (...)
commands:	motion add
*/
function irc_motion_add ($cmd, $args, $user) {
	global $users, $stack, $roles, $speakers;
	list($mover, $motion) = $args;
	
	switch (get_class($stack->peek())) {
	case false:	return say("No meeting is currently in progress.", $user);
	default:	return say("You can't start a new motion right now.", $user);
	case 'meeting':
	case 'motion':
	}
	
	if ($roles['speaking'])
		irc_speakers_end('speakers end', array(), $user);
	if ($speakers)
		irc_speakers_update('speakers clear', array(), $user);
	
	if (getuser($mover) == false)
		return say("I couldn't find a user named \"$mover\"!", $user);
	
	$mover = getuser($mover);
	
	if (!$users[$mover]['uid'])
		return say("$mover isn't logged in.", $user);
	
	$stack->push(new motion(array('name' => $motion, 'owner' => $mover)));
}

/* irc_motion_end (...)
commands:	motion vote
			motion pubvote
			motion end
*/
function irc_motion_end ($cmd, $args, $user) {
	global $stack;
	
	if (!$stack->peek() instanceof motion)
		return say("There is no presently active motion to end.", $user);
	
	if ($cmd == 'motion end') {
		$motion = $stack->pop();
		
		say("Motion ended.", $user);
		say("Discussion on {$motion->owner}'s motion {$motion->name} has concluded.");
		record("The motion {$motion->name} concluded without a vote.");
		unset($motion);
	} else {
		say("Calling vote.", $user);
		$stack->push(new vote(array('owner' => $stack->peek()->owner, 'name' => $stack->peek()->name, 'announce' => ($cmd == 'motion vote'))));
	}
}

/* irc_motion_vote (...)
commands:	y
			n
			a
*/
function irc_motion_vote ($cmd, $args, $user) {
	global $stack, $users;
	
	if ($stack->peek() instanceof vote)
		$stack->peek()->castvote($users[$user]['uid'], $cmd);
	else
		say("No vote is currently in progress.", $user);
}

/* irc_motion_modify (...)
commands:	motion modify
*/
function irc_motion_modify ($cmd, $args, $user) {
	global $stack;
	
	if (!$stack->peek() instanceof motion)
		return say("There is no presently active motion to modify.", $user);
	
	$owner = $stack->peek()->owner;
	$oldname = $stack->peek()->name;
	$newname = $args[0];
	
	announce("Motion $oldname amended", "$owner's motion $oldname has been amended to read \"$newname\".");
	record("$owner's motion $oldname was amended to read \"$newname\".");
	$stack->peek()->name = $newname;
}

/* irc_speakers_toggle (...)
commands	speakers on
			speakers off
*/
function irc_speakers_toggle ($cmd, $args, $user) {
	global $speakers, $speakers_locked, $roles, $config, $stack;
	
	switch ($cmd) {
	case 'speakers on':
		if (($args[0] != '') && (strtolower($args[0]) != 'locked'))
			return say("Invalid command. Type \"".chr(2)."/msg {$config['bots'][1]['nick']} HELP ADVANCED SPEAKERS ON".chr(2)."\" for details.", $user);
		
		$config['speakers']['locked'] = (bool) $args;
		
		switch (get_class($stack->peek())) {
		case false:	return say("No meeting is currently in progress.", $user);
		default:	return say("You can't enable the speakers list right now.", $user);
		case 'meeting':
		case 'motion':
		}
		
		if ($speakers === null) {
			$speakers = array();
			
			if ($config['speakers']['voice'])
				voice(true);
			elseif ($roles['speaking'])
				voice($roles['speaking']);
			else
				voice(false);
			
			say("The speakers list is now active".($config['speakers']['locked']?" and locked.":"."), $user);
			say("The speakers list is now active. ".($config['speakers']['locked']?"The Chair is responsible for adding speakers to the list.":("If you would like to speak, type \"".chr(2)."/msg {$config['bots'][1]['nick']} SPEAKERS ADD".chr(2)."\".")));
			record("The speakers list was enabled.");
		} else {
			say("The speakers list is already active.", $user);
		}
		
		return;
	case 'speakers off':
		if ($speakers === null) {
			say("The speakers list is already disabled.", $user);
		} else {
			$speakers = null;
			say("The speakers list is now disabled.", $user);
			say("The speakers list is no longer active. It is no longer necessary to request to speak.");
			record("The speakers list was disabled.");
			voice(true);
		}
		
		return;
	}
}

/* irc_speakers_set (...)
commands	speakers set
*/
function irc_speakers_set ($cmd, $args, $user) {
	global $speakers, $roles, $stack, $config;
	
	$target = getuser($args[0]);
	
	if (!$stack->size())
		return say("You can't set a speech while no meeting is in progress.", $user);
	if ($target === false)
		return say("I couldn't find a user named {$args[0]}!", $user);
	if ($roles['speaking'])
		irc_speakers_end('speakers end', array(), $user);
	
	$roles['speaking'] = $target;
	
	say("{$roles['speaking']} now has the floor.");
	say("You now have the floor. If you would like to end your speaking period before your time has elapsed, type \"".chr(2)."/msg {$config['bots'][1]['nick']} SPEAKERS END".chr(2)."\".", $roles['speaking']);
	if ($roles['chair'] != $speaking) say("{$roles['speaking']} now has the floor." . (is_null($speakers) ? "" : (" ".count($speakers)." remaining in speakers list.")) . " The speaker can end the speech themselves, or you can do so with \"".chr(2)."/msg {$config['bots'][1]['nick']} SPEAKERS END".chr(2)."\".", $roles['chair']);
	record("{$roles['speaking']}'s speaking period began.");
	
	voice($config['speakers']['voice'] ? true : $roles['speaking']);
}

/* irc_speakers_update (...)
commands	speakers add [username [position]]
			speakers remove
			speakers clear
*/
function irc_speakers_update ($cmd, $args, $user) {
	global $speakers, $roles, $config;
	
	if (is_null($speakers)) return say("The speakers list is not currently active.".(($user==$roles['chair'])?(" To activate it, type \"".chr(2)."/msg {$config['bots'][1]['nick']} SPEAKERS ON".chr(2)."\"."):""), $user);
	
	switch ($cmd) {
	case 'speakers add':
		$target = $args ? getuser($args[0]) : $user;
		
		if ($target === false) {
			say("I couldn't find a user named {$args[0]}!", $user);
		} elseif (in_array($target, $speakers)) {
			say(($args ? "$target is" : "You're") . " already on the speakers list.", $user);
		} elseif ($config['speakers']['locked'] && ($user != $roles['chair'])) {
			say("The speakers list is currently locked. Speakers can only be added by the Chair.", $user);
		} elseif (!irc_permission_check($target, 'a')) {
			say(($args ? "$target is" : "You're") . " not eligible to speak (not logged in).", $user);
		} else {
			if (isset($args[1]) && ($args[1] < count($speakers)) && ($args[1] >= 0))
				array_splice($speakers, $args[1], 0, array($target));
			else
				$speakers[] = $target;
			
			say("You have been added to the speakers list".($args?" by $user":"").".", $target);
			if ($args) say("$target has been added to the speakers list.", $user);
		}
		
		return;
	case 'speakers remove':
		$target = getuser($args ? $args[0] : $user);
		$pos = array_search($target, $speakers);
		
		if ($target === false) {
			say("I couldn't find a user named {$args[0]}!", $user);
		} elseif ($pos === false) {
			say(($args ? "$target is" : "You are") . " not currently in the speakers list.", $user);
		} else {
			array_splice($speakers, $pos, 1);
			$count = count($speakers);
			
			say("You have been removed from the speakers list".(($target==$user)?".":" by $user."), $target);
			if ($args) say("$target removed from the speakers list. $count remaining.", $user);
		}
		
		return;
	case 'speakers clear':
		if (!$speakers)
			return say("There is currently no one in the speakers list.", $user);
		
		foreach ($speakers as $speaker)
			say("You have been cleared from the speakers list.", $speaker);
		
		$speakers = array();
		say("Speakers list cleared.", $user);
		say("The speakers list has been cleared.");
		return;
}	}

/* irc_speakers_end (...)
commands:	speakers end
			speakers next
*/
function irc_speakers_end ($cmd, $args, $user) {
	global $roles, $speakers, $config;
	$update = array();
	
	if ($roles['speaking']) {
		say("Your speaking period has concluded.", $roles['speaking']);
		if ($roles['chair'] != $user) say("{$roles['speaking']} has ended their speaking period.", $roles['chair']);
		elseif ($roles['chair'] != $roles['speaking']) say("{$roles['speaking']}'s speaking period ended.", $roles['chair']);
		record("{$roles['speaking']}'s speaking period ended.");
		$update[] = "{$roles['speaking']}'s speaking period has ended.";
		
		$roles['speaking'] = '';
	} elseif ($cmd == 'speakers end') {
		say("There is no one currently speaking.", $user);
	}
	
	if ($cmd == 'speakers next') {
		if (is_null($speakers)) {
			return say("The speakers list is not currently active.".(($user==$roles['chair'])?(" To activate it, type \"".chr(2)."/msg {$config['bots'][1]['nick']} SPEAKERS ON".chr(2)."\"."):""), $user);
		} elseif (count($speakers)) {
			$roles['speaking'] = array_shift($speakers);
			
			say("You now have the floor. If you would like to end your speaking period before your time has elapsed, type \"".chr(2)."/msg {$config['bots'][1]['nick']} SPEAKERS END".chr(2)."\".", $roles['speaking']);
			if ($roles['chair'] != $roles['speaking']) say("{$roles['speaking']} now has the floor. ".count($speakers)." remaining in speakers list.", $roles['chair']);
			if ($speakers) say("{$roles['speaking']} now has the floor. You're next on the speakers list.", $speakers[0]);
			record("{$roles['speaking']}'s speaking period began.");
			$update[] = "{$roles['speaking']} now has the floor.";
		} else {
			say("The speakers list is empty.", $user);
		}
	}
	
	if ($update) say(implode(' ', $update));
	
	if ($config['speakers']['voice'])
		voice(true);
	elseif ($roles['speaking'])
		voice($roles['speaking']);
	else
		voice(is_null($speakers));
}

/* irc_speakers_lock
commands:	speakers lock
			speakers unlock
*/
function irc_speakers_lock ($cmd, $args, $user) {
	global $config, $speakers;
	
	$lock = (strtolower($cmd) == 'speakers lock');
	$locked = $lock ? "locked" : "unlocked";
	
	if ($lock xor $config['speakers']['locked']) {
		if (!is_null($speakers)) say("The speakers list is now $locked. ".($lock?"Only the Chair may add speakers.":("If you would like to speak, type \"".chr(2)."/msg {$config['bots'][1]['nick']} SPEAKERS ADD".chr(2)."\".")));
		say("The speakers list is now $locked.", $user);
		$config['speakers']['locked'] = $lock;
	} else {
		say("The speakers list is already $locked.", $user);
	}
}

/* irc_speakers_voice
commands:	speakers voice
*/
function irc_speakers_voice ($cmd, $args, $user) {
	global $config, $roles, $stack;
	
	switch (strtolower($args[0])) {
	case 'all':	$voice = true; break;
	case 'one':	$voice = false; break;
	default:	return say("Invalid arguments. Type \"".chr(2)."/msg {$config['bots'][1]['nick']} HELP ADVANCED SPEAKERS VOICE".chr(2)."\" for details.", $user);
	}
	
	say("Voice management is ".(($voice xor $config['speakers']['voice'])?"now ":"already ").($voice?"off.":"on."), $user);
	$config['speakers']['voice'] = $voice;
	
	if (!is_object($stack->peek()))
		voice(false);
	elseif ($config['speakers']['voice'])
		voice(true);
	elseif ($roles['speaking'])
		voice($roles['speaking']);
	else
		voice(false);
}

/* irc_speakers_shuffle (...)
commands:	speakers shuffle
*/
function irc_speakers_shuffle ($cmd, $args, $user) {
	global $speakers;
	
	if ($args[0]) {
		$count = min(max(2, intval($args[0])), count($speakers));
		$shuffle = array_splice($speakers, 0, $count);
		shuffle($shuffle);
		$speakers = array_merge($shuffle, $speakers);
	} else {
		$count = count($speakers);
		shuffle($speakers);
	}
	
	say("$count speaker".(($count==1)?"":"s")." shuffled.", $user);
	irc_list('speakers list', array(), $user);
}

/* irc_help (...)
commands:	help
			[unrecognized commands]
*/
function irc_help ($cmd, $args, $user) {
	global $help, $config, $commands;
	
	$args = $args[0] ? explode(' ', $args[0]) : array();
	$entry = $help;
	$allow = $commands;
	
	foreach ($args as $arg) {
		$arg = strtoupper($arg);
		
		if (isset($entry['children'][$arg])) {
			$entry = $entry['children'][$arg];
			if (isset($allow[$arg]['children']))
				$allow = $allow[$arg]['children'];
			elseif (is_array($allow))
				$allow = strval($allow[$arg]['allow']);
			else
				$allow = '*';
			
		} elseif (isset($entry['children'][''])) {
			$entry = $entry['children'][''];
			break;
		} else {
//			var_dump($cmd, $args);
			say("There is no help entry on that subject. For more information, type ".chr(2)."/msg {$config['bots'][1]['nick']} HELP".chr(15).".", $user);
			return;
	}	}
	
	say(str_replace('_AVAILABLE_', chr(3) . (irc_permission_check($user, isset($entry['allow']) ? $entry['allow'] : $allow) ? '3available' : '4unavailable'), $entry['text']), $user);
}

/* irc_who (...)
commands:	who
*/
function irc_who($cmd, $args, $user) {
	global $roles;
	
	switch (true) {
	case $roles['chair'] && $roles['clerk']:
		return say("{$roles['chair']} is chair; {$roles['clerk']} is clerk.", $user);
	case $roles['chair']:
		return say("{$roles['chair']} is chair. There is presently no clerk.", $user);
	case $roles['clerk']:
		return say("{$roles['clerk']} is clerk. There is presently no chair.", $user);
	default:
		return say("There is presently neither chair nor clerk.", $user);
	}
}

/* irc_die (...)
commands:	die
*/
function irc_die($cmd, $args, $user) {
	say("You first!", $user);
	die();
}

/* irc_uptime (...)
commands:	uptime
*/
function irc_uptime($cmd, $args, $user) {
	global $uptime;
	
	say("Current uptime: " . duration(time() - $uptime, 3), $user);
}

/* irc_login ($cmd, $user)
commands:	ab2, 3cD (examples)
*/
function irc_login ($cmd, $user) {
	global $config, $roles, $stack, $users;
	
	if (!$stack->size() || (($stack->peek() instanceof meeting) && ($stack->peek()->start > time() + 3600))) {
		say("No meeting is presently in progress.", $user);
		return;
	} elseif (!isset($users[$user])) {
		say("You must be present {$config['channel']} before you can log in. To join, type " . chr(2) . "/join {$config['channel']}" . chr(2) . ".", $user);
		return;
	} elseif ($users[$user]['uid']) {
		say("You are already authenticated.", $user);
		return;
	}
	
	$res = mysql_query("SELECT uid FROM users WHERE passkey LIKE BINARY '".mres($cmd)."'");
	
	if (!@mysql_num_rows($res)) {
		say("Unknown passkey. Please visit {$config['loginurl']} to log in. If you have already done so, please ensure that you have correctly typed the key, including capitalization.", $user);
		return;
	}
	
	$row = mysql_fetch_assoc($res);
	
	// maybe someone is already using that account? naughty, naughty
	$prevuser = getuser($row['uid']);
	if ($prevuser !== false) {
		$users[$prevuser]['uid'] = 0;
		say("{$user} has connected using your member number. You have been logged out. If this is not you, your member number and PIN may have been compromised. Please contact a staff member for help, and log in again at {$config['loginurl']}.", $prevuser);
	}
	
	$users[$user]['uid'] = intval($row['uid']);
	
	mysql_query("DELETE FROM users WHERE uid = {$row['uid']}");
	
	voice();
	say("Thank you. You are now authenticated and can speak and vote in your turn.", $user);
	record("$user authenticated with ID {$row['uid']}.");
}

function fix() {
	global $users, $config, $roles;
	
	foreach ($users as $name => $user) {
		if (($name != $config['bots'][0]['nick']) && (!$user['uid'])) {
			$i++;
			$users[$name]['uid'] = $i;
		}
	}
	
	$roles['chair'] = 'MikkelPaulson';
	
	voice();
	logdata('Fixed!');
}

?>