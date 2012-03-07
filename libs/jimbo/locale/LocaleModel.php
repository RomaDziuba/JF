<?php 
class LocaleModel
{
    protected $labels = array();
    
    public function addDictionary(IDictionaryLocale $dictionary)
    {
        $dictionary->load();
        $this->labels += $dictionary->getAll();
    }
    
    public function get($key)
    {
        return isset($this->labels[$key]) ? $this->labels[$key] : false;
    }
    
}
?>