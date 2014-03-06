<?php
/**
 * @file
 * Copyright (C) 2007
 * Licensed to CiviCRM under the Academic Free License version 3.0.
 *
 * Written and contributed by Ideal Solution, LLC (http://www.idealso.com)
 */

/**
 *
 * @package CRM
 * @author Marshal Newrock <marshal@idealso.com>
 * $Id: Dummy.php 40328 2012-05-11 23:06:13Z allen $
 */

/* NOTE:
 * When looking up response codes in the Authorize.Net API, they
 * begin at one, so always delete one from the "Position in Response"
 */
class org_dfs_payment_Nets extends CRM_Core_Payment {
  CONST CHARSET = 'iso-8859-1';

  static protected $_mode = NULL;

  static protected $_params = array();

  /**
   * We only need one instance of this object. So we use the singleton
   * pattern and cache the instance in this variable
   *
   * @var object
   * @static
   */
  static protected $_singleton = NULL;

  /**
   * Constructor.
   *
   * @param string $mode
   *  The mode of operation: live or test.
   *
   * @return void
   */
  function __construct($mode, &$paymentProcessor) {
    $this->_mode = $mode;
    $this->_paymentProcessor = $paymentProcessor;
    $this->_processorName = ts('Nets');
  }

  /**
   * Singleton function used to manage this object.
   *
   * @param string $mode the mode of operation: live or test
   *
   * @return object
   * @static
   */
  static
  function &singleton($mode, &$paymentProcessor) {
    $processorName = $paymentProcessor['name'];
    if (CRM_Utils_Array::value($processorName, self::$_singleton) === NULL) {
      self::$_singleton[$processorName] = new org_dfs_payment_Nets($mode, $paymentProcessor);
    }
    return self::$_singleton[$processorName];
  }

  /**
   * Function not implemented.
   */
  function doDirectPayment(&$params) {
    CRM_Core_Error::fatal(ts('This function is not implemented'));
  }


  /**
   * Submit a payment using notify Method.
   *
   * @param array $params assoc array of input parameters for this transaction
   *
   * @return array the result in a nice formatted array (or an error object)
   * @public
   */
  function doTransferCheckout($params, $component) {
    // Include required class files for Nets API.
    require_once "netsAPI/ClassRegisterRequest.php";
    require_once "netsAPI/ClassCustomer.php";
    require_once "netsAPI/ClassTerminal.php";
    require_once "netsAPI/ClassItem.php";
    require_once "netsAPI/ClassArrayOfItem.php";
    require_once "netsAPI/ClassOrder.php";
    require_once "netsAPI/ClassEnvironment.php";

    // Get whatever we are on a HTTPS or HTTP protocol.
    if (isset($_SERVER['HTTPS'])) {
      if ($_SERVER["HTTPS"] == "on") {
        $protocol = 'https://';
      }
      else {
        $protocol = 'http://';
      }
    }
    else {
      $protocol = 'http://';
    }

    // Get the current payment processor.
    $paymentProcessor = $this->_paymentProcessor;

    // Store some private data that we need when the payment has been made.
    $qfkey = $params['qfKey'];
    $private_data = "&contactID={$params['contactID']}&contributionID={$params['contributionID']}&invoiceID={$params['invoiceID']}&membershipID={$params['membershipID']}";

    $values = dfs_civicrm_memberfee_get_membership($params['contactID']);

    // Get membershiptype fees.
    $membership_fee = dfs_civicrm_memberfee_get_fee($values['membership_type_id']);

    // Set the acceptUrl.
    if ($component == "contribute") {
      $returnURL = $protocol . $_SERVER['HTTP_HOST'] . '/civicrm/payment/ipn?processor_name=Nets&module=contribute&qfkey=' . $qfkey . $private_data;
      if ($this->_mode == 'test') {
        $returnURL = $returnURL . '&action=preview';
      }
    }

    // Get the total amount for the membership. Incl membership fee.
    $total_amount = $params['amount'] + $membership_fee['remaining_member_fee'];

    $nets_params = array(
      'amount' => number_format($total_amount, 2, '', ''),
      'currencyCode' => 'SEK',
      'merchant' => $paymentProcessor['user_name'],
      'test' => 1,
      'language' => 'sv_SE',
      'orderNumber' => md5(uniqid($params['contributionID'], TRUE)),
      'os' => NULL,
      'WebServicePlatform' => 'PHP5',
      'autoAuth' => NULL,
      'paymentMethodList' => NULL,
      'orderDescription' => $params['description'],
      'redirectOnError' => NULL,
      'returnURL' => $returnURL,
      'UpdateStoredPaymentInfo' => NULL,
    );

    $Environment = new Environment(
      $nets_params['language'],
      $nets_params['os'],
      $nets_params['WebServicePlatform']
    );

    $Terminal = new Terminal(
      $nets_params['autoAuth'],
      $nets_params['paymentMethodList'],
      $nets_params['language'],
      $nets_params['orderDescription'],
      $nets_params['redirectOnError'],
      $nets_params['returnURL']
    );

    $Order = new Order(
      $nets_params['amount'],
      $nets_params['currencyCode'],
      $nets_params['force3DSecure'],
      $nets_params['redirectOnError'],
      $nets_params['orderNumber'],
      $nets_params['UpdateStoredPaymentInfo']
    );

    $Customer = new Customer(
      $params['street_address-1'],
      $params['supplemental_address_1-1'],
      $customerCompanyName,
      $customerCompanyRegistrationNumber,
      $customerCountry,
      $customerNumber,
      $params['email-5'],
      $params['first_name'],
      $params['last_name'],
      $params['phone-Primary-1'],
      $params['postal_code-1'],
      $params['birth_date'],
      $params['city-1']
    );

    $register_request = new RegisterRequest(
      $AvtaleGiro,
      $CardInfo,
      $Customer,
      $description,
      $DnBNorDirectPayment,
      $Environment,
      $MicroPayment,
      $Order,
      $Recurring,
      $serviceType,
      $Terminal,
      $transactionId,
      $transactionReconRef
    );

    $input_parameters = array(
      "token" => $paymentProcessor['password'],
      "merchantId"  => $paymentProcessor['user_name'],
      "request" => $register_request
    );

    $wsdl = $paymentProcessor['url_site'];
    $terminal = $paymentProcessor['url_api'];

    try {
      if (strpos($_SERVER["HTTP_HOST"], 'uapp') > 0) {
        // Creating new client having proxy.
        $client = new SoapClient($wsdl, array('proxy_host' => "isa4", 'proxy_port' => 8080, 'trace' => TRUE, 'exceptions' => TRUE));
      }
      else {
        // Creating new client without proxy.
        $client = new SoapClient($wsdl, array('trace' => TRUE, 'exceptions' => TRUE));
      }

      // Do the API call.
      $output_parameters = $client->__call('Register', array("parameters" => $input_parameters));

      // RegisterResult.
      $register_result = $output_parameters->RegisterResult;

      $terminal_parameters = "?merchantId=" . $paymentProcessor['user_name'] . "&transactionId=" .  $register_result->TransactionId;
      CRM_Utils_System::redirect($terminal . $terminal_parameters);
      exit();
    }
    catch (SoapFault $fault) {
      drupal_set_message(t('An unrecognised error has occured. Please contact an administrator.'), 'error');
      drupal_goto('civicrm/contribute/transact?reset=1&id=8');
      watchdog('nets_payment_do_transfer', $fault, array(), WATCHDOG_ERROR, NULL);
    }
  }

  /**
   * This function returns an error if there is any.
   *
   * @return
   *  String the error message if any
   * @public
   */
  function &error($error_code = NULL, $error_message = NULL) {
    return $e;
  }

  /**
   * This function checks to see if we have the right config values.
   *
   * @return string
   *   the error message if any
   * @public
   */
  function checkConfig() {
    return NULL;
  }

  /**
   * Handle a notification request from a payment gateway.
   *
   * Might be useful to pass in the paymentProcessor object.
   *
   * $_GET and $_POST are already available in IPN so no point passing them?
   */
  function handlePaymentNotification() {
    require_once 'NetsIPN.php';

    $ipn = new org_dfs_payment_NetsIPN();
    $ipn->main('contribute', $this->_paymentProcessor);
  }
}
