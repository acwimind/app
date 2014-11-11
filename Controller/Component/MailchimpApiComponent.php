<?php  

/* 
CakePHP Component for mailchimp.   
This component is not complete.  Much more you can do with the mailchimp API; 
For full documentation check: http://www.mailchimp.com/api/rtfm/ 
* 
* By Scott T. Murphy (hapascott) 2008 
*/ 

App::import('Vendor', 'mailchimp/mailchimp'); 

class MailchimpApiComponent extends Component { 

// Put your mailchimp API key here   
  var $_apiKey = 'b654ad1ba15c0d0df912c2258c9f2623';   

///*************LISTS********************************************************/ 
/***returns an array of all lists you have under your mailchimp account. * 
* 
*EXAMPLE: 
* 
Controller 
    function mc() { 
        $lists = $this->MCAPI->lists(); 
        $this->set('lists', $lists);  
    }  
* 
View (mc.ctp) 
  var_dump($lists); //to view the full array. 
* 
*/ 

function lists() { 
    $api = $this->_credentials(); 
    $retval = $api->lists(); 
    if (!$retval){ 
                $retval = $api->errorMessage; 
        }  
    return $retval; 
} 

///*************LIST ALL MEMBERS IN A LIST*****************************************************/ 
/***returns an array of all members you have under the specified mailchimp list * 
Example 
Controller 
    function mclist_view($id) { 
        $lists = $this->MailchimpApi->listMembers($id); 
        $this->set('id',$id); 
        $this->set('lists', $lists);  
    } 
* 
View (mclist_view.ctp) 
  var_dump($lists); //to view the full array. 
*/ 

function listMembers($id) { 
     
    $api = $this->_credentials(); 
     
    $retval = $api->listMembers( $id , 'subscribed', 0, 5000 ); 
    if (!$retval){ 
                $retval = $api->errorMessage; 
        }  
    return $retval; 
} 

function campaignList(){
    
    $api = $this->_credentials();
    
    $retval = $api->campaigns();
        
 if (!$retval){ 
                $retval = $api->errorMessage; 
     }  
 
 return $retval; 
    
}
function campaignSendTest($id,$ArrayEmail){
    
    $api = $this->_credentials();
    
    $retval = $api->campaignSendTest($id,$ArrayEmail);

 if (!$retval){ 
                $retval = $api->errorMessage; 
     }  
 
 return $retval; 
    
    
}


function campaignReplicate($id){
    
    $api = $this->_credentials();
    
    $retval = $api->campaignReplicate($id);

 if (!$retval){ 
                $retval = $api->errorMessage; 
     }  
 
 return $retval; 
    
    
}


function campaignSendNow($id){
    
    $api = $this->_credentials();
    
    $retval = $api->campaignSendNow($id);

 if (!$retval){ 
                $retval = $api->errorMessage; 
     }  
 
 return $retval; 
    
}




///*****ADD MEMBER TO A LIST*******************************// 
//Used to save the user's info to your subscription list. 
/* 
Example: 
  $add = $this->MailchimpApi->addMembers($user_email, $id); 
    if($add) { 
        $this->Session->setFlash('Successfully added user to your list.'); 
    } else { 
        $this->Session->setFlash('Oops, something went wrong.  Email was not added to your user.'); 
    } 
  $this->redirect(array('action'=>'mclist_view', 'id'=> $id)); 
    */ 

function addMembers($list_id, $email, $first, $last) { 
        $api = $this->_credentials(); 
         
        $merge_vars = array('FNAME'=> $first, 'LNAME'=> $last); 
        if(empty($merge_vars)) { 
            $merge_vars = array(''); 
        } 
        $retval = $api->listSubscribe($list_id, $email, $merge_vars ); 
        if (!$retval){ 
                $retval = $api->errorMessage; 
        }  
        return $retval; 
} 


//****UNSUBSCRIBE OR REMOVE MEMBER FROM A LIST********************// 
//Use to remove a particular user from a list.   
//returns true if success else return false. 
/*Example usage: 
*function mc_remove($user_email,$id) { 
    $remove = $this->MailchimpApi->remove($user_email, $id); 
    if($remove) { 
        $this->Session->setFlash('Email successfully removed from your list.'); 
    } else { 
        $this->Session->setFlash('Oops, something went wrong.  Email was not removed from the list.'); 
    } 
       $this->redirect(array('action'=>'mclist_view', 'id'=> $id)); 
} 
*/ 

function remove($user_email,$id) { 
$api = $this->_credentials(); 

$retval = $api->listUnsubscribe($id,$user_email); 
if (!$retval){ 
   return false; 
   exit(); 
} else { 
    return true; 
    exit(); 
} 

} 
//***MailChimp Auth**/ 
function _credentials() {  
    $api = new MCAPI($this->_apiKey);  
     
    if ($api->errorCode!=''){  
        $retval = $api->errorMessage;  
        echo $retval; die;  
        exit();  
    }  
    return $api;  
}    

}  


?>