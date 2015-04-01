<?php
class PrivacySettingsController extends AppController {
	public $uses = array( 'PrivacySetting','Member');//load these models
	
	
	
	/**
	 * get PrivacySettings for logged user 
	 */
	public function api_getPrivacySettings() {
		$this->_checkVars ( array (
				'member_big'
		) );
		
		$MyComments= $this->PrivacySetting->getPrivacySettings($this->api ['member_big']);
	
	/*	foreach ( $MyComments as $key => $val ) {
			
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
		*/
		$this->_apiOk ( $MyComments );
	}
	
	/**
	 * save a acooment
	 */
	public function api_setPrivacySettings() {
		
		// update existing member
	
	if ($this->PrivacySetting->savePrivacySettings($this->api ))	
	{
        $this->Member->rank($this->api ['member_big'],5);//rank +5 cambio impostaz privacy
        $this->_apiOk ( __("PrivacySettings Saved") );
	}
	else 
	{	
		$this->_apiEr( __("Impostazioni Privacy non salvate") );
	}
	}

}