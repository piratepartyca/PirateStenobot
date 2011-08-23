<?php

/* $commands
this array lists all recognized IRC commands & permissions.
parameters:
fn		name of function to call (ALL CAPS)
allow	permissions (lowercase to grant, uppercase to deny):
	*	all (default)
	a	authenticated
	v	+v (voiced)
	h	+h (half op)
	o	+o (op) & +q (founder)
	c	Chair
	l	Clerk
	s	currently-speaking user
children subcommands (if applicable); default '' (if specified) should appear at the end
args	expected arguments, or a list of possibilities; per-argument permissions can be set with perm:arg; should be ordered most to least specific, regardless of permissions
		none (default)
	#	number
	a	text
	&	timestamp
	[space at end] requires preceding field to be a single word
*/

$GLOBALS['commands'] = array(
	'CHAIR' => array(
		'fn' => 'irc_roles',
		'allow' => 'oc',
		'args' => array('a ', 'oC:')),
	'CLERK' => array(
		'fn' => 'irc_roles',
		'allow' => 'holc',
		'args' => array('a ', 'hoLc:')),
	'WHO' => array('fn' => 'irc_who'),
	'MOTION' => array(
		'children' => array(
			'ADD' => array(
				'fn' => 'irc_motion_add',
				'allow' => 'c',
				'args' => array('aa')),
			'LIST' => array(
				'fn' => 'irc_list'),
			'VOTE' => array(
				'fn' => 'irc_motion_end',
				'allow' => 'c'),
			'PUBVOTE' => array(
				'fn' => 'irc_motion_end',
				'allow' => 'c'),
			'END' => array(
				'fn' => 'irc_motion_end',
				'allow' => 'c'),
			'MODIFY' => array(
				'fn' => 'irc_motion_modify',
				'allow' => 'c',
				'args' => array('a'))/*,
			'' => array('fn' => 'irc_help', 'args' => array('a', ''))*/)),
	'SPEAKERS' => array(
		'children' => array(
			'ADD' => array(
				'fn' => 'irc_speakers_update',
				'allow' => 'ac',
				'args' => array('c:a#', 'c:a ', 'aS:')),
			'LIST' => array(
				'fn' => 'irc_list'),
			'REMOVE' => array(
				'fn' => 'irc_speakers_update',
				'allow' => 'acS',
				'args' => array('c:a ', 'a:')),
			'CLEAR' => array(
				'fn' => 'irc_speakers_update',
				'allow' => 'c'),
			'END' => array(
				'fn' => 'irc_speakers_end',
				'allow' => 'cs'),
			'NEXT' => array(
				'fn' => 'irc_speakers_end',
				'allow' => 'c'),
			'SET' => array(
				'fn' => 'irc_speakers_set',
				'allow' => 'c',
				'args' => array('a')),
			'VOICE' => array(
				'fn' => 'irc_speakers_voice',
				'allow' => 'c',
				'args' => array('a ')),
			'LOCK' => array(
				'fn' => 'irc_speakers_lock',
				'allow' => 'c'),
			'UNLOCK' => array(
				'fn' => 'irc_speakers_lock',
				'allow' => 'c'),
			'ON' => array(
				'fn' => 'irc_speakers_toggle',
				'allow' => 'c',
				'args' => array('a ', '')),
			'OFF' => array(
				'fn' => 'irc_speakers_toggle',
				'allow' => 'c'),
			'SHUFFLE' => array(
				'fn' => 'irc_speakers_shuffle',
				'allow' => 'c',
				'args' => array('#', ''))/*,
			'' => array('fn' => 'irc_help', 'args' => array('a', ''))*/)),
	'MEETING' => array(
		'children' => array(
			'START' => array(
				'fn' => 'irc_meeting',
				'allow' => 'c',
				'args' => array('&', '')),
			'END' => array(
				'fn' => 'irc_meeting',
				'allow' => 'c')/*,
			'' => array('fn' => 'irc_help', 'args' => array('a', ''))*/)),
	'Y' => array('fn' => 'irc_motion_vote', 'allow' => 'a'),
	'N' => array('fn' => 'irc_motion_vote', 'allow' => 'a'),
	'A' => array('fn' => 'irc_motion_vote', 'allow' => 'a'),
	'HELP' => array(
		'fn' => 'irc_help',
		'args' => array('a', '')),
	'DIE' => array(
		'fn' => 'irc_die',
		'allow' => 'o'),
	'UPTIME' => array('fn' => 'irc_uptime'),
	'' => array('fn' => 'irc_help', 'args' => array('a', '')));

/* $help
provides complete script for the help function
*/
$b = chr(2);	// bold
$i = chr(22);	// italic
$u = chr(31);	// underline
$c = chr(15);	// none
$tz = date('T');// time zone

$GLOBALS['help'] = array('allow' => '*', 'text' => <<<help_
{$b}{$config['bots'][0]['nick']}{$c} is a bot to support the Chair and Clerk in their duties. Its principal duties are to take and record votes, to manage who can speak and when, to record discussion during meetings, and to verify members' identities in support of these duties. {$config['bots'][0]['nick']} does not accept direct commands.
{$b}{$config['bots'][1]['nick']}{$c} is a second login used by {$config['bots'][0]['nick']}, protecting the bot from interference when communicating in the channel and making commands shorter for users interfacing directly with it. It provides the direct command interface on behalf of {$config['bots'][0]['nick']}.
The following commands are used by normal users to interact with the bot. For information on a particular command, type {$b}/msg {$config['bots'][1]['nick']} HELP {$u}command{$c}. For a list of commands applicable only to the Chair, type {$b}/msg {$config['bots'][1]['nick']} HELP ADVANCED{$c}.
   ab1 (eg)      Log in (see {$b}HELP LOGIN{$c})
   MOTION LIST   View current motion(s)
   SPEAKERS      View or add to speakers queue
   Y/N/A         Cast a vote (see {$b}HELP VOTING{$c})
   WHO           Lists the current chair and clerk
Details on to whom each command is available are shown on individual commands' help page, along with a note indicating its availability to you at the present time.
help_

,'children' => array('LOGIN' => array('text' => <<<help_login_
{$b}Logging in{$c}
Restrictions: not logged in ({$b}_AVAILABLE_{$c})
The login system has two parts: a web page and the bot interface. The web page is located at {$config['loginurl']}. Once the login form is completed, you will be instructed to message a 3-character code to the bot. This code is unique and a new one is issued every time you log in.
Logging in ties your IRC account to your membership, allowing {$config['bots'][0]['nick']} to grant voting and speaking privileges during meetings exclusively to members. It only lasts as long as you are present in {$config['channel']}; as soon as you log out or leave the channel, your login information will be erased and you'll have to complete the login form again should you choose to log back in later.
To log out, simply leave the channel or quit your IRC client. Next time you enter the channel, you will no longer be logged in.
help_login_
,'allow' => '*A'

),'MOTION' => array('allow' => '*', 'text' => "See {$b}HELP MOTION LIST{$c}. For Chair-only motion commands, see {$b}HELP ADVANCED MOTION{$c}."
,'children' => array('LIST' => array('text' => <<<help_motion_list_
Syntax: {$b}MOTION LIST{$c}
Restrictions: none ({$b}_AVAILABLE_{$c})
Lists the current motion stack (eg. an amendment to an amendment to a motion).
help_motion_list_


))),'SPEAKERS' => array('allow' => '*', 'text' => <<<help_speakers_
Syntax: {$b}SPEAKERS LIST{$c}
        {$b}SPEAKERS ADD{$c}
        {$b}SPEAKERS REMOVE{$c}
        {$b}SPEAKERS END{$c}
The {$b}SPEAKERS{$c} commands control the speakers list, that is, the list of people waiting to speak to the current motion. These commands are only available after the list has been enabled by the Chair. Type {$b}/msg {$config['bots'][1]['nick']} HELP SPEAKERS {$u}option{$c} for details on a particular command. For Chair-only commands, see {$b}HELP ADVANCED SPEAKERS{$c}.
help_speakers_

,'children' => array('LIST' => array('text' => <<<help_speakers_list_
Syntax: {$b}SPEAKERS LIST{$b}
Restrictions: none ({$b}_AVAILABLE_{$c})
Shows the speakers queue for the current motion.
help_speakers_list_

),'ADD' => array('text' => <<<help_speakers_add_
Syntax: {$b}SPEAKERS ADD{$c}
Restrictions: logged in ({$b}_AVAILABLE_{$c})
Add yourself to the speakers queue. This command is only available when the Chair has enabled and unlocked the queue. If the queue is enabled but locked, only the Chair may add speakers.
For information on the Chair-only parameters of this command, see {$b}HELP ADVANCED SPEAKERS ADD{$c}.
help_speakers_add_
,'allow' => 'a'

),'REMOVE' => array('text' => <<<help_speakers_remove_
Syntax: {$b}SPEAKERS REMOVE{$c}
Restrictions: logged in ({$b}_AVAILABLE_{$c})
Remove yourself from the speakers queue. Obviously, this is only available when you have been added to the queue with {$b}SPEAKERS ADD{$c}.
For information on the Chair-only parameters of this command, see {$b}HELP ADVANCED SPEAKERS REMOVE{$c}.
help_speakers_remove_
,'allow' => 'a'

),'END' => array('text' => <<<help_speakers_end_
Syntax: {$b}SPEAKERS END{$c}
Restrictions: current speaker, Chair ({$b}_AVAILABLE_{$c})
Concludes the current speech.
help_speakers_end_
,'allow' => 's'


))),'VOTING' => array('text' => <<<help_voting_
Syntax: {$b}{Y|N|A}{$c}
Restrictions: logged in, not Chair ({$b}_AVAILABLE_{$c})
When prompted, cast a vote by typing {$b}/msg {$config['bots'][1]['nick']} Y{$c} to vote yes, {$b}/msg {$config['bots'][1]['nick']} N{$c} to vote no, or {$b}/msg {$config['bots'][1]['nick']} A{$c} to abstain. The bot will respond with a confirmation that your vote was recorded. This may take a few seconds if there is high traffic volume.
The bot's response will include a 5-character code. If you choose, you can use this code to verify the integrity of our voting process. When results are published (immediately for some votes, after a web and telephone voting period for others), check for the following:
1. Your member ID is on the voters list if you voted, or isn't if you didn't vote.
2. Your key is associated with the correct vote.
3. The total number of votes equals the number of member IDs, and all sums are correct.
If there are any irregularities, the vote may have been tampered with.
help_voting_
,'allow' => $GLOBALS['commands']['Y']['allow']

),'Y' => array('text' => "See {$b}HELP VOTING{$c}."
),'N' => array('text' => "See {$b}HELP VOTING{$c}."
),'A' => array('text' => "See {$b}HELP VOTING{$c}."
),'WHO' => array('text' => <<<help_who_
Syntax: {$b}WHO{$c}
Restrictions: none ({$b}_AVAILABLE_{$c})
Names the current chair and clerk, if applicable.
help_who_

),'HELP' => array('text' => "You're looking at it!"
,'children' => array('HELP' => array('text' => "Very funny."
,'children' => array('HELP' => array('text' => "Seriously, cut it out."
,'children' => array('HELP' => array('text' => "You're a dick."
,'children' => array('HELP' => array('text' => "I'm not talking to you anymore."



))))))))),'ADVANCED' => array('text' => <<<help_advanced_
{$b}Advanced commands{$c}
The following commands are only used by or relevant to the Chair. For more details on a command, type {$b}/msg {$config['bots'][1]['nick']} HELP ADVANCED {$u}command{$c}. See also {$b}HELP{$c} for general commands.
   CHAIR      Designates the Chair
   CLERK      Designates the Clerk
   MOTION     View or edit current motions
   SPEAKERS   View or edit speakers queue
   MEETING    Begin or end meeting
help_advanced_

,'children' => array('CHAIR' => array('text' => <<<help_advanced_chair_
Syntax: {$b}CHAIR {$u}[username]{$c}
Restrictions: op, Chair ({$b}_AVAILABLE_{$c})
If the {$u}username{$c} field is left blank, the person running the command will be designated as Chair; otherwise, the named user will be Chair. Only one user can be Chair at a time. Many of {$config['bots'][0]['nick']}'s commands can only be run by the Chair.
help_advanced_chair_
,'allow' => $GLOBALS['commands']['CHAIR']['allow']

),'CLERK' => array('text' => <<<help_advanced_clerk_
Syntax: {$b}CLERK {$u}[username]{$c}
Restrictions: hop, op, Chair, Clerk ({$b}_AVAILABLE_{$c})
If the {$u}username{$c} field is left blank, the person running the command will be designated as Clerk; otherwise, the named user will be Chair. Only one user can be Clerk at a time. There are no additional privileges awarded to the Clerk, but the bot will automatically keep the Clerk appraised of activity as the meeting proceeds.
help_advanced_clerk_
,'allow' => $GLOBALS['commands']['CLERK']['allow']


),'MOTION' => array('allow' => '*', 'text' => <<<help_advanced_motion_
Syntax: {$b}MOTION LIST{$c} {$i}(see {$b}HELP MOTION LIST{$b}){$c}
        {$b}MOTION ADD {$u}username{$u} {$u}description{$c}
        {$b}MOTION MODIFY {$u}description{$c}
        {$b}MOTION {VOTE|PUBVOTE|END}{$c}
The {$b}MOTION{$c} commands control the motion stack. Type {$b}/msg {$config['bots'][1]['nick']} HELP MOTION {$u}option{$c} for details on a command.
help_advanced_motion_

,'children' => array('ADD' => array('text' => <<<help_advanced_motion_add_
Syntax: {$b}MOTION ADD {$u}username{$u} {$u}description{$c}
Restrictions: Chair ({$b}_AVAILABLE_{$c})
Begins a new motion, temporarily suspending the current motion if one is already in progress. The bot will interpret the command as "{$i}{$u}username{$u} moved {$u}description{$c}", so structure the command/motion accordingly.
help_advanced_motion_add_
,'allow' => $GLOBALS['commands']['MOTION']['children']['ADD']['allow']

),'MODIFY' => array('text' => <<<help_advanced_motion_modify_
Syntax: {$b}MOTION MODIFY {$u}description{$c}
Restrictions: Chair ({$b}_AVAILABLE_{$c})
Modifies the current motion with a new description, for instance, after an amendment has been made. The mover cannot be changed. Use {$b}MOTION LIST{$c} if you need to check the description of the current motion. The {$u}description{$c} field is interpreted as "{$i}SomeUser moved {$u}description{$c}".
help_advanced_motion_modify_
,'allow' => $GLOBALS['commands']['MOTION']['children']['MODIFY']['allow']

),'VOTE' => array('text' => <<<help_advanced_motion_vote_
Syntax: {$b}MOTION {VOTE|PUBVOTE|END}{$c}
Restrictions: Chair ({$b}_AVAILABLE_{$c})
Concludes the current motion.
{$b}MOTION VOTE{$c} puts the motion to a vote, announcing the results immediately. For information on the voting process, see {$b}VOTING{$c}.
{$b}MOTION PUBVOTE{$c} also calls a vote, giving attendees the option of voting at the meeting, but doesn't announce the results pending voting done by other means (eg. web, telephone) at a later date.
{$b}MOTION END{$c} concludes the motion without calling a vote.
help_advanced_motion_vote_
,'allow' => $GLOBALS['commands']['MOTION']['children']['VOTE']['allow']

),'PUBVOTE' => array('text' => "See {$b}HELP ADVANCED MOTION VOTE{$c}."
),'END' => array('text' => "See {$b}HELP ADVANCED MOTION VOTE{$c}."


))),'SPEAKERS' => array('allow' => '*', 'text' => <<<help_advanced_speakers_
Syntax: {$b}SPEAKERS LIST{$c} {$i}(see {$b}HELP SPEAKERS LIST{$b}){$c}
        {$b}SPEAKERS ADD {$u}username{$u} {$u}[position]{$c} {$i}(see also {$b}HELP SPEAKERS ADD{$b}){$c}
        {$b}SPEAKERS REMOVE {$u}username{$c} {$i}(see also {$b}HELP SPEAKERS REMOVE{$b}){$c}
        {$b}SPEAKERS SET{$c}
        {$b}SPEAKERS END{$c} {$i}(see {$b}HELP SPEAKERS END{$b}){$c}
        {$b}SPEAKERS NEXT{$c}
        {$b}SPEAKERS CLEAR{$c}
        {$b}SPEAKERS VOICE {ALL|NONE}{$c}
        {$b}SPEAKERS {LOCK|UNLOCK}{$c}
        {$b}SPEAKERS {ON|OFF}{$c}
        {$b}SPEAKERS SHUFFLE {$u}[number]{$c}
The {$b}SPEAKERS{$c} commands control the speakers list, that is, the list of people waiting to speak to the current motion. Most commands are only available after the list has been turned on using {$b}SPEAKERS ON{$c}. Type {$b}/msg {$config['bots'][1]['nick']} HELP ADVANCED SPEAKERS {$u}option{$c} for details on a command.
help_advanced_speakers_

,'children' => array('ADD' => array('text' => <<<help_advanced_speakers_add_
Syntax: {$b}SPEAKERS ADD {$u}username{$u} {$u}[position]{$c}
Restrictions: Chair (as shown; {$b}_AVAILABLE_{$c})
Add the named user to the speakers queue. When {$u}position{$c} is included, the user is added {$i}after{$c} the specified position as numbered in {$b}SPEAKERS LIST{$c}; 0 adds the user to the top of the list. There is no way to rearrange the queue, so bumping a user up on the list requires removing them with {$b}SPEAKERS REMOVE{$c} and re-adding at the desired index.
For information on how users can add themselves to the list, see {$b}HELP SPEAKERS ADD{$c}. This is only possible when the speakers list is not locked (see {$b}HELP ADVANCED SPEAKERS LOCK{$c}).
help_advanced_speakers_add_
,'allow' => 'c'

),'REMOVE' => array('text' => <<<help_advanced_speakers_remove_
Syntax: {$b}SPEAKERS REMOVE {$u}username{$c}
Restrictions: Chair (as shown; {$b}_AVAILABLE_{$c})
Remove the named user from the speakers queue.
For information on how users can remove themselves from the list, see {$b}HELP SPEAKERS REMOVE{$c}. This can be done whether the speakers list is locked or not (see {$b}HELP ADVANCED SPEAKERS LOCK{$c}).
help_advanced_speakers_remove_
,'allow' => 'c'

),'CLEAR' => array('text' => <<<help_advanced_speakers_clear_
Syntax: {$b}SPEAKERS CLEAR{$c}
Restrictions: Chair ({$b}_AVAILABLE_{$c})
Clear the speakers queue. This will happen automatically when {$b}SPEAKERS OFF{$c} is run.
help_advanced_speakers_clear_
,'allow' => $GLOBALS['commands']['SPEAKERS']['children']['CLEAR']['allow']

),'NEXT' => array('text' => <<<help_advanced_speakers_next_
Syntax: {$b}SPEAKERS NEXT{$c}
Restrictions: Chair ({$b}_AVAILABLE_{$c})
Recognizes the next speaker, concluding the current speech if one is in progress. See {$b}HELP SPEAKERS END{$c} to end a speech without proceeding to the next one.
help_advanced_speakers_next_
,'allow' => $GLOBALS['commands']['SPEAKERS']['children']['NEXT']['allow']

),'SET' => array('text' => <<<help_advanced_speakers_set_
Syntax: {$b}SPEAKERS SET {$u}username{$c}
Restrictions: Chair ({$b}_AVAILABLE_{$c})
Immediately recognizes a speaker, regardless of the status of the speakers list. If the speakers list is on, the current speech will be concluded if applicable. If not, the speech will proceed but the list will not be available unless subsequently enabled.
help_advanced_speakers_set_
,'allow' => $GLOBALS['commands']['SPEAKERS']['children']['SET']['allow']

),'VOICE' => array('text' => <<<help_advanced_speakers_voice_
Syntax: {$b}SPEAKERS VOICE {ALL|ONE}{$c}
Restrictions: Chair ({$b}_AVAILABLE_{$c})
Enables or disables voice management while the speakers list is active. {$b}ALL{$c} will merely announce when a speech begins and ends, while {$b}ONE{$c} will manage +v (voice) so that only the currently-recognized user can speak.
help_advanced_speakers_voice_
,'allow' => $GLOBALS['commands']['SPEAKERS']['children']['VOICE']['allow']

),'LOCK' => array('text' => <<<help_advanced_speakers_lock_
Syntax: {$b}SPEAKERS {LOCK|UNLOCK}{$c}
Restrictions: Chair ({$b}_AVAILABLE_{$c})
Locks or unlocks the speakers list. When the speakers list is locked, only the Chair can add speakers; when it's unlocked, anyone qualified (logged in) can add themselves with {$b}SPEAKERS ADD{$c}. Regardless of the list status, anyone on the list can remove themselves using {$b}SPEAKERS REMOVE{$c}.
help_advanced_speakers_lock_
,'allow' => $GLOBALS['commands']['SPEAKERS']['children']['LOCK']['allow']
),'UNLOCK' => array('text' => "See {$b}HELP ADVANCED SPEAKERS LOCK{$c}."

),'ON' => array('text' => <<<help_advanced_speakers_on_
Syntax: {$b}SPEAKERS ON [LOCKED]{$c}
Restrictions: Chair ({$b}_AVAILABLE_{$c})
Enables the speakers list and voice management. When the {$b}LOCKED{$c} keyword is specified, speakers can only be added by the Chair; otherwise, anyone qualified can add themselves. This can be changed once the speakers list is active with {$b}SPEAKERS LOCK{$c} and {$b}SPEAKERS UNLOCK{$c}.
help_advanced_speakers_on_
,'allow' => $GLOBALS['commands']['SPEAKERS']['children']['ON']['allow']

),'OFF' => array('text' => <<<help_advanced_speakers_off_
Syntax: {$b}SPEAKERS OFF{$c}
Restrictions: Chair ({$b}_AVAILABLE_{$c})
Disables the speakers list and voice management, concluding the current speech if it is in progress (see {$b}HELP SPEAKERS END{$c}) and clearing the speakers list (see {$b}HELP ADVANCED SPEAKERS CLEAR{$c}).
help_advanced_speakers_off_
,'allow' => $GLOBALS['commands']['SPEAKERS']['children']['OFF']['allow']


),'SHUFFLE' => array('text' => <<<help_advanced_speakers_shuffle_
Syntax: {$b}SPEAKERS SHUFFLE {$u}[number]{$c}
Restrictions: Chair ({$b}_AVAILABLE{$c})
Randomly shuffles the speakers list. If specified, only the first {$u}number{$c} speakers will be shuffled; otherwise, the entire list will be shuffled.
help_advanced_speakers_shuffle_
,'allow' => $GLOBALS['commands']['SPEAKERS']['children']['SHUFFLE']['allow']

))),'MEETING' => array('allow' => '*', 'text' => <<<help_advanced_meeting_
Syntax: {$b}MEETING START {$u}[time]{$c}
        {$b}MEETING END{$c}
The {$b}MEETING{$c} commands control starting and ending meetings. Type {$b}/msg {$config['bots'][1]['nick']} HELP ADVANCED MEETING {$u}option{$c} for details on a command.
help_advanced_meeting_

,'children' => array('START' => array('text' => <<<help_advanced_meeting_start_
Syntax: {$b}MEETING START {$u}[time]{$c}
Restrictions: Chair ({$b}_AVAILABLE_{$c})
Signals a meeting to begin. If {$u}time{$c} is specified and is in the future, the meeting will begin at the scheduled time; otherwise, it will begin immediately.
Valid values for {$u}time{$c} are anything that can be interpreted by PHP's date and time formats http://php.net/datetime.formats . Particularly useful in this case are the relative formats, so you can specify that a meeting should begin in "+15 minutes" or "tomorrow noon". Unless you specify otherwise, the server interprets time values in $tz.
help_advanced_meeting_start_
,'allow' => $GLOBALS['commands']['MEETING']['children']['START']['allow']

),'END' => array('text' => <<<help_advanced_meeting_end_
Syntax: {$b}MEETING END{$c}
Restrictions: Chair ({$b}_AVAILABLE_{$c})
Ends the meeting immediately. No time delay is available as there is for {$b}MEETING START{$c}.
help_advanced_meeting_end_
,'allow' => $GLOBALS['commands']['MEETING']['children']['END']['allow']


))),'' => array('allow' => '*', 'text' => "Unrecognized command. Type {$b}/msg {$config['bots'][1]['nick']} HELP{$c} for help."

)))));


?>