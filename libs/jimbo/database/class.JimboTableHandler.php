<?php
/**
 * @author Denis Panaskin <denis@panaskin.com>
 * @package Jimbo
 * @subpackage Databases
 */
abstract class JimboTableHandler
{
	protected $tblAction;

	public $params = array();

	public function __construct(&$tblAction)
	{
		$this->tblAction = $tblAction;
	}

	public function display($info, &$result)
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

    public function isRemove()
    {
        return isset($_POST['performPost']) && $_POST['performPost'] == 'remove';
    }

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

    /**
     * Override this method if you need change data in rows table for list template
     *
     * @param array $rows
     * @return array
     */
    public function modifyTableData($rows)
    {
        return $rows;
    }

    /**
     * Old method for highlight row. Don't use this method in production
     *
     * @param array $pageIDs
     * @return array
     */
    public function highlightlist($pageIDs)
    {
        return false;
    }

    /**
     * Override for show custome values in filters
     *
     * @param strinf $fieldName
     * @return boolean
     */
    public function getFilterValues($fieldName)
    {
        return false;
    }

    /**
     * Override this method for modified template or change data for edit form
     *
     * @param string  $section
     * @param Template_Lite $tpl
     * @param array $currentRow
     * @return boolean
     */
    public function templateCallback($section, &$tpl, &$currentRow)
    {
        return false;
    }


}
?>