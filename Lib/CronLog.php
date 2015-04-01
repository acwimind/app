<?php

/*
 * Logger wrapper for cron jobs
 */
class CronLog {
	
	/**
	 * Debug log for Push Notifications
	 * @param string $msg
	 * @param array $data
	 */
	public static function Debug($msg, array $data = null)
	{
		if ($data)
		{
			$msg = CronLog::_processDataAndMsg($msg, $data);
		}
		
		CakeLog::debug($msg, 'cronjobs');
		
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
			$msg = CronLog::_processDataAndMsg($msg, $data);
		}
		
		CakeLog::info($msg, 'cronjobs');
		
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
			$msg = CronLog::_processDataAndMsg($msg, $data);
		}
		
		CakeLog::warning($msg, 'cronjobs');
		
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
			$msg = CronLog::_processDataAndMsg($msg, $data);
		}
		
		CakeLog::error($msg, 'cronjobs');
		
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
	        $msg .= ' Exception caused by improper format. This is a logger error, not a valid log entry!';
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