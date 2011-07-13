<?php

//if ()

$users = array();
$res = mysql_query("SELECT username FROM attendees WHERE mid = $mid");
while (list($username) = @mysql_fetch_row($res)) {
	$attendees[] = $username;
	$users[$username] = getcolor($username);
}

if ($attendees) natcasesort($attendees);

$res = mysql_query("SELECT username FROM transcripts WHERE mid = $mid GROUP BY username");		// find a list of unique users
while (list($username) = mysql_fetch_row($res))
	$users[$username] = getcolor($username);

?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" dir="ltr" lang="en-US">
<head profile="http://gmpg.org/xfn/11">
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
	<title>Transcript: <?=$title?> on <?=date('F jS, Y', $start)?></title>
	<!--[if lt IE 8]>
	<script src="http://ie7-js.googlecode.com/svn/version/2.1(beta3)/IE8.js"></script>
	<![endif]-->
	<link type="text/css" rel="stylesheet" href="style.css" />
	<style type="text/css">
	/* dynamically-generated colours for each active user */
<? foreach ($users as $user => $color) { ?>
	.<?=userclass($user)?> { color: #<?=$color?>; }
<? } ?>
	</style>
	
	<!--[if lt IE 8]>
	<style type="text/css">#log li p.name {top: 0;}</style>
	<![endif]-->
</head>
<body>

<h1><?=htmlspecialchars($name)?></h1>

<p id="date"><?=date('l, F jS, Y', $start)?><br />
<?=date('g:i a',$start).($end?(' &ndash; '.date('g:i a T',$end)):'')?></p>


<ul id="attendees">
	<li>Attendees:</li>
<?php if (!$attendees) {
	?><em>none</em><?
} else {
	foreach ($attendees as $attendee) { ?>
	<li class="<?=userclass($attendee)?>"><?=$attendee?></li>
<? } } ?>
</ul>

<ul id="log">

<?php

$res = mysql_query("SELECT vid, uid, username, name, UNIX_TIMESTAMP(start) AS start, status, yea AS y, nay AS n, abstain AS a, link FROM motions WHERE mid = $mid ORDER BY start ASC");
$motions = array();
while ($row = mysql_fetch_assoc($res)) $motions[] = $row;

$res = mysql_query("SELECT uid, username, UNIX_TIMESTAMP(sent) AS sent, message FROM transcripts WHERE mid = $mid ORDER BY sent ASC, tid ASC");
$ticktime = 0;
$prevname = '';
$times = array();
$finishedmotions = array();

// make an array to do a case-insensitive regexp search for users later on
function regex1($a){return"/(\b$a\b)/ie";}
function regex2($a){return"/(\b$a\b)/";}
function regex3($a){return"<acronym title='$a'>\\1</acronym>";}

$users_pattern = array_map("regex1", array_keys($users));

$acronyms_pattern = array_map("regex2", array('PPCA', 'USPP', 'PPI', 'ACTA', 'EC', 'NPO'));
$acronyms_replacement = array_map("regex3", array('Pirate Party of Canada', 'United States Pirate Party', 'Pirate Party International', 'Anti-Counterfeiting Trade Agreement', 'Elections Canada', 'Non-Profit Organization'));

while ($row = mysql_fetch_assoc($res)) {
	if ($motions[0]['start'] < $row['sent'])
		printmotion(array_shift($motions));
	
	if ($row['sent'] >= ($ticktime + 300)) {
		$ticktime = floor($row['sent'] / 300) * 300;
		if ($prevname) echo "</li>";
		
		echo "\n\n<li class='timestamp' id='" . date('gi', $ticktime) . "'>" . date('g:i a', $ticktime) . "";
		$times[] = date('gi', $ticktime);
		$ticktime += 300;
		$prevname = '';
	}
	
	$row['message'] = htmlspecialchars($row['message']);
	$row['message'] = preg_replace($users_pattern, "'<span class=\'mention '.userclass($1).'\'>$1</span>'", $row['message']);	// highlight mentions
	$row['message'] = preg_replace($acronyms_pattern, $acronyms_replacement, $row['message']);
	$row['message'] = preg_replace("#(([a-zA-Z]+://)([a-zA-Z0-9?&%.,;:/=+_-]*))#", "<a href='$1' target='_blank'>$1</a>", $row['message']);	// linkify URLs
	if (substr(trim($row['message']), 1, 7) == 'ACTION ') {
		$row['message'] = '*' . trim(substr($row['message'], 7)) . '*';
	}
	
	if ($prevname != $row['username']) {
		echo "</li>

<li>" . formatuser($row['username']) . $row['message'];
	} else
		echo "<br />\n" . $row['message'];
	$prevname = $row['username'];
}

foreach ($motions as $motion)
	printmotion($motion);

$times[] = date('gi', $end);

echo "</li>
<li class='motion' id='" .date('gi', $end) . "'><p>The meeting adjourned at " . date('g:i a e', $end) . ".</p></li>

</ul>

<div class='clear'></div>
<div id='navbar'>


<div style='left: -9.5em; width: 8.5em; text-align: right; float: left; margin-right: 1em;'>
	<a href='./'>&laquo; Back to index</a></div>

<div style='float: right; margin-right: 1em;'>" . ($prev ? "<a href='?show=transcript&mid=$prev'>&laquo; Previous meeting</a>" : "") . (($prev && $next) ? " &bull; " : "") . ($next ? "<a href='?show=transcript&mid=$next'>Next meeting &raquo;</a>" : "") . "</div>

	Motions:";

$i = 0;

foreach ($finishedmotions as $motion) {
	$i++;
	
	echo "
	<a href='#motion{$i}' class='motion_" . (($motion['status'] == 'passed') ? 'passed' : 'failed') . "' title='{$motion['username']} moved to {$motion['question']}'>$i</a>";
}

echo "

	&bull; Times:";

for ($i=0;$i<count($times);$i++) {
	if (substr($times[$i], 0, strlen($times[$i]) - 2) != substr($times[$i-1], 0, strlen($times[$i-1]) - 2)) echo "
	<strong>" . substr($times[$i], 0, strlen($times[$i]) - 2) . "</strong>";
	
	echo "
	<a href='#{$times[$i]}'>:" . substr($times[$i], -2) . "</a>";
}
?>

</div>
</body>
</html><?php

function printmotion($motion) {
	global $finishedmotions;
	
	$res2 = mysql_query("SELECT username, vote FROM votes WHERE vid = {$motion['vid']} ORDER BY vote DESC");
	$pubvotes = array(
			'y'=>array(),
			'n'=>array(),
			'a'=>array());
	
	while ($row2 = mysql_fetch_assoc($res2)) {
		$pubvotes[$row2['vote']][] = $row2['username'];
	}
	
	natcasesort($pubvotes['y']);
	natcasesort($pubvotes['n']);
	natcasesort($pubvotes['a']);
	
	if (!$motion['name']) $motion['name'] = 'that the meeting stand adjourned';
	
	echo "</li>
<li class='clear'></li>

<li class='motion {$motion['status']}' id='motion" . (count($finishedmotions) + 1) . "'>
	<div class='bar'></div>
	<p><span class='".userclass($motion['username'])."'>{$motion['username']}</span> moved ".htmlspecialchars($motion['name']).".</p>";
	
	foreach (array('y','n','a') as $v) {
		echo "
	<div class='votes'>
		<em>" . (($v == 'y') ? "In favour" : (($v == 'n') ? "Opposed" : "Abstained")) . ": {$motion[$v]}</em>";
		
		foreach ($pubvotes[$v] as $vote) {
			echo "<br />
		<span class='" . userclass($vote) . "'>$vote</span>";
		}
		
		if ($motion[$v] > count($pubvotes[$v])) echo "<br />
		<em>" . ($motion[$v] - count($pubvotes[$v])) . " anonymous</em>";
		
		echo "
	</div>";
	}
	
	echo "
	<p>The motion ".(($motion['status']=='passed')?"passed":"was defeated").".</p>
";
	
	$finishedmotions[] = $motion;
}

?>