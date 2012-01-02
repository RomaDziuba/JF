<?php 
abstract class AbstractPlugin
{
    protected $tpl;
     
    public function __construct(&$tpl)
    {
        $this->tpl = &$tpl;
    }
    
    public function setOptions($options)
    {
        $this->options = $options;
    }
    
    abstract public function onInit();
    
    
    public function __set($label, $object) 
    {
        $this->tpl->_vars[$label] = $object;
    }


    public function __unset($label) 
    {
        if ($this->tpl->_vars[$label]) {
            unset($this->tpl->_vars[$label]);
        }
    }

    public function __get($label) 
    {
        if (isset($this->tpl->_vars[$label])) {
            return $this->tpl->_vars[$label];
        }
        return false;
    }

    public function __isset($label) 
    {
        return isset($this->tpl->_vars[$label]);
    }
    
    public function fetch($template)
    {
        $path = $this->options['plugin_path'].'templates/'.$template;
        
        $currentPath = $this->tpl->template_dir; 
        
        if (file_exists($path)) {
            $this->tpl->template_dir = $this->options['plugin_path'].'templates/';
        }
        
        $content = $this->tpl->fetch($template);
        $this->tpl->template_dir = $currentPath;
        
        return $content;
    }
}
?>