<?php

if (!class_exists('Event')):
class Event
{
    public $bubbles;
 	public $currentTarget;
 	public $type;
 	
 	const INIT   = "INIT";
 	const UPDATE = "UPDATE";
 	const ADD    = "ADD";
 	const DELETE = "DELETE";    
 	
 	public function __construct($type, &$currentTarget = null, $bubbles = false)
 	{
 		$this->type = $type;
 		$this->currentTarget = $currentTarget;
 		$this->bubbles = $bubbles;
 	} 
}
endif;
?>
