<?php

class meeting extends event {
	// properties
	public $status = 'scheduled';
	public $procedure = array();
	public $started = false;
	
	function _construct() {
		voice(true);
		
		if ($this->start == time()) return;
		
		$interval = $this->start - time();
		$say = "Meeting begins at " . date('h:i a T', $this->start);
		
		if (date('Y-m-d') != date('Y-m-d', $this->start)) {
			$intdays = (strtotime(date('Y-m-d', $this->start)) - strtotime(date('Y-m-d'))) / 86400;
			
			if ($intdays == 1)
				$say .= " tomorrow";
			elseif ($intdays < 7)
				$say .= " on " . date('l', $this->start);
			elseif (date('Y', $this->start) == date('Y'))
				$say .= " on " . date('F jS', $this->start);
			else
				$say .= " on " . date('F jS, Y', $this->start);
		}
		
		$say .= " (in " . duration($interval) . ").";
		say($say);
	}
	
	/* TICK()
	Called to trigger the countdown process and initialize the event.
	*/
	function tick() {
		global $stack, $config;
		
		if ($this->status == 'scheduled') {
			if ($this->countdown()) {
				announce("This meeting is called to order", "All discussion until the conclusion of the meeting is being logged and will be made publicly available on the Pirate Party of Canada's website. This software is currently under development, so please message any feedback to MikkelPaulson or email mikkel@pirateparty.ca. If you have not yet logged in, please do so at {$config['baseurl']}/login.php.");
				record('The meeting was called to order.');
				$GLOBALS['logging'] = true;
				$GLOBALS['mid'] = $this->id;
				$this->status = 'started';
				
				moderate(true);
			}
/*		} else {
			if (!$this->procedure) {
				unset($this);
				return;
			}
			
			$event = array_shift($this->procedure);
			
			// add a new object to the stack of a type corresponding to the next order of business
			switch ($event) {
			case 'opening discussion':
			case 'closing discussion':
				$stack->push(new speech(array(
						'name' => ucfirst($event),
						'length' => (($event == 'opening discussion') ? 60 : 600),
						'uid' => 0)));
				break;
			default:
				$res = mysql_query("SELECT uid, name FROM motions WHERE vid = ".intval($event));
				$row = mysql_fetch_assoc($res);
				
				$stack->push(new motion(array(
						'id' => $event,
						'name' => $row['name'],
						'uid' => $row['uid'])));
				break;
	}*/	}	}
	
	/* _DESTRUCT()
	Called by __destruct() in event when the object is unset.
	*/
	function _destruct() {
		global $motions, $recesses, $users, $speakers, $roles;
		$this->end = time();
		$this->length = $this->end - $this->start;
		$GLOBALS['logging'] = false;
		$GLOBALS['mid'] = 0;
		$GLOBALS['speakers'] = null;
		
		moderate(false);
		voice(false);
		
		announce("Meeting adjourned", "This meeting stands adjourned. The transcript will be available online shortly.");
		record("The meeting stood adjourned.");
		
		$roles = array();
		$speakers = array();
		
/*		mysql_query("UPDATE meetings (\"" . mres($this->owner) . "\", {$this->start}, {$this->end}, \"" . mres($this->name) . "\")");
		$mid = mysql_insert_id();
		
		foreach ($motions as $motion) {
			mysql_query("INSERT INTO motions (mid, username, start, question, yea, nay, abstain, passed) VALUES ($mid, \"" . mres($motion['owner']) . "\", {$motion['start']}, \"" . mres($motion['name']) . "\", {$motion['yea']['count']}, {$motion['nay']['count']}, {$motion['abstain']['count']}, {$motion['passed']})");
			$vid = mysql_insert_id();
			
			$query = '';
			
			foreach ($motion['yea']['public'] as $public)
				$query .= " ($vid, \"" . mres($public) . "\", \"y\")";
			foreach ($motion['nay']['public'] as $public)
				$query .= " ($vid, \"" . mres($public) . "\", \"n\")";
			foreach ($motion['abstain']['public'] as $public)
				$query .= " ($vid, \"" . mres($public) . "\", \"a\")";
			
			if ($query)
				mysql_query("INSERT INTO votes (vid, username, vote) VALUES{$query}");
		}
		
		unset($motions);
		
		foreach ($recesses as $recess)
			mysql_query("INSERT INTO recesses (mid, start, length, reason, username) VALUES ({$this->id}, {$recess['start']}, " . ($recess['length'] / 60) . ", \"" . mres($recess['name']) . "\", \"" . mres($recess['owner']) . "\")");
		
		unset($recesses);
		
		foreach ($users as $user => $uid)
			mysql_query("INSERT INTO attendees (mid, uid, username) VALUES ({$this->id}, $uid, \"" . mres($user) . "\")");
		unset($users);*/
}	}

?>