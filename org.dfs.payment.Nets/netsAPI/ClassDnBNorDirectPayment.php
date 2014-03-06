<?php

class DnBNorDirectPayment {
 public $KID;
 public $Message;
 public $ToAccount;
 
 function DnBNorDirectPayment
   (
        $KID,
        $Message,
        $ToAccount
   )
   {
        $this->KID        = $KID;
        $this->Message    = $Message;
        $this->ToAccount  = $ToAccount;
   }
};

?>
