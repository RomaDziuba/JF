<?php
class cityFormField extends abstractFormField {


	function getEditInput($value = '', $inline = false) {
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

class doubleVisitGeoRegionFormField extends abstractFormField {

	function getEditInput($value = '', $inline = false) {
		global $db, $_sessionData;
		
		$city = $db->getCol('SELECT distinct(geo_region) FROM double_visit WHERE slave_worker_id = '.$_sessionData['auth_id'].' ORDER BY geo_region');
		
		$value = htmlspecialchars(stripslashes($value));
		$out = '<input style="width:150px" type="text" name="'.$this->name.'" id="'.$this->name.'" value="'.$value.'" class="thin">';		
		$out .= '&nbsp;<select style="width:190px" class="thin" onChange="doSelectTo(this, \''.$this->name.'\')"><option value=""></option>';
		foreach ($city as $item) {
			$out .= '<option value="'.htmlspecialchars($item).'">'.htmlspecialchars($item);
		}
		$out .= '</select>';
		return $out;
	}
}

class healthFacilityAddrStreetFormField extends abstractFormField {

	function getEditInput($value = '', $inline = false) {
		global $db, $_sessionData;
		
		$ID = (int)$_GET['ID'];
		if (!empty($ID)) {
			$hfCity = (int)$db->getOne('SELECT city_id FROM health_facility WHERE id='.$ID);		
			$streets = $db->getCol('SELECT DISTINCT(addr_street) FROM health_facility WHERE city_id = '.$hfCity.' ORDER BY addr_street');
		}
		else {
			$streets = $db->getCol('SELECT DISTINCT(addr_street) FROM health_facility ORDER BY addr_street');
		}
		
		$value = htmlspecialchars(stripslashes($value));
		$out = '<input style="width:150px" type="text" name="'.$this->name.'" id="'.$this->name.'" value="'.$value.'" class="thin">';		
		$out .= '&nbsp;<select style="width:190px" class="thin" onChange="doSelectTo(this, \''.$this->name.'\')"><option value=""></option>';
		foreach ($streets as $item) {
			$out .= '<option value="'.htmlspecialchars($item).'">'.htmlspecialchars($item);
		}
		$out .= '</select>';
		
		return $out;
	}
}

class shopAddrStreetFormField extends abstractFormField {

	function getEditInput($value = '', $inline = false) {
		global $db, $_sessionData;
		
		$ID = (int)$_GET['ID'];
		if (!empty($ID)) {
			$hfCity = (int)$db->getOne('SELECT city_id FROM shop WHERE id='.$ID);		
			$streets = $db->getCol('SELECT DISTINCT(addr_street) FROM shop WHERE city_id = '.$hfCity.' ORDER BY addr_street');
		}
		else {
			$streets = $db->getCol('SELECT DISTINCT(addr_street) FROM shop ORDER BY addr_street');
		}
		
		$value = htmlspecialchars(stripslashes($value));
		$out = '<input style="width:150px" type="text" name="'.$this->name.'" id="'.$this->name.'" value="'.$value.'" class="thin">';		
		$out .= '&nbsp;<select style="width:190px" class="thin" onChange="doSelectTo(this, \''.$this->name.'\')"><option value=""></option>';
		foreach ($streets as $item) {
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


	function getEditInput($value = '', $inline = false) {
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