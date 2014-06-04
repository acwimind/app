<?php

App::uses('CronLog', 'Lib');

class CheckoutShell extends AppShell {
    
	public $uses = array('Checkin');
	
	public function main() {
		date_default_timezone_set('Europe/Rome');
		CronLog::Info('----------- Checkout job start -------------');
		// Get inactive members having active checkins
		$memberBigs = $this->Checkin->findInactiveMembers();
		if (!empty($memberBigs))
		{
			
			CronLog::Debug('Member to be checked out - 0', array($memberBigs));
		
//			$log = $this->Checkin->getDataSource()->getLog(false, false);
//			debug($log);

			// Check them out
			$result = $this->Checkin->checkout($memberBigs, true);
			$res = is_a($result, 'PDOStatement') ? $result->rowCount() . ' checkout(s)' : $result;
			CronLog::Info('Result - 0', array($res));
		}
		else 
		{
			CronLog::Debug('No Members to be checked out');
		}
//        $this->out($result);
        CronLog::Info('----------- Checkout job end -------------');
    }
    
}