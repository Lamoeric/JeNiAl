<?php
/*
Author : Eric Lamoureux
*/
require_once('../../../../../private/'. $_SERVER['HTTP_HOST'].'/include/config.php');
require_once('../../../../include/nocache.php');
require_once('../../directives/billing/bills.php');

if( isset($_POST['type']) && !empty( isset($_POST['type']) ) ){
	$type = $_POST['type'];

	switch ($type) {
		case "getRegistrationBill":
			getRegistrationBill($mysqli, $_POST['registrationid'], $_POST['language']);
			break;
		case "getMemberBill":
			getMemberBill($mysqli, $_POST['memberid'], $_POST['language']);
			break;
		case "getBill":
			getBill($mysqli, $_POST['billid'], $_POST['language']);
			break;
		case "updateBillPaidAmount":
			updateBillPaidAmount($mysqli, $_POST['billid'], $_POST['amount']);
			break;
		case "insertSingleTransaction":
			insertSingleTransaction($mysqli, $_POST['bill']);
			break;
		case "getBillingNames":
			getBillingNames($mysqli, $_POST['memberid'], $_POST['language']);
			break;
		case "getBillingEmails":
			getBillingEmails($mysqli, $_POST['billid'], $_POST['language']);
			break;
		case "getAllObjects":
			getAllBills($mysqli, $_POST['sessionid']);
			break;
		case "getObjectDetails":
			getBillDetails($mysqli, $_POST['id']);
			break;
		case "createSingleTestBill":
			createSingleTestBill($mysqli, $_POST['testregistration'], $_POST['language']);
			break;
		case "splitBill":
			splitBill($mysqli, $_POST['billid'], $_POST['registrationid'], $_POST['language']);
			break;
		case "updatePaymentAgreement":
			updatePaymentAgreement($mysqli, $_POST['currentbill']);
			break;
		default:
			invalidRequest();
	}
} else {
	invalidRequest();
};


function invalidRequest(){
	$data = array();
	$data['success'] = false;
	$data['message'] = "Invalid request.";
	echo json_encode($data);
	exit;
};

?>
