<?php

class abstractFormField {

    public $tblAction;
    
	var $attributes;
	var $name;
	
	public $lastErrorMessage = false;
	
	public function __construct($tblAction) 
	{
        $this->tblAction = $tblAction;
	}

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
				return " <= '".mysql_escape_string($value[1])."'";
			} elseif (empty($value[1])) {
				return " >= '".mysql_escape_string($value[0])."'";
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
	
    public function getValue($requests = array())
    {
        $value = empty($requests[$this->name]) ? '' : $requests[$this->name];
        
        if( !$value && isset($this->attributes['isnull']) ) {
            $value = null;
        }
        
        if(!$this->isValidValue($value)) {
            return false;
        }
        
        return $value;
    } // end getValue
    
    public function isValidValue($value)
    {
        global $dbAdminMessages;
        
        if (isset($this->attributes['required']) && empty($value)) {
            $this->lastErrorMessage = $dbAdminMessages['ERR_REQUIRED']." '".$this->attributes['caption']."'";
            return false;
        }

        return true;
    } // end isValidValue
    

}


class fileFormField extends abstractFormField {

	function getEditInput($value = '', $inline = false) {
        $_sessionData = &$this->tblAction->sessionData;
	    
	    $httpBase = $this->tblAction->getOption('http_base');
	    
		$value = explode(';0;', $value);
		if (!empty($this->attributes['fileName'])) {
			$httpPath = !empty($this->attributes['httpPath']) ? $this->attributes['httpPath'] : $httpBase.'storage/'.$_sessionData['DB_CURRENT_TABLE'].'/';
			$link = $httpPath.$value[0];
		} elseif (isset($GLOBALS['currentID'])) {
			$link = $httpBase.'getfile/'.$_sessionData['DB_CURRENT_TABLE'].'/'.$this->name.'/'.$GLOBALS['currentID'].'/'.$value[0];
		}
		
		$html = '<input type="file" name="'.$this->name.'" class="thin">';
		if (isset($link)) {
			$html .= '&nbsp; <a href="'.$link.'" class="db_link" target="_blank" style="margin:2px">'.$value[0].'</a>';
		}
		
		return $html;
	}

	function displayValue($value) {
        
	    $_sessionData = &$this->tblAction->sessionData;
        $httpBase = $this->tblAction->getOption('http_base');
	    
		$value = explode(';0;', $value);
		if (!empty($value[0])) {
			if (!empty($this->attributes['fileName'])) {
				$httpPath = !empty($this->attributes['httpPath']) ? $this->attributes['httpPath'] : $httpBase.'storage/'.$_sessionData['DB_CURRENT_TABLE'].'/';
				
				$preview = $httpPath.'thumbs/'.$value[0];
				$link = $httpPath.$value[0];
			} else {
				$preview = $httpBase.'getfile/'.$_sessionData['DB_CURRENT_TABLE'].'/'.$this->name.'/'.$GLOBALS['currentID'].'/'.$value[0].'?thumb=1';
				$link = $httpBase.'getfile/'.$_sessionData['DB_CURRENT_TABLE'].'/'.$this->name.'/'.$GLOBALS['currentID'].'/'.$value[0];
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
	
	
	public function getValue($requests = array())
    {
        $value = $_FILES[$this->name]['name'].';0;'.$_FILES[$this->name]['type'];
        
        return $value;
    } // end getValue
	
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

	function getEditInput($value = '', $inline = false) {
		$value = htmlspecialchars($value);
		$readonly = $this->getAttribute('readonly') == 'true' ? 'readonly' : '';
		$width = $this->getWidth($inline);
		return '<input  type="password" name="'.$this->name.'" id="'.$this->name.'" value="'.$value.'" class="thin" '.$readonly.' style="'.$width.'">';
	}

    public function getValue($requests = array())
    {
        $value = parent::getValue($requests);
        
        if(!$value) {
            return false;
        }
        
        $value = strlen($value) != 32 ? md5($value) : $value;
        
        return $value;
    }
	
}



class passwordFormField extends abstractFormField {

	function getEditInput($value = '', $inline = false) {
		$value = htmlspecialchars($value);
		$readonly = $this->getAttribute('readonly') == 'true' ? 'readonly' : '';
		return '<input style="width:400px" type="password" name="'.$this->name.'" id="'.$this->name.'" value="'.$value.'" class="thin" '.$readonly.'>';
	}

}



class checkboxFormField extends abstractFormField {

	function getEditInput($value = '', $inline = false) {
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
		$httpBase = $this->tblAction->getOption('http_base');
		$checked = (strtoupper(substr($value, 0, 1)) == 'Y') ? '<img align="center" src="'.$httpBase.'images/dbadmin_tick.png" />' : '';
		return $checked;
	}

	function displayRO($value) {
		return $this->displayValue($value);
	}
}


class timestampFormField extends abstractFormField {
	var $isTimestamp = true;

	function readItself($node, $charset = 'UTF-8') {
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

/**
 * DateTime Field
 * 
 * @package Jimbo
 * @subpackage Fields
 */
class datetimeFormField extends abstractFormField 
{
    public $needTime;

    function readItself($node, $charset = 'UTF-8') 
    {
        parent::readItself($node);
        
        $length = $this->getAttribute('length');
        if (!in_array($length, array(5, 10, 16, 19)))  {
            $this->attributes['length'] = 19;
        }
    } // end readItself

    function getEditInput($value = '', $inline = false) 
    {
        if (!empty($this->attributes['readonly'])) {
            return $this->displayRO($value) ;
        }

        $format = $this->getFormat();
        
        $value = substr($value, 0, $this->getAttribute('length'));
        if ($value == '0000-00-00') {
            $value = '';
        }
        
        if (empty($value)) {
            $value  = isset($this->attributes['default']) ? $this->attributes['default'] :  strftime($format);
        } else {
            $value = strftime($format, strtotime($value));
        }
        
        $width = $this->getWidth($inline, '350px');
        
        return $this->getHtml($value, $width, $format);
    } // end getEditInput

    /**
     * Returns date format according to locale settings.
     */
    public function getFormat()
    {
        $format = $this->getAttribute('format');
        if($format) {
            return $format;
        }
        
        $length = $this->getAttribute('length');
        
        if ( in_array($length, array(16, 19)) ) {
            $this->needTime = 'true';
            $format = '%Y-%m-%d %H:%M';
        } elseif ( in_array($length, array(5)) )  {
            $this->needTime = 'true';
            $format = '%H:%M';
        } else {
            $this->needTime = 'false';
            $format = '%Y-%m-%d';
        }
        
        return $format;
    } // end getFormat
    
    function displayValue($value) 
    {
        $length = $this->getAttribute('length');
        if (!in_array($length, array(5, 10, 16, 19)))  {
            $this->attributes['length'] = 10;
        }
        
        $format = $this->getFormat();
        $value = substr($value, 0, $length);
        
        if(!empty($value)) {
            $value = strftime($format, strtotime($value));
        }
        
        return $value.'';
    } // end displayValue
    
    public function getRangeFilter($filterName, $value)
    {
    	$tpl = dbDisplayer::getTemplateInstance();
    	
    	$tpl->assign("attributes", $this->attributes);
    	$tpl->assign("value", $value);
    	$tpl->assign("filterName", $filterName);
    	
    	return $tpl->fetch("fields/datetime/filter_range.tpl");
    	
    } // end getRangeFilter
    
    private function getHtml($value, $width, $format)
    {
        $content = '<input style="'.$width.' vertical-align:top;" type="text" name="'.$this->name.'" id="'.$this->name.'" value="'.$value.'" />
        <input type="reset" value=" ... " class="button" style="vertical-align:top;" id="'.$this->name.'_cal" name="'.$this->name.'_cal" /> 
        <script type="text/javascript">
            Calendar.setup({
                inputField     :    "'.$this->name.'",           //*
                ifFormat       :    "'.$format.'",
                '.(empty($this->needTime) ? '' : 'showsTime: '.$this->needTime.',').'
                button         :    "'.$this->name.'_cal",        //*
                step           :    1
            });
        </script>
        ';
        
        return $content;
    } // end getHtml
    
    public function getValue($requests = array())
    {
        $value = parent::getValue($requests);
        
    	if( !$value && isset($this->attributes['isnull']) ) {
            $value = null;
			return $value;
        }
        
        return date('Y-m-d H:i:s', strtotime($value));
    } // end getValue
    
} // end class datetimeFormField



class textareaFormField extends abstractFormField {
	function getEditInput($value = '', $inline = false) {
		$width = $this->getWidth();
		$readonly = $this->getAttribute('readonly') ? 'readonly="true"' : '';
		return '<textarea style="'.$width.'" type="text" '.$readonly.' name="'.$this->name.'" class="thin" rows="3">'.$value.'</textarea>';
	}
}

class readonlyFormField extends abstractFormField {
	function getEditInput($value = '', $inline = false) {
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
			if (isset($this->charset) && ($this->charset != 'UTF-8') & (function_exists('iconv'))) {
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
		$value = isset($this->valuesList[$value]) ? $this->valuesList[$value] : "";
		return $value;
	}

}

class foreignKeyFormField extends abstractFormField {
	var $foreignKey = true;
	var $keyData = array();

	function getEditInput($value = false, $inline = false) {
		if (!empty($this->attributes['readonly'])) {
			return $this->displayRO($value) ;
		}

		$ajaxHtml = empty($this->attributes['ajaxChild']) ? '' : ' onChange="dbaForeignKeyLoad(\''.$this->attributes['ajaxChild'].'\', this.name, this.value)" ';
		$ajaxHtml2 = empty($this->attributes['ajaxChild']) ? '' : '<option '.($value === false ? ' selected ' : '').' value="-999">';
		$width = $this->getWidth();
		$result = '<select style="'.$width.'" class="thin" name="'.$this->name.'" id="'.$this->name.'" '.$ajaxHtml.'>' . $ajaxHtml2;
		if (isset($this->attributes['allowEmpty'])) {
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
		$value = empty($this->keyData[$value]) ? '' : $this->keyData[$value];
		return $value;
	}
}


class numeratorFormField extends abstractFormField {


	function getEditInput($value = '', $inline = false) {
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


	function getEditInput($value = false, $inline = false) {

		$this->attributes['extendedValue'] = $this->extended;

		if (isset($_GET['ID'])) {
			$list = $this->tblAction->loadForeignAssigns((int)$_GET['ID'], $this->attributes);
		} else {
			$list = $this->tblAction->loadForeignAssigns(0, $this->attributes);
			if (isset($value)) {
				$value = $this->tblAction->prepareAddonWhere($value);
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
		<b>'.$this->tblAction->locale['FORM_CHECK_ALL'].'</b></label>';
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

class comboFormField extends abstractFormField {


    function getEditInput($value = '', $inline = false) {
        global $db;
        $city = $db->getCol("select distinct {$this->name} from {$this->table} order by {$this->name}");

        $value = htmlspecialchars(stripslashes($value));
        $out = '<input style="width:150px" type="text" name="'.$this->name.'" id="'.$this->name.'" value="'.$value.'" class="thin">';
        $out .= '&nbsp;<select style="width:190px" class="thin" onChange="doSelectTo(this, \''.$this->name.'\')">';
        foreach ($city as $item) {
            $out .= '<option value="'.htmlspecialchars($item).'">'.htmlspecialchars($item);
        }
        $out .= '</select>';
        return $out;
    }


}


?>
