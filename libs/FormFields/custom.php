<?php
class cityFormField extends abstractFormField {


	function getEditInput($value = '') {
		global $db;
		$city = $db->getCol("select distinct city from client_tt order by city");

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

class texthintFormField extends abstractFormField {


	function getEditInput($value = '') {
		global $db;
		$city = $db->getCol($this->attributes['hintsql']);

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