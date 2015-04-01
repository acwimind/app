<?php

class ProductsController extends AppController {
	
public $uses = array('Wallet','Product'); 
  
        public function api_productsList() {
          //return a list of products (id,description,price)
                   
                 
          $products=$this->Product->find('all',array('fields'=>array('id','description','price')));
                       
          
          //print_r($products);
          
          $this->_apiOk($products);
            
           
        }
      
    
        public function api_buyProduct(){
           //buy product
                
            $this->_checkVars (array(
                                     'member_big',
                                     'id_product' 
                                     ));           
            
            $memBig=$this->api['member_big'];
            $idProduct=$this->api['id_product'];
            
                       
           //query
            $params = array(
            'conditions' => array(
                'id' => $idProduct
                             ),
            'fields' => array(
                 'id',
                 'price',
                 'duration',
                 'description'
             ));
          
           $productProperties=$this->Product->find('first', $params);
                               
           //find member credit
           
           $getMemberCredit=$this->Wallet->getCredit($memBig);
           
           $getMemberCredit=$getMemberCredit[0][0];
           
           //$getMemberCredit=$this->Wallet->getCredit2($memBig);
                    
           if ($getMemberCredit['credit']>=$productProperties['Product']['price']){
                           
              $result=$this->Product->addPurchase($memBig,$productProperties);
                            
              $this->_apiOk($result);       
              }
              else {
                 $this->_apiError(__('Non hai credito sufficiente'));       
          }  
                  
        }
        
        
}