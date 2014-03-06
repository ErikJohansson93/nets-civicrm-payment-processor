<?php
/**
 * @file
 * Instant payment processor notification file.
 * +--------------------------------------------------------------------+
 * | CiviCRM version 4.2                                                |
 * +--------------------------------------------------------------------+
 * | Copyright CiviCRM LLC (c) 2004-2012                                |
 * +--------------------------------------------------------------------+
 * | This file is a part of CiviCRM.                                    |
 * |                                                                    |
 * | CiviCRM is free software; you can copy, modify, and distribute it  |
 * | under the terms of the GNU Affero General Public License           |
 * | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 * |                                                                    |
 * | CiviCRM is distributed in the hope that it will be useful, but     |
 * | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 * | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 * | See the GNU Affero General Public License for more details.        |
 * |                                                                    |
 * | You should have received a copy of the GNU Affero General Public   |
 * | License and the CiviCRM Licensing Exception along                  |
 * | with this program; if not, contact CiviCRM LLC                     |
 * | at info[AT]civicrm[DOT]org. If you have questions about the        |
 * | GNU Affero General Public License or the licensing of CiviCRM,     |
 * | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 * +--------------------------------------------------------------------+
 */

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2012
 * $Id$
 */
class org_dfs_payment_NetsIPN extends CRM_Core_Payment_BaseIPN {
  static $_paymentProcessor = NULL;
  function __construct() {
    parent::__construct();
  }

  static function retrieve($name, $type, $location = 'POST', $abort = TRUE) {
    static $store = NULL;
    $value = CRM_Utils_Request::retrieve($name, $type, $store,
      FALSE, NULL, $location
    );
    if ($abort && $value === NULL) {
      CRM_Core_Error::debug_log_message("Could not find an entry for $name in $location");
      echo "Failure: Missing Parameter<p>";
      exit();
    }
    return $value;
  }

  function single(&$input, &$ids, &$objects, $recur = FALSE, $first = FALSE) {
    $contribution = &$objects['contribution'];

    $transaction = new CRM_Core_Transaction();
    $membership = &$objects['membership'];
    $contribution->total_amount = $input['amount'];

    $status = $input['paymentStatus'];
    if ($status != 'OK') {
      return $this->failed($objects, $transaction);
    }

    // Check if contribution is already completed, if so we ignore this ipn
    if ($contribution->contribution_status_id == 1) {
      $transaction->commit();
      CRM_Core_Error::debug_log_message("returning since contribution has already been handled");
      echo "Success: Contribution has already been handled<p>";
      return TRUE;
    }

    $finalURL = CRM_Utils_System::url('civicrm/contribute/transact',
      "_qf_ThankYou_display=1&qfKey={$_GET['qfkey']}",
      FALSE, NULL, FALSE
    );

    // Create membership contribution.
    dfs_civicrm_memberfee_update_contribution('');

    // Complete the transaction.
    $this->completeTransaction($input, $ids, $objects, $transaction, $recur);

    CRM_Utils_System::redirect($finalURL);
  }

  /**
   * Handle the payment.
   *
   * @return Query object
   * @public
   */
  function main($component = 'contribute', $processor) {
    $objects = $ids = $input = array();
    $input['component'] = $component;
    $queryInfo = $this->netsQuery($processor);

    $paymentResult = $this->netsSale($processor);

    // Calculate the amount without the memberfee.
    $values = dfs_civicrm_memberfee_get_membership($_GET['contactID']);

    // Get membershiptype fees.
    $fee = dfs_civicrm_memberfee_get_fee($values['membership_type_id']);
    $amount = rtrim($fee['total'], '.');

    // Get the contribution and contact ids from the GET params
    $ids['contact'] = self::retrieve('contactID', 'Integer', 'GET', TRUE);
    $ids['contribution'] = self::retrieve('contributionID', 'Integer', 'GET', TRUE);
    $ids['membership'] = self::retrieve('membershipID', 'Integer', 'GET', FALSE);

    $input['invoice'] = self::retrieve('invoiceID', 'String', 'GET', TRUE);
    $input['trxn_id'] = self::retrieve('transactionId', 'String', 'GET', TRUE);
    $input['amount'] = $amount;
    $input['paymentStatus'] = $paymentResult->ResponseCode;

    $paymentProcessorID = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_PaymentProcessorType', 'Nets', 'id', 'name');
    $paymentProcessorID = intval($paymentProcessorID);

    if (!$this->validateData($input, $ids, $objects, TRUE, $paymentProcessorID)) {
      return FALSE;
    }

    self::$_paymentProcessor = &$objects['paymentProcessor'];

    return $this->single($input, $ids, $objects, FALSE, FALSE);
  }

  /**
   * Do a Sale against nets.
   *
   * @return Query object
   * @public
   */
  function netsSale($processor) {
    require_once("netsAPI/ClassProcessRequest.php");

    $query = $this->netsQuery($processor);

    $transactionId = "";
    if (isset($_GET['transactionId'])) {
      $transactionId = $_GET['transactionId'];
    }
    if (isset($_POST['transactionId'])) {
      $transactionId = $_POST['transactionId'];
    }

    $ProcessRequest = new ProcessRequest(
      $query->OrderInformation->OrderDescription,
      "SALE",
      $query->OrderInformation->Total,
      $transactionId,
      ""
    );

    $InputParametersOfProcess = array(
      "token"       => $processor['password'],
      "merchantId"  => $processor['user_name'],
      "request"     => $ProcessRequest
    );

    $wsdl = $processor['url_site'];

    try {
      if (strpos($_SERVER["HTTP_HOST"], 'uapp') > 0) {
        // Creating new client having proxy
        $client = new SoapClient($wsdl, array('proxy_host' => "isa4", 'proxy_port' => 8080, 'trace' => true,'exceptions' => true));
      }
      else {
        // Creating new client without proxy
        $client = new SoapClient($wsdl, array('trace' => true,'exceptions' => true ));
      }

      $OutputParametersOfProcess = $client->__call('Process' , array("parameters"=>$InputParametersOfProcess));

      $ProcessResult = $OutputParametersOfProcess->ProcessResult;

      return $ProcessResult;
    }
    catch (SoapFault $fault) {
      CRM_Core_Error::fatal(ts('Error: ' . $fault));
    }
  }

  /**
   * Get a query result from nets.
   *
   * @return Query object
   * @public
   */
  function netsQuery($processor) {
    require_once("netsAPI/ClassQueryRequest.php");

    $QueryRequest = new QueryRequest($_GET['transactionId']);

    $InputParametersOfQuery = array(
      "token"       => $processor['password'],
      "merchantId"  => $processor['user_name'],
      "request"     => $QueryRequest
    );

    $wsdl = $processor['url_site'];

    try {
      if (strpos($_SERVER["HTTP_HOST"], 'uapp') > 0) {
        // Creating new client having proxy
        $client = new SoapClient($wsdl, array('proxy_host' => "isa4", 'proxy_port' => 8080, 'trace' => TRUE,'exceptions' => TRUE));
      }
      else {
        // Creating new client without proxy
        $client = new SoapClient($wsdl, array('trace' => TRUE, 'exceptions' => TRUE));
      }

      $OutputParametersOfQuery = $client->__call('Query', array("parameters" => $InputParametersOfQuery));
      $QueryResult = $OutputParametersOfQuery->QueryResult;
      return $QueryResult;
    }
    catch (SoapFault $fault) {
      CRM_Core_Error::fatal(ts('Error: ' . $fault));
    }
  }
}
