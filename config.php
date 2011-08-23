<?php

$config = array(
	'db' => array( // database login creds
		'server' => 'localhost',
		'user' => '[username]',
		'pass' => '[password]',
		'database' => 'stenobot'),
	'server' => 'irc.freenode.net',
	'port' => 6667,
	'ssl' => false,
	'bots' => array(
		0 => array(
			'nick' => 'Stenobot',
			'name' => 'responsible for logging and tallying meetings',
			'pass' => ''),
		1 => array(
			'nick' => 'sb',
			'name' => 'a helper for Stenobot for interacting with users',
			'pass' => '')),
	'channel' => '#stenobot',
	'quitmsg' => 'I\'m a little T-bot!',
	'sleeptime' => 200000,
	'baseurl' => 'http://www.example.com', // no trailing slash
	'logfile' => 'log.txt',
	'timezone' => 'America/Toronto',
	'readline' => in_array('readline', get_loaded_extensions()), // readline is a nicer way to take command-line input
	'speakers' => array('voice' => false)); // default values for the speakers list

/* AUTHENTICATE($uid, $pin)
Everyone has a different interface for ensuring that people are who they say they are.
*/
function authenticate($uid, $pin) {
	return true;
}

// save a bit of time since we usually want to connect to the database while we're at it
// we can set $noconnect = true before requiring this file if we don't want to connect
if ($noconnect !== true) {
	$res = mysql_connect($config['db']['server'], $config['db']['user'], $config['db']['pass']);
	if ($res !== false) $res = mysql_select_db($config['db']['database']);
	if ($res === false) die('Database error!');
}

?>
