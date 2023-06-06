<?php
/*
Author : Eric Lamoureux
*/
require_once('../../../private/'. $_SERVER['HTTP_HOST'].'/include/config.php');
require_once('../../include/nocache.php');

if(isset($_POST['type']) && !empty(isset($_POST['type']))){
	$type = $_POST['type'];

	switch ($type) {
		case "updateMemberTest":
			updateMemberTest($mysqli, $_POST['member'], $_POST['test']);
			break;
		case "getCourseElementDetails":
			getCourseElementDetails($mysqli, $_POST['sessionscoursesid'], $_POST['sublevelcode'], $_POST['canskatetestid'], $_POST['language']);
			break;
		default:
			invalidRequest();
	}
} else {
	invalidRequest();
};

/**
 * This function will handle update/delete of a test result in the DB. Always delete first, insert if success is true
 * @throws Exception
 */
function updateMemberTest($mysqli, $member, $test) {
	$data = array();
	try {
		$memberid 								=	$mysqli->real_escape_string(isset($member['memberid']) 								? $member['memberid'] : '');
		$canskatetestid 					=	$mysqli->real_escape_string(isset($test['canskatetestid']) 						? $test['canskatetestid'] : '');
		$membercanskatetestid 		=	$mysqli->real_escape_string(isset($test['id']) 												? $test['id'] : '');
		$success 									=	$mysqli->real_escape_string(isset($test['success']) 									? $test['success'] : '');

		$data['success'] = false;
		if (!empty($memberid) && !empty($canskatetestid) && !empty($success)) {
			$query = "DELETE FROM cpa_members_canskate_tests WHERE memberid = $memberid and canskatetestid = $canskatetestid";
			if ($mysqli->query($query)) {
				if ($success == 'true') {
					$query = "INSERT INTO cpa_members_canskate_tests (id, memberid, canskatetestid, testdate, success) VALUES (null, $memberid, $canskatetestid, curdate(), $success)";
					if (!$mysqli->query($query)) {
						throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
					}
				}
				// Once the update is done, we need to recount the number of tests passed for this member to update UI
				$data['memberid'] = $memberid;
				$data['testscount'] = getMemberCanskateTestsCount($mysqli, $memberid, $canskatetestid);
				$data['success'] = true;
			} else {
				throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
			}
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


/**
 * This function gets the number of success for tests of the same type of one member from the database
 */
function getMemberCanskateTestsCount($mysqli, $memberid, $canskatetestid){
	$data = 0;
	if(empty($memberid)) throw new Exception("Invalid Member ID.");
	$query = "SELECT count(*) cnt
						FROM cpa_members_canskate_tests cmcst
						WHERE memberid = $memberid
						AND cmcst.canskatetestid in (SELECT id
						                             FROM cpa_canskate_tests cct 
						                             WHERE cct.canskateid = (SELECT canskateid FROM cpa_canskate_tests cct2 WHERE cct2.id = $canskatetestid)
						                             AND type = 'TEST')";
	$result = $mysqli->query($query);
	while ($row = $result->fetch_assoc()) {
		$data = $row['cnt'];
	}
	return $data;
};

/**
 * This function gets the ribbon date for one type of test of one member from the database
 */
function getMemberCanskateRibbonDate($mysqli, $memberid, $canskatetestid){
	$data = array();
	$data['ribbondatetestsucces'] = false;
	$data['memberid'] = $memberid;
	$data['canskatetestid'] = $canskatetestid;
	if(empty($memberid)) throw new Exception("Invalid Member ID.");
	$query = "SELECT ribbondate
						FROM cpa_members_canskate_ribbons cmcr
						WHERE memberid = $memberid
						AND cmcr.canskateid = (SELECT canskateid FROM cpa_canskate_tests cct2 WHERE cct2.id = $canskatetestid)";
	$result = $mysqli->query($query);
	while ($row = $result->fetch_assoc()) {
		$data = $row['ribbondate'];
	}
	return $data;
};

/**
 * This function gets one canskate element (and sub elements) of one member from the database
 */
function getMemberCanskateTests($mysqli, $memberid, $canskatetestid, $language){
	if(empty($memberid)) throw new Exception("Invalid Member ID.");
	$query = "SELECT ccst.id canskatetestid, success, ccst.canskateid, ccst.type, cmcst.id as id, getTextLabel(ccst.label, '$language') label
						FROM cpa_canskate_tests ccst
						left join cpa_members_canskate_tests cmcst on cmcst.canskatetestid = ccst.id AND (memberid = '$memberid' || memberid is null)
						left join cpa_canskate ccs on ccs.id = ccst.canskateid
						WHERE (ccst.id = $canskatetestid OR ccst.relatedtestid = $canskatetestid)
						ORDER BY ccst.sequence";
	$result = $mysqli->query($query);
	$data = array();
	while ($row = $result->fetch_assoc()) {
		$data[] = $row;
	}
	return $data;
};

/**
 * This function gets one canskate element (and sub elements) of one member from the database
 */
function getCourseMembersDetails($mysqli, $sessionscoursesid, $sublevelcode, $canskatetestid, $language){
		$query = "SELECT cscm.memberid, cm.firstname, cm.lastname, cscm.registrationstartdate, cscm.registrationenddate
							FROM cpa_sessions_courses_members cscm
							JOIN cpa_members cm ON cm.id = cscm.memberid
							WHERE cscm.sessionscoursesid = $sessionscoursesid
							AND ('$sublevelcode' = '' OR cscm.sublevelcode = '$sublevelcode')
							AND ((cscm.registrationstartdate is null OR cscm.registrationstartdate < curdate()) AND (cscm.registrationenddate is null OR cscm.registrationenddate > curdate()))
							ORDER BY cm.lastname, cm.firstname";
		$result = $mysqli->query($query);
		$data = array();
		$data['data'] = array();
		$data['data'] = array();
		while ($row = $result->fetch_assoc()) {
			$row['tests'] 		 = getMemberCanskateTests($mysqli, $row['memberid'], $canskatetestid, $language);
			$row['testscount'] = getMemberCanskateTestsCount($mysqli, $row['memberid'], $canskatetestid);
			$row['ribbondate'] = getMemberCanskateRibbonDate($mysqli, $row['memberid'], $canskatetestid);
			$data['data'][] = $row;
		}
	return $data;
};


/**
 * This function gets the members of a course (and optionnaly a sub group) with their results for a canskate test id
 */
function getCourseElementDetails($mysqli, $sessionscoursesid, $sublevelcode, $canskatetestid, $language) {
	$data['data'] = array();
	try{
		$query = "SELECT cc.*, (select count(*) FROM cpa_canskate_tests cct2 WHERE cct2.canskateid = cc.id AND type = 'TEST') maxnboftests
							FROM cpa_canskate cc
							JOIN cpa_canskate_tests cct ON cct.canskateid = cc.id
							WHERE cct.id = $canskatetestid";
		$result = $mysqli->query($query);
//		$data = array();
//		$data['data'] = array();
//		$data['data']['details'] = array();
		while ($row = $result->fetch_assoc()) {
			$data['data']['details'] = $row;
		}
		$data['data']['members'] = getCourseMembersDetails($mysqli, $sessionscoursesid, $sublevelcode, $canskatetestid, $language)['data'];
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


function invalidRequest(){
	$data = array();
	$data['success'] = false;
	$data['message'] = "Invalid request.";
	echo json_encode($data);
	exit;
};

?>
