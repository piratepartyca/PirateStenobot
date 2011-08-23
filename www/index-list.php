<?php

?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" dir="ltr" lang="en-US">
<head profile="http://gmpg.org/xfn/11">
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
	<title>Meetings of the Pirate Party of Canada</title>
	<link rel='stylesheet' href='list-style.css' type='text/css' media='all' />
</head>
<body>

<h1><img src="logo.png" alt="Pirate Party of Canada / Parti Pirate du Canada" width="633" height="104" id="logo" /></h1>

<ul>
<?php

$res = mysql_query("SELECT mid, uid, username, name, UNIX_TIMESTAMP(start) AS start, status FROM meetings ORDER BY start DESC");
$year = '';
$day = '';

while ($row = mysql_fetch_assoc($res)) {
	if ($year != date('Y', $row['start'])) {
		$year = date('Y', $row['start']);
		echo "

<li class='date'>$year</li>
<li class='clear'></li>";
	}
	
	$res2 = mysql_query("SELECT status, COUNT(vid) AS count FROM motions WHERE mid = {$row['mid']} GROUP BY status");
	$motions = array();
	while ($row2 = @mysql_fetch_assoc($res2))
		$motions[$row2['status']] = $row2['count'];
	
	if ($day != date('F j', $row['start'])) {
		$day = date('F j', $row['start']);
		
		echo "
<li class='date'>".date('F j', $row['start'])."</li>";
	}
	
	echo "
<li class='title'>".htmlspecialchars($row['name'])."</li>";
	
	if (($row['status'] == 'scheduled') && ($row['start'] >= time() - 604800))
		echo "
<li class='subtitle'><a href='?show=vote&mid={$row['mid']}' style='color: #ca0000;'>vote now</a></li>";
//	else
//		echo "
//<li class='subtitle'>agenda &bull; minutes &bull; transcript (currently offline)</li>";
	
	echo "
<li class='subtitle'>Motions: ";
/*<li class='subtitle'>"."<a href='?show=agenda&mid={$row['mid']}'>agenda</a> &bull; ".(($row['status']=='scheduled')?"minutes &bull; transcript":"<a href='?show=minutes&mid={$row['mid']}'>minutes</a> &bull; <a href='?show=transcript&mid={$row['mid']}'>transcript</a>")."</li>*/
	
	if (!$motions)
		echo 'none';
	else {
		// we want to output the motions in a logical order, which means using illogical code to get it
		$motionlist = array();
		foreach (array('passed','defeated','voting','discussing','suspended','scheduled','proposed') AS $motion)
			if ($motions[$motion])
				$motionlist[] = "{$motions[$motion]} $motion";
		
		echo implode(', ', $motionlist);
	}
	
	echo "</li>
<li class='clear'></li>
";


}

?>

</ul>

</body>
</html>