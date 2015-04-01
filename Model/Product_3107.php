<?php
class Product extends AppModel{
    

    
        
    public function getCredit($memberBig)
    {
         
        $credit=$this->find('all',
                                    array('fields'=>array(
                                    'Product.id',
                                    'Product.description',
                                    'Product.price')));
                       
            $this->_apiOk($productList);    
        
        
    }
    
    
}
?>
