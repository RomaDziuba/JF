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
    
    public function &getObject($name)
    {
        global $jimbo;
        
        return Object::getInstance($name, $jimbo->db, $this->options['plugin_path']);
    }
    
    
}

?>