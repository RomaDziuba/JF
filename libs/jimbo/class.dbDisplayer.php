<?php
require_once dirname(__FILE__).'/events/EventDispatcher.php';
require_once dirname(__FILE__).'/class.EventJimbo.php';

/**
* Form's displayer
*
* Class for read XML table definition and save it as array
*
* @autor Alexander Voytsekhovskyy <young@php.net>;
* @version 1.2
*/


class dbDisplayer extends EventDispatcher 
{

	var $knownActions = array('child', 'edit', 'remove', 'about', 'parent', 'excel');
	var $generalActions = array('list', 'insert', 'child', 'parent', 'excel');
	var $tblAction;

	private static $tpl;

	function dbDisplayer(&$tblAction, &$tpl = null) {
		$this->tblAction = &$tblAction;

		self::$tpl = $tpl;
		
		if (!is_null(self::$tpl)) {
			include dirname(__FILE__).'/'.$this->tblAction->getLangFile();
			self::$tpl->assign("lang", $dbAdminMessages);
		}
	}

	function performDisplay($action) {

		if (isset($_GET['ID'])) {
			if (!$this->tblAction->loadRow(mysql_escape_string($_GET['ID']))) {
				$action = 'error';
				$errorType = 'invalidRow';
			}
			$this->currentID = (int)$_GET['ID'];
			$GLOBALS['currentID'] = (int)$_GET['ID'];
		}

		$tableDefinition = $this->tblAction->tableDefinition;

		if (!empty($tableDefinition->attributes['customHandler'])) {
			include_once $this->tblAction->getOption('handlers_path').$tableDefinition->attributes['customHandler'].'.php';
			$this->customHandler = new customTableHandler();
			$this->customHandler->params = $this->tblAction->getOption('handler_params');
			$info['action'] = $action;
			$result = '';
			if ($this->customHandler->display($info, $result)) {
				return $result;
			}
		}


		switch ($action) {
			case "insert":
			case 'edit':
			case 'remove':
			case 'info': {
				$content = $this->displayForm($action);
			} break;
			case 'excel':
				$content = $this->getExcel();
				break;
			case "error": {
				$content = $this->displayErrorMessage($errorType);
			} break;
			default: {

				$content = $this->displayList();
			}
		}

		return $content;
	}

	
	public static function &getTemplateInstance($tplRoot = false)
	{
	    require_once dirname(__FILE__)."/../templates/class.template.php";
	    
	    /*
	    if(!is_null(self::$tpl) && !$tplRoot) {
            return self::$tpl;
        }
        
        if(!$tplRoot) {
            $tplRoot = realpath(dirname(__FILE__).'/../../templates');
        }
        
        if(!is_dir($tplRoot)) {
            throw new Exception('Not found template directory');
        }
        
        $tpl = new Template_Lite();
        $tpl->template_dir = $tplRoot;
        $tpl->compile_dir = $tplRoot.'/compiled/';
        $tpl->force_compile = true;
        $tpl->cache = false;
        $tpl->reserved_template_varname = "tpl";
        
        if (is_null(self::$tpl)) {
            self::$tpl = $tpl;
        }
        
        return $tpl;
        */
	    
	    if (!is_null(self::$tpl)) {
	        if ($tplRoot) {
	            self::$tpl->template_dir = $tplRoot;
                self::$tpl->compile_dir = $tplRoot.'/compiled/';
	        }

            return self::$tpl;
	    }
	    
	    if (!$tplRoot) {
            $tplRoot = realpath(dirname(__FILE__).'/../../templates');
	    }
	    
        if (!is_dir($tplRoot)) {
            throw new Exception('Not found template directory: '.$tplRoot);
        }
	    
		$tpl = new Template_Lite();
		$tpl->template_dir = $tplRoot;
		$tpl->compile_dir = $tplRoot.'/compiled/';
		$tpl->force_compile = true;
		$tpl->cache = false;
		$tpl->reserved_template_varname = "tpl";

		self::$tpl = $tpl;
		
		return self::$tpl;
	} // end getTemplateInstance

	function displayError($message) {
	    throw new Exception($message);
        
		$tpl = self::getTemplateInstance();
		$tpl->assign('message', $message);
		return $tpl->fetch('error.ihtml');
	}

	function getExcel() {
		$tableDefinition =& $this->tblAction->tableDefinition;
		$tblName = $tableDefinition->name;
		$tblCharset = $tableDefinition->charset;
		$needConvert = strtolower($tblCharset) != 'windows-1251';

		$fieldsData = array();
		foreach ($tableDefinition->fields as $field) {
		    // FIXME:
			$fieldsData[$field->name] = $needConvert ? iconv($tblCharset, 'WINDOWS-1251', $field->attributes['caption']) : $field->attributes['caption'];
		}
		$tableData = $this->tblAction->loadTableData('excel');

		$tmpFname = tempnam(sys_get_temp_dir(), 'jimbo_export');
		$fp = fopen($tmpFname, 'w');

		// выводим хидер
		$line = array();
		foreach ($fieldsData as $fieldName => $fieldValue) {
			// Эксель бьется когда у него первый филд равен ID
			if (strtolower($fieldValue) == 'id') {
				$fieldValue = ' '.$fieldValue;
			}
			$line[] = $fieldValue;
		}
		$line = array_map('strip_tags', $line);
		fputcsv($fp, $line, ';');

		// выводим данные
		foreach ($tableData as $item) {
			$line = array();
			foreach ($fieldsData as $name => $caption) {
				$line[] = $needConvert ? iconv($tblCharset, 'WINDOWS-1251', $item[$name]) : $item[$name];
			}
			$line = array_map('strip_tags', $line);
			fputcsv($fp, $line, ';');
		}
		fclose($fp);

		// отдаем файл
		$fp = fopen($tmpFname, 'r');
		header('Content-Type: text/comma-separated-values');
		header('Content-Length: ' . filesize($tmpFname));
		header('Content-Disposition: attachment; filename='.$tblName.'.csv');
		header("Cache-Control: maxage=1");
		header("Pragma: public");
		fpassthru($fp);
		fclose($fp);
		unlink($tmpFname);
		exit();
	}

	function displayList() {
		$_sessionData = &$this->tblAction->sessionData;

		$tableDefinition =& $this->tblAction->tableDefinition;
		$tblName = $tableDefinition->name;
		$tableData = $this->tblAction->loadTableData();
		$tpl = self::getTemplateInstance();

		if (isset($this->customHandler) && method_exists($this->customHandler, 'modifyTableData')) {
			if (!empty($tableData)) {
				$tableData = $this->customHandler->modifyTableData($tableData);
			}
		}

		$info = array();

		if (is_file($tpl->template_dir."/".$tblName."_list.ihtml")) {
			$customTemplate = true;
			$tplFile = $tblName."_list.ihtml";
		} else {
			$customTemplate = false;
			$tplFile = 'dba_list.ihtml';
		}

		$info['grouped'] = !empty($tableDefinition->grouped);

		// название таблицы может не совпадать с именем файла
		$tblAlias =  isset($tableDefinition->attributes['alias']) ? $tableDefinition->attributes['alias'] : $tblName;

		// Наследуем родительский заголовок, если он есть
		$relCaption = isset($tableDefinition->actions['parent']) && isset($_sessionData['DB_'.$tblAlias."_caption"]) ? $relCaption = $_sessionData['DB_'.$tblAlias."_caption"] : '';

		$info['caption'] = empty($relCaption) ? $tableDefinition->actions['list']['caption'] : $relCaption.' / '.$tableDefinition->actions['list']['caption'];
		$info['backlink'] = isset($tableDefinition->attributes['backLink']) ? $tableDefinition->attributes['backLink'] : '';
		$info['parent'] = isset($tableDefinition->actions['parent']['caption']) ? $tableDefinition->actions['parent']['caption'] : '';

        // Если нет парента то не отображаем ссылку "вверх"
        if ( empty($_sessionData['DB__'.$tblAlias.'__PARENT']) ) {
            unset($info['parent']);
        }

		$_SERVER['QUERY_STRING'] = preg_replace("/&?order=[A-Za-z0-9_]+/", '', @$_SERVER['QUERY_STRING']);

		$info['baseurl'] = $this->tblAction->getOption('http_base');
		$info['query'] = $_SERVER['QUERY_STRING'];
		$info['totalRows'] = $this->tblAction->totalRows;
		$info['rowsPerPage'] = $this->tblAction->rowsPerPage;
		$info['limitOptions'] = array(20 => 20, 50 => 50, 100 => 100, '1000' => 'all');

		// Заголовок таблицы
		$info['sorting'] = array(
		'field' => $this->tblAction->orderField,
		'direction' => $this->tblAction->orderDirection
		);

		foreach ($tableDefinition->fields as $key => $field) {
			if ($field->getAttribute('hide')) {
				continue;
			}
			$info['fields'][] = $field->attributes;
		} // END: заголовок



		$tpl->assign('filters', $this->addListFilters());

		// Обработка каждой строки
		$numerator = $this->tblAction->currentRowN;
		$data = array();
		$subtotals = array();
		$subtotals_group = array();
		$pageIDs = array();

		if ($groupBy = $this->tblAction->tableDefinition->getAttribute('groupBy')) {
			$_group_field = '';
		}

		$isHiddenFields = false;
		$primaryKey = $tableDefinition->getAttribute('primaryKey');
		$fieldInputs = array();

		if ($tableDefinition->getAttribute('fastAdd')) {
			$this->tblAction->loadForeignKeys();

			foreach ($tableDefinition->fields as $field) {
				if ($field->getAttribute('hide')) {
					$isHiddenFields = true;
				}

				if (($field->name == $primaryKey) ||  ($field->getAttribute('type') == 'sql')) {
					$fieldInputs[] = '';
				} else {
					$value = ($tmp = $field->getAttribute('default')) ? $this->prepareValue($tmp) : NULL;
					$fieldInputs[] = $field->getEditInput($value, true);
				}
			}
		}
		 
        $info['generalActions'] = $this->getGeneralActions();
		
		foreach($tableData as $tdRow) {
			// HotFix: для полей которые могут от этого зависеть
			$ID = $tdRow[$tableDefinition->getAttribute('primaryKey')];
			$GLOBALS['currentRow'] = $tdRow;
			$GLOBALS['currentID'] = $ID;

			$line = array('id' => $ID);
			$pageIDs[] = $ID;

			if (isset($this->customHandler) && method_exists($this->customHandler, 'getAlowedActions')) {
				$alowedActions = $this->customHandler->getAlowedActions(array_keys($tableDefinition->actions), $tdRow);
			} else {
				$alowedActions = array_keys($tableDefinition->actions);
			}

			if (isset($_group_field)) {
				if ($_group_field != $tdRow['_group_field']) {
					if (!empty($_group_field)) {
						if (array_sum($subtotals_group) != 0) {
							$line['_group_total'] = $subtotals_group;
						}
						$subtotals_group = array();
					}
					$line['_group_caption'] = $tdRow['_group_field'];
					$_group_field = $tdRow['_group_field'];
				}
				$line['relation'] = $tdRow['_group_field'];
			}


			foreach ($tableDefinition->fields as $field) {

				if ($field->getAttribute('hide')) {
					continue;
				}

				// Поле отображаем само себя
				$displayValue =  ($field->attributes['type'] == 'numerator') ? ++$numerator : $field->displayValue($tdRow[$field->name]);

				// Поле может быть ссылкой
				if (!empty($field->attributes['clicable'])) {
					if ($field->attributes['clicable'] == 'info') {
						$displayValue = '<a HREF="#" onClick=\'openWindow("?action=info&ID='.$ID.'&popup=true"); return false;\' class="db_rellink">'.$displayValue.'</a>';
					} elseif (isset($tableDefinition->actions['child'])) {
						$displayValue = '<a href="?action=child&ID='.$ID.'" class="db_rellink" title="'.$tableDefinition->actions['child']['caption'].'">'.$displayValue.'</a>';
					}
				}

				$item = array();
				$item['value'] = $displayValue;

				if ($align = $field->getAttribute('align')) {
					$item['align'] = $align;
				}

				if ($sprintf = $field->getAttribute('sprintf')) {
					$item['value'] = sprintf($sprintf, $item['value']);
				}


				if ($field->getAttribute('subtotal')) {
					if (!isset($subtotals[$field->name])) {
						$subtotals[$field->name] = 0;
					}
					$subtotals[$field->name] += $item['value'];
					if (isset($_group_field)) {
						if (!isset($subtotals_group[$field->name])) {
							$subtotals_group[$field->name] = 0;
						}
						$subtotals_group[$field->name] += $item['value'];
					}
				} else {
					$subtotals[$field->name] = '';
					if (isset($_group_field)) {
						$subtotals_group[$field->name] = '';
					}
				}

				$line['data'][] = $item;

				if ($customTemplate) {
					//$tpl->setVar('VALUE_'.strtoupper($field->name), $displayValue);
				}
			}

			// Обрабатываем кнопки
			// потенциально можно запретить некоторые операции

			$line['actions'] = array();
			$line['action_lists'] = array();
			foreach ($tableDefinition->actions as $type => $action) {
				if (in_array($type, $this->generalActions)) continue;
				if (!in_array($type, $alowedActions)) continue;

				$external = in_array($type, array('edit', 'remove', 'info')) || isset($action['ext']);
				$target = isset($action['target']) ? 'target="'.$action['target'].'"' : '';
				$link = isset($action['link']) ? str_replace("%ID%", $ID, @html_entity_decode ($action['link'], ENT_QUOTES, 'UTF-8')) : '?action='.$type.'&ID='.$ID;
				$src = empty($action['src']) ? $this->tblAction->getOption('engine_http_base').'images/dbadmin_'.$type.'.gif' : $action['src'];

				if (isset($action['fullscreen'])) {
					$popupFunction = 'openFullWindow';
				} else {
					$popupFunction = 'openWindow';
				}

				$item = array(
                    'src' => $src,
                    'alt' => $action['caption'],
                    'href' => $link,
                    'addon' => $external ? ' target="_blank" ' : '',
                    'target' => $target,
                    'popup' => $external,
                    'popupFunction' => $popupFunction,
				    'js' => isset($action['js']) ? $action['js'] : false, 
                );
				
                $lineKey = (isset($action['view']) && $action['view'] == "list") ? 'action_lists' : 'actions';
				

				$line[$lineKey][] = $item;
			}

			$data[] = $line;
		};

		$_sessionData['DB_ALLOWED_IDS'][$this->tblAction->alias] = array_unique(array_merge($pageIDs, (array)@$_sessionData['DB_ALLOWED_IDS'][$this->tblAction->alias]));


		if (isset($this->customHandler) && method_exists($this->customHandler, 'getAlowedActions')) {
			$alowedActions = $this->customHandler->getAlowedActions(array_keys($tableDefinition->actions));
		} else {
			$alowedActions = array_keys($tableDefinition->actions);
		}

		$info['insert'] = isset($tableDefinition->actions['insert']['caption']) && (in_array('insert', $alowedActions)) ? $tableDefinition->actions['insert']['caption'] : '';
		$info['excel'] = isset($tableDefinition->actions['excel']['caption']) && (in_array('excel', $alowedActions)) ? $tableDefinition->actions['excel']['caption'] : '';

		$_GET['pageID'] = 3;
		
		// Навигация по страницам
		if ( ($forPage = $this->tblAction->rowsPerPage) && ($this->tblAction->totalRows > $forPage)) {
			require_once 'Pager/Pager.php';
			
			$params = array(
			'totalItems' => $this->tblAction->totalRows,
			'mode'       => 'Sliding',
			'perPage'    => $forPage,
			'delta'      => 3,
			'linkClass' => 'page',
			'spacesBeforeSeparator' => 1,
			'spacesAfterSeparator' => 1,
			'append' => false,
			'path' => substr($this->tblAction->getHttpPath(), 0, strlen($this->tblAction->getHttpPath()) - 1),
			'fileName' => '?pageID=%d',
			'currentPage' => $this->tblAction->pageID
			);
			
			$pager = Pager::factory($params);
			$links = $pager->getLinks();
			$info['pager'] = $links['all'];
		}
		

		if (!empty($tableDefinition->grouped)) {
			$gSelect = '
			<select class="thin" style="width:160px" name="gSelect" id="gSelect"><option value=0>Select action:';
			foreach ($tableDefinition->grouped as $item) {
			    $item['link'] = !isset($item['link']) ? '' : $item['link'];
				$gSelect .= '<option value="'.$item['link'].'">&nbsp;--&nbsp;'.$item['caption'];
			}
			$gSelect .= '</select>
			<input type="button" onClick="gorupSubmit();" class="sbutton" value="OK" style="vertical-align: middle">
			';
			$info['grouped'] = $gSelect;
		}

		include dirname(__FILE__).'/'.$this->tblAction->getLangFile();
		$tpl->assign('lang', $dbAdminMessages);

		if (isset($_group_field)) {
			$info['subtotals'] = $subtotals_group;
		} elseif (array_sum($subtotals) != 0) {
			$info['subtotals'] = $subtotals;
		}

		if (!$isHiddenFields && ($tableDefinition->getAttribute('fastAdd'))) {
			$info['token'] = $this->tblAction->createInsertToken();
			$info['fastAdd'] = true;
		}

		if (isset($this->customHandler) && method_exists($this->customHandler, 'highlightlist')) {
			$info['highlight'] = $this->customHandler->highlightlist($pageIDs);
		}
		$info['fieldInputs'] = $fieldInputs;

		if (isset($tableDefinition->attributes['filter'])) {
			$info['filter'] = $tableDefinition->attributes['filter'];
		}

		// to be able change data from the code generating event with reference to data
        $obj = array(
            'info' => &$info,
            'data' => &$data,
        );
        
        $event = new EventJimbo(EventJimbo::PREDISPLAY_LIST, $obj);
        $this->dispatchEvent($event);
        
        $tpl->assign($obj);
		return trim($tpl->fetch($tplFile));
	}


	/**
	 * Returns a list of actions for the table. That displays at the top of the table
	 */
	private function getGeneralActions()
	{
	    $generalActions = array();

	    foreach ($this->tblAction->tableDefinition->actions as $type => $action) {
		    if( !isset($action['view']) || $action['view'] != 'top' ) {
		        continue;
		    }
		    
		    $link = isset($action['link']) ? $action['link'] : '?action='.$type;
		    
		    $item = array(
				'caption' => $action['caption'],
				'href' => $link,
				'js' => isset($action['js']) ? $action['js'] : false, 
            );
		    
		    $generalActions[$type] = $item;

		    unset($this->tblAction->tableDefinition->actions[$type]);
		}

		return $generalActions;
	} // end getGeneralActions
	
	function addListFilters () {
		$_sessionData = &$this->tblAction->sessionData;
		include dirname(__FILE__).'/'.$this->tblAction->getLangFile();

		// Строим фильтры
		$tableDefinition =& $this->tblAction->tableDefinition;
		$tblName = $tableDefinition->name;

		$filters = array();
		$filtersCnt = 0;

		foreach ($tableDefinition->fields as $key => $field) {
			if ($field->getAttribute('hide')) {
				continue;
			}

			if (!$filterType = $field->getAttribute('filter')) {
				// не задан тип фильтра
				$filters[] = '';
				continue;
			}

			// Cчетчик количества фильтров
			$filtersCnt++;

			$filterName = $tblName.'_'.$field->name;
			if (isset($_sessionData['DB_FILTERS'][$tblName][$filterName])) {
				$value = $_sessionData['DB_FILTERS'][$tblName][$filterName];
			} else {
				$value = null;
			}

			if (strtolower($filterType) == 'select') {
				if ($field->getAttribute('type') == 'select') {
					$values = $field->valuesList;
				} elseif ($field->getAttribute('type') == 'foreignKey') {
					$values = false;
					if (isset($this->customHandler) && method_exists($this->customHandler, 'getFilterValues')) {
						$values = $this->customHandler->getFilterValues($field->name);
					}
					if ($values === false) {
						$this->tblAction->loadForeignKeys();
						$values =  (array)$tableDefinition->fields[$key]->keyData;
					}
				} elseif ($field->getAttribute('type') == 'sql') {
					if (isset($this->customHandler) && method_exists($this->customHandler, 'getFilterValues')) {
						$values = $this->customHandler->getFilterValues($field->name);
					}
				} else {
					$values = array();
				}
				if (!$length = $field->getAttribute('filterLength')) {
					$length = 25;
				}
				$html = '<select name="filter['.$filterName.']" style="width:100%">';
				$html .= '<option value="">  ...';
				foreach ($values as $fKey => $fValue) {
					$selected = (isset($value) && ($value == $fKey) ) ? 'selected' : '';
					$fValue = mb_substr($fValue, 0, $length, CHARSET);
					$html .= "<option value='$fKey' $selected>$fValue</option> \n";
				}
				$html .= '</select>';
				$filters[] = $html;
			} elseif (strtolower($filterType) == 'range') {
				
				if (is_callable(array($field, 'getRangeFilter'))) {
					$filters[] = $field->getRangeFilter($filterName, $value);
				} else {
					$filters[]  = '
                    <table>
                    <tr>
                    <td>'.$dbAdminMessages['FROM'].':</td><td><input type="text" name="filter['.$filterName.'][0]" value="'.$value[0].'" size="5" style="vertical-align: top"></td>
                    </tr>
                    <tr>
                    <td>'.$dbAdminMessages['TO'].':</td><td><input type="text" name="filter['.$filterName.'][1]" value="'.$value[1].'" size="5" style="vertical-align: top"></td>
                    </tr>
                    </table>';
                }
            } else {

                if ($field->getAttribute('type') == 'checkbox') {
                    $html = '<select name="filter['.$filterName.']">';
                    $html .= '<option value="">  ...';
                    $selected = (isset($value) && ($value == 1) ) ? 'selected' : '';
                    $html .= "<option value='1' $selected>checked</option> \n";
                    $selected = (isset($value) && ($value == 0) ) ? 'selected' : '';
                    $html .= "<option value='0' $selected>none</option> \n";
                    $html .= '</select>';
                    $filters[] = $html;
                } elseif ($field->getAttribute('type') == 'datetime') {
                    $format = substr($field->getFormat(), 0, 8);
                    
                    $html = '
                    <input type="text" name="filter['.$filterName.']" id="filter['.$filterName.']" value="'.$value.'" size="10" style="vertical-align: top">
                    <input type="reset" value=" ... " class="button" style="vertical-align:top;" id="filter_'.$field->name.'_cal" name="'.$field->name.'_cal"> 
                    <script type="text/javascript">
                        Calendar.setup({
                            inputField     :    "filter['.$filterName.']",
                            ifFormat       :    "'.$format.'",
                            showsTime      :    false,
                            button         :    "filter_'.$field->name.'_cal",
                            step           :    1
                        });
                    </script>';

                    $filters[] = $html;
                } else {
                    $inputSize = $field->getAttribute('inputSize');
                    if (empty($inputSize)) {
                        $inputSize = 20;
                    }
                    $filters[]  = '<input type="text" name="filter['.$filterName.']" value="'.$value.'" size="'.$inputSize.'">';
                }
            }
        }

		return $filtersCnt > 0 ? $filters : false;
	}


	function displayErrorMessage($message = '') {
		echo $this->displayError($message);
	}


	function displayForm($what) {
		include dirname(__FILE__).'/'.$this->tblAction->getLangFile();
		global $_dictionary;

		$tableDefinition = $this->tblAction->tableDefinition;
		$primaryKey = $tableDefinition->getAttribute('primaryKey');

		$customTemplate = $tableDefinition->getAttribute('customForm');

		// При удалении мы подгружаем не дропдаун, а конкретное значение
		$this->tblAction->loadForeignKeys($what == 'remove');

		if ($what != 'insert') {
			$currentRow = $this->tblAction->currentRow;
			$GLOBALS['currentRow'] = $currentRow;
			$token = false;
		} else {
			$token = $this->tblAction->createInsertToken();
		}

		// FIXME:
		$path = !empty($customTemplate) && defined('TPL_ROOT') ? TPL_ROOT : false;
		$tpl = self::getTemplateInstance($path);

		if ($what == 'insert') {
			$info = array('caption' => $tableDefinition->actions['insert']['caption'], 'action' => 'insert');
		} elseif ($what == 'info') {
			$info = array('caption' => $dbAdminMessages['ROW_VIEW']);
		} elseif ($what == 'edit') {
			$info = array('caption' => $tableDefinition->actions[$what]['caption'], 'action' => 'save');
		} elseif ($what == 'remove') {
			$info = array('caption' => $tableDefinition->actions[$what]['caption'], 'action' => 'remove');
		}

		if ($token) {
			$info['token'] = $token;
		}
		$info['hint'] = $tableDefinition->getAttribute('hint');

		$info['httproot'] = $this->tblAction->getOption('http_base');
		$info['url'] = $this->tblAction->getHttpPath();

		$items = array();
		$qtips = array();

		foreach ($tableDefinition->fields as $field) {
			$item = array();

			if ($field->name == $primaryKey) {
				// Не показываем primary key
				continue;
			}

			if ($field->getAttribute('type') == 'sql') {
				// Вычисляемое SQL поле, пропускаем
				continue;
			}

			$item['caption'] = $field->getAttribute('caption');

			if ($what == 'insert') {
				$value = ($tmp = $field->getAttribute('default')) ? $this->prepareValue($tmp) : NULL;
				$value = $field->getEditInput($value);
			} elseif ($what == 'edit') {
				$value = isset($currentRow[$field->name]) ? $currentRow[$field->name] : NULL;
				$value = $field->getEditInput($value);
			} elseif ($what == 'remove') {
				$value = isset($currentRow[$field->name]) ? $currentRow[$field->name] : NULL;
				$value = $field->displayRO($value) . '<input type="hidden" name="'.$field->name.'" value="'.htmlentities($value, ENT_QUOTES, 'utf-8').'">';
			} elseif ($what == 'info') {
				$value = isset($currentRow[$field->name]) ? $currentRow[$field->name] : NULL;
				$value = $field->displayRO($value);

			}

			if ($field->getAttribute('required')) {
				$item['required'] = true;
			}
			$item['input'] = $value;
			if (!empty($field->name)) {
				$items[$field->name] = $item;
			} else {
				$items[] = $item;
			}

			if ($qtip = $field->getAttribute('hint')) {
				$qtips[$field->name] = $qtip;
			}
		}

		$info['backaction'] = $this->getBackAction();

		if (isset($tableDefinition->actions[$what]['caption']) && $tableDefinition->actions[$what]['caption'] != '' && 0)    {
			$info['actionbutton'] = $tableDefinition->actions[$what]['caption'];
		} elseif (isset($dbAdminMessages['BUTTON_'.strtoupper($what)])) {
			$info['actionbutton'] = $dbAdminMessages['BUTTON_'.strtoupper($what)];
		}

		$info['afetrpatyjs'] = isset($GLOBALS['dba_afetrpatyjs']) ? $GLOBALS['dba_afetrpatyjs'] : '';
		if (!empty($_dictionary)) {
			$tpl->assign('_dictionary', $_dictionary);
		}
		$tpl->assign('items', $items);
		$tpl->assign('info', $info);
		$tpl->assign('qtips', $qtips);

		$tpl->assign('lang', $dbAdminMessages);

		if (isset($_GET['popup']) && ($_GET['popup'] == 'true')) {
			$GLOBALS['dbaCustomTemplate'] = 'light.ihtml';
		}

		if (!empty($tableDefinition->attributes['customHandler'])) {
			include_once $this->tblAction->getOption('handlers_path').$tableDefinition->attributes['customHandler'].'.php';
			$this->customHandler = new customTableHandler();
			$this->customHandler->params = $this->tblAction->getOption('handler_params');
			if (method_exists($this->customHandler, 'templateCallback')) {
				$this->customHandler->templateCallback('form', $tpl, $this->tblAction->currentRow);
			}
		}


		if (empty($customTemplate)) {
			return trim($tpl->fetch('dba_form.ihtml'));
		} else {
			$tpl->assign('body', trim($tpl->fetch($customTemplate)));
			return trim($tpl->fetch('dba_customform.ihtml'));
		}
	}

	function getBackAction() {
		if (isset($_GET['backLink'])) {
			$backLink = $_GET['backLink'];
		} else {
			$backLink = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '?';
		}
		if (isset($_GET['popup']) && ($_GET['popup'] == 'true')) {
			return 'window.close()';
		} else {
			return 'window.location.href=\''.$backLink.'\'';
		}

	}

	function prepareValue($value) {
		$_sessionData = &$this->tblAction->sessionData;
		// FIXME:
		$GLOBALS["_sessionData"] = $_sessionData;
		$value = @preg_replace_callback("#S%(.+?)%#", create_function('$matches', 'return $GLOBALS["_sessionData"][$matches[1]];'), $value);
		$value = @preg_replace_callback("#G%(.+?)%#", create_function('$matches', 'return $_GET[$matches[1]];'), $value);
		return $value;
	}


	

}

?>
