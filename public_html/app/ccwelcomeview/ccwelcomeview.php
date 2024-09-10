<?php
/*
Author : Eric Lamoureux
*/
require_once('../../../private/'. $_SERVER['HTTP_HOST'].'/include/config.php');
require_once('../../include/nocache.php');

if( isset($_POST['type']) && !empty( isset($_POST['type']) ) ){
	$type = $_POST['type'];

	switch ($type) {
		case "getWelcomeDetails":
			getWelcomeDetails($mysqli, $_POST['userid'], $_POST['language']);
			break;
		case "saveProfileDetails":
			saveProfileDetails($mysqli, $_POST['currentUser']);
			break;
		default:
			invalidRequest();
	}
} else {
	invalidRequest();
};

/**
 * This function gets the registration of the skaters
 * @throws Exception
 */
function getSkaterRegistrations($mysqli, $memberid, $language){
	$data = array();
	$data['data'] = array();
	$query = "SELECT cs.id sessionid, getTextLabel(cs.label, '$language') sessionlabel, cr.id registrationid
						FROM cpa_sessions cs
						left join cpa_registrations cr ON cr.sessionid = cs.id AND cr.memberid = $memberid AND (cr.relatednewregistrationid is null OR cr.relatednewregistrationid = 0)
						WHERE (isonlineregistactive = 1 AND curdate() between cs.onlineregiststartdate AND cs.onlineregistenddate)
						ORDER BY cs.startdate";
	$result = $mysqli->query( $query );
	while ($row = $result->fetch_assoc()) {
		$data['data'][] = $row;
	}
	$data['success'] = true;
	return $data;
};

/**
 * This function gets the show registration of the skaters
 * @throws Exception
 */
function getSkaterShowRegistrations($mysqli, $memberid, $language){
	$data = array();
	$data['data'] = array();
	$query = "SELECT cs.id showid, getTextLabel(cs.label, '$language') showlabel, csr.id showregistrationid
						FROM cpa_shows cs
						left join cpa_registrations csr ON csr.showid = cs.id and csr.memberid = $memberid and (csr.relatednewregistrationid is null or csr.relatednewregistrationid = 0)
						WHERE now() between cs.onlineregiststartdate and cs.onlineregistenddate
						AND cs.active = 1
						ORDER BY cs.sessionid, cs.id";
	$result = $mysqli->query( $query );
	while ($row = $result->fetch_assoc()) {
		$data['data'][] = $row;
	}
	$data['success'] = true;
	return $data;
};

/**
 * This function gets the details of the welcome page
 * Info can come from cpa_contacts or cpa_members, in this order.
 * @throws Exception
 */
function getWelcomeDetails($mysqli, $userid, $language){
	try{
		$data = array();
		$data['data'] = array();
		$data['data']['skaters'] = array();
		// Select the skaters for the user
		$query = "select cm.id, cm.firstname, cm.lastname, cm.skatecanadano, birthday
							from cpa_members cm
							join cpa_members_contacts cmc ON cmc.memberid = cm.id
							join cpa_users cu ON cu.contactid = cmc.contactid
							where cu.userid = '$userid'
							ORDER BY birthday, firstname";
		$result = $mysqli->query( $query );
		while ($row = $result->fetch_assoc()) {
			$row['registrations'] = getSkaterRegistrations($mysqli, $row['id'], $language)['data'];
			$row['showsregistrations'] = getSkaterShowRegistrations($mysqli, $row['id'], $language)['data'];
			$data['data']['skaters'][] = $row;
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
 * This function will handle insert/update/delete of a member profile in DB
 * @throws Exception
 */
function saveProfileDetails($mysqli, $currentUser){
	try{
		$data = array();
		$id = 								$mysqli->real_escape_string(isset( $currentUser['contact']['id'] )								? $currentUser['contact']['id'] : '');
		$firstname = 					$mysqli->real_escape_string(isset( $currentUser['contact']['firstname'] )					? $currentUser['contact']['firstname'] : '');
		$lastname = 					$mysqli->real_escape_string(isset( $currentUser['contact']['lastname'] )					? $currentUser['contact']['lastname'] : '');
		$homephone = 					$mysqli->real_escape_string(isset( $currentUser['contact']['homephone'] )					? $currentUser['contact']['homephone'] : '');
		$cellphone = 					$mysqli->real_escape_string(isset( $currentUser['contact']['cellphone'] )					? $currentUser['contact']['cellphone'] : '');
		$officephone = 				$mysqli->real_escape_string(isset( $currentUser['contact']['officephone'] )				? $currentUser['contact']['officephone'] : '');
		$officeext = 					$mysqli->real_escape_string(isset( $currentUser['contact']['officeext'] )					? $currentUser['contact']['officeext'] : '');
		$email = 							$mysqli->real_escape_string(isset( $currentUser['contact']['email'] )							? $currentUser['contact']['email'] : '');
		$userid = 						$mysqli->real_escape_string(isset( $currentUser['contact']['userid'] )						?	$currentUser['contact']['userid'] : '');
		$preferedlanguage = 	$mysqli->real_escape_string(isset( $currentUser['contact']['preferedlanguage'] )	? $currentUser['contact']['preferedlanguage'] : '');

		// Update user
		$query = "update cpa_users
							set fullname = concat('$firstname', ' ', '$lastname'),	preferedlanguage = '$preferedlanguage',	email = '$email'
							where userid = '$userid'";
		if( $mysqli->query( $query ) ){
		} else {
			throw new Exception( $mysqli->sqlstate.' - '. $mysqli->error );
		}
		// Update contact
		$query = "update cpa_contacts
							set firstname = '$firstname', lastname = '$lastname', homephone = '$homephone', cellphone = '$cellphone', officephone = '$officephone',
							    officeext = '$officeext', email = '$email'
							where id = '$id'";
		if( $mysqli->query( $query ) ){
		} else {
			throw new Exception( $mysqli->sqlstate.' - '. $mysqli->error );
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



function invalidRequest(){
	$data = array();
	$data['success'] = false;
	$data['message'] = "Invalid request.";
	echo json_encode($data);
	exit;
};

?>
