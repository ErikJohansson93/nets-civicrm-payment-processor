<?php

class CardInfo {
  
 public $ExpiryDate;
 public $Issuer;
 public $Pan;
 public $SecurityCode;
 
 function CardInfo
   (
        $ExpiryDate,
        $Issuer,
        $Pan,
        $SecurityCode
   )
   {
        $this->ExpiryDate   = $ExpiryDate;
        $this->Issuer       = $Issuer;
        $this->Pan          = $Pan;
        $this->SecurityCode = $SecurityCode;
   }
};

?>
