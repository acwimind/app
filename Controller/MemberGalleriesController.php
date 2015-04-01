<?php
  
  class MemberGalleriesController extends AppController{
    //var $uses = array('', '');
    //public $helpers = array('Session', 'Html', 'Form');


    
    public function api_removePhotoGallery(){
        
        $this->_checkVars ( array ('photo_big') );
        
                
        $photo_big=$this->api['photo_big'];
        $MemBig=$this->logged ['Member'] ['big'];
               
       
       try {//Verifica che la foto da cancellare non sia già cancellata status=255 o inesistente
              $res=$this->MemberGallery->find('first',array(
                               'conditions' => array('big' => $photo_big,'member_big' => $MemBig,'status <' => 255),
                               'fields' => array('isdefault','name','original_ext'))
                                            );
        
            } catch ( Exception $e ) {
         
                 $this->_apiEr ( __ ( 'Errore : foto inesistente '), __ ( 'Errore : foto inesistente' ), true, null, '989' );
        }
        
         
        if (count($res['MemberGallery'])>0){
        
             try {//Cancella la foto
              
              $this->MemberGallery->updateAll(
                                            array('status' => 255),
                                            array('big' => $photo_big)
                                            );
        
            } catch ( Exception $e ) {
         
               $this->_apiEr ( __ ( 'Errore cancellazione foto' ), __ ( 'Errore cancellazione foto' ), true, null, '989' );
        }
            
            $this->_apiOk ( array (
                'photo_big' => $photo_big, 
                'status'=> 'deleted' ) );
        }  else {
            
            
             $this->_apiEr ( __ ( 'Errore : Foto inesistente' ), __ ( 'Errore : Foto inesistente' ), true, null, '989' );
            
            
        }             
        
            
    }
    
    public function api_setDefaultPhotoGallery(){
       /* Imposta una foto come immagine di default
        * 
        *  
        * params :
        *       photo_big
        *  
        * 
        */
        
        
        $this->_checkVars ( array ('photo_big') );
        
        $photo_big=$this->api['photo_big'];
        $MemBig=$this->logged ['Member'] ['big'];
               
       
       try {//Verifica che la foto da impostare non sia status=255
              $res=$this->MemberGallery->find('first',array(
                               'conditions' => array('big' => $photo_big,'status <' => 255),
                               'fields' => array('isdefault','name','original_ext'))
                                            );
        
            } catch ( Exception $e ) {
         
                 $this->_apiEr ( __ ( $e), __ ( $e ), true, null, '989' );
        }
        
        
        if (count($res['MemberGallery'])>0){
        
          
        try {//Imposta a default=true la foto
              
              $this->MemberGallery->updateAll(
                                            array('isdefault' => 1),
                                            array('big' => $photo_big)
                                            );
        
            } catch ( Exception $e ) {
         
               $this->_apiEr ( __ ( 'set Photo default failed' ), __ ( 'set Photo default failed' ), true, null, '989' );
        }
        
        
        try {//Tutte le altre foto del member diventano default=false
              $this->MemberGallery->updateAll(
                                            array('isdefault' => 0),
                                            array('AND' =>array(
                                                         'member_big' => $MemBig,
                                                         'big !=' => $photo_big))
                                            );
        
            } catch ( Exception $e ) {
         
                 $this->_apiEr ( __ ( $e), __ ( $e ), true, null, '989' );
        }
        
         try {//Restituisce la modifica
              $result=$this->MemberGallery->find('first',array(
                               'conditions' => array('big' => $photo_big),
                               'fields' => array('isdefault','name','original_ext'))
                                            );
        
            } catch ( Exception $e ) {
         
                 $this->_apiEr ( __ ( $e), __ ( $e ), true, null, '989' );
        }
        
         //$prefixLink='https:'.DS.DS.$_SERVER['HTTP_HOST'].DS.'api'.DS.'files'.DS.'members'.DS.$MemBig.DS.$MemBig.DS;
        $prefixLink='http:'.DS.DS.$_SERVER['HTTP_HOST'].DS.'api'.DS.'files'.DS.'members'.DS.$MemBig.DS.$MemBig.DS;
         $photoUrl = $prefixLink.$result['MemberGallery']['name'].DS.$result['MemberGallery']['original_ext'];        
                 
        $this->_apiOk ( array (
                'photo_big' => $photo_big, 
                'default'=> $result['MemberGallery']['isdefault'],
                'photo_url'=> $photoUrl
        ) );   
        
        }  else {  
              $this->_apiEr ( __ ('Errore : Si sta impostando una Foto inesistente'), __ ( 'Errore : Si sta impostando una Foto inesistente' ), true, null, '989' );
        }
    }
    
    public function api_addPhotoGallery() {
        /*Aggiunge una foto alla galleria
        * 
        * params 
        *   
        *   *photo : nome del file 
        *   *default : 1=imposta la foto per default; 0=foto non di default
        *   
        *   *=required
        * 
        * output
        *   true : foto caricata
        *   false : errore caricamento
        * 
        *   
        */ 
        
        $this->_checkVars ( array ('photo') );
     
         
         $MemBig=$this->logged ['Member'] ['big'];
         $photo=$this->api['photo'];
         $default=0;
         //$default=$this->api['default'];
//         $prefixLink='https:'.DS.DS.$_SERVER['HTTP_HOST'].DS.'api'.DS.'files'.DS.'members'.DS.$MemBig.DS.$MemBig.DS;
         $prefixLink='http:'.DS.DS.$_SERVER['HTTP_HOST'].DS.'api'.DS.'files'.DS.'members'.DS.$MemBig.DS.$MemBig.DS;
         
         //print_r($_FILES);
          
          try {//Verifica che la foto da impostare non sia status=255
              $res=$this->MemberGallery->find('count',array(
                               'conditions' => array('member_big' => $MemBig,'status <' => 255),
                                        )
                               );
        
            } catch ( Exception $e ) {
         
                 $this->_apiEr ( __ ( $e), __ ( $e ), true, null, '989' );
        }
        
        
        if ($res==0){ //è la prima foto quindi imposta default=1
        
            $default=1;
        }
            
            
            
        try {
            
            if (! isset ( $photo ) || ! isset ( $_FILES [$photo] )) {
                $this->_apiEr ( __ ( 'Per favore fai l\'upload del file' ), true );
            }
                       
            $filename = pathinfo ($_FILES[$photo]['name']);
            $extension=$filename['extension'];
            
            //print_r($filename);
            $newFileName=$MemBig.time(); //idmembro_timestamp  per evitare doppioni 
                        
            $save=$this->MemberGallery->save ( array (
                    'member_big' => $MemBig,
                    'original_ext' => $extension,
                    'status' => ACTIVE,
                    'created' => DboSource::expression ( 'NOW()' ),
                    'isdefault' => $default,
                    'name' =>  $newFileName
            ) );
            
            //print("Salvataggio DB ".$save);
            
            $photoGallery_path = MEMBERS_UPLOAD_PATH . DS . $MemBig . DS . $MemBig . DS;
            
            if (! is_dir ( $photoGallery_path )) {
                mkdir ( $photoGallery_path, 0777, true );
            }
        } catch ( Exception $e ) {
            
            $this->log ( serialize ( $e ) );
        }
        
        try {
                
                $uploaded = $this->Upload->directUpload ( $_FILES[$photo],$photoGallery_path . $newFileName . '.' . $extension );   
  
               
        } catch ( Exception $e ) {
         
            $this->_apiEr ( __ ( serialize ( $e ) ), __ ( serialize ( $e )), true, null, '989' );
        }
        if (! $uploaded) { 
            $this->MemberGallery->delete($this->MemberGallery->id);
            
            $this->_apiEr ( __ ( 'Photo upload failed' ), __ ( 'Photo upload failed' ), true, null, '989' );
           
        }
                
        $photoUrl = $prefixLink.$newFileName.DS.$extension;
        
        //$this->log("photoUrl ".$photoUrl);
        $this->_apiOk ( array (
                'photo_big' => (float)$this->MemberGallery->id, 
                'photo_url'=> $photoUrl
        ) );
        
        
        
    }          
}

?>
