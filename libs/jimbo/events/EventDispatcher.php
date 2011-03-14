<?php
/**
 * 
 * author: Denis Panaskin
 * version: Beta 1.0 29.04.2007
 */
require_once dirname(__FILE__)."/IEventDispatcher.php";
require_once dirname(__FILE__)."/Event.php";

 class EventDispatcher implements IEventDispatcher
 {
 
 	private $listeners;
 	
 	public function __construct()
 	{
 	    $this->listeners = array();
 	}
 	
 
 	/**
 	 * $this->addEventListener("Init", array(&$this, "f"));
	 * $this->addEventListener("Init", ty);
 	 */
 	public function addEventListener($type, $listener)
 	{
 		/** Проверка на существование ф-ции или метода */
 		if(!is_callable($listener))
 			return false;
		
		if(is_array($listener))
			$key = get_class($listener[0]);
		else
			$key = "Function";
		
		/** Проверка на наличие одинакового слушателя */
		if(isset($this->listeners[$type]) && count($this->listeners[$type])>0) {	
			foreach($this->listeners[$type] as $s_listener) {
				if($s_listener==$listener) {
					return false;
				}
			}
		}
			
		if(is_array($listener))
 			$this->listeners[$type][get_class($listener[0])."::".$listener[1]] = $listener;
 		else
 			$this->listeners[$type]["Function::".$listener] = $listener;
		
 	}// end addEventListener
 	
 	public function dispatchEvent(Event $event)
 	{
 	    if( empty($this->listeners[$event->type]) ) {
 	        return false;
 	    }
 	    
        foreach($this->listeners[$event->type] as $key=>$c_listener) {
            if(is_callable(array($c_listener[0], $c_listener[1]))) {
                call_user_method_array($c_listener[1], $c_listener[0], array($event));
            }
        }
		
        return true;
 	} // end dispatchEvent
 	
 	public function hasEventListener($type)
 	{
 		return !empty($this->listeners[$type]);
 	} 
 	
 	public function removeEventListener($type, $listener)
 	{
 		if(is_array($listener))
 			unset($this->listeners[$type][get_class($listener[0])."::".$listener[1]]);
		else
			unset($this->listeners[$type]["Function::".$listener]);
 	}
 	
 }
?>
