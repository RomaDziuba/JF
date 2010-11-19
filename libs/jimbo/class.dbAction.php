<?php 
/**
* DB Admin
*
* Class for do database actions
*
* @autor Alexander Voytsekhovskyy <young@php.net>;
* @version 2.0
*/

class dbAction {

	public $tableDefinition;
	public $lastErrorMessage;
	public $wasError;
	public $dbDriver;
	public $currentRow;

	function dbAction($dsn, $tblName, $tblPath = './tblDefs/') {
        global $dbAdminMessages;
        
		// подгружаем языковые сообщения
		include dbDisplayer::getLangFile();
		$this->locale = $dbAdminMessages;

		// подгружаем описание таблицы

		$this->tblPath = $tblPath;
		$this->tableDefinition = $this->loadTableDefinition($tblName);

		if(!$this->tableDefinition) {
			die();
		}

		// подключение к БД
		if (is_object($dsn)) {
			// объект PEAR_DB
			$this->dbDriver = $dsn;
		} else {
			$this->dbDriver = MDB2::factory(DSN);
			if(PEAR::isError($this->dbDriver)) {
				echo "Can't connect to database: " . $this->dbDriver->getMessage();
				die();
			}
			$this->dbDriver->setFetchMode(MDB2_FETCHMODE_ASSOC);
			$this->dbDriver->loadModule('Extended');
		}

		$this->tableName = $tblName;

		if (isset($this->tableDefinition->attributes['alias'])) {
			$tblAlias = $this->tableDefinition->attributes['alias'];
		} else {
			$tblAlias = $this->tableDefinition->name;
		}

		$this->alias = $tblAlias;

	}

	function loadTableData($action = 'list') {
		global $_sessionData;

		$tblName = $this->tableDefinition->name;

		$where = array();
		$join = array();
		$tables = array();
		$fields = array($tblName.".".$this->tableDefinition->getAttribute('primaryKey'));

		if ($groupby = $this->tableDefinition->getAttribute('groupBy')) {
			$fields[] = $groupby.' AS _group_field';
		}

		if ($customSQL = $this->tableDefinition->getAttribute('customSQL')) {

		} else {

			// Обрабатываем все поля - строим запрос
			foreach ($this->tableDefinition->fields as $item) {

				// Если у нас экспорт в ексель - показываем все поля
				if ($item->getAttribute('hide') && $action != 'excel') {
					// Данное поле мы не отображаем в списке
					continue;
				}

				if (empty($item->name)) continue;


				// Обрабатываем внешний ключ
				if (isset($item->foreignKey)) {
					// Возможно таблица по алиасу
					$foreignValueField = $item->getAttribute('foreignValueField');
					$foreignTable = $item->getAttribute('foreignTable');

					//Goliath:
					$foreignTableName = $foreignTable;
					if (preg_match("/\sas\s(.+)$/", $foreignTable, $tmp)) {
						$foreignTableName = $tmp[1];
					}


					// Внешний ключ может быть сложным, признак - наличие скобок
					$foreignValueField = $this->getFieldString($foreignValueField, $foreignTableName, $item->name);

					$singleWhere = $tblName.".".$item->name.' = '.$foreignTableName.".".$item->getAttribute('foreignKeyField');
					$fields[] = $foreignValueField;
					if (in_array($item->getAttribute('filter'), array('select', 'exact'))) {
						$filterFieldName = $foreignTableName.".".$item->getAttribute('foreignKeyField');
					} else {
						$filterFieldName = $this->getFieldString($item->getAttribute('foreignValueField'), $foreignTableName);
					}
					$fields[] = $foreignValueField;

					$joinType = $item->getAttribute('join');

					if ($joinType == 'true') {
						// Необходимо отработать JOIN
						$singleJoin = ' left join '.$foreignTable.' ON ('.$singleWhere;

						if ($addWhere = $item->getAttribute('where')) {
							// Поле может иметь собственное условие
							$singleJoin .= ' AND ' .$this->prepareAddonWhere($addWhere);
						}
						$singleJoin .= ')';

						$join[] = $singleJoin;
					} else {
						// Добавить внешний ключ к запросу
						$where[]  = $singleWhere;
						$tables[] = $item->getAttribute('foreignTable');

						// Поле может иметь собственное условие
						if ($addWhere = $item->getAttribute('where')) {
							// Поле может иметь собственное условие
							$where[] = $this->prepareAddonWhere($addWhere);
						}

						if (!empty($joinType)) {
							$join[] = $joinType;
						}

					}
				} else {
					if (isset($item->isTimestamp)) {
						$fields[] = 'LEFT(FROM_UNIXTIME('.$tblName.".".$item->name.'), '.$item->getAttribute('length').') AS '.$item->name;
					} else {
						if ($item->getAttribute('type') == 'sql') {
							$fields[] = $item->getAttribute('sql') .' as '.$item->name;
							$filterFieldName = $item->getAttribute('sql');
						} elseif (strpos($item->name, ')')) {
							// У нас вычисляемая функция
							$fields[] = $item->name;
							$filterFieldName = $item->name;
						} else {
							$fields[] = $tblName.".".$item->name;
							$filterFieldName = $tblName.".".$item->name;
						}
					}
					if ($_join = $this->prepareAddonWhere($item->getAttribute('join'))) {
						$join[] = $_join;
					}
					if ($addWhere = $item->getAttribute('where')) {
						$where[] = $this->prepareAddonWhere($addWhere);
					}
				}

				$filterName = $tblName.'_'.$item->name;
				if ($filterType = $item->getAttribute('filter')) {
					// Возможно у нас установлен пользовательский фильтр по полю
					if (isset($_sessionData['DB_FILTERS'][$filterName]) && ($_sessionData['DB_FILTERS'][$filterName] != '')) {
						$filterValue = $_sessionData['DB_FILTERS'][$filterName];
						if (in_array($filterType, array('select', 'exact'))) {
							$where[] = $filterFieldName." = '".mysql_escape_string($filterValue)."'";
						} else {
							$where[] = $filterFieldName.' '.$item->getSearchFilter($filterValue);
						}
					}
				} // конец "фильтр по полю"

			}

			// Предустановленные фильтры
			if (!empty($this->tableDefinition->filters)) {
				foreach ($this->tableDefinition->filters as $field => $value) {
					if (preg_match("/^S%(.+)%$/", $value, $tmp)) {
						$value = $_sessionData[$tmp[1]];
					}
					if (isset($value)) {
						$where[] = $tblName.".".$field." IN ($value)";
					}
				}
			}

			// ParentID влияет на выборку
			if (isset($this->tableDefinition->actions['parent'])) {
				$relation = $this->tableDefinition->relations['parent'][$this->tableDefinition->actions['parent']['relation']];
				if (isset($relation['foreignTable']) && isset($relation['foreignField'])) {
					$tmpName = "DB__".$this->alias.'__PARENT';
					// Инициализируем переменную для первого захода
					if (empty($_sessionData[$tmpName])) {
						$where[] = "( {$tblName}.{$relation['field']} = 0 or {$tblName}.{$relation['field']} is NULL)";
					} else {
						$where[]  = $tblName.".".$relation['field']." = '".mysql_escape_string($_sessionData[$tmpName])."'";
					}
				}
			}

			$this->fields = $fields;

			if ($additionalWhere = $this->tableDefinition->getAttribute('additionalWhere')) {
				$where[] = $additionalWhere;
			}

			if ($customFrom = $this->tableDefinition->getAttribute('customFrom')) {
				$fromSection = $customFrom;
			} else {
				$join = (count($join) > 0) ? join(' ', $join) : '';
				// Добавляем саму таблицу и джойны на нее
				array_unshift ($tables, $tblName.' '.$join);
				$fromSection = join(', ', $tables);

			}

			$whereSection = (count($where) > 0) ? join(' AND ', $where) : '1';
			$fields = join(', ', $fields);

			$sql = "SELECT count(*) as cnt FROM $fromSection WHERE $whereSection";
			$countRow = $this->dbDriver->getRow($sql);
			if (PEAR::isError($countRow)) {
				print_r($sql);
				$this->raiseError('SQL Exception: '.$countRow->getMessage());
				return false;
			} else {
				$this->totalRows = $countRow['cnt'];
			}
		}
		
		// common code for both ways


		if (isset($_GET['pager']) && (is_numeric($_GET['pager']) || $_GET['pager'] == 'all')) {
			$rowsPerPage = $_GET['pager'];
			if ($rowsPerPage == 'all') {
				$rowsPerPage = 1000;
			}
			$_sessionData['DB_PAGER'][$tblName] = $rowsPerPage;
		} elseif (isset($_sessionData['DB_PAGER'][$tblName])) {
			$rowsPerPage = $_sessionData['DB_PAGER'][$tblName];
		} else {
			$rowsPerPage = $this->tableDefinition->getAttribute('rowsForPage');
		}
		$this->rowsPerPage = $rowsPerPage;

		// Строим условие LIMIT
		if (isset($_GET['pageID'])) {
			$currentID = ( (int)$_GET['pageID'] - 1) * $rowsPerPage;
			$_sessionData['DB_'.$tblName.'_currentID'] = $currentID;
		} elseif (isset($_sessionData['DB_'.$tblName.'_currentID'])) {
			$currentID = (int)$_sessionData['DB_'.$tblName.'_currentID'];
			$_GET['pageID']  = $currentID / $rowsPerPage + 1;
			if (!empty($_SERVER['QUERY_STRING'])) {
				$_SERVER['QUERY_STRING'] .= '&';
			}
			$_SERVER['QUERY_STRING'] .= 'pageID='.$_GET['pageID'];
		} else {
			$currentID = 0;
		}


		if ((empty($customSQL)) && ($currentID >= $this->totalRows)) {
			if ($this->totalRows <= $rowsPerPage) {
				$currentID = 0;
			} else {
				$currentID = max($this->totalRows - 1, 0);
			}
		}
		$this->currentRowN = $currentID;

		if (empty($customSQL)) {
			$sql = "SELECT $fields FROM $fromSection WHERE $whereSection";
		} else {
			$sql = $customSQL;
		}
		
		$sql .= ' '. $this->getOrderDirection();

		if ($action != 'excel') {
			$sql .= " LIMIT $currentID, $rowsPerPage";
		}

		$dataRes = $this->dbDriver->query($sql);
		if (PEAR::isError($dataRes)) {
			print_r($sql);
			$this->raiseError('SQL Exception: '.$dataRes->getMessage());
			return false;
		}
		
		if (!empty($customSQL)) {
			$sql = "SELECT FOUND_ROWS()";
			$this->totalRows = $this->dbDriver->getOne($sql);
		}

		$this->dbData = array();
		while ($currentRow = $dataRes->fetchRow()) {
			$this->dbData[] = $currentRow;
		};
		$dataRes->free();
		return $this->dbData;
	}

	function getParentIdVar($relation) {
		return 'DB_T:'.$this->tableDefinition->name.'F:'.$relation['field'];
	}

	function loadRow($id) {
		global  $_sessionData;

		if (!in_array($id, (array)@$_sessionData['DB_ALLOWED_IDS'][$this->alias])) {
			echo "<font style='color:red; font-weight: bold'>System error. Please, contact support</font>";
			die;
		}

		// Выбор записи по первичному ключу с подргузкой внешних ключей в массивы
		$tblName = $this->tableDefinition->name;

		//$this->loadForeignKeys();

		$sql = 'SELECT * FROM '.$tblName.' WHERE '.$tblName.'.'.$this->tableDefinition->primaryKey.' = "'.$id.'"';
		$dataRes = $this->dbDriver->query($sql);

		if (PEAR::isError($dataRes)) {
			$this->raiseError('SQL Exception: '.$dataRes->toString());
			return false;
		}

		if ($dataRes->numRows() != 1) {
			return false;
		} else {
			$this->currentRow = $dataRes->fetchRow();
			$dataRes->free();
			return true;
		}
	}

	function performAction($action, $needRedirect = true) {
		global $_sessionData;


		$this->adjustPostData();
		$baseURL = '?';
		foreach ($_GET as $key => $val) {
			if (!in_array($key, array('action', 'ID'))) {
				$baseURL .= $key.'='.urlencode($val).'&';
			}
		}

		// defaults
		$wasCommit = false;
		$handledEvent = false;
		$status = true;



		if (in_array($action, array('save', 'remove')) && (!in_array($_REQUEST['ID'], (array)@$_sessionData['DB_ALLOWED_IDS'][$this->alias]))) {
			echo "<font style='color:red; font-weight: bold'>System error. Please, contact support</font>";
			die;
		}


		if (!empty($this->tableDefinition->attributes['customHandler'])) {
			include_once "./tblHandlers/".$this->tableDefinition->attributes['customHandler'].'.php';
			if (class_exists('customTableHandler')) {
				$customHandler = new customTableHandler();
				if (method_exists ($customHandler, 'handle')) {
					$info = array('action' => 'post');
					$handledEvent = $customHandler->handle(&$info);
					if ($handledEvent && !empty($info['lastErrorMessage'])) {
						// Processed with error code
						$status = false;
						$this->lastErrorMessage = $info['lastErrorMessage'];
					} elseif ($handledEvent) {
						$wasCommit = true;
					}
				}
			}
		}


		if (!$handledEvent) {
			if (isset($this->tableDefinition->actions[$action])) {
				$actionInfo = $this->tableDefinition->actions[$action];
			} else {
				$actionInfo = array();
			}
			if (isset($actionInfo['relation'])) {
				$action = 'relation';
			}
			switch ($action) {
				case "save": {
					$status = $this->updateDBItem();
					$wasCommit = true;
				} break;
				case "insert": {
					$status = $this->insertDBItem();
					$wasCommit = true;
				} break;
				case "remove": {
					$status = $this->removeDBItem(@$_GET['ID']);
					$wasCommit = true;
				} break;
				case "assign": {
					$this->assignDBItem();
				} break;
				case "relation": {
					$this->followRelation($actionInfo);
					$needRedirect = true;
				} break;
				case "foreignKeyLoad": {
					$this->ajaxForeignKeyLoad();
					$needRedirect = true;
				}
				default: {
					$needRedirect = false;
				} break;
			}
		}

		if (isset($this->tableDefinition->attributes['customLocation'])) {
			$newLocation = $this->tableDefinition->attributes['customLocation'];
		} else {
			$newLocation = $this->getHttpPath().$baseURL;
		}

		if (!$status) {
			header('Content-Type: text/html; charset='.SITE_CHARSET);
			$message = empty($this->lastErrorMessage) ? $this->locale['ERR_UNKNOWN'] : $this->lastErrorMessage;

			$response = array(
			'type' => 'error',
			'message' => $this->getText($message)
			);

			// TODO: Move to root logic
			$json = json_encode($response);
			echo "<script>parent.setIframeResponse('".mysql_escape_string($json)."');</script>";
			exit();
		}

		if ($wasCommit) {
			if (isset($customHandler) && method_exists ($customHandler, 'afterCommit')) {
				$customHandler->afterCommit($this->updateInfo);
			}

			// for compatibility with the old versions
			$isPoupMode = (int) isset($_GET['popup']) && ($_GET['popup'] == 'true');

			$response = array(
			'type' => 'success',
			'message' => $this->getText($this->locale['STATUS_SUCCESS']),
			'url' => $newLocation,
			'isPoupMode' => $isPoupMode
			);

			// TODO: Move to root logic
			$json = json_encode($response);
			echo "<script>parent.setIframeResponse('".mysql_escape_string($json)."');</script>";
			exit();
		}

		if ($needRedirect) {
			Header("Location: ".$newLocation);
			die;
		}

		return true;
	}


	// предварительная обработка данных POST
	function adjustPostData() {
		// убрать лишние слеши, в случае небоходимости
		if (get_magic_quotes_gpc()) {
			foreach ($_POST as $key => $val) {
				if (!is_array($val)) {
					$_POST[$key] = stripslashes($val);
				}
			}
		}
		foreach ($_POST as $key => $val) {
			if (!is_array($val)) {
				$_POST[$key] = trim($val);
			}
		}
	}

	private function prepareQueryParams()
	{
	    $primaryKey = $this->tableDefinition->getAttribute('primaryKey');
	    
	    $columns   = array();
	    $values    = array();
	    $many2many = array();
		$toUpload  = array();

		foreach ($this->tableDefinition->fields as $info) {
		    
			switch($info->attributes['type']) {
			    case 'many2many': {
			        $many2many[] = $info;
				    continue;
			    } break;
			    
			    case 'file': {
    			    if (empty($_FILES[$info->name]['name'])) {
    					continue;
    				}
    				
    				$value =  $info->getValue();
    				$toUpload[] = $info;
			    } break;
			    
			    default: 
                    $value = $info->getValue($_POST);
			} // end switch
			
			if($value === false) {
                $this->wasError = true;
                $this->lastErrorMessage = $info->lastErrorMessage;
                return false;
            }
			
		    if ( ($info->name == $primaryKey) || empty($info->name) || ($info->attributes['type'] == 'sql')) {
				continue;
			}
            
			$columns[] = $this->dbDriver->escape($info->name);
			$values[] = $this->dbDriver->quote($value);
		} // end foreach
		
		return array($columns, $values, $many2many, $toUpload);
	} // end prepareQueryParams

	function prepareAddonWhere($value, $currentValue = false) {
		global $_sessionData;
		$value = @preg_replace_callback("#S%(.+?)%#", create_function('$matches', 'return $GLOBALS["_sessionData"][$matches[1]];'), $value);
		if ($currentValue !== false) {
			if (is_null($currentValue)) {
				$currentValue = -9999999999;
			}
			$value = str_replace("%VALUE%", $currentValue, $value);
		}
		if (strpos($value, '%VALUE%')) {
			$value = str_replace("%VALUE%", -1, $value);
		}
		return $value;
	}

	function loadForeignKeys($exactly = false) {

		if (isset($this->foreignKeysLoaded)) {
			return true;
		}

		$tblName = $this->tableDefinition->name;
		foreach ($this->tableDefinition->fields as $key => $item) {
			if (isset($item->foreignKey)) {

				if ($exactly) {
					$this->loadForeignKeyValues(&$this->tableDefinition->fields[$key], true);
				} elseif (empty($item->attributes['ajaxParent']) ) {
					$this->loadForeignKeyValues(&$this->tableDefinition->fields[$key]);
				} else {
					$GLOBALS['currentRow'] = $this->currentRow;
					if (!empty($this->currentRow)) {
						$where =& $this->tableDefinition->fields[$key]->attributes['valuesWhere'];
						$where =  @preg_replace_callback("#G%(.+?)%#", create_function('$matches', 'return $GLOBALS["currentRow"][$matches[1]];'), $where);
						$this->loadForeignKeyValues(&$this->tableDefinition->fields[$key]);
					}
				}
			}
		}

		$this->foreignKeysLoaded = true;
	}


	// Подгрузка списка значений для внешнего ключа
	function loadForeignKeyValues(&$item, $exactly = false) {
		$keyField = $item->getAttribute('foreignKeyField');
		$valueField = $item->getAttribute('foreignValueField');

		$joinType = $item->getAttribute('join');
		$joinSQL = ( !empty($joinType) && ($joinType != 'true')) ? $joinType : '';

		$joinType = $item->getAttribute('joinWhere');
		if ( !empty($joinType) && ($joinType != 'true')) {
			$joinSQL = $joinType;
		}

		$table = $item->getAttribute('foreignTable');

		if (preg_match("/\sas\s(.+)$/", $table, $tmp)) {
			$table = $tmp[1];
		}

		$sql = 'select '.$table.'.'.$keyField.', '. $this->getFieldString($valueField, $table).' as capt from '.$item->getAttribute('foreignTable').' ';

		$sql .= $joinSQL;
		$sql .= ' WHERE 1 ';

		if ($exactly) {
			$sql .= " AND $table.".$keyField.' = "'.$this->currentRow[$item->name].'"';
		} else {
			if ($where = $item->getAttribute('valuesWhere')) {
				$sql .= ' AND '.$this->prepareAddonWhere($where, $this->currentRow[$item->name]);
			} elseif ($where = $item->getAttribute('where')) {
				$sql .= ' AND '.$this->prepareAddonWhere($where, $this->currentRow[$item->name]);
			}


			if ($order = $item->getAttribute('valuesOrder')) {
				$sql .= ' order by '.$order;
			} else {
				$sql .= ' order by '.$valueField;
			}
		}
		$dataRes = $this->dbDriver->getAssoc($sql);
		if (PEAR::isError($dataRes)) {
			echo $sql;
			$this->raiseError('SQL Exception: '.$dataRes->getMessage());
			return false;
		} else {
			$item->keyData = $dataRes;
		}

	}

	function ajaxForeignKeyLoad() {
		foreach ($this->tableDefinition->fields as $key => $item) {
			if (isset($item->foreignKey) && ($item->name == $_GET['ajaxChild'])) {

				$item =& $this->tableDefinition->fields[$key];
				if ($item->attributes['ajaxParent'] == $_GET['ajaxParent']) {
					$where = preg_replace("/G%".$item->attributes['ajaxParent']."%/", mysql_escape_string($_GET['value']), $item->attributes['valuesWhere']);
					$item->attributes['valuesWhere'] = $where;
				}
				$this->loadForeignKeyValues(&$item);
				break;
			}
		}

		header("Content-type: text/html; charset=".SITE_CHARSET);
		$html = '';
		foreach ($item->keyData as $key => $value) {
			$html .= '
		var oOption = document.createElement("OPTION");
		oOption.text="'.strtr($value, array('"' => "'")).'";
		oOption.value="'.$key.'";
		elSel.options.add(oOption);';
		}
		echo '<script>
		
		var elSel = parent.document.getElementById("'.$_GET['ajaxChild'].'");
  		var i;
		for (i = elSel.length - 1; i>=0; i--) {
			elSel.remove(i);
		}
		
	'.$html.'
	
	</script>';
		die;

	}



	/**
	 * Загружаем значения связи много ко многим
	 *
	 * @param int $primaryID
	 * @param array $relation
	 * @return array
	 */
	function loadForeignAssigns($primaryID, $relation) {

		$foreignTable = $relation['foreignTable'];
		$foreignKeyField = $relation['foreignKeyField'];
		$foreignValueField = $relation['foreignValueField'];
		$primaryKey = $this->tableDefinition->getAttribute('primaryKey');
		$values = array();

		if (strpos($foreignValueField, '(')) {
			// Сложное выражение
			$sqlForeignValueField = $foreignValueField;
		} else {
			$sqlForeignValueField = $foreignTable.".".$foreignValueField;
		}
		$sql = "SELECT $foreignTable.$foreignKeyField, $sqlForeignValueField FROM ".$relation['foreignTable'];


		$sql .= " WHERE 1 ";
		if (!empty($relation['filter'])) {
			$sql .= $relation['filter'];
		}

		if (!empty($relation['valuesWhere'])) {
			$sql .= ' AND '.$this->prepareAddonWhere($relation['valuesWhere']);
		}

		if (!empty($relation['valuesOrder'])) {
			$sql .= " ORDER BY ".$relation['valuesOrder'];
		} else {
			$sql .= " ORDER BY $sqlForeignValueField";
		}

		// SQL for all values
		$dataRes = $this->dbDriver->query($sql);
		while ($currentRow = $dataRes->fetchRow()) {
			$values[$currentRow[$foreignKeyField]] = array('value' => $currentRow[$foreignValueField]);
		}

		if (empty($relation['extendedValue'])) {
			$extendedValue = '0 as myvalue';
		} else {
			$extendedValue = $relation['linkTable'].'.value as myvalue';
		}

		// SQL for checked values
		$sql = "SELECT $foreignTable.$foreignKeyField, $sqlForeignValueField, $extendedValue
        FROM ".$relation['linkTable'].", ".$relation['foreignTable']."
        WHERE ".$relation['linkTable'].".".$relation['linkField']."=".$primaryID."
        AND ".$relation['linkTable'].".".$relation['linkForeignField']."=".$foreignTable.".".$relation['foreignKeyField'];

		$dataRes = $this->dbDriver->query($sql);
		if (PEAR::isError($dataRes)) {
			$this->raiseError('SQL Exception: '.$dataRes->getMessage().'<br/>SQL: '.$sql);
			return false;
		}
		while ($currentRow = $dataRes->fetchRow()) {
			if (empty($values[$currentRow[$foreignKeyField]])) {
				continue;
			}
			if (empty($relation['extendedValue'])) {
				$values[$currentRow[$foreignKeyField]]['checked'] = true;
			} else {
				$values[$currentRow[$foreignKeyField]]['checked'] = $currentRow['myvalue'];
			}
		}

		return $values;
	}


	// ------------------------------------------
	// ------- Удаление записи из базы ----------
	// ------------------------------------------
	function removeDBItem($itemID)	{

		if (!is_numeric($itemID)) {
			return false;
		}

		// Сохраняем саму строку
		$this->loadRow($itemID);


		// Удаляем данные для дочерхних таблиц
		if (isset($this->tableDefinition->actions['child'])) {
			$relation = $this->tableDefinition->relations['child'][$this->tableDefinition->actions['child']['relation']];
			$relatedTable = $tblAction = new dbAction($this->dbDriver, $relation['foreignTable'], FS_ROOT."tblDefs/");
			if (isset($relation['cascade'])) {
				$relation2 = $relatedTable->tableDefinition->relations['parent'][$relatedTable->tableDefinition->actions['parent']['relation']];
				$relatedSQL = "select ".$relatedTable->tableDefinition->primaryKey." as id from ".$relatedTable->tableDefinition->name." where ".$relation2['field']." = ".$itemID;
				$dataRes = $this->dbDriver->query($relatedSQL);
				if (PEAR::isError($dataRes)) {
					$this->lastErrorMessage = $this->locale['ERR_REMOVECASCADE'].' SQL:'.$relatedSQL;
					return false;
				}
				while ($row = $dataRes->fetchRow()) {
					$relatedTable->removeDBItem($row['id']);
				}

			}
		}

		// Удаляем данные для связи много ко многим
		foreach ($this->tableDefinition->fields as $field) {
			if ($field->attributes['type'] == 'many2many') {
				$relation = $field->attributes;
				$sql = "delete from ".$relation['linkTable']." where ".$relation['linkField']." = '".$itemID."'";
				$dbRes = $this->dbDriver->query($sql);
				if (PEAR::isError($dbRes)) {
					$this->lastErrorMessage = $this->locale['ERR_SQL'].' SQL:'.$relatedSQL;
					return false;
				}
			}
		}

		// Удаляем саму строку
		$sql = 'DELETE FROM '.$this->tableDefinition->name;
		$sql .= ' WHERE '.$this->tableDefinition->primaryKey. ' = '.$itemID." LIMIT 1";
		$dbRes = $this->dbDriver->query($sql);
		if (PEAR::isError($dbRes)) {
			$this->lastErrorMessage = $this->locale['ERR_SQL'].' SQL:'.$relatedSQL;
			return false;
		}

		$this->updateInfo = array('id' => $itemID, 'action' => 'remove', 'row' => $this->currentRow);
		return true;
	} // END: удаление


	// ------------------------------------------
	// ------- Добавление записи в базу  --------
	// ------------------------------------------
    function insertDBItem() 
    {
		global $_sessionData;
		
		$result = $this->prepareQueryParams();
		if($this->wasError) {
		    return false;
		}

		list($columns, $values, $many2many, $toUpload) = $result;
		
		if (isset($_POST['__token'])) {
			if (isset($_sessionData['insert'][$_POST['__token']])) {
				$tokenData = $_sessionData['insert'][$_POST['__token']];
				foreach ($tokenData as $key => $value) {
					$columns[] = $this->dbDriver->escape($key);
					$values[] = $value;
				}
			} else {
				$this->wasError = true;
				$this->lastErrorMessage = "Wrong token key";
				return false;
			}
		}
		
		$sql = 'INSERT INTO '.$this->tableDefinition->name.' ';
		$sql .= " (".join(", ", $columns).") values (".$this->prepareAddonWhere(join(", ", $values)).") ";
		
		$result = $this->dbDriver->query($sql);
		if (PEAR::isError($result)) {
			$this->mysqlerror2text($result, 'insert');
			return false;
		} else {
			$id = mysql_insert_id($this->dbDriver->connection);
			$this->updateInfo = array('id' => $id, 'action' => 'insert');
		}

		$this->uploadFiles($toUpload, $id);

		// в случае если INSERT был успешно, проставляем many2many
		foreach ($many2many as $info) {
			$this->setMany2Many($info, $id);
		}

		return true;
	} // end insertDBItem
	
	function createInsertToken() {
		global $_sessionData;


		$token = md5(rand());
		$tokenData = array();
		// Возможно, есть родительская таблица
		if (isset($this->tableDefinition->actions['parent'])) {
			$relation = $this->tableDefinition->relations['parent'][$this->tableDefinition->actions['parent']['relation']];
			if (isset($relation['foreignTable']) && isset($relation['foreignField'])) {
				$keyName = 'DB__'.$this->alias."__PARENT";
				if (empty($_sessionData[$keyName]) && isset($relation['isnull'])) {
					$value = 'NULL';
				} else {
					$value = "'".mysql_escape_string($_sessionData[$keyName])."'";
				}
				$tokenData[$relation['field']] = $value;
			}
		}

		// Возможно, есть предустановленные фильтры
		if (!empty($this->tableDefinition->filters)) {
			foreach ($this->tableDefinition->filters as $field => $value) {
				// возможно, оно уже было в полях выше
				if (!in_array($field, $tokenData)) {
					$tokenData[$field] = "'".mysql_escape_string($value)."'";
				}
			}
		}

		if (!empty($tokenData)) {
			$_sessionData['insert'][$token] = $tokenData;
			return $token;
		} else {
			return false;
		}
	}

	function mysqlerror2text($result, $operation) {
		if ($result->code == '-3') {
			$this->lastErrorMessage = $this->locale['ERR_UNIQKEY'];
		} else {
			$this->lastErrorMessage = $this->locale['ERR_'.strtoupper($operation)].$result->toString();
		}

	}

	function uploadFiles($toUpload, $id) {

		foreach ($toUpload as $info) {
			if (empty($info->attributes['fileName'])) {
				$fileName = $id.'_'.$info->name;
			} else {
				$fileName = $_FILES[$info->name]['name'];

				$pos = strrpos($fileName, '.');
				$repl = array(
				'__ID__' => $id,
				'__EXT__' => substr($fileName, $pos + 1),
				'__NAME__' =>substr($fileName, 0, $pos),
				);
				$fileName = strtr($info->attributes['fileName'], $repl);
				$value = $fileName.';0;'.$_FILES[$info->name]['type'];;
				$sql = "update ".$this->tableDefinition->name." set ".$info->name." = '$value' where ".$this->tableDefinition->primaryKey.' = '.(int)$id;
				$GLOBALS['db']->query($sql);
			}

			if (!move_uploaded_file($_FILES[$info->name]['tmp_name'],  FS_ROOT.'storage/'.$this->tableDefinition->name.'/'.$fileName)) {
				$this->lastErrorMessage = 'UPLOAD ERRORR';
			} elseif (in_array($_FILES[$info->name]['type'], array('image/jpeg', 'image/gif', 'image/png'))) {

				if (!empty($info->attributes['thumb'])) {
					//IMAGEMAGIC
					$fname = FS_ROOT.'storage/'.$this->tableDefinition->name.'/'.$fileName;
					$thumb = FS_ROOT.'storage/'.$this->tableDefinition->name.'/thumbs/'.$fileName;
					$cmd = IMAGEMAGIC_BIN." -resize ".$info->attributes['thumb']." $fname $thumb";
					`$cmd`;
				}

				if (!empty($info->attributes['resize'])) {
					$fname = FS_ROOT.'storage/'.$this->tableDefinition->name.'/'.$fileName;
					list($needWidth, $needHeight) = explode('x', $info->attributes['resize']);
					list($width, $height, $type, $attr) = getimagesize($fname);
					if ( ($width > $needWidth) || ($height > $needHeight)) {
						$cmd = IMAGEMAGIC_BIN." -resize {$needWidth}x{$needHeight} $fname $fname";
						`$cmd`;
					}
				}
			}

		}
	}

	function updateDBItem() 
	{
		if (!is_numeric($_GET['ID'])) {
			return false;
		}
		$id = $_GET['ID'];

		$result = $this->prepareQueryParams();
		if($this->wasError) {
		    return false;
		}

		list($columns, $values, $many2many, $toUpload) = $result;
		
		$rows = array();
		foreach($columns as $index => $column) {
            $rows[] = $column . " = ".$values[$index];
		}
		
		$sql = 'UPDATE '.$this->tableDefinition->name.' SET '.join(', ', $rows);
		$sql .= ' WHERE '.$this->tableDefinition->primaryKey. ' = '.(int)$id;
		$result = $this->dbDriver->query($sql);

		if (PEAR::isError($result)) {
			$this->mysqlerror2text($result, 'update');
			return false;
		}

		$this->updateInfo = array('id' => $id, 'action' => 'update');

		foreach ($many2many as $info) {
			if (!$this->setMany2Many($info, $id)) {
				return false;
			}
		}

		$this->uploadFiles($toUpload, $id);

		return true;
	} // end updateDBItem

	function setMany2Many($item, $id) {
		global $db;

		$sql = "delete from ".$item->attributes['linkTable']." where ".$item->attributes['linkField']." = '$id'";
		$result = $db->query($sql);
		if (PEAR::isError($result)) {
			$this->lastErrorMessage = $this->locale['ERR_SQL'].$result->toString();
			return false;
		}

		if (isset($_POST['m2m_'.$item->attributes['linkTable']]) && is_array($_POST['m2m_'.$item->attributes['linkTable']])) {
			$values = $_POST['m2m_'.$item->attributes['linkTable']];

			foreach ($values as $key => $value) {

				if ($value == 1) {
					$sql = "insert into ".$item->attributes['linkTable']." (".$item->attributes['linkField'].",  ".$item->attributes['linkForeignField'].") values ('$id', '$key')";
				} else {
					$sql = "insert into ".$item->attributes['linkTable']." (".$item->attributes['linkField'].",  ".$item->attributes['linkForeignField'].", value) values ('$id', '$key', '".array_sum($value)."')";
				}
				$result = $db->query($sql);
				if (PEAR::isError($result)) {
					$this->lastErrorMessage = $this->locale['ERR_SQL'].$result->toString();
					return false;
				}
			}
		}
		return true;
	}

	function loadTableDefinition($tblName) {
		$tbl = new tableDefinition();

		if (is_file($this->tblPath.'/custom/'.$tblName.'.xml')) {
			$tblPath = $this->tblPath. '/custom/';
		} else {
			$tblPath = $this->tblPath;
		}

		if (!$tbl->loadFromXML($tblPath.$tblName.'.xml', false)) {
			$this->raiseError($this->locale['ERR_TABLEDEF'].' : '.$tbl->lastErrorMessage);
			return false;
		}
		return $tbl;
	}


	function followRelation($action) {
		global $_sessionData;

		$relation = $this->tableDefinition->relations[$action['type']][$action['relation']];
		if (empty($relation)) {
			$relation = $this->tableDefinition->relations[$action['relationType']][$action['relation']];
		}

		$relationTable = $this->loadTableDefinition($relation['foreignTable']);

		if (isset($relationTable->attributes['alias'])) {
			$relationAlias = $relationTable->attributes['alias'];
		} else {
			$relationAlias = $relationTable->name;
		}


		// название таблицы может не совпадать с именем файла

		if ($relation['type'] == 'child') {
			// Перейти к дочерней таблице
			$currentID = (int)$_GET['ID'];
			$this->loadRow($currentID);

			// Прописываем primary ID key в сессию и key Value
			$_sessionData['DB__'.$relationAlias.'__PARENT'] = $this->currentRow[$relation['field']];

			if (!empty($relation['treeCaption'])) {
				// Необходимо использовать заголовок
				if ($this->tableDefinition->fields[$relation['treeCaption']]->attributes['type'] == 'foreignKey') {
					$this->loadForeignKeyValues($this->tableDefinition->fields[$relation['treeCaption']]);
					$_capt = $this->tableDefinition->fields[$relation['treeCaption']]->keyData[$this->currentRow[$relation['treeCaption']]];
				} else {
					$_capt = $this->currentRow[$relation['treeCaption']];
				}
				if (strlen($_capt) > 24) {
					$trimPos = (int)strpos($_capt, ' ', 20);
					if ($trimPos > 0) {
						$_capt = substr($_capt, 0, $trimPos)."...";
					}
				}
				$_sessionData['DB_'.$relationAlias."_caption"]  = empty($_sessionData['DB_'.$this->alias."_caption"]) ? $_capt : $_sessionData['DB_'.$this->alias."_caption"]. ' / ' . $_capt;
			} else {
				$_sessionData['DB_'.$relationAlias."_caption"] = null;
			}

		} elseif ($relation['type'] == 'parent') {
			/*
			if (!$this->loadRow($currentID)) {
			// такой строки нет, вероятно мы уже на самом верхнем уровне
			$_sessionData['DB_'.$tblAlias."_caption"] = null;
			}

			*/
			// Убираем уровень заголовка
			if (!empty($_sessionData['DB_'.$this->alias."_caption"])) {
				$caption = $_sessionData['DB_'.$this->alias."_caption"];
				$caption = ($pos = strrpos($caption, '/')) ? substr($caption, 0, $pos) : '';
				$_sessionData['DB_'.$relationAlias."_caption"] = $caption;
			}

			if (DBADMIN_CURRENT_TABLE == $relation['foreignTable']) {
				// реализация дерева
				$currentID = (int)$_sessionData['DB__'.$this->alias."__PARENT"];
				$this->loadRow($currentID);
				$_sessionData['DB__'.$this->alias."__PARENT"] = $this->currentRow[$relation['field']];
			} else {
				$_sessionData['DB__'.$this->alias."__PARENT"] = null;
			}
		}

		$url = str_replace("/".$_sessionData['DB_CURRENT_TABLE']."/", "/".$relation['foreignTable']."/", $this->getHttpPath());
		
		header("Location: ".$url);
		die();
	}


	function getOrderDirection() {
		global $_sessionData;
		$tblName = $this->tableDefinition->name;

		// Мы уже один раз считали сортировку
		if (isset($this->orderField)) {
			$orderDirection = $this->orderDirection;
			$orderField = $this->orderField;
			$sql = ' ORDER BY '.$this->tableDefinition->name.".".$orderField. ' ' .$orderDirection.' ';
			return $sql;
		}

		$getOrderField = empty($_GET['order']) ? false : $_GET['order'];
		$sessionOrderField = empty($_sessionData['DB_'.$tblName.'_order']) ? false : $_sessionData['DB_'.$tblName.'_order'];
		$defaultOrderField = $this->tableDefinition->getAttribute('defaultOrderField');

		$defaultOrderDirection = $this->tableDefinition->getAttribute('defaultOrderDirection');
		if (!in_array($defaultOrderDirection, array('ASC', 'DESC'))) {
			$defaultOrderDirection = 'ASC';
		}
		$getOrderDirection = empty($_GET['direction']) ? 'ASC' : $_GET['direction'];
		if (!in_array($getOrderDirection, array('ASC', 'DESC'))) {
			$getOrderDirection = 'ASC';
		}
		$sessionOrderDirection = empty($_sessionData['DB_'.$tblName.'_direction']) ? false : $_sessionData['DB_'.$tblName.'_direction'];

		if (empty($getOrderField)) {
			$orderField 		= empty($sessionOrderField) ? $defaultOrderField : $sessionOrderField;
			$orderDirection 	= empty($sessionOrderDirection) ? $defaultOrderDirection : $sessionOrderDirection;
		} else {
			$orderField = $getOrderField;
			$orderDirection = $getOrderDirection;
		}

		$orderBY = $this->tableDefinition->name.".".$orderField;
		foreach ($this->tableDefinition->fields as $field) {
			if (isset($field->attributes['name']) && ($field->attributes['name'] == $orderField)) {
				// вот оно наше поле сортировкм
				if ($field->attributes['type'] == 'foreignKey') {
					$orderBY = $this->getFieldString($field->attributes['foreignValueField'], $field->attributes['foreignTable']);
					break;
				} elseif ($field->attributes['type'] == 'sql') {
					$orderBY = $field->attributes['name'];
				}
			}
		}

		$_sessionData['DB_'.$tblName.'_direction'] = $orderDirection;
		$_sessionData['DB_'.$tblName.'_order'] = $orderField;

		$this->orderDirection = $orderDirection;
		$this->orderField = $orderField;

		if ($groupby = $this->tableDefinition->getAttribute('groupBy')) {
			$groupbyorder = $this->tableDefinition->getAttribute('groupByOrder');
			if (empty($groupbyorder)) {
				$groupbyorder = 'ASC';
			}
			$sql = " ORDER BY $groupby $groupbyorder, ".$orderBY. ' ' .$orderDirection.' ';
		} else {
			$sql = ' ORDER BY '.$orderBY. ' ' .$orderDirection.' ';
		}
		return $sql;
	}

	function raiseError($msg) {
		$this->lastErrorMessage = $msg;
		echo dbDisplayer::displayError($msg);
	}

	function getFieldString($field, $table, $as = false) {
		$value = strpos($field, '(') ? $field : $table.".".$field;
		if ($as) {
			$value .= ' as '.$as;
		}
		return $value;

	}

	private function getText($text)
	{
		if(strtolower(CHARSET) == 'utf-8') {
			return $text;
		}

		return iconv(CHARSET, 'UTF-8', $text);
	}

	/**
	 * Returns current http jimbo path
	 */
	public function getHttpPath()
	{
		return empty($_SERVER['REDIRECT_URL']) ? $_SERVER['PHP_SELF'] : $_SERVER['REDIRECT_URL'];
	} // end getHttpPath
} 

?>