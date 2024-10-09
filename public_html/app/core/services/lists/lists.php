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
		case "getCoaches":
			getCoaches($mysqli, $_POST['language']);
			break;
		case "getPartners":
			getPartners($mysqli, $_POST['language']);
			break;
		case "getAllArenaRooms":
			getAllArenaRooms($mysqli, $_POST['arenaid'], $_POST['iceid'], $_POST['language']);
			break;
		case "getAllCoaches":
			getAllCoaches($mysqli, $_POST['language']);
			break;
		case "getAllTestDirectors":
			getAllTestDirectors($mysqli, $_POST['language']);
			break;
		case "getAllSessionsAndShows":
			getAllSessionsAndShows($mysqli, $_POST['language']);
			break;
		case "getAllTestSessions":
			getAllTestSessions($mysqli, $_POST['language']);
			break;
		case "getTestPeriodsForSession":
			getTestPeriodsForSession($mysqli, $_POST['testsessionid'], $_POST['language']);
			break;
		case "getAllJudges":
			getAllJudges($mysqli, $_POST['language']);
			break;
		case "getAllJudgesForPeriod":
			getAllJudgesForPeriod($mysqli, $_POST['day'], $_POST['language']);
			break;
		case "getAllProgramAssistants":
			getAllProgramAssistants($mysqli, $_POST['language']);
			break;
		case "getAllProgramAssistantHelpers":
			getAllProgramAssistantHelpers($mysqli, $_POST['language']);
			break;
		case "getAllCourses":
			getAllCourses($mysqli, $_POST['language']);
			break;
		case "getAllCoursesForRules":
			getAllCoursesForRules($mysqli, $_POST['language']);
			break;
		case "getAllCourseLevels":
			getAllCourseLevels($mysqli, $_POST['coursecode'], $_POST['language']);
			break;
		case "getAllCanskateids":
			getAllCanskateids($mysqli, $_POST['language']);
			break;
		case "getAllCanskateTests":
			getAllCanskateTests($mysqli, $_POST['language']);
			break;
		case "getAllTestsDefinitions":
			getAllTestsDefinitions($mysqli, $_POST['language']);
			break;
		case "getAllSessions":
			getAllSessions($mysqli, $_POST['language']);
			break;
		case "getAllSessionsEx":
			getAllSessionsEx($mysqli, $_POST['language'], $_POST['exception']);
			break;
		case "getActiveSession":
			getActiveSession($mysqli, $_POST['language']);
			break;
		case "getAllTests":
			getAllTests($mysqli, $_POST['testtype'], $_POST['language']);
			break;
		case "getAllStarTests":
			getAllStarTests($mysqli, $_POST['testtype'], $_POST['language']);
			break;
		case "getAllTestsEx":
			getAllTestsEx($mysqli, $_POST['language']);
			break;
		case "getAllTestsForMember":
			getAllTestsForMember($mysqli, $_POST['testtype'], $_POST['memberid'], $_POST['language']);
			break;
		case "getAllStarTestsForMember":
			getAllStarTestsForMember($mysqli, $_POST['testtype'], $_POST['memberid'], $_POST['language']);
			break;
		case "getAllTestLevelsByType":
			getAllTestLevelsByType($mysqli, $_POST['testtype'], $_POST['language']);
			break;
		case "getAllPrivileges":
			getAllPrivileges($mysqli, $_POST['language']);
			break;
		case "getAllRoles":
			getAllRoles($mysqli, $_POST['language']);
			break;
		case "getAllActiveCourses":
			getAllActiveCourses($mysqli, $_POST['language']);
			break;
		case "getAllSessionCourses":
			getAllSessionCourses($mysqli, $_POST['sessionid'], $_POST['language']);
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
		case "getAllDanceMusics":
			getAllDanceMusics($mysqli, $_POST['language']);
			break;
		case "getAllClubs":
			getAllClubs($mysqli, $_POST['language']);
			break;
		case "getAllWsDocuments":
			getAllWsDocuments($mysqli, $_POST['language']);
			break;
		case "getWsSectionsForPage":
			getWsSectionsForPage($mysqli, $_POST['pagename'], $_POST['language']);
			break;
		case "getAllWsPages":
			getAllWsPages($mysqli, $_POST['language']);
			break;
		case "getMemberEmails":
			getMemberEmails($mysqli, $_POST['language'], $_POST['memberid']);
			break;
		case "getAllPages":
			getAllPages($mysqli, $_POST['language']);
			break;
		case "getAllShowTasks":
			getAllShowTasks($mysqli, $_POST['language']);
			break;
		case "getAllEmailTemplates":
			getAllEmailTemplates($mysqli, $_POST['language']);
			break;
		default:
			invalidRequest();
	}
} else {
	invalidRequest();
};

/**
 * This function gets all privileges from database
 */
function getAllPrivileges($mysqli, $language) {
	try {
		$query = "SELECT id, concat(code, ' - ', description) text
							FROM cpa_privileges
							order by id";
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
 * This function gets all ws pages from database
 */
function getAllPages($mysqli, $language) {
	try {
		$query = "SELECT *, getWSTextLabel(cws.navbarlabel, 'fr-ca') navbarlabel_fr, getWSTextLabel(cws.navbarlabel, 'en-ca') navbarlabel_en,
											getWSTextLabel(navbarlabel, '$language') navbarlabeltext, getWSTextLabel(label, '$language') labeltext
							FROM cpa_ws_pages cws
							ORDER BY pageindex";
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
 * This function gets all roles from database
 */
function getAllRoles($mysqli, $language) {
	try {
		$query = "SELECT id, concat(roleid, ' - ', rolename)  text
							FROM cpa_roles
							order by id";
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
 * This function gets all tests for one type from database
 */
function getAllTests($mysqli, $testtype, $language) {
	try {
		$query = "SELECT ct.id, getTextLabel(label, '$language') text
							FROM cpa_tests ct
							JOIN cpa_tests_definitions ctd ON ctd.id = ct.testsdefinitionsid
							WHERE ctd.type = '$testtype'
							AND ctd.version = 1
							order by ct.sequence";
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
 * This function gets all STAR tests for one type from database
 */
function getAllStarTests($mysqli, $testtype, $language) {
	try {
		$query = "SELECT ct.id, getTextLabel(label, '$language') text
							FROM cpa_tests ct
							JOIN cpa_tests_definitions ctd ON ctd.id = ct.testsdefinitionsid
							WHERE ctd.type = '$testtype'
							AND ctd.version = 2
							order by ct.sequence";
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
 * This function gets all tests for all types from database
 */
function getAllTestsEx($mysqli, $language) {
	try {
		$query = "SELECT ct.id, getTextLabel(label, '$language') text
							FROM cpa_tests ct
							order by ct.sequence";
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
 * This function gets all tests for one test type that the member has not passed yet from database
 */
function getAllTestsForMember($mysqli, $testtype, $memberid, $language) {
	try {
		$query = "SELECT ct.id, getTextLabel(label, '$language') text
							FROM cpa_tests ct
							JOIN cpa_tests_definitions ctd ON ctd.id = ct.testsdefinitionsid
							WHERE ctd.type = '$testtype'
							AND ctd.version = 1
              AND NOT EXISTS (SELECT testid FROM cpa_members_tests cmt WHERE cmt.testid = ct.id AND success in (1,5) AND memberid = $memberid)
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
 * This function gets all tests levels for one test type
 */
function getAllTestLevelsByType($mysqli, $testtype, $language) {
	try {
		$query = "SELECT code, getTextLabel(description, '$language') text
							FROM cpa_codetable cc
							WHERE cc.ctname = 'testlevels'
							AND cc.code IN (SELECT level FROM cpa_tests_definitions WHERE type = '$testtype' AND version = 1)
							ORDER BY cc.sequence";
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
 * This function gets all coaches from database
 */
function getCoaches($mysqli, $language) {
	try {
		$query = "SELECT id, concat(lastname, ', ', firstname) text
							FROM cpa_members
							where qualifications like '%COACH%'
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
 * This function gets all coaches, partners and choreographers from database
 */
function getAllCoaches($mysqli, $language) {
	try {
		$query = "SELECT id, concat(lastname, ', ', firstname) text
							FROM cpa_members
							where qualifications like '%COACH%' or qualifications like '%PARTNER%' or qualifications like '%CHOR%'
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
 * This function gets all test directors from database
 */
function getAllTestDirectors($mysqli, $language) {
	try {
		$query = "SELECT id, concat(lastname, ', ', firstname) text
							FROM cpa_members
							where qualifications like '%dir%'
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
 * This function gets all test sessions from database
 */
function getAllTestSessions($mysqli, $language) {
	try {
		$query = "SELECT id,  getTextLabel(label, '$language') text
							FROM cpa_tests_sessions
							order by registrationstartdate DESC";
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
 * This function gets all test sessions from database
 */
function getTestPeriodsForSession($mysqli, $testsessionid, $language) {
	try {
		$query = "SELECT ctsdp.id, concat(ctsd.testdate, ' ', getTextLabel(ca.label, '$language'), ' ', if(ctsdp.iceid != 0, getTextLabel(cai.label, '$language'), ''), ' ', ctsdp.starttime, ' - ', ctsdp.endtime) text
							FROM cpa_tests_sessions_days_periods ctsdp
							JOIN cpa_tests_sessions_days ctsd ON ctsd.id = ctsdp.testsdaysid
							JOIN cpa_arenas ca ON ca.id = ctsdp.arenaid
							LEFT JOIN cpa_arenas_ices cai ON cai.id = ctsdp.iceid
							WHERE ctsd.testssessionsid = $testsessionid
							ORDER BY starttime";
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
 * This function gets all judges from database
 */
function getAllJudgesForPeriod($mysqli, $day, $language) {
	try {
		$query = "SELECT ctdj.id, concat(lastname, ', ', firstname) text
							FROM cpa_tests_sessions_days_periods_judges ctdj
							JOIN cpa_members cm ON cm.id = ctdj.judgesid
							WHERE ctdj.testsdaysid = $day
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
 * This function gets all judges from database
 */
function getAllJudges($mysqli, $language) {
	try {
		$query = "SELECT id, concat(lastname, ', ', firstname) text
							FROM cpa_members
							where qualifications like '%jud%'
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
 * This function gets all program asistants from database
 */
function getAllProgramAssistants($mysqli, $language) {
	try {
		$query = "SELECT id, concat(lastname, ', ', firstname) text
							FROM cpa_members
							where qualifications like '%pa,%'
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
 * This function gets all program asistant helpers from database
 */
function getAllProgramAssistantHelpers($mysqli, $language) {
	try {
		$query = "SELECT id, concat(lastname, ', ', firstname) text
							FROM cpa_members
							where qualifications like '%pah,%'
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
 * This function gets all courses from database
 */
function getAllCourses($mysqli, $language) {
	try {
		$query = "SELECT code, getTextLabel(label, '$language') label
							FROM cpa_courses
							order by label";
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
 * This function gets all courses from database and adds the fake SHOWNUMBER to the list
 */
function getAllCoursesForRules($mysqli, $language) {
	$data = array();
	try {
		$query = "SELECT code, getTextLabel(label, '$language') label
							FROM cpa_courses
							UNION
							SELECT 'SHOWNUMBER' as code, if ('$language' = 'fr-ca',  'Numero de spectacle (interne)',  'Show number (Internal)') as label
							FROM cpa_courses
							order by label";
		$result = $mysqli->query( $query );
		while ($row = $result->fetch_assoc()) {
			$data['data'][] = $row;
		}
		$toto = array();
//		$toto['code'] = "toto";
//		$toto['label'] = ($language == "fr-ca"? "Num�ro de spectacle" : "Show number");
//		$data['data'][] =  $toto;
		$data['success'] = true;
		echo json_encode($data);
		exit;
	} catch (Exception $e) {
		$data['success'] = false;
		$data['message'] = $e->getMessage();
		echo json_encode($data);
		exit;
	}
};


/**
 * This function gets all courses from database
 */
function getAllCourseLevels($mysqli, $coursecode, $language) {
	try {
		$query = "SELECT code, getTextLabel(label, '$language') label
							FROM cpa_courses_levels
							WHERE coursecode = '$coursecode'";
		$result = $mysqli->query( $query );
		$data = array();
		$data['data'] = array();
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
 * This function gets all test definitions from database
 */
function getAllTestsDefinitions($mysqli, $language) {
	try {
		$query = "SELECT id, convert(concat(getCodeDescription('testtypes', type, '$language'), '/',
															  getCodeDescription('testlevels', level, '$language'),
       				                  if(subtype != '', concat('/',  getCodeDescription('testsubtypes', subtype, '$language')), '')) using utf8) description,
                                type, subtype, level, version
							FROM cpa_tests_definitions
							WHERE version = 1
							UNION
							SELECT id, concat(getCodeDescription('testtypes', type, '$language'), '/STAR ', level) description, type, subtype, level, version
							FROM cpa_tests_definitions
							WHERE version = 2
							ORDER BY version, type, subtype, cast(level as DECIMAL)";
		$result = $mysqli->query( $query );
		$data = array();
		while ($row = $result->fetch_assoc()) {
			$row['id'] = (int) $row['id'];
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
 * This function gets the canSkate ids from database
 */
function getAllCanskateids($mysqli, $language) {
	try {
		$data = array();
		$query = "SELECT id, category, stage
							FROM cpa_canskate
							order by category, stage";
		$result = $mysqli->query( $query );
		while ($row = $result->fetch_assoc()) {
			$row['id'] = (int) $row['id'];
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
 * This function gets all the tests for CanSkate from database
 */
function getAllCanskateTests($mysqli, $language) {
	try {
		$data = array();
		$query = "SELECT id, category, stage, getCodeDescription('canskatetestcategories', category, '$language') label
							FROM cpa_canskate
							order by category, stage";
		$result = $mysqli->query( $query );
		while ($row = $result->fetch_assoc()) {
			$row['id'] = (int) $row['id'];
			$row['tests'] = getAllCanskateTestDef($mysqli, $row['id'], $language);
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
 * This function gets the sessions from database
 */
function getAllSessions($mysqli, $language) {
	try {
		$data = array();
		$query = "SELECT id, concat(getTextLabel(label, '$language'), if(active=1, ' (active)', '')) label, coursesstartdate, coursesenddate, active, getTextLabel(label, '$language') origlabel
							FROM cpa_sessions cs
							order by active DESC, startdate DESC";
		$result = $mysqli->query( $query );
		while ($row = $result->fetch_assoc()) {
			$row['id'] = (int) $row['id'];
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
 * This function gets the sessions from database, except the exception
 */
function getAllSessionsEx($mysqli, $language, $exception) {
	try {
		$data = array();
		$where = " WHERE 1=1 ";
		if (isset($exception) && !empty($exception)) {
			$where = " WHERE id != $exception ";
		}
		$query = "SELECT id, getTextLabel(label, '$language') label
							FROM cpa_sessions cs" . $where .
							"order by startdate DESC";
		$result = $mysqli->query( $query );
		while ($row = $result->fetch_assoc()) {
			$row['id'] = (int) $row['id'];
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
 * This function gets the active session from database
 */
function getActiveSession($mysqli, $language) {
	try {
		$data = array();
		$query = "SELECT id, name, getTextLabel(label, '$language') label
							FROM cpa_sessions cs
							WHERE active = 1";
		$result = $mysqli->query( $query );
		while ($row = $result->fetch_assoc()) {
//			$row['id'] = (int) $row['id'];
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
 * This function gets all the courses for the given session
 */
function getAllSessionCourses($mysqli, $sessionid, $language) {
	try {
		$data = array();
		$query = "SELECT csc.id, concat(concat(csc.name, ' - '), getTextLabel(csc.label, '$language')) text
							FROM cpa_sessions_courses csc
							WHERE csc.sessionid = $sessionid";
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
 * This function gets all the active courses, i.e. courses for the active session
 */
function getAllActiveCourses($mysqli, $language) {
	try {
		$data = array();
		$query = "SELECT csc.id, csc.name, getTextLabel(csc.label, '$language') label
							FROM cpa_sessions_courses csc
							JOIN cpa_sessions cs ON cs.id = csc.sessionid
							WHERE cs.active = 1";
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
 * This function gets all the musics
 */
function getAllDanceMusics($mysqli, $language) {
	try {
		$data = array();
		$query = "SELECT cm.id, concat(cm.song, ' - ', cm.author) label
							FROM cpa_musics cm";
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
 * This function gets all the documents declared for the web site
 */
function getAllWsDocuments($mysqli, $language) {
	try {
		$data = array();
		$query = "SELECT id, documentname
							FROM cpa_ws_documents cwd
							WHERE getWsTextLabel(filename, '$language') != ''
							AND publish = 1
							ORDER BY cwd.publishon DESC";
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
 * This function gets all the pages for the web site
 */
function getAllWsPages($mysqli, $language) {
	try {
		$data = array();
		$query = "SELECT cwp.name
							FROM cpa_ws_pages cwp
							ORDER BY cwp.name";
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

/**
 * This function gets all the tasks for a show
 */
function getAllShowTasks($mysqli, $language) {
	try {
		$data = array();
		$query = "SELECT cst.*, getTextLabel(label, '$language') text
							FROM cpa_shows_tasks cst
							JOIN cpa_codetable cct on cct.ctname = 'taskcategories' and cct.code = cst.category
							WHERE cst.active = 1
							ORDER BY cct.sequence, cst.id";
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

function getAllEmailTemplates($mysqli, $language) {
	try {
		$query = "SELECT id, templatename
							FROM cpa_emails_templates
							where active = 1
							order by id DESC";
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

function invalidRequest() {
	$data = array();
	$data['success'] = false;
	$data['message'] = "Invalid request.";
	echo json_encode($data);
	exit;
};

?>
