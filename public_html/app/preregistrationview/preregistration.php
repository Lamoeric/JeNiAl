<?php
/*
Author : Eric Lamoureux
*/
require_once('../../../private/'. $_SERVER['HTTP_HOST'].'/include/config.php');
require_once('../../include/nocache.php');
require_once('../../include/invalidrequest.php');

if (isset($_POST['type']) && !empty(isset($_POST['type']))) {
	$type = $_POST['type'];

	switch ($type) {
		case "copyPreRegistration":
			copyPreRegistration($mysqli, $_POST['preregistration']);
			break;
		case "markPreRegistration":
			markPreRegistration($mysqli, $_POST['id']);
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
		$id = isset($preregistration['id']) ? $mysqli->real_escape_string((int)$preregistration['id']) : 0;

		if ($id != 0) {
			$query = "DELETE FROM cpa_pre_members WHERE id in (select prememberid from cpa_pre_members_contacts where precontactid = $id)";
			if ($mysqli->query($query)) {
				$query = "DELETE FROM cpa_pre_members_contacts where precontactid = $id";
				if ($mysqli->query($query)) {
					$query = "DELETE FROM cpa_pre_contacts where id = $id";
					if ($mysqli->query($query)) {
						$data['success'] = true;
						$data['message'] = 'Pre-registration deleted successfully.';
						echo json_encode($data);
						exit;
					} else {
						throw new Exception('deletePreRegistration - 1 - ' . $mysqli->sqlstate.' - '. $mysqli->error);
					}
				} else {
					throw new Exception('deletePreRegistration - 2 - ' . $mysqli->sqlstate.' - '. $mysqli->error);
				}
			} else {
				throw new Exception('deletePreRegistration - 3 - ' . $mysqli->sqlstate.' - '. $mysqli->error);
			}
		} else {
			throw new Exception('deletePreRegistration - 4 - ' . $mysqli->sqlstate.' - '. $mysqli->error);
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
 * 
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
 * This function gets the list of all possible match for a member
 * 
 */
function getPossibleMemberList($mysqli, $firstname, $lastname, $skatecanadano) {
	$data = array();
	$data['data'] = array();
	$query = "	SELECT DISTINCT id, firstname, lastname, birthday, skatecanadano, concat(firstname, ' ', lastname, ' (', birthday, '; ', skatecanadano, ')') as text 
				FROM (
					SELECT id, firstname, lastname, birthday, skatecanadano, 1 
					FROM cpa_members
					WHERE lastname = '$lastname' AND firstname = '$firstname' AND skatecanadano = '$skatecanadano'
					UNION
					SELECT id, firstname, lastname, birthday, skatecanadano, 2 
					FROM cpa_members
					WHERE lastname = '$lastname' AND firstname = '$firstname'
					UNION
					SELECT id, firstname, lastname, birthday, skatecanadano, 3 
					FROM cpa_members
					WHERE skatecanadano = '$skatecanadano'
					ORDER BY 6
				) a";
	$result = $mysqli->query($query);
	while ($row = $result->fetch_assoc()) {
		$row['id'] = (int)$row['id'];
		$data['data'][] = $row;
	}
	$data['success'] = true;
	return $data;
}

/**
 * This function gets the details of all members for a preregistration from database
 * 
 */
function getPreRegistrationMembers($mysqli, $preregistrationid, $language) {
	$data = array();
	$data['data'] = array();
	$query = "	SELECT cpm.*, 0 selected, 1 tobecopied
				FROM cpa_pre_members cpm
				JOIN  cpa_pre_members_contacts cpmc ON cpmc.prememberid = cpm.id 
				WHERE cpmc.precontactid = $preregistrationid";
	$result = $mysqli->query($query);
	while ($row = $result->fetch_assoc()) {
		$row['id'] = (int)$row['id'];
		$firstname = $mysqli->real_escape_string($row['firstname']);
		$lastname = $mysqli->real_escape_string($row['lastname']);
		$skatecanadano = $mysqli->real_escape_string($row['skatecanadano']);
		$row['possiblemembers'] = getPossibleMemberList($mysqli, $firstname, $lastname, $skatecanadano)['data'];

		$query = "SELECT count(*) cnt FROM cpa_members WHERE (lastname = '$lastname' AND firstname = '$firstname') OR skatecanadano = '$skatecanadano'";
		$resultcount = $mysqli->query($query);
		$rowcount = $resultcount->fetch_assoc();
		$row['countmembername'] = $rowcount['cnt'];
		
		$data['data'][] = $row;
	}
	$data['success'] = true;
	return $data;
	exit;
};


/**
 * This function gets the list of all possible match for a contact
 * 
 */
function getPossibleContactList($mysqli, $firstname, $lastname, $email) {
	$data = array();
	$data['data'] = array();
	$query = "	SELECT DISTINCT id, firstname, lastname, email, concat(firstname, ' ', lastname, ' (', email, ')') as text
				FROM (
					SELECT id, firstname, lastname, email, 1 
					FROM cpa_contacts
					WHERE lastname = '$lastname' AND firstname = '$firstname'
					UNION
					SELECT id, firstname, lastname, email, 2 
					FROM cpa_contacts
					WHERE email = '$email'
					UNION
					SELECT id, firstname, lastname, email, 3 
					FROM cpa_contacts
					WHERE id = (SELECT contactid FROM cpa_users WHERE email = '$email')
					ORDER BY 5
				) a";
	$result = $mysqli->query($query);
	while ($row = $result->fetch_assoc()) {
		$row['id'] = (int)$row['id'];
		$data['data'][] = $row;
	}
	$data['success'] = true;
	return $data;
}

/**
 * This function gets the details of one pre-registration from database
 * 
 */
function getPreRegistrationDetails($mysqli, $id, $language) {
	try {
		$query = "	SELECT 	cpc.id, cpc.firstname, cpc.lastname, cpc.homephone, cpc.cellphone, cpc.email, cpc.contacttype,
							cpc.firstname2, cpc.lastname2, cpc.homephone2, cpc.cellphone2, cpc.email2, cpc.contacttype2, 
							cpc.usesecondcontact, cpc.creationdate, cpc.treated, 1 tobecopied,
							if (cpc.usesecondcontact=1, 1, 0) tobecopied2
					FROM cpa_pre_contacts cpc
					WHERE cpc.id = $id";
		$result = $mysqli->query($query);
		$data = array();
		while ($row = $result->fetch_assoc()) {
			$row['id'] = (int)$row['id'];
			$row['treated'] = (int)$row['treated'];
			$row['usesecondcontact'] = (int)$row['usesecondcontact'];
			$email = $row['email'];
			$firstname = $mysqli->real_escape_string($row['firstname']);
			$lastname = $mysqli->real_escape_string($row['lastname']);
			$row['members'] = getPreRegistrationMembers($mysqli, $id, $language)['data'];
			$row['possiblecontact1'] = getPossibleContactList($mysqli, $firstname, $lastname, $email)['data'];
			if ($row['usesecondcontact'] == 1) {
				$firstname = $mysqli->real_escape_string($row['firstname2']);
				$lastname = $mysqli->real_escape_string($row['lastname2']);
				$email = $row['email2'];
				$row['possiblecontact2'] = getPossibleContactList($mysqli, $firstname, $lastname, $email)['data'];
			}
			
			$email = $row['email'];
			$firstname = $mysqli->real_escape_string($row['firstname']);
			$lastname = $mysqli->real_escape_string($row['lastname']);
			
			$query = "SELECT count(*) cnt FROM cpa_users WHERE email = '$email'";
			$resultcount = $mysqli->query($query);
			$rowcount = $resultcount->fetch_assoc();
			$row['countuser'] = $rowcount['cnt'];

			$query = "SELECT count(*) cnt FROM cpa_contacts WHERE lastname = '$lastname' AND firstname = '$firstname' AND email = '$email'";
			$resultcount = $mysqli->query($query);
			$rowcount = $resultcount->fetch_assoc();
			$row['countcontact'] = $rowcount['cnt'];
			
			$query = "SELECT count(*) cnt FROM cpa_contacts WHERE lastname = '$lastname' AND firstname = '$firstname' AND email != '$email'";
			$resultcount = $mysqli->query($query);
			$rowcount = $resultcount->fetch_assoc();
			$row['countcontactname'] = $rowcount['cnt'];

			$query = "SELECT count(*) cnt FROM cpa_contacts WHERE email = '$email' AND (lastname != '$lastname' OR firstname != '$firstname')";
			$resultcount = $mysqli->query($query);
			$rowcount = $resultcount->fetch_assoc();
			$row['countemail'] = $rowcount['cnt'];

			$row['countuser2'] = 0;
			$row['countcontact2'] = 0;
			$row['countcontactname2'] = 0;
			$row['countemail2'] = 0;
			if ($row['usesecondcontact'] == 1) {
				$email = $row['email2'];
				$firstname = $mysqli->real_escape_string($row['firstname2']);
				$lastname = $mysqli->real_escape_string($row['lastname2']);

				$query = "SELECT count(*) cnt FROM cpa_users WHERE email = '$email'";
				$resultcount = $mysqli->query($query);
				$rowcount = $resultcount->fetch_assoc();
				$row['countuser2'] = $rowcount['cnt'];

				$query = "SELECT count(*) cnt FROM cpa_contacts WHERE lastname = '$lastname' AND firstname = '$firstname' AND email = '$email'";
				$resultcount = $mysqli->query($query);
				$rowcount = $resultcount->fetch_assoc();
				$row['countcontact2'] = $rowcount['cnt'];
				
				$query = "SELECT count(*) cnt FROM cpa_contacts WHERE lastname = '$lastname' AND firstname = '$firstname' AND email != '$email'";
				$resultcount = $mysqli->query($query);
				$rowcount = $resultcount->fetch_assoc();
				$row['countcontactname2'] = $rowcount['cnt'];

				$query = "SELECT count(*) cnt FROM cpa_contacts WHERE email = '$email' AND (lastname != '$lastname' OR firstname != '$firstname')";
				$resultcount = $mysqli->query($query);
				$rowcount = $resultcount->fetch_assoc();
				$row['countemail2'] = $rowcount['cnt'];
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
 * This function will handle update of a pre-member in DB
 * @throws Exception
 * 
 */
function updateEntireMembers($mysqli, $preregistration) {
	$data = array();
	$members = $preregistration['members'];

	for ($x = 0; $members && $x < count($members); $x++) {
		$id = 				$mysqli->real_escape_string(isset($members[$x]['id'])				? (int)$members[$x]['id'] : 0);
		$firstname = 		$mysqli->real_escape_string(isset($members[$x]['firstname']) 		? $members[$x]['firstname'] : '');
		$lastname = 		$mysqli->real_escape_string(isset($members[$x]['lastname']) 		? $members[$x]['lastname'] : '');
		$gender = 			$mysqli->real_escape_string(isset($members[$x]['gender']) 			? $members[$x]['gender'] : '');
		$language = 		$mysqli->real_escape_string(isset($members[$x]['language']) 		? $members[$x]['language'] : '');
		$skatecanadano =	$mysqli->real_escape_string(isset($members[$x]['skatecanadano'])	? $members[$x]['skatecanadano'] : '');
		$birthday = 		$mysqli->real_escape_string(isset($members[$x]['birthday']) 		? $members[$x]['birthday'] : '');
		$address1 =			$mysqli->real_escape_string(isset($members[$x]['address1'])			? $members[$x]['address1'] : '');
		$town = 			$mysqli->real_escape_string(isset($members[$x]['town'])				? $members[$x]['town'] : '');
		$postalcode = 		$mysqli->real_escape_string(isset($members[$x]['postalcode'])		? $members[$x]['postalcode'] : '');

		$query = "	UPDATE cpa_pre_members 
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
 * This function will handle preregistration update functionality
 * @throws Exception
 * 
 */
function updatePreRegistration($mysqli, $precontact) {
	$data = array();
	$id = 				$mysqli->real_escape_string(isset($precontact['id'])				? (int)$precontact['id'] : 0);
	$firstname = 		$mysqli->real_escape_string(isset($precontact['firstname'])			? $precontact['firstname'] : '');
	$lastname = 		$mysqli->real_escape_string(isset($precontact['lastname'])			? $precontact['lastname'] : '');
	$homephone = 		$mysqli->real_escape_string(isset($precontact['homephone'])			? $precontact['homephone'] : '');
	$cellphone = 		$mysqli->real_escape_string(isset($precontact['cellphone'])			? $precontact['cellphone'] : '');
	$email = 			$mysqli->real_escape_string(isset($precontact['email'])				? $precontact['email'] : '');
	$contacttype = 		$mysqli->real_escape_string(isset($precontact['contacttype'])		? $precontact['contacttype'] : '');
	$firstname2 = 		$mysqli->real_escape_string(isset($precontact['firstname2'])		? $precontact['firstname2'] : '');
	$lastname2 = 		$mysqli->real_escape_string(isset($precontact['lastname2'])			? $precontact['lastname2'] : '');
	$homephone2 = 		$mysqli->real_escape_string(isset($precontact['homephone2'])		? $precontact['homephone2'] : '');
	$cellphone2 = 		$mysqli->real_escape_string(isset($precontact['cellphone2'])		? $precontact['cellphone2'] : '');
	$email2 = 			$mysqli->real_escape_string(isset($precontact['email2'])			? $precontact['email2'] : '');
	$contacttype2 = 	$mysqli->real_escape_string(isset($precontact['contacttype2'])		? $precontact['contacttype2'] : '');
	$usesecondcontact =	$mysqli->real_escape_string(isset($precontact['usesecondcontact'])	? (int)$precontact['usesecondcontact'] : '');

	$query = "	UPDATE cpa_pre_contacts 
				SET firstname = '$firstname', lastname = '$lastname', homephone = '$homephone', cellphone = '$cellphone', email = '$email', contacttype = '$contacttype',
					firstname2 = '$firstname2', lastname2 = '$lastname2', homephone2 = '$homephone2', cellphone2 = '$cellphone2', email2 = '$email2', contacttype2 = '$contacttype2'
				WHERE id = $id";
	if (!$mysqli->query($query)) {
		throw new Exception('updatePreRegistration - ' . $mysqli->sqlstate.' - '. $mysqli->error);
	}
	return $data;
	exit;
};

/**
 * This function will handle preregistration update functionality
 * 
 */
function updateEntirePreRegistration($mysqli, $preregistration) {
	try {
		$data = array();
		$id = isset($preregistration['id']) ? $mysqli->real_escape_string((int)$preregistration['id']) : 0;

		$data['successpreregistration'] = updatePreRegistration($mysqli, $preregistration);
		if (isset($preregistration['members'])) {
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
 * This function will handle copying the contact. It will create a new contact if needed, or update an existing one.
 * @throws Exception
 * 
 */
function copyContact($mysqli, $precontact) {
	$data = array();
	$data['contactid1'] = null;
	$data['contactid2'] = null;
	$usesecondcontact =	isset($precontact['usesecondcontact'])	? $mysqli->real_escape_string((int)$precontact['usesecondcontact']) : 0;
	$tobecopied = 		isset($precontact['tobecopied'])		? $mysqli->real_escape_string((int)$precontact['tobecopied']) : 0;

	if ($tobecopied == 1) {
		$firstname =	isset($precontact['firstname'])	? $mysqli->real_escape_string($precontact['firstname']) : '';
		$lastname =		isset($precontact['lastname'])	? $mysqli->real_escape_string($precontact['lastname']) : '';
		$homephone =	isset($precontact['homephone'])	? $mysqli->real_escape_string($precontact['homephone']) : '';
		$cellphone =	isset($precontact['cellphone'])	? $mysqli->real_escape_string($precontact['cellphone']) : '';
		$email =		isset($precontact['email'])		? $mysqli->real_escape_string($precontact['email']) : '';
		$query = "	INSERT INTO cpa_contacts(firstname, lastname, homephone, cellphone, email, officephone, officeext)
					VALUE('$firstname', '$lastname', '$homephone', '$cellphone', '$email', '', '')";
		if ($mysqli->query($query)) {
			$data['contactid1'] = (int) $mysqli->insert_id;
		} else {
			throw new Exception('copyContact - insert contact1 - ' . $mysqli->sqlstate.' - '. $mysqli->error);
		}
	} else {
		$contactid1 = 	isset($precontact['contact1']['id'])	? $mysqli->real_escape_string((int)$precontact['contact1']['id']) : 0;
		$firstname = 	isset($precontact['firstname'])			? $mysqli->real_escape_string($precontact['firstname']) : null;
		$lastname = 	isset($precontact['lastname'])			? $mysqli->real_escape_string($precontact['lastname']) : null;
		$homephone = 	isset($precontact['homephone'])			? $mysqli->real_escape_string($precontact['homephone']) : null;
		$cellphone = 	isset($precontact['cellphone'])			? $mysqli->real_escape_string($precontact['cellphone']) : null;
		$email =		isset($precontact['email'])				? $mysqli->real_escape_string($precontact['email']) : null;
		if ($contactid1 != 0) {
			$query = "	UPDATE cpa_contacts SET ";
			$setclause = "officephone = officephone";
			$setclause .= $firstname ? ",firstname = '$firstname'" : ""; 
			$setclause .= $lastname ? ",lastname = '$lastname'" : ""; 
			$setclause .= $homephone ? ",homephone = '$homephone'" : ""; 
			$setclause .= $cellphone ? ",cellphone = '$cellphone'" : ""; 
			$setclause .= $email ? ",email = '$email'" : ""; 
			$query .= $setclause . " WHERE id = $contactid1";
			if (!$mysqli->query($query)) {
				throw new Exception('copyContact - update contact 1 - ' . $mysqli->sqlstate.' - '. $mysqli->error);
			}
			// Check if contact has a connected user, if so, update the user
			$query = "SELECT userid FROM cpa_users WHERE contactid = $contactid1";
			$result = $mysqli->query($query);
			$row = $result->fetch_assoc();
			if (isset($row['userid'])) {
				// Contact has a connected user
				$query = "	UPDATE cpa_users SET ";
				$setclause = "contactid = contactid";
				//  check if userid has a @ in it, if so, update it with the new email address
				if (strpos($row['userid'],'@')) {
					$setclause .= $email ? ",userid = '$email'" : ""; 
				}
				$setclause .= $firstname != null && $lastname != null ? ",fullname = concat('$firstname', ' ', '$lastname')" : ""; 
				$setclause .= $email ? ",email = '$email'" : ""; 
				$query .= $setclause . " WHERE contactid = $contactid1";
				if (!$mysqli->query($query)) {
					throw new Exception('copyContact - update user 1 - ' . $mysqli->sqlstate.' - '. $mysqli->error);
				}
			}
		}
	}

	if ($usesecondcontact == 1) {
		$tobecopied2 = 	isset($precontact['tobecopied2'])	? $mysqli->real_escape_string((int)$precontact['tobecopied2']) : 0;
		if ($tobecopied2 == 1) {
			$firstname =	isset($precontact['firstname2'])	? $mysqli->real_escape_string($precontact['firstname2']) : '';
			$lastname =		isset($precontact['lastname2'])		? $mysqli->real_escape_string($precontact['lastname2']) : '';
			$homephone =	isset($precontact['homephone2'])	? $mysqli->real_escape_string($precontact['homephone2']) : '';
			$cellphone =	isset($precontact['cellphone2'])	? $mysqli->real_escape_string($precontact['cellphone2']) : '';
			$email =		isset($precontact['email2'])		? $mysqli->real_escape_string($precontact['email2']) : '';
			$query = "	INSERT INTO cpa_contacts(firstname, lastname, homephone, cellphone, email, officephone, officeext)
						VALUE('$firstname', '$lastname', '$homephone', '$cellphone', '$email', '', '')";
			if ($mysqli->query($query)) {
				$data['contactid2'] = (int) $mysqli->insert_id;
			} else {
				throw new Exception('copyContact - ' . $mysqli->sqlstate.' - '. $mysqli->error);
			}
		} else {
			$contactid2 = 	isset($precontact['contact2']['id'])	? $mysqli->real_escape_string((int)$precontact['contact2']['id']) : 0;
			$firstname =	isset($precontact['firstname2'])		? $mysqli->real_escape_string($precontact['firstname2']) : null;
			$lastname =		isset($precontact['lastname2'])			? $mysqli->real_escape_string($precontact['lastname2']) : null;
			$homephone =	isset($precontact['homephone2'])		? $mysqli->real_escape_string($precontact['homephone2']) : null;
			$cellphone =	isset($precontact['cellphone2'])		? $mysqli->real_escape_string($precontact['cellphone2']) : null;
			$email =		isset($precontact['email2'])			? $mysqli->real_escape_string($precontact['email2']) : null;
			if ($contactid2 != 0) {
				$query = "	UPDATE cpa_contacts SET ";
				$setclause = "officephone = officephone";
				$setclause .= $firstname ? ",firstname = '$firstname'" : ""; 
				$setclause .= $lastname ? ",lastname = '$lastname'" : ""; 
				$setclause .= $homephone ? ",homephone = '$homephone'" : ""; 
				$setclause .= $cellphone ? ",cellphone = '$cellphone'" : ""; 
				$setclause .= $email ? ",email = '$email'" : ""; 
				$query .= $setclause . " WHERE id = $contactid2";
				if (!$mysqli->query($query)) {
					throw new Exception('copyContact - update contact 2 - ' . $mysqli->sqlstate.' - '. $mysqli->error);
				}
				// Check if contact has a connected user, if so, update the user
				$query = "SELECT userid FROM cpa_users WHERE contactid = $contactid2";
				$result = $mysqli->query($query);
				$row = $result->fetch_assoc();
				if (isset($row['userid'])) {
					// Contact has a connected user
					$query = "	UPDATE cpa_users SET ";
					$setclause = "contactid = contactid";
					//  check if userid has a @ in it, if so, update it with the new email address
					if (strpos($row['userid'],'@')) {
						$setclause .= $email ? ",userid = '$email'" : ""; 
					}
					$setclause .= $firstname != null && $lastname != null ? ",fullname = concat('$firstname', ' ', '$lastname')" : ""; 
					$setclause .= $email ? ",email = '$email'" : ""; 
					$query .= $setclause . " WHERE contactid = $contactid2";
					if (!$mysqli->query($query)) {
						throw new Exception('copyContact - update user 2 - ' . $mysqli->sqlstate.' - '. $mysqli->error);
					}
				}
			}
		}
	}
	return $data;
	exit;
};

/**
 * This function will handle connecting a member to a contact
 * @throws Exception
 * 
 */
function connectMemberToContact($mysqli, $memberid, $contactid, $contacttype) {
	if ($memberid != null && $contactid != null && $contacttype != null) {
		$query = "	SELECT count(*) cnt 
					FROM cpa_members_contacts 
					WHERE memberid = '$memberid' 
					AND contactid = '$contactid'";
		$resultcount = $mysqli->query($query);
		$rowcount = $resultcount->fetch_assoc();
		if ($rowcount['cnt'] != 0) {
			// Relation already exists
			// Should we update the contacttype?
		} else {
			// Relation doesn't exist
			$query = "	INSERT INTO cpa_members_contacts (memberid, contactid, contacttype)
						VALUES ($memberid, $contactid, '$contacttype')";
			if (!$mysqli->query($query)) {
				throw new Exception('connectMemberToContact - ' . $mysqli->sqlstate.' - '. $mysqli->error);
			}
		}
	}
}

/**
 * This function will handle updating one member
 * @throws Exception
 * 
 */
function updateMember($mysqli, $id, $member, $email, $homephone, $cellphone) {
	$data = array();

	$firstname = 		isset($member['firstname']) 	? $mysqli->real_escape_string($member['firstname']) : null;
	$lastname = 		isset($member['lastname']) 		? $mysqli->real_escape_string($member['lastname']) : null;
	$gender = 			isset($member['gender']) 		? $mysqli->real_escape_string($member['gender']) : null;
	$language = 		isset($member['language']) 		? $mysqli->real_escape_string($member['language']) : null;
	$skatecanadano =	isset($member['skatecanadano'])	? $mysqli->real_escape_string($member['skatecanadano']) : null;
	$birthday = 		isset($member['birthday']) 		? $mysqli->real_escape_string($member['birthday']) : null;
	$address1 = 		isset($member['address1'])		? $mysqli->real_escape_string($member['address1']) : null;
	$town = 			isset($member['town'])			? $mysqli->real_escape_string($member['town']) : null;
	$province = 		isset($member['province'])		? $mysqli->real_escape_string($member['province']) : null;
	$country = 			isset($member['country'])		? $mysqli->real_escape_string($member['country']) : null;
	$postalcode =		isset($member['postalcode'])	? $mysqli->real_escape_string($member['postalcode']) : null;

	$query = "	UPDATE cpa_members SET ";
	$setclause = "initial = initial";
	$setclause .= $firstname ? ",firstname = '$firstname'" : ""; 
	$setclause .= $lastname ? ",lastname = '$lastname'" : ""; 
	$setclause .= $gender ? ",gender = '$gender'" : ""; 
	$setclause .= $language ? ",language = '$language'" : ""; 
	$setclause .= $skatecanadano ? ",skatecanadano = '$skatecanadano'" : ""; 
	$setclause .= $birthday ? ",birthday = '$birthday'" : ""; 
	$setclause .= $address1 ? ",address1 = '$address1'" : ""; 
	$setclause .= $town ? ",town = '$town'" : ""; 
	$setclause .= $province ? ",province = '$province'" : ""; 
	$setclause .= $country ? ",country = '$country'" : ""; 
	$setclause .= $postalcode ? ",postalcode = '$postalcode'" : ""; 
	$setclause .= $homephone ? ",homephone = '$homephone'" : ""; 
	$setclause .= $cellphone ? ",cellphone = '$cellphone'" : ""; 
	$setclause .= $email ? ",email = '$email'" : ""; 
	$query .= $setclause . " WHERE id = $id";
	if (!$mysqli->query($query)) {
		throw new Exception('updateMember - ' . $mysqli->sqlstate.' - '. $mysqli->error);
	}
}

/**
 * This function will handle copying the members
 * @throws Exception
 * 
 */
function copyMembers($mysqli, $preregistration, $contactid, $contactid2) {
	$data = array();
	$members = $preregistration['members'];
	$email = 		isset($preregistration['email'])		? $mysqli->real_escape_string($preregistration['email']) : '';
	$homephone = 	isset($preregistration['homephone'])	? $mysqli->real_escape_string($preregistration['homephone']) : '';
	$cellphone = 	isset($preregistration['cellphone'])	? $mysqli->real_escape_string($preregistration['cellphone']) : '';
	$contacttype = 	isset($preregistration['contacttype']) 	? $mysqli->real_escape_string($preregistration['contacttype']) : '';
	$contacttype2 =	isset($preregistration['contacttype2'])	? $mysqli->real_escape_string($preregistration['contacttype2']) : '';

	for ($x = 0; $x < count($members); $x++) {
		$tobecopied = 		isset($members[$x]['tobecopied'])		? $mysqli->real_escape_string((int)$members[$x]['tobecopied']) : 0;
		$memberid = 		isset($members[$x]['member']['id']) 	? $mysqli->real_escape_string((int)$members[$x]['member']['id']) : 0;
		$firstname = 		isset($members[$x]['firstname']) 		? $mysqli->real_escape_string($members[$x]['firstname']) : '';
		$lastname = 		isset($members[$x]['lastname']) 		? $mysqli->real_escape_string($members[$x]['lastname']) : '';
		$gender = 			isset($members[$x]['gender']) 			? $mysqli->real_escape_string($members[$x]['gender']) : '';
		$language = 		isset($members[$x]['language']) 		? $mysqli->real_escape_string($members[$x]['language']) : '';
		$skatecanadano =	isset($members[$x]['skatecanadano'])	? $mysqli->real_escape_string($members[$x]['skatecanadano']) : '';
		$birthday = 		isset($members[$x]['birthday']) 		? $mysqli->real_escape_string($members[$x]['birthday']) : '';
		$address1 = 		isset($members[$x]['address1'])			? $mysqli->real_escape_string($members[$x]['address1']) : '';
		$town = 			isset($members[$x]['town'])				? $mysqli->real_escape_string($members[$x]['town']) : '';
		$province = 		isset($members[$x]['province'])			? $mysqli->real_escape_string($members[$x]['province']) : '';
		$country = 			isset($members[$x]['country'])			? $mysqli->real_escape_string($members[$x]['country']) : '';
		$postalcode =		isset($members[$x]['postalcode'])		? $mysqli->real_escape_string($members[$x]['postalcode']) : '';

		if ($tobecopied == 1) {
			$query = "	INSERT INTO cpa_members (firstname, lastname, gender, language, skatecanadano, birthday, address1, town, province, country, postalcode, email, homephone, cellphone)
						VALUES ('$firstname', '$lastname', '$gender', '$language', '$skatecanadano', '$birthday', '$address1', '$town', '$province', '$country', '$postalcode', '$email', '$homephone', '$cellphone')";
			if ($mysqli->query($query)) {
				$memberid = (int) $mysqli->insert_id;
				connectMemberToContact($mysqli, $memberid, $contactid, $contacttype);
				connectMemberToContact($mysqli, $memberid, $contactid2, $contacttype2);
			} else {
				throw new Exception('copyMembers - ' . $mysqli->sqlstate.' - '. $mysqli->error);
			}
		} else if ($tobecopied == 2) {
			// Need to update member in db
			updateMember($mysqli, $memberid, $members[$x], $email, $homephone, $cellphone);
			// Need to reconnect existing member with the contacts
			connectMemberToContact($mysqli, $memberid, $contactid, $contacttype);
			connectMemberToContact($mysqli, $memberid, $contactid2, $contacttype2);
		}
	}
	return $data;
	exit;
};

/**
 * This function will handle the marking of the pre-registration as treated 
 * This is the internal version
 * 
 */
function markPreRegistrationInt($mysqli, $id) {
	$data = array();

	if ($id != 0) {
		$query = "UPDATE cpa_pre_contacts SET treated = 1 WHERE id = $id";
		if (!$mysqli->query($query)) {
			throw new Exception('markPreRegistrationInt - ' . $mysqli->sqlstate.' - '. $mysqli->error);
		}
		$mysqli->close();

		$data['success'] = true;
		$data['message'] = 'Pre-registration marked successfully.';
		return $data;
	} else {
		throw new Exception('markPreRegistrationInt - invalid pre-registration id');
	}
	exit;
}

/**
 * This function will handle copying the pre-registration
 * 
 */
function copyPreRegistration($mysqli, $preregistration) {
	try {
		$data = array();
		$contact1 = null;
		$contact2 = null;
		$id = isset($preregistration['id']) ? $mysqli->real_escape_string((int)$preregistration['id']) : 0;
		if ($id != 0) {
			$contacts = copyContact($mysqli, $preregistration);
			// Analyse the return value and try to figure out the new contact id
			if (isset($contacts['contactid1']) && $contacts['contactid1'] != null) {
				// Contact 1 was copied from pre-registration, use this value
				$contact1 = $contacts['contactid1'];
			} else if (isset($preregistration['contact1']) && $preregistration['contact1'] != null) {
				// Contact 1 was not copied, already exists and must be connected to
				$contact1 = $preregistration['contact1']['id'];
			}
			if (isset($contacts['contactid2']) && $contacts['contactid2'] != null) {
				// Contact 2 was copied from pre-registration, use this value
				$contact2 = $contacts['contactid2'];
			} else if (isset($preregistration['contact2']) && $preregistration['contact2'] != null) {
				// Contact 2 was not copied, already exists and must be connected to
				$contact2 = $preregistration['contact2']['id'];
			}
			if (isset($preregistration['members'])) {
				$data['successmembers'] = copyMembers($mysqli, $preregistration, $contact1, $contact2);
			}
			// Mark pre-registration has treated
			$data['mark'] = markPreRegistrationInt($mysqli, $id);
		} else {
			throw new Exception('copyPreRegistration - invalid pre-registration id');
		}
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
 * This function will handle the marking of the pre-registration as treated 
 * This is the internal version
 * 
 */
function markPreRegistration($mysqli, $id) {
	try {
		$data = array();
		$data = markPreRegistrationInt($mysqli, $id);
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

?>
