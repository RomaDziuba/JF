<?php

class abstractFormField {

	var $attributes;
	var $name;

	function abstractFormElement() {
	}

	function readItself($node, $charset = 'UTF-8') {

		foreach($node->attributes() as $key => $value) {
			$this->attributes[$key] = (string)$value;
		}

		$this->name = (string) $node['name'];
		$this->attributes['caption'] = @html_entity_decode($node['caption'], ENT_COMPAT, $charset);
	}

	function getAttribute($name) {
		if (isset($this->attributes[$name])) {
			return $this->attributes[$name]	;
		} else {
			return '';
		}
	}

	function getFilter($value) {
		return "= '".$value."'";
	}

	function getSearchFilter($value) {
		if (is_array($value)) {
			if (empty($value[0])) {
				return " < '".mysql_escape_string($value[1])."'";
			} elseif (empty($value[1])) {
				return " > '".mysql_escape_string($value[0])."'";
			} else {
				return " BETWEEN '".mysql_escape_string($value[0])."' AND '".mysql_escape_string($value[1])."'";
			}
		} else {
			return " LIKE '%".mysql_escape_string($value)."%'";
		}
	}

	function getEditInput($value = '', $inline = false) {
		$value = htmlspecialchars($value);
		$width = $this->getWidth($inline);
		return "<input style='{$width}' type='text' name='{$this->name}' value='{$value}' class='thin'>";
	}

	function displayValue($value) {
		if (isset($this->attributes['trim']) && ($this->attributes['trim'] != 'false')) {
			// need to trim
			if (is_numeric($this->attributes['trim'])) {
				$length = $this->getAttribute('trim');
				$value =  ($length < strlen($value)) ? $this->substr($value, 0, $length)."..." : $value;
				$value = htmlspecialchars($value);
			} elseif ($this->attributes['trim'] == 'link') {
				if (strlen($value) > 36) {
					$value = '<a href="'.$value.'" target="_blank">'.htmlspecialchars(substr($value, 0, 32)) . '...' . substr($value, -4).'</a>';
				} else {
					$value = '<a href="'.$value.'" target="_blank">'.htmlspecialchars($value).'</a>';
				}
			}
		} else {
			$value = htmlspecialchars($value);
		}
		return $value;
	}

	function displayRow($value) {
		$width = $this->getWidth();
		return '<input style="'.$width.'" type="text" readonly disabled name="'.$this->name.'" value="'.$value.'" class="thin">';
	}

	function DisplayRO($value) {
		return nl2br($value);
	}

	function substr($string, $from, $to) {
		if (function_exists('mb_substr')) {
			return mb_substr($string, $from, $to, 'UTF-8');
		} else {
			return substr($string, $from, $to);
		}
	}


	function getWidth($inline = false, $default = '380px') {
		$width = $this->getAttribute('inputWidth');
		if ($inline) {
			$width = '150px';
		} elseif (empty($width)) {
			$width = $default;
		}
		return 'width:'.$width.';';
	}

}


class fileFormField extends abstractFormField {

	function getEditInput($value = '') {
		$value = explode(';0;', $value);
		if (!empty($this->attributes['fileName'])) {
			$link = HTTP_ROOT.'storage/'.$GLOBALS['currentTable'].'/'.$value[0];
		} else {
			$link = HTTP_ROOT.'getfile/'.$GLOBALS['currentTable'].'/'.$this->name.'/'.$GLOBALS['currentID'].'/'.$value[0];
		}
		return '<input type="file" name="'.$this->name.'" class="thin">&nbsp; <a href="'.$link.'" class="db_link" target="_blank" style="margin:2px">'.$value[0].'</a>';
	}

	function displayValue($value) {

		$value = explode(';0;', $value);
		if (!empty($value[0])) {
			if (!empty($this->attributes['fileName'])) {
				$preview = HTTP_ROOT.'storage/'.$GLOBALS['currentTable'].'/thumbs/'.$value[0];
				$link = HTTP_ROOT.'storage/'.$GLOBALS['currentTable'].'/'.$value[0];
			} else {
				$preview = HTTP_ROOT.'getfile/'.$GLOBALS['currentTable'].'/'.$this->name.'/'.$GLOBALS['currentID'].'/'.$value[0].'?thumb=1';
				$link = HTTP_ROOT.'getfile/'.$GLOBALS['currentTable'].'/'.$this->name.'/'.$GLOBALS['currentID'].'/'.$value[0];
			}
			if (!empty($this->attributes['thumb'])) {
				return "<a href='$link' class='db_link' target='_blank'><img src='$preview' border='0' vspace='2px'></a>";
			} else {
				return "<a href='$link' class='db_link'>".$value[0]."</a>";
			}
		} else {
			return '';
		}
	}
}




class textFormField extends abstractFormField {

	function getEditInput($value = '', $inline = false) {
		$value = htmlspecialchars($value);
		$readonly = $this->getAttribute('readonly') == 'true' ? 'readonly' : '';
		$width = $this->getWidth($inline);
		return '<input style="'.$width.'" type="text" name="'.$this->name.'" id="'.$this->name.'" value="'.$value.'" class="thin" '.$readonly.'>';
	}

}

class md5FormField extends abstractFormField {

	function getEditInput($value = '') {
		$value = htmlspecialchars($value);
		$readonly = $this->getAttribute('readonly') == 'true' ? 'readonly' : '';
		return '<input  type="password" name="'.$this->name.'" id="'.$this->name.'" value="'.$value.'" class="thin" '.$readonly.' style="width:400px">';
	}

}



class passwordFormField extends abstractFormField {

	function getEditInput($value = '') {
		$value = htmlspecialchars($value);
		$readonly = $this->getAttribute('readonly') == 'true' ? 'readonly' : '';
		return '<input style="width:400px" type="password" name="'.$this->name.'" id="'.$this->name.'" value="'.$value.'" class="thin" '.$readonly.'>';
	}

}



class checkboxFormField extends abstractFormField {

	function getEditInput($value = '') {
		$readonly = $this->getAttribute('readonly');
		if ($readonly) {
			return $this->displayValue($value);
		}
		if (is_numeric($value)) {
			$checked = ($value) ? 'checked' : '';
		} else {
			$checked = (strtoupper(substr($value, 0, 1)) == 'Y') ? 'checked' : '';
		}
		return '<input type="checkbox" '.$checked.' name="'.$this->name.'" value="1">';
	}

	function displayValue($value) {
		if (is_numeric($value)) {
			$value = ($value) ? 'Yes' : 'No';
		}
		$checked = (strtoupper(substr($value, 0, 1)) == 'Y') ? '<img align="center" src="'.HTTP_ROOT.'images/tick.png" />' : '';
		return $checked;
	}
}


class timestampFormField extends abstractFormField {
	var $isTimestamp = true;

	function readItself($node) {
		parent::readItself($node);
		$length = $this->getAttribute('length');
		if (!in_array($length, array(5, 10, 16, 19)))  {
			$this->attributes['length'] = 19;
		}
	}

	function getEditInput($value = '', $inline = false) {
		$value = substr(date("Y-m-d H:i:s"), 0, $this->getAttribute('length'));
		$width = $this->getWidth($inline, '350px');
		return '<input style="'.$width.'" type="text" name="'.$this->name.'" value="'.$value.'" class="thin">';
	}

}

class datetimeFormField extends abstractFormField {

	function readItself($node) {
		parent::readItself($node);
		$length = $this->getAttribute('length');
		if (!in_array($length, array(5, 10, 16, 19)))  {
			$this->attributes['length'] = 19;
		}
	}

	function getEditInput($value = '', $inline = false) {
		if (!empty($this->attributes['readonly'])) {
			return $this->displayRO($value) ;
		}

		if (empty($value)) {
			if (isset($this->attributes['default'])) {
				$value = $this->attributes['default'];
			} else {
				if ($this->getAttribute('length') != 5) {
					$value = date("Y-m-d");
				} else {
					$value = date("H:i");
				}
			}
		}
		$value = substr($value, 0, $this->getAttribute('length'));
		if ($value == '0000-00-00') {
			$value = '';
		}


		if (in_array($this->getAttribute('length'), array(16, 19)))  {
			$needTime = 'true';
			$format = '%Y-%m-%d %H:%M';
		} elseif (in_array($this->getAttribute('length'), array(5)))  {
			$needTime = 'true';
			$format = '%H:%M';
		} else {
			$needTime = 'false';
			$format = '%Y-%m-%d';
		}

		$width = $this->getWidth($inline, '350px');

		return '<input style="'.$width.' vertical-align:top;" type="text" name="'.$this->name.'" id="'.$this->name.'" value="'.$value.'">
        <input type="reset" value=" ... " class="button" style="vertical-align:top;" id="'.$this->name.'_cal" name="'.$this->name.'_cal"> 
        <script type="text/javascript">
    Calendar.setup({
        inputField     :    "'.$this->name.'",           //*
        ifFormat       :    "'.$format.'",
        showsTime      :    '.$needTime.',
        button         :    "'.$this->name.'_cal",        //*
        step           :    1
    });
</script>
        ';
		return $value;
	}

	function displayValue($value) {
		$length = $this->getAttribute('length');
		if (!in_array($length, array(5, 10, 16, 19)))  {
			$this->attributes['length'] = 10;
		}
		$value = substr($value, 0, $length);
		return $value;
	}


}


class textareaFormField extends abstractFormField {
	function getEditInput($value = '') {
		$width = $this->getWidth();
		return '<textarea style="'.$width.'" type="text" name="'.$this->name.'" class="thin" rows="3">'.$value.'</textarea>';
	}
}

class readonlyFormField extends abstractFormField {
	function getEditInput($value = '') {
		$width = $this->getWidth();
		return '<input style="width:'.$width.'" type="text" name="'.$this->name.'" value="'.$value.'" class="thin" readonly>';
	}

}


class selectFormField extends abstractFormField {

	var $valuesList;

	function readItself($node, $charset = 'UTF-8') {
		parent::readItself($node);

		foreach ($node as $item) {
			$value = (string)$item;
			if (trim($value) == '') {
				continue;
			}
			if (($this->charset != 'UTF-8') & (function_exists('iconv'))) {
				$value = iconv("UTF-8", $charset, $value);
			}
			$attr = $item->attributes();
			$this->valuesList[(string)$attr['id']] = @html_entity_decode($value, ENT_COMPAT, $charset);
		}
	}

	function getEditInput($value = '', $inline = false) {
		if (!empty($this->attributes['readonly'])) {
			return $this->displayRO($value)	;
		}

		$width = $this->getWidth($inline);
		$select = '<select style="'.$width.'" class="thin" name="'.$this->name.'" >';
		foreach ($this->valuesList as $key => $val) {
			$selected = ($key == $value) ? 'selected' : '';
			$select .= '<option value="'.$key.'" '.$selected.'>'.$val;
		}
		$select .= '</select>';
		return $select;
	}

	function displayRO($value) {
		$value = $this->valuesList[$value];
		return $value;
	}

	function displayValue($value) {
		$value = $this->valuesList[$value];
		return $value;
	}

}

class foreignKeyFormField extends abstractFormField {
	var $foreignKey = true;
	var $keyData = array();

	function getEditInput($value = false) {
		if (!empty($this->attributes['readonly'])) {
			return $this->displayRO($value) ;
		}

		$ajaxHtml = empty($this->attributes['ajaxChild']) ? '' : ' onChange="dbaForeignKeyLoad(\''.$this->attributes['ajaxChild'].'\', this.name, this.value)" ';
		$ajaxHtml2 = empty($this->attributes['ajaxChild']) ? '' : '<option '.($value === false ? ' selected ' : '').' value="-999">';
		$width = $this->getWidth();
		$result = '<select style="'.$width.'" class="thin" name="'.$this->name.'" id="'.$this->name.'" '.$ajaxHtml.'>' . $ajaxHtml2;
		if ($this->attributes['allowEmpty']) {
			$result .= '<option value="0"></option>';
		}
		foreach ($this->keyData as $key => $val) {
			$selected = ($key == $value) ? 'selected' : '';
			$result .= '<option value="'.$key.'" '.$selected.'>'.$val."\n";
		}
		$result .= '</select>';
		if ( (!empty($this->attributes['ajaxChild'])) && (!empty($value)) ) {
			//$GLOBALS['dba_afetrpatyjs'] .= '<script>dbaForeignKeyLoad(\''.$this->attributes['ajaxChild'].'\', "'.$this->name.'", "'.$value.'")</script>';
		}
		return $result;
	}

	function displayRO($value) {
		$value = $this->keyData[$value];
		return $value;
	}
}


class numeratorFormField extends abstractFormField {


	function getEditInput($value = '') {
		return $value;
	}


}

class many2manyFormField extends abstractFormField {

	var $valuesList = array();
	var $extended = false;

	function readItself($node, $charset = 'UTF-8') {
		parent::readItself($node);

		if (!empty($node->option)) {

			foreach ($node->option as $item) {
				$value = (string)$item;
				if (trim($value) == '') {
					continue;
				}
				if (!empty($this->charset) && ($this->charset != 'UTF-8') & (function_exists('iconv'))) {
					$value = iconv("UTF-8", $charset, $value);
				}
				$attr = $item->attributes();
				$this->valuesList[(string)$attr['id']] = @html_entity_decode($value, ENT_COMPAT, $charset);
			}

			$this->extended = true;
		}

	}


	function getEditInput($value = false) {

		global $tblAction;

		$this->attributes['extendedValue'] = $this->extended;

		if (isset($_GET['ID'])) {
			$list = $tblAction->loadForeignAssigns((int)$_GET['ID'], $this->attributes);
		} else {
			$list = $tblAction->loadForeignAssigns(0, $this->attributes);
			if (isset($value)) {
				$value = $tblAction->prepareAddonWhere($value);
				if (isset($list[$value])) {
					$list[$value]['checked'] = true;
				}
			}
		}

		$html = '<div style="width:348px; border: 1px dotted gray; text-align:left; height:200px; overflow-y: scroll; padding: 3px">';

		if ($this->extended) {
			$html .= '<table width="95%">';
			$firstLine = true;
			foreach ($list as $id => $option) {
				if (!$firstLine) {
					$html .= '<tr style="height:1px;"><td colspan=2 style="border-bottom: 1px dotted grey; font-size:1px;">&nbsp;</td></tr>';
				}
				$firstLine = false;
				$html .= '<tr><td rowspan="'.count($this->valuesList).'"><b>'. $option['value'].": </b></td>";
				$first = true;
				foreach ($this->valuesList as $key => $caption) {
					if (!$first) {
						$html .= '<tr>';
						$first = false;
					}
					$checked = (isset($option['checked']) && (($option['checked'] & pow(2, $key)) == pow(2, $key))) ? 'checked' : '';
					$html .= '<td><input type="checkbox" name="m2m_'.$this->attributes['linkTable'].'['.$id.'][]" style="vertical-align: middle;" value="'.pow(2, $key).'" '.$checked.'> '.$caption.'</td></tr>';
				}
			}
			$html .= '</table>';
		} else {
			foreach ($list as $id => $option) {
				$checked = isset($option['checked']) ? 'checked' : '';
				$html .= '<input type="checkbox" name="m2m_'.$this->attributes['linkTable'].'['.$id.']" style="vertical-align: middle;" value="1" '.$checked.'> '.$option['value']." <br/>\n";
			}
		}
		$html .= '
		</div>
		<label><input type="checkbox" style="vertical-align: middle; margin-left:5px" onClick="tbl_check_all(\'m2m_'.$this->attributes['linkTable'].'\', this.checked)">
		<b>'.$tblAction->locale['FORM_CHECK_ALL'].'</b></label>';
		return $html;
	}

	function displayValue($value) {
		$attr = $this->attributes;
		$sql = "SELECT mt.{$attr['foreignValueField']} from {$attr['foreignTable']} mt join {$attr['linkTable']} lt on (mt.{$attr['foreignKeyField']} = lt.{$attr['linkForeignField']}) where lt.{$attr['linkField']} = ".(int)$GLOBALS['currentID'];
		$list = $GLOBALS['db']->getCol($sql);
		$content = '<div style="height:200px;overflow-y:scroll"><ul style="margin:0px;">';
		foreach ($list as $item) {
			$content .= '<li>'.$item;
		}
		$content .= '</ul></div>';
		return $content;
	}
}

class sqlFormField  extends abstractFormField {

}
?>