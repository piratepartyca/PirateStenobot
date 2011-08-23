#!/usr/bin/php
<?php



  //======================//
 // Initialize variables //
//======================//

chdir(dirname(__FILE__));

require_once('../config.php');
require_once('stenobot-functions.php');

initialize();



  //=====================//
 // It's business time! //
//=====================//

// of note: while ('RIAA is evil') is about 9% faster than while (true)
// guess PHP just knows that it's truer than true...

while ('RIAA is evil') {
	if (tick())			continue;	// keep the various objects doing their thing
	
	if (catch_std())	continue;	// data from stdin
	if (catch_db())		continue;	// data from database
	if (catch_irc())	continue;	// data from IRC server
	
	usleep($config['sleeptime']);	// prevent the system from locking up if there's no data from any source
}

  //=====================================================================//
 // When you're with me you only need 2 minutes, 'cause I'm so INTENSE. //
//=====================================================================//

?>
