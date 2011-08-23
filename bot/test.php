#!/usr/bin/php
<?php
$i = 0;
if (false && $i++) echo 'foo';
echo "$i\n";

die();

//require_once('stenobot-functions.php');

/* add a / to the beginning of the line to enable this block and comment the following block
for ($i=0;$i<=4;$i++)
	for ($j=0;$j<=200;$j++)
		echo chr(27)."[$i;{$j}m$i;$j ".chr(27)."[0m";
/*

	$termformats = array(
		'colors'  => array('black'    => 30,  'red'         => 31,  'green'     => 32,  'yellow'     => 33,
		                   'blue'     => 34,  'magenta'     => 35,  'cyan'      => 36,  'gray'       => 37,
		                   'dkgray'   => 90,  'ltred'       => 91,  'ltgreen'   => 92,  'ltyellow'   => 93,
		                   'ltblue'   => 94,  'ltmagenta'   => 95,  'ltcyan'    => 96,  'ltgray'     => 97,
		                   
		                   'bgblack'  => 7,   'bgred'       => 41,  'bggreen'   => 42,  'bgyellow'   => 43,
		                   'bgblue'   => 44,  'bgmagenta'   => 45,  'bgcyan'    => 46,  'bggray'     => 47,
		                   'bgdkgray' => 100, 'bgltred'     => 101, 'bgltgreen' => 102, 'bgltyellow' => 103,
		                   'bgltblue' => 104, 'bgltmagenta' => 105, 'bgltcyan'  => 106, 'bgltgray'   => 107),
		'formats' => array('normal'   => 0,   'bold'        => 1,   'underline' => 4));

foreach ($termformats['colors'] as $color => $value)
	foreach ($termformats['formats'] as $format => $value)
		echo termcolor("$color $format ", $color, $format);
//*/

?>
