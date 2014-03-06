<?php

class MicroPayment {
  
 public $Pan;
 public $ShowTransactionHistory;
 
 function MicroPayment
   (
        $Pan,
        $ShowTransactionHistory
   )
   {
        $this->Pan                      = $Pan;
        $this->ShowTransactionHistory   = $ShowTransactionHistory;
   }
};

?>
