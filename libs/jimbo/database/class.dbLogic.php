<?php
/**
 *
 * @package Jimbo
 * @subpackage Databases
 */
class dbLogic {

	var $knownPostActions = array('save', 'insert', 'remove', 'info');

	function detectPerformAction($tblAction) {
		$_sessionData = &$tblAction->sessionData;

		if (isset($_REQUEST['filter_wtd'])) {
			if ( ($_REQUEST['filter_wtd'] == 'apply') && (empty($_REQUEST['grouped_action']))) {

				$filters = (array) $_REQUEST['filter'];
				if (get_magic_quotes_gpc()) {
					foreach ($filters as $key => $val) {
						if (!is_array($val)) {
							$filters[$key] = stripslashes($val);
						}
					}
				}

				$tblName = $tblAction->tableDefinition->name;

				unset($_GET['order']);

				if (!isset($_sessionData['DB_FILTERS'][$tblName])) {
					$_sessionData['DB_FILTERS'][$tblName] = array();
				}
				foreach ($filters as $key => $value) {
					if (!$this->isEmpty($value)) {
						$_sessionData['DB_FILTERS'][$tblName][$key] = $value;
					} else {
						unset($_sessionData['DB_FILTERS'][$tblName][$key]);
					}
				}
			}
			//Header("Location: ".$_SERVER['REQUEST_URI']);
			//die;

		}

		$allowedActions = array_keys($tblAction->tableDefinition->actions);

		if (in_array('edit', $allowedActions)) {
			$allowedActions[] = 'save';
		}


		//print_r($allowedActions); die;
		// Пробуем понять есть ли действие POST
		// Для этого 1. мы должны уметь его обрабатывать, 2. оно должно быть разрешено
		if (isset($_POST['performPost'])) {
			if (in_array($_POST['performPost'], $allowedActions) && in_array($_POST['performPost'], $this->knownPostActions)) {
				return $_POST['performPost'];
			}
		}

		$allowedActions[] = 'foreignKeyLoad';

		if (isset($_GET['action'])) {
			if (!in_array($_GET['action'], $this->knownPostActions) && in_array($_GET['action'], $allowedActions)) {
				return $_GET['action'];
			}
		}


		return 'nothing';
	}

	function detectViewAction($tblAction, $status = true) {
		$allowedActions = array_keys($tblAction->tableDefinition->actions);

		$action = ($status && isset($_POST['performPost'])) ? 'list' : (isset($_GET['action']) ? $_GET['action'] : 'list');

		if (!in_array($action, $allowedActions)) {
			// Permission denied
			return 'list';
		}
		switch ($action) {
			case 'insert':
			case 'edit':
			case 'remove':
			case 'excel':
			case 'info': {
				return $action;
			} break;
			default:
				return 'list';
		}
	}

	function isEmpty($var) {
		if (is_array($var)) {
			$var = array_unique(array_values($var));
			return ($var == array(''));
		} else {
			return trim($var) == '';
		}
	}
}
?>