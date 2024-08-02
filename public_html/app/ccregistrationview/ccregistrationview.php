<?php
/*
Author : Eric Lamoureux
*/
require_once('../../../private/'. $_SERVER['HTTP_HOST'].'/include/config.php');
require_once('../../include/nocache.php');
require_once('../../include/registration.php');
require_once('../core/directives/billing/bills.php');
require_once('../../include/invalidrequest.php');

if( isset($_POST['type']) && !empty( isset($_POST['type']) ) ){
	$type = $_POST['type'];

	switch ($type) {
		case "getSkaterRegistrationDetails":
			getSkaterRegistrationDetails($mysqli, $_POST['userid'], $_POST['skaterid'], $_POST['sessionid'], $_POST['registrationdate'], $_POST['language']);
			break;
		case "getSessionRules":
			getSessionRules($mysqli, $_POST['sessionid'], $_POST['language']);
			break;
		case "acceptRegistration":
			acceptRegistrationWeb($mysqli, $_POST['registration'], $_POST['billid'], $_POST['language'], true);
			break;
        case "testPaypal":
            testPaypal($mysqli, $_POST['registration']);
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

function acceptRegistrationWeb($mysqli, $registration, $billid, $language, $validcount) {
	if (memberAlreadyHasARegistration($mysqli, $registration) == false) {
		acceptRegistration($mysqli, $registration, $billid, $language, $validcount);
	} else {
		$data['success'] = false;
		$data['errno']   = 9999;
		$data['message'] = 'Member already has a registration.';
	}
	echo $data;
}

/**
 * This function gets the details of one member from database
 */
function getMemberDetailsInt($mysqli, $id, $language) {
	$data = array();
	$data['data'] = array();
	$query = "SELECT * FROM cpa_members WHERE id = '$id'";
	$result = $mysqli->query($query);
	while ($row = $result->fetch_assoc()) {
		$row['id'] = (int) $row['id'];
		$data['data'][] = $row;
	}
	$data['success'] = true;
	return $data;
};

// Try to get the course codes for the filter
 function getSessionCourseCodes($mysqli, $sessionid, $language) {
	$data = array();
	$data['data'] = array();
	$query = "select distinct cc.code, getTextLabel(cc.label, '$language') coursecodelabel
						from cpa_sessions_courses csc
						join cpa_courses cc ON cc.code = csc.coursecode
						where csc.sessionid = $sessionid
						and cc.active = 1
						and cc.acceptregistrations = 1
						order by cc.code";
	$result = $mysqli->query($query);
	while ($row = $result->fetch_assoc()) {
		$data['data'][] = $row;
	}
	$data['success'] = true;
	return $data;
};

/*
	This function copies an existing registration and returns the new registration id
*/
function copyRegistration($mysqli, $registrationid, $newregistrationdatestr, $newregistrationstatus) {
	try{
		$data = array();
		$query = "INSERT INTO cpa_registrations(id, memberid, sessionid, showid, registrationdate, relatednewregistrationid, relatedoldregistrationid, status, regulationsread, familycount)
							SELECT null, memberid, sessionid, showid, curdate(), null, id, '$newregistrationstatus', regulationsread, familycount
							FROM cpa_registrations where id = '$registrationid' ";
		if ($mysqli->query($query)) {
			$newregistrationid = (int) $mysqli->insert_id;
			$query = "UPDATE cpa_registrations SET relatednewregistrationid = '$newregistrationid', lastupdateddate = CURRENT_TIMESTAMP where id = '$registrationid'";
			if ($mysqli->query($query)) {
				$query = "INSERT INTO cpa_registrations_charges(id, registrationid, chargeid, amount, comments, oldchargeid)
									SELECT null, '$newregistrationid', chargeid, amount, comments, id
									FROM cpa_registrations_charges
									WHERE registrationid = '$registrationid' ";
				if ($mysqli->query($query)) {
					$query = "INSERT INTO cpa_registrations_courses(id, registrationid, courseid, amount, selected)
										SELECT null, '$newregistrationid', courseid, amount, selected
										FROM cpa_registrations_courses
										WHERE registrationid = '$registrationid'";
					if (!$mysqli->query($query)) {
						throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
					}
					$query = "INSERT INTO cpa_registrations_numbers(id, registrationid, numberid, amount, selected)
										SELECT null, '$newregistrationid', numberid, amount, selected
										FROM cpa_registrations_numbers
										WHERE registrationid = '$registrationid'";
					if (!$mysqli->query($query)) {
						throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
					}
				} else {
					throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
				}
			} else {
				throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
			}
		} else {
			throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
		}
		$data['newregistrationid'] = $newregistrationid;
		$data['success'] = true;
		$data['message'] = 'Registration copied successfully.';
		return $data;
		exit;
	}catch (Exception $e) {
		$data = array();
		$data['success'] = false;
		$data['message'] = $e->getMessage();
		return $data;
		exit;
	}
}


/**
 * This function gets the registration of the skaters
 * @throws Exception
 */
function getSkaterRegistrationDetails($mysqli, $userid, $skaterid, $sessionid, $registrationdate, $language){
	try{
		$data = array();
		$lastupdateddate=null;
		// Check if user already has a registration for this session
		$query = "SELECT cr.id, cr.lastupdateddate
				  FROM cpa_registrations cr
				  WHERE cr.memberid = $skaterid
				  AND cr.sessionid = $sessionid
				  AND (cr.relatednewregistrationid is null OR cr.relatednewregistrationid = 0)";
		$result = $mysqli->query( $query );
		$row = $result->fetch_assoc();
		if (!empty($row['id'])) {
			$registrationid = (int)$row['id'];
			$lastupdateddate = $row['lastupdateddate'];
		} else {
			$registrationid = 0;
		}
		$query = "SELECT cs.id sessionid, getTextLabel(cs.label, '$language') sessionname, cs.coursesstartdate, cs.coursesenddate, cs.onlinepaymentoption, $skaterid memberid
				  FROM cpa_sessions cs
				  WHERE cs.id = $sessionid";
		$result = $mysqli->query( $query );
		while ($row = $result->fetch_assoc()) {
			$row['onlinepaymentoption'] = (int)$row['onlinepaymentoption'];
			$temp = getMemberDetailsInt($mysqli, $skaterid, $language)['data'];
			if (count($temp) > 0) {
				$row['member'] = $temp[0];
			}
			$row['courses'] 			= getSessionCoursesDetails($mysqli, $registrationid, $registrationdate, $sessionid, $language)['data'];
			$row['coursecodes'] 		= getSessionCourseCodes($mysqli, $sessionid, $language)['data'];
			$row['charges'] 			= getChargesDetails($mysqli, $registrationid, $sessionid, $language, true)['data'];
			$row['familyMemberCount']	= countFamilyMembersRegistrations($mysqli, 1, $sessionid, $skaterid, $language);
			$tmpBillData    			= getRegistrationBillInt($mysqli, $registrationid, $language)['data'];
			if (count($tmpBillData) > 0) {
				$row['bill'] = $tmpBillData[0];
			}
			$data['data'][] = $row;
		}
		$data['data'][0]['id'] = (int)$registrationid;
		$data['data'][0]['originalId'] = (int)$registrationid;
		$data['data'][0]['lastupdateddate'] = $lastupdateddate;
		$data['success'] = true;
		echo json_encode($data);
		exit;
	}catch (Exception $e){
		$data = array();
		$data['success'] = false;
		$data['message'] = $e->getMessage();
		echo json_encode($data);
		exit;
	}
};

/**
 * This function gets the session rules for the session. Either the new rules paragraphs or the old rule file
 * @throws Exception
 */
function getSessionRules($mysqli, $sessionid, $language){
	try{
		$data = array();
		$data['newrule'] = false;
		$query = "	SELECT 	csr.id, csr.paragraphindex, csr.title, csr.subtitle, csr.paragraphtext, csr.visiblepreview publish,
							getWSTextLabel(csr.title, '$language') title, getWSTextLabel(csr.subtitle, '$language') subtitle, getWSTextLabel(csr.paragraphtext, '$language') paragraphtext
					FROM cpa_sessions_rules2 csr
					WHERE csr.sessionid = $sessionid
					AND csr.publish = 1
					ORDER BY paragraphindex";
		$result = $mysqli->query($query);
		$data['data'] = array();
		while ($row = $result->fetch_assoc()) {
			$row['id'] = (int)$row['id'];
			$row['paragraphindex'] = (int)$row['paragraphindex'];
			$data['paragraphs'][] = $row;
			$data['newrule'] = true;
		}
		// New rule format has been found
		if ($data['newrule'] == true) {
			$data['success'] = true;
			echo json_encode($data);
		} else {
			// New rule format not found, load the old format.
			$query = "	SELECT rules
						FROM cpa_sessions_rules csr
						WHERE csr.sessionid = $sessionid
						AND language = '$language'";
			$result = $mysqli->query( $query );
			$row = $result->fetch_assoc();
			header("Content-type: text/text;charset=iso-8859-1");
			echo $row['rules'];
		}
		exit;
	}catch (Exception $e){
		$data = array();
		$data['success'] = false;
		$data['message'] = $e->getMessage();
		echo json_encode($data);
		exit;
	}
};

// Test PAYPAL


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
            //'noShipping' => 'true',         // does nothing
            // "brand_name" =>"CPA L'inconnu", // does nothing!
            'returnUrl' => 'http://localhost.jenial.ca/app/#!/configurationview',
            'cancelUrl' => 'http://localhost.jenial.ca/app/#!/configurationview',
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


?>
