<?php

/* STACK
Contains the currently-active objects, organized in a stack.
*/
class stack {
	private $data = array();
	
	function tick() {
		if (count($this->data))
			$this->data[count($this->data) - 1]->tick();
	}
	
	function push($data)	{ $this->data[]=$data; } // array_push() has more overhead
	function pop()			{ $data=$this->data?array_pop($this->data):null; $this->tick(); return $data; }
	function peek()			{ return $this->data?$this->data[count($this->data)-1]:null; }
	function size()			{ return count($this->data); }
	function clear()		{ for ($i=count($this->data)-1;$i>=0;$i--) unset($this->data[$i]); $this->data=array(); } // deepest first
	function index()		{ foreach ($this->data as $item) $list[]=array('name'=>$item->name,'owner'=>$item->owner,'type'=>get_class($item)); return $list; }
}

?>