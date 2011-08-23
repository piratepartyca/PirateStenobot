<?php

require_once('../config.php');

if (!date_default_timezone_set($_GET['timezone']))
	date_default_timezone_set('America/Toronto');

$show = in_array($_GET['show'], array('transcript','agenda','minutes','vote')) ? $_GET['show'] : 'list';
if ($show != 'list') $mid = (int) $_GET['mid'];
unset($_GET['show'], $_GET['mid']);

// get previous and next meeting IDs
if ($mid) {
	$res = mysql_query("SELECT uid, username, name, UNIX_TIMESTAMP(start) AS start, UNIX_TIMESTAMP(end) AS end, status, notes FROM meetings WHERE mid = $mid");
	if (@mysql_num_rows($res)) extract(mysql_fetch_assoc($res));
	else die("No meeting with ID $mid!");
	
	$res = mysql_query("SELECT (SELECT mid FROM meetings WHERE mid < $mid ORDER BY start DESC LIMIT 1), (SELECT mid FROM meetings WHERE mid > $mid ORDER BY start ASC LIMIT 1)");
	list($prev, $next) = @mysql_fetch_row($res);
}

require("index-$show.php");


// general functions

function formatuser($name, $type = 0) {
	switch ($type) {
	case 0:	// line label
		return "<p class='name " . userclass($name) . "'>{$name}</p>";
	case 1:	// mention
		return '';
	}
}

function getcolor($name) {
	mt_srand(hexdec(substr(md5(strtolower($name)), 0, 10)));	// seeding by username hash gives us the same colour each time
	return str_pad(dechex(mt_rand(0, 221)), 2, '0', STR_PAD_LEFT)	// R: 00-DD
		 . str_pad(dechex(mt_rand(0, 221)), 2, '0', STR_PAD_LEFT)	// G: 00-DD
		 . str_pad(dechex(mt_rand(0, 221)), 2, '0', STR_PAD_LEFT);	// B: 00-DD
}

function userclass($user) {
	return 'user_' . strtolower(substr($user, 0, strspn(strtolower($user), 'abcdefghijklmnopqrstuvwxyz1234567890-_')));
}

?>