<?php
class CommentsController extends AppController {
	public $uses = array( 'Comment','Member','Wallet');//load these models
	
	
	
	/**
	 * get board content for logged user 
	 */
	public function api_getComments() {
		$this->_checkVars ( array (
				'checkin_big'
		) );
		
		$MyComments= $this->Comment->getComments($this->api ['checkin_big']);
	
		foreach ( $MyComments as $key => $val ) {
			
			$params = array (
					'conditions' => array (
							'Member.big' => $val['Comment']['member_big']
					),
					'recursive' => - 1
			);
			
			$data = $this->Member->find ( 'first', $params );
			
			unset ( $data ['Member'] ['password'] );
			unset ( $data ['Member'] ['salt'] );
			unset ( $data ['Member'] ['created'] );
			unset ( $data ['Member'] ['updated'] );
			unset ( $data ['Member'] ['last_mobile_activity'] );
			unset ( $data ['Member'] ['last_web_activity'] );
			unset ( $data ['Member'] ['status'] );
			unset ( $data ['Member'] ['type'] );
			
			if ($data ['Member'] ['photo_updated'] > 0) {
				$data ['Member'] ['profile_picture'] = $this->FileUrl->profile_picture ( $data ['Member'] ['big'], $data ['Member'] ['photo_updated'] );
			}
			else
			{
				// standard image
				$sexpic=2;
				if($data ['Member']['sex']=='f' )
				{
					$sexpic=3;
				}
					
				$data ['Member'] ['profile_picture'] = $this->FileUrl->profile_picture ( $sexpic );
					
			}
		
				//die(debug($val));
			$MyComments [$key] ['Member']=$data ['Member'];
				
			
			}
		
		$this->_apiOk ( $MyComments );
	}
	
	/**
	 * save a acooment
	 */
	public function api_saveComment() {
		
		// update existing member
	
	if ($this->Comment->saveComment($this->api ))	
	{
        if ($this->api['place_big']!=NULL AND $this->api['place_big']>0 AND $this->api['comment']!=''){
            
            //crediti e rank per inserimento commento
            $this->Wallet->addAmount($this->api['member_big'], '4', 'Inserimento Commento' );
            $this->Member->rank($this->api['member_big'],4); 
                        }
        $this->_apiOk ( __("Comment/Like Saved") );
	}
	else 
	{	
		$this->_apiEr( __("Commento o Like non salvato") );
	}
	}

}