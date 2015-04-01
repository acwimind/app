<?php
          
class MarketingController extends AppController {
	
    public $uses = array (
           
            'Member',
            'ProfileVisit',
            'ChatMessage',
            'PushToken',
            'Friend',
            'PrivacySetting',
            'MemberSetting',
            'Wallet'
            
    ); // load these models
    
    var $components = array('MailchimpApi');
    
     
	public function api_loginAnalyzer($day=2) {
		//Verifica se è stato fatto un login dopo day giorni dall'iscrizione
        // casi 4,9
        $hoursStart=$day*24;
        $hoursStop=($day+1)*24;
        
        $db = $this->Member->getDataSource();
		$query="SELECT name,surname,email ".
               "FROM members ".
               "WHERE created=updated ".
               "AND created <= NOW() - interval '$hoursStart hours' AND created > NOW() - interval '$hoursStop hours' ".
               "AND last_web_activity IS NULL ".
               "AND last_mobile_activity IS NULL ".
               "AND status<255";
        //print($query);
        try {
             $membersArray=$db->fetchAll($query);
                          //print_r($membersArray);
        }
        catch (Exception $e)
        {
            debug($e);
            return false;
        }
          
         //print_r($members);       
        $this->_apiOk($membersArray);
    }
    
    
    public function api_photoAnalyzer() {
        //Verifica se è stato fatto l'upload della foto profilo entro day giorni
        //caso 5 
        $day=3;
        
        $hoursStart=($day)*24;
        $hoursStop=($day+1)*24;
        
        $db = $this->Member->getDataSource();
        $query="SELECT name,surname,email ".
               "FROM members ".
               "WHERE photo_updated IS NULL ".
               "AND created <= NOW() - interval '$hoursStart hours' AND created > NOW() - interval '$hoursStop hours' ".
               "AND status<255";
        
        try {
             $membersArray=$db->fetchAll($query);
        }
        catch (Exception $e)
        {
            debug($e);
            return false;
        }

        $this->_apiOk($membersArray);
        
    }
    
   
    public function api_contactUploadAnalyzer($day=7) {
        //Verifica se è stato fatto l'upload dei contatti entro day giorni
        // casi 6,7       
        $hoursStart=$day*24;
        $hoursStop=($day+1)*24;
        
        $db = $this->Member->getDataSource();
        $query="SELECT members.name,members.surname,members.email ".
               "FROM members ".
               "LEFT JOIN contacts ON members.big=contacts.member_big ".
               "WHERE members.status<255 ".
               "AND contacts.name IS NULL AND contacts.email IS NULL AND contacts.phone IS NULL ".
               "AND members.created <= NOW() - interval '$hoursStart hours' AND members.created > NOW() - interval '$hoursStop hours' ";
        
        try {
             $membersArray=$db->fetchAll($query);
        }
        catch (Exception $e)
        {
            debug($e);
            return false;
        }

       $this->_apiOk($membersArray);
        
    }
	
       public function api_checkinAnalyzer() {
        //Verifica se è stato fatto un checkin entro day giorni
        //caso 8
        $day=10;
        
        $hoursStart=$day*24;
        $hoursStop=($day+1)*24;
        
        $db = $this->Member->getDataSource();
        $query="SELECT members.name,members.surname,members.email ".
               "FROM members ".
               "LEFT JOIN checkins ON members.big=checkins.member_big ".
               "WHERE members.status<255 ".
               "AND checkins.event_big IS NULL AND checkins.created IS NULL ".
               "AND members.created <= NOW() - interval '$hoursStart hours' AND members.created > NOW() - interval '$hoursStop hours' ";
        
        try {
             $membersArray=$db->fetchAll($query);
        }
        catch (Exception $e)
        {
            debug($e);
            return false;
        }

        $this->_apiOk($membersArray);
        
    }
    
      public function api_friendsRequestAnalyzer() {
        //Verifica se in un certo tempo T sono state ricevute X richieste di amicizia
        //caso 10
        $days=7;
        $minrequests=20;
        
        $hoursStart=$day*24;
                
        $db = $this->Member->getDataSource();
        $query="SELECT members.name,members.surname,members.email,R.requests FROM ( ".
               "SELECT member2_big, COUNT(*) AS requests ".
               "FROM friends ".
               "WHERE status='R' AND created >= NOW() - interval '$days days' ".
               "GROUP BY member2_big ".
               ") AS R ".
               "JOIN members ON R.member2_big=members.big ".
               "WHERE requests>=$minrequests AND members.status<255 ".
               //"ORDER BY requests DESC ".
               "";
        
        try {
             $membersArray=$db->fetchAll($query);
        }
        catch (Exception $e)
        {
            debug($e);
            return false;
        }

        $this->_apiOk($membersArray);
        
    }
	
    
     public function api_visitsAnalyzer() {
        //Verifica quante visite di membri distinti sono state ricevute in un certo tempo T
        //caso 12
        $days=7;
        $minvisits=20;
        $hoursStart=$day*24;
                
        $db = $this->Member->getDataSource();
        $query="SELECT members.name,members.surname,members.email,visits ".
               "FROM ( ".
                    "SELECT visited_big,COUNT(*) as visits ".
                    "FROM ".
                    "(SELECT DISTINCT(visitor_big),visited_big ".
                        "FROM profile_visits ".
                        "WHERE created>=NOW() - interval '$days days' ".
                        ") AS T ".
                    "GROUP BY visited_big ".
                    ") AS Z ".
               "JOIN members ON members.big=Z.visited_big ".
               "WHERE visits>=$minvisits AND members.status<255";
                      
        try {
             $membersArray=$db->fetchAll($query);
        }
        catch (Exception $e)
        {
            debug($e);
            return false;
        }

        $this->_apiOk($membersArray);
        
    }
    
   public function api_campaignList(){
       
       $this->_apiOK($this->MailchimpApi->campaignList());
       
       
   } 
    
   public function api_campaignReplicate(){
       
       
      $this->_apiOK($this->MailchimpApi->campaignReplicate("74f24e0301")); 
       
       
   } 
    
    
    public function api_campaignUpdate(){
       
      $value='8b7eef3ed5'; 
      $this->_apiOK($this->MailchimpApi->campaignUpdate("74f24e0301","list_id",$value)); 
       
       
   } 
    
    
    public function api_campaignSendTest(){
       
      $email=array("robcarda@gmail.com","alessandro.ciaccia@haamble.com"); 
      $this->_apiOK($this->MailchimpApi->campaignSendTest("74f24e0301",$email)); 
       
       
   }
   
   public function api_fakeVisit(){
       
       
           $db = $this->Member->getDataSource();
           
           $fakeUser=array('45920400','45920407','45920410','45920414','45920420','45920424','45920427','45920433','45920439','45920442','45920447',
                           '45920452','45920457','45920462','45920468','45920472','45920476','45920479','45920482','45920485','45920488','45920491',
                           '45920494','45920497','45920500','45933295','45933298','45933301','45933304','45933307','45933310','45933313','45933316',
                           '45933319','45933322','45933325','45933328','45933331','45933334','45933337','45933340','45933343','45933346','45933349',
                           '45933352','45933355','45933358','45933361','45933364','45933367','45933370','45933373','45933376','45933379');
                           
           $visitedUser=array();
           
           
           $fakeUserList=implode(',',$fakeUser);
           
           $query="SELECT big,sex ".
                  "FROM members ".
                  "WHERE status<255 AND big IN ($fakeUserList)";  
           
           try {
                $fakeUserSex=$db->fetchAll($query);
        }
        catch (Exception $e)
        {
            debug($e);
            return false;
        }
           
                      
           foreach($fakeUserSex as $key=>$val){
           //separa fake maschi da fake femmine
               
               if ($val[0]['sex']=='m'){
                   
                   $maleFakeUser[]=$val[0]['big'];
                   
               } else {
                   
                   $femaleFakeUser[]=$val[0]['big'];
                   
               }           
               
           }
                      
           
           $indexMale=count($maleFakeUser)-1;
           $indexFemale=count($femaleFakeUser)-1;
           
           
           $query_filtrata="SELECT m.big,m.sex ".
                  "FROM members m ".
                  "LEFT JOIN profile_visits pv ON m.big=pv.visitor_big ".
                  "WHERE (((pv.created <= NOW() - interval '24 hours') AND (pv.created > NOW() - interval '72 hours')) OR pv.created IS NULL) ".
                  "AND m.big NOT IN (" . $fakeUserList. ") AND m.status<255 ".
                  "ORDER BY pv.created DESC ".
                  "LIMIT 30";
           
           $query="SELECT m.big,m.sex ".
                  "FROM members m ".
                  "WHERE m.status<255 ";
                             
              try {
                    $contactList=$db->fetchAll($query);
                    }
              catch (Exception $e)
              {
                debug($e);
                return false;
                }
           
           foreach($contactList as $key=>$val){
               
               
               if ($val[0]['sex']=='m'){
                   
                    //fake femmina random
                    $index=mt_rand(0,$indexFemale);
                    $fakeVisitor=$femaleFakeUser[$index];
                    $this->ProfileVisit->create(); 
                    $resultFemale[]=$this->ProfileVisit->saveVisit($fakeVisitor,$val[0]['big']);
                   
                   
               } else {
                   
                    //fake maschio random
                    $index=mt_rand(0,$indexMale);
                    $fakeVisitor=$maleFakeUser[$index];
                    $this->ProfileVisit->create(); 
                    $resultMale[]=$this->ProfileVisit->saveVisit($fakeVisitor,$val[0]['big']);
                   
                   
               }
               
               
           }
           
            $result['VisitorMale']=count($resultMale);
            $result['VisitorFemale']=count($resultFemale);
           //$result=$this->ProfileVisit->saveVisit(45710937,45517058);
           
           $this->_apiOK($result); 
       
   }
   
        public function api_fakeChat(){
       
           set_time_limit(0); //evita timeout con corrispondente file internal error di cake
           $db = $this->Member->getDataSource();
           
           $fakeUser=array('45920400','45920407','45920410','45920414','45920420','45920424','45920427','45920433','45920439','45920442','45920447',
                           '45920452','45920457','45920462','45920468','45920472','45920476','45920479','45920482','45920485','45920488','45920491',
                           '45920494','45920497','45920500','45933295','45933298','45933301','45933304','45933307','45933310','45933313','45933316',
                           '45933319','45933322','45933325','45933328','45933331','45933334','45933337','45933340','45933343','45933346','45933349',
                           '45933352','45933355','45933358','45933361','45933364','45933367','45933370','45933373','45933376','45933379');
                           
           
           
           $fakeMessageMale=array('Ciao','Ciao come va ?','Ciao ti posso disturbare ?');
           $fakeMessageFemale=array('Ciao','Ciao dove ti trovi ?','Ehi di dove sei ?');
           
           //contatti test
           //sostituisce la query che recupera i membri 396-408
           /*$contactList[0][0]['big']=45517058;
           $contactList[0][0]['sex']='f';
           $contactList[1][0]['big']=45545831;
           $contactList[1][0]['sex']='f';     
           $contactList[2][0]['big']=45710937;
           $contactList[2][0]['sex']='f';
           $contactList[3][0]['big']=44548401;
           $contactList[3][0]['sex']='m';
           $contactList[4][0]['big']=45630387;
           $contactList[4][0]['sex']='f';
                                           */
                                           
           $fakeUserList=implode(',',$fakeUser);
           
           $query="SELECT big,sex,name,surname,middle_name ".
                  "FROM members ".
                  "WHERE status<255 AND big IN ($fakeUserList)";  
           
           try {
                $fakeUserSex=$db->fetchAll($query);
        }
        catch (Exception $e)
        {
            debug($e);
            return false;
        }
           
                      
           foreach($fakeUserSex as $key=>$val){
           //separa fake maschi da fake femmine
               
               if ($val[0]['sex']=='m'){
                   
                   $maleFakeUser[]=array('big'=>$val[0]['big'],'name'=>$val[0]['name'],'surname'=>$val[0]['surname'],'middle'=>$val[0]['middle_name']);
                   
               } else {
                   
                   $femaleFakeUser[]=array('big'=>$val[0]['big'],'name'=>$val[0]['name'],'surname'=>$val[0]['surname'],'middle'=>$val[0]['middle_name']);
                                     
               }           
               
           }
                      
           
           $indexMale=count($maleFakeUser)-1;
           $indexFemale=count($femaleFakeUser)-1;
           
           
           $query_filtrata="SELECT m.big,m.sex ".
                  "FROM members m ".
                  "LEFT JOIN profile_visits pv ON m.big=pv.visitor_big ".
                  "WHERE (((pv.created <= NOW() - interval '24 hours') AND (pv.created > NOW() - interval '72 hours')) OR pv.created IS NULL) ".
                  "AND m.big NOT IN (" . $fakeUserList. ") AND m.status<255 ".
                  "ORDER BY pv.created DESC ".
                  "LIMIT 30";
           
           
           $query="SELECT m.big,m.sex ".
                  "FROM members m ".
                  "WHERE m.status<255 AND m.big NOT IN ($fakeUserList)";      
                             
              try {
                    $contactList=$db->fetchAll($query);
                    }
              catch (Exception $e)
              {
                debug($e);
                return false;
                }        
          
           $resultMale=0;
           $resultFemale=0;
            
           foreach($contactList as $key=>$val){
               
               
               if ($val[0]['sex']=='m'){
                   
                    //fake femmina random
                    $index=mt_rand(0,$indexFemale);
                    $fakeVisitor=$femaleFakeUser[$index];
                    $fakeMsg=$fakeMessageFemale[mt_rand(0,count($fakeMessageFemale)-1)];
                    $this->chatMsgSend($fakeVisitor,$val[0]['big'],$fakeMsg); 
                    $resultFemale+=1;          
                   
               } else {
                   
                    //fake maschio random
                    $index=mt_rand(0,$indexMale);
                    $fakeVisitor=$maleFakeUser[$index];
                    $fakeMsg=$fakeMessageMale[mt_rand(0,count($fakeMessageMale)-1)];
                    $this->chatMsgSend($fakeVisitor,$val[0]['big'],$fakeMsg); 
                    $resultMale+=1;        
               }
              
           }
           
            $res['VisitorMale']=$resultMale;
            $res['VisitorFemale']=$resultFemale;
           //$result=$this->ProfileVisit->saveVisit(45710937,45517058);
           
           $this->_apiOK($res); 
       
   }
   
   
   
      public function chatMsgSend($fakeMember,$partnerMember,$textMsg) {
      /*
      * $fakeMember is array 
      * $fakeMember['big']=memberBig
      * $fakeMember['name']=name
      * $fakeMember['surname']=surname
      * $fakeMember['middle']=middle name
      */  
                   
        $memBig = $fakeMember['big'];
        $fakeName = $fakeMember['name'];
        $fakeSurname = $fakeMember['surname'];
        $fakeMiddle = $fakeMember['middle'];
        $partnerBig = $partnerMember;
        $text = $textMsg;
        $relId = null;
        $checkinBig = null;
        $xfoto = null;
        //$pollo=$this->api['photo']; 
        $newerThan = null;
        
        /*
         * Check if user is not in partners ignore list Find checkins -> because of status and checkin big Find ,potentially create member_rel If users are not checked in at the same place, they have to have a memberRel record (chat started based on previous conversation) Save to DB
         */
        
        // Check if user is not on partners ignore list
        $isIgnored = $this->ChatMessage->Sender->MemberSetting->isOnIgnoreListDual ( $partnerBig, $memBig );
        if ($isIgnored) {
            $this->_apiEr ( __('Cannot send chat message. User is blocked by the second party.'), false, false, array (
                    'error_code' => '510' 
            ) );
        }
        
        // Find valid checkin for member and partner
        //$memCheckin = $this->ChatMessage->Checkin->getCheckedinEventFor ( $memBig, TRUE );
        
        //$partnerCheckin = $this->ChatMessage->Checkin->getCheckedinEventFor ( $partnerBig, TRUE );
                     
        // Find relationship in member_rels table
        $memRel = $this->ChatMessage->MemberRel->findRelationships ( $memBig, $partnerBig );
        
        //$frieRel = $this->Friend->FriendsRelationship ( $memBig, $partnerBig, 'A' );
        
        if (empty ( $memRel )) {
            // Create a new one
            $relationship = array (
                    'member1_big' => $memBig,
                    'member2_big' => $partnerBig 
            );
            $this->ChatMessage->MemberRel->create();
            $this->ChatMessage->MemberRel->set( $relationship );
            try {
                $memRel = $this->ChatMessage->MemberRel->save();
                $relId = $memRel ['MemberRel'] ['id'];
                                              
            } catch ( Exception $e ) {
                $this->_apiEr ( __('Error occured. Relationship not created.') );
            }
        } else {
            $relId = $memRel ['MemberRel'] ['id'];
        }
        
        // Create chat message record
        $message = array (
                'rel_id' => $relId,
                'from_big' => $memBig,
                'to_big' => $partnerBig,
                'checkin_big' => $checkinBig,
                'text' => $text,
                'from_status' => 1, // from status = 1 (not deleted)
                'to_status' => 1, // tp status = 1 (not deleted)
                'created' => 'NOW()',
                'status' => 1 
        // 'photo' => $hasphoto,
                );
        
        // $this->Model->getLastInsertId();
        $this->ChatMessage->create();
        $this->ChatMessage->set( $message );
        $msgId = null;
        $chatMsg = null;
        try {
            $res = $this->ChatMessage->save ();
            $result = ($res) ? true : false;
       /*    $this->log("-------ChatMessages CONTROLLER-api_receive-----");
             $this->log("id messaggio inserito = ".serialize($res[ChatMessage][id]));
             $this->log("--------------close api_receive----------------");
       */      
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
            
            
            $chatMsg = $this->ChatMessage->find ( 'first', $pars );
                    
        
        } catch ( Exception $e ) {
            $this->_apiEr ( __('Error occured. Message not created.') );
        }
        //$this->log("link photo = $photolink");
        $this->ChatCache->write ( $partnerBig . '_last_msg', strtotime ( $chatMsg ['ChatMessage'] ['created'] ) );
        
        // Determine number of unread messages
        $unreadCount = $this->ChatMessage->getUnreadMsgCount ( $partnerBig );
        // debug($unreadCount);
        
        // Send push notifications
        $privacySettings=$this->PrivacySetting->getPrivacySettings($partnerBig);
        $privacySettings=$privacySettings[0]['PrivacySetting'];
        $notifyChatMessages=$privacySettings['notifychatmessages'];
        
        $goonPrivacy=true;
        $this->log("-------chatmessages----------");
        $this->log("Settings ".serialize($privacySettings));
        $this->log("notifychatmessages ".intval($notifyChatMessages));
        if (count($privacySettings)>0)
        {
            if ($notifyChatMessages == 0)
            {
                $goonPrivacy=false;
            }
        }
         $this->log("goonPrivacy ".intval($goonPrivacy));
        if ($goonPrivacy)
        {
        $strLen = 50;
        
        $friendsRel=$this->Friend->FriendsRelationship($memBig, $partnerBig, 'A');
        if (count($friendsRel)>0){
        $name = $fakeName . (! empty ( $fakeMiddle ) ? ' ' . $fakeMiddle . ' ' : ' ') . $fakeSurname;
        } else {
            
          $name = $fakeName . ' '. strtoupper(substr( $fakeSurname, 0, 1 )) . '.';  
            
        }
        
        $msg = (strlen ( $text ) > $strLen + 4) ? substr ( $text, 0, $strLen ) . ($text [$strLen + 1] == ' ' ? ' ...' : '...') : $text;
        $this->PushToken->sendNotification ( $name, $msg, array (
                'partner_big' => $memBig,
                'created' => $chatMsg ['ChatMessage'] ['created'],
                'rel_id' => $chatMsg ['ChatMessage'] ['rel_id'],
                'msg_id' => $chatMsg ['ChatMessage'] ['id'],
                // 'timestamp' => time(),
                'unread' => $unreadCount 
        ), array (
                $partnerBig 
        ), 'chat', 'new' );
        
        }
        // return chat messages like in the receive call with refresh enabled
        $newMsgs = $this->ChatMessage->findConversations ( $memBig, $partnerBig, null, $newerThan, 0, true );
        
        // Mark mesaages as read
        if (! empty ( $newMsgs ['chat_messages'] )) {
            $updated = $this->ChatMessage->markAsRead ( $memBig, $partnerBig );
            if (! $updated)
                CakeLog::warning ( 'Messages not marked as read. Membig ' . $memBig . ' Partner big ' . $partnerBig );
                /* $this->log("-------ChatMessages CONTROLLER-api_send-----");
                 $this->log("updated = $updated ");
                 $this->log("WWWROOT =".WWW_ROOT);
                 $this->log("--------------close api_send----------------");*/
        }
        
        $newMsgs['chat_messages'][count($newMsgs['chat_messages'])-1]['photo']=$this->FileUrl->chatmsg_picture($msgId);
        //print_r($newMsgs);
        /*if ($result !== false) {
            $this->Util->transform_name ( $chatMsg );
            $this->Util->transform_name ( $newMsgs );
            $this->_apiOk ( $chatMsg );
            $this->_apiOk ( $newMsgs );
            
            
        } else {
            $this->_apiEr ( __('Error occured. Message not sent.') );
        }
        */
    }
   
   
   
   public function chatMsgBonus($member,$partnerMember,$textMsg) {
      /*
      * $fakeMember is array 
      * $fakeMember['big']=memberBig
      * $fakeMember['name']=name
      * $fakeMember['surname']=surname
      * $fakeMember['middle']=middle name
      */  
                   
        $memBig = $member;
        $partnerBig = $partnerMember;
        $text = $textMsg;
        $relId = null;
        $checkinBig = null;
        $xfoto = null;
        //$pollo=$this->api['photo']; 
        $newerThan = null;
        
        /*
         * Check if user is not in partners ignore list Find checkins -> because of status and checkin big Find ,potentially create member_rel If users are not checked in at the same place, they have to have a memberRel record (chat started based on previous conversation) Save to DB
         */
        
        // Check if user is not on partners ignore list
        $isIgnored = $this->ChatMessage->Sender->MemberSetting->isOnIgnoreListDual ( $partnerBig, $memBig );
        if ($isIgnored) {
            $this->_apiEr ( __('Cannot send chat message. User is blocked by the second party.'), false, false, array (
                    'error_code' => '510' 
            ) );
        }
        
        // Find valid checkin for member and partner
        //$memCheckin = $this->ChatMessage->Checkin->getCheckedinEventFor ( $memBig, TRUE );
        
        //$partnerCheckin = $this->ChatMessage->Checkin->getCheckedinEventFor ( $partnerBig, TRUE );
                     
        // Find relationship in member_rels table
        $memRel = $this->ChatMessage->MemberRel->findRelationships ( $memBig, $partnerBig );
        
        //$frieRel = $this->Friend->FriendsRelationship ( $memBig, $partnerBig, 'A' );
        
        if (empty ( $memRel )) {
            // Create a new one
            $relationship = array (
                    'member1_big' => $memBig,
                    'member2_big' => $partnerBig 
            );
            $this->ChatMessage->MemberRel->create();
            $this->ChatMessage->MemberRel->set( $relationship );
            try {
                $memRel = $this->ChatMessage->MemberRel->save();
                $relId = $memRel ['MemberRel'] ['id'];
                                              
            } catch ( Exception $e ) {
                $this->_apiEr ( __('Error occured. Relationship not created.') );
            }
        } else {
            $relId = $memRel ['MemberRel'] ['id'];
        }
        
        // Create chat message record
        $message = array (
                'rel_id' => $relId,
                'from_big' => $memBig,
                'to_big' => $partnerBig,
                'checkin_big' => $checkinBig,
                'text' => $text,
                'from_status' => 1, // from status = 1 (not deleted)
                'to_status' => 1, // tp status = 1 (not deleted)
                'created' => 'NOW()',
                'status' => 1 
        // 'photo' => $hasphoto,
                );
        
        // $this->Model->getLastInsertId();
        $this->ChatMessage->create();
        $this->ChatMessage->set( $message );
        $msgId = null;
        $chatMsg = null;
        try {
            $res = $this->ChatMessage->save ();
            $result = ($res) ? true : false;
       /*    $this->log("-------ChatMessages CONTROLLER-api_receive-----");
             $this->log("id messaggio inserito = ".serialize($res[ChatMessage][id]));
             $this->log("--------------close api_receive----------------");
       */      
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
            
            
            $chatMsg = $this->ChatMessage->find ( 'first', $pars );
                    
        
        } catch ( Exception $e ) {
            $this->_apiEr ( __('Error occured. Message not created.') );
        }
        //$this->log("link photo = $photolink");
        $this->ChatCache->write ( $partnerBig . '_last_msg', strtotime ( $chatMsg ['ChatMessage'] ['created'] ) );
        
        // Determine number of unread messages
        $unreadCount = $this->ChatMessage->getUnreadMsgCount ( $partnerBig );
        // debug($unreadCount);
        
        // Send push notifications
        $privacySettings=$this->PrivacySetting->getPrivacySettings($partnerBig);
        $privacySettings=$privacySettings[0]['PrivacySetting'];
        $notifyChatMessages=$privacySettings['notifychatmessages'];
        
        $goonPrivacy=true;
        $this->log("-------chatmessages----------");
        $this->log("Settings ".serialize($privacySettings));
        $this->log("notifychatmessages ".intval($notifyChatMessages));
        if (count($privacySettings)>0)
        {
            if ($notifyChatMessages == 0)
            {
                $goonPrivacy=false;
            }
        }
         $this->log("goonPrivacy ".intval($goonPrivacy));
        if ($goonPrivacy)
        {
        $strLen = 50;
        
        $friendsRel=$this->Friend->FriendsRelationship($memBig, $partnerBig, 'A');
        if (count($friendsRel)>0){
        $name = $fakeName . (! empty ( $fakeMiddle ) ? ' ' . $fakeMiddle . ' ' : ' ') . $fakeSurname;
        } else {
            
          $name = $fakeName . ' '. strtoupper(substr( $fakeSurname, 0, 1 )) . '.';  
            
        }
        
        $msg = (strlen ( $text ) > $strLen + 4) ? substr ( $text, 0, $strLen ) . ($text [$strLen + 1] == ' ' ? ' ...' : '...') : $text;
        $this->PushToken->sendNotification ( $name, $msg, array (
                'partner_big' => $memBig,
                'created' => $chatMsg ['ChatMessage'] ['created'],
                'rel_id' => $chatMsg ['ChatMessage'] ['rel_id'],
                'msg_id' => $chatMsg ['ChatMessage'] ['id'],
                // 'timestamp' => time(),
                'unread' => $unreadCount 
        ), array (
                $partnerBig 
        ), 'chat', 'new' );
        
        }
        // return chat messages like in the receive call with refresh enabled
        $newMsgs = $this->ChatMessage->findConversations ( $memBig, $partnerBig, null, $newerThan, 0, true );
        
        // Mark mesaages as read
        if (! empty ( $newMsgs ['chat_messages'] )) {
            $updated = $this->ChatMessage->markAsRead ( $memBig, $partnerBig );
            if (! $updated)
                CakeLog::warning ( 'Messages not marked as read. Membig ' . $memBig . ' Partner big ' . $partnerBig );
                /* $this->log("-------ChatMessages CONTROLLER-api_send-----");
                 $this->log("updated = $updated ");
                 $this->log("WWWROOT =".WWW_ROOT);
                 $this->log("--------------close api_send----------------");*/
        }
        
        $newMsgs['chat_messages'][count($newMsgs['chat_messages'])-1]['photo']=$this->FileUrl->chatmsg_picture($msgId);
        //print_r($newMsgs);
        /*if ($result !== false) {
            $this->Util->transform_name ( $chatMsg );
            $this->Util->transform_name ( $newMsgs );
            $this->_apiOk ( $chatMsg );
            $this->_apiOk ( $newMsgs );
            
            
        } else {
            $this->_apiEr ( __('Error occured. Message not sent.') );
        }
        */
    }
   
     public function api_connTest(){
       
      /* $db=$this->Member->getDataSource();
       $this->Member->close($db);
       print_r($db);  */
       $this->_apiOK($db);
       
       
   } 
     
     
      public function api_setBonus(){
       //Imposta un bonus da schedulare e restituisce il numero di operazione 
       $db = $this->Member->getDataSource();
       $this->_checkVars ( array ('reason','amount'), array ());
       
       $reason=$this->api['reason'];
       $amount=$this->api['amount'];
                     
       $query_maxop="SELECT MAX(operation) as max FROM tmp_bonus";
       try {
                $res=$db->fetchAll($query_maxop);
        }
       catch (Exception $e)
        {
            debug($e);
            return false;
        }
       
       $opnum=$res[0][0]['max'] + 1;
                
         
       //Questa query copia gli id membri attivi nella tabella tmp_bonus
       $query_copy="INSERT INTO tmp_bonus(member_id,reason,amount,operation) ".
                   "SELECT big,'$reason','$amount','$opnum' ".
                   "FROM members ".
                   "WHERE status<255 AND big IN (45545831,45517058,45710937) ".
                   "ORDER BY big ASC";
       
       try {
                $db->fetchAll($query_copy);
        }
        catch (Exception $e)
        {
            debug($e);
            return false;
        }
       
        //return($opnum);
        $result['operation_id']=$opnum;   
        $this->_apiOK($result);           
       
   } 
     
      public function api_scheduleBonus(){
       //schedula Bonus
       $sent=0;
       
       $db = $this->Member->getDataSource();
       $this->_checkVars ( array ('op_id','volume','msg'), array ());
       //volume contiene il numero di record da elaborare in ogni esecuzione
              
       $op=$this->api['op_id'];
       $elem=$this->api['volume'];
       $msg=$this->api['msg'];       
       
                        
       //Estrae un certo volume di utenti per l'invio del bonus
       $query="SELECT member_id,reason,amount FROM tmp_bonus ".
              "WHERE operation=$op AND status=0 ".
              "LIMIT $elem";
       
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
                $this->Wallet->addAmount($val[0]['member_id'],$val[0]['amount'],$val[0]['reason']);
                $this->chatMsgBonus(ID_HAAMBLE_USER,$val[0]['member_id'],$msg);           
            
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
     
     
     
     
     
}