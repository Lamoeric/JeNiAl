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
		case "insert_contact":
			insert_contact($mysqli);
			break;
		case "updateEntireContact":
			updateEntireContact($mysqli, $_POST['contact']);
			break;
		case "delete_contact":
			delete_contact($mysqli, $_POST['contact']);
			break;
		case "getAllContacts":
			getAllContacts($mysqli, $_POST['filter']);
			break;
		case "getContactDetails":
			getContactDetails($mysqli, $_POST['id']);
			break;
		default:
			invalidRequest();
	}
} else {
	invalidRequest();
};

/**
 * This function will handle contact add, update functionality
 * @throws Exception
 */
function insert_contact($mysqli)
{
	try {
		$data = array();
		$firstname =	$mysqli->real_escape_string(isset($_POST['contact']['firstname']) 	? $_POST['contact']['firstname'] : '');
		$lastname =		$mysqli->real_escape_string(isset($_POST['contact']['lastname'])	? $_POST['contact']['lastname'] : '');

		if ($firstname == '' || $lastname == '') {
			throw new Exception("Required fields missing, Please enter and submit");
		}

		$query = "INSERT INTO cpa_contacts (firstname, lastname, homephone, cellphone, officephone, officeext, email)
					VALUES ('$firstname', '$lastname', '', '', '', '', '')";
		if ($mysqli->query($query)) {
			$data['success'] = true;
			if (empty($id)) $data['id'] = (int) $mysqli->insert_id;
		} else {
			throw new Exception($mysqli->sqlstate . ' - ' . $mysqli->error);
		}
		$mysqli->close();
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

/**
 * This function will handle contact add, update functionality
 * @throws Exception
 */
function update_contact($mysqli, $contact)
{
	try {
		$data = array();
		$id =			$mysqli->real_escape_string(isset($contact['id'])			? (int)$contact['id'] : 0);
		$lastname =		$mysqli->real_escape_string(isset($contact['lastname'])		? $contact['lastname'] : '');
		$firstname =	$mysqli->real_escape_string(isset($contact['firstname'])	? $contact['firstname'] : '');
		$homephone =	$mysqli->real_escape_string(isset($contact['homephone'])	? $contact['homephone'] : '');
		$cellphone =	$mysqli->real_escape_string(isset($contact['cellphone'])	? $contact['cellphone'] : '');
		$officephone =	$mysqli->real_escape_string(isset($contact['officephone']) 	? $contact['officephone'] : '');
		$officeext =	$mysqli->real_escape_string(isset($contact['officeext'])	? $contact['officeext'] : '');
		$email =		$mysqli->real_escape_string(isset($contact['email'])		? $contact['email'] : '');

		if ($id == 0) {
			throw new Exception("Invalid contact id. Please enter and submit");
		}

		$query = "UPDATE cpa_contacts 
					SET lastname = '$lastname', firstname = '$firstname', homephone = '$homephone', cellphone = '$cellphone', officephone = '$officephone', 
						officeext = '$officeext', email = '$email' 
					WHERE id = $id";
		if ($mysqli->query($query)) {
			$data['success'] = true;
			$data['message'] = 'Contact updated successfully.';
		} else {
			throw new Exception($mysqli->sqlstate . ' - ' . $mysqli->error);
		}
		return $data;
		exit;
	} catch (Exception $e) {
		$data = array();
		$data['success'] = false;
		$data['message'] = $e->getMessage();
		return $data;
		exit;
	}
};

/**
 * This function will handle user deletion
 * @throws Exception
 */
function delete_contact($mysqli, $contact)
{
	try {
		$id = $mysqli->real_escape_string(isset($contact['id']) ? (int)$contact['id'] : 0);

		if ($id == 0) {
			throw new Exception("Invalid contact id. Please enter and submit");
		}
		$query = "DELETE FROM cpa_contacts WHERE id = $id";
		if ($mysqli->query($query)) {
			$data['success'] = true;
			$data['message'] = 'Contact deleted successfully.';
			echo json_encode($data);
			exit;
		} else {
			throw new Exception($mysqli->sqlstate . ' - ' . $mysqli->error);
		}
	} catch (Exception $e) {
		$data = array();
		$data['success'] = false;
		$data['message'] = $e->getMessage();
		echo json_encode($data);
		exit;
	}
};

/**
 * This function gets list of all contacts from database
 */
function getAllContacts($mysqli, $filter)
{
	try {
		$whereclause = "";
		if (!empty($filter['firstname']))			$whereclause .= " and ca.id in (select cmc.contactid from cpa_members_contacts cmc where cmc.memberid in (select id from cpa_members cm where cm.firstname like '" . $filter['firstname'] . "'))";
		if (!empty($filter['lastname']))			$whereclause .= " and ca.id in (select cmc.contactid from cpa_members_contacts cmc where cmc.memberid in (select id from cpa_members cm where cm.lastname like '" .  $filter['lastname']  . "'))";
		if (!empty($filter['qualification']))	$whereclause .= " and ca.id in (select cmc.contactid from cpa_members_contacts cmc where cmc.memberid in (select id from cpa_members cm where cm.qualifications like BINARY '%" .  $filter['qualification']  . "%'))";
		if (!empty($filter['course']))	  		$whereclause .= " and ca.id in (select cmc.contactid from cpa_members_contacts cmc where cmc.memberid in (select memberid from cpa_sessions_courses_members where sessionscoursesid = '" . $filter['course']  . "'))";
		if (!empty($filter['registration']) && $filter['registration'] == 'REGISTERED')	  	$whereclause .= " and ca.id in (select cmc.contactid from cpa_members_contacts cmc where cmc.memberid in (select memberid from cpa_sessions_courses_members where sessionscoursesid in (select id from cpa_sessions_courses where sessionid = (select id from cpa_sessions where active = '1'))))";
		if (!empty($filter['registration']) && $filter['registration'] == 'NOTREGISTERED')	$whereclause .= " and ca.id in (select cmc.contactid from cpa_members_contacts cmc where cmc.memberid not in (select memberid from cpa_sessions_courses_members where sessionscoursesid in (select id from cpa_sessions_courses where sessionid = (select id from cpa_sessions where active = '1'))))";
		if (!empty($whereclause)) $whereclause = " WHERE 1=1 " . $whereclause;
		$query = "SELECT ca.id, ca.firstname, ca.lastname 
					FROM cpa_contacts ca " . $whereclause . " ORDER BY lastname, firstname";
		$result = $mysqli->query($query);
		$data = array();
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

/**
 * This function gets the details of all members for a contact from database
 */
function getContactMembers($mysqli, $contactid = '')
{
	try {
		$query = "SELECT cmc.id, cmc.memberid, cm.firstname, cm.lastname, cmc.contacttype, cmc.incaseofemergency 
					FROM cpa_members_contacts cmc
					JOIN cpa_members cm ON cm.id = cmc.memberid
					WHERE cmc.contactid = $contactid";
		$result = $mysqli->query($query);
		$data = array();
		$data['data'] = array();
		while ($row = $result->fetch_assoc()) {
			$data['data'][] = $row;
		}
		$data['success'] = true;
		return $data;
		exit;
	} catch (Exception $e) {
		$data = array();
		$data['success'] = false;
		$data['message'] = $e->getMessage();
		return $data;
		exit;
	}
};

/**
 * This function gets the details of one contact from database
 */
function getContactDetails($mysqli, $id = '')
{
	try {
		$query = "SELECT ca.*, cu.userid,
						(SELECT count(*) FROM cpa_members_contacts cmc WHERE cmc.contactid = ca.id) +
						(SELECT count(*) FROM cpa_bills cb WHERE cb.contactid = ca.id) as isused
					FROM cpa_contacts ca
					LEFT JOIN cpa_users cu ON cu.contactid = ca.id
					WHERE ca.id = $id";
		$result = $mysqli->query($query);
		$data = array();
		while ($row = $result->fetch_assoc()) {
			$row['members'] = getContactMembers($mysqli, $id)['data'];
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

/**
 * This function will handle insert/update/delete of a member in DB
 * @throws Exception
 */
function updateEntireMembers($mysqli, $contactid, $members)
{
	$data = array();
	for ($x = 0; $members && $x < count($members); $x++) {
		$id =					$mysqli->real_escape_string(isset($members[$x]['id'])					? (int)$members[$x]['id'] : 0);
		$memberid =				$mysqli->real_escape_string(isset($members[$x]['memberid'])				? (int)$members[$x]['memberid'] : 0);
		$incaseofemergency = 	$mysqli->real_escape_string(isset($members[$x]['incaseofemergency']) 	? (int)$members[$x]['incaseofemergency'] : 0);
		$contacttype =			$mysqli->real_escape_string(isset($members[$x]['contacttype'])			? $members[$x]['contacttype'] : '');

		if ($mysqli->real_escape_string(isset($members[$x]['status'])) and $members[$x]['status'] == 'New') {
			$query = "INSERT INTO cpa_members_contacts (id, memberid, contactid, incaseofemergency, contacttype)
						VALUES (null, $memberid, $contactid, $incaseofemergency, '$contacttype')";
			if (!$mysqli->query($query)) {
				throw new Exception($mysqli->sqlstate . ' - ' . $mysqli->error);
			}
		}

		if ($mysqli->real_escape_string(isset($members[$x]['status'])) and $members[$x]['status'] == 'Modified') {
			$query = "UPDATE cpa_members_contacts SET incaseofemergency = $incaseofemergency, contacttype = '$contacttype' WHERE id = $id";
			if ($mysqli->query($query)) {
				$data['success'] = true;
			} else {
				throw new Exception($mysqli->sqlstate . ' - ' . $mysqli->error);
			}
		}

		if ($mysqli->real_escape_string(isset($members[$x]['status'])) and $members[$x]['status'] == 'Deleted') {
			$query = "DELETE FROM cpa_members_contacts WHERE id = $id";
			if (!$mysqli->query($query)) {
				throw new Exception($mysqli->sqlstate . ' - ' . $mysqli->error);
			}
		}
	}
	$data['success'] = true;
	return $data;
};

function updateEntireContact($mysqli, $contact)
{
	try {
		$data = array();
		$id = $mysqli->real_escape_string(isset($contact['id']) ? $contact['id'] : '');

		$data['successcontact'] = update_contact($mysqli, $contact);
		if ($mysqli->real_escape_string(isset($contact['members']))) {
			$data['successices'] = updateEntireMembers($mysqli, $id, $contact['members']);
		}
		$mysqli->close();

		$data['success'] = true;
		$data['message'] = 'Contact updated successfully.';
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
