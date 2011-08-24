<?php
require_once dirname(__FILE__)."/IEventDispatcher.php";
require_once dirname(__FILE__)."/Event.php";

/**
* author: Denis Panaskin <goliathdp@gmail.com>
* version: RC 1.0
*/
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
        if (!is_callable($listener)) {
            return false;
        }
        
        $key = $this->_getKey($listener);
        
        if (isset($this->listeners[$type][$key])) {
            return false;
        }

        $this->listeners[$type][$key] = $listener;
        return true;
    } // end addEventListener
     
    public function dispatchEvent(Event $event)
    {
        if (empty($this->listeners[$event->type])) {
            return false;
        }
         
        foreach ($this->listeners[$event->type] as $key => $listener) {
            if (is_callable($listener)) {
                call_user_func_array($listener, array($event));
            }
        }
        
        return true;
    } // end dispatchEvent
     
    public function removeEventListener($type, $listener)
    {
        $key = $this->_getKey($listener);
        unset($this->listeners[$type][$key]);
    }

    private function _getKey($listener)
    {
        if (is_array($listener)) {
            $key = get_class($listener[0]).":".$listener[1];
        } else {
            $key = 'f:'.$listener;
        }
        
        return $key;
    } // end _getKey
     
}
?>