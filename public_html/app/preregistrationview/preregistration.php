<?php
/*
Author : Eric Lamoureux
*/
require_once('../../../private/'. $_SERVER['HTTP_HOST'].'/include/config.php');
require_once('../../include/nocache.php');

if (isset($_POST['type']) && !empty(isset($_POST['type']))) {
	$type = $_POST['type'];

	switch ($type) {
		case "copyPreRegistration":
			copyPreRegistration($mysqli, $_POST['preregistration']);
			break;
		case "markPreRegistration":
			markPreRegistration($mysqli, $_POST['preregistration']);
			break;
		case "updateEntirePreRegistration":
			updateEntirePreRegistration($mysqli, $_POST['preregistration']);
			break;
		case "deletePreRegistration":
			deletePreRegistration($mysqli, $_POST['preregistration']);
			break;
		case "getAllPreRegistrations":
			getAllPreRegistrations($mysqli);
			break;
		case "getPreRegistrationDetails":
			getPreRegistrationDetails($mysqli, $_POST['id'], $_POST['language']);
			break;
		default:
			invalidRequest();
	}
} else {
	invalidRequest();
};

/**
 * This function will handle pre-registration deletion
 * @throws Exception
 */
function deletePreRegistration($mysqli, $preregistration) {
	try {
		$id = 		$mysqli->real_escape_string(isset($preregistration['id']) 		? (int)$preregistration['id'] : '');

		$query = "DELETE FROM cpa_pre_members WHERE id in (select prememberid from cpa_pre_members_contacts where precontactid = $id)";
		if ($mysqli->query($query)) {
			$query = "DELETE FROM cpa_pre_members_contacts where precontactid = $id";
			if ($mysqli->query($query)) {
				$query = "DELETE FROM cpa_pre_contacts where id = $id";
				if ($mysqli->query($query)) {
					$data['success'] = true;
					$data['message'] = 'Preregistration deleted successfully.';
					echo json_encode($data);
					exit;
				} else {
					throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
				}
			} else {
				throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
			}
		} else {
			throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
		}
	} catch(Exception $e) {
		$data = array();
		$data['success'] = false;
		$data['message'] = $e->getMessage();
		echo json_encode($data);
		exit;
	}
};

/**
 * This function gets list of all pre-registrations from database
 */
function getAllPreRegistrations($mysqli) {
	try {
		$query = "SELECT id, firstname, lastname, treated FROM cpa_pre_contacts ORDER BY creationdate desc";
		$result = $mysqli->query($query);
		$data = array();
		while ($row = $result->fetch_assoc()) {
			$row['treated'] = (int)$row['treated'];
			$data['data'][] = $row;
		}
		$data['success'] = true;
		echo json_encode($data);exit;
	} catch(Exception $e) {
		$data = array();
		$data['success'] = false;
		$data['message'] = $e->getMessage();
		echo json_encode($data);
		exit;
	}
};

/**
 * This function gets the details of all ices for an preregistration from database
 */
function getPreRegistrationMembers($mysqli, $preregistrationid, $language) {
	try {
		$query = "SELECT cpm.*, 0 selected, 1 tobecopied
							FROM cpa_pre_members cpm
							join  cpa_pre_members_contacts cpmc ON cpmc.prememberid = cpm.id 
							WHERE cpmc.precontactid = $preregistrationid";
		$result = $mysqli->query($query);
		$data = array();
		$data['data'] = array();
		while ($row = $result->fetch_assoc()) {
			$row['id'] = (int)$row['id'];
			
			$firstname = $mysqli->real_escape_string($row['firstname']);
			$lastname = $mysqli->real_escape_string($row['lastname']);
			$query = "select count(*) cnt from cpa_members where lastname = '$lastname' and firstname = '$firstname'";
			$resultcount = $mysqli->query($query);
			$rowcount = $resultcount->fetch_assoc();
			$row['countmembername'] = $rowcount['cnt'];
			
			$data['data'][] = $row;
		}
		$data['success'] = true;
		return $data;
		exit;
	} catch(Exception $e) {
		$data = array();
		$data['success'] = false;
		$data['message'] = $e->getMessage();
		return $data;
		exit;
	}
};

/**
 * This function gets the details of one preregistration from database
 */
function getPreRegistrationDetails($mysqli, $id, $language) {
	try {
		$query = "select cpc.id, cpc.firstname, cpc.lastname, cpc.homephone, cpc.cellphone, cpc.email, cpc.contacttype,
										 cpc.firstname2, cpc.lastname2, cpc.homephone2, cpc.cellphone2, cpc.email2, cpc.contacttype2, 
										 cpc.usesecondcontact, cpc.creationdate, cpc.treated, 1 tobecopied,
										 if (cpc.usesecondcontact=1, 1, 0) tobecopied2
							from cpa_pre_contacts cpc
							where cpc.id = $id";
		$result = $mysqli->query($query);
		$data = array();
		while ($row = $result->fetch_assoc()) {
			$row['id'] = (int)$row['id'];
			$row['treated'] = (int)$row['treated'];
			$row['usesecondcontact'] = (int)$row['usesecondcontact'];
//			$row['tobecopied'] = 1;
			$row['members'] = getPreRegistrationMembers($mysqli, $id, $language)['data'];
			
			$email = $row['email'];
			$query = "select count(*) cnt from cpa_users where email = '$email'";
			$resultcount = $mysqli->query($query);
			$rowcount = $resultcount->fetch_assoc();
			$row['countuser'] = $rowcount['cnt'];

			$query = "select count(*) cnt from cpa_contacts where email = '$email'";
			$resultcount = $mysqli->query($query);
			$rowcount = $resultcount->fetch_assoc();
			$row['countcontact'] = $rowcount['cnt'];
			
			$firstname = $mysqli->real_escape_string($row['firstname']);
			$lastname = $mysqli->real_escape_string($row['lastname']);
			$query = "select count(*) cnt from cpa_contacts where lastname = '$lastname' and firstname = '$firstname'";
			$resultcount = $mysqli->query($query);
			$rowcount = $resultcount->fetch_assoc();
			$row['countcontactname'] = $rowcount['cnt'];

			$row['countuser2'] = 0;
			$row['countcontact2'] = 0;
			$row['countcontactname2'] = 0;
			if ($row['usesecondcontact'] == 1) {
				$email = $row['email2'];
				$query = "select count(*) cnt from cpa_users where email = '$email'";
				$resultcount = $mysqli->query($query);
				$rowcount = $resultcount->fetch_assoc();
				$row['countuser2'] = $rowcount['cnt'];

				$query = "select count(*) cnt from cpa_contacts where email = '$email'";
				$resultcount = $mysqli->query($query);
				$rowcount = $resultcount->fetch_assoc();
				$row['countcontact2'] = $rowcount['cnt'];
				
				$firstname = $mysqli->real_escape_string($row['firstname2']);
				$lastname = $mysqli->real_escape_string($row['lastname2']);
				$query = "select count(*) cnt from cpa_contacts where lastname = '$lastname' and firstname = '$firstname'";
				$resultcount = $mysqli->query($query);
				$rowcount = $resultcount->fetch_assoc();
				$row['countcontactname2'] = $rowcount['cnt'];
			}

			$data['data'][] = $row;
		}
		$data['success'] = true;
		echo json_encode($data);
		exit;
	} catch(Exception $e) {
		$data = array();
		$data['success'] = false;
		$data['message'] = $e->getMessage();
		echo json_encode($data);
		exit;
	}
};

/**
 * This function will handle insert/update/delete of a member in DB
 * @throws Exception
 */
function updateEntireMembers($mysqli, $preregistration) {
	$data = array();
	$members = 		$preregistration['members'];

	for ($x = 0; $members && $x < count($members); $x++) {
		$id = 						$mysqli->real_escape_string(isset($members[$x]['id'])							? (int)$members[$x]['id'] : 0);
		$firstname = 			$mysqli->real_escape_string(isset($members[$x]['firstname']) 			? $members[$x]['firstname'] : '');
		$lastname = 			$mysqli->real_escape_string(isset($members[$x]['lastname']) 			? $members[$x]['lastname'] : '');
		$gender = 				$mysqli->real_escape_string(isset($members[$x]['gender']) 				? $members[$x]['gender'] : '');
		$language = 			$mysqli->real_escape_string(isset($members[$x]['language']) 			? $members[$x]['language'] : '');
		$skatecanadano = 	$mysqli->real_escape_string(isset($members[$x]['skatecanadano']) 	? $members[$x]['skatecanadano'] : '');
		$birthday = 			$mysqli->real_escape_string(isset($members[$x]['birthday']) 			? $members[$x]['birthday'] : '');
		$address1 =				$mysqli->real_escape_string(isset($members[$x]['address1'])				? $members[$x]['address1'] : '');
		$town = 					$mysqli->real_escape_string(isset($members[$x]['town'])						? $members[$x]['town'] : '');
		$postalcode = 		$mysqli->real_escape_string(isset($members[$x]['postalcode'])			? $members[$x]['postalcode'] : '');

		$query = "UPDATE cpa_pre_members 
							SET firstname = '$firstname', lastname = '$lastname', gender = '$gender', language = '$language', skatecanadano = '$skatecanadano', 
									birthday = '$birthday', address1 = '$address1', town = '$town', postalcode = '$postalcode' 
							WHERE id = $id";
		if (!$mysqli->query($query)) {
			throw new Exception('updateEntireMembers - ' . $mysqli->sqlstate.' - '. $mysqli->error);
		}
	}
	$data['success'] = true;
	return $data;
};

/**
 * This function will handle preregistration add, update functionality
 * @throws Exception
 */
function updatePreRegistration($mysqli, $precontact) {
	$data = array();
	$id = 								$mysqli->real_escape_string(isset($precontact['id'])								? (int)$precontact['id'] : 0);
	$firstname = 					$mysqli->real_escape_string(isset($precontact['firstname'])					? $precontact['firstname'] : '');
	$lastname = 					$mysqli->real_escape_string(isset($precontact['lastname'])					? $precontact['lastname'] : '');
	$homephone = 					$mysqli->real_escape_string(isset($precontact['homephone'])					? $precontact['homephone'] : '');
	$cellphone = 					$mysqli->real_escape_string(isset($precontact['cellphone'])					? $precontact['cellphone'] : '');
	$email = 							$mysqli->real_escape_string(isset($precontact['email'])							? $precontact['email'] : '');
	$contacttype = 				$mysqli->real_escape_string(isset($precontact['contacttype'])				? $precontact['contacttype'] : '');
	$firstname2 = 				$mysqli->real_escape_string(isset($precontact['firstname2'])				? $precontact['firstname2'] : '');
	$lastname2 = 					$mysqli->real_escape_string(isset($precontact['lastname2'])					? $precontact['lastname2'] : '');
	$homephone2 = 				$mysqli->real_escape_string(isset($precontact['homephone2'])				? $precontact['homephone2'] : '');
	$cellphone2 = 				$mysqli->real_escape_string(isset($precontact['cellphone2'])				? $precontact['cellphone2'] : '');
	$email2 = 						$mysqli->real_escape_string(isset($precontact['email2'])						? $precontact['email2'] : '');
	$contacttype2 = 			$mysqli->real_escape_string(isset($precontact['contacttype2'])			? $precontact['contacttype2'] : '');
	$usesecondcontact = 	$mysqli->real_escape_string(isset($precontact['usesecondcontact'])	? (int)$precontact['usesecondcontact'] : '');

	$query = "UPDATE cpa_pre_contacts 
						SET firstname = '$firstname', lastname = '$lastname', homephone = '$homephone', cellphone = '$cellphone', email = '$email', contacttype = '$contacttype',
								firstname2 = '$firstname2', lastname2 = '$lastname2', homephone2 = '$homephone2', cellphone2 = '$cellphone2', email2 = '$email2', contacttype2 = '$contacttype2'
						WHERE id = $id";
	if (!$mysqli->query($query)) {
		throw new Exception('updatePreRegistration - ' . $mysqli->sqlstate.' - '. $mysqli->error);
	}
	return $data;
	exit;
};

function updateEntirePreRegistration($mysqli, $preregistration) {
	try {
		$data = array();
		$id = $mysqli->real_escape_string(isset($preregistration['id']) ? $preregistration['id'] : '');

		$data['successpreregistration'] = updatePreRegistration($mysqli, $preregistration);
		if ($mysqli->real_escape_string(isset($preregistration['members']))) {
			$data['successmembers'] = updateEntireMembers($mysqli, $preregistration);
		}
		$mysqli->close();

		$data['success'] = true;
		$data['message'] = 'Pre-registration updated successfully.';
		echo json_encode($data);
		exit;
	} catch(Exception $e) {
		$data = array();
		$data['success'] = false;
		$data['message'] = $e->getMessage();
		echo json_encode($data);
		exit;
	}
};

/**
 * This function will handle copying the contact
 * @throws Exception
 */
function copyContact($mysqli, $precontact) {
	$data = array();
	$data['contactid1'] = null;
	$data['contactid2'] = null;
	$id = 								$mysqli->real_escape_string(isset($precontact['id'])								? (int)$precontact['id'] : 0);
	$tobecopied = 				$mysqli->real_escape_string(isset($precontact['tobecopied'])				? (int)$precontact['tobecopied'] : 0);
	$firstname = 					$mysqli->real_escape_string(isset($precontact['firstname'])					? $precontact['firstname'] : '');
	$lastname = 					$mysqli->real_escape_string(isset($precontact['lastname'])					? $precontact['lastname'] : '');
	$homephone = 					$mysqli->real_escape_string(isset($precontact['homephone'])					? $precontact['homephone'] : '');
	$cellphone = 					$mysqli->real_escape_string(isset($precontact['cellphone'])					? $precontact['cellphone'] : '');
	$email = 							$mysqli->real_escape_string(isset($precontact['email'])							? $precontact['email'] : '');
	$tobecopied2 = 				$mysqli->real_escape_string(isset($precontact['tobecopied2'])				? (int)$precontact['tobecopied2'] : 0);
	$usesecondcontact = 	$mysqli->real_escape_string(isset($precontact['usesecondcontact'])	? (int)$precontact['usesecondcontact'] : 0);

	if ($tobecopied == 1) {
		$query = "insert into cpa_contacts(firstname, lastname, homephone, cellphone, email, officephone, officeext)
							value('$firstname', '$lastname', '$homephone', '$cellphone', '$email', '', '')";
		if ($mysqli->query($query)) {
			$data['contactid'] = (int) $mysqli->insert_id;
		} else {
			throw new Exception('copyContact - ' . $mysqli->sqlstate.' - '. $mysqli->error);
		}
	} else {
		$data['contactid'] = null;
	}

	if ($usesecondcontact == 1 && $tobecopied2 == 1) {
		$firstname = 					$mysqli->real_escape_string(isset($precontact['firstname2'])				? $precontact['firstname2'] : '');
		$lastname = 					$mysqli->real_escape_string(isset($precontact['lastname2'])					? $precontact['lastname2'] : '');
		$homephone = 					$mysqli->real_escape_string(isset($precontact['homephone2'])				? $precontact['homephone2'] : '');
		$cellphone = 					$mysqli->real_escape_string(isset($precontact['cellphone2'])				? $precontact['cellphone2'] : '');
		$email = 							$mysqli->real_escape_string(isset($precontact['email2'])						? $precontact['email2'] : '');

		$query = "insert into cpa_contacts(firstname, lastname, homephone, cellphone, email, officephone, officeext)
							value('$firstname', '$lastname', '$homephone', '$cellphone', '$email', '', '')";
		if ($mysqli->query($query)) {
			$data['contactid2'] = (int) $mysqli->insert_id;
		} else {
			throw new Exception('copyContact - ' . $mysqli->sqlstate.' - '. $mysqli->error);
		}
	}
	return $data;
	exit;
};

/**
 * This function will handle copying the members
 * @throws Exception
 */
function copyMembers($mysqli, $preregistration, $contactid, $contactid2) {
	$data = array();
	$members = $preregistration['members'];
	$email = 							$mysqli->real_escape_string(isset($preregistration['email'])						? $preregistration['email'] : '');
	$homephone = 					$mysqli->real_escape_string(isset($preregistration['homephone'])				? $preregistration['homephone'] : '');
	$cellphone = 					$mysqli->real_escape_string(isset($preregistration['cellphone'])				? $preregistration['cellphone'] : '');
	$contacttype = 				$mysqli->real_escape_string(isset($preregistration['contacttype']) 			? $preregistration['contacttype'] : '');
	$contacttype2 = 			$mysqli->real_escape_string(isset($preregistration['contacttype2']) 		? $preregistration['contacttype2'] : '');

	for ($x = 0; $x < count($members); $x++) {
		$tobecopied = 		$mysqli->real_escape_string(isset($members[$x]['tobecopied'])			? (int)$members[$x]['tobecopied'] : 0);
		$firstname = 			$mysqli->real_escape_string(isset($members[$x]['firstname']) 			? $members[$x]['firstname'] : '');
		$lastname = 			$mysqli->real_escape_string(isset($members[$x]['lastname']) 			? $members[$x]['lastname'] : '');
		$gender = 				$mysqli->real_escape_string(isset($members[$x]['gender']) 				? $members[$x]['gender'] : '');
		$language = 			$mysqli->real_escape_string(isset($members[$x]['language']) 			? $members[$x]['language'] : '');
		$skatecanadano = 	$mysqli->real_escape_string(isset($members[$x]['skatecanadano']) 	? $members[$x]['skatecanadano'] : '');
		$birthday = 			$mysqli->real_escape_string(isset($members[$x]['birthday']) 			? $members[$x]['birthday'] : '');
		$address1 = 			$mysqli->real_escape_string(isset($members[$x]['address1'])				? $members[$x]['address1'] : '');
		$town = 					$mysqli->real_escape_string(isset($members[$x]['town'])						? $members[$x]['town'] : '');
		$province = 			$mysqli->real_escape_string(isset($members[$x]['province'])				? $members[$x]['province'] : '');
		$country = 				$mysqli->real_escape_string(isset($members[$x]['country'])				? $members[$x]['country'] : '');
		$postalcode = 		$mysqli->real_escape_string(isset($members[$x]['postalcode'])			? $members[$x]['postalcode'] : '');

		if ($tobecopied == 1) {
			$query = "INSERT into cpa_members (firstname, lastname, gender, language, skatecanadano, birthday, address1, town, province, country, postalcode, email, homephone, cellphone)
								VALUES ('$firstname', '$lastname', '$gender', '$language', '$skatecanadano', '$birthday', '$address1', '$town', '$province', '$country', '$postalcode', '$email', '$homephone', '$cellphone')";
			if ($mysqli->query($query)) {
				$memberid = (int) $mysqli->insert_id;
				if ($contactid != null) {
					$query = "INSERT into cpa_members_contacts (memberid, contactid, contacttype)
										VALUES ($memberid, $contactid, '$contacttype')";
					if (!$mysqli->query($query)) {
						throw new Exception('copyMembers - ' . $mysqli->sqlstate.' - '. $mysqli->error);
					}
				}
				if ($contactid2 != null) {
					$query = "INSERT into cpa_members_contacts (memberid, contactid, contacttype)
										VALUES ($memberid, $contactid2, '$contacttype2')";
					if (!$mysqli->query($query)) {
						throw new Exception('copyMembers - ' . $mysqli->sqlstate.' - '. $mysqli->error);
					}
				}
			} else {
				throw new Exception('copyMembers - ' . $mysqli->sqlstate.' - '. $mysqli->error);
			}
		}
	}
	return $data;
	exit;
};

function copyPreRegistration($mysqli, $preregistration) {
	try {
		$data = array();
		$id = $mysqli->real_escape_string(isset($preregistration['id']) ? $preregistration['id'] : '');

		$return = copyContact($mysqli, $preregistration);
		if ($mysqli->real_escape_string(isset($preregistration['members']))) {
			$data['successmembers'] = copyMembers($mysqli, $preregistration, $return['contactid'], $return['contactid2']);
		}
		
		$query = "update cpa_pre_contacts set treated = 1 where id = $id";
		if (!$mysqli->query($query)) {
			throw new Exception('copyPreRegistration - ' . $mysqli->sqlstate.' - '. $mysqli->error);
		}
		$mysqli->close();

		$data['success'] = true;
		$data['message'] = 'Pre-registration updated successfully.';
		echo json_encode($data);
		exit;
	} catch(Exception $e) {
		$data = array();
		$data['success'] = false;
		$data['message'] = $e->getMessage();
		echo json_encode($data);
		exit;
	}
};

function markPreRegistration($mysqli, $preregistration) {
	try {
		$data = array();
		$id = $mysqli->real_escape_string(isset($preregistration['id']) ? $preregistration['id'] : '');

		$query = "update cpa_pre_contacts set treated = 1 where id = $id";
		if (!$mysqli->query($query)) {
			throw new Exception('markPreRegistration - ' . $mysqli->sqlstate.' - '. $mysqli->error);
		}
		$mysqli->close();

		$data['success'] = true;
		$data['message'] = 'Pre-registration updated successfully.';
		echo json_encode($data);
		exit;
	} catch(Exception $e) {
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
