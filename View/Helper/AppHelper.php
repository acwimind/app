<?php
/**
 * Application level View Helper
 *
 * This file is application-wide helper file. You can put all
 * application-wide helper-related methods here.
 *
 * PHP 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       app.View.Helper
 * @since         CakePHP(tm) v 0.2.9
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

App::uses('Helper', 'View');

/**
 * Application helper
 *
 * Add your application-wide methods in the class below, your helpers
 * will inherit them.
 *
 * @package       app.View.Helper
 */
class AppHelper extends Helper {
	
	public function rating_counter($place_rating)
	{
		if (empty($place_rating))
			return 0;
	    
		$a_rate = $place_rating;
	    $full = 75;
	    $max = 5;
	
	    $avg_rating = ($a_rate/$max)*$full;
	
	    if($a_rate > 4){
	        $avg_rating += 12;
	    }elseif($a_rate > 3){
	        $avg_rating += 9;
	    }elseif($a_rate > 2){
	        $avg_rating += 6;
	    }elseif($a_rate > 1){
	        $avg_rating += 3;
	    }
	    
	    return $avg_rating;
	}
	
	public function shorten($name, $length)
	{
		if (empty($name))
			return null;
			
		if (empty($length))
			return $name;
			
		$shortName = (strlen($name) > $length) ? mb_substr($name, 0, $length - 3) . ($name[$length - 2] == ' ' ? ' ...' : '...') : $name;

		return $shortName;
	}
	
}
