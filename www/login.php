<?php 

require_once('../config.php');

if ($_POST) {
	$uid = intval($_POST['uid']);
	$pin = intval($_POST['pin']);
	
	// insert code to validate UID and PIN with database here
	$valid = true;

	if ($valid) {
		$res = mysql_query("SELECT password, passkey FROM users WHERE uid = $uid");
		if (@mysql_num_rows($res))
			$row = mysql_fetch_assoc($res);
		
		if ($password && ($row['password'] != sha1($password, true)))
			$valid = false;
		
		if (!$row['passkey'] && $valid) {
			// leaving out some characters that are easily confused with one another (O/0 etc.)
			$letters = 'abcdefghijkmnopqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ';
			$numbers = '123456789';
			$chars = $letters.$numbers;
			
			// passkeys should have at least one letter and one number
			do {
				$passkey = str_shuffle($chars[mt_rand(0, 57)] . $letters[mt_rand(0, 48)] . $numbers[mt_rand(0, 8)]);
				$res = mysql_query("SELECT 1 FROM users WHERE passkey = '$passkey'");
			} while (@mysql_num_rows($res));
			
			$res = mysql_query("UPDATE users SET passkey = '$passkey' WHERE uid = $uid");
			if (!@mysql_affected_rows($res))
				mysql_query("INSERT INTO users (uid, passkey) VALUES ($uid, '$passkey')");
		} else
			$passkey = $row['passkey'];
	}
}

?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" dir="ltr" lang="en-US">
<head>
	<title>Pirate Party of Canada : Log In</title>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
	<link rel='stylesheet' href='login.css' type='text/css' media='all' />
	<link rel='stylesheet' href='list-style.css' type='text/css' media='all' />
	<meta name='robots' content='noindex,nofollow' />
</head>
<body>

<img src="logo.png" alt="Pirate Party of Canada / Parti Pirate du Canada" width="527" height="86" id="logo" />

<?php

if ($_POST && $valid) {

?>

<p>Thank you. You may now vote in motions during the meeting. Please type the following command (case-sensitive) into your chat client: <tt>/msg sb <?=$passkey?></tt></p>

<?php

} else {

if ($valid === false) {?>

<p>Your member ID and PIN don't match, or you've made a typo. Please try again.</p>

<? } ?>


<div id="login">


<form name="loginform" id="loginform" action="login.php" method="post">
	<p>
		<label>Member ID # (check your email)<br />
		<input type="text" name="uid" id="user_id" class="input" value="" size="20" tabindex="10" /></label>
	</p>
	<p>
		<label>PIN (check your email)<br />
		<input type="password" name="pin" id="pin" class="input" value="" size="20" tabindex="20" /></label>
	</p>
	<p class="submit">
		<input type="submit" name="wp-submit" id="wp-submit" class="button-primary" value="Log In" tabindex="100" />
	</p>
</form>


</div>

<? } ?>

<script type="text/javascript">
try{document.getElementById('user_name').focus();}catch(e){}
</script>
</body>
</html>
