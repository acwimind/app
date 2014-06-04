<?php

class DateHelper extends AppHelper {
	
	protected $date_short_format = 'Y-m-d';
	protected $date_long_format = 'Y-m-d';
	protected $time_format = 'H:i';
	protected $full_short_format = 'Y-m-d H:i';
	protected $full_long_format = 'Y-m-d H:i';
	protected $short_datetime_format = 'd.m.Y H:i';
	
	/*
	protected $date_short_format = 'j. M';
	protected $date_long_format = 'j. M Y';
	protected $time_format = 'H:i';
	protected $full_short_format = 'H:i, j. M';
	protected $full_long_format = 'H:i, j. M Y';
	*/
	
	
	public function datepicker_format() {
		return str_replace(
				array('j', 'd',  'n', 'm',  'y',  'Y'), 
				array('d', 'dd', 'm', 'mm', 'yy', 'yyyy'), 
				$this->date_long_format
			);
	}
	
	public function date($input, $force_full=false) {
		
		$time = strtotime($input);
		
		if (!$force_full && date('Y', $time) == date('Y')) {
			return date($this->date_short_format, $time);
		} else {
			return date($this->date_long_format, $time);
		}
		
	}
	
	public function time($input) {
		return date($this->time_format, strtotime($input));
	}
	
	public function full($input, $force_full=false) {
		
		$time = strtotime($input);
		
		if (!$force_full && date('Y', $time) == date('Y')) {
			return date($this->full_short_format, strtotime($input));
		} else {
			return date($this->full_long_format, strtotime($input));
		}
		
	}
	
	public function smart($input, $force_time=false) {
		
		$time = strtotime($input);
		
		if (date('Y-m-d', $time) == date('Y-m-d')) {
			$date_string = __('Today');
		} elseif (date('Y-m-d', $time) == date('Y-m-d', strtotime('-1 day'))) {
			$date_string = __('Yesterday');
		} elseif (date('w Y', $time) == date('w Y')) {
			$date_string = date('l', $time);
		} elseif (date('Y', $time) == date('Y')) {
			$date_string = date($this->date_short_format, $time);
		} else {
			$date_string = date($this->date_long_format, $time);
		}
		
		if ($force_time || date('H:i', $time) !== '00:00') {
			return $date_string . ' ' . date('H:i', $time);
		}
		
		return $date_string;
		
	}
	
	public function dateTime($input)
	{
		return date($this->short_datetime_format, strtotime($input));
	}
	
}