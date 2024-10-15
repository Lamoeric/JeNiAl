<?php
/*
Author : Eric Lamoureux
*/
require_once('../../../private/' . $_SERVER['HTTP_HOST'] . '/include/config.php');
require_once('../../include/nocache.php');
require_once('../../include/registration.php');
require_once('../../backend/invalidrequest.php');

if (isset($_POST['type']) && !empty(isset($_POST['type']))) {
	$type = $_POST['type'];

	switch ($type) {
		case "getAllCourses":
			getAllCourses($mysqli, $_POST['eventType'], $_POST['eventId'], $_POST['language']);
			break;
		case "getCourseDetails":
			getCourseDetails($mysqli, $_POST['eventType'], $_POST['id'], $_POST['language']);
			break;
		case "updateEntireCourse":
			updateEntireCourse($mysqli, $_POST['eventType'], json_decode($_POST['sessioncourse'], true));
			break;
		default:
			invalidRequest($type);
	}
} else {
	invalidRequest();
};

/**
 * This function will handle course attendance (add, update) functionality
 * @throws Exception
 */
function updateEntireCourse($mysqli, $type, $course)
{
	try {
		$data = array();
		if ($type == 1) {
			$data['members'] = updateCourseAttendance($mysqli, $course['members']);
			$data['staffs']  = updateCourseAttendance($mysqli, $course['staffs']);
			$data['success'] = true;
		} else if ($type == 2) {
			$data['members'] = updateNumberAttendance($mysqli, $course['members']);
			$data['staffs']  = updateNumberAttendance($mysqli, $course['staffs']);
			$data['success'] = true;
		}
		echo json_encode($data);
	} catch (Exception $e) {
		$data = array();
		$data['success'] = false;
		$data['message'] = $e->getMessage();
		echo json_encode($data);
		exit;
	}
}

/**
 * This function will handle course attendance (add, update) functionality
 * @throws Exception
 */
function updateCourseAttendance($mysqli, $members)
{
	$data = array();
	$data['success'] = true;
	$data['insert'] = 0;
	$data['update'] = 0;
	$data['insertedStaff'] = 0;

	for ($x = 0; $x < count($members); $x++) {
		$member = $members[$x];
		if (isset($member['sublevelcode'])) {
			$sublevelcode = $member['sublevelcode'];
			$sessionscoursesmembersid = $member['sessionscoursesmembersid'];
			$query = "UPDATE cpa_sessions_courses_members SET sublevelcode = '$sublevelcode' where id = $sessionscoursesmembersid";
			if (!$mysqli->query($query)) {
				throw new Exception('updateCourseAttendance - update members - ' . $mysqli->sqlstate . ' - ' . $mysqli->error);
			}
		}
		if (!isset($member['sessionscoursesmembersid'])){
			$sessionscoursesid =	$mysqli->real_escape_string(isset($member['sessionscoursesid'])	? $member['sessionscoursesid'] : '');
			$memberid =				$mysqli->real_escape_string(isset($member['memberid'])			? $member['memberid'] : '');
			$staffcode =			$mysqli->real_escape_string(isset($member['staffcode'])			? $member['staffcode'] : '');
			$statuscode =			$mysqli->real_escape_string(isset($member['statuscode'])		? $member['statuscode'] : '');

			$query = "INSERT INTO cpa_sessions_courses_staffs (sessionscoursesid, memberid, staffcode, statuscode)
						VALUES ('$sessionscoursesid', '$memberid', '$staffcode', '$statuscode')";

			if ($mysqli->query($query)) {
				$member['id'] = $mysqli->insert_id;
				$data['insertedStaff']++;
			} else {
				throw new Exception('updateCourseAttendance - insert new member - ' . $mysqli->sqlstate . ' - ' . $mysqli->error);
			}
		} else {
			for ($y = 0; $y < count($member['dates']); $y++) {
				$date = $member['dates'][$y];
				$sessionscoursesdatesid = $date['sessionscoursesdatesid'];
				$memberid = $member['memberid'];
				$presenceid = $date['presenceid'];
				$ispresent = $date['ispresent'];
				// Transform ispresent to ignore non numerical character
				if ($ispresent == 'XXX' || $ispresent == '---' ||  $ispresent == '$') {
					$ispresent = 0;
				}
				if ($presenceid == null && $ispresent != 0) {	// Need to insert the new attendance
					$query = "	INSERT INTO cpa_sessions_courses_presences (sessionscoursesdatesid, memberid, ispresent)
								VALUES ($sessionscoursesdatesid, $memberid, '$ispresent')";
					$data['insert']++;
					if (!$mysqli->query($query)) {
						throw new Exception('updateCourseAttendance - insert presences - ' . $mysqli->sqlstate . ' - ' . $mysqli->error);
					}
				} else if ($presenceid != null) {		// Need to update the attendance
					$query = "UPDATE cpa_sessions_courses_presences SET ispresent = '$ispresent' where id = $presenceid";
					$data['update']++;
					if (!$mysqli->query($query)) {
						throw new Exception('updateCourseAttendance - update presences - ' . $mysqli->sqlstate . ' - ' . $mysqli->error);
					}
				} else {	// Nothing to do.... attendance doesn't exist, but the ispresent == 0.
				}
			}
		}
	}
	return $data;
};

/**
 * This function will handle number attendance (add, update) functionality
 * @throws Exception
 */
function updateNumberAttendance($mysqli, $members)
{
	$data = array();
	$data['success'] = true;
	$data['insert'] = 0;
	$data['update'] = 0;
	$data['insertedStaff'] = 0;

	for ($x = 0; $x < count($members); $x++) {
		$member = $members[$x];
		if (!isset($member['sessionscoursesmembersid'])){
			$numberid =		$mysqli->real_escape_string(isset($member['numberid'])	? $member['numberid'] : '');
			$showid =		$mysqli->real_escape_string(isset($member['showid'])	? $member['showid'] : '');
			$memberid =		$mysqli->real_escape_string(isset($member['memberid'])	? $member['memberid'] : '');
			$staffcode =	$mysqli->real_escape_string(isset($member['staffcode'])	? $member['staffcode'] : '');

			$query = "INSERT INTO cpa_shows_numbers_staffs (numberid, showid, memberid, staffcode)
						VALUES ('$numberid', '$showid', '$memberid', '$staffcode')";

			if ($mysqli->query($query)) {
				$member['id'] = $mysqli->insert_id;
				$data['insertedStaff']++;
			} else {
				throw new Exception('updateNumberAttendance - insert new member - ' . $mysqli->sqlstate . ' - ' . $mysqli->error);
			}
		} else {
			for ($y = 0; $y < count($member['dates']); $y++) {
				$date = $member['dates'][$y];
				$showsnumbersdatesid = $date['sessionscoursesdatesid'];
				$memberid = $member['memberid'];
				$presenceid = $date['presenceid'];
				$ispresent = $date['ispresent'];
				// Transform ispresent to ignore non numerical character
				if ($ispresent == 'XXX' || $ispresent == '---' ||  $ispresent == '$') {
					$ispresent = 0;
				}
				if ($presenceid == null && $ispresent != 0) {		// Need to insert the new attendance
					$query = "	INSERT into cpa_shows_numbers_presences (showsnumbersdatesid, showid, numberid, memberid, ispresent) 
								VALUES ($showsnumbersdatesid, (select showid from cpa_shows_numbers_dates where id = $showsnumbersdatesid), 
										(select numberid from cpa_shows_numbers_dates where id = $showsnumbersdatesid), $memberid, $ispresent)";
					$data['insert']++;
					if (!$mysqli->query($query)) {
						throw new Exception('updateNumberAttendance - insert presences - ' . $mysqli->sqlstate . ' - ' . $mysqli->error);
					}
				} else if ($presenceid != null) {		// Need to update the attendance
					$query = "UPDATE cpa_shows_numbers_presences SET ispresent = '$ispresent' where id = $presenceid";
					$data['update']++;
					if (!$mysqli->query($query)) {
						throw new Exception('updateNumberAttendance - update presences - ' . $mysqli->sqlstate . ' - ' . $mysqli->error);
					}
				} else {	// Nothing to do.... attendance doesn't exist, but the ispresent == 0.
				}
			}
		}
	}
	return $data;
};

/**
 * This function gets list of all courses from database
 */
function getAllCourses($mysqli, $type, $id, $language)
{
	try {
		if ($type == 1) {
			$query = "SELECT csc.id, csc.coursecode, courselevel, csc.name, csc.maxnumberskater, getTextLabel(csc.label, '$language') courselabel, 
							getTextLabel(cc.label, '$language') codelabel,
							getTextLabel(ccl.label, '$language') levellabel, 
							(select count(*) from cpa_sessions_courses_members cscm where cscm.sessionscoursesid = csc.id and (cscm.registrationenddate is null or cscm.registrationenddate > curdate())) nbskater
						FROM cpa_sessions_courses csc
						JOIN cpa_sessions cs ON cs.id = csc.sessionid
						JOIN cpa_courses cc ON cc.code = csc.coursecode
						LEFT JOIN cpa_courses_levels ccl ON ccl.coursecode = cc.code AND ccl.code = csc.courselevel
						WHERE cs.id = $id 
						AND cc.acceptregistrations = 1
						ORDER BY csc.name";
		} else if ($type == 2) {
			$query = "SELECT csn.id, null, null, csn.name, '-' as maxnumberskater, getTextLabel(csn.label, '$language') courselabel, 
								null, 
								(select count(*) from cpa_shows_numbers_members csnm where csnm.numberid = csn.id and (csnm.registrationenddate is null or csnm.registrationenddate > curdate())) nbskater
						FROM cpa_shows_numbers csn
						JOIN cpa_shows cs ON cs.id = csn.showid
						WHERE cs.id = $id
						AND csn.type = 1
						-- AND csn.datesgenerated = 1
						ORDER BY csn.name";
		}
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
 * This function gets the details of all levels for a course from database
 */
function getCourseLevels($mysqli, $coursecode = '')
{
	try {
		$query = "SELECT *, getEnglishTextLabel(label) as label_en, getFrenchTextLabel(label) as label_fr
							FROM cpa_courses_levels
							WHERE coursecode = '$coursecode'";
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
 * This function gets the details of all schedules for a session course
 */
function getSessionCourseDates($mysqli, $type, $sessionscoursesid)
{
	if (empty($sessionscoursesid)) throw new Exception("Invalid session course.");
	if ($type == 1) {
		$query = "SELECT *, getTextLabel(label, 'fr-ca') label_fr, getTextLabel(label, 'en-ca') label_en
							FROM cpa_sessions_courses_dates
							WHERE sessionscoursesid = $sessionscoursesid
							order by coursedate";
	} else if ($type == 2) {
		$query = "SELECT csnd.*, csnd.practicedate as coursedate
							FROM cpa_shows_numbers_dates csnd
							WHERE csnd.numberid = $sessionscoursesid 
							order by practicedate";
	}
	$result = $mysqli->query($query);
	$data = array();
	$data['data'] = array();
	while ($row = $result->fetch_assoc()) {
		$data['data'][] = $row;
	}
	$data['success'] = true;
	return $data;
};

/**
 * This function gets the list of presences for a member for a course from database
 */
function getSessionCourseMembersDates($mysqli, $type, $memberid, $sessionscoursesid, $registrationenddate, $registrationstartdate, $paidinfull)
{
	if (empty($memberid)) throw new Exception("Invalid Member ID.");
	if ($type == 1) {
		$query = "SELECT cscd.id sessionscoursesdatesid, if ('$registrationenddate' != '' and cscd.coursedate >= '$registrationenddate', 'XXX', if ('$registrationstartdate' != '' and cscd.coursedate < '$registrationstartdate', 'XXX', if (cscd.canceled, '---', cscp.ispresent))) ispresent, cscp.id presenceid
							FROM cpa_sessions_courses_dates cscd
							LEFT JOIN cpa_sessions_courses_presences cscp ON cscp.sessionscoursesdatesid = cscd.id and cscp.memberid = '$memberid'
							WHERE sessionscoursesid = '$sessionscoursesid'
							ORDER BY coursedate";
	} else if ($type == 2) {
		$query = "SELECT csnd.id sessionscoursesdatesid, csnd.practicedate as coursedate, if ('$registrationenddate' != '' and csnd.practicedate >= '$registrationenddate', 'XXX', if ('$registrationstartdate' != '' and csnd.practicedate <= '$registrationstartdate', 'XXX', if (csnd.canceled, '---', csnp.ispresent))) ispresent, csnp.id presenceid
							FROM cpa_shows_numbers_dates csnd
							LEFT JOIN cpa_shows_numbers_presences csnp ON csnp.showsnumbersdatesid = csnd.id and csnp.memberid = $memberid
							WHERE csnd.numberid = $sessionscoursesid
							ORDER BY practicedate";
	}
	$result = $mysqli->query($query);
	$data = array();
	$data['data'] = array();
	while ($row = $result->fetch_assoc()) {
		// Check the paidinfull flag
		if ($paidinfull == 0) {
			if ($row['ispresent'] == '') {
				$row['ispresent'] = '$';
			}
		}
		$data['data'][] = $row;
	}
	$data['success'] = true;
	return $data;
};

/**
 * This function gets the list of presences for a staff for a course from database
 */
function getSessionCourseStaffsDates($mysqli, $type, $memberid, $sessionscoursesid, $registrationenddate)
{
	if (empty($memberid)) throw new Exception("Invalid Member ID.");
	if ($type == 1) {
		$query = "SELECT cscd.id sessionscoursesdatesid, cscp.id presenceid, 
							if ('$registrationenddate' != '' and cscd.coursedate >= '$registrationenddate', 'XXX', 
													if (cscd.canceled, '---', if (cscp.ispresent is null, 0, cscp.ispresent))) ispresent
							FROM cpa_sessions_courses_dates cscd
							LEFT JOIN cpa_sessions_courses_presences cscp ON cscp.sessionscoursesdatesid = cscd.id and cscp.memberid = '$memberid'
							WHERE sessionscoursesid = '$sessionscoursesid'
							ORDER BY coursedate";
	} else if ($type == 2) {
		$query = "SELECT csnd.id sessionscoursesdatesid, csnd.practicedate as coursedate, csnp.id presenceid, 
										if ('$registrationenddate' != '' and csnd.practicedate >= '$registrationenddate', 'XXX', 
																				if (csnd.canceled, '---', if (csnp.ispresent is null, 0, csnp.ispresent))) ispresent
									 
							FROM cpa_shows_numbers_dates csnd
							LEFT JOIN cpa_shows_numbers_presences csnp ON csnp.showsnumbersdatesid = csnd.id and csnp.memberid = $memberid
							WHERE csnd.numberid = $sessionscoursesid
							ORDER BY practicedate";
	}
	$result = $mysqli->query($query);
	$data = array();
	$data['data'] = array();
	while ($row = $result->fetch_assoc()) {
		$data['data'][] = $row;
	}
	$data['success'] = true;
	return $data;
};

/**
 * This function gets the members of a specific course
 */
function getSessionCourseMembers($mysqli, $type, $sessionscoursesid)
{
	if (empty($sessionscoursesid)) throw new Exception("Invalid session course.");
	if ($type == 1) {
		$query = "SELECT cscm.id sessionscoursesmembersid, cscm.sublevelcode sublevelcode, cscm.registrationenddate, cscm.registrationstartdate, cm.firstname, cm.lastname, 
										 cm.id memberid, cs.attendancepaidinfull, cb.paidinfull, cb.haspaymentagreement
							FROM cpa_sessions_courses_members cscm
							JOIN cpa_members cm ON cm.id = cscm.memberid
							JOIN cpa_sessions_courses csc ON csc.id = cscm.sessionscoursesid
							JOIN cpa_sessions cs on cs.id = csc.sessionid
							JOIN cpa_registrations cr ON cr.sessionid = cs.id AND cr.memberid = cscm.memberid AND (relatednewregistrationid = 0 OR relatednewregistrationid is null)
							JOIN cpa_bills_registrations cbr ON cbr.registrationid = cr.id
							JOIN cpa_bills cb ON cb.id = cbr.billid AND relatednewbillid is null
							WHERE sessionscoursesid = $sessionscoursesid
							order by cm.lastname, cm.firstname";
	} else if ($type == 2) {
		$query = "SELECT csnm.id sessionscoursesmembersid, null as sublevelcode, csnm.registrationenddate, csnm.registrationstartdate, cm.firstname, cm.lastname, cm.id memberid
							FROM cpa_shows_numbers_members csnm
							JOIN cpa_members cm ON cm.id = csnm.memberid
							JOIN cpa_shows_numbers csn ON csn.id = csnm.numberid
							JOIN cpa_shows cs on cs.id = csn.showid
							WHERE csnm.numberid = $sessionscoursesid
							order by cm.lastname, cm.firstname";
	}
	$result = $mysqli->query($query);
	$data = array();
	$data['data'] = array();
	while ($row = $result->fetch_assoc()) {
		$paidinfull = 0;
		$attendancepaidinfull = 0;
		if (isset($row['paidinfull'])) $paidinfull = (int)$row['paidinfull'];
		if (isset($row['haspaymentagreement'])) $haspaymentagreement = (int)$row['haspaymentagreement'];
		if (isset($row['attendancepaidinfull'])) $attendancepaidinfull = (int)$row['attendancepaidinfull'];
		if ($attendancepaidinfull == 0) { // Skater does not need to have a paidinfull bill to attend courses, so change the paidinfull flag
			$paidinfull = 1;
		} else if ($attendancepaidinfull == 1) { // Skater needs to have a paidinfull bill to attend courses or a paymentagreement
			if ($haspaymentagreement == 1) {
				$paidinfull = 1;
			}
		}
		$row['dates'] = getSessionCourseMembersDates($mysqli, $type, $row['memberid'], $sessionscoursesid, $row['registrationenddate'], $row['registrationstartdate'], $paidinfull)['data'];
		$data['data'][] = $row;
	}
	$data['success'] = true;
	return $data;
};

/**
 * This function gets the details of all staffs for a session course
 */
function getSessionCourseStaffs($mysqli, $type, $sessionscoursesid, $language)
{
	if (empty($sessionscoursesid)) throw new Exception("Invalid session course.");
	if ($type == 1) {
		$query = "SELECT cscs.id sessionscoursesmembersid, null registrationenddate, null registrationstartdate, cm.firstname, cm.lastname, 
						concat(cm.firstname, ' ', cm.lastname) fullname, cm.id memberid, cscs.staffcode, cscs.statuscode, 
						getCodeDescription('staffcodes', cscs.staffcode, '$language') staffcodelabel
					FROM cpa_sessions_courses_staffs cscs
					JOIN cpa_members cm ON cm.id = cscs.memberid
					WHERE sessionscoursesid = $sessionscoursesid
					ORDER BY cscs.staffcode, cm.lastname";
	} else if ($type == 2) {
		$query = "SELECT csns.id sessionscoursesmembersid, null registrationenddate, null registrationstartdate, cm.firstname, cm.lastname, 'PERM' as statuscode,
						concat(cm.firstname, ' ', cm.lastname) fullname, cm.id memberid, getCodeDescription('staffcodes', csns.staffcode, '$language') staffcodelabel
					FROM cpa_shows_numbers_staffs csns
					JOIN cpa_members cm ON cm.id = csns.memberid
					WHERE numberid = $sessionscoursesid
					ORDER BY csns.staffcode, cm.lastname";
	}
	$result = $mysqli->query($query);
	$data = array();
	$data['data'] = array();
	while ($row = $result->fetch_assoc()) {
		$row['dates'] = getSessionCourseStaffsDates($mysqli, $type, $row['memberid'], $sessionscoursesid, $row['registrationenddate'])['data'];
		$data['data'][] = $row;
	}
	$data['success'] = true;
	return $data;
};

/**
 * This function gets the details of one course from database
 */
function getCourseDetails($mysqli, $type, $id, $language)
{
	try {
		if ($type == 1) {
			$query = "SELECT csc.*, getEnglishTextLabel(csc.label) as label_en, getFrenchTextLabel(csc.label) as label_fr, 
							getTextLabel(csc.label, '$language') courselabel, getTextLabel(ccl.label, '$language') levellabel,
							getTextLabel(cc.label, '$language') codelabel,
							getCodeDescription('yesno', csc.availableonline, '$language') availableonlinelabel
						FROM cpa_sessions_courses csc
						JOIN cpa_courses cc ON cc.code = csc.coursecode
						LEFT JOIN cpa_courses_levels ccl ON ccl.coursecode = cc.code and ccl.code = csc.courselevel
						WHERE id = $id";
		} else if ($type == 2) {
			$query = "SELECT csn.*, getEnglishTextLabel(csn.label) as label_en, getFrenchTextLabel(csn.label) as label_fr, getTextLabel(csn.label, '$language') as courselabel, null as levellabel
						FROM cpa_shows_numbers csn
						WHERE id = $id";
		}
		$result = $mysqli->query($query);
		$data = array();
		while ($row = $result->fetch_assoc()) {
			if ($type == 1) {
				$row['schedules'] 	= getSessionCourseSchedule($mysqli, $row['id'], $language);
			} else {
				$row['schedules'] 	= getShowNumberSchedule($mysqli, $row['id'], $language);
			}
			$row['dates'] 	= getSessionCourseDates($mysqli, $type, $row['id'])['data'];
			$row['members'] = getSessionCourseMembers($mysqli, $type, $row['id'])['data'];
			$row['staffs'] 	= getSessionCourseStaffs($mysqli, $type, $row['id'], $language)['data'];
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
