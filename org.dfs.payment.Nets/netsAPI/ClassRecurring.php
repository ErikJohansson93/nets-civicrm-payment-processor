<?php

class Recurring {
  
 public $ExpiryDate;
 public $Frequency;
 public $Type;
 public $PanHash;
 
 function Recurring
   (
        $ExpiryDate,
        $Frequency,
        $Type,
        $PanHash
   )
   {
        $this->ExpiryDate       = $ExpiryDate;
        $this->Frequency        = $Frequency;
        $this->Type             = $Type;
        $this->PanHash          = $PanHash;
   }
};

?>
