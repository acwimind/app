<?php

App::uses('CakeEmail', 'Network/Email');

class Emailer
{
	
    public static function sendEmail($templateName, $plainTextMessage, array $params, $subject, $recipient) {
		
	    CakeLog::debug('Emailer send email: '.$templateName.', BODY: '.$plainTextMessage . ' SUBJECT: ' . $subject . ' RECIPIENT: ' . $recipient);
	    if (empty($recipient))
	        return false;
	    
	    try {
	        
    	    $email = new CakeEmail('production');
            $email->to($recipient);
            $email->subject($subject);
            if (!empty($plainTextMessage))
            {
                $email->emailFormat('text');
                $email->send($plainTextMessage);
            }
            else
            {
                $email->template($templateName, 'default');
                $email->viewVars($params);
                $email->emailFormat('both');
                $email->send();
            }
            
            return true;
	    }
	    catch (Exception $exc)
	    {
	        CakeLog::error('Libs/Emailer::sendEmail / Exception: ' . $exc->getMessage() . "\n" . $exc->getTraceAsString());
	    }
	    
	    return false;
		
	}   
    
}