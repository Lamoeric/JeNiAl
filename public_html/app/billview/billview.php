<?php
/*
Author : Eric Lamoureux
*/
require_once('../../../private/'. $_SERVER['HTTP_HOST'].'/include/config.php');
require_once('../../include/nocache.php');
require_once('../core/directives/billing/bills.php');

if( isset($_POST['type']) && !empty( isset($_POST['type']) ) ){
	$type = $_POST['type'];

	switch ($type) {
		case "getAllBills":
			getAllBillsExt($mysqli, $_POST['filter']);
			break;
		case "getBillDetails":
			getBillDetailsExt($mysqli, $_POST['id'], $_POST['language']);
			break;
		default:
			invalidRequest();
	}
} else {
	invalidRequest();
};

/**
 * This function gets the list of all bills from database
 */
function getAllBillsExt($mysqli, $filter){
	try{
		$data = array();
		$whereclause = " where 1=1 ";
		$billingname = '';
		if (!empty($filter['firstname'])) {	
			$billingname = $filter['firstname'] . '%';
		}
		if (!empty($filter['lastname'])) {
			$billingname .= '%' . $filter['lastname'];
		}
		if (!empty($billingname)) {
			$whereclause .= " and billingname like '" . $billingname . "'";
		}
		if (isset($filter['billpaid'])) {
			$billpaid = $filter['billpaid'];
			if ($billpaid == '0') {
				$whereclause .= " and (paidinfull = '1' and totalamount + paidamount = 0) ";
			} else if ($billpaid == '1') {
				$whereclause .= " and totalamount + paidamount < 0 ";
			} else if ($billpaid == '2') {
				$whereclause .= " and totalamount + paidamount > 0 ";
			}
		}

		if (!empty($filter['registration']) && $filter['registration'] == 'REGISTERED') {
			$whereclause .= " and id in (select billid from cpa_bills_registrations where registrationid in (select id from cpa_registrations where sessionid = (select id from cpa_sessions where active = '1') and memberid in (select memberid from cpa_sessions_courses_members where sessionscoursesid in (select id from cpa_sessions_courses where sessionid = (select id from cpa_sessions where active = '1')))))" ;
		}
		if (!empty($filter['registration']) && $filter['registration'] == 'NOTREGISTERED') {
			$whereclause .= " and id not in (select billid from cpa_bills_registrations where registrationid in (select id from cpa_registrations where sessionid = (select id from cpa_sessions where active = '1') and memberid in (select memberid from cpa_sessions_courses_members where sessionscoursesid in (select id from cpa_sessions_courses where sessionid = (select id from cpa_sessions where active = '1')))))" ;
		}
		if (!empty($filter['onlyopenedbills']) && $filter['onlyopenedbills'] == '1') {
			$whereclause .= " and relatednewbillid is null ";
		}
		$data['where'] = $whereclause;
		$query = "	SELECT id, billingname, billingdate, totalamount, totalamount+paidamount as amountleft, relatednewbillid, splitfrombillid, relatedoldbillid 
					FROM cpa_bills ". $whereclause ."
					order by billingdate desc, id desc";
		$result = $mysqli->query( $query );
		while ($row = $result->fetch_assoc()) {
			$data['data'][] = $row;
		}
		$data['success'] = true;
		echo json_encode($data);exit;
	}catch (Exception $e){
		$data = array();
		$data['success'] = false;
		$data['message'] = $e->getMessage();
		echo json_encode($data);
		exit;
	}
};

/**
 * This function gets the list of all contacts for this bill
 */
function getAllBillContacts($mysqli, $billid){
	$data = array();
	$query = "	SELECT distinct cc.* 
				FROM cpa_bills cb
				JOIN cpa_bills_registrations cbr ON cbr.billid = cb.id
				JOIN cpa_registrations cr ON cr.id = cbr.registrationid
				JOIN cpa_members cm ON cm.id = cr.memberid
				JOIN cpa_members_contacts cmc ON cmc.memberid = cm.id
				JOIN cpa_contacts cc ON cc.id = cmc.contactid
				WHERE cb.id = $billid";
	$result = $mysqli->query( $query );
	while ($row = $result->fetch_assoc()) {
		$data['data'][] = $row;
	}
	$data['success'] = true;
	return $data;
};

/**
 * This function gets the details of one bill from database
 */

function getBillDetailsExt($mysqli, $id, $language){
	try{
		$data = getBillInt($mysqli, $id, $language);
		$data['contacts'] = getAllBillContacts($mysqli, $id);
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

function invalidRequest(){
	$data = array();
	$data['success'] = false;
	$data['message'] = "Invalid request.";
	echo json_encode($data);
	exit;
};

?>
