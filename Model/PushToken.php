<?php

App::uses('Logger', 'Lib');

class PushToken extends AppModel {
	
	public $belongsTo = array(
		'Member'
	);
	
// TEST!!! 2015	protected static $apiKey = "AIzaSyC7rT2QGeKjHaE6NoRUPQ6i_6UtO7Dyeaw";
	
	protected static $apiKey = "AIzaSyDPwFwVvXhq60DqoagAyP-_xZQrcRYt4PM";
	
	public function sendNotification($title, $message, array $data, array $userIds, $type, $action)
    {
        Logger::Info('Called push/sendNotification with data: Ttl - 0 Msg - 1 Data - 2 Rcps - 3 Type - 4 Act - 5',
            array($title, $message, $data, $userIds, $type, $action));
        
        // TODO Initial checks e.g. if empty userIDs
        
        // Get tokens from DB by userids
        $regids = array();
        $dvcTkns = array();
        $winIds = array();
        // Recipients
        $andrRcps = array();
        $iosRcps = array();
        $winRcps = array();
        foreach ($userIds as $userId)
        {
            $tokens = $this->getPushTokens($userId);
            if (!empty($tokens))
            {
                if (isset($tokens['andr']))
                {
                    $regids = array_merge($regids, $tokens['andr']);
                    $andrRcps[] = $userId;
                }
                
                if (isset($tokens['ios']))
                {
                    $dvcTkns = array_merge($dvcTkns, $tokens['ios']);
                    $iosRcps[] = $userId;
                }
                
                if (isset($tokens['win']))
                {
                    $winIds = array_merge($winIds, $tokens['win']);
                    $winRcps[] = $userId;
                }
                
            }
        }
        
        // Decide which call to call :)
        if (!empty($regids))
        {
            $resultAndr = null;
            try {
                $resultAndr = $this->sendAndroidMsg($title, $message, $data, $andrRcps, $type, $action, $regids);
            }
            catch (Exception $e)
            {
                Logger::Error($e);
            }
            Logger::Info('Call push/sendNotification Android with result: 0', 
        	    array($resultAndr));
        }
        if (!empty($dvcTkns))
        {
            try {
                $this->sendIosMsg($title, $message, $data, $iosRcps, $type, $action, $dvcTkns);
            }
            catch (Exception $e)
            {
                Logger::Error($e);
            }
        }
        if (!empty($winIds))
        {
            // TODO Call WMS
        	try {
        		$this->sendWindowsMsg($title, $message, $data, $iosRcps, $type, $action, $winIds);
        	}
        	catch (Exception $e)
        	{
        		Logger::Error($e);
        	}
        }
        
        Logger::Info(' --------------------- Call push/sendNotification ended. ---------------------------------------------------');
        
//        print_r($regids);
//        die();
        
        // For testing purposes
//        echo 'Message: ' . $message;

        
        
        // For testing purposes
//        print_r(json_decode($result));
//        die();

    }
    
    private function sendWindowsMsg($title, $message, array $data, array $userIds, $type, $action, array $regids)
    {
    
    	/*
    	 * Lato server, quando deve essere inviata una notifica, deve essere composto un messagio nel seguente formato:
 
<?xml version=""1.0"" encoding=""utf-8""?>" & "<wp:Notification xmlns:wp=""WPNotification"">" & "<wp:Toast>" & "<wp:Text1>" & “TITOLO DELLA NOTIFICA” & "</wp:Text1>" & "<wp:Text2>" & “messaggio” & "</wp:Text2>" & "<wp:Param>/MainPage.xaml?NotifyType=TipoDiNotifica& ObjId=IdOggettoNotificato&</wp:Param>" & "</wp:Toast> " & "</wp:Notification>"
 
Dove TipoDiNotifica può avere uno dei seguenti valori: Message-FriendshipRequest-NewVisit (nel caso si vogliano notificare le visite al profilo, cosa che sconsiglio) e IdOggettoNotificato deve essere l’Id del messaggio, o del membro oggetto dell’azione.
 
Poi vanno aggiunti:
 
ContentType = "text/xml"
Headers ("X-WindowsPhone-Target", "toast")
Headers ("X-NotificationClass", "2")
    	 */
    	// TODO: manage notifications from friends?
    	$IDNotifica='0';
    	if (!empty($data['msg_id']))
    	{
    		$IDNotifica=$data['msg_id'];
    	}
    	
    	//die(debug());
    	
    	$toastMessage = "<?xml version=\"1.0\" encoding=\"utf-8\"?>" .
    			"<wp:Notification xmlns:wp=\"WPNotification\">" .
    			"<wp:Toast>" .
    			"<wp:Text1>" . $title . "</wp:Text1>" .
    			"<wp:Text2>" . $message . "</wp:Text2>" .
    			"<wp:Param>/MainPage.xaml?NotifyType=NavigatedFrom=Toast Notification&TipoDiNotifica=".$type."&ObjId=".$IDNotifica."</wp:Param></wp:Toast> </wp:Notification>";
    					



/* &ObjId=1&TipoDiNotifica=chat
 *    					".$TipoDiNotifica."& ObjId=IdOggettoNotificato&</wp:Param>".
    			"</wp:Toast> " .
    			"</wp:Notification>";
*/
//& "<wp:Text1>" & “TITOLO DELLA NOTIFICA” & "</wp:Text1>" & "<wp:Text2>" & “messaggio” & "</wp:Text2>" & "<wp:Param>/MainPage.xaml?NotifyType=TipoDiNotifica& ObjId=IdOggettoNotificato&</wp:Param>" & "</wp:Toast> " & "</wp:Notification>"
    	 
    	
    	
    	
    	$tokens = $this->getPushTokens($userIds[0]);
    	$url = $tokens['win'][0];
  //  	die(debug($url));
   // 	$url='http://am3.notify.live.net/throttledthirdparty/01.00/AQHaIU3PSNCoQ7QBdUSLO0PDAgAAAAADAQAAAAQUZm52OkJCMjg1QTg1QkZDMkUxREQFBkVVV0UwMQ';

    	// $url='http://s.notify.live.net/u/1/db3/HmQAAAB5iUVyRvhIYZsE9OnlSEArvLeTQTvgGTALSxjG6Rcf6PnkNOcp2vd9H_WAugVgKEBfZX0C6QdphkBFz5OMgAvJ/d2luZG93c3Bob25lZGVmYXVsdA/NqYqOzMjp0CBDY1uXPcYBg/cL58iuV_7x454ye0_tguN0OPvDo';
    	// Create request to send
    	$r = curl_init();
    	curl_setopt($r, CURLOPT_URL,  $url );
    	curl_setopt($r, CURLOPT_RETURNTRANSFER, 1);
    	curl_setopt($r, CURLOPT_POST, true);
    	curl_setopt($r, CURLOPT_HEADER, true);
    	
    	// add headers
    	$httpHeaders=array('Content-type: text/xml; charset=utf-8', 'X-WindowsPhone-Target: toast',
    			'Accept: application/*', 'X-NotificationClass: 2','Content-Length:'.strlen($toastMessage));
    	curl_setopt($r, CURLOPT_HTTPHEADER, $httpHeaders);
    	
    	// add message
    	curl_setopt($r, CURLOPT_POSTFIELDS, $toastMessage);
    	
    	// execute request
    	$output = curl_exec($r);
    	curl_close($r);
    //	Logger::Info( $output);

    }   
    
    private function sendAndroidMsg($title, $message, array $data, array $userIds, $type, $action, array $regids)
    {
    	//Log device tokens
    	Logger::Info('Device tokens: 0', $regids);
    	
        // Set POST variables
        $url = 'https://android.googleapis.com/gcm/send';
        
        
        
        $fields = array(
            'registration_ids'  => $regids,
            'data'              => array( 'ttl' => $title, 'msg' => $message, 'data' => $data, 'rcps' => $userIds, 'type' => $type, 'act' => $action ),
        );
        
        
        // rimettere . GOOGLE_API_KEY,
        $headers = array( 
            'Authorization: key=AIzaSyDPwFwVvXhq60DqoagAyP-_xZQrcRYt4PM',        		 
            'Content-Type: application/json'
        );
        
        // Open connection
        $ch = curl_init();
        
        // Set the url, number of POST vars, POST data
        curl_setopt( $ch, CURLOPT_URL, $url );
        curl_setopt ($ch, CURLOPT_POST, true );
        curl_setopt ($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt ($ch, CURLOPT_RETURNTRANSFER, true );
        curl_setopt ($ch, CURLOPT_VERBOSE, true);
        curl_setopt ($ch, CURLOPT_SSL_VERIFYHOST, 0); 
        curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt( $ch, CURLOPT_POSTFIELDS, json_encode( $fields ) );
        
        // Execute post
        $result = curl_exec($ch);
        
        if(curl_errno($ch))
        { 
            echo 'Curl error: ' . curl_error($ch); 
        }
        
        // Result processing
        $decRes = json_decode($result);
        
        if (is_object($decRes) && ($decRes->failure != 0 || $decRes->canonical_ids != 0))
        {
            $resArr = $decRes->results;
            
            foreach ($resArr as $key=>$res)
            {
                if (isset($res->message_id) && isset($res->registration_id))
                {
                    // Get the original registration id from array of reg. ids and update in database
                    $origKey = $regids[$key];
                    $this->updatePushTokenBy($origKey, $res->registration_id);
                }
                elseif (isset($res->error))
                {
                    // TODO Log error, advanced error handling
                    Logger::Error($res->error);
                    
                    // TODO Extend with additional errors handling
                    switch ($res->error)
                    {
                        case 'InvalidRegistration':
                        case 'NotRegistered':
                             $this->deleteSinglePushToken($regids[$key]);
                             break;       
                    }
                }
            }
        }
        
        // Close connection
        curl_close($ch);
        
        return $result;
    }
    
    private function sendIosMsg($title, $msg, array $data, array $userIds, $type, $action, array $dvcTkns)
    {
		//Log device tokens
    	Logger::Info('Device tokens: 0', array(implode(',', $dvcTkns)));
    	
		$dvcTkns = array_unique($dvcTkns);
			
        // Adjust to your timezone
        date_default_timezone_set('Europe/Rome');
        
        // Report all PHP errors
//        error_reporting(-1);
        
        // Using Autoload all classes are loaded on-demand
//        require_once 'modules/ApnsPHP/Autoload.php';
		
		App::import('Vendor', 'ApnsPHP_Autoload', array('file' => 'ApnsPHP' . DS . 'Autoload.php'));		
        
		$devTokens = array(
			'012342592f61d59678d604f7f39cb68ad2067c6876a7325ab6abcccf5161c36a',
			'bad45465459ea402165095e2a75c7b6dd627e2e1a946686e86af2b509492a678');
				        
		$messagesProd = array();
		$messagesDev = array();		
		
        foreach ($dvcTkns as $dvcTkn)
        {
			$message = null;
			
            // Instantiate a new Message with a single recipient
            try 
            {
                $message = new ApnsPHP_Message($dvcTkn);
            }
            catch (Exception $e)
            {
				$message = null;
				
                Logger::Error($e);
                $this->deleteSinglePushToken($dvcTkn);
				
                continue;
            }
			
			if ($message) {

				// Set a custom identifier. To get back this identifier use the getCustomIdentifier() method
				// over a ApnsPHP_Message object retrieved with the getErrors() message.
				$message->setCustomIdentifier("Message-Badge-3");

				// Set badge icon to number of unread messages
				if (!empty($data['unread']))
				{
	            	$message->setBadge($data['unread']);
				}
				
				// Set a simple welcome text
				$message->setText($title . ' - ' . $msg);

				// Play the default sound
				$message->setSound();

				unset($data['created']);
				unset($data['rel_id']);
				unset($data['msg_id']);
				
				// Set a custom property
				$message->setCustomProperty('data', $data);
				$message->setCustomProperty('rcps', $userIds);
				$message->setCustomProperty('type', $type);
				$message->setCustomProperty('act', $action);
				$message->setCustomProperty('ttl', $title);

				// Set the expiry value to 30 seconds
				$message->setExpiry(30);

				if (in_array($dvcTkn, $devTokens)) {
					Logger::Info('Message to send through SANDBOX');
					$messagesDev[] = $message;					
				}
				else {
					Logger::Info('Message to send through PRODUCTION');
					$messagesProd[] = $message;					
				}
				
				$message = null;								
			}
        }
		
		if (!empty($messagesDev)) {
			Logger::Info('Initializing Sandbox connection');
        	
	        // Instantiate a new ApnsPHP_Push object	        
	        $push = new ApnsPHP_Push(
		        ApnsPHP_Abstract::ENVIRONMENT_PRODUCTION,
		        'files/certificate/ck.pem'
	        );
	        
	        // Set the Provider Certificate passphrase
	        $push->setProviderCertificatePassphrase('iphonehaamble');
	        
	        // Set the Root Certificate Autority to verify the Apple remote peer
			//        $push->setRootCertificationAuthority('files/certificate/entrust_root_certification_authority.pem');
	        
	        // Connect to the Apple Push Notification Service
	        $push->connect();
			
			foreach($messagesDev as $msg)
			{
				$push->add($msg);
			}				
			$messagesDev = null;
			
			// Send all messages in the message queue
			$push->send();				
        
			// Disconnect from the Apple Push Notification Service
			$push->disconnect();
			
			// Examine the error message container
	        $aErrorQueue = $push->getErrors();
	        if (!empty($aErrorQueue)) {			
	            Logger::Info('Call push/sendNotification iOS on SANDBOX with error: 0', 
	    	        array($aErrorQueue));
	        }
			
			$push = null;
		}
		
		if (!empty($messagesProd)) {
			// Instantiate a new ApnsPHP_Push object FOR PRODUCTION        
			$pushProd = new ApnsPHP_Push(
				ApnsPHP_Abstract::ENVIRONMENT_SANDBOX,
		// TODO:  QUESTA PUSH CAMBIARE IN PRODUZIONE			ApnsPHP_Abstract::ENVIRONMENT_PRODUCTION,		
				'files/certificate/HaambleDist.pem'
			);

			// Set the Provider Certificate passphrase
			$pushProd->setProviderCertificatePassphrase('ansia');

			// Set the Root Certificate Autority to verify the Apple remote peer
			//        $push->setRootCertificationAuthority('files/certificate/entrust_root_certification_authority.pem');
			
			// Connect to the Apple Push Notification Service
			$pushProd->connect();
			
			foreach($messagesProd as $msg)
			{
				$pushProd->add($msg);
			}				
			$messagesProd = null;
			
			
			// Send all messages in the message queue
			$pushProd->send();

			// Disconnect from the Apple Push Notification Service
			$pushProd->disconnect();


			// Examine the error message container
			$aErrorQueue = $pushProd->getErrors();
			if (!empty($aErrorQueue)) {
	//        var_dump($aErrorQueue);
				Logger::Info('Call push/sendNotification iOS with error: 0', 
					array($aErrorQueue));
			}
		}        
    }
	
	public function insertPushTokenToDb($memBig, $pushToken, $platformId)
    {
        $data = array(
    		'PushToken' => array(
        		'member_big' => $memBig,
        		'token' => $pushToken,
        		'platform' => $platformId,
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
	
	public function checkIfUniqueAndSave($pushToken, $memBig, $platformId)
	{
		
		// Try to find the token in DB
        $pars = array(
        	'fields' => array('PushToken.*'),
        	'conditions' => array(
        		'PushToken.token' => $pushToken,
				'PushToken.platform' => $platformId
        	),
        	'recursive' => 0
        );
        
        $pToken = $this->find('first', $pars);
            
        // If not found, insert can be done            
        if (empty($pToken))
        {
            return $this->insertPushTokenToDb($memBig, $pushToken, $platformId);
        }
        
        // If found, check the user id, if the push token is for the same user, no changes need to be done
        if ($pToken['PushToken']['member_big'] == $memBig)
        {
			$params = array(
				'PushToken.token' => $pushToken,
				'PushToken.platform' => $platformId,
				'PushToken.id !=' => $pToken['PushToken']['id']				
			);
			$this->deleteAll($params);
		
            return $pToken;
        }

        // If the user IDs do not match, update the record
        $pToken['PushToken']['platform'] = $platformId;
        $pToken['PushToken']['member_big'] = $memBig;
        $res = $this->save($pToken);
				
		$params = array(
			'PushToken.token' => $pushToken,
			'PushToken.platform' => $platformId,
			'PushToken.id !=' => $pToken['PushToken']['id']    		
    	);
		$this->deleteAll($params);
        
        // If token was updated successfully return true
        if ($res !== FALSE)
        {
            return $res;
        }
        
        // If there was an error return false
        return $res;
		
	}
	
	public function updatePushTokenInDb($memBig, $pushToken, $ptId, $platformId)
    {
        // Tries to find push token in DB
        $pars = array(
        	'fields' => array(
        		'PushToken.*',
        	),
        	'conditions' => array(
        		'PushToken.id' => $ptId,
        	),
        	'recursive' => 0
        );
        
        $pToken = $this->find('first', $pars);
        
        // If found, update, if not found, insert
        if (!empty($pushToken))
        {
            $pToken['PushToken']['token'] = $pushToken;			
            $res = $this->save($pToken);
			
			$params = array(
				'PushToken.token' => $pushToken,
				'PushToken.platform' => $pToken['PushToken']['platform'],
				'PushToken.id !=' => $pToken['PushToken']['id']    		
			);
			$this->deleteAll($params);
        }
        else 
        {
            return $this->checkIfUniqueAndSave($pushToken, $memBig, $platformId);
        }
        
        if (!empty($res))
        {
            return $res;
        }
        
        return FALSE;
    }
	
	public function updatePushTokenBy($oldPushToken, $newPushToken )
    {
        // Tries to find push token in DB
        // Try to find the token in DB
        $pars = array(
        	'conditions' => array(
        		'PushToken.token' => $oldPushToken,
        	),
        );
        
        $pToken = $this->find('first', $pars);
        
        // If found, update, if not found, insert
        if (!empty($pToken))
        {
            $pToken['PushToken']['token'] = $newPushToken;
            $this->save($pToken);
        }
        else 
        {
            Logger::Error('Push Token not updated. OldToken: 0 NewToken: 1', array($oldPushToken, $newPushToken));
            return FALSE;
        }
        
        if (!empty($pToken))
        {
            return $pToken;
        }
        
        // TODO Log error
        return FALSE;
    }
    
    public function getPushTokens($memBig)
    {
    	// Tries to find push token in DB
        $pars = array(
        	'conditions' => array(
        		'PushToken.member_big' => $memBig,
        	),
        		'order' => array('PushToken.id DESC'),
        	'recursive' => -1
        );
        
        $pTokens = $this->find('all', $pars);
        
        if (!empty($pTokens))
        {
            $andr = array();
            $ios = array();
            $win = array();
            foreach ($pTokens as $ptoken)
            {
                switch ($ptoken['PushToken']['platform'])
                {
                    case PUSH_ANDROID:
                        $andr[] = $ptoken['PushToken']['token'];
                        break;
                    
                    case PUSH_IOS:
                        $ios[] = $ptoken['PushToken']['token'];
                        break;
                    
                    case PUSH_WINDOWS:
                        $win[] = $ptoken['PushToken']['token'];
                        break;
                    
                }
            }
            return array('andr' => $andr, 'ios' => $ios, 'win' => $win);
        }
        else
        {
            return array();
        }
    }
    
	
    
    public function deleteSinglePushToken($dvcToken)
    {
    	$params = array(
    		'conditions' => array(
    			'PushToken.token' => $dvcToken,
    		),
    		'recursive' => -1,
    	);
    	
    	$token = $this->find('first', $params);
    	
    	if (!empty($token))
    	{
    		$result = $this->delete($token['PushToken']['id']);
    		if (!$result)
    		{
    			Logger::Error('Token with id 0 not deleted', array($token['PushToken']['id']));
    		}
    	}
    	else
    	{
    		Logger::Error('deleteSinglePushToken Empty token');
    	}
    }
	
    public function deleteAllPushTokens($memBig)
    {
    	$params = array('PushToken.member_big' => $memBig);
    	
    	$result = $this->deleteAll($params, false);
    	if (!$result)
    	{
    		Logger::Error('Tokens with member_big 0 not deleted', array($memBig));
    	}
    	
    }
	
}