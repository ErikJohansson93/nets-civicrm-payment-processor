<?php
require_once("Parameters.php");
require_once("ClassProcessRequest.php");

$transactionId = "";
if (isset($_GET['transactionId']))
{
  $transactionId = $_GET['transactionId'];
}
if (isset($_POST['transactionId']))
{
  $transactionId = $_POST['transactionId'];
}

$description = "description of SALE operation";
$operation = "SALE";
$transactionAmount = "";
$transactionReconRef = "";

####  PROCESS OBJECT  ####
$ProcessRequest = new ProcessRequest(
  $description,
  $operation,
  $transactionAmount,
  $transactionId,
  $transactionReconRef
);


$InputParametersOfProcess = array
(
  "token"       => $token,
  "merchantId"  => $merchantId,
  "request"     => $ProcessRequest 
);


echo "<h3><font color='gray'>Input parameters:</font></h3>";

echo "merchantId= " . $merchantId . "<br/>";
echo "token= " . $token . "<br/>";

echo "description= " . $description . "<br/>";
echo "operation= " . $operation . "<br/>";
echo "transactionAmount= " . $transactionAmount . "<br/>";
echo "transactionId= " . $transactionId . "<br/>";
echo "transactionReconRef= " . $transactionReconRef . "<br/>";

/*
  // you can also display this way
  echo "<br/>Parameters in Process<br/>"; 
  echo "<pre>"; 
  echo print_r($InputParametersOfProcess); 
  echo "</pre>";
*/

####  START PROCESS CALL  ####
try 
{
  if (strpos($_SERVER["HTTP_HOST"], 'uapp') > 0)
  {
  // Creating new client having proxy
  $client = new SoapClient($wsdl, array('proxy_host' => "isa4", 'proxy_port' => 8080, 'trace' => true,'exceptions' => true));
  }
  else
  {
  // Creating new client without proxy
  $client = new SoapClient($wsdl, array('trace' => true,'exceptions' => true ));
  }
  
  $OutputParametersOfProcess = $client->__call('Process' , array("parameters"=>$InputParametersOfProcess));
  
  $ProcessResult = $OutputParametersOfProcess->ProcessResult; 
  
  echo "<h3><font color='gray'>Output parameters:</font></h3>";
  echo "<pre>"; 
  print_r($ProcessResult);
  echo "</pre>";
  
  echo "<h3><font color='green'>Process call successfully done.</font></h3>";
  
  $process_parameters = "?transactionId=" .  $ProcessResult->TransactionId;  

  if ($ProcessResult->ResponseCode == "OK")
  {
    echo "<br/>ResponseCode is OK, so you can call or Credit:";
    echo "<h3><a href='callingCredit.php$process_parameters'>Calling Credit</a></h3>";
    echo "<h3><a href='callingQuery.php$process_parameters'>Calling Query</a></h3>";
    echo "<h3><a href='index.php'>Test Webshops</a><h3>";
  }
  else
  {
    echo "<h3><a href='index.php'>Test Webshops</a><h3>";
  }
  
} // End try
catch (SoapFault $fault) 
{
  ## Do some error handling in here...
  echo "<h3><a href='index.php'>Test Webshops</a><h3>";

  echo "<br/><font color='red'>EXCEPTION!";   
  echo "<br/><br/><h3><font color='red'>Process call failed</font></h3>";
  echo "<pre>"; 
  print_r($fault);
  echo "</pre>";
} // End catch
####  END   QUERY CALL  ####

?>
