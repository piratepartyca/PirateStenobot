<?php

$res = mysql_query("SELECT vid AS id, username AS owner, name, UNIX_TIMESTAMP(start) AS start, status, yea, nay, abstain, dnv FROM motions WHERE vid = " . intval($_GET['vid']) . " AND (status = 'passed' OR status = 'defeated') AND secret = 0");

if (!mysql_num_rows($res)) die();
$vote = mysql_fetch_object($res);

$res = mysql_query("SELECT vote, vote_key FROM vote_keys WHERE vid = {$vote->id} ORDER BY vote_key ASC");
$votes = array('y'=>array(), 'n'=>array(), 'a'=>array());

while ($row = @mysql_fetch_object($res))
	$votes[$row->vote][] = $row->vote_key;

?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" dir="ltr" lang="en-US">
<head profile="http://gmpg.org/xfn/11">
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
	<title>Vote: <?=$vote->owner?> moved <?=$vote->name?> on <?=date('F jS, Y', $vote->start)?></title>
	<link rel='stylesheet' href='list-style.css' type='text/css' media='all' />
</head>
<body>

<h1><img src="logo.png" alt="Pirate Party of Canada / Parti Pirate du Canada" width="633" height="104" id="logo" /></h1>

<h2><?=$vote->owner?> moved <?=$vote->name?></h2>
<p class="subtitle"><?=date('F jS, Y \a\t g:i a T', $vote->start)?></p>

<ul class="vote_results">
<li>Yes: <?=$vote->yea?> <?=$votes['y'] ? ("(" . implode(', ', $votes['y']) . ")") : ''?></li>
<li>No: <?=$vote->nay?> <?=$votes['n'] ? ("(" . implode(', ', $votes['n']) . ")") : ''?></li>
<li>Abstaining: <?=$vote->abstain?> <?=$votes['a'] ? ("(" . implode(', ', $votes['a']) . ")") : ''?></li>
<li>Total votes: <?=$vote->yea + $vote->nay + $vote->abstain?></li>
<li>Did not vote: <?=$vote->dnv?></li>
</ul>

</body>
</html>