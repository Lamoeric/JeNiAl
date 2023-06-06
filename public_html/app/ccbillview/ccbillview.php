<?php
/*
Author : Eric Lamoureux
*/
require_once('../../../private/'. $_SERVER['HTTP_HOST'].'/include/config.php');
require_once('../../include/nocache.php');

if( isset($_POST['type']) && !empty( isset($_POST['type']) ) ){
	$type = $_POST['type'];

	switch ($type) {
		case "getBillDetails":
			getBillDetails($mysqli, $_POST['userid'], $_POST['language']);
			break;
		// case "saveProfileDetails":
		// 	saveProfileDetails($mysqli, $_POST['currentUser']);
		// 	break;
		default:
			invalidRequest();
	}
} else {
	invalidRequest();
};

/**
 * This function gets the details of the profile.
 * Info can come from cpa_contacts or cpa_members, in this order. (**** TODO *****)
 * @throws Exception
 */
function getBillDetails($mysqli, $userid, $language){
	try{
		$data = array();

		$query = "SELECT distinct cb.billingdate, cb.*, cs.id sessionid, gettextLabel(cs.label, '$language') sessionlabel
							FROM cpa_bills cb
							JOIN cpa_bills_registrations cbr ON cbr.billid = cb.id
							JOIN cpa_registrations cr ON cr.id = cbr.registrationid
							JOIN cpa_members cm ON cm.id = cr.memberid
							JOIN cpa_members_contacts cmc ON cmc.memberid = cm.id
							JOIN cpa_sessions cs ON cs.id = cr.sessionid
							JOIN cpa_users cu ON cu.contactid = cmc.contactid
							WHERE cb.relatednewbillid is null
							AND cu.userid = '$userid'
							UNION
							SELECT distinct cb.billingdate, cb.*, null, gettextLabel(ct.label, '$language') sessionlabel
							FROM cpa_bills cb
							JOIN cpa_bills_testsessions cbt ON cbt.billid = cb.id
							JOIN cpa_newtests_sessions_periods_registrations cnspr ON cnspr.id = cbt.testssessionsid
							JOIN cpa_tests ct ON ct.id = cnspr.testid
							JOIN cpa_members cm ON cm.id = cnspr.memberid
							JOIN cpa_members_contacts cmc ON cmc.memberid = cm.id
							JOIN cpa_users cu ON cu.contactid = cmc.contactid
							WHERE cb.relatednewbillid is null
							AND cu.userid = '$userid'
							UNION
							SELECT distinct cb.billingdate, cb.*, null, gettextLabel(cs.label, '$language') sessionlabel
							FROM cpa_bills cb
							JOIN cpa_bills_registrations cbr ON cbr.billid = cb.id
							JOIN cpa_registrations cr ON cr.id = cbr.registrationid
							JOIN cpa_members cm ON cm.id = cr.memberid
							JOIN cpa_members_contacts cmc ON cmc.memberid = cm.id
							JOIN cpa_shows cs ON cs.id = cr.showid
							JOIN cpa_users cu ON cu.contactid = cmc.contactid
							WHERE cb.relatednewbillid is null
							AND cu.userid = '$userid'
							ORDER BY 1 DESC";
		$result = $mysqli->query( $query );
		while ($row = $result->fetch_assoc()) {
			$data['data'][] = $row;
		}
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
 * This function will handle insert/update/delete of a ice in DB
 * @throws Exception
 */
// function saveProfileDetails($mysqli, $currentUser){
// 	try{
// 		$data = array();
// 		$id = 								$mysqli->real_escape_string(isset( $currentUser['contact']['id'] )								? $currentUser['contact']['id'] : '');
// 		$firstname = 					$mysqli->real_escape_string(isset( $currentUser['contact']['firstname'] )					? $currentUser['contact']['firstname'] : '');
// 		$lastname = 					$mysqli->real_escape_string(isset( $currentUser['contact']['lastname'] )					? $currentUser['contact']['lastname'] : '');
// 		$homephone = 					$mysqli->real_escape_string(isset( $currentUser['contact']['homephone'] )					? $currentUser['contact']['homephone'] : '');
// 		$cellphone = 					$mysqli->real_escape_string(isset( $currentUser['contact']['cellphone'] )					? $currentUser['contact']['cellphone'] : '');
// 		$officephone = 				$mysqli->real_escape_string(isset( $currentUser['contact']['officephone'] )				? $currentUser['contact']['officephone'] : '');
// 		$officeext = 					$mysqli->real_escape_string(isset( $currentUser['contact']['officeext'] )					? $currentUser['contact']['officeext'] : '');
// 		$email = 							$mysqli->real_escape_string(isset( $currentUser['contact']['email'] )							? $currentUser['contact']['email'] : '');
// 		$userid = 						$mysqli->real_escape_string(isset( $currentUser['contact']['userid'] )						?	$currentUser['contact']['userid'] : '');
// 		$preferedlanguage = 	$mysqli->real_escape_string(isset( $currentUser['contact']['preferedlanguage'] )	? $currentUser['contact']['preferedlanguage'] : '');
//
// 		// Update user
// 		$query = "update cpa_users
// 							set fullname = concat('$firstname', ' ', '$lastname'),	preferedlanguage = '$preferedlanguage',	email = '$email'
// 							where userid = '$userid'";
// 		if( $mysqli->query( $query ) ){
// 		} else {
// 			throw new Exception( $mysqli->sqlstate.' - '. $mysqli->error );
// 		}
// 		// Update contact
// 		$query = "update cpa_contacts
// 							set firstname = '$firstname', lastname = '$lastname', homephone = '$homephone', cellphone = '$cellphone', officephone = '$officephone',
// 							    officeext = '$officeext', email = '$email'
// 							where id = '$id'";
// 		if( $mysqli->query( $query ) ){
// 		} else {
// 			throw new Exception( $mysqli->sqlstate.' - '. $mysqli->error );
// 		}
// 		$data['success'] = true;
// 		echo json_encode($data);
// 		exit;
// 	}catch (Exception $e){
// 		$data = array();
// 		$data['success'] = false;
// 		$data['message'] = $e->getMessage();
// 		echo json_encode($data);
// 		exit;
// 	}
// };



function invalidRequest(){
	$data = array();
	$data['success'] = false;
	$data['message'] = "Invalid request.";
	echo json_encode($data);
	exit;
};

?>
