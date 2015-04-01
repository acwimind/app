<?php

class Services13Shell extends AppShell {
	 public $uses = array (
           
            'Member',
            'ProfileVisit',
            'ChatMessage',
            'PushToken',
            'Friend',
            'PrivacySetting',
            'MemberSetting',
            'Wallet',
            'Marketing'
    ); // load these models
    
    public function send()
    {
    
       $str="Buon Natale da Haamble! Non siamo venuti a mani vuote...ecco un bonus speciale di 100 crediti che puoi usare subito ".
             "per aumentare la tua visibilità sul radar Haamble e conoscere persone nuove nella tua città. ".
             "Fai Join nei luoghi che ami ed entra in chat per augurare a tutti buone feste :)";

	$msg = utf8_encode($str);//Fondamentale altrimenti non invia i caratteri accentati.

	$idBonus=3;
	$invii_x_ciclo=1;

        
        $this->Marketing->scheduleBonus($idBonus,$invii_x_ciclo,13,$msg);
	
    
    } 
    
}