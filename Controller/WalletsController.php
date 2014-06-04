<?php
class WalletsController extends AppController {
	public $uses = array (
			'Wallet',
			'Member'
	);
	
	

	/**
	 * Add credits to wallet 
	 */
	public function api_addCredit() {
		
		$this->_checkVars ( array (
				'idMember','amount'
		) );
		
		
		$idMember=$this->api['idMember'];
		$amount =$this->api['amount'];
		
		$reason='';
		if (isset ( $this->request->data['reason'] ) && $this->request->data['reason'] != null) {
			$reason=$this->request->data['reason'];
			}

	   if ( $this->Wallet->addAmount($idMember,$amount,$reason))
	   {
		
       $this->_apiOk($amount );
	   }
	else  {
					$this->_apiEr("Amount not added");
					
				}
				

	}
	
	
	
	/**
	* Return user credit 
	 */
	public function api_getCredit() {
	
		$this->_checkVars ( array (
				'idMember'
		) );
	
	
		$idMember=$this->api['idMember'];
		
	
	
	
		$xresponse = $this->Wallet->getCredit($idMember);
	   
		$xami = array();
	
	
	
		$this->_apiOk($xresponse );
	
	}
	
	
	/**
	 * Return loged user credit
	 */
	public function api_getMyCredit() {
	
		$this->_checkVars ( array (
				'member_big'
		) );
	
	
		$idMember=$this->api['member_big'];
	
	
	
	
		$xresponse = $this->Wallet->getCredit($idMember);
	
		$xami = array();
	
	
	
		$this->_apiOk($xresponse );
	
	}
	
}