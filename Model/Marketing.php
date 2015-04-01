<?php
  
  App::import('Component', 'Mandrill');        

  class Marketing extends AppModel {
	
    public $useTable = false;
         
    
     public function messagesSend_template($params){
         
         
         $this->Mandrill->messagesSend_template($params);
         
         
     }
    
    
    
     public function scheduleBonus($op,$elem,$msg){
       //schedula Bonus
       
       $query="SELECT member_id,reason,amount ".
              "FROM tmp_bonus ".
              "WHERE operation=$op AND status=0 ".
              "ORDER BY member_id ".
              "LIMIT $elem";
                           
       $sent=0;
       
       $db = $this->getDataSource();
       $WalletModel = ClassRegistry::init('Wallet');
          
       
       try {
                $utenti=$db->fetchAll($query);
        }
        catch (Exception $e)
        {
            debug($e);
            return false;
        }
       
        
        if (count($utenti>0)){//ci sono utenti da processare
        
            
            foreach($utenti as $key=>$val){
            
                
                $memid=$val[0]['member_id'];
                $WalletModel->addAmount($val[0]['member_id'],$val[0]['amount'],$val[0]['reason']);
                $this->chatBonusMsg($val[0]['member_id'],$msg);           
            
                $update="UPDATE tmp_bonus ".
                        "SET status=1 ".
                        "WHERE member_id=$memid AND operation=$op ";
                 
                 try {
                        $db->fetchAll($update);
                        }
                        catch (Exception $e)
                        {
                            debug($e);
                            return false;
                        }
                 $sent+=1;       
            }
                    
        }
        $result['spediti']=$sent;
        $result['programmati']=$elem;
        $this->_apiOK($result);                 
   } 
     
       
        public function chatBonusMsg($partnerMember,$textMsg) {
      /* Questo metodo invia notifiche via chat con l'utente Haamble */  
        //$this->ChatCache=new ChatCacheComponent(null); 
        //$this->ChatCache->initialize($this->Controller);
        
        $MemberSettingModel = ClassRegistry::init('MemberSetting');
        $MemberRelModel = ClassRegistry::init('MemberRel');
        $ChatMessageModel = ClassRegistry::init('ChatMessage');
        $PrivacySettingModel = ClassRegistry::init('PrivacySetting');
        $PushTokenModel = ClassRegistry::init('PushToken');
                   
        $memBig = 90644;
        $partnerBig = $partnerMember;
        $text = $textMsg;
        $relId = null;
        $checkinBig = null;
        $xfoto = null;
         
        $newerThan = null;
        
        /*
         * Check if user is not in partners ignore list Find checkins -> because of status and checkin big Find ,potentially create member_rel If users are not checked in at the same place, they have to have a memberRel record (chat started based on previous conversation) Save to DB
         */
                                   
        // Find relationship in member_rels table
        
        $memRel = $this->findRel($partnerBig);
            
        if (empty ( $memRel ) OR ($memRel==0)) {
            // Create a new one
            $relationship = array (
                    'member1_big' => $memBig,
                    'member2_big' => $partnerBig 
            );
            $MemberRelModel->create();
            $MemberRelModel->set( $relationship );
            try {
                $memRel = $MemberRelModel->save();
                //$relId = $memRel ['MemberRel'] ['id'];
                $this->log("RELID ".$relId);
                                              
            } catch ( Exception $e ) {
                $this->_apiEr ( __('Error occured. Relationship not created.') );
            }
        } else {
            $relId = $this->findRel($partnerBig);
        }
                // Create chat message record
        $relation=$relId;
                 
        $message = array (
                'rel_id' => $relation,
                'from_big' => $memBig,
                'to_big' => $partnerBig,
                'checkin_big' => $checkinBig,
                'text' => $text,
                'from_status' => 1, // from status = 1 (not deleted)
                'to_status' => 1, // tp status = 1 (not deleted)
                'created' => 'NOW()',
                'status' => 1 
                  );
        
        $ChatMessageModel->create();
        $ChatMessageModel->set( $message );
        $msgId = null;
        $chatMsg = null;
        try {
            $res = $ChatMessageModel->save();
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
            
            
            $chatMsg = $ChatMessageModel->find ( 'first', $pars );
                    
        
        } catch ( Exception $e ) {
            $this->_apiEr ( __('Error occured. Message not created.') );
        }
        
        //$this->ChatCache->write ( $partnerBig . '_last_msg', strtotime ( $chatMsg ['ChatMessage'] ['created'] ) );
        
        // Determine number of unread messages
        $unreadCount = $ChatMessageModel->getUnreadMsgCount ( $partnerBig );
                
        // Send push notifications
        $privacySettings=$PrivacySettingModel->getPrivacySettings($partnerBig);
        $privacySettings=$privacySettings[0]['PrivacySetting'];
        $notifyChatMessages=$privacySettings['notifychatmessages'];
        
        $goonPrivacy=true;
        
        if (count($privacySettings)>0 AND $notifyChatMessages == 0)
        {
             $goonPrivacy=false;
            
        }
         //$this->log("goonPrivacy ".intval($goonPrivacy));
        if ($goonPrivacy)
        {
        $strLen = 50;
        
        $name = 'Haamble';
                
        $msg = (strlen ( $text ) > $strLen + 4) ? substr ( $text, 0, $strLen ) . ($text [$strLen + 1] == ' ' ? ' ...' : '...') : $text;
        $PushTokenModel->sendNotification ( $name, $msg, array (
                'partner_big' => $memBig,
                'created' => $chatMsg ['ChatMessage'] ['created'],
                'rel_id' => $chatMsg ['ChatMessage'] ['rel_id'],
                'msg_id' => $chatMsg ['ChatMessage'] ['id'],
                'unread' => $unreadCount 
        ), array (
                $partnerBig 
        ), 'chat', 'new' );
        
        }
        // return chat messages like in the receive call with refresh enabled
        $newMsgs = $ChatMessageModel->findConversations ( $memBig, $partnerBig, null, $newerThan, 0, true );
        
        // Mark mesaages as read
        if (! empty ( $newMsgs ['chat_messages'] )) {
            $updated = $ChatMessageModel->markAsRead ( $memBig, $partnerBig );
            if (! $updated)
                CakeLog::warning ( 'Messages not marked as read. Membig ' . $memBig . ' Partner big ' . $partnerBig );
               
        }
        
        //$newMsgs['chat_messages'][count($newMsgs['chat_messages'])-1]['photo']=$this->FileUrl->chatmsg_picture($msgId);
        
    }
         
    
     
    public function findRel($member)
    {
        $db = $this->getDataSource();
                       
        $query="SELECT id FROM member_rels ".
               "WHERE (member1_big=90644 AND member2_big=$member) OR (member1_big=$member AND member2_big=90644)";
                    
        $id=$db->fetchAll($query);
        $result=$id[0][0]['id'];
        
        
        return $result;
    }
     
}