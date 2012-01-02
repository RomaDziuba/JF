<?php 
class ObjectJimboPlugin extends BaseJimboPlugin
{
    public function __construct(&$tpl)
    {
        parent::__construct($tpl);
        
        if (!class_exists('Object')) {
            throw new SystemException(_('Class "Object" was not found.'));
        }
    } // end __construct
    
    public function onInit()
    {
        
    }
    
 	public function getObject($name, $pluginName = true)
    {
        global $jimbo;
        
        if ($pluginName === true) {
        	$pluginName = str_replace("Plugin", "", get_class($this));
        }
        
        return $jimbo->getObject($name, $pluginName); 
    }
        
}

?>