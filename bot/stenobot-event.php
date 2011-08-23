<?php

abstract class event {
	public $id = 0;
	public $name = '';
	public $owner = '';
	public $start = 0;
	
	public $length = 0;
	public $end = 0;
	public $counter = 0;
	
	private $classname = '';
	
	function __construct($args) {
		// it's a lot more refined to unpack an array than trying to dump a ton of undocumented arguments at once
		// so new event(array('property' => 'value')) instead of new event('value')
		foreach ($args as $property => $value)
			$this->$property = $value;
		
		if (!$this->start)
			$this->start = time();
		
		if ($this->length)
			$this->end = $this->start + $this->length;
		
		$this->classname = get_class($this);
		
		$this->countdown(true);
		
		// maybe the extending class wants its own constructor. it's so cute.
		if (in_array('_construct', get_class_methods($this->classname)))
			call_user_func(array($this, '_construct'));
	}
	
	function __destruct() {
		if (in_array('_destruct', get_class_methods($this->classname)))
			call_user_func(array($this, '_destruct'));
		
		// magic: record class vote's properties in $votes (or whatever)
		$classname = $this->classname;
		$classname .= (substr($classname, -1) == 's') ? 'es' : 's';
//		$GLOBALS[$classname][] = get_object_vars($this);
	}
	
	function countdown($construct = false) {
		global $config;
		
		$countdown = array(//Used to find time increments in countdowns:
				604800 => 86400,// 1 week => 1 day
				86400 => 3600,	// 1 day => 1 hour
				3600 => 900,	// 1 hour => 15 minutes
				1800 => 600,	// 30 minutes => 10 minutes
				900 => 300,		// 15 minutes => 5 minutes
				600 => 300,		// 10 minutes => 5 minutes
				300 => 60,		// 5 minutes => 1 minute
				60 => 15,		// 1 minute => 15 seconds
				30 => 10,		// 30 seconds => 10 seconds
				15 => 5,		// 15 seconds => 5 seconds
				10 => 5,		// 10 seconds => 5 seconds
				5 => 0);		// 5 seconds => lunchtime!
		
		$type = $this->classname;
		
		if ($construct) {
			if ($type == 'meeting') {
				foreach (array_keys($countdown) as $start) {
					if ($start <= ($this->start - time())) {
						$this->counter = $start;
						return;
				}	}
			} else {
				foreach (array_keys($countdown) as $start) {
					if (($start * 3) <= $this->length) {
						$this->counter = $start;
						return;
			}	}	}
			
			$this->counter = 0;
			return;
		}
		
		if (time() >= (($type == 'meeting') ? $this->start : $this->end))
			return true;
		
		if (time() < (($type == 'meeting') ? ($this->start - $this->counter) : ($this->end - $this->counter)))
			return;
		
		$remaining = ($type == 'meeting') ? "until meeting" : "remaining in $type";
		
		if ($this->counter == 86400) {
			say("1 day $remaining.");
		} elseif ($this->counter == 3600) {
			say("1 hour $remaining.");
		} elseif ($this->counter > 60) {
			if (($this->counter <= 900) && ($type == 'meeting'))
				announce(($this->counter / 60) . " minutes $remaining", "Meeting begins in " . ($this->counter / 60) . " minutes. Please visit {$GLOBALS['config']['baseurl']}/login.php to log in.");
			else
				say(($this->counter / 60) . " minutes $remaining.");
		} elseif ($this->counter == 60) {
			if ($type == 'meeting') {
				announce("1 minute until meeting", "Meeting begins in 1 minute. If you have not yet logged in, please visit {$GLOBALS['config']['baseurl']}/login.php. All discussion for the duration of the meeting is being logged and will be made publicly available.");
			} else {
				say("1 minute $remaining.");
			}
		} elseif ($this->counter) {
			say("{$this->counter} seconds $remaining...");
		} else {
			return;
		}
		
		$this->counter = $countdown[$this->counter];
	}
}
?>
