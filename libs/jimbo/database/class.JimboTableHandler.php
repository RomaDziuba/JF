<?php
/**
 * @package Jimbo
 */
abstract class JimboTableHandler
{
	protected $tblAction;

	public $params = array();

	public function __construct(&$tblAction)
	{
		$this->tblAction = $tblAction;
	}

	public function display()
    {
        return false;
    }

	public function handle(&$info)
	{
		return false;
	}

	public function afterCommit(&$info)
	{

	}

	public function getAlowedActions($actions, $currentRow = array())
    {
        return $actions;
    } // end getAlowedActions

    public function isInsert()
    {
    	return isset($_POST['performPost']) && $_POST['performPost'] == 'insert';
    }

    public function isUpdate()
    {
    	return isset($_POST['performPost']) && $_POST['performPost'] == 'save';
    }

    public function isChange()
    {
    	return isset($_POST['performPost']) && $this->isAction($_POST['performPost']);
    }


    public function isAction($needle)
    {
    	$actions = array('insert', 'save');

    	return in_array($needle, $actions);
    }


}
?>