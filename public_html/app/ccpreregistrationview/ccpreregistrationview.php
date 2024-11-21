<?php
/*
Author : Eric Lamoureux
*/
require_once('../../../private/' . $_SERVER['HTTP_HOST'] . '/include/config.php');
require_once('../../include/nocache.php');
require_once('../../backend/invalidrequest.php'); //

if (isset($_POST['type']) && !empty(isset($_POST['type']))) {
	$type = $_POST['type'];

	switch ($type) {
		case "savePreRegistration":
			savePreRegistration($mysqli, $_POST['preregistration'], $_POST['premembers']);
			break;
		default:
			invalidRequest($type);
	}
} else {
	invalidRequest();
};

/**
 * This function will insert the preregistration in the DB
 * @throws Exception
 */
function savePreRegistration($mysqli, $preregistration, $premembers) {
	try {
		$data = array();
		$firstname = 		$mysqli->real_escape_string(isset($preregistration['contact']['firstname'])		? $preregistration['contact']['firstname'] : '');
		$lastname = 		$mysqli->real_escape_string(isset($preregistration['contact']['lastname'])		? $preregistration['contact']['lastname'] : '');
		$homephone = 		$mysqli->real_escape_string(isset($preregistration['contact']['homephone'])		? $preregistration['contact']['homephone'] : '');
		$cellphone = 		$mysqli->real_escape_string(isset($preregistration['contact']['cellphone'])		? $preregistration['contact']['cellphone'] : '');
		$email = 			$mysqli->real_escape_string(isset($preregistration['contact']['email'])			? $preregistration['contact']['email'] : '');
		$contacttype = 		$mysqli->real_escape_string(isset($preregistration['contact']['contacttype'])	? $preregistration['contact']['contacttype'] : '');
		$usesecondcontact =	$mysqli->real_escape_string(isset($preregistration['usesecondcontact'])			? (int)$preregistration['usesecondcontact'] : 0);
		$firstname2 = 		$mysqli->real_escape_string(isset($preregistration['contact2']['firstname'])	? $preregistration['contact2']['firstname'] : '');
		$lastname2 = 		$mysqli->real_escape_string(isset($preregistration['contact2']['lastname'])		? $preregistration['contact2']['lastname'] : '');
		$homephone2 = 		$mysqli->real_escape_string(isset($preregistration['contact2']['homephone'])	? $preregistration['contact2']['homephone'] : '');
		$cellphone2 = 		$mysqli->real_escape_string(isset($preregistration['contact2']['cellphone'])	? $preregistration['contact2']['cellphone'] : '');
		$email2 = 			$mysqli->real_escape_string(isset($preregistration['contact2']['email'])		? $preregistration['contact2']['email'] : '');
		$contacttype2 = 	$mysqli->real_escape_string(isset($preregistration['contact2']['contacttype'])	? $preregistration['contact2']['contacttype'] : '');

		// insert pre contact 1
		$query = "insert into cpa_pre_contacts(firstname, lastname, homephone, cellphone, email, contacttype, usesecondcontact, firstname2, lastname2, homephone2, cellphone2, email2, contacttype2)
							value('$firstname', '$lastname', '$homephone', '$cellphone', '$email', '$contacttype', $usesecondcontact, '$firstname2', '$lastname2', '$homephone2', '$cellphone2', '$email2', '$contacttype2')";
		if ($mysqli->query($query)) {
			$contactid1 = (int) $mysqli->insert_id;
		} else {
			throw new Exception('savePreRegistration - ' . $mysqli->sqlstate . ' - ' . $mysqli->error);
		}

		for ($x = 0; $x < count($premembers); $x++) {
			$firstname = 			$mysqli->real_escape_string(isset($premembers[$x]['firstname']) 			? $premembers[$x]['firstname'] : '');
			$lastname = 			$mysqli->real_escape_string(isset($premembers[$x]['lastname']) 				? $premembers[$x]['lastname'] : '');
			$gender = 				$mysqli->real_escape_string(isset($premembers[$x]['gender']) 				? $premembers[$x]['gender'] : '');
			$language = 			$mysqli->real_escape_string(isset($premembers[$x]['language']) 				? $premembers[$x]['language'] : '');
			$skatecanadano =		$mysqli->real_escape_string(isset($premembers[$x]['skatecanadano']) 		? $premembers[$x]['skatecanadano'] : '');
			$birthday = 			$mysqli->real_escape_string(isset($premembers[$x]['birthday']) 				? $premembers[$x]['birthday'] : '');
			$usepreviousaddress =	$mysqli->real_escape_string(isset($premembers[$x]['usepreviousaddress']) 	? (int)$premembers[$x]['usepreviousaddress'] : '');
			if ($usepreviousaddress == null or $usepreviousaddress == 0) {
				$address1 = 	$mysqli->real_escape_string(isset($premembers[$x]['address1'])		? $premembers[$x]['address1'] : '');
				$town =			$mysqli->real_escape_string(isset($premembers[$x]['town'])			? $premembers[$x]['town'] : '');
				$postalcode =	$mysqli->real_escape_string(isset($premembers[$x]['postalcode'])	? $premembers[$x]['postalcode'] : '');
			}

			$query = "	INSERT into cpa_pre_members (firstname, lastname, gender, language, skatecanadano, birthday, address1, address2, town, postalcode)
						VALUES ('$firstname', '$lastname', '$gender', '$language', '$skatecanadano', '$birthday', '$address1', '', '$town', '$postalcode')";
			if (!$mysqli->query($query)) {
				throw new Exception('savePreRegistration - ' . $mysqli->sqlstate . ' - ' . $mysqli->error);
			}
			$memberid = (int) $mysqli->insert_id;
			$query = "INSERT into cpa_pre_members_contacts (prememberid, precontactid) VALUES ($memberid, $contactid1)";
			if (!$mysqli->query($query)) {
				throw new Exception('savePreRegistration - ' . $mysqli->sqlstate . ' - ' . $mysqli->error);
			}
		}

		// We need to send a template email to the first enail address if the template is defined in the active session
		$query = "	SELECT cs.onlinepreregistemailtpl
					FROM cpa_sessions cs
					WHERE cs.active = 1 AND cs.isonlinepreregistemail = 1";
		$result = $mysqli->query($query);
		$row = $result->fetch_assoc();
		$data['onlinepreregistemailtpl'] = isset($row['onlinepreregistemailtpl']) ? $row['onlinepreregistemailtpl'] : null;
		$data['email'] = $email;
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
