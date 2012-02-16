<?php 

/**
 * @package Jimbo
 * @subpackage Spot
 */
class SpotController extends EventDispatcher
{
    protected $db;
    
    private $_sessionData;
    
    protected $requiredFields = array('auth', 'auth_id', 'auth_login', 'info');
    
    public function __construct(&$_sessionData)
    {
    	$this->_sessionData = &$_sessionData;
    }
    
    public function doLogin($fields)
    {
    	global $jimbo;
    
    	foreach($this->requiredFields as $name) {
    		if(!isset($fields[$name])) {
    			throw new SystemException(sprintf(_('Not found parametr %s'), $name));
    		}
    	}
    
    	$this->_sessionData = $fields;
    
    	return true;
    }
    
    public function isLogin()
    {
    	return isset($this->_sessionData["auth"]) && $this->_sessionData["auth"] == "yes";
    }
    
    public function get($name)
    {
    	$value = isset($this->_sessionData[$name]) ? $this->_sessionData[$name] : false;
    
    	return $value;
    }
    
    public function set($name, $value)
    {
    	$this->_sessionData[$name] = $value;
    
    	return $value;
    }
    
    public function getData()
    {
    	return $this->_sessionData;
    }
    
    public function getID()
    {
    	return $this->get("auth_id");
    }
    
    public function getDSN()
    {
    	$info = $this->_sessionData['info']['spot'];
    	$dsn = 'mysql://'.$info['user'].':'.$info['password'].'@'.$info['host'].':'.$info['port'].'/'.$info['prefix'].'_'.$info['id'];
    	 
    	return $dsn;
    }
    
}

?>