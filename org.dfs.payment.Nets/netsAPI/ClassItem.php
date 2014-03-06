<?php
/*
  string(175) "struct Item {
 string Amount;
 string ArticleNumber;
 float Discount;
 boolean Handling;
 boolean IsVatIncluded;
 int Quantity;
 boolean Shipping;
 string Title;
 float VAT;
}"
*/

class Item {
  
 public $Amount;
 public $ArticleNumber;
 public $Discount;
 public $Handling;
 public $IsVatIncluded;
 public $Quantity;
 public $Shipping;
 public $Title;
 public $VAT;
 
 function Item
   (
        $Amount,
        $ArticleNumber,
        $Discount,
        $Handling,
        $IsVatIncluded,
        $Quantity,
        $Shipping,
        $Title,
        $VAT
   )
   {
        $this->Amount       = $Amount;
        $this->ArticleNumber        = $ArticleNumber;
        $this->Discount             = $Discount;
        $this->Handling             = $Handling;
        $this->IsVatIncluded             = $IsVatIncluded;
        $this->Quantity             = $Quantity;
        $this->Shipping             = $Shipping;
        $this->Title             = $Title;
        $this->VAT          = $VAT;
   }
};

?>
