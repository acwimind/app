<?php

class MemberSetting extends AppModel{
	
	public $belongsTo = array(
		'Sender' => array(
			'className' => 'Member',
			'foreignKey' => 'from_big'
		),
		'Recipient' => array(
			'className' => 'Member',
			'foreignKey' => 'to_big'
		),
	);
	
	public function addToIgnoreList($memBig, $ignoredBig)
	{
		// Check if record for this two users already exists
		$params = array(
			'fields' => array(
				'MemberSetting.id',
				'MemberSetting.chat_ignore',
			),
			'conditions' => array(
				'MemberSetting.from_big' => $memBig,
				'MemberSetting.to_big' => $ignoredBig,
			 ),
			 'recursive' => 0
		);
		$ign = $this->find('first', $params);
		
		if (empty($ign))
		{
			
			$data = array(
	    		'MemberSetting' => array(
	        		'from_big' => $memBig,
	        		'to_big' => $ignoredBig,
	        		'chat_ignore' => 1,
	        	)
	    	);
	    	try {
		    	$res = $this->save($data);
	    		
	    	} catch (Exception $e) {
	//    		debug($e);
				$res = false;
				
	    	}
    	
		}
		else 
		{
			$ign['MemberSetting']['chat_ignore'] = 1;
			$res = $this->save($ign);
		}	
		
    	return $res;
	} 
	
	public function isOnIgnoreList($memberBig, $ignoredBig)
	{
		$params = array(
			'conditions' => array(
				'MemberSetting.from_big' => $memberBig,
				'MemberSetting.to_big' => $ignoredBig,
				'MemberSetting.chat_ignore' => 1,
			 ),
		);
		$ign = $this->find('all', $params);
		
		if (empty($ign))
			return false;
			
		return true;
	}
	
    
    public function isOnIgnoreListDual($memberBig, $ignoredBig)
    {
                     
        $params = array(
            'conditions' => array(
                
                'AND' => array(
                
                'AND' => array (
                    'OR' => array (
                    
                                'MemberSetting.from_big' => $memberBig,
                                'MemberSetting.to_big' =>   $memberBig
                    ),array(
                    'OR' => array(
                                'MemberSetting.from_big' => $ignoredBig,
                                'MemberSetting.to_big' =>   $ignoredBig
                                ))),
                                
                    'MemberSetting.chat_ignore' => 1
                    ),
             ),
        );
        
        $ign = $this->find('all', $params);
               

        if (empty($ign))
            return false;
            
        return true;
    }
    
    
	public function removeFromIgnoreList($memberBig, $ignoredBig)
	{
		$params = array(
			'fields' => array(
				'MemberSetting.id',
				'MemberSetting.chat_ignore',
			),
			'conditions' => array(
				'MemberSetting.from_big' => $memberBig,
				'MemberSetting.to_big' => $ignoredBig,
				'MemberSetting.chat_ignore' => 1,
			 ),
			 'recursive' => 0,
		);
		$ign = $this->find('all', $params);
		
		if (empty($ign))
			return false;

		foreach ($ign as $val)
		{
			$val['MemberSetting']['chat_ignore'] = 0;
			$res = $this->save($val);
			if ($res === FALSE)
				return $res;
		}
		return true;
	}
	
}