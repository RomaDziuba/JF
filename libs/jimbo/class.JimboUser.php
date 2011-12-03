<?php 
class JimboUser 
{
    private $_sessionData;
    
    private $requiredFields = array('auth', 'auth_id', 'auth_login', 'auth_role');
    
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
	
	public function getRole()
	{
	    return $this->get('auth_role');
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
	
}
?>