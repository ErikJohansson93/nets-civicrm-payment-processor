<?php

require_once 'CRM/Core/Payment.php';

class org_dfs_payment_Nets extends CRM_Core_Payment {
  /**
   * We only need one instance of this object. So we use the singleton
   * pattern and cache the instance in this variable.
   *
   * @var object
   * @static
   */
  static private $_singleton = NULL;

  /**
   * mode of operation: live or test.
   *
   * @var object
   */
  protected $_mode = NULL;

  /**
   * URLS used by Nets.
   */
  protected $liveprocessurl = '';
  protected $testprocessurl = '';
  protected $livequeryurl = '';
  protected $testqueryurl = '';


  /**
   * Constructor.
   *
   * @param string $mode
   *   The mode of operation: live or test.
   *
   * @return void
   */
  function __construct($mode, &$payment_processor) {
    $this->liveprocessurl = 'https://epayment.nets.eu/Netaxept/Process.aspx';
    $this->testprocessurl = 'https://test.epayment.nets.eu/Netaxept/Process.aspx';
    $this->livequeryurl = 'https://epayment.nets.eu/Netaxept/Query.aspx';
    $this->testqueryurl = 'https://test.epayment.nets.eu/Netaxept/Query.aspx';

    $this->_mode = $mode;
    $this->_paymentProcessor = $payment_processor;
    $this->_processorName = ts('Nets');
  }

  /**
   * Singleton function used to manage this object.
   *
   * @param string $mode
   *   The mode of operation: live or test
   *
   * @return object
   *   Processor.
   * @static
   */
  static function &singleton($mode, &$payment_processor, &$payment_form = NULL, $force = FALSE) {
    $processorName = $payment_processor['name'];
    if (self::$_singleton[$processorName] === NULL) {
      self::$_singleton[$processorName] = new org_dfs_payment_Nets($mode, $payment_processor);
    }
    return self::$_singleton[$processorName];
  }

  /**
   * This function checks to see if we have the right config values.
   *
   * @return string
   *   The error message if any.
   * @public
   */
  public function checkConfig() {
    $config = CRM_Core_Config::singleton();
    $error = array();
    if (empty($this->_paymentProcessor['user_name'])) {
      $error[] = ts('Merchant Identifier must not be empty.');
    }
    if (empty($this->_paymentProcessor['password'])) {
      $error[] = ts('Secret Key must not be empty.');
    }
    if (!empty($error)) {
      return implode('<p>', $error);
    }
    else {
      return NULL;
    }
  }

  /**
   * Function not implemented.
   */
  public function doDirectPayment(&$params) {
    CRM_Core_Error::fatal(ts('This function is not implemented'));
  }

  /**
   * Method for getting payment Nets Payment processor.
   */
  private function getProcessor($is_test) {
    $response = civicrm_api3('PaymentProcessor', 'get', array(
      'sequential' => 1,
      'is_test' => $is_test,
      'name' => $this->_paymentProcessor['name'],
    ));

    // Add relevant payment processor information.
    $processor = $response['values'][0];
    $processor['query_url'] = $is_test ? $this->testqueryurl : $this->$livequeryurl;
    $processor['process_url'] = $is_test ? $this->testprocessurl : $this->liveprocessurl;

    return $processor;
  }

  /**
   * Build query string.
   *
   * @param  array $params
   * @return string
   */
  private function buildQueryString($params) {
    $query_tring = '?';

    foreach ($params as $name => $value) {
      $query_tring .= $name . '=' . $value . '&';
    }

    return rtrim($query_tring, '&');
  }

  /**
   * Get protocol for url.
   *
   * @return string
   *   The current protocol.
   */
  private function getProtocol() {
    return (isset($_SERVER['HTTPS']) && $_SERVER["HTTPS"] == "on") ? 'https://' : 'http://';
  }

  /**
   * Build return url.
   */
  private function getRedirectUrl($params, $component) {
    $return_url = '';

    if ($component == "contribute") {

      // Build query string.
      $query_tring = http_build_query(array(
        'processor_name' => 'Nets',
        'module' => $component,
        'qfkey' => $params['qfKey'],
        'contactID' => $params['contactID'],
        'contributionID' => $params['contributionID'],
        'invoiceID' => $params['invoiceID'],
      ));

      // Set return url.
      $return_url = $this->getProtocol() . $_SERVER['HTTP_HOST'] . '/civicrm/payment/ipn?' . $query_tring;

      if ($this->_mode == 'test') {
        $return_url = $return_url . '&action=preview&test=1';
      }
    }

    return urlencode($return_url);
  }

  /**
   * Register and transaction at Nets and gets the transaction ID.
   *
   * @param array $netaxept
   *   Data used to communicate with netaxept.
   *
   * @return Mixed
   *   Transaction id or FALSE if the registration failed.
   */
  private function registerAndGetTransactionId($netaxept) {
    $url = $this->_paymentProcessor['url_site'] . $this->buildQueryString($netaxept);

    $response = drupal_http_request($url, array('method' => 'POST'));

    if ($response->code == 200 && !empty($response->data)) {
      $xml = simplexml_load_string($response->data);

      if (!empty($xml->TransactionId)) {
        $this->transactionId = $xml->TransactionId;
        return $xml->TransactionId;
      }
    }

    return FALSE;
  }

  /**
   * Sets appropriate parameters for checking out to UCM Payment Collection.
   *
   * @param array $params
   *   Name value pair of contribution data.
   *
   * @param string $component
   *   The Civicrm that triggered payment.
   *
   * @access public
   */
  public function doTransferCheckout(&$params, $component) {
    $component = strtolower($component);
    $config = CRM_Core_Config::singleton();

    // This payment processor only supports contributions atm.
    if ($component != 'contribute' && $component != 'event') {
      CRM_Core_Error::fatal(ts('Component is invalid'));
    }

    // Important because without storing session objects, civicrm wouldnt know
    // if the confirm page ever submitted as we are using exit at the end and
    // and it will never redirect to the thank you page, rather keeps
    // redirecting to the confirmation page.
    require_once 'CRM/Core/Session.php';
    CRM_Core_Session::storeSessionObjects();

    // Set default values for netaxept.
    $netaxept = array(
      'amount' => number_format($params['amount'], 2, '', ''),
      'currencyCode' => 'SEK',
      'merchantId' => $this->_paymentProcessor['user_name'],
      'token' => $this->_paymentProcessor['password'],
      'orderNumber' => $params['contributionID'],
      'redirectUrl' => $this->getRedirectUrl($params, $component),
    );

    if ($transaction_id = $this->registerAndGetTransactionId($netaxept)) {
      $params['trxn_id'] = $transaction_id;

      // Define query parameters.
      $values = array(
        'merchantId' => $this->_paymentProcessor['user_name'],
        'transactionID' => (string) $transaction_id,
        'redirectUrl' => $netaxept['redirectUrl'],
      );

      // Build redirect URL.
      $url = $this->_paymentProcessor['url_api'] . $this->buildQueryString($values);

      // Redirect to payment gateway.
      CRM_Utils_System::redirect($url);

      return $params;
    }
  }

  /**
   * Handle response from Nets.
   */
  public function handlePaymentNotification() {
    require_once 'CRM/Utils/Array.php';

    // Get values.
    $module = CRM_Utils_Array::value('module', $_GET);
    $qf_key = CRM_Utils_Array::value('qfkey', $_GET);
    $cid = CRM_Utils_Array::value('contributionID', $_GET);
    $response_code = CRM_Utils_Array::value('responseCode', $_GET);
    $trxn_id = CRM_Utils_Array::value('transactionId', $_GET);

    // Proceed if everything went fine at Nets.
    if ($response_code == 'OK') {
      // Authenticate payment.
      $auth = $this->processPayment('AUTH');

      // If authenticate was fine, capture amount.
      if ($auth) {
        $capture = $this->processPayment('CAPTURE');

        // If capture was fine, proceed to thank you page..
        if ($capture) {
          // Finish payment and mark contributions as payed.
          $this->finishPayment($cid, $trxn_id);
        }
      }
    }
    else {
      // Show error if something went wrong.
      CRM_Core_Error::fatal(ts('Unable to establish connection to the payment gateway.'));
    }

    // Determine redirect.
    switch ($module) {
      case 'contribute':
        $final_url = CRM_Utils_System::url('civicrm/contribute/transact', "_qf_ThankYou_display=1&qfKey={$qf_key}", FALSE, NULL, FALSE);
        break;

      case 'event':
        $final_url = CRM_Utils_System::url('civicrm/event/register', "_qf_Confirm_display=true&qfKey={$qf_key}", FALSE, NULL, FALSE);
        break;

      default:
        require_once 'CRM/Core/Error.php';
        CRM_Core_Error::debug_log_message("Could not get module name from request url");
        echo "Could not get module name from request url\r\n";
        break;
    }

    // Redirect to success page.
    CRM_Utils_System::redirect($final_url);
  }

  /**
   * Method for processing a payment at Nets.
   */
  private function processPayment($op) {
    require_once 'CRM/Utils/Array.php';
    $is_test = CRM_Utils_Array::value('test', $_GET);
    $transaction_id = CRM_Utils_Array::value('transactionId', $_GET);
    $processor = $this->getProcessor($is_test);

    // Define params to be sent.
    $params = array(
      'merchantId' => $processor['user_name'],
      'token' => $processor['password'],
      'transactionId' => $transaction_id,
      'operation' => $op,
    );

    // Try to auth payment.
    $url = $processor['process_url'] . '?' . http_build_query($params);
    $response = drupal_http_request($url, array('method' => 'POST'));

    // Parse result.
    $xml = simplexml_load_string($response->data);

    // Return result.
    return (string) $xml->ResponseCode == 'OK';
  }


  /**
   * Method for marking contributions as payed.
   */
  private function finishPayment($cid, $trxn_id) {
    if (!is_numeric($cid)) {
      require_once 'CRM/Core/Error.php';
      CRM_Core_Error::debug_log_message("No cid in payment.");
      echo "Could not finish payment.";
    }

    // Try to complete transaction.
    try {
      // Complete payment.
      civicrm_api3('Contribution', 'completetransaction', array(
        'id' => $cid,
        'trxn_id' => $trxn_id,
        "is_email_receipt" => 1,
      ));

      // Send receipt via email.

    }
    catch (Exception $e) {
      // DANADA.
    }
  }
}
