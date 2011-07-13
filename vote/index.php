<?php 

require_once('../config.php');

date_default_timezone_set($config['timezone']);

if (isset($_POST['wp-submit-en']))
	$lang = 'en';
elseif (isset($_POST['wp-submit-fr']))
	$lang = 'fr';
elseif ($_SERVER['SERVER_NAME'] == 'vote.pirateparty.ca')
	$lang = 'en';
elseif ($_SERVER['SERVER_NAME'] == 'vote.partipirate.ca')
	$lang = 'fr';
else
	$lang = '';

if ($_POST) {
	$uid = intval($_POST['uid']);
	
	if (strlen($_POST['pin']) == 40)
		$pin = $_POST['pin'];
	else
		$pin = sha1($_POST['pin']);
	
	// insert code to validate authentication here
	$valid = true;
	
	if ($valid) {
		if (isset($_POST['vote']))
			page('record');
		else
			page('vote');
	} else {
		page('login', ($lang=='fr')?"Votre num&eacute;ro de membre ou NIP ne sont pas valide. Veuillez r&eacute;essayer.":"Your member ID and PIN don't match, or you've made a typo. Please try again.");
	}
} else {
	page();
}

function page($page = '', $error = '') {
	global $lang;

?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" dir="ltr" lang="en-US">
<head>
	<title><?=($lang=='fr')?"Parti pirate du Canda: Voter":(($lang=='en')?"Pirate Party of Canada: Vote":"PPCA: Vote / Voter")?></title>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
	<link rel='stylesheet' href='style.css' type='text/css' media='all' />
	<link rel='stylesheet' href='list-style.css' type='text/css' media='all' />
	<meta name='robots' content='noindex,nofollow' />
</head>
<body>

<img src="logo.png" alt="Pirate Party of Canada / Parti Pirate du Canada" width="527" height="86" id="logo" />

<?php

	if (in_array($page, array('badlogin', 'vote', 'record', 'fail')))
		$func = "page_$page";
	else
		$func = "page_login";
	
	if ($error)
		$func($error);
	else
		$func();

?>
</body>
</html><?php

}

function page_login($error = '') {
	$fr = $GLOBALS['lang'] == 'fr';
?><div id="login">


<form name="loginform" id="content" action="/" method="post">
<?php if ($error) echo "<p style='font-weight: bold; color: red;'>$error</p>"; ?>
	<p>
		<label><?=$fr?"Num&eacute;ro d'adh&eacute;sion / Member ID":"Member ID / Num&eacute;ro d'adh&eacute;sion"?><br />
		<input type="text" name="uid" id="user_id" class="input" value="" size="20" tabindex="10" /></label>
	</p>
	<p>
		<label><?=$fr?"NIP / PIN":"PIN / NIP"?><br />
		<input type="password" name="pin" id="pin" class="input" value="" size="20" tabindex="20" /></label>
	</p>
	<p class="submit">
		<?=($_SERVER['SERVER_NAME']=='vote.partipirate.ca')?"<input type='submit' name='wp-submit-fr' id='wp-submit-fr' class='button-primary' value='Se connecter (fran&ccedil;ais)' tabindex='110' />
		<input type='submit' name='wp-submit-en' id='wp-submit-en' class='button-primary' value='Log in (English)' tabindex='100' />"
		
		:"<input type='submit' name='wp-submit-en' id='wp-submit-en' class='button-primary' value='Log in (English)' tabindex='100' />
		<input type='submit' name='wp-submit-fr' id='wp-submit-fr' class='button-primary' value='Se connecter (fran&ccedil;ais)' tabindex='110' />"?>
	</p>
	
	<p style="clear: both; font-style: italic; margin-top: 4em;">
		<?=$fr?"Si vous auriez besoin d'aide, veuillez contacter <a href='mailto:mikkel@pirateparty.ca'>mikkel@pirateparty.ca</a>.<br />If you experience any difficulties voting, please contact <a href='mailto:mikkel@pirateparty.ca'>mikkel@pirateparty.ca</a>":"If you experience any difficulties voting, please contact <a href='mailto:mikkel@pirateparty.ca'>mikkel@pirateparty.ca</a>.<br />Si vous auriez besoin d'aide, veuillez contacter <a href='mailto:mikkel@pirateparty.ca'>mikkel@pirateparty.ca</a>."?>
	</p>
</form>


</div>
<script type="text/javascript">
try{document.getElementById('user_id').focus();}catch(e){}
</script>
<?php

}

function page_vote($error = '') {
	global $lang, $uid, $pin;
	
	$fr = $GLOBALS['lang'] == 'fr';
	$res = mysql_query("SELECT * FROM motions WHERE status = 'voting' ORDER BY mid ASC, vid ASC");

?>

<form id="content" name="vote" method="post" action="/">

<?php if ($error) echo "<p style='font-weight: bold; color: red;'>$error</p>\n\n"; ?>
<h2><?=$fr?"Voter":"Vote"?></h2>

<p><?=$fr?"Les votes suivants sont maintenent disponsibles. Veuillez choisir <strong>O</strong> pour voter oui, <strong>N</strong> pour voter non, ou <strong>A</strong> pour abstenir. Il est possible de modifier votre vote jusqu'au fin de la p&eacute;riode de vote.":"The following motions are presently available for voting. Please cast your vote below by selecting <strong>Y</strong> for yes, <strong>N</strong> for no, or <strong>A</strong> to abstain. You may modify your vote at any time until voting closes."?></p>

<table id="motions">
<tr><th><?=$fr?"Nom":"Motion"?></th><th><?=$fr?'O':'Y'?></th><th>N</th><th>A</th></tr>
<?php

while ($row = mysql_fetch_assoc($res)) {
	$res2 = mysql_query("SELECT vote FROM votes WHERE uid = $uid AND vid = {$row['vid']}");
	list($vote) = @mysql_fetch_row($res2);
	if (!$vote) $vote = 'a';
	$checked[$vote] = "checked='checked' ";
	
	echo
"<tr>
	<td>".($row['link']?"<a href='{$row['link']}' target='_blank'>":"")."{$row['username']} moved {$row['name']}".($row['link']?"</a>":"")."</td>
	<td><input type='radio' id='motion_{$row['vid']}_y' name='motion_{$row['vid']}' value='y' {$checked['y']}/></td>
	<td><input type='radio' id='motion_{$row['vid']}_n' name='motion_{$row['vid']}' value='n' {$checked['n']}/></td>
	<td><input type='radio' id='motion_{$row['vid']}_a' name='motion_{$row['vid']}' value='a' {$checked['a']}/></td>
</tr>";
}

?>
</table>

<input type="hidden" name="uid" value="<?=$uid?>" />
<input type="hidden" name="pin" value="<?=$pin?>" />
<input type="hidden" name="wp-submit-<?=$lang?>" value="" />

<input type="submit" value="<?=$fr?"Voter":"Vote"?>" name="vote" id="votebutton" />

</form>

<?php

}

function page_record() {
	global $uid, $lang;
	$fr = $lang == 'fr';
	$success = null;
	
	// record any valid votes
	foreach ($_POST as $vid => $vote) {
		if ((substr($vid, 0, 7) != 'motion_') || !is_numeric(substr($vid, 7)) || !in_array($vote, array('y','n','a')))
			continue;
		
		$vid = intval(substr($vid, 7));
		
		$res = mysql_query("SELECT COUNT(*) FROM motions WHERE vid = $vid AND status = 'voting'");
		$row = @mysql_fetch_row($res);
		
		if ($row[0]) {
			$res = mysql_query("REPLACE INTO votes SET vote = '$vote', vid = $vid, uid = $uid");
			if ($res === false)
				$success = false;
			elseif ($success === null)
				$success = true;
		} else $success = false;
	}
	
	if (!$success) {
		page_vote($fr?"Une erreur est survenue lors de l'enregistrement de vote, veuillez r&eacute;essayer.":"There was an error recording one or more of your votes. Please try again.");
	} else {
		?>

<div id="content">

<h2><?=$fr?"Vote compl&eacute;t&eacute;":"Voting completed"?></h2>
<p><?=$fr?"Merci ; votre vote a &eacute;t&eacute; enregistr&eacute;.":"Thank you; your vote has been recorded."?></p>

</div>
<?php
	}
}

function page_fail($error) {
	echo "<div id='content'><p style='font-weight: bold; color: red;'>$error</p></div>";
}

?>
