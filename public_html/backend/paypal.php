<?php
header('Access-Control-Allow-Origin: *');

/**
 * Handle Paypal payments
 */
//Import Omni classes into the global namespace to handle payment
use Omnipay\Omnipay;
require dirname(__FILE__) . '/../vendor/autoload.php';
require_once('../../private/'. $_SERVER['HTTP_HOST'].'/include/config.php');
require_once('../include/nocache.php');
require_once('../include/invalidrequest.php');

if (isset($_POST['type']) && !empty(isset($_POST['type']))) {
    $type = $_POST['type'];

    switch ($type) {
        case "testPaypal":
            testPaypal($mysqli, $_POST['clientid'], $_POST['clientsecret']);
            break;
        default:
            invalidRequest();
    }
} else {
    invalidRequest();
};

function createGateway($mysqli, $clientid, $clientsecret) {
    $gateway = Omnipay::create('PayPal_Rest');
    $gateway->setClientId($clientid);
    $gateway->setSecret($clientsecret);
    $gateway->setTestMode(true); //set it to 'false' when go live
    return $gateway;
}

function testPurchase($gateway) {
    // try {
        $data = array();
        $response = null;
        $purchase = $gateway->purchase(array(
            'amount' => '10.00',
            'currency' => 'CAD',
            'returnUrl' => 'http://localhost.jenial.ca/app/paypal_return.php',
            // 'cancelUrl' => 'http://localhost.jenial.ca/app/paypal_cancel.php',
            'cancelUrl' => 'http://localhost.jenial.ca/app/#!/configurationview'
        ));
        $response = $purchase->send();
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

/**
 * This function tests Paypal
 */
function testPaypal($mysqli, $clientid, $clientsecret) {
    $data = array();
    try {
        $gateway = createGateway($mysqli, $clientid, $clientsecret);
        $data['purchase'] = testPurchase($gateway);
        $data['success'] = true;
        $data['clientId'] = $gateway->getClientId();
	}catch (Exception $e) {
        $data = array();
        $data['success'] = false;
        $data['message'] = $e->getMessage();
        echo json_encode($data);
	}
    echo json_encode($data);
}

