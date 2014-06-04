<?php

/*
 * Logger wrapper for push notifications
 */
class Logger {
	
	/**
	 * Debug log for Push Notifications
	 * @param string $msg
	 * @param array $data
	 */
	public static function Debug($msg, array $data = null)
	{
		if ($data)
		{
			$msg = Logger::_processDataAndMsg($msg, $data);
		}
		
		CakeLog::debug($msg, 'notifs');
		
	}

	/**
	 * Info log for Push Notifications
	 * @param string $msg
	 * @param array $data
	 */
	public static function Info($msg, array $data = null)
	{
		if ($data)
		{
			$msg = Logger::_processDataAndMsg($msg, $data);
		}
		
		CakeLog::info($msg, 'notifs');
		
	}
	
	/**
	 * Warning log for Push Notifications
	 * @param string $msg
	 * @param array $data
	 */
	public static function Warning($msg, array $data = null)
	{
		if ($data)
		{
			$msg = Logger::_processDataAndMsg($msg, $data);
		}
		
		CakeLog::warning($msg, 'notifs');
		
	}
	
	/**
	 * Error log for Push Notifications
	 * @param string $msg
	 * @param array $data
	 */
	public static function Error($msg, array $data = null)
	{
		if ($data)
		{
			$msg = Logger::_processDataAndMsg($msg, $data);
		}
		
		CakeLog::error($msg, 'notifs');
		
	}
	
	/**
	 * Data processing and appending to the message
	 * @param string $msg
	 * @param array $data
	 * @return string
	 */
	private static function _processDataAndMsg($msg, array $data)
	{
	    try {
	        foreach ($data as &$val)
	        {
	            if (is_array($val))
	            {
	                $val = self::_ProcessArray($val);
	            }
	        }
   			// Insert the values into the message
   			$msg = strtr($msg, $data);
		        
	    } catch (Exception $e) {
	        $msg .= ' Exception caused improper format';
	    }
	    
	    return $msg;
	}
	
	private static function _ProcessArray(array $arr)
	{
		$msg = '';
		foreach ($arr as $val)
		{
			if (!empty($msg))
				$msg .= ',';
				
			if (is_array($val))
			{
				$msg .= self::_ProcessArray($val);
			}
			else 
			{
				$msg .= $val;
			}
		}
		
		return $msg;
		
	}
	
}