<?php

function clientsStatusCheck($groupID) {
	global $db;

	$db->query("update clients set status=0, share=0 where id_group=$groupID");

	// Считаем долю клиентов
	$db->query("update clients set share=IFNULL((select sum(tt_part) from client_tt where client_tt.id_client=clients.id_client and (date_close > NOW() or date_close='0000-00-00')), 0) where id_group = $groupID");

	// Считаем количество точек
	$db->query("update clients set tt_count=IFNULL((select count(*) from client_tt where client_tt.id_client=clients.id_client), 0) where id_group = $groupID");

	// Клиенты без точек вообще
	$db->query("update clients left join client_tt using (id_client) set status=2 where id_client_tt is NULL and clients.id_group = $groupID and clients.status = 0");

	// Клиенты без открытых точек
	$db->query("update clients left join client_tt on (clients.id_client = client_tt.id_client and (date_close > NOW() or date_close='0000-00-00')) set status=1 where id_client_tt is NULL and clients.id_group = $groupID and clients.status = 0");

	// Доля не 100%
	$db->query("update clients set status=3 where clients.id_group = $groupID and (share <= 99 or share >= 101) and status=0");
}

function parseFilters() {
	global $db, $ddcPerms;

	$currentFilter = array();
	foreach (explode("\n", $_REQUEST['hiddenFilters']) as $filterLine) {
		$tmp = explode(":", $filterLine);
		$filterType = trim($tmp[0]);

		if (empty($filterType)) {
			continue;
		}

		$currentFilter[$filterType] = array();
		$filterValues = explode(",", $tmp[1]);
		foreach ($filterValues as $item) {
			$currentFilter[$filterType][] = trim($item);
		}
	}

	if (isset($currentFilter['scu_groupped'])) {
		$currentFilter['scu'] = $currentFilter['scu_groupped'];
		unset($currentFilter['scu_groupped']);
	}

	if (empty($currentFilter['distributor'])) {
		$currentFilter['distributor'] = $ddcPerms->allowedGroupKeys;
	} else {
		$currentFilter['distributor'] = array_intersect($currentFilter['distributor'], $ddcPerms->allowedGroupKeys);
	}

	if (isset($ddcPerms->allowedSCU)) {
		if (empty($currentFilter['scu'])) {
			$currentFilter['scu'] = $ddcPerms->allowedSCU;
		} else {
			$currentFilter['scu'] = array_intersect($currentFilter['scu'], $ddcPerms->allowedSCU);
		}
	}

	return $currentFilter;
}

function getDistributorFilter($currentFilter) {
	global $db;
	$sql = array();
	$sql['from'] = array();
	$sql['where'] = array();
	if (isset($currentFilter['dgroup'])) {
		foreach ($currentFilter['dgroup'] as $dgroupKey => $dgroupItem) {
			$currentFilter['dgroup'][$dgroupKey] = $db->escape($dgroupItem);
		}
		$sql['where'][] = 'g.dist_group IN ("'. implode('", "', $currentFilter['dgroup']) .'")';
	}
	if (!empty($currentFilter['manager_pm'])) {
		if (!empty($currentFilter['manager_am'])) {
			$currentFilter['manager_am'] = array_map('intval', $currentFilter['manager_am']);
			$sql['where'][] = 'g.id_manager IN ("'. implode('", "', $currentFilter['manager_am']) .'")';
		} else {
			$currentFilter['manager_pm'] = array_map('intval', $currentFilter['manager_pm']);
			$sql['from'][] = 'JOIN manager_provincial mp ON mp.id = g.id_manager';
			$sql['where'][] = 'mp.id_regional IN ("'. implode('", "', $currentFilter['manager_pm']) .'")';
		}
	}

	$distributorIds = $currentFilter['distributor'];
	if (!empty($sql['where']) && !empty($distributorIds)) {
		$distributorIds = $db->getCol('
			SELECT	g.id
			FROM 	groups g '. implode(' ', $sql['from']) .'
			WHERE 	'. implode(' AND ', $sql['where']) .' AND
					g.id IN ('. implode(', ', $distributorIds) .')
		');	
	}

	$distrFilter = ' (NULL) ';
	if (!empty($distributorIds)) {
		$distrFilter = ' ('. implode(', ', $distributorIds) .') ';
	}
	return $distrFilter;

}

function getSCUFilter($currentFilter, $fieldName = 'id_product') {
	global $db, $ddcPerms;
	$sql = '';
	if (isset($currentFilter['scu_group'])) {
		$sql .= " AND p_group in ('".join("', '", $currentFilter['scu_group'])."')";
	}
	if (isset($currentFilter['scu_sgroup'])) {
		$sql .= " AND p_sgroup in ('".join("', '", $currentFilter['scu_sgroup'])."')";
	}
	if (isset($currentFilter['scu_brend'])) {
		$sql .= " AND traidm in ('".join("', '", $currentFilter['scu_brend'])."')";
	}
	if (isset($currentFilter['scu_category'])) {
		$sql .= " AND category in ('".join("', '", $currentFilter['scu_category'])."')";
	}
	if (isset($currentFilter['scu'])) {
		$sql .= " AND id in ('".join("', '", $currentFilter['scu'])."')";
	}

	if (isset($ddcPerms->allowedSCU)) {
		$sql .= " AND id in ('".join("', '", $ddcPerms->allowedSCU)."')";
	}

	if (empty($sql)) {
		$scuFilter = '';
	} else {
		$sql = 'select id from production where 1 '.$sql;
		$tmp = $db->getCol($sql);
		$scuFilter = !empty($tmp) ? " AND $fieldName in (".join(",", $tmp).')' : ' AND 0';
	}

	return $scuFilter;
}

function displayTemplate($content, $templatePath, $template) {
	$tpl = new Template_PHPLIB($templatePath, 'remove');
	$tpl->setFile('OUT', $template);
	$tpl->setVar('CONTENT', $content);
	$tpl->parse('RESULT', 'OUT');

	basicDisplay($tpl->get('RESULT'));
}

function recalcShip($shipID) {
	global $db;

	$sql = "select * from scj_ships where ships_id = $shipID";
	$info = $db->getRow($sql);
	$shipmentTs = strtotime($info['date']);

	$group = $info['group_id'];
	if (empty($group)) {
		return;
	}

	// точки открытые на момент отгрузки
	$clientTT = array();
	$sql = "select client_code, client_tt.id_client_tt, tt_part, date_close from client_tt, clients where clients.id_client = client_tt.id_client and clients.id_group=$group";
	$list = $db->getAll($sql);
	foreach ($list as $row) {
		$clientTT[$row['client_code']][] = $row;
	}


	// строим списк качественных продуков
	$sql = "select id from production where qa='Y'";
	$products = $db->getCol($sql);

	$db->query("delete from scj_tt_ships where scj_ships_id = $shipID");

	$sql = "select * from scj_ships_data where scj_ships_id = $shipID";
	$list = $db->query($sql);

	while ($row = $list->fetchRow()) {
		if (is_array($clientTT[$row['client_code']])) {
			foreach ($clientTT[$row['client_code']] as $tt) {
				$value = round($row['amount'] * $tt['tt_part'] / 100, 2);
				$sql = "insert into scj_tt_ships (scj_ships_id, id_product, amount, price, id_client_tt) values ($shipID, ".$row['id_product'].", $value, ".$row['price'].", ".$tt['id_client_tt'].")";
				$qRes = $db->query($sql);
				if (PEAR::isError($qRes)) {
					print_r($qRes);
				}

				if ((substr($tt['date_close'], 0, 4) != '0000') && (strtotime($tt['date_close']) < $shipmentTs) ) {
					$sql = "update client_tt set date_close='{$info['date']}' where id_client_tt=".$tt['id_client_tt'];
					$db->query($sql);
				}
			}
		}

		if (in_array($row['id_product'], $products)) {
			$qaShip[$row['id_product']] = true;
		}
	}


	$qa = 1;
	foreach ($products as $item) {
		if (empty($qaShip[$item])) {
			$qa = 0;
			break;
		}
	}

	$sql = "update scj_ships set quality=$qa where ships_id = ".$shipID;
	$db->query($sql);

	$list->free();
	unset($clientTT);
	unset($products);
	unset($qaShip);
}

function frameError($message) {
	echo '<h2 style="color:red; text-align: center; font-size:16px">'.$message.'</h2>';
	die;
}

function toTranslit($var) {
	$f = array("Сервис", "Дилер", "Офис", "Центр", "Кс", "сервис", "дилер", "офис", "центр", "кс", "ЧП", "чп");
	$t = array("Service", "Dealer", "Office", "Center", "X", "service", "dealer", "office", "center", "x", "", "");
	$var = str_replace($f,$t,$var);

	$f = array('а','б','в','г', 'д', 'е', 'ё', 'ж', 'з', 'и', 'й', 'к', 'л', 'м', 'н', 'о', 'п', 'р', 'с', 'т', 'у', 'ф', 'х', 'ц', 'ч', 'ш', 'щ', 'ъ', 'ы', 'ь', 'э', 'ю', 'я', 'є',
	'А','Б','В','Г', 'Д', 'Е', 'Ё', 'Ж', 'З', 'И', 'Й', 'К', 'Л', 'М', 'Н', 'О', 'П', 'Р', 'С', 'Т', 'У', 'Ф', 'Ч', 'Ц', 'Ч', 'Ш', 'Щ', 'Ъ', 'Ы', 'Ь', 'Э', 'Ю', 'Я'
	);
	$r = array('a', 'b', 'v', 'g', 'd', 'e', 'e', 'zh', 'z', 'i', 'y', 'k', 'l', 'm', 'n', 'o', 'p', 'r', 's', 't', 'u', 'f', 'kh', 'ts', 'ch', 'sh', 'sz', '', 'i', '', 'e', 'yu', 'ya', 'e',
	'A', 'B', 'V', 'G', 'D', 'E', 'E', 'Zh', 'Z', 'I', 'Y', 'K', 'L', 'M', 'N', 'O', 'P', 'R', 'S', 'T', 'U', 'F', 'Kh', 'Ts', 'Ch', 'Sh', 'Sz', 'y', 'i', '', 'E', 'Yu', 'Ya'
	);	
	$var = str_replace($f,$r,$var);
	return $var;
}

function getMonday($stamp = false) {
	if (!$stamp) {
		$stamp = time();
	}
	if (date("w", $stamp) == 1) {
		// уже понедельник
		return  strtotime("monday", $stamp);
	} else {
		return  strtotime("last monday", $stamp);
	}
}

function getFormatedMonday($date) {
	$stamp = strtotime($date);
	if (date("w", $stamp) == 1) {
		// уже понедельник
		return  date('Y-m-d', strtotime("monday", $stamp));
	} else {
		return  date('Y-m-d', strtotime("last monday", $stamp));
	}
}

function formatValue($value, $format) {

	if ($format == '%d') {
		return (int)$value;
	} elseif ($format == '%s') {
		$value = trim($value);
		if (empty($value)) {
			$value = '&nbsp;';
		}
		return $value;
	} else {
		// %0.2f
		return number_format($value, 2, '.', ' ');
	}
}

function get_active_tt_cond($date_from, $date_to) {
	$date_from = date('Y-m-d', strtotime($date_from));
	$date_to = date('Y-m-d', strtotime($date_to));
	$sql = "(client_tt.date_start <= '$date_to') AND ( (client_tt.date_close >= '$date_from') OR (client_tt.date_close = '0000-00-00')) ";
	return $sql;
}

function basicDisplay($content, $template = false, $vars = false) {
	global $ddcPerms, $db,$_sessionData, $_language;

	include_once "libs/class.dbDisplayer.php";
	include_once "libs/class.dbMenu.php";
	if (!$template) {
		$template = 'main.ihtml';
	}
	$tpl = dbDisplayer::getTemplateInstance(TPL_ROOT);
	$tpl->assign('content', $content);

	$info = array(
	'basehttp' => HTTP_ROOT,
	'charset' => CHARSET,
	'title' => SITE_TITLE
	);
	
	if (!empty($_language)) {
		$tpl->assign('_language', $_language);
	}

	if ($_sessionData["auth"] == "yes") {
		$sql = "select * from dbdrive_menu order by id_parent, order_n";
		$tmp = $db->getAll($sql);

		$menu = array();
		$parents = array();

		foreach ($tmp as $item) {
			if ((!empty($item['require_mod'])) && (!defined($item['require_mod']))) {
				continue;
			}
			if (!empty($item['require_op'])) {
				$allowed = false;
				foreach (explode("|", $item['require_op']) as $rOp) {
					$op = $ddcPerms->getOpStatus(trim($rOp));
					$allowed = $allowed || (!empty($op));
				}
				if (!$allowed) {
					continue;
				}
			}

			$parents[$item['id']] = $item['id_parent'];
			if (empty($item['id_parent'])) {
				// Первый уровень
				$menu[$item['id']] = array(
				'caption' => $item['caption'],
				'href' => $item['url'],
				'level' => 1,
				'items' => array()
				);
			} elseif($menu[$item['id_parent']]['level'] == 1) {
				// второй уровень
				$menu[$item['id_parent']]['items'][$item['id']] = array(
				'caption' => $item['caption'],
				'href' => $item['url'],
				'level' => 2,
				'id_parent' => $item['id_parent'],
				'items' => array()
				);
			} else {
				// третий уровень
				$parent = $item['id_parent'];
				$top = $parents[$parent];
				$menu[$top]['items'][$parent]['items'][] = array(
				'caption' => $item['caption'],
				'href' => $item['url']
				);
			}
		}
		$menu = new dbMenu($menu);
		$tpl->assign('menu', $menu->getHTML());
	}
	$tpl->assign('info', $info);
	echo $tpl->fetch($template);
}

function excelHeaderDisplay($columns = array(), $sendHttpHeaders = true) {
	global $_sessionData;

	$id_user = (int)$_sessionData['auth_id'];
	$GLOBALS['__DELIMITER'] = $GLOBALS['db']->getOne("select delimiter from users where id=".$id_user);

	$style= '';
	if (!empty($columns)) {
		foreach ($columns as $columnId => $columnItem) {
			if (isset($columnItem['number-format'])) {
				$style .= " .col{$columnId}{mso-number-format:{$columnItem['number-format']};} ";
			}
		}
	}

	//ob_start("ob_gzhandler", 1);
	if ($sendHttpHeaders) {
		header("Cache-Control: maxage=1");
		header("Pragma: public");
		header('Content-Disposition: attachment; filename="spot2d-report-'.time().'.xls"');
		header("Content-type:application/vnd.ms-excel;charset=utf-8");
	}

	echo iconv('cp1251', 'UTF-8', '<html xmlns:o="urn:schemas-microsoft-com:office:office"
xmlns:x="urn:schemas-microsoft-com:office:excel"
xmlns="http://www.w3.org/TR/REC-html40">


<!--[if gte mso 9]><xml>
<x:ExcelWorkbook>
  <x:ExcelWorksheets>
   <x:ExcelWorksheet>
    <x:Name>DDC Report</x:Name>
    <x:WorksheetOptions>
     <x:Panes>
      <x:Pane>
       <x:Number>3</x:Number>
       <x:ActiveCol>4</x:ActiveCol>
      </x:Pane>
     </x:Panes>
     <x:ProtectContents>False</x:ProtectContents>
     <x:ProtectObjects>False</x:ProtectObjects>
     <x:ProtectScenarios>False</x:ProtectScenarios>
    </x:WorksheetOptions>
   </x:ExcelWorksheet>
  </x:ExcelWorksheets>
  <x:WindowHeight>11340</x:WindowHeight>
  <x:WindowWidth>17055</x:WindowWidth>
  <x:WindowTopX>0</x:WindowTopX>
  <x:WindowTopY>0</x:WindowTopY>
  <x:ProtectStructure>False</x:ProtectStructure>
  <x:ProtectWindows>False</x:ProtectWindows>
</x:ExcelWorkbook>
</xml><![endif]-->
<meta http-equiv="Content-Type" content="application/vnd.ms-excel; charset=utf-8">

<style type=\'text/css\'>
.thin, .grey {
    mso-style-parent:style0;
    padding-top:1px;
    padding-right:1px;
    padding-left:1px;
    mso-ignore:padding;
    color:windowtext;
    font-size:10.0pt;
    font-weight:400;
    font-style:normal;
    text-decoration:none;
    font-family:Arial;
    mso-generic-font-family:auto;
    mso-font-charset:0;
    mso-number-format:General;
    text-align:general;
    vertical-align:bottom;
    border:none;
    mso-background-source:auto;
    mso-pattern:auto;
    mso-protection:locked visible;
    white-space:nowrap;
    mso-rotate:0;
}
.grey {
	font-weight:bold;
	text-align:center;
}

.caption {
	font-weight:bold;
	text-align:center;
}
'. $style .'
</style>

</head>


<body>');

}

function displayExcelGrid($params, $caption = null) {
	$style = isset($params['style']) ? $params['style'] : '';
	$columns = isset($params['columns']) ? $params['columns'] : array();

	if (is_null($caption)) {
		$caption = $params['title'].'<br/>';
		if ( (!empty($params['report_from'])) && (!empty($params['report_to']))) {
			$caption.= 'Период отчета: с '.date("d/m/Y", strtotime($params['report_from'])). ' по '.date("d/m/Y", strtotime($params['report_to']))."<br/>";
		} elseif (!empty($params['report_from'])) {
			$caption.= 'Отчет на дату: '.date("d/m/Y", strtotime($params['report_from'])).'<br/>';
		} elseif (!empty($params['report_from'])) {
			$caption.= 'Отчет на дату: '.date("d/m/Y", strtotime($params['report_to'])).'<br/>';
		}
		$caption .= 'Показано: '.$params['shown']."<br/>";
		if (!empty($params['filters'])) {
			$caption.= 'Фильтры: <br/>'.$params['filters'];
		}
	}

	ob_start();

	$sendHttpHeaders = empty($params['excel_file_path']) ? true : false;
	excelHeaderDisplay($columns, $sendHttpHeaders);
	excelDisplayRow('<style type=\'text/css\'>'. $style .'</style><table>');
	excelDisplayRow("<tr><td colspan='20' align='center'><b>$caption</b></td></tr>");

	if (!empty($params['dynamic'])) {
		echo "<tr>";
		foreach ($params['dynamic'] as $item) {
			excelDisplayRow("<td colspan='{$item['colspan']}'>{$item['caption']}</td>");
		}
		echo "</tr>";
	}

	$header = !is_array($params['header'][0]) ? array($params['header']) : $params['header'];
	foreach ($header as $h) {
		excelDisplayRow('<tr><td>'.join("</td><td>", $h) . "</tr>\n");
	}

	foreach ($params['data'] as $row) {
		$rowStr = '<tr>';
		foreach ($row as $itemId => $rowItem) {
			$rowStr .= '<td'. ((isset($columns[$itemId]['number-format'])) ? ' class="col'. $itemId .'"' : '') .'>'. $rowItem .'</td>';
		}
		$rowStr .= "</tr>\n";
		excelDisplayRow($rowStr);
	}
	if (!empty($params['footer'])) {
		excelDisplayRow('<tr><td>'.join("</td><td>",$params['footer']) . '</tr>');
	}
	excelDisplayRow('</table>');

	echo '</body></html>';

	$content = ob_get_clean();

	if (!empty($params['excel_file_path'])) {
		file_put_contents($params['excel_file_path'], $content);
	}
	else {
		echo $content;
		die;
	}
}

function displayCSV($params) {
	displayCSVHeader();
	$header = !is_array($params['header'][0]) ? array($params['header']) : $params['header'];
	foreach ($header as $h) {
		$h = array_map('doubleQuotes', $h);
		echo '"'. join('";"', $h) . '"' . "\n";
	}

	foreach ($params['data'] as $row) {
		$row = array_map('doubleQuotes', $row);
		echo '"'. join('";"', $row) . '"' . "\n";
	}
}

function displayCSVHeader() {
	header("Cache-Control: maxage=1");
	header("Pragma: public");
	header("Content-type: application/csv");
	header("Content-Disposition: attachment; filename=spot2d-report-". time() .".csv");
	header("Expires: 0");
}


function doubleQuotes($str) {
	return str_replace('"', '""', $str);
}

function arrayMap($callback, $arr1) {
	$results = array();
	$args = array();
	if(func_num_args() > 2)
	$args = (array) array_shift(array_slice(func_get_args(), 2));
	foreach($arr1 as $key=>$value) {
		$temp = $args;
		array_unshift($temp, $value);
		if(is_array($value)) {
			array_unshift($temp, $callback);
			$results[$key] = call_user_func_array('arrayMap', $temp);
		} else {
			$results[$key] = call_user_func_array($callback, $temp);
		}
	}
	return $results;
}

function excelDisplay($content) {
	excelHeaderDisplay();

	excelDisplayRow($content);

	echo '</body></html>';
	die;
}

function excelDisplayRow($content) {
	if ($GLOBALS['__DELIMITER'] == ',') {

		$content = preg_replace("#(\d)\.(\d)#", "\${1},\${2}", $content);
	}

	echo  iconv("cp1251", "UTF-8", $content);
}

function reportRowDisplay($content) {
	if (defined('EXCEL_REPORT')) {
		excelDisplayRow($content);
	} else {
		echo $content;
	}
}

function displayJSError($message, $needSpecialChars = true) {
	if ($needSpecialChars) {
		$message = htmlspecialchars($message);
	}
	$message = str_replace("\n", '\n', $message);
	echo '<html><head><meta http-equiv="Content-Type" content="text/html; charset=windows-1251"><link rel="STYLESHEET" type="text/css" href="/style.css"></head><body>';
	echo '
	<script>
	parent.document.getElementById("status_div").style.color = "red";
	parent.document.getElementById("status_div").innerHTML = "<textarea rows=6 cols=95 readonly style=\'color:red;border:1px solid red;font-weight: bold\'>'.$message.'</textarea>";
	</script>
	'; die;
}

function displayJSSuccess($message, $backLink = false) {
	echo '<html><head><meta http-equiv="Content-Type" content="text/html; charset=windows-1251"><link rel="STYLESHEET" type="text/css" href="/style.css"></head><body>';
	echo '
	<script>
	parent.document.getElementById("status_div").style.color = "green";
	parent.document.getElementById("status_div").innerHTML = "'.$message.'";';
	if ($backLink != false) {
		echo 'setTimeout("parent.document.location.replace(\''.$backLink.'\')", 1200);';
	}
	echo '</script>
	'; die;
}

function displayError($msg, $backlink = false) {
	global $_sessionData;
	if ($backlink === false) {
		$backlink = 'javascript:location.back()';
	}
	$back = ($_sessionData['lang'] == 'en') ? 'back' : 'назад';
	$msg = '<br/><br/><p style="color:red; font-size:14px; text-align:center; font-weight:bold">'.$msg.'<br/>
	<a href="'.$backlink.'" style="color:blue">'.$back.'</a>
	</p>';
	basicDisplay($msg, 'light.ihtml'); die;
}

function getOrderSuggestion($orderInfo) {
	global $db;

	$mondayDate0 = getMonday(strtotime($orderInfo['c_time']) + 4 * 3600);
	$mondayDate1 = $mondayDate0 - 7 * 24 * 3600;
	$mondayDate2 = $mondayDate0 - 14 * 24 * 3600;
	$mondayDate4 = $mondayDate0 - 28 * 24 * 3600;
	$nextMondayDate = $mondayDate0 + 7 * 24 * 3600;


	$addons['rest0'] = getRestHash($orderInfo['id_group'], date('Y-m-d', $mondayDate0 - 12 * 3600));
	$addons['rest1'] = getRestHash($orderInfo['id_group'], date('Y-m-d', $mondayDate1 - 12 * 3600));
	$addons['rest2'] = getRestHash($orderInfo['id_group'], date('Y-m-d', $mondayDate2 - 12 * 3600));
	$addons['rest4'] = getRestHash($orderInfo['id_group'], date('Y-m-d', $mondayDate4 - 12 * 3600));

	$addons['current_rest'] = getRestHash($orderInfo['id_group'], date('Y-m-d', $nextMondayDate - 12 * 3600));
	$addons['current_rest'] = empty($addons['current_rest']) ? $addons['rest0'] : $addons['current_rest'];

	$addons['orders1'] = getOrderHash($orderInfo['id_group'], date('Y-m-d', $mondayDate1), date('Y-m-d', $mondayDate0));
	$addons['orders2'] = getOrderHash($orderInfo['id_group'], date('Y-m-d', $mondayDate2), date('Y-m-d', $mondayDate1));

	$addons['orders4'] = getOrderHash($orderInfo['id_group'], date('Y-m-d', $mondayDate4), date('Y-m-d', $mondayDate0));

	$addons['moves1'] = getMovesHash($orderInfo['id_group'], date('Y-m-d', $mondayDate1), date('Y-m-d', $mondayDate0));
	$addons['moves2'] = getMovesHash($orderInfo['id_group'], date('Y-m-d', $mondayDate2), date('Y-m-d', $mondayDate1));

	$addons['moves4'] = getMovesHash($orderInfo['id_group'], date('Y-m-d', $mondayDate4), date('Y-m-d', $mondayDate0));

	$production = $db->getCol("select id from production");

	// до заполнить все массивы
	foreach ($addons as $key => $values) {
		foreach ($production as $id) {
			if (!isset($values[$id])) {
				$addons[$key][$id] = 0;
			}
		}
	}

	// считаем все параметры
	foreach ($production as $id) {
		$addons['sales1'][$id] = $addons['rest1'][$id] + $addons['orders1'][$id] + $addons['moves1'][$id] - $addons['rest0'][$id];
		$addons['sales2'][$id] = $addons['rest2'][$id] + $addons['orders2'][$id] + $addons['moves2'][$id] - $addons['rest1'][$id];

		$addons['sales4'][$id] = sprintf("%0.2f", ($addons['rest4'][$id] + $addons['orders4'][$id] + $addons['moves4'][$id] - $addons['rest0'][$id]) / 4);

		$tmp = (int)(($addons['sales1'][$id] + $addons['sales2'][$id]) * 1.5);
		$addons['recomended'][$id] = ($tmp < $addons['rest0'][$id]) ? 0 : $tmp - $addons['rest0'][$id];

		$addons['expected'][$id] = $addons['rest0'][$id] - (int)(($addons['sales1'][$id] + $addons['sales2'][$id]) * 0.5);
	}

	return $addons;

}

function getRestHash($group, $date) {
	global $db;

	$curWeekMonday = date("Y-m-d", strtotime("last Monday", strtotime($date)));
	$sql = 'select id from rest_list where group_id='.$group.' and c_time = (SELECT MAX(c_time) FROM rest_list rl2 WHERE rl2.c_time BETWEEN "'.$curWeekMonday.'" AND "'.$date.'" AND rl2.group_id = rest_list.group_id)';

	$restID = $db->getOne($sql);
	if (empty($restID)) {
		$restValues = array();
	} else {
		$restValues = $db->getAssoc('select id_product, sum(amount) from rest_data where id_rest='.$restID." group by id_product");
	}
	return $restValues;
}


function getOrderHash($group, $dateFrom, $dateTo) {
	global $db;

	$sql = "SELECT order_values.id_production, sum(amount) as cnt
		FROM order_list, order_values, production
		WHERE id_production = production.id AND order_values.id_order = order_list.id
		AND current_status in (8, 10) and current_status=id_status 
		AND erp_date>='$dateFrom' and erp_date < '$dateTo'
		AND id_group = '$group'
		GROUP BY order_values.id_production";
	$orderValues = $db->getAssoc($sql);
	return $orderValues;
}

function getMovesHash($group, $dateFrom, $dateTo) {
	global $db;
	$moves = array();

	// Перемещения
	$sql = "SELECT movement_values.id_production, sum(amount) as cnt
		FROM movement_list, movement_values, production
		where id_production = production.id AND id_movement = movement_list.id 
		AND movement_list.c_time >= '$dateFrom' AND movement_list.c_time < '$dateTo' 
		AND id_to = $group
		GROUP BY  movement_values.id_production";
	$_data = $db->getAll($sql);

	foreach ($_data as $row) {
		$moves[$row['id_production']] = $row['cnt'];
	}

	$sql = "SELECT movement_values.id_production, sum(amount) as cnt
		FROM movement_list, movement_values, production
		where id_production = production.id AND id_movement = movement_list.id 
		AND movement_list.c_time >= '$dateFrom' AND movement_list.c_time < '$dateTo' 
		AND id_from = $group
		GROUP BY  movement_values.id_production";
	$_data = $db->getAll($sql);
	foreach ($_data as $row) {
		$moves[$row['id_production']] -= $row['cnt'];
	}
	return $moves;

}

function translatetext($text) {
	$LCLANG = $GLOBALS['db']->getAssoc('select concat("{", keyword,"}") as keyw, value_'.$_SESSION['lang']." from lang_keywords");
	return strtr($text, $LCLANG);
}

function getTemplateHandle() {
	require_once FS_LIBS."templates/class.template.php";

	$tpl = new Template_Lite();
	$tpl->template_dir = FS_TEMPLATES;
	$tpl->reserved_template_varname = "tpl";
	$tpl->compile_dir = FS_TEMPLATES.'compiled/';
	$tpl->cache = false;

	return $tpl;
} // end getTemplateHandle

function authUser($login, $password, $normalLogin = true) {

	global $db;
	global $_sessionData;

	$auth = $db->getRow("select * from users where (login='".mysql_escape_string($login)."') AND (password='".mysql_escape_string(md5($password))."')");

	// Проверка на то, есть ли такой пользователь в системе
	if (empty($auth['id'])) {
		return false;
	} else {

		// Заносиим в сессию данные о пользователе
		$_sessionData['auth'] = 'yes';
		$_sessionData["auth_id"] = (int) $auth["id"];
		$_sessionData["auth_name"] = $auth["name"];
		$_sessionData["auth_login"] = $auth["login"];
		$_sessionData["auth_group"] = $auth["id_group"];
		$_sessionData["auth_role"] = $auth["id_dbrole"];
		$_sessionData["auth_can_upload"] = $auth["can_upload"];
		$_sessionData["lang"] = $auth["lang"];

		$_sessionData['DB_ALLOWED_IDS']['users'] = array($auth["id"]);

		if ($normalLogin) {
			$res = $db->query("insert into last_logins (user_id, last_ip, last_date) values (".$_sessionData["auth_id"].", '".$_SERVER['REMOTE_ADDR']."', NOW())");
			if (PEAR::isError($res) && ($res->code == -27)) {
				define('IS_READONLY', true);
				$_sessionData['IS_READONLY'] = true;
			}
		}

		if (defined('FS_IPB') && $normalLogin) {

			// IPB
			require_once FS_IPB.'init.php';
			require_once FS_IPB."sources/ipsclass.php";
			require_once KERNEL_PATH."class_converge.php";
			require_once FS_IPB."sources/classes/class_session.php";
			require_once FS_IPB.'conf_global.php';
			include_once FS_IPB.'sources/loginauth/login_core.php';
			include_once FS_IPB.'sources/loginauth/external/conf.php';
			include_once FS_IPB.'sources/loginauth/external/auth.php';

			$ipsclass       = new ipsclass();
			$ipsclass->vars = $INFO;
			$ipsclass->init_db_connection();
			$ipsclass->converge = new class_converge( $ipsclass->DB );

			$ipsclass->sess             =  new session();
			$ipsclass->sess->ipsclass   =& $ipsclass;

			$ipb = new login_method();
			$ipb->login_conf = $LOGIN_CONF;
			$ipb->ipsclass = $ipsclass;
			$ipb->allow_create = true;
			$ipb->is_scj = true;
			$ipb->authenticate($login, $password);

			setcookie("session_id", $ipb->member['session_id']);
			if(in_array($ipb->return_code, array('SUCCESS', 'ADD'))) {
				setcookie("member_id", $ipb->member['id']);
				setcookie("pass_hash", $ipb->member['member_login_key']);
				setcookie("coppa", "0");
				setcookie("anonlogin", "");
			}
		}

		return true;


	}

}

function displayJSMessage($status, $message) {
	if ($success) {
		displayJSMessage($message);
	} else {
		displayJSError($message);
	}
}


function getFilterCaptions($what, $id) {

	global $db;

	switch ($what) {
		case 'scu': {
			$values = $db->getCol("select caption_".LANG." from production where id in (".join(",", $id).") ");
		} break;

		// Справочные типы и категории
		case 'category': {
			$values = $db->getCol("select caption_".LANG." from tt_categories where id in (".join(",", $id).") ");
		} break;
		case 'tt_type':
		case 'type':	{
			$values = $db->getCol("select caption_".LANG." from tt_types where id in (".join(",", $id).") ");
		} break;

		case 'tt_custom_type': {
			$values = $db->getCol("select caption_".LANG." from tt_custom_types where id in (".join(",", $id).") ");
		} break;


		case 'client': {
			$values = $db->getCol("select client_name from clients where id_client in (".join(",", $id).") ");
		} break;

		case 'tt_max':
		case 'tt': {
			$values = $db->getCol("select tt_name from client_tt where id_client_tt in (".join(",", $id).") ");
		} break;

		case 'itt': {
			$values = $db->getCol("select tt_name from client_itt where id in (".join(",", $id).") ");
		} break;

		case 'ta':
		case 'id_merchant':
		case 'taships': {
			$values = $db->getCol("select ta_name from client_ta where client_ta_id in (".join(",", $id).") ");
		} break;

		case "global_netw":
		case "netw": {
			$values = $db->getCol("select netw_caption from netw_list where netw_id in (".join(",", $id).") ");
		} break;

		case "subnetw": {
			$values = $db->getCol("select subnet_caption from subnet_list where subnet_id in (".join(",", $id).") ");
		} break;


		case "manager_pm": {
			$values = $db->getCol("select name from manager_regional where id in (".join(",", $id).") ");
		} break;

		case "manager_am": {
			$values = $db->getCol("select name from manager_provincial where id in (".join(",", $id).") ");
		} break;


		case 'distributor': {
			$values = $db->getCol("select group_name from groups where id in (".join(",", $id).") ");
		} break;

		case 'ta_type': {
			$values = $db->getCol("select caption from ta_types where id in (".join(",", $id).") ");
		} break;

		case 'id_supervisor': {
			$values = $db->getCol("select caption from client_supervisors where id in (".join(",", $id).") ");
		} break;

		default:
			$values = array_diff($id, array(-1));
	}	

	$values = array_map('toTranslit', $values);	

	return $values;
}

function getJsGridContent($params, $token) {
	//getJsGridContent($params['header'], &$params['data'], $params['footer'], $params['dynamic'], $params['columns'], $key, empty($params['filters']) ? 100 : 160);
	//getJsGridContent($header, $data, $footer, $headerDynamic, $columnsData, $token, $sysHeight)
	//ob_start("ob_gzhandler", 1);

	$style = isset($params['style']) ? $params['style'] : '';
	$header = isset($params['header']) ? $params['header'] : array();
	$data = isset($params['data']) ? $params['data'] : array();
	$footer = isset($params['footer']) ? (array) $params['footer'] : array();
	$headerDynamic = isset($params['dynamic']) ? $params['dynamic'] : array();
	$columnsData = isset($params['columns']) ? $params['columns'] : array();
	$sysHeight = empty($params['filters']) ? 100 : 160;

	$tpl = getTemplateHandle();
	//

	$idDynamicHeader = 0;
	$headerColSpans = array();
	if (!empty($headerDynamic)) {
		if (!is_array($header[0])) {
			$header = array($header);
		}
		$headerDynamicCaptions = array();
		foreach ($headerDynamic as $headerDynamicItem) {
			$headerDynamicCaptions[] = $headerDynamicItem['caption'];
			$headerColSpans[] = $headerDynamicItem['colspan'];
			if ($headerDynamicItem['colspan'] > 1) {
				for ($i = 1; $i < $headerDynamicItem['colspan']; $i++) {
					$headerDynamicCaptions[] = '';
					$headerColSpans[] = 0;
				}
			}
		}
		$idDynamicHeader = count($header);
		$header[] = $headerDynamicCaptions;
	}

	$header = !is_array($header[0]) ? array($header) : $header;
	foreach ($header as $k => $h) {
		$header[$k] = array_map('escapeQuotes', $header[$k]);
	}
	$footer = array_map('escapeQuotes', $footer);
	foreach ($data as $k => $row) {
		$data[$k] = '"' . strtr  (join("<|%|>", $row), array('"' => '\"', "\n" => '', "<|%|>" => '", "')).'"';
	}

	$fixedCnt = 0;
	foreach($columnsData as $row) {
		if (!empty($row['fixed'])) {
			$fixedCnt++;
		}
	}

	$tpl->assign('style', $style);
	$tpl->assign('header', $header);
	$tpl->assign('cntHeaders', count($header));
	$tpl->assign('idDynamicHeader', $idDynamicHeader);
	$tpl->assign('headerColSpans', $headerColSpans);
	$tpl->assign('headerDynamic', $headerDynamic);
	$tpl->assign('footer', $footer);
	$tpl->assign('data', $data);
	$tpl->assign('columnsData', $columnsData);
	$tpl->assign('cntColumns', count($header[0]));
	$tpl->assign('cntRows', count($data));
	$tpl->assign('fixedCnt', $fixedCnt);
	$tpl->assign('sysHeight', $sysHeight);

	$fp = fopen(FS_ROOT."reports/json/$token.js", "w");
	fputs($fp, $tpl->fetch("js_grid.ihtml"));
	fclose($fp);
}

function displayGraph ($params, $token) {
	include('FusionClass/FusionCharts_Gen.php');


	$captionCol = isset($params['graph']['caption']) ? (int)$params['graph']['caption'] : 0;
	$columnsData = isset($params['columns']) ? $params['columns'] : array();
	$fixedCnt = 0;
	foreach($columnsData as $row) {
		if (!empty($row['fixed'])) {
			$fixedCnt++;
		}
	}
	if (empty($fixedCnt)) {
		$fixedCnt = 1;
	}

	if ((count($params['header']) - $fixedCnt > 50) OR count($params['data']) > 50) {
		return false;
	}

	if (empty($params['axis_x'])) {
		$FC =& displayGraph2D($params, $captionCol, $fixedCnt);
	} else {
		$FC =& displayGraph3D($params, $captionCol, $fixedCnt);
	}

	$fp = fopen(FS_ROOT."reports/json/$token.xml", "w");
	fputs($fp, $FC->getXML());
	fclose($fp);
}

function &displayGraph2D ($params, $captionCol, $fixedCnt) {
	$FC = new FusionCharts("Bar2D","100%","100%");
	$FC->setSWFPath("/js/FusionCharts/");

	$strParam="caption={$params['title']};xAxisName={$params['axis_x']};";
	$FC->setChartParams($strParam);


	foreach ($params['data'] as $dataRow) {
		$label = strip_tags($dataRow[$captionCol]);
		$val = (float) str_replace(' ', '', $dataRow[$fixedCnt]);
		$FC->addChartData("{$val}","label={$label}");
	}

	return $FC;
}

function &displayGraph3D ($params, $captionCol, $fixedCnt) {
	$FC = new FusionCharts("MSCombi3D","100%","100%");
	$FC->setSWFPath("/js/FusionCharts/");

	$strParam="caption={$params['title']};xAxisName={$params['axis_x']};";
	$FC->setChartParams($strParam);

	foreach ($params['header'] as $headerId => $headerItem) {
		if ($headerId) {
			$FC->addCategory(strip_tags($headerItem));
		}
	}

	foreach ($params['data'] as $dataRow) {
		foreach ($dataRow as $dataRowId => $dataRowItem) {
			if ($dataRowId == $captionCol) {
				$FC->addDataset(strip_tags($dataRowItem));
			} elseif ($dataRowId >=$fixedCnt) {
				$value = (float) str_replace(' ', '', $dataRowItem);
				$FC->addChartData($value);
			}
		}
	}

	return $FC;
}

function displayReport($params) {
	global $db, $_sessionData;

	$params['axis_x'] = isset($params['axis_x']) ? $params['axis_x'] : '';
	$params['axis_y'] = isset($params['axis_y']) ? $params['axis_y'] : '';

	$key = md5(mt_rand());

	$sql = "insert into reports (id_user, created, token, caption, shown, filters, report_from, report_to, excel, url, ident, axis_x, axis_y, id_group) values (
	{$_sessionData['auth_id']},
	NOW(),
	'$key',
	'".$db->escape($params['title'])."',
	'".$db->escape($params['shown'])."',
	'".$db->escape($params['filters'])."',
	'{$params['report_from']}',
	'{$params['report_to']}',
	'{$params['excel']}',
	'".$db->escape($params['url'])."',
	'{$params['ident']}',
	'".$db->escape($params['axis_x'])."',
	'".$db->escape($params['axis_y'])."',
	". $_sessionData['auth_group'] ."
	)";
	$db->query($sql);
	$id = mysql_insert_id($db->connection);

	//session_commit();

	if (!empty($params['graph'])) {
		//displayGraph($params, $key);
	}

	if ($params['excel'] || empty($id)) {
		if (empty($params['excel2csv'])) {
			displayExcelGrid($params);
		} else {
			displayCSV($params);
		}
		die;
	} else {
		getJsGridContent($params, $key);
		//
		$params['excel_file_path'] = FS_ROOT."reports/json/$key.xls";
		displayExcelGrid($params);

		if (isset($params['target']) && ($params['target'] == '_self')) {
			echo "<script>document.location.replace('/reports/display/$id/$key');</script>";
		} else {
			//echo "<script>parent.openFullWindow('/reports/display/{$id}/{$key}');</script>";
			echo "<script>document.location.replace('/reports/display/$id/$key');</script>";
		}
		die;
	}
}

function escapeQuotes($str) {
	return strtr  ($str, array('"' => '\"', "\n" => ''));
}

function closeNotShippedTT($shipDateFrom, $dateClose, $dListIds = null) {
	global $db;
	$shipDateFrom = date('Y-m-d', strtotime($shipDateFrom));
	$dateClose = date('Y-m-d', strtotime($dateClose));
	if (!empty($dListIds)) {
		$dListIds = array_map('intval', $dListIds);
	}
	//
	$sql = 'SELECT DISTINCT(tt_s.id_client_tt)
			FROM scj_ships s 
			JOIN scj_tt_ships tt_s ON s.ships_id = tt_s.scj_ships_id 
			WHERE s.date > "'.$shipDateFrom.'"';
	if (!empty($dListIds)) {
		$sql .= ' AND s.group_id IN ('.implode(', ', $dListIds).')';
	}
	$clientTT = $db->getCol($sql);
	//
	$sql = 'UPDATE client_tt SET date_close="'.$dateClose.'", is_closed=1 WHERE is_closed=0 AND date_start < "'.$shipDateFrom.'"';
	if (!empty($clientTT)) {
		$sql .= ' AND id_client_tt NOT IN ('.implode(', ', $clientTT).')';
	}
	if (!empty($dListIds)) {
		$sql .= ' AND id_group IN ('.implode(', ', $dListIds).')';
	}
	$db->query($sql);

	//закрываем клиентов
	if (empty($dListIds)) {
		$dListIds = $db->getCol('SELECT id FROM groups');
	}
	foreach ($dListIds as $dId) {
		clientsStatusCheck($dId);
	}
}

function getLastBackupData() {
	$backupItem = false;
	$backupsList = array();
	if ($handle = @opendir(BACKUP_DIR)) {
		while (false !== ($file = readdir($handle))) {
			if (preg_match('/^mysql/', $file)) {
				$backupsList[] = $file;
			}
		}
	}
	if (!empty($backupsList)) {
		rsort($backupsList);
		$backupItem['name'] = current($backupsList);

		$size = filesize(BACKUP_DIR.$backupItem['name']);
		$backupItem['size'] = round($size/pow(2, 20), 1).' Mb';

		$backupItem['ctime'] = date('Y-m-d h:i:s', filectime(BACKUP_DIR.$backupItem['name']));

	}

	return $backupItem;
}

function escapeSqlData($data) {
	global $db;
	if (!is_array($data)) {
		return $db->escape($data);
	}
	foreach ($data as $k => $v) {
		$data[$k] = $db->escape($v);
	}
	return $data;
}

function isValidUserPassword($password, $login) {
	global $db;
	$password = trim($password);
	$login = trim($login);
	//
	$isKnownPassword = $db->getOne('SELECT COUNT(*) FROM ddc_common.known_passwords WHERE password="'.$db->escape($password).'"');
	if (strlen($password) <= 7) {
		$error = 'Пароль должен содержать более 7 символов';
	}
	elseif (preg_match("/[0-9]{1}.*[0-9]{1}/", $password) == 0) {
		$error = 'Пароль должен содержать не менее 2х цифр';
	}
	elseif ($password == $login) {
		$error = 'Пароль не должен совпадать с логином';
	}
	elseif ($isKnownPassword) {
		$error = 'Данный пароль содержится в базе самых популярных паролей. Введите другой пароль.';
	}
	if (!empty($error)) {
		return $error;
	}
	return true;
}

function flushContent() {
	global $_sessionData;
	if (isset($_sessionData['IS_READONLY'])) {
		return;
	}
	echo str_repeat('  ', 15000);
	ob_end_flush();
	ob_flush();
	flush();
}

function yearweekToLimits($yw) {
	$jan1st = mktime(0, 0, 0, 1, 1, floor($yw / 100));
	$sw = $yw % 100 - 2;
	$ew = $sw + 1;
	$dow = idate('w', $jan1st);
	if($dow >= 5 || $dow <= 0) {
		++$sw;
		++$ew;
	}
	elseif($dow === 1)
	++$sw;
	if($sw >= 0)
	$sw = '+'.$sw;
	if($ew >= 0)
	$ew = '+'.$ew;
	return date("d/m/Y", strtotime($sw.' week mon', $jan1st)) .' - '. date("d/m/Y", strtotime($ew.' week sun', $jan1st));
}

function monthToName($mm) {
	return date("m/Y", strtotime($mm.'-01'));
}

function dayToName($mm) {
	return date("d/m/Y", strtotime($mm));
}

function sendMail($email, $subject, $body, $files = array()) {
	require_once('Mail.php');
	require_once('Mail/mime.php');
	//
	$crlf = "\n";
	$hdrs = array(
	'From'    => 'info@ddc.com.ua',
	'Subject' => $subject
	);

	$mime = new Mail_mime($crlf);
	$mime->setTXTBody($body);

	foreach ($files as $file) {
		$mime->addAttachment($file, 'application/octet-stream', $file, true);
	}

	$body = $mime->get(array('text_charset' => 'windows-1251', 'head_charset' => 'windows-1251', 'html_charset' => 'windows-1251'));
	$hdrs = $mime->headers($hdrs);

	$mail =& Mail::factory('mail');
	$mail->send($email, $hdrs, $body);
}

function logImportData($fileType, $isAuto, $isSuccess, $message = '', $fileName = null, $md5file = '') {
	global $db, $_sessionData;
	$isAuto = (int)$isAuto;
	$isSuccess = (int)$isSuccess;
	//
	if (!empty($fileName) && file_exists($fileName)) {
		$md5file = md5_file($fileName);
		if (is_writable(FS_IMPORTED_FILES)) {
			copy($fileName, FS_IMPORTED_FILES.$md5file.'.csv');
		}
	}
	$db->query("insert into import_log (c_date, id_group, id_user, filetype, success, ip, error, md5file, is_auto) values (NOW(), {$_sessionData['current_d']}, {$_sessionData['auth_id']}, '".$db->escape($fileType)."', $isSuccess, '{$_SERVER['REMOTE_ADDR']}', '".$db->escape($message)."', '$md5file', $isAuto)");
}

function _getText($text) {
	global $_language;
	if (LANG == 'en') {
		$text = toTranslit($text);
	}
	$k = array_merge(array_keys($_language), array('{','}'));
	$v = array_merge(array_values($_language), array('',''));
	return str_replace($k,$v,$text);
}
?>
