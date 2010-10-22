<?php

function dbadmin_perms($table, &$tblAction) {
	global $_sessionData, $ddcPerms, $allowEditButtons;
	
	if ($table == 'user_profile') {
		if ($_GET['action'] != 'edit' || $_GET['ID'] != $_sessionData['auth_id']) {			
			die();
		}
		return true;
	}	
	
	if ($table == 'client_itt') {
		if ($_GET['action'] != 'info') {
			die;
		} else {
			$_sessionData['DB_ALLOWED_IDS']['client_itt'][] = (int)@$_GET['ID'];
		}
	}
	
	if (empty($_sessionData['DB_groups_id'])) {
		displayError('Неверный порядок операций', "/");
	} elseif (in_array($_sessionData['DB_groups_id'], $ddcPerms->getOpStatus('DDC_DINFO_WRITE'))) {
		// Всьо чотко
	} elseif (in_array($_sessionData['DB_groups_id'], $ddcPerms->getOpStatus('DDC_DINFO_READ'))) {
		$allowEditButtons = false;
	} else {
		displayError('Отказано в доступе', "/");
	}

}

?>
