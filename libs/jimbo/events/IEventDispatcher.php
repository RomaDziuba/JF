<?php
/**
 * 
 * author: Denis Panaskin
 * version: Beta 1.0 28.04.2007
 */
 

 
 interface IEventDispatcher
 {
 	public function addEventListener($type, $listener);
 	public function dispatchEvent(Event $event); 
 	public function hasEventListener($type); 
 	public function removeEventListener($type, $listener); 
 }
?>
