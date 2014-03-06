<?php
$wsdl="https://epayment-test.bbs.no/netaxept.svc?wsdl";
if (isset ($_GET["wsdl"]))
{
  $wsdl = $_GET["wsdl"];
}

$tmp_php_self = $_SERVER["PHP_SELF"];

echo "<html>                                                                             \n";
echo "<head>                                                                             \n";
echo "<title>" . $tmp_php_self . "?wsdl=" . $wsdl . "</title>                            \n";
echo "</head>                                                                            \n";
echo "<body>                                                                             \n";
echo "                                                                                   \n";

echo "wsdl=" . $wsdl . "<br/>\n";
echo "<a href='" . $tmp_php_self . "?wsdl=" . $wsdl  ."'>again...</a>"; 

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
  
  echo "<hr><br/>\n";

  echo "<b>__getTypes()</b>\n";
  echo "              <pre>                                                                 \n";
  print_r(var_dump( $client->__getTypes()));
  echo "              </pre>                                                                \n";

  echo "<hr><br/>\n";

  echo "<b>__getFunctions()</b>\n";
  echo "              <pre>                                                                 \n";
  print_r(var_dump( $client->__getFunctions()));
  echo "              </pre>                                                                \n";
  echo "<hr><br/>\n";
}
catch (SoapFault $fault) 
{
  echo "<hr><br/>\n";
  echo "<b>SOAP Fault exception value-paires</b>\n";
  echo "              <pre>                                                                 \n";
  print_r($fault);
  echo "              </pre>                                                                \n";
  echo "<hr><br/>\n";
  // the following line is only visible on the command line
  trigger_error("SOAP Fault: (faultcode: {$fault->faultcode}, faultstring: {$fault->faultstring})", E_USER_ERROR);
}

echo "<h3><a href='index.php'>Test Webshops</a><h3>";

echo "</body>                                                                            \n";
echo "</html>                                                                            \n";
?>
