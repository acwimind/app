<?php

class Signalation extends AppModel{
	
	public $belongsTo = array(
		'Gem',
		'Member'
	);
	
	public function addSignalation($memBig, $gemBig, $type, $text)
	{
		$data = array(
    		'Signalation' => array(
        		'member_big' => $memBig,
        		'gem_big' => $gemBig,
        		'type' => $type,
        		'text' => $text,
        		'status' => ACTIVE,
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
	
	public function canSignal($memBig, $gemBig, $type)
	{
		$params = array(
    		'conditions' => array(
				'OR' => array(
					array(
		        		'Signalation.member_big' => $memBig,
		        		'Signalation.gem_big' => $gemBig,
		        		'Signalation.type' => $type,
		        		'Signalation.status' => ACTIVE,
					),
					array(
		        		'Signalation.member_big' => $memBig,
		        		'Signalation.gem_big' => $gemBig,
		        		'Signalation.type' => $type,
		        		'Signalation.status' => INACTIVE,
						'Signalation.created >' => date('Y-m-d H:m', strtotime('- ' . API_RETAIN_CHECK_IN_FUNC . ' days'))
					),
				)
        	)
    	);
    	
    	$res = $this->find('first', $params);
    	if (empty($res))
    		return true;
    		
    	return false;
	}
	
}