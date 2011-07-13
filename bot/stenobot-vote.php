<?php

class vote extends event {
	// properties
	public $voters = array();
	public $votes = array('y'=>array(), 'n'=>array(), 'a'=>array());
	public $announce = true;
	
	function _construct() {
		global $config;
		
		$this->start = time();
		$this->length = 45;
		$this->end = time() + 45;
		$this->countdown(true);
		
		$b = chr(2);
		$bot = $config['bots'][1]['nick'];
		
		announce("Vote on motion {$this->name}", "{$this->owner} has moved {$this->name}. To vote yes, type {$b}/msg $bot Y{$b}; to vote no, {$b}/msg $bot N{$b}; to abstain, {$b}/msg $bot A{$b}. The voting period will conclude after {$this->length} seconds".($this->announce?".":", although results will not be announced until after the conclusion of the web and telephone voting period."));
		voice(false);
	}
	
	function tick() {
		if ($this->countdown()) {
			$GLOBALS['stack']->pop();
		}
	}
	
	function _destruct() {
		global $stack, $speakers, $config;
		
		// modify order so as to mask possible ID/vote key indicators
		sort($this->voters);
		sort($this->votes['y']);
		sort($this->votes['n']);
		sort($this->votes['a']);
		
		$voters = count($this->voters);
		$y = count($this->votes['y']);
		$n = count($this->votes['n']);
		$a = count($this->votes['a']);
		
		if ($this->announce) {
			$passed = $y > $n;
			
			announce("Motion {$this->name} ".($passed ? "passed" : "defeated"), 
"The vote on {$this->owner}'s motion {$this->name} has concluded with the following results:
Yes: $y".($y?(" (key: " . implode(', ', $this->votes['y']) . ")"):"")."
No: $n".($n?(" (key: " . implode(', ', $this->votes['n']) . ")"):"")."
Abs: $a".($a?(" (key: " . implode(', ', $this->votes['a']) . ")"):"")."
Votes cast: $voters".($voters?(" (member ID: " . implode(', ', $this->voters) . ")"):"")."
".chr(2).($passed ? "The motion {$this->name} passes." : "The motion {$this->name} is defeated.").chr(2));
			
			record("The motion {$this->name} " . ($passed ? "passed" : "was defeated")." with a vote of $y yes to $n no, with $a abstaining.");
		} else {
			announce("Vote on motion {$this->name} concluded", "The vote on {$this->owner}'s motion {$this->name} has concluded. Results will be available following the web and telephone voting period.");
			record("Voting on the motion {$this->name} concluded with the preliminary results: $voters vote(s) cast (IDs " . implode(', ', $this->voters) . "); $y yes, $n no, with $a abstaining.");
		}
		
		// time for this instance to die and take the motion with it
		$stack->pop();
		voice(is_null($speakers) || $config['speakers']['voice']);
	}
	
	function castvote($uid, $vote) {
		global $config;
		
		if (in_array($uid, $this->voters)) {
			if (getuser($uid))
				say("You have already voted in this motion. You may not change your vote.", getuser($uid));
			
			return false;
		}
		
		// generate a unique 5-digit key; chances of collision with 60 million possibilities are minute, but better to be safe
		do { $key = str_pad(base_convert(mt_rand(0, 60466175), 10, 36), 5, '0', STR_PAD_LEFT);
		} while (in_array($key, $this->votes['y']) || in_array($key, $this->votes['n']) || in_array($key, $this->votes['a']));
		
		$this->voters[] = $uid;
		$this->votes[$vote][] = $key;
		$b = chr(2);
		
		$vote = ($vote=='y')?"yes":(($vote=='n')?"no":"abstain");
		
		if (!getuser($uid)) {
			logdata(var_export($GLOBALS['users'], true));
			logdata("$uid $vote");
		} else {
			say("Your vote of {$b}{$vote}{$b} has been recorded. Your key is {$b}{$key}{$b}, and your user ID is {$b}{$uid}{$b}. For information on how this information can be used to verify the integrity of our voting process, type \"{$b}/msg {$config['bots'][1]['nick']} HELP VOTING{$b}\".", getuser($uid));
}	}	}

?>