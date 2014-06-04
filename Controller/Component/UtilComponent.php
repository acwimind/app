<?php

class UtilComponent extends Component {
	
	private $_keys = array('middle_name', 'surname');
	
	public function transform_name(&$input)
	{
		if (!is_array($input) && !is_object($input))
		{
			return $input;
		}
		
		foreach ($input as $key => &$value)
		{
//			debug($key);
//			debug($value);
			if (is_array($value) || is_object($value))
				$this->transform_name($value);
				
			if (in_array($key, $this->_keys) && !empty($value) && is_string($value) && trim($value) != '')
			{
//				debug('trans');
				$value = mb_substr(trim($value), 0, 1) . '.';
			}
//			debug($key);
//			debug($value);
		}
		
		return $input;
		
	}
	
}