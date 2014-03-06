<?php
require_once("Parameters.php");
require_once("ClassQueryRequest.php");
require_once("ClassProcessRequest.php");

$transactionId = "";
if (isset($_GET['transactionId']))
{
  $transactionId = $_GET['transactionId'];
}

$responseCode = "";
if (isset($_GET['responseCode']))
{
  $responseCode = $_GET['responseCode'];
}

// this is an example is showing how to add one (or several additional parameters on the terminal)
$webshopParameter = "";
if (isset($_GET['webshopParameter']))
{
  $webshopParameter = $_GET['webshopParameter'];
}


####  Display all parameters  ####
echo "<h3><font color='gray'>Terminal returned parameters to the webshop:</font></h3>";

echo "transactionId= " . $transactionId . "<br/>";
echo "responseCode= " . $responseCode . "<br/>";

echo "<h3><font color='gray'>Additional parameter defined by the webshop:</font></h3>";

echo "webshopParameter= " . $webshopParameter . "<br/>";

$process_parameters = "?transactionId=" .  $transactionId;  

if ($responseCode == "OK")
{
  echo "<br/>ResponseCode is OK, so you can call manually following action:";
  echo "<h3><a href='callingAuth.php$process_parameters'>Calling Auth</a></h3>";
  echo "<h3><a href='callingSale.php$process_parameters'>Calling Sale</a></h3>";
  echo "<h3><a href='callingQuery.php$process_parameters'>Calling Query</a></h3>";
  // in PRODUCTION environment: you will run a query at this stage
  // and read the AuthenticationInformation in order to decide if you will offer your client to call Auth or Sale
  // e.g. $AuthenticationInformation = $OutputParametersOfQuery->QueryResult->AuthenticationInformation; 
  //      $AuthenticationInformation->AuthenticatedStatus 
  //      $AuthenticationInformation->AuthenticatedWith 
  //      $AuthenticationInformation->ECI 
}
else
{
  echo "<br/>ResponseCode is not equal OK, call Query to get more information";
  echo "<h3><a href='callingQuery.php$process_parameters'>Calling Query</a></h3>";
  echo "<h3><a href='index.php'>Test Webshops</a><h3>";
}

?>
