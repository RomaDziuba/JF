<?php
/**
 * @package Jimbo
 */ 
abstract class JimboTableHandler
{
	public $params = array();
	
	abstract public function display();
	abstract public function handle(&$info);
	abstract public function afterCommit(&$info); 
	abstract public function getAlowedActions($actions, $currentRow = array()); 
}
?>