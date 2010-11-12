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

class autocompleteFormField extends abstractFormField {
	
	function getEditInput($value = '', $inline = false) {
		$value = htmlspecialchars($value);
		$readonly = $this->getAttribute('readonly') == 'true' ? 'readonly' : '';
		$width = $this->getWidth($inline);
		$out = '<input style="'.$width.'" type="text" name="'.$this->name.'" id="'.$this->name.'_autocomplete" value="'.$value.'" class="thin autocomplete-input" '.$readonly.'>';
		$out .= "<script>
					(function () {
								jQuery('#".$this->name."_autocomplete').autocomplete({
								    serviceUrl: '".$this->attributes['autocompleteURL']."',
								    minChars: ".$this->attributes['minChars'].",
								    maxHeight: 400, 
								    width: 380, 
								    zIndex: 9999,
								    deferRequestBy: 300 
								});
					})(jQuery)
				</script>";
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