<?php

class AvtaleGiro {
  
 public $AccountNumber;
 public $AmountLimit;
 public $CompanyName;
 public $Kid;
 
 function AvtaleGiro
   (
        $AccountNumber,
        $AmountLimit,
        $CompanyName,
        $Kid
   )
   {
        $this->AccountNumber   = $AccountNumber;
        $this->AmountLimit     = $AmountLimit;
        $this->CompanyName     = $CompanyName;
        $this->Kid             = $Kid;
   }
};

?>
