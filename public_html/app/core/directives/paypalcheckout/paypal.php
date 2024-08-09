<?php
header('Access-Control-Allow-Origin: *');

/**
 * Handle Paypal payments
 */
//Import Omni classes into the global namespace to handle payment
use Omnipay\Omnipay;
require dirname(__FILE__) . '/../../../../vendor/autoload.php';
require_once('../../../../../private/'. $_SERVER['HTTP_HOST'].'/include/config.php');
require_once('../../../../include/nocache.php');
require_once('../../../../include/invalidrequest.php');

if (isset($_POST['type']) && !empty(isset($_POST['type']))) {
    $type = $_POST['type'];

    switch ($type) {
        case "testPaypal":
            testPaypal($mysqli);
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


// $response = $this->gateway->purchase(array(
//     'amount' => $request->input('amount'),
//     'items' => array(
//         array(
//             'name' => 'Course Subscription',
//             'price' => $request->input('amount'),
//             'description' => 'Get access to premium courses.',
//             'quantity' => 1
//         ),
//     ),
//     'currency' => env('PAYPAL_CURRENCY'),
//     'returnUrl' => url('success'),
//     'cancelUrl' => url('error'),
// ))->send();

function testPurchase($gateway) {
    // try {
        $data = array();
        $response = null;
        $purchase = $gateway->purchase(array(
            'amount' => '10.00',
            'currency' => 'CAD',
            'items' => array(
                array(
                    'name' => 'PP1_SAM',
                    'price' => '10.00',
                    'description' => 'Cours de PP du samedi',
                    'quantity' => 1
                ),
            ),
            // 'transactionId' =>'1234567891',      // Put the billId here!
            'note_to_payer' => 'Testing note',
            'noShipping' => 'true',         // does nothing
            // "brand_name" =>"CPA L'inconnu", // does nothing!
            'returnUrl' => '/#!/configurationview',
            'cancelUrl' => '/#!/configurationview',
            //'application_context' => array('shipping_preference' => 'NO_SHIPPING'),
        ));
        // $purchase->setTransactionId('1234567890'); // same as "trasactionId" in purchase array
        
        // Set no shipping - Does not seem to work! Info is passed in request but he dialog still shows shipping info!
        // Had to change the getData() method in C:\wamp\www\JeNiAl\public_html\vendor\omnipay\paypal\src\Message\RestAuthorizeRequest.php
        // $transData = $purchase->getData();
        // $data['transDataBefore'] = $transData;
        // $transData['application_context']['shipping_preference'] = 'NO_SHIPPING';
        // $data['transDataSending'] = $transData;

        $response = $purchase->send();
//        $purchase->sendData($transData);
        $data['transDataAfter'] = $purchase->getPayerId();
        if ($response->isRedirect()) {
            $data['redirect'] = true;
            // $response->redirect(); // this will automatically forward the customer
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
    // } catch(Exception $e) {
    //     throw($response->getData());
    //     echo $e->getMessage();
    // }
}

function completePurchase($mysqli, $payerid, $paymentid) {
    $data = Array();
    try {
        $gateway = createGateway($mysqli);
        $transaction = $gateway->completePurchase(array('payer_id'=> $payerid,'transactionReference' => $paymentid));
        $data['transaction'] = $transaction;
        $response = $transaction->send();

        if ($response->isSuccessful()) {
            // The customer has successfully paid.
            $arr_body = $response->getData();
            $data['reponse'] = $arr_body;

            // $payment_id = $db->real_escape_string($arr_body['id']);
            // $payer_id = $db->real_escape_string($arr_body['payer']['payer_info']['payer_id']);
            // $payer_email = $db->real_escape_string($arr_body['payer']['payer_info']['email']);
            // $amount = $db->real_escape_string($arr_body['transactions'][0]['amount']['total']);
            // $currency = PAYPAL_CURRENCY;
            // $payment_status = $db->real_escape_string($arr_body['state']);

            // $sql = sprintf("INSERT INTO payments(payment_id, payer_id, payer_email, amount, currency, payment_status) VALUES('%s', '%s', '%s', '%s', '%s', '%s')", $payment_id, $payer_id, $payer_email, $amount, $currency, $payment_status);
            // $db->query($sql);
            $data['success'] = true;
            // echo "Payment is successful. Your transaction id is: ". $payment_id;
        } else {
            $data['success'] = false;
            $data['detail'] = $response->getMessage();
        }
    } catch(Exception $e) {
        $data = array();
        $data['success'] = false;
        $data['message'] = $e->getMessage();
        echo json_encode($data);
    }
    echo json_encode($data);
}

/**
 * This function tests Paypal
 */
function testPaypal($mysqli) {
    $data = array();
    try {
        $gateway = createGateway($mysqli);
        if (isset($gateway)) {
            $data['purchase'] = testPurchase($gateway);
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

