<?php

/**
* XML-definitions reader
*
* Class for read XML table definition and save it as array
*
* @autor Alexander Voytsekhovskyy <young@php.net>;
* @version 1.1
*/

class tableDefinition {

	var $name = '';
	var $fields = array();
	var $defaultOrder = array();
	var $parentRelation = false;
	var $cacheFSPath;
	var $lastErrorMsg;

	var $attributesValue = array();
	var $attributeItems = array('name', 'primaryKey', 'fields', 'attributes', 'actions', 'relations', 'filters', 'grouped');

	public $lastErrorMessage;

	function tableDefinition() {
	}

	function loadFromXML($file, $useCache = true) {

		if (!extension_loaded('simplexml')) {
			$this->raiseError("Sorry, simplexml extension not loaded!");
			return false;
		}

		if (!file_exists($file)) {
			$this->raiseError("File ".$file." not found!");
			return false;
		}

		if (!is_file($file)) {
			$this->raiseError("Cant open file $file for reading!");
			return false;
		}
		
		$tpl = dbDisplayer::getTemplateInstance();
		try {
            $info = Controller::call('TblDefinition', 'definition', array(&$tpl));
		} catch( Exception $exp) {
		    $info = array();
		}
		
        $tableDef = trim($tpl->fetch($file));

		$xmlObj = simplexml_load_string($tableDef);
		if(!$xmlObj) {
			$this->raiseError("Cant parse XML content of file!");
			return false;
		}

		$attributes = $xmlObj->attributes();

		$this->name 		= (string) $attributes['name'];
		$this->primaryKey 	= (string) $attributes['primaryKey'];
		$this->charset 		= (string) $attributes['charset'];

		foreach($xmlObj->attributes() as $key => $value) {
			$this->attributes[$key] = (string)$value;
		}

		if (($this->charset != 'UTF-8') && (function_exists('iconv')) && isset($this->attributes['hint'])) {
			$this->attributes['hint'] = iconv("UTF-8", 'cp1251',$this->attributes['hint']);
		}

		// Parsing Fields List
		$fieldsNodes = $xmlObj->xpath("fields/field");

		foreach ($fieldsNodes as $xmlField) {
			if(empty($xmlField['type'])) {
				continue;
			}

			$className = $xmlField['type']."FormField";

			if(!class_exists($className)) {
				continue;
			}

			$field = new $className();
			$field->readItself($xmlField, $this->charset);

			if (($this->charset != 'UTF-8') && (function_exists('iconv'))) {
				$field->attributes['caption'] = iconv("UTF-8", 'cp1251', $field->attributes['caption']);
				if (isset($field->attributes['hint'])) {
					$field->attributes['hint'] = iconv("UTF-8", 'cp1251', $field->attributes['hint']);
				}
			}
			$field->table = $this->name;
            if (!empty($field->name) && !isset($this->fields[$field->name])) {
                $this->fields[$field->name] = $field;
            } else {
                $this->fields[] = $field;
            }
			unset($field);
		}
		unset($fieldsNodes);

		// Parsing captions
		$this->loadAttributeValues($xmlObj, "actions/action", 'type', 'actions', 'attributes');

		// Parsing relations
		$this->loadRelations($xmlObj, "relations/link");

		// Parsing filters
		$this->loadAttributeValues($xmlObj, "filters/filter", 'field', 'filters', 'content');

		// Parsing Group operations
		$this->loadAttributeValues($xmlObj, "grouped/item", 'type', 'grouped', 'attributes');

		unset($xmlObj);

		return true;
	}

	/**
    * Load group of attributes from XML Tree
    *
    *
    * @param object XML_Tree $xmlTree XML-Tree Object
    * @param string $path      Path to key
    * @param string $keyAttr   Name of key attribute
    * @param string $keyName   How to name saved result
    * @param string $whatToGet What need to put as value
    * @return boolean returns true
    */
	function loadAttributeValues(&$xmlObj, $path, $keyAttr, $keyName, $whatToGet = 'attributes') {
		$items = $xmlObj->xpath($path);

		$result = array();

		if (!empty($items)) {
			foreach ($items as $item) {
				$attributes = $item->attributes();
				if (!$attributes) {
					continue;
				}

				$index = (string) $attributes[$keyAttr];

				if($whatToGet == 'attributes') {
					foreach($attributes as $key => $value) {
						if (strtoupper($this->charset) == 'UTF-8' || !function_exists('iconv')) {
							$result[$index][$key] = (string)$value;
						} else {
							$result[$index][$key] = html_entity_decode(iconv("UTF-8", $this->charset, (string)$value), ENT_COMPAT, $this->charset);
						}
					} // end foreach
				} else if ($whatToGet == 'content') {
					$key = (string) $attributes[$keyAttr];
					if (strtoupper($this->charset) == 'UTF-8' || !function_exists('iconv')) {
						$result[$index] = (string)$item;
					} else {
						$result[$index] = html_entity_decode(iconv("UTF-8", $this->charset, (string)$item), ENT_COMPAT, $this->charset);
					}
				} // end if

			}
		}
		$this->$keyName = $result;

		unset($items);
		unset($result);
		return true;
	}

	function loadRelations(&$xmlObj, $path) {
		$items = $xmlObj->xpath($path);

		$result = array();

		if (!empty($items)) {
			foreach ($items as $item) {
				$attributes = $item->attributes();
				if (!isset($attributes['foreignTable']) ||  $attributes['foreignTable'] == "") {
					continue;
				}

				$type = (string) $attributes['type'];
				$foreignTable = (string) $attributes['foreignTable'];
				foreach($attributes as $key => $value) {
					$result[$type][$foreignTable][$key] = (string)$value;
				}

			}
		}
		$this->relations = $result;

		unset($items);
		unset($result);
		return true;
	}


	function raiseError($msg) {
		$this->lastErrorMsg = $msg;
		if (!class_exists('dbDisplayer')) {
			echo "Error: ".$msg;
		} else {
			echo dbDisplayer::displayError($msg);
		}
	}

	function getAttribute($nane) {
		if (isset($this->attributes[$nane])) {
			return $this->attributes[$nane];
		} else {
			return false;
		}
	}
}
?>
