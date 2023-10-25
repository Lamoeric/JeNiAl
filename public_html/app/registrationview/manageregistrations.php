<?php
/*
Author : Eric Lamoureux
*/
require_once('../../../private/'. $_SERVER['HTTP_HOST'].'/include/config.php');
require_once('../../include/nocache.php');
require_once('../../include/registration.php');
require_once('../core/directives/testsummary/manageTestSummary.php');
require_once('../core/directives/contacts/contacts.php');
require_once('../core/directives/billing/bills.php');

$thisfilename = 'manageRegistration';
if (isset($_POST['type']) && !empty(isset($_POST['type']))) {
	$type = $_POST['type'];

	switch ($type) {
		case "acceptRegistration":
			acceptRegistration($mysqli, $_POST['registration'], $_POST['billid'], $_POST['language'], $_POST['validcount']);
			break;
		case "insert_registration":
			insert_registration($mysqli, $_POST['registration']);
			break;
		case "updateEntireRegistration":
			updateEntireRegistration($mysqli, $_POST['registration'], $_POST['newstatus']);
			break;
		case "delete_registration":
			delete_registration($mysqli, $_POST['registration']);
			break;
		case "getAllRegistrations":
			getAllRegistrations($mysqli, $_POST['eventType'], $_POST['eventId'], $_POST['filter']);
			break;
		case "getRegistrationDetails":
			getRegistrationDetails($mysqli, $_POST['eventType'], $_POST['eventId'], $_POST['id'], $_POST['language']);
			break;
		case "getAllMembers":
			getAllMembers($mysqli);
			break;
		case "getMemberDetails":
			getMemberDetails($mysqli, $_POST['id'], $_POST['language']);
			break;
		case "getAllBills":
			getAllBills($mysqli);
			break;
		case "getBillDetails":
			getBillDetails($mysqli, $_POST['id']);
			break;
		case "getRegistrationBill":
			getRegistrationBill($mysqli, $_POST['registrationid'], $_POST['language']);
			break;
		case "copyRegistration":
			copyRegistration($mysqli, json_decode($_POST['registration'], true), $_POST['registrationid'], $_POST['registrationdatestr'], $_POST['newstatus']);
			break;
		case "countFamilyMembersRegistrations":
			countFamilyMembersRegistrationsInt($mysqli, $_POST['eventtype'], $_POST['eventid'], $_POST['memberid'], $_POST['language']);
			break;
		default:
			invalidRequest();
	}
} else {
	invalidRequest();
};

function countFamilyMembersRegistrationsInt($mysqli, $eventtype, $eventid, $memberid, $language) {
	try{
		$data = array();
		$data['count'] = countFamilyMembersRegistrations($mysqli, $eventtype, $eventid, $memberid, $language);
		$data['success'] = true;
		echo json_encode($data);
	}catch (Exception $e) {
		$data = array();
		$data['success'] = false;
		$data['message'] = $e->getMessage();
		echo json_encode($data);
	}
}

// Try to get the course codes for the filter
 function getSessionCourseCodes($mysqli, $sessionid, $language) {
	$data = array();
	$data['data'] = array();
	$query = "select distinct cc.code, getTextLabel(cc.label, '$language') coursecodelabel
						from cpa_sessions_courses csc
						join cpa_courses cc ON cc.code = csc.coursecode
						where csc.sessionid = $sessionid
						and cc.active = 1
						and cc.acceptregistrations = 1
						order by cc.code";
	$result = $mysqli->query($query);
	while ($row = $result->fetch_assoc()) {
		$data['data'][] = $row;
	}
	$data['success'] = true;
	return $data;
};

/**
 * This function gets the details of one bill from database
 */
function getBillDetailsInt($mysqli, $id = '') {
	try{
		if (empty($id)) throw new Exception("Invalid Bill.");
		$query = "SELECT * FROM cpa_bills WHERE id = '$id'";
		$result = $mysqli->query($query);
		$data = array();
		while ($row = $result->fetch_assoc()) {
			$row['id'] = (int) $row['id'];
			$data['data'][] = $row;
		}
		$data['success'] = true;
		return $data;
	}catch (Exception $e) {
		$data = array();
		$data['success'] = false;
		$data['message'] = $e->getMessage();
		return $data;
	}
};

/**
 * This function gets list of all members from database
 */
function getAllMembers($mysqli) {
	try{
		$query = "SELECT id, firstname, lastname, skatecanadano FROM cpa_members order by lastname, firstname";
		$result = $mysqli->query($query);
		$data = array();
		while ($row = $result->fetch_assoc()) {
			$row['id'] = (int) $row['id'];
			$data['data'][] = $row;
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

/**
 * This function gets the details of one member from database
 */
function getMemberDetailsInt($mysqli, $id, $language) {
	$data = array();
	$data['data'] = array();
	$query = "SELECT * FROM cpa_members WHERE id = '$id'";
	$result = $mysqli->query($query);
	while ($row = $result->fetch_assoc()) {
		$row['id'] = (int) $row['id'];
		$row['contacts']			= getMemberContacts($mysqli, $id, $language)['data'];
		$row['summary'] 			= getAllTestSummary($mysqli, $id, $language);
		$row['csbalanceribbons'] 	= getMemberCanskateStageRibbons($mysqli, $id, 'BALANCE')['data'];
		$row['cscontrolribbons'] 	= getMemberCanskateStageRibbons($mysqli, $id, 'CONTROL')['data'];
		$row['csagilityribbons'] 	= getMemberCanskateStageRibbons($mysqli, $id, 'AGILITY')['data'];
		$row['csstagebadges'] 		= getMemberCanskateStageBadges($mysqli, $id, 'BALANCE')['data'];
		$data['data'][] = $row;
	}
	$data['success'] = true;
	return $data;
};

/**
 * This function gets the details of one member from database
 */
function getMemberDetails($mysqli, $id, $language) {
	echo json_encode(getMemberDetailsInt($mysqli, $id, $language));
};

function updateEntireRegistration($mysqli, $registration, $newstatus) {
	try{
		$data = updateEntireRegistrationInt($mysqli, $registration, $newstatus);
		echo json_encode($data);
		exit;
	}catch (Exception $e) {
		$data = array();
		$data['success'] = false;
		$data['message'] = $e->getMessage();
		$data['errno'] = $mysqli->errno;
		echo json_encode($data);
		exit;
	}
}

function insert_registration($mysqli, $registration) {
	try {
		echo json_encode(update_registration($mysqli, $registration, null));
		exit;
	}catch (Exception $e) {
		$data = array();
		$data['success'] = false;
		$data['message'] = $e->getMessage();
		echo json_encode($data);
		exit;
	}
}

/**
 * This function will handle registration deletion
 * @param string $registration
 * @throws Exception
 */
function delete_registration($mysqli, $registration) {
	try{
		$id = $mysqli->real_escape_string(isset($registration['id']) ? $registration['id'] : '');
		if (empty($id)) throw new Exception("Invalid registration.");
		$query = "UPDATE cpa_registrations SET relatednewregistrationid = null, lastupdateddate = CURRENT_TIMESTAMP WHERE relatednewregistrationid	 = $id";
		if ($mysqli->query($query)) {
			$query = "DELETE FROM cpa_registrations WHERE id = $id";
			if ($mysqli->query($query)) {
				$data['success'] = true;
				$data['message'] = 'Registration deleted successfully.';
				echo json_encode($data);
				exit;
			} else {
				throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
			}
		} else {
			throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
		}
	}catch (Exception $e) {
		$data = array();
		$data['success'] = false;
		$data['message'] = $e->getMessage();
		echo json_encode($data);
		exit;
	}
};

/**
 * This function gets list of all registrations from database
 */
function getAllRegistrations($mysqli, $eventtype, $eventid, $filter) {
	try{
		if ($eventtype == 1) { // Session
			$query =   "SELECT cr.id, cr.registrationdate, cr.status, cm.lastname memberlastname, cm.firstname memberfirstname, cs.name sessionname, cs.id as eventid, 1 as eventtype
						FROM cpa_registrations cr
						LEFT JOIN cpa_members cm ON cm.id = cr.memberid
						JOIN cpa_sessions cs ON cs.id = cr.sessionid 
						WHERE cs.id = $eventid
						AND (relatednewregistrationid is null || relatednewregistrationid = 0)
						ORDER BY cr.sessionid DESC, cr.id DESC, cr.registrationdate DESC";
		} else if ($eventtype == 2) { // Show
			$query =   "SELECT cr.id, cr.registrationdate, cr.status, cm.lastname memberlastname, cm.firstname memberfirstname, cs.name sessionname, cs.id as eventid, 2 as eventtype
						FROM cpa_registrations cr
						LEFT JOIN cpa_members cm ON cm.id = cr.memberid
						JOIN cpa_shows cs ON cs.id = cr.showid 
						WHERE cs.id = $eventid
						AND (relatednewregistrationid is null || relatednewregistrationid = 0)
						ORDER BY cr.sessionid DESC, cr.id DESC, cr.registrationdate DESC";
		}
		$result = $mysqli->query($query);
		$data = array();
		$data['data'] = array();
		while ($row = $result->fetch_assoc()) {
			$row['id'] = (int) $row['id'];
			$data['data'][] = $row;
		}
		$data['success'] = true;
		echo json_encode($data);exit;
	}catch (Exception $e) {
		$data = array();
		$data['success'] = false;
		$data['message'] = $e->getMessage();
		echo json_encode($data);
		exit;
	}
};

/**
 * This function gets the details of one registration from database
 */
function getRegistrationDetails($mysqli, $eventtype, $eventid, $id, $language) {
	try{
		if (empty($id)) throw new Exception("Invalid registration.");
		if ($eventtype == 1) { // Session
			$query =   "SELECT  cr.id, cr.memberid, cr.sessionid, cr.showid, cr.registrationdate, cr.familycount familyMemberCount, cr.relatednewregistrationid,
								cr.relatedoldregistrationid, cr.status, cr.regulationsread, cr.comments, cr.creationdate, cr.lastupdateddate,
								getTextLabel(cs.label, '$language') sessionlabel, cs.name sessionname, cs.proratastartdate, 
								if(cs.proratastartdate is null OR cs.proratastartdate <= cr.registrationdate, 1, 0) use_prorata,
								cs.prorataoptions, cs.id as eventid, 1 as eventtype
						FROM cpa_registrations cr
						JOIN cpa_sessions cs ON cs.id = cr.sessionid
						WHERE cr.id = $id";
		} else if ($eventtype == 2) { // Show
			$query =   "SELECT  cr.id, cr.memberid, cr.sessionid, cr.showid, cr.registrationdate, cr.familycount familyMemberCount, cr.relatednewregistrationid,
								cr.relatedoldregistrationid, cr.status, cr.regulationsread, cr.comments, cr.creationdate, cr.lastupdateddate,
								getTextLabel(cs.label, '$language') sessionlabel, cs.name sessionname, null as proratastartdate, 0 as use_prorata,
								null as prorataoptions, cs.id as eventid, 2 as eventtype
						FROM cpa_registrations cr
						JOIN cpa_shows cs ON cs.id = cr.showid
						WHERE cr.id = $id";
		}
		$result = $mysqli->query($query);
		$data = array();
		while ($row = $result->fetch_assoc()) {
			$row['id'] = (int) $row['id'];
			$sessionid = (int) $row['sessionid'];
			$registrationdate = $row['registrationdate'];
			$memberid					= '';
			if (!empty($row['memberid'])) {
				$memberid = (int) $row['memberid'];
				$temp = getMemberDetailsInt($mysqli, $memberid, $language)['data'];
				if (count($temp) > 0) {
					$row['member'] = $temp[0];
				}
			}
			if ($eventtype == 1) { // Session
				$row['courses'] 	= getSessionCoursesDetails($mysqli, $id, $registrationdate, $sessionid, $language)['data'];
				$row['coursecodes'] = getSessionCourseCodes($mysqli, $sessionid, $language)['data'];
				$row['charges'] 	= getChargesDetails($mysqli, $id, $sessionid, $language)['data'];
			} else if ($eventtype == 2) { // Show
				$row['shownumbers'] = getShowNumbersDetails($mysqli, $id, $registrationdate, $eventid, $memberid, $language)['data'];
				$row['charges'] 	= getShowChargesDetails($mysqli, $id, $eventid, $language)['data'];
			}
			// Bills
			$tmpBillData = getRegistrationBillInt($mysqli, $id, $language)['data'];
			if (count($tmpBillData) > 0) {
				$row['bill'] = $tmpBillData[0];
			}
			// Family members with a registration for the current session, used in calculation for the family discounts
			if ($row['status'] == 'DRAFT') {
				$row['familyMemberCount'] = countFamilyMembersRegistrations($mysqli, $eventtype, $eventid, $memberid, $language);
			}
			$data['data'][] = $row;
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

/*
	This function copies an existing registration and returns the new registration id
*/
function copyRegistration($mysqli, $registration, $registrationid, $newregistrationdatestr, $newregistrationstatus) {
	try{
		$data = array();
		// Check if registration is still up to date, if not, exit with error
		if (isRegistrationUpToDate($mysqli, $registration) == false) {
			$data['success'] = false;
		 	$data['errno']   = 8888;
		 	$data['message'] = 'Registration is not up to date.';
		 	echo json_encode($data);
		 	exit;
		}
		$query = "INSERT INTO cpa_registrations(id, memberid, sessionid, showid, registrationdate, relatednewregistrationid, relatedoldregistrationid, status, regulationsread, familycount)
							SELECT null, memberid, sessionid, showid, '$newregistrationdatestr', null, id, '$newregistrationstatus', regulationsread, familycount
							FROM cpa_registrations where id = '$registrationid' ";
		if ($mysqli->query($query)) {
			$newregistrationid = (int) $mysqli->insert_id;
			$query = "UPDATE cpa_registrations SET relatednewregistrationid = '$newregistrationid', lastupdateddate = CURRENT_TIMESTAMP where id = '$registrationid'";
			if ($mysqli->query($query)) {
				$query = "INSERT INTO cpa_registrations_charges(id, registrationid, chargeid, amount, comments, oldchargeid)
									SELECT null, '$newregistrationid', chargeid, amount, comments, id
									FROM cpa_registrations_charges
									WHERE registrationid = '$registrationid' ";
				if ($mysqli->query($query)) {
					$query = "INSERT INTO cpa_registrations_courses(id, registrationid, courseid, amount, selected)
										SELECT null, '$newregistrationid', courseid, amount, selected
										FROM cpa_registrations_courses
										WHERE registrationid = '$registrationid'";
					if (!$mysqli->query($query)) {
						throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
					}
					$query = "INSERT INTO cpa_registrations_numbers(id, registrationid, numberid, amount, selected)
										SELECT null, '$newregistrationid', numberid, amount, selected
										FROM cpa_registrations_numbers
										WHERE registrationid = '$registrationid'";
					if (!$mysqli->query($query)) {
						throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
					}
				} else {
					throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
				}
			} else {
				throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
			}
		} else {
			throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
		}
		$data['newregistrationid'] = $newregistrationid;
		$data['success'] = true;
		$data['message'] = 'Registration copied successfully.';
		echo json_encode($data);
		exit;
	}catch (Exception $e) {
		$data = array();
		$data['success'] = false;
		$data['message'] = $e->getMessage();
		echo json_encode($data);
		exit;
	}
}

function invalidRequest() {
	$data = array();
	$data['success'] = false;
	$data['message'] = "Invalid request.";
	echo json_encode($data);
	exit;
};

?>
