<?php
/*
Author : Eric Lamoureux
*/
require_once('../../../../../private/'. $_SERVER['HTTP_HOST'].'/include/config.php');
require_once('../../../../include/nocache.php');

if (isset($_POST['type']) && !empty(isset($_POST['type']))) {
	$type = $_POST['type'];

	switch ($type) {
	case "getOneRegistrationFullDetails":
		getOneRegistrationFullDetails($mysqli, $_POST['testregistrationid'], $_POST['language']);
		break;
  }
} else {
	invalidRequest(null);
};

/**
 * This function gets the member details of a registration for a period from database
 */
function getPeriodRegistrationMember($mysqli, $memberid) {
	$data = array();
	$data['data'] = array();
	$query = "SELECT cm.* FROM cpa_members cm	WHERE id = $memberid";
	$result = $mysqli->query( $query );
	while ($row = $result->fetch_assoc()) {
		$data['data'][] = $row;
	}
	$data['success'] = true;
	return $data;
};

/**
 * This function gets the details of one registration from database
 */
function getOneRegistrationFullDetails($mysqli, $id, $language) {
  try {
  	$data = array();
  	$query = "SELECT cnspr.*, cm1.firstname skaterfirstname, cm1.lastname skaterlastname, cm2.firstname coachfirstname, cm2.lastname coachlastname,
  									 cm3.firstname partnerfirstname, cm3.lastname partnerlastname,
  									 getTextLabel(ct.label, '$language') testlabel, getCodeDescription('startesttypes', ctd.type, '$language') testtypelabel,
  									 ctd.type testtype, ct.summarycode, cu.fullname createdbyfullname, cbt.billid billid, cb.paidinfull
  						FROM cpa_newtests_sessions_periods_registrations cnspr
  						JOIN cpa_members cm1 ON cm1.id = cnspr.memberid
  						JOIN cpa_members cm2 ON cm2.id = cnspr.coachid
  						left JOIN cpa_members cm3 ON cm3.id = cnspr.partnerid
  						JOIN cpa_tests ct ON ct.id = cnspr.testid
  						JOIN cpa_tests_definitions ctd ON ctd.id = ct.testsdefinitionsid
  						left JOIN cpa_users cu ON cu.userid = cnspr.createdby
  						left JOIN cpa_bills_testsessions cbt ON cbt.testssessionsid = cnspr.id
  						left JOIN cpa_bills cb ON cb.id = cbt.billid and cb.relatednewbillid is null
  						WHERE cnspr.id = $id";
  	$result = $mysqli->query( $query );
  	$data['data'] = array();
  	while ($row = $result->fetch_assoc()) {
  		$row['paidinfull'] = (int) $row['paidinfull'];
  		$row['member'] = getPeriodRegistrationMember($mysqli, $row['memberid'])['data'][0];
  		$data['data'][] = $row;
  	}
  	$data['success'] = true;
    echo json_encode($data);
  } catch (Exception $e) {
    $data = array();
    $data['success'] = false;
    $data['message'] = $e->getMessage();
    echo json_encode($data);
  }
};

function invalidRequest($type) {
	$data = array();
	$data['success'] = false;
	$data['message'] = "Invalid request " . $type;
	echo json_encode($data);
	exit;
};

?>
