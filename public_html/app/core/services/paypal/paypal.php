<?php
header('Access-Control-Allow-Origin: *');

/**
 * Handle Paypal payments
 */
//Import Omni classes into the global namespace to handle payment
use Omnipay\Omnipay;
require dirname(__FILE__) . '/../../../../vendor/autoload.php';
require_once('../../../../../private/'. $_SERVER['HTTP_HOST'].'/include/config.php');
require_once('../../directives/billing/bills.php');
require_once('../../../../include/nocache.php');
require_once('../../../../include/invalidrequest.php');

if (isset($_POST['type']) && !empty(isset($_POST['type']))) {
    $type = $_POST['type'];

    switch ($type) {
        case "startPurchase":
            startPurchase($mysqli, json_decode($_POST['purchase'], true));
            break;
        case "completePurchase":
            completePurchase($mysqli, $_POST['payerid'], $_POST['paymentid']);
            break;
        default:
            invalidRequest();
    }
} else {
    invalidRequest();
};


/**
 * This function gets the clientId and the clientSecret from the database
 */
function getPaypalClientInfo($mysqli) {
    $query = "SELECT paypal_clientid, paypal_clientsecret, paypal_usesandbox FROM cpa_configuration order by id";
    $result = $mysqli->query($query);
    $data = array();
    $data['data'] = array();
    $row = $result->fetch_assoc();
    if (isset($row)) {
        $row['paypal_usesandbox'] = (int) $row['paypal_usesandbox'];
        return $row;
    }
    return null;
};

/**
 * This function initializes the gateway
 */
function createGateway($mysqli) {
    $clientInfo = getPaypalClientInfo($mysqli);
    if (isset($clientInfo)) {
        $gateway = Omnipay::create('PayPal_Rest');
        $gateway->setClientId($clientInfo['paypal_clientid']);
        $gateway->setSecret($clientInfo['paypal_clientsecret']);
        if ($clientInfo['paypal_usesandbox']) {
            $gateway->setTestMode(true); //set it to 'false' when go live
        } else {
            $gateway->setTestMode(false); //set it to 'false' when go live
        }
        return $gateway;
    }
    return null;
}

/**
 * Creates the purchase and returns the redirect url
 */
function createPurchase($gateway, $purchase) {
    $data = array();
    $response = null;
    $purchase = $gateway->purchase(array(
        'amount' => $purchase['amount']['total'],
        'currency' => 'CAD',
        'items' => $purchase['item_list']['items'],
        'transactionId' =>(isset($purchase['billid']) ? $purchase['billid'] : null),      // Put the billId here!
        'description' =>$purchase['description'],
        'note_to_payer' => 'Testing note',
        'returnUrl' => $purchase['returnUrl'],
        'cancelUrl' => $purchase['returnUrl'],
    ));
    
    // Set no shipping - Does not seem to work! Info is passed in request but he dialog still shows shipping info!
    // Had to change the getData() method in C:\wamp\www\JeNiAl\public_html\vendor\omnipay\paypal\src\Message\RestAuthorizeRequest.php

    $response = $purchase->send();
    $data['transDataAfter'] = $purchase->getPayerId();
    if ($response->isRedirect()) {
        $data['redirect'] = true;
        $data['redirecturl'] = $response->getRedirectUrl(); // this gets the redirect URL
    } else {
        // not successful
        $data['success'] = false;
        $data['response'] = $response->getMessage();
        $data['detail'] = $response->getData();
        return $data;
        }
    $data['success'] = true;
    $data['detail'] = isSet($response) ? $response->getData() : null;
    return $data;
}

/**
 * Complete the purchase. Called by the return url when the paypal returns.
 */
function completePurchase($mysqli, $payerid, $paymentid) {
    $data = Array();
    $gateway = createGateway($mysqli);
    $transaction = $gateway->completePurchase(array('payer_id'=> $payerid,'transactionReference' => $paymentid));
    $data['transaction'] = $transaction;
    $response = $transaction->send();

    if ($response->isSuccessful()) {
        // The customer has successfully paid.
        $arr_body = $response->getData();
        $data['reponse'] = $arr_body;

        $paymentid      = $mysqli->real_escape_string($arr_body['id']);
        $createddate    = $mysqli->real_escape_string($arr_body['create_time']);
        $payerid        = $mysqli->real_escape_string($arr_body['payer']['payer_info']['payer_id']);
        $payeremail     = $mysqli->real_escape_string($arr_body['payer']['payer_info']['email']);
        $transactionid  = $mysqli->real_escape_string($arr_body['transactions'][0]['related_resources'][0]['sale']['id']);
        $amount         = $mysqli->real_escape_string($arr_body['transactions'][0]['amount']['total']);
        $transactionfee = $mysqli->real_escape_string($arr_body['transactions'][0]['related_resources'][0]['sale']['transaction_fee']['value']);
        $invoicenumber  = $mysqli->real_escape_string(isset($arr_body['transactions'][0]['invoice_number'])?$arr_body['transactions'][0]['invoice_number']:0);
        $paymentstatus  = $mysqli->real_escape_string($arr_body['state']);
        $dbresponse     = json_encode($arr_body);

        $query = "INSERT INTO cpa_paypal_transactions(/*createddate, */paymentid, payerid, payeremail, transactionid, amount, transactionfee, invoicenumber, paymentstatus, response) 
                  VALUES(/*null*//*convert_tz('$createddate', '+00:00', @@session.time_zone),*/ '$paymentid', '$payerid', '$payeremail', '$transactionid', '$amount', '$transactionfee', '$invoicenumber', '$paymentstatus', '$dbresponse')";
        try {
            // $invoicenumber could be 0 for example when testing the configuration. We charge 10$ but we don't have a billid.
            if ($invoicenumber != 0) {
                // On a refresh of the page, we will send the complete purchase several times and it doesn't matter, 
                // but let's not save this transaction several times. Catch the duplicate exception.
                $mysqli->query($query);
                // We need to add a transaction for this bill
                $query = "INSERT INTO cpa_bills_transactions (id, billid, transactiontype, transactionamount, transactiondate, paymentmethod, checkno, receiptno, paperreceiptno, receivedby, comments) 
                        VALUES (NULL, $invoicenumber, 'PAYMENT', '$amount', CURDATE() /*convert_tz('$createddate', '+00:00', @@session.time_zone)*/, 'PAYPAL', 0, 0, 0, 'PAYPAL', '')";
                if ($mysqli->query($query)) {
                    $amount = $amount * -1;
                    updateBillPaidAmountInt($mysqli, $invoicenumber, $amount);
                }
            }
            $data['success'] = true;
        } catch(Exception $e) {
            $data['success'] = false;
            $data['detail'] = $e->getMessage();
        }
    } else {
        $data['success'] = false;
        $data['detail'] = $response->getMessage();
    }
    echo json_encode($data);
}

/**
 * This function opens the paypal gateway and creates the purchase
 */
function startPurchase($mysqli, $purchase) {
    $data = array();
    try {
        $gateway = createGateway($mysqli);
        if (isset($gateway)) {
            $data['purchase'] = createPurchase($gateway, $purchase);
            $data['success'] = true;
            $data['clientId'] = $gateway->getClientId();
        } else {
            $data['success'] = false;
            $data['message'] = '$gateway is null';
        }
	} catch (Exception $e) {
        $data = array();
        $data['success'] = false;
        $data['message'] = $e->getMessage();
        echo json_encode($data);
	}
    echo json_encode($data);
}

