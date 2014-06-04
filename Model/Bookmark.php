<?php

class Bookmark extends AppModel {
	
	public $belongsTo = array(
		'Place',
		'Member'
	);
	
	public function addBookmark($memBig, $placeBig)
	{
		$data = array(
    		'Bookmark' => array(
        		'member_big' => $memBig,
        		'place_big' => $placeBig,
        		'created' => 'NOW()',
        	)
    	);
    	try {
	    	$res = $this->save($data);
    		
    	} catch (Exception $e) {
//    		debug($e);
			$res = false;
			
    	}
    	
    	return $res;
	}

	public function isBookmarked($memBig, $placeBig) {

		$exists = $this->find('count', array(
			'conditions' => array(
				'member_big' => $memBig,
        		'place_big' => $placeBig,
			),
			'recursive' => -1,
		));
		return (bool) $exists;

	}
	
}