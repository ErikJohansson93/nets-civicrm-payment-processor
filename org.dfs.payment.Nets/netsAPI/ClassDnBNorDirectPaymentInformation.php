<?php

class DnBNorDirectPaymentInformation {
 public $BankRef;
 public $FromAccount;
 public $KID;
 public $Message;
 public $ToAccount;
 
 function DnBNorDirectPaymentInformation
   (
        $BankRef,
        $FromAccount,
        $KID,
        $Message,
        $ToAccount,
   )
   {
        $this->BankRef      = $BankRef;
        $this->FromAccount  = $FromAccount;
        $this->KID          = $KID;
        $this->Message      = $Message;
        $this->ToAccount    = $ToAccount;
   }
};

?>
