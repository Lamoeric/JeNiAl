<?php
/*
Author : Eric Lamoureux
*/
require('../../../../../private/'. $_SERVER['HTTP_HOST'].'/include/config.php');
require('../../../../include/nocache.php');

if( isset($_POST['type']) && !empty( isset($_POST['type']) ) ) {
	$type = $_POST['type'];

	switch ($type) {
		case "getAllCharges":
			getAllCharges($mysqli, $_POST['language'], $_POST['includesystem'], $_POST['includenonactive']);
			break;
		case "getAllArenaRooms":
			getAllArenaRooms($mysqli, $_POST['arenaid'], $_POST['iceid'], $_POST['language']);
			break;
		case "getAllSessionsAndShows":
			getAllSessionsAndShows($mysqli, $_POST['language']);
			break;
		case "getAllTestsForMember":
			getAllTestsForMember($mysqli, $_POST['testtype'], $_POST['memberid'], $_POST['language']);
			break;
		case "getAllStarTestsForMember":
			getAllStarTestsForMember($mysqli, $_POST['testtype'], $_POST['memberid'], $_POST['language']);
			break;
		case "getAllActiveCoursesWithSubGroups":
			getAllActiveCoursesWithSubGroups($mysqli, $_POST['language']);
			break;
		case "getRangeCourseDates":
			getRangeCourseDates($mysqli, $_POST['sessionscoursesid'], $_POST['language']);
			break;
		case "getDanceMusics":
			getDanceMusics($mysqli, $_POST['testsid'], $_POST['language']);
			break;
		case "getWsSectionsForPage":
			getWsSectionsForPage($mysqli, $_POST['pagename'], $_POST['language']);
			break;
		case "getMemberEmails":
			getMemberEmails($mysqli, $_POST['language'], $_POST['memberid']);
			break;
		case "getSimpleListPattern1":
			getSimpleListPattern1($mysqli, $_POST['query']);
			break;
		default:
			invalidRequest();
	}
} else {
	invalidRequest();
};

/**
 * This function gets all info from the database for a specific pattern based on the parameters passed to the function
 * Basicaly, it just executes the query and returns the values.
 * Always return a array. Array can be empty.
 */
function getSimpleListPattern1($mysqli, $query)
{
	$data = array();
	$data['data'] = array();
	try {
		$result = $mysqli->query($query);
		while ($row = $result->fetch_assoc()) {
			if (isset($row['id'])) {
				$row['id'] = (int)$row['id'];
			}
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
 * This function gets all tests for one test type that the member has not passed yet from database
 */
function getAllTestsForMember($mysqli, $testtype, $memberid, $language) {
	try {
		$data = array();
		$data['data'] = array();
		$query = "SELECT ct.id, getTextLabel(label, '$language') text
							FROM cpa_tests ct
							JOIN cpa_tests_definitions ctd ON ctd.id = ct.testsdefinitionsid
							WHERE ctd.type = '$testtype'
							AND ctd.version = 1
              AND NOT EXISTS (SELECT testid FROM cpa_members_tests cmt WHERE cmt.testid = ct.id AND success in (1,5) AND memberid = $memberid)
							ORDER BY ct.sequence";
		$result = $mysqli->query( $query );
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
 * This function gets all STAR tests for one test type that the member has not passed yet from database
 */
function getAllStarTestsForMember($mysqli, $testtype, $memberid, $language) {
	try {
		$query = "SELECT ct.id, getTextLabel(label, '$language') text
							FROM cpa_tests ct
							JOIN cpa_tests_definitions ctd ON ctd.id = ct.testsdefinitionsid
							WHERE ctd.type = '$testtype'
							AND ctd.version = 2
              AND NOT EXISTS (SELECT testid FROM cpa_members_tests cmt WHERE cmt.testid = ct.id AND success not in (1,2) AND memberid = $memberid)
							ORDER BY ct.sequence";
		$result = $mysqli->query( $query );
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
 * This function gets all partners from database
 */
function getPartners($mysqli, $language) {
	try {
		$query = "SELECT id, concat(lastname, ', ', firstname) text
							FROM cpa_members
							where qualifications like '%PARTNER%'
							order by concat(lastname, ', ', firstname)";
		$result = $mysqli->query( $query );
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
 * This function gets all sessions and shows from the database
 */
function getAllSessionsAndShows($mysqli, $language) {
	try {
		$query = "SELECT id,  getTextLabel(label, '$language') text, 1 as type, cs.active, cs.coursesenddate
							FROM cpa_sessions cs
							UNION
							SELECT id,  getTextLabel(label, '$language') text, 2, cs.active, null
							FROM cpa_shows cs
							order by 4 DESC, 3, 1 DESC";
		$result = $mysqli->query( $query );
		$data = array();
		while ($row = $result->fetch_assoc()) {
			$row['id'] = (int)$row['id'];
			$row['type'] = (int)$row['type'];
			$row['active'] = (int)$row['active'];
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
 * This function gets all charges from database
 */
function getAllCharges($mysqli, $language, $includesystem, $includenonactive) {
	try {
		$query = "SELECT code, getTextLabel(label, '$language') label FROM cpa_charges WHERE 1=1 ";
		if (!$includesystem) {
			$query .= " AND issystem = 0 ";
		}
		if (!$includenonactive) {
			$query .= " AND active = 1 ";
		}
		$query .= " ORDER BY label ";
		$result = $mysqli->query( $query );
		$data = array();
		while ($row = $result->fetch_assoc()) {
			$data['data'][] = $row;
		}
		$data['success'] = true;
		$data['includesystem'] = $includesystem;
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
 * This function gets all the tests for CanSkate from database
 */
function getAllCanskateSubTestDef($mysqli, $relatedtestid, $language) {
	$data = array();
	$query = "SELECT id, type, sequence, name, getTextLabel(label, '$language') label
						FROM cpa_canskate_tests
						WHERE relatedtestid = $relatedtestid
						AND type = 'SUBTEST'
						ORDER BY sequence";
	$result = $mysqli->query( $query );
	while ($row = $result->fetch_assoc()) {
		$row['id'] = (int) $row['id'];
		$data[] = $row;
	}
	return $data;
	exit;
};

/**
 * This function gets all the tests for CanSkate from database
 */
function getAllCanskateTestDef($mysqli, $canskateid, $language) {
	$data = array();
	$query = "SELECT id, type, sequence, name, getTextLabel(label, '$language') label
						FROM cpa_canskate_tests
						WHERE canskateid = $canskateid
						AND type = 'TEST'
						ORDER BY sequence";
	$result = $mysqli->query( $query );
	while ($row = $result->fetch_assoc()) {
		$row['id'] = (int) $row['id'];
		$row['subtests'] = getAllCanskateSubTestDef($mysqli, $row['id'], $language);
		$data[] = $row;
	}
	return $data;
	exit;
};

/**
 * This function gets all the subgroups for a course
 */
function getCoursesSubGroups($mysqli, $sessionscoursesid, $language) {
	$data = array();
	$query = "SELECT cscs.id, cscs.code, getTextLabel(cscs.label, '$language') label
						FROM cpa_sessions_courses_sublevels cscs
						WHERE cscs.sessionscoursesid = $sessionscoursesid
						ORDER BY cscs.sequence";
	$result = $mysqli->query( $query );
	while ($row = $result->fetch_assoc()) {
		$data[] = $row;
	}
	return $data;
};

/**
 * This function gets all the active courses, i.e. courses for the active session, with their subgroups
 */
function getAllActiveCoursesWithSubGroups($mysqli, $language) {
	try {
		$data = array();
		$query = "SELECT csc.id, csc.name, getTextLabel(csc.label, '$language') label
							FROM cpa_sessions_courses csc
							JOIN cpa_sessions cs ON cs.id = csc.sessionid
							JOIN cpa_courses cc ON cc.code = csc.coursecode
							WHERE cs.active = 1
							AND cc.acceptregistrations = 1";
		$result = $mysqli->query( $query );
		while ($row = $result->fetch_assoc()) {
			$row['subgroups'] = getCoursesSubGroups($mysqli, $row['id'], $language);
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
 * This function gets all the dates for a course between today - 1 and today + 1 day
 */
function getRangeCourseDates($mysqli, $sessionscoursesid, $language) {
	try {
		$data = array();
		$query = "SELECT cscd.id,
							concat(cscd.coursedate, ' ', cscd.starttime, ' - ', cscd.endtime, ' ', getTextLabel(ca.label, '$language'), ' ', if(cscd.iceid != 0, getTextLabel(cai.label, '$language'), '')) text
							FROM cpa_sessions_courses_dates cscd
							JOIN cpa_arenas ca ON ca.id = cscd.arenaid
							LEFT JOIN cpa_arenas_ices cai ON cai.id = cscd.iceid
							WHERE cscd.sessionscoursesid = $sessionscoursesid
							AND cscd.coursedate >= (CURDATE() - INTERVAL 1 DAY) AND cscd.coursedate < (CURDATE() + INTERVAL 1 DAY)
							ORDER BY cscd.coursedate, cscd.starttime";
		$result = $mysqli->query( $query );
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
 * This function gets all the musics for a dance
 */
function getDanceMusics($mysqli, $testsid, $language) {
	try {
		$data = array();
		$query = "SELECT cm.id, concat(cm.song, ' - ', cm.author) label
							FROM cpa_musics cm
							JOIN cpa_tests_musics ctm ON ctm.musicsid = cm.id
							WHERE ctm.testsid = '$testsid'";
		$result = $mysqli->query( $query );
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
 * This function gets all the clubs
 */
function getAllClubs($mysqli, $language) {
	try {
		$data = array();
		$query = "SELECT cc.code, getTextLabel(label, '$language') text
							FROM cpa_clubs cc
							ORDER BY cc.code";
		$result = $mysqli->query( $query );
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
 * This function gets all the visible sections for a given page
 */
function getWsSectionsForPage($mysqli, $pagename, $language) {
	try {
		$data = array();
		$query = "SELECT cwps.sectionname
							FROM cpa_ws_pages_sections cwps
							WHERE cwps.pagename = '$pagename'
							AND cwps.visible = 1
							ORDER BY cwps.sectionname";
		$result = $mysqli->query( $query );
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
 * This function gets all the email addresses for a member. Email addresses includes, in this order, email from contacts and then email from member
 */
function getMemberEmails($mysqli, $language, $memberid) {
	try {
		$data = array();
		$query = "SELECT concat(cm.firstname, ' ', cm.lastname) fullname, cm.email, concat(cm.firstname, ' ', cm.lastname, ' (', cm.email, ')') label, 2 emailtype
							FROM cpa_members cm
							WHERE cm.id = $memberid
							UNION
							SELECT concat(cc.firstname, ' ', cc.lastname) fullname, cc.email, concat(cc.firstname, 	' ', cc.lastname, ' (', cc.email, ')') label, 1 emailtype
							FROM cpa_contacts cc
							WHERE id in (SELECT contactid FROM cpa_members_contacts WHERE memberid  = $memberid)
							order by 4, 1";
		$result = $mysqli->query( $query );
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
 * This function gets all the rooms for an ice in a arena. If iceid is null, get all the rooms for the arena
 */
function getAllArenaRooms($mysqli, $arenaid, $iceid, $language) {
	try {
		$data = array();
		$iceid = $iceid==null || $iceid == 0 ? 0 : $iceid;
		$query = "SELECT *, getTextLabel(label, '$language') roomlabel
							FROM cpa_arenas_ices_rooms car
							WHERE car.arenaid = $arenaid and ($iceid = 0 OR car.iceid = $iceid)
							ORDER BY id";
		$result = $mysqli->query( $query );
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

function invalidRequest() {
	$data = array();
	$data['success'] = false;
	$data['message'] = "Invalid request.";
	echo json_encode($data);
	exit;
};

?>
