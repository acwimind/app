<?php

class Wallet extends AppModel{
	

	
	public function addAmount($memBig, $amount,$reason)
	{
		$return=false;
		
		$data=array(
				'member1_big' => $memBig,
				'amount' => $amount,
				'reason' => $reason
		);
	
		if ($this->save($data))
		{
			$return=true;
		}
		
		return $return;
		
	} 
	
	public function getCredit($memberBig)
	{
		// SUM of the wallet 
		
				$db = $this->getDataSource ();
		
		$MySql= 'SELECT sum(wallets.amount) as Credit FROM public.wallets WHERE wallets.member1_big = '.$memberBig;
		// try {
		$result = $db->fetchAll ( $MySql );
		
		return $result;
			
		
	}
	
	
}