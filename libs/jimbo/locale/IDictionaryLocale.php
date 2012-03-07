<?php 

interface IDictionaryLocale 
{
    public function load();
    public function get($key);
    public function getAll();
}

?>