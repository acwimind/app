<?php
class ExtraInfos extends AppModel {

	public $primaryKey = 'member_big';

	public $belongsTo = array(
		'Member' => array(
			'foreignKey' => 'member_big',
		),
		'Countries' => array(
			'foreignKey' => 'country_code'
		)
	);
	
	
	
	public function getMemberByExtraInfos($params)
	{
		$params = array(
				'conditions' => array('OR' => array ($params)),
				'recursive' => 1
		);
	
		return $this->find('all', $params);
	}
	
	public function CreateInfos($dataBig,$dataSex) {
		$res =false;
		//$comment = array ();
		/*	foreach ( $data as $column => $field ) {
		 if (isset ( $this->api [$field] )) {
		$comment [$column] = trim ( $data [$field] );
		}
		}
		*/
	
		$data=array ();
		$data['member_big']=$dataBig;
		// pessima idea ... 
		if (strtolower($dataSex)=='f') {
		$data['looking_for']='Maschi'	;
		}
		else 
		{
		$data['looking_for']='Femmine';
		}
		 
		 
		try {
			$res = $this->save ( $data );
		} catch ( Exception $e ) {
			//die(debug($e));
			$res = false;
		}
		return $res;
	}
    
    
     public function getExtraInfos($memBig)
    {
        $params = array(
                'conditions' => array('member_big' => $memBig),
                'recursive' => -1
        );
    
        $extraInfos=$this->find('first', $params);
        
        return($extraInfos);
    }
	
}