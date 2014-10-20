<?php

class PushTokensController extends AppController{
	
	public function api_save()
	{
		$this->_checkVars(array('push_token', 'platform_id'), array('push_token_id'));
		
		$memBig = $this->logged['Member']['big'];
		$pushToken = $this->api['push_token'];
		$platformId = $this->api['platform_id'];
		$ptId = isset($this->api['push_token_id']) ? $this->api['push_token_id'] : null ;
		
		if (!empty($platformId) && !empty($ptId))
		{
			// If push_token_id is present, this should be an update of the token. Check if user has a push_token with this id inside function.
			$result = $this->PushToken->updatePushTokenInDb($memBig, $pushToken, $ptId, $platformId);
		}
		elseif (!empty($platformId))
        {
            // If push_token_id is not present, this should be a new registration
            $result = $this->PushToken->checkIfUniqueAndSave($pushToken, $memBig, $platformId);
        }
        
		
		if ($result !== false)
		{
			// Transform to appropriate shape
			$res = array(
				'PushToken' => array(
					'push_token_id' => $result['PushToken']['id'],
					'member_big' => $result['PushToken']['member_big'],
					'platform_id' => $result['PushToken']['platform'],
					'push_token' => $result['PushToken']['token'],
				)
			);
			
			$this->_apiOk($res);
		}
		else
		{
			$this->_apiEr(__('Error occured. Push token not saved.'));
		}
	}
	
}