<?php
/*
  string(287) "struct Customer {
 string Address1;
 string Address2;
 string CompanyName;
 string CompanyRegistrationNumber;
 string Country;
 string CustomerNumber;
 string Email;
 string FirstName;
 string LastName;
 string PhoneNumber;
 string Postcode;
 string SocialSecurityNumber;
 string Town;
}"
*/
class Customer {
  
 public $Address1;
 public $Address2;
 public $CompanyName;
 public $CompanyRegistrationNumber;
 public $Country;
 public $CustomerNumber;
 public $Email;
 public $FirstName;
 public $LastName;
 public $PhoneNumber;
 public $Postcode;
 public $SocialSecurityNumber;
 public $Town;
 
 function Customer
   (
        $Address1,
        $Address2,
        $CompanyName,
        $CompanyRegistrationNumber,
        $Country,
        $CustomerNumber,
        $Email,
        $FirstName,
        $LastName,
        $PhoneNumber,
        $Postcode,
        $SocialSecurityNumber,
        $Town
   )
   {
        $this->Address1         = $Address1;
        $this->Address2         = $Address2;
        $this->CompanyName      = $CompanyName;
        $this->CompanyRegistrationNumber = $CompanyRegistrationNumber;
        $this->Country          = $Country;
        $this->CustomerNumber   = $CustomerNumber;
        $this->Email            = $Email;
        $this->FirstName        = $FirstName;
        $this->LastName         = $LastName;
        $this->PhoneNumber      = $PhoneNumber;
        $this->Postcode         = $Postcode;
        $this->SocialSecurityNumber = $SocialSecurityNumber;
        $this->Town             = $Town;
   }
};

?>

