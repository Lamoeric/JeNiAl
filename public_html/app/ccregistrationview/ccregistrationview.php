<?php
/*
Author : Eric Lamoureux
*/
require_once('../../../private/'. $_SERVER['HTTP_HOST'].'/include/config.php');
require_once('../../include/nocache.php');
require_once('../../include/registration.php');
require_once('../core/directives/billing/bills.php');

if( isset($_POST['type']) && !empty( isset($_POST['type']) ) ){
	$type = $_POST['type'];

	switch ($type) {
		case "getSkaterRegistrationDetails":
			getSkaterRegistrationDetails($mysqli, $_POST['userid'], $_POST['skaterid'], $_POST['sessionid'], $_POST['registrationdate'], $_POST['language']);
			break;
		case "getSessionRules":
			getSessionRules($mysqli, $_POST['sessionid'], $_POST['language']);
			break;
		case "acceptRegistration":
			acceptRegistrationWeb($mysqli, $_POST['registration'], $_POST['billid'], $_POST['language'], true);
			break;
		default:
			invalidRequest();
	}
} else {
	invalidRequest();
};

function acceptRegistrationWeb($mysqli, $registration, $billid, $language, $validcount) {
	if (memberAlreadyHasARegistration($mysqli, $registration) == false) {
		acceptRegistration($mysqli, $registration, $billid, $language, $validcount);
	} else {
		$data['success'] = false;
		$data['errno']   = 9999;
		$data['message'] = 'Member already has a registration.';
	}
	echo $data;
}

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
		$data['data'][] = $row;
	}
	$data['success'] = true;
	return $data;
};

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

/*
	This function copies an existing registration and returns the new registration id
*/
function copyRegistration($mysqli, $registrationid, $newregistrationdatestr, $newregistrationstatus) {
	try{
		$data = array();
		$query = "INSERT INTO cpa_registrations(id, memberid, sessionid, showid, registrationdate, relatednewregistrationid, relatedoldregistrationid, status, regulationsread, familycount)
							SELECT null, memberid, sessionid, showid, curdate(), null, id, '$newregistrationstatus', regulationsread, familycount
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
		return $data;
		exit;
	}catch (Exception $e) {
		$data = array();
		$data['success'] = false;
		$data['message'] = $e->getMessage();
		return $data;
		exit;
	}
}


/**
 * This function gets the registration of the skaters
 * @throws Exception
 */
function getSkaterRegistrationDetails($mysqli, $userid, $skaterid, $sessionid, $registrationdate, $language){
	try{
		$data = array();
		// Check if user already has a registration for this session
		$query = "SELECT cr.id, cr.lastupdateddate
				  FROM cpa_registrations cr
				  WHERE cr.memberid = $skaterid
				  AND cr.sessionid = $sessionid
				  AND (cr.relatednewregistrationid is null OR cr.relatednewregistrationid = 0)";
		$result = $mysqli->query( $query );
		$row = $result->fetch_assoc();
		if (!empty($row['id'])) {
			$registrationid = (int)$row['id'];
			$lastupdateddate = $row['lastupdateddate'];
		} else {
			$registrationid = 0;
		}
		$query = "SELECT cs.id sessionid, getTextLabel(cs.label, '$language') sessionname, cs.coursesstartdate, cs.coursesenddate, $skaterid memberid
				  FROM cpa_sessions cs
				  WHERE cs.id = $sessionid";
		$result = $mysqli->query( $query );
		while ($row = $result->fetch_assoc()) {
			$temp = getMemberDetailsInt($mysqli, $skaterid, $language)['data'];
			if (count($temp) > 0) {
				$row['member'] = $temp[0];
			}
			$row['courses'] 			= getSessionCoursesDetails($mysqli, $registrationid, $registrationdate, $sessionid, $language)['data'];
			$row['coursecodes'] 		= getSessionCourseCodes($mysqli, $sessionid, $language)['data'];
			$row['charges'] 			= getChargesDetails($mysqli, $registrationid, $sessionid, $language, true)['data'];
			$row['familyMemberCount']	= countFamilyMembersRegistrations($mysqli, 1, $sessionid, $skaterid, $language);
			$tmpBillData    			= getRegistrationBillInt($mysqli, $registrationid, $language)['data'];
			if (count($tmpBillData) > 0) {
				$row['bill'] = $tmpBillData[0];
			}
			$data['data'][] = $row;
		}
		$data['data'][0]['id'] = (int)$registrationid;
		$data['data'][0]['originalId'] = (int)$registrationid;
		$data['data'][0]['lastupdateddate'] = $lastupdateddate;
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
 * This function gets the session rules for the session
 * @throws Exception
 */
function getSessionRules($mysqli, $sessionid, $language){
	try{
		$data = array();
		$query = "SELECT rules
							FROM cpa_sessions_rules csr
							WHERE csr.sessionid = $sessionid
							AND language = '$language'";
		$result = $mysqli->query( $query );
		$row = $result->fetch_assoc();
		header("Content-type: text/text;charset=iso-8859-1");
    echo $row['rules'];
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
