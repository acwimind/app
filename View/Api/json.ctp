<?php

if (!function_exists('json_readable_encode')) {
	
	function _is_assoc($arr){
		return array_keys($arr) !== range(0, count($arr) - 1);
	}
	
	function _escape($str) {
		$str = str_replace(array("\n", "\r\n", "\r", '"'), array('\\n', '\\n', '', '\\"'), $str);
		return $str;	//preg_replace("!([\b\t\n\r\f\"\\\])!", "\\\\\\1", $str);
	};
	
	function json_readable_encode($in, $indent = 0, $readable=true){
		$_myself = __FUNCTION__;
	
		$out = null;
		
		$is_assoc = _is_assoc($in);
		
		// Array of fields where we do not expect numeric values only strings (like name of a place).
		$nonNumeric = array('name', 'slug', 'phone', 'address_zip'); 
		
		foreach ($in as $key => $value) {
			if ($readable) {
				$out .= str_repeat("\t", $indent + 1);
			}
			if ($is_assoc) {
				$out .= "\"" . _escape((string) $key) . "\":";
			}
			
			/*if (!is_array($value) && !is_object($value) && !is_bool($value) && !is_null($value)) {	//fix for new line characters
				$value = str_replace(array("\n", "\r\n"), array('\\n', '\\n'), $value);
			}*/
	
			if (is_object($value) || is_array($value)) {
				//$out .= "\n";
				$out .= $_myself($value, $indent + 1, $readable);
			} elseif (is_bool($value)) {
				$out .= $value ? 'true' : 'false';
			} elseif (is_null($value)) {
				$out .= 'null';
			} elseif (preg_match('/^[0-9]+(\.)?[0-9]*$/', $value) && !in_array($key, $nonNumeric)) {
				$out .= _escape($value);
			} elseif (is_string($value)) {
				$out .= "\"" . _escape($value) . "\"";
			} else {
				$out .= $value;
			}
	
			$out .= ",";
			$out .= "\n";
		}
	
		if (!empty($out)) {
			$out = substr($out, 0, -2);
		}
	
		//$out = str_repeat("\t", $indent) . "{\n" . $out;
		$bracket = $is_assoc ? array('{', '}') : array('[', ']');
		
		if ($out == null) {
			$out = 'null';
		} elseif ($readable) {
			$out = $bracket[0] . "\n" . $out . "\n" . str_repeat("\t", $indent) . $bracket[1];
		} else {
			$out = $bracket[0] . $out . $bracket[1];
		}
	
		return $out;
	}
	
}

if (!isset($data['status']) && !empty($data)) {
	$data = array(
		'status' => 1,
		'data' => $data,
	);
}

if (isset($debug) && $debug>0) {
	echo json_readable_encode($data);
} else {
	echo json_encode($data);
}

if (isset($debug) && $debug==2) {
	echo $this->element('sql_dump');
}