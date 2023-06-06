<?php
/*
Author : Eric Lamoureux
*/
require_once('../../../private/'. $_SERVER['HTTP_HOST'].'/include/config.php');
require_once('../../include/nocache.php');

if( isset($_POST['type']) && !empty( isset($_POST['type']) ) ){
	$type = $_POST['type'];

	switch ($type) {
		case "getSessionTaxReceiptMembers":
			getSessionTaxReceiptMembers($mysqli, $_POST['sessionid']);
			break;
		default:
			invalidRequest();
	}
} else {
	invalidRequest();
};

/**
 * This function gets all the bills for the session.
 * The bills are used to determine the amount of the receipt
 */
function getSessionTaxReceiptMembers($mysqli, $sessionid) {
	try{
		$data = array();
		$data['data'] = array();
		$query = "SELECT cm.id, concat(cm.firstname, ' ',  cm.lastname) fullname, cm.email, cbr.subtotal
              FROM cpa_registrations cr
              JOIN cpa_sessions cs ON cs.id = cr.sessionid
              JOIN cpa_bills_registrations cbr ON cbr.registrationid = cr.id
              JOIN cpa_bills cb ON cb.id = cbr.billid
              JOIN cpa_members cm ON cm.id = cr.memberid
              WHERE cs.id = $sessionid
              AND (relatednewregistrationid = 0 or relatednewregistrationid is null)
              AND cb.relatednewbillid is null
              ORDER BY cm.lastname, cm.firstname";
		$result = $mysqli->query($query );
		while ($row = $result->fetch_assoc()) {
			$data['data'][] = $row;
		}
		$data['success'] = true;
		echo json_encode($data);
		exit;
	} catch (Exception $e) {
		$data = array();
		$data['success'] = false;
		$data['message'] = $e->getMessage();
		echo json_encode($data);
		exit;
	}
};


function invalidRequest(){
	$data = array();
	$data['success'] = false;
	$data['message'] = "Invalid request.";
	echo json_encode($data);
	exit;
};

?>
