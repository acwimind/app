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
}
?>