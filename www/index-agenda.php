<?php

// ** for testing purposes only ** //
$uid = 2208;
$username = 'Mikkel';

if ($_GET) {
	$vid = (int) $_GET['vid'];
	$action = $_GET['action'];
	
	$res = mysql_query("SELECT status FROM motions WHERE mid = $mid AND vid = $vid");
	list($status) = mysql_fetch_row($res);
	
	switch ($action) {
	case 'discuss':
		if ($status == 'scheduled' || $status == 'suspended')
			mysql_query("INSERT INTO events (mid, vid, uid, username, event_key, event_value) VALUES ($mid, $vid, $uid, '" . mysql_real_escape_string($username) . "', 'action', 'discuss')");
		
		break;
		
	case 'vote':
		if ($status == 'suspended' || $status == 'discussing' || $_GET['vid'] === '0')
			mysql_query("INSERT INTO events (mid, vid, uid, username, event_key, event_value) VALUES ($mid, $vid, $uid, '" . mysql_real_escape_string($username) . "', 'action', 'vote')");
		
		break;
	}
	
	header("Location: ?show=agenda&mid=$mid&security=obscurity");
	die();
}

$users = array();
$res = mysql_query("SELECT username FROM motions WHERE mid = $mid GROUP BY username");
while (list($username) = mysql_fetch_row($res))
	$users[$username] = getcolor($username);

?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml"><head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
	<title>Agenda: <?=$title?> on <?=date('F jS, Y', $start)?></title>
	
	<link type="text/css" rel="stylesheet" href="style.css" />
	
	<style type="text/css">
	/* dynamically-generated colours for each active user */
<? foreach ($users as $user => $color) { ?>
	.<?=userclass($user)?> { color: #<?=$color?>; }
<? } ?>
	</style>
</head>
<body>

<h1>General Meeting</h1>

<p id="date"><?=date('l, F jS, T', $start)?><br />
<?=date('g:i a',$start).($end?date(' - g:i a',$end):'').date(' T',$start)?></p>

<p style="font-weight: bold;">All major motions should be solidified at least one week in advance to allow members to review the agenda and vote in advance if desired.</p>

<ul id="log">

<?php

$res = mysql_query("SELECT vid, uid, username, name, status, yea, nay, abstain, link FROM motions WHERE mid = $mid ORDER BY start ASC");
$adjourn = 0;

do {
	$row = @mysql_fetch_assoc($res);
	
	if (!$row['name'] && !$adjourn) {
		if (!$row) {
			$row['username'] = 'Mikkel';
			$row['status'] = 'scheduled';
		}
		
		$row['name'] = 'that the meeting stand adjourned';
		
		if ($row['status'] != 'defeated')
			$adjourn = 1;
	} elseif ($adjourn == 1) {
		$adjourn = 2;
	}
	
	echo "
<li class='motion {$row['status']}'>
	<div class='bar'></div>
	<span class='title'><span class='".userclass($row['username'])."'>".$row['username']."</span> ".(($row['status']=='scheduled'||$row['status']=='proposed')?'to move':'moved')." ".($row['link']?"<a href='{$row['link']}' target='_blank'>":'').htmlspecialchars($row['name']).($row['link']?"</a>":'').".</span>
	";
	
	switch ($row['status']) {
	case 'proposed':
		echo "<br /><em>Proposed by {$row2['username']} on " . date('Y-m-d g:i:s a e', $row2['eventtime']) . "</em>";
		break;
	
	case 'scheduled':
		if ($adjourn == 1)	// adjournment isn't discussed, only voted upon
			echo "<br /><em>Scheduled</em> &ndash; <a href='meeting.php?mid=$mid&vid={$row['vid']}&action=vote'>call vote</a>";
		else				// normal motions are discussed prior to calling a vote
			echo "<br /><em>Scheduled</em> &ndash; <a href='meeting.php?mid=$mid&vid={$row['vid']}&action=discuss'>open discussion</a>";
		
		break;
	case 'discussing':
		echo "<br /><em>Discussing</em> &ndash; <a href='meeting.php?mid=$mid&vid={$row['vid']}&action=vote'>call vote</a>";
		break;
	case 'suspended':
		echo "<br /><em>Suspended</em> &ndash; <a href='meeting.php?mid=$mid&vid={$row['vid']}&action=discuss'>resume discussion</a>&nbsp;<a href='meeting.php?mid=$mid&vid={$row['vid']}&action=vote'>call vote</a>";
		break;
	case 'voting':
		echo "<br /><em>Voting in progress</em>";
		break;
	case 'passed':
		echo "<br /><em>Passed: {$row['yea']} yea, {$row['nay']} nay, {$row['abstain']} abstained</em>";
		break;
	case 'defeated':
		echo "<br /><em>Defeated: {$row['yea']} yea, {$row['nay']} nay, {$row['abstain']} abstained</em>";
	}
	
	echo "\n</li>";
} while ($row && !$adjourn);

?>

</ul>

<div class='clear'></div>
<div id='navbar'>


<div style='left: -9.5em; width: 8.5em; text-align: right; float: left; margin-right: 1em;'>
	<a href='./'>&laquo; Back to index</a></div>
	<div style='float: right; margin-right: 1em;'><?=($prev?"<a href='?show=agenda&mid=$prev'>&laquo; Previous meeting</a>":'').(($prev && $next)?" &bull; ":'').($next?"<a href='?show=agenda&mid=$next'>Next meeting &raquo;</a>":'')?></div>
</div>

</body>
</html>