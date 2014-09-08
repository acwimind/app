<?php

class Signalation extends AppModel{
	
	public $belongsTo = array(
		'Gem',
		'Member'
	);
	
    
    public function addSignalation($memBig, $BadMemBig, $type, $text, $idObj)
    {
        $data = array(
            'Signalation' => array(
                'member_big' => $memBig,
                'gem_big' => $BadMemBig,
                'type' => $type,
                'text' => $text,
                'status' => ACTIVE,
                'created' => 'NOW()',
                'from_big' => $BadMemBig,
                'id_obj' => $idObj
            )
        );
                
        try {
            $res = $this->save($data);
            
        } catch (Exception $e) {
//            debug($e);
            $res = false;
            
        }
        
        return $res;
    }
    
    
	public function addSignalationOLD($memBig, $gemBig, $type, $text)
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
	public function canSignalOLD($memBig, $gemBig, $type)
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
	
    
    public function findBadMemBig($type,$idObj)
    {
        
        switch ($type){
            
            
            case 1 : // chatmessage
                        
                     $query = 'SELECT from_big as badbig '.
                              'FROM chat_messages '.
                              'WHERE id='.$idObj;
                     break;
                     
            case 2 : //photo
                     
                     $query = 'SELECT member_big as badbig '.
                              'FROM photos '.
                              'WHERE big='.$idObj;
                     break;         
            
            case 3 : //comment
            
                     $query = 'SELECT member_big as badbig '.
                              'FROM comments '.
                              'WHERE big='.$idObj;
                     break;
            
        }
        
           $db = $this->getDataSource ();
           $result = $db->fetchAll ( $query );
           
           
            
            if (empty ( $result ))
                return array ();
        
        
        return $result[0][0]['badbig'];
               
    }
       
    
    public function canSignal($memBig, $BadMemBig, $type, $idObj)
	{        
            
		$params = array(
    		'conditions' => array(
				'OR' => array(
					array(
		        		'Signalation.member_big' => $memBig,
		        		'Signalation.from_big' => $BadMemBig,
		        		'Signalation.type' => $type,
                        'Signalation.id_obj' => $idObj,
		        		'Signalation.status' => ACTIVE,
					),
					array(
		        		'Signalation.member_big' => $memBig,
		        		'Signalation.from_big' => $BadMemBig,
		        		'Signalation.type' => $type,
                        'Signalation.id_obj' => $idObj,
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