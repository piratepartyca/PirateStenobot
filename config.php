<?php

$config = array(
	'db' => array(					// database login creds
		'server' => 'localhost',		// currently only used for authentication hook
		'user' => 'username',
		'pass' => 'password',
		'database' => 'stenobot'),
	'server' => 'irc.example.com',			// IRC server name or IP
	'port' => 6667,					// normal ports: 6667 for unencrypted TCP, 6697 with SSL
	'ssl' => false,					// whether or not to connect using ssl
	'bots' => array(				// uses two accounts to avoid being slowed for flooding
		0 => array(				// main bot active in the channel
			'nick' => 'Stenobot',
			'name' => 'responsible for logging and tallying meetings',
			'pass' => 'password'),
		1 => array(				// bot helper, /msg only
			'nick' => 'sb',
			'name' => 'a helper for Stenobot for interacting with users',
			'pass' => 'password')),
	'channel' => '#meeting',			// channel in which the bot should operate
	'quitmsg' => 'I\'m a little T-bot!',		// sent when the bot exits
	'sleeptime' => 200000,				// you shouldn't need to change this
	'baseurl' => 'https://meetings.example.com',	// no trailing slash
	'logfile' => 'log.txt',
	'timezone' => 'America/Toronto',
	'readline' => in_array('readline', get_loaded_extensions()),
							// readline is a nicer way to take command-line input
							// should automatically detect if it's usable
	'speakers' => array('voice' => false));		// default values for the speakers list

// save a bit of time since we usually want to connect to the database while we're at it
// we can set $noconnect = true before requiring this file if we don't want to connect
if ($noconnect !== true) {
	$res = mysql_connect($config['db']['server'], $config['db']['user'], $config['db']['pass']);
	if ($res !== false) $res = mysql_select_db($config['db']['database']);
	if ($res === false) die('Database error!');
}

?>
