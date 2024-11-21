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
		case "updateMemberAttendance":
			updateMemberAttendance($mysqli, $_POST['memberattendance'], $_POST['eventtype'], $_POST['sessionscoursesid'], $_POST['sessionscoursesdatesid'], $_POST['language']);
			break;
		case "getCourseAttendanceDetails":
			getCourseAttendanceDetails($mysqli, $_POST['eventtype'], $_POST['sessionscoursesid'], $_POST['sessionscoursesdatesid'], $_POST['language']);
			break;
		case "getCoursesList":
			getCoursesList($mysqli, $_POST['language']);
			break;
		default:
			invalidRequest($type);
	}
} else {
	invalidRequest();
};

/**
 * This function will handle insert/update/delete of a attendance in DB
 * @throws Exception
 */
function updateMemberAttendance($mysqli, $memberattendance, $eventtype, $sessionscoursesid, $sessionscoursesdatesid, $language)
{
	$data = array();
	try {
		$id						=	isset($memberattendance['id']) && !empty($memberattendance['id'])	? $mysqli->real_escape_string($memberattendance['id']) : null;
		$status					=	isset($memberattendance['status']) 					? $mysqli->real_escape_string($memberattendance['status']) : null;
		$staffcode				=	isset($memberattendance['staffcode'])				? $mysqli->real_escape_string($memberattendance['staffcode']) : '';
		$statuscode				=	isset($memberattendance['statuscode'])				? $mysqli->real_escape_string($memberattendance['statuscode']) : '';
		$memberid				=	isset($memberattendance['memberid'])				? (int)$memberattendance['memberid'] : 0;
		$ispresent				=	isset($memberattendance['ispresent'])				? $memberattendance['ispresent'] : 0;

		if ($eventtype == 1) {
			if (!is_null($status) && $status == 'New') {
				// We have a new staff member, we need to add it to the list of staff for the current course
				$query = "INSERT INTO cpa_sessions_courses_staffs (sessionscoursesid, memberid, staffcode, statuscode) 
						  VALUES ($sessionscoursesid, $memberid, '$staffcode', '$statuscode')";
				if (!$mysqli->query($query)) {
					throw new Exception('updateMemberAttendance - course - Insert new staff - ' . $mysqli->sqlstate . ' - ' . $mysqli->error);
				}
			}
			if (is_null($id)) {
				// If id is not set, try first to insert, then try to update. It's possible the id is not set because we don't reload everything all the time.
				$query = "INSERT INTO cpa_sessions_courses_presences (sessionscoursesdatesid, memberid, ispresent) 
						  VALUES ($sessionscoursesdatesid, $memberid, $ispresent)";
				if (!$mysqli->query($query)) {
					$query = "UPDATE cpa_sessions_courses_presences 
							  SET ispresent = $ispresent 
							  WHERE memberid = $memberid 
							  AND sessionscoursesdatesid = $sessionscoursesdatesid";
					if (!$mysqli->query($query)) {
						throw new Exception('updateMemberAttendance - course - insert new presence - ' . $mysqli->sqlstate . ' - ' . $mysqli->error);
					}
				}
			} else {
				$query = "UPDATE cpa_sessions_courses_presences 
						  SET ispresent = $ispresent 
						  WHERE id = $id";
				if (!$mysqli->query($query)) {
					throw new Exception('updateMemberAttendance - course - update presence - ' . $mysqli->sqlstate . ' - ' . $mysqli->error);
				}
			}
			$data['CourseDetail'] = getCourseAttendanceDetailsInt($mysqli, $sessionscoursesid, $sessionscoursesdatesid, $language);
		} else if ($eventtype == 2) {
			if (!$mysqli->real_escape_string(isset($memberattendance['id'])) || empty($id)) {
				$query = "INSERT INTO cpa_shows_numbers_presences (showsnumbersdatesid, showid, numberid, memberid, ispresent) 
						  VALUES ($sessionscoursesdatesid, 
						          (SELECT showid FROM cpa_shows_numbers_dates WHERE id = $sessionscoursesdatesid), 
								  (SELECT numberid FROM cpa_shows_numbers_dates WHERE id = $sessionscoursesdatesid), 
								  $memberid, $ispresent)";
				if (!$mysqli->query($query)) {
					$query = "UPDATE cpa_shows_numbers_presences 
							  SET ispresent = $ispresent 
							  WHERE memberid = $memberid 
							  AND showsnumbersdatesid = $sessionscoursesdatesid";
					if (!$mysqli->query($query)) {
						throw new Exception($mysqli->sqlstate . ' - ' . $mysqli->error);
					}
				}
			} else {
				$query = "UPDATE cpa_shows_numbers_presences 
						  SET ispresent = $ispresent 
						  WHERE id = $id";
				if (!$mysqli->query($query)) {
					throw new Exception($mysqli->sqlstate . ' - ' . $mysqli->error);
				}
			}
			$data['CourseDetail'] = getNumberAttendanceDetailsInt($mysqli, $sessionscoursesid, $sessionscoursesdatesid, $language);
		}
		$data['success'] = true;
		echo json_encode($data);
		exit;
	} catch (Exception $e) {
		// $data = array();
		$data['success'] = false;
		$data['message'] = $e->getMessage();
		echo json_encode($data);
		exit;
	}
};

/**
 * This function gets the details of a course attendance
 */
function getCourseAttendanceDetailsInt($mysqli, $sessionscoursesid, $sessionscoursesdatesid, $language)
{
	$query = "SELECT cscp.id, cscm.memberid, cm.firstname, cm.lastname, cscd.coursedate, cscm.registrationstartdate, cscm.registrationenddate, cscp.ispresent, 
									 cscd.id sessionscoursesdatesid, cs.attendancepaidinfull, cb.paidinfull, cb.haspaymentagreement, 
					        (SELECT count(*)
							FROM cpa_sessions_courses_presences cscp2
							WHERE cscp2.memberid = cscm.memberid
							AND cscp2.ispresent = true
							AND cscp2.sessionscoursesdatesid IN (SELECT cscd2.id
																FROM cpa_sessions_courses_dates cscd2 
																WHERE cscd2.coursedate <= (SELECT coursedate from cpa_sessions_courses_dates where id = $sessionscoursesdatesid)
																AND cscd2.sessionscoursesid = $sessionscoursesid
																AND cscd2.canceled = 0)) nbpresence,
					        (SELECT count(*) 
					         from cpa_sessions_courses_dates cscd 
					         where coursedate <= (select coursedate from cpa_sessions_courses_dates where id = $sessionscoursesdatesid) 
					         and sessionscoursesid = $sessionscoursesid and canceled = 0) nbofcourses
			FROM cpa_sessions_courses_members cscm
			LEFT JOIN cpa_sessions_courses_dates cscd ON cscd.id = $sessionscoursesdatesid
			LEFT JOIN cpa_sessions_courses_presences cscp ON cscp.memberid = cscm.memberid and cscp.sessionscoursesdatesid = $sessionscoursesdatesid
			JOIN cpa_members cm ON cm.id = cscm.memberid
            JOIN cpa_sessions_courses csc ON csc.id = cscd.sessionscoursesid
            JOIN cpa_sessions cs ON cs.id = csc.sessionid
			JOIN cpa_registrations cr ON cr.sessionid = cs.id AND cr.memberid = cscm.memberid AND (relatednewregistrationid = 0 OR relatednewregistrationid is null)
			JOIN cpa_bills_registrations cbr ON cbr.registrationid = cr.id
			JOIN cpa_bills cb ON cb.id = cbr.billid AND relatednewbillid is null
			WHERE cscm.sessionscoursesid = $sessionscoursesid
			AND ((cscm.registrationstartdate is null OR cscm.registrationstartdate <= cscd.coursedate) AND (cscm.registrationenddate is null OR cscm.registrationenddate > cscd.coursedate))
			ORDER BY cm.lastname, cm.firstname";
	$result = $mysqli->query($query);
	$data = array();
	$data['data'] = array();
	while ($row = $result->fetch_assoc()) {
		if (isset($row['paidinfull'])) $row['paidinfull'] = (int)$row['paidinfull'];
		if (isset($row['haspaymentagreement'])) $row['haspaymentagreement'] = (int)$row['haspaymentagreement'];
		if (isset($row['attendancepaidinfull'])) $row['attendancepaidinfull'] = (int)$row['attendancepaidinfull'];
		if ($row['attendancepaidinfull'] == 0) { // Skater does not need to have a paidinfull bill to attend courses, so change the paidinfull flag
			$row['paidinfull'] = 1;
		} else if ($row['attendancepaidinfull'] == 1) { // Skater needs to have a paidinfull bill to attend courses or a paymentagreement
			if ($row['haspaymentagreement'] == 1) {
				$row['paidinfull'] = 1;
			}
		}
		$data['skaters'][] = $row;
	}
	$query = "SELECT cscp.id, cscs.memberid, cm.firstname, cm.lastname, cscd.coursedate, cscp.ispresent, cscd.id sessionscoursesdatesid, cscs.statuscode
			FROM cpa_sessions_courses_staffs cscs
			LEFT JOIN cpa_sessions_courses_dates cscd ON cscd.id = $sessionscoursesdatesid
			LEFT JOIN cpa_sessions_courses_presences cscp ON cscp.memberid = cscs.memberid and cscp.sessionscoursesdatesid = $sessionscoursesdatesid
			JOIN cpa_members cm ON cm.id = cscs.memberid
			WHERE cscs.sessionscoursesid = $sessionscoursesid
			-- AND cscs.statuscode = 'PERM'
			ORDER BY cscs.staffcode, cm.lastname, cm.firstname";
	$result = $mysqli->query($query);
	$data['staffs'] = array();
	while ($row = $result->fetch_assoc()) {
		$data['staffs'][] = $row;
	}
	$query = "SELECT csc.id sessionscourseid, csc.name, getTextLabel(csc.label, '$language') courselabel,
					concat(cscd.coursedate, '@', cscd.starttime) coursedatetime,
					concat(getTextLabel(ca.label, '$language'), ' ', if(cscd.iceid != 0, getTextLabel(cai.label, '$language'), '')) courselocation
			FROM cpa_sessions_courses csc
			LEFT JOIN cpa_sessions_courses_dates cscd ON cscd.id = $sessionscoursesdatesid
			JOIN cpa_arenas ca ON ca.id = cscd.arenaid
			LEFT JOIN cpa_arenas_ices cai ON cai.id = cscd.iceid
			WHERE csc.id = $sessionscoursesid";
	$result = $mysqli->query($query);
	while ($row = $result->fetch_assoc()) {
		$data['coursedetails'] = $row;
	}
	$data['success'] = true;
	return $data;
	exit;
};

/**
 * This function gets the details of a number attendance
 */
function getNumberAttendanceDetailsInt($mysqli, $showsnumbersid, $showsnumbersdatesid, $language)
{
	$query = "SELECT csnp.id, csnm.memberid, cm.firstname, cm.lastname, csnd.practicedate as coursedate, csnm.registrationstartdate, csnm.registrationenddate, csnp.ispresent, 
									 csnd.id sessionscoursesdatesid, 1 as attendancepaidinfull, 1 as paidinfull, 0 as haspaymentagreement,
					(SELECT count(*)
					FROM cpa_shows_numbers_presences csnp2
					WHERE csnp2.memberid = csnm.memberid
					AND csnp2.ispresent = true
					AND csnp2.showsnumbersdatesid IN (SELECT csnd2.id
														FROM cpa_shows_numbers_dates csnd2 
														WHERE csnd2.practicedate <= (SELECT practicedate from cpa_shows_numbers_dates where id = $showsnumbersdatesid)
														AND csnd2.numberid = $showsnumbersid
														AND csnd2.canceled = 0)) nbpresence,
					(SELECT count(*) from cpa_shows_numbers_dates csnd where practicedate <= (select practicedate from cpa_shows_numbers_dates where id = $showsnumbersdatesid) and numberid = $showsnumbersid and canceled = 0) nbofcourses
			FROM cpa_shows_numbers_members csnm
			LEFT JOIN cpa_shows_numbers_dates csnd ON csnd.id = $showsnumbersdatesid
			LEFT JOIN cpa_shows_numbers_presences csnp ON csnp.memberid = csnm.memberid and csnp.showsnumbersdatesid = $showsnumbersdatesid
			JOIN cpa_members cm ON cm.id = csnm.memberid
			WHERE csnm.numberid = $showsnumbersid
			AND ((csnm.registrationstartdate is null OR csnm.registrationstartdate < csnd.practicedate) AND (csnm.registrationenddate is null OR csnm.registrationenddate > csnd.practicedate))
			ORDER BY cm.lastname, cm.firstname";
	$result = $mysqli->query($query);
	$data = array();
	$data['data'] = array();
	while ($row = $result->fetch_assoc()) {
		$data['skaters'][] = $row;
	}
	$query = "SELECT csnp.id, csns.memberid, cm.firstname, cm.lastname, csnd. practicedate as coursedate, csnp.ispresent, csnd.id sessionscoursesdatesid, 'PERM' statuscode
			FROM cpa_shows_numbers_staffs csns
			LEFT JOIN cpa_shows_numbers_dates csnd ON csnd.id = $showsnumbersdatesid
			LEFT JOIN cpa_shows_numbers_presences csnp ON csnp.memberid = csns.memberid and csnp.showsnumbersdatesid = $showsnumbersdatesid
			JOIN cpa_members cm ON cm.id = csns.memberid
			WHERE csns.numberid = $showsnumbersid
			ORDER BY csns.staffcode, cm.lastname, cm.firstname";
	$result = $mysqli->query($query);
	$data['staffs'] = array();
	while ($row = $result->fetch_assoc()) {
		$data['staffs'][] = $row;
	}
	$query = "SELECT csc.id numberid, csn.showid, csn.name, getTextLabel(csn.label, '$language') courselabel,
					concat(csnd.practicedate, ' ', csnd.starttime, ' - ', csnd.endtime) coursedatetime,
					concat(getTextLabel(ca.label, '$language'), ' ', if(csnd.iceid != 0, getTextLabel(cai.label, '$language'), '')) courselocation
			FROM cpa_shows_numbers csn
			LEFT JOIN cpa_shows_numbers_dates csnd ON csnd.id = $showsnumbersdatesid
			JOIN cpa_arenas ca ON ca.id = csnd.arenaid
			LEFT JOIN cpa_arenas_ices cai ON cai.id = csnd.iceid
			WHERE csn.id = $showsnumbersid";
	$result = $mysqli->query($query);
	while ($row = $result->fetch_assoc()) {
		$data['coursedetails'] = $row;
	}
	$data['success'] = true;
	return $data;
	exit;
};

/**
 * This function gets the details of a course attendance
 */
function getCourseAttendanceDetails($mysqli, $eventtype, $sessionscoursesid, $sessionscoursesdatesid, $language)
{
	$data = array();
	try {
		if ($eventtype == 1) {
			$data = getCourseAttendanceDetailsInt($mysqli, $sessionscoursesid, $sessionscoursesdatesid, $language);
		} else if ($eventtype == 2) {
			$data = getNumberAttendanceDetailsInt($mysqli, $sessionscoursesid, $sessionscoursesdatesid, $language);
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
};

function getCourseDate($mysqli, $sessionscoursesid, $type, $language)
{
	$data = array();
	$data['data'] = array();
	if ($type == 1) {
		$query = "SELECT cscd.id, concat(cscd.coursedate, '@', cscd.starttime, ' ', getTextLabel(ca.label, '$language'), ' ', if(cscd.iceid != 0, getTextLabel(cai.label, '$language'), '')) text						
				FROM cpa_sessions_courses_dates cscd
				JOIN cpa_arenas ca ON ca.id = cscd.arenaid
				LEFT JOIN cpa_arenas_ices cai ON cai.id = cscd.iceid
				WHERE cscd.sessionscoursesid = $sessionscoursesid
				AND cscd.coursedate >= (CURDATE() - INTERVAL 1 DAY) AND cscd.coursedate < (CURDATE() + INTERVAL 1 DAY)
				ORDER BY cscd.coursedate, cscd.starttime";
	} else if ($type == 2) {
		$query = "SELECT csnd.id, concat(csnd.practicedate, ' ', csnd.starttime, ' - ', csnd.endtime, ' ', getTextLabel(ca.label, '$language'), ' ', if(csnd.iceid != 0, getTextLabel(cai.label, '$language'), '')) text
				FROM cpa_shows_numbers_dates csnd
				JOIN cpa_arenas ca ON ca.id = csnd.arenaid
				LEFT JOIN cpa_arenas_ices cai ON cai.id = csnd.iceid
				WHERE csnd.numberid = $sessionscoursesid
				AND csnd.practicedate >= (CURDATE() - INTERVAL 1 DAY) AND csnd.practicedate < (CURDATE() + INTERVAL 1 DAY)
				ORDER BY csnd.practicedate, csnd.starttime";
	}
	$result = $mysqli->query($query);
	while ($row = $result->fetch_assoc()) {
		$data['data'][] = $row;
	}
	$data['success'] = true;
	return $data;
	exit;
};

/**
 * This function gets the list of courses/numbers with a course/practice date within a one day range from today
 */
function getCoursesList($mysqli, $language)
{
	try {
		$query = "SELECT distinct csc.id, csc.name,  getTextLabel(csc.label, '$language') label, cscd.coursedate, 1 as type
				FROM cpa_sessions_courses csc
				JOIN cpa_sessions cs ON cs.id = csc.sessionid
				JOIN cpa_sessions_courses_dates cscd ON cscd.sessionscoursesid = csc.id
				JOIN cpa_courses cc ON cc.code = csc.coursecode
				WHERE cs.active = 1
				AND cscd.coursedate >= (CURDATE() - INTERVAL 1 DAY) AND cscd.coursedate < (CURDATE() + INTERVAL 1 DAY)
				AND cc.acceptregistrations = 1
				UNION
				SELECT distinct csc.id, csc.name,  getTextLabel(csc.label, '$language') label, csnd.practicedate as coursedate, 2 as type
				FROM cpa_shows_numbers csc
				JOIN cpa_shows cs ON cs.id = csc.showid
				JOIN cpa_shows_numbers_dates csnd ON csnd.numberid = csc.id
				WHERE csnd.practicedate >= (CURDATE() - INTERVAL 1 DAY) AND csnd.practicedate < (CURDATE() + INTERVAL 1 DAY)
				ORDER BY 5, 4, 2";
		$result = $mysqli->query($query);
		$data = array();
		$data['data'] = array();
		while ($row = $result->fetch_assoc()) {
			$row['type'] = (int)$row['type'];
			$row['dates'] = getCourseDate($mysqli, $row['id'], $row['type'], $language)['data'];
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
