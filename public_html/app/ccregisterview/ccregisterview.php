<?php
/*
Author : Eric Lamoureux
*/
require_once('../../../private/'. $_SERVER['HTTP_HOST'].'/include/config.php');
require_once('../../include/nocache.php');

if( isset($_POST['type']) && !empty( isset($_POST['type']) ) ){
	$type = $_POST['type'];

	switch ($type) {
		case "validateEmail":
			validateEmail($mysqli, $_POST['email']);
			break;
		case "getFuturUserInfo":
			getFuturUserInfo($mysqli, $_POST['email']);
			break;
		case "createAccount":
			createAccount($mysqli, $_POST['newaccountinfo']);
			break;
		default:
			invalidRequest();
	}
} else {
	invalidRequest();
};

/**
 * This function gets the details of the futur user.
 * Info can come from cpa_contacts or cpa_members, in this order.
 * @throws Exception
 */
function getFuturUserInfo($mysqli, $email){
	try{
		$data = array();

		// Address must an email address used by a contact
		$query = "SELECT count(*) cnt FROM cpa_contacts WHERE email = '$email'";
		$result = $mysqli->query( $query );
		$row = $result->fetch_assoc();
		if ($row['cnt'] != 0) {
			$query = "SELECT cc.id, cc.firstname, cc.lastname, cc.email userid, cc.email
								FROM cpa_contacts cc
								WHERE cc.email = '$email'";
			$result = $mysqli->query( $query );
			$row = $result->fetch_assoc();
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
 * This function validate email address for registration
 * The email address must not be already in use by another user
 * The email address must be a email used by a contact or a member
 * @throws Exception
 */
function validateEmail($mysqli, $email) {
	try {
		$data = array();
		$data['success'] = false;
		$data['alreadyused'] = false;

		// Address must not be used by another user
		$query = "SELECT count(*) cnt FROM cpa_users WHERE email = '$email'";
		$result = $mysqli->query( $query );
		$row = $result->fetch_assoc();
		if ($row['cnt'] == 0) {
			// Address must an email address used by a contact
			$query = "SELECT count(*) cnt FROM cpa_contacts WHERE email = '$email'";
			$result = $mysqli->query( $query );
			$row = $result->fetch_assoc();
			if ($row['cnt'] == 0) {
				// Or address must an email address used by a member
				$query = "SELECT count(*) cnt FROM cpa_members WHERE email = '$email' OR email2 = '$email'";
				$result = $mysqli->query( $query );
				$row = $result->fetch_assoc();
				if ($row['cnt'] == 0) {
					$data['success'] = false;
				} else {
					// Valid address of a member
					$data['success'] = true;
				}
			} else {
				// Valid address of a contact
				$data['success'] = true;
			}
		} else {
			// Email address already used
			$data['success'] = false;
			$data['alreadyused'] = true;
		}
		echo json_encode($data);
		exit;
	} catch (Exception $e) {
		$data = array();
		$data['success'] = false;
		$data['message'] = $e->getMessage();
		echo json_encode($data);
		exit;
	}
}

/**
 * This function creates a new account and send an validation email
 * @throws Exception
 */
function createAccount($mysqli, $newaccountinfo) {
	try {
		$data = array();
		$data['success'] = false;
		$data['alreadyused'] = false;

		$userid =						$mysqli->real_escape_string(isset($newaccountinfo['userid']) 						? $newaccountinfo['userid'] : '');
		$contactid =				$mysqli->real_escape_string(isset($newaccountinfo['contactid']) 				? $newaccountinfo['contactid'] : '');
		$fullname =					$mysqli->real_escape_string(isset($newaccountinfo['fullname']) 					? $newaccountinfo['fullname'] : '');
		$email =						$mysqli->real_escape_string(isset($newaccountinfo['email']) 						? $newaccountinfo['email'] : '');
		$preferedlanguage =	$mysqli->real_escape_string(isset($newaccountinfo['preferedlanguage']) 	? $newaccountinfo['preferedlanguage'] : '');

		$query = "SELECT count(*) cnt FROM cpa_users WHERE userid = '$userid'";
		$result = $mysqli->query( $query );
		$row = $result->fetch_assoc();
		if ($row['cnt'] == 0) {
			$query = "INSERT INTO cpa_users (userid, fullname, email, passwordexpired, active, terminationdate, preferedlanguage, contactid)
								VALUES('$userid', '$fullname', '$email', 0, 1, null, '$preferedlanguage', $contactid)";
			if ($mysqli->query($query)) {
				$data['success'] = true;
			}
		} else {
			$data['alreadyused'] = true;
		}
		echo json_encode($data);
		exit;
	} catch (Exception $e) {
		$data = array();
		$data['success'] = false;
		$data['message'] = $e->getMessage();
		echo json_encode($data);
		exit;
	}
}

function invalidRequest(){
	$data = array();
	$data['success'] = false;
	$data['message'] = "Invalid request.";
	echo json_encode($data);
	exit;
};

?>
