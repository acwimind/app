<?php
App::uses('Wallet', 'Model');

class Product extends AppModel{
    
       public function addPurchase($memBig,$productProperties){
               
        $reason="Acquistato ".$productProperties['Product']['description'];
        $duration="'".$productProperties['Product']['duration']." second '";
                
        $data=array(
                'member1_big' => $memBig,
                'amount' => $productProperties['Product']['price']*(-1),
                'reason' => $reason,
                'product_id' => $productProperties['Product']['id'],
                'expirationdate'=> DboSource::expression('NOW()+ interval '.$duration), 
        );
         //mi permette di usare il metodo save del model Wallet 
         $modelWallet = ClassRegistry::init ( 'Wallet' );  
         
         $result=$modelWallet->save($data);
             
         return $result;
             
         }           
    }

?>