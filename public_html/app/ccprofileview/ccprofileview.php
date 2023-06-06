<?php
/*
Author : Eric Lamoureux
*/
require_once('../../../private/'. $_SERVER['HTTP_HOST'].'/include/config.php');
require_once('../../include/nocache.php');

if (isset($_POST['type']) && !empty(isset($_POST['type']))) {
	$type = $_POST['type'];

	switch ($type) {
		case "getProfileDetails":
			getProfileDetails($mysqli, $_POST['userid']);
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
 * This function gets the details of the profile.
 * Info can come from cpa_contacts or cpa_members, in this order.
 * @throws Exception
 */
function getProfileDetails($mysqli, $userid) {
	try{
		$data = array();

		$query = "SELECT cc.*, cu.preferedlanguage, cu.userid, cu.userid newuserid
							FROM cpa_contacts cc
		 					JOIN cpa_users cu ON cu.contactid = cc.id
							WHERE cu.userid = '$userid'";
		$result = $mysqli->query($query);
		$row = $result->fetch_assoc();
		$data['data']['contact'] = $row;
		$data['success'] = true;
		echo json_encode($data);
		exit;
	}catch (Exception $e) {
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
function saveProfileDetails($mysqli, $currentUser, $newuserid = null) {
	try{
		$data = array();
		$id = 								$mysqli->real_escape_string(isset($currentUser['contact']['id'])								? $currentUser['contact']['id'] : '');
		$firstname = 					$mysqli->real_escape_string(isset($currentUser['contact']['firstname'])					? $currentUser['contact']['firstname'] : '');
		$lastname = 					$mysqli->real_escape_string(isset($currentUser['contact']['lastname'])					? $currentUser['contact']['lastname'] : '');
		$homephone = 					$mysqli->real_escape_string(isset($currentUser['contact']['homephone'])					? $currentUser['contact']['homephone'] : '');
		$cellphone = 					$mysqli->real_escape_string(isset($currentUser['contact']['cellphone'])					? $currentUser['contact']['cellphone'] : '');
		$officephone = 				$mysqli->real_escape_string(isset($currentUser['contact']['officephone'])				? $currentUser['contact']['officephone'] : '');
		$officeext = 					$mysqli->real_escape_string(isset($currentUser['contact']['officeext'])					? $currentUser['contact']['officeext'] : '');
		$email = 							$mysqli->real_escape_string(isset($currentUser['contact']['email'])							? $currentUser['contact']['email'] : '');
		$userid = 						$mysqli->real_escape_string(isset($currentUser['contact']['userid'])						?	$currentUser['contact']['userid'] : '');
		$preferedlanguage = 	$mysqli->real_escape_string(isset($currentUser['contact']['preferedlanguage'])	? $currentUser['contact']['preferedlanguage'] : '');
		$newuserid = 					$mysqli->real_escape_string(isset($currentUser['contact']['newuserid'])					? $currentUser['contact']['newuserid'] : '');

		if ($newuserid != $userid) {
			$query = "SELECT count(*) cnt
								FROM cpa_users
								WHERE userid = '$newuserid'";
			$result = $mysqli->query($query);
			$row = $result->fetch_assoc();
			if ($row['cnt'] != 0) {
				// User id already in use
				$data['success'] = false;
				$data['errno'] = 96;
				echo json_encode($data);
				exit;
			}
		}
		// Update user
		$query = "update cpa_users
							set fullname = concat('$firstname', ' ', '$lastname'),	preferedlanguage = '$preferedlanguage',	email = '$email', userid = '$newuserid'
							where userid = '$userid'";
		if ($mysqli->query($query)) {
		} else {
			throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
		}
		// Update contact
		$query = "update cpa_contacts
							set firstname = '$firstname', lastname = '$lastname', homephone = '$homephone', cellphone = '$cellphone', officephone = '$officephone',
							    officeext = '$officeext', email = '$email'
							where id = '$id'";
		if ($mysqli->query($query)) {
		} else {
			throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
		}
		$data['success'] = true;
		echo json_encode($data);
		exit;
	}catch (Exception $e) {
		$data = array();
		$data['success'] = false;
		$data['message'] = $e->getMessage();
		echo json_encode($data);
		exit;
	}
};



function invalidRequest() {
	$data = array();
	$data['success'] = false;
	$data['message'] = "Invalid request.";
	echo json_encode($data);
	exit;
};

?>
