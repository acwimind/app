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
    
    
    public function hasActiveService($servicesList,$memberBig){
        /* return numero di servizi attivi tra quelli specificati
        * 
        * $servicesList = array che contiene gli id dei servizi da verificare
        * $memberBig = id utente di cui verificare i servizi attivi
        */
        
               
         $params = array(
            'conditions' => array(
                'Wallet.member1_big' => $memberBig,
                'Wallet.expirationdate >' => 'NOW()',
                'Wallet.product_id' => $servicesList,              
                 ));
          
        $result=$this->find('count', $params);
        
        return $result;
                        
    }
    
    public function getCredit_2($memberBig)
    {
        $params = array(
            'conditions' => array(
                'member1_big' => $memberBig
                             ),
            'fields' => array(
                 'SUM(amount) AS Credit'));
          
        $getCredit=$this->find('first', $params);
        
        $getCredit=$getCredit[0];
        
        return $getCredit;
                   
    }
    
    public function sendChatNotification() {
         
        //default system member
        $memBig=90644;
        $checkinBig = null;
        $partnerBig=45545831;
        $textmsg="Complimenti hai raggiunto 100 crediti";
        $ChatMessage = ClassRegistry::init('ChatMessage');
        $MemberRel = ClassRegistry::init('MemberRel');
        $PushToken = ClassRegistry::init('PushToken');
        
        // Find relationship in member_rels table
        $memRel = $MemberRel->findRelationships ( $memBig, $partnerBig );  //ChatMessage->
                       
        if (empty ( $memRel )) {
            // Create a new one
            $relationship = array (
                    'member1_big' => $memBig,
                    'member2_big' => $partnerBig 
            );
            $MemberRel->set ( $relationship ); //ChatMessage->
            try {
                $memRel = $MemberRel->save ();  //ChatMessage->
                $relId = $memRel ['MemberRel'] ['id'];
                                
            } catch ( Exception $e ) {
                $this->_apiEr ( __('Error occured. Relationship not created.') );
            }
        } else {
            $relId = $memRel ['MemberRel'] ['id'];
        }
        
        $message = array (
                'rel_id' => $relId,
                'from_big' => $memBig,
                'to_big' => $partnerBig,
                'checkin_big' => $checkinBig,
                'text' => $textmsg,
                'from_status' => 1, // from status = 1 (not deleted)
                'to_status' => 1, // tp status = 1 (not deleted)
                'created' => 'NOW()',
                'status' => 1 
        // 'photo' => $hasphoto,
                );
        
        // $this->Model->getLastInsertId();
        
        $ChatMessage->set ( $message );
        $msgId = null;
        $chatMsg = null;
        try {
            $res = $ChatMessage->save ();
            $result = ($res) ? true : false;
            
            $msgId = $res ['ChatMessage'] ['id'];
            $pars = array (
                    'conditions' => array (
                            'ChatMessage.id' => $msgId 
                    ),
                    'fields' => array (
                            'ChatMessage.id',
                            'ChatMessage.rel_id',
                            'ChatMessage.created' 
                    ),
                    'recursive' => - 1 
            );
          
          $chatMsg = $ChatMessage->find ( 'first', $pars );  
          
        } catch ( Exception $e ) {
            $this->_apiEr ( __('Error occured. Message not created.') );
        }
        
                
        // Send push notifications
               
        $unreadCount = $ChatMessage->getUnreadMsgCount ( $partnerBig ); 
        $strLen = 50;
        $name = 'Haamble';
        $msg = (strlen ( $textmsg ) > $strLen + 4) ? substr ( $textmsg, 0, $strLen ) . ($textmsg [$strLen + 1] == ' ' ? ' ...' : '...') : $textmsg;
        $PushToken->sendNotification ( $name, $msg, array (
                'partner_big' => $memBig,
                'created' => $chatMsg ['ChatMessage'] ['created'],
                'rel_id' => $chatMsg ['ChatMessage'] ['rel_id'],
                'msg_id' => $chatMsg ['ChatMessage'] ['id'],
                // 'timestamp' => time(),
                'unread' => $unreadCount 
        ), array (
                $partnerBig 
        ), 'chat', 'new' );
        
         if ($result !== false) {
            //$this->Util->transform_name ( $chatMsg );
            //$this->Util->transform_name ( $newMsgs );
            $this->_apiOk ( $chatMsg );
            $this->_apiOk ( $newMsgs );
            
            
        } else {
            $this->_apiEr ( __('Error occured. Message not sent.') );
        }        
    }
    
}
?>