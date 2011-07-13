<?php

class motion extends event {
	function _construct() {
		announce("Motion {$this->name}", "{$this->owner} has moved {$this->name}. A speaking period of up to 5 minutes is now available for the {$this->owner} to introduce the motion.");
		record("BEGIN: {$this->owner} moved {$this->name}.");
		irc_speakers_set('speakers set', array($this->owner), '');
	}
	
	function _destruct() {
		global $stack;
		
		record("END: {$this->owner} moved {$this->name}.");
		if (@in_array('resume', get_class_methods(get_class($stack->peek())))) $stack->peek()->resume();
	}
	
	function resume() {
		announce("Motion {$this->name}", "Discussion resumes on {$this->owner}'s motion {$this->name}.");
		say("Discussion on your motion {$this->name} has resumed.", $this->owner);
		record("RESUMED: {$this->owner} moved {$this->name}.");
	}
	
	function tick() {}
}

?>