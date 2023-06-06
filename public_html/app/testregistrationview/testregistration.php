<?php
/*
Author : Eric Lamoureux
*/
require_once('../../../private/'. $_SERVER['HTTP_HOST'].'/include/config.php');
require_once('../../include/nocache.php');

if (isset($_POST['type']) && !empty(isset($_POST['type']) ) ) {
	$type = $_POST['type'];

	switch ($type) {
		case "insert_testregistration":
			insert_testregistration($mysqli);
			break;
		case "updateEntireTestregistration":
			updateEntireTestregistration($mysqli, $_POST['testregistration']);
			break;
		case "delete_testregistration":
			delete_testregistration($mysqli);
			break;
		case "getAllTestregistrations":
			getAllTestregistrations($mysqli, $_POST['testssessionsid']);
			break;
		case "getTestregistrationDetails":
			getTestregistrationDetails($mysqli, $_POST['id'], $_POST['language']);
			break;
		case "getTestSessionDetails":
			getTestSessionDetails($mysqli, $_POST['id'], $_POST['language']);
			break;
		default:
			invalidRequest();
	}
} else {
	invalidRequest();
};

/**
 * This function returns if the home club of the skater is the same as the home club for the test or if its home club is a friendly club
 */
function getIsSkaterExternal($mysqli, $testssessionsid, $memberid) {
	$retVal = 1;
	$query = "SELECT cm.homeclub skaterhomeclub, cc.friendly, (SELECT cts.homeclub FROM cpa_tests_sessions cts WHERE id = $testssessionsid) testhomeclub
						FROM cpa_members cm
						JOIN cpa_clubs cc ON cc.code = cm.homeclub
						WHERE cm.id = $memberid";
	$result = $mysqli->query($query );
	while ($row = $result->fetch_assoc()) {
		if ($row['skaterhomeclub'] == $row['testhomeclub'] || $row['friendly'] == 1) {
			$retVal = 0;
		}
	}
	return $retVal;
	exit;
};

/**
 * This function will handle testregistration add functionality
 * @throws Exception
 */
function insert_testregistration($mysqli) {
	try {
		$data = array();
		$testssessionsid 			= $mysqli->real_escape_string(isset($_POST['testregistration']['testssessionsid'] )	? $_POST['testregistration']['testssessionsid'] : '');
		$memberid 						= $mysqli->real_escape_string(isset($_POST['testregistration']['member']['id'] ) 		? $_POST['testregistration']['member']['id'] : '');
		$coachid 							= $mysqli->real_escape_string(isset($_POST['testregistration']['coachid'] ) 				? $_POST['testregistration']['coachid'] : '');
		// $extskater 						= $mysqli->real_escape_string(isset($_POST['testregistration']['extskater'] ) 			? $_POST['testregistration']['extskater'] : '');

		if ($testssessionsid == '' || $memberid == '' || $coachid == '') {
			throw new Exception("Required fields missing, Please enter and submit" );
		}

		$extskater = getIsSkaterExternal($mysqli, $testssessionsid, $memberid);
		$query = "INSERT INTO cpa_tests_sessions_registrations (testssessionsid, memberid, coachid, extskater)
							VALUES ('$testssessionsid', '$memberid', $coachid, $extskater)";

		if ($mysqli->query($query ) ) {
			$data['success'] = true;
			$data['id'] = (int) $mysqli->insert_id;
			$data['extskater'] = $extskater;
		} else {
			throw new Exception($mysqli->sqlstate.' - '. $mysqli->error );
		}
		$mysqli->close();
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
 * This function will handle testregistration add, update functionality
 * @throws Exception
 */
function update_testregistration($mysqli, $testregistration) {
	try {
		$data = array();
		$id 							= $mysqli->real_escape_string(isset($testregistration['id'] ) 							? $testregistration['id'] : '');
		$coachid 					= $mysqli->real_escape_string(isset($testregistration['coachid'] ) 					? $testregistration['coachid'] : '');
		$extrafees 				= $mysqli->real_escape_string(isset($testregistration['extrafees'] ) 				? $testregistration['extrafees'] : '');
		$fees 						= $mysqli->real_escape_string(isset($testregistration['fees'] ) 						? $testregistration['fees'] : '');
		$approbreceived 	= $mysqli->real_escape_string(isset($testregistration['approbreceived'] ) 	? $testregistration['approbreceived'] : '');
		$comments 				=	$mysqli->real_escape_string(isset($testregistration['comments'] ) 				? $testregistration['comments'] : '');

		if ($id == '') {
			throw new Exception("Required fields missing, Please enter and submit" );
		}

		$query = "UPDATE cpa_tests_sessions_registrations SET coachid = $coachid, fees =  $fees, extrafees = $extrafees, approbreceived = $approbreceived, comments = '$comments' WHERE id = $id";
		if ($mysqli->query($query ) ) {
			$data['success'] = true;
			$data['message'] = 'Testregistration updated successfully.';
		} else {
			throw new Exception($mysqli->sqlstate.' - '. $mysqli->error );
		}
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
 * This function will handle testregistration deletion
 * @throws Exception
 */
function delete_testregistration($mysqli) {
	try {
		$id = $mysqli->real_escape_string(isset($_POST['testregistration']['id'] ) ? $_POST['testregistration']['id'] : '');
		if (empty($id)) throw new Exception("Invalid Testregistration." );
		$query = "DELETE FROM cpa_tests_sessions_registrations WHERE id = $id";
		if ($mysqli->query($query )) {
			$data['success'] = true;
			$data['message'] = 'Testregistration deleted successfully.';
			echo json_encode($data);
			exit;
		} else {
			throw new Exception($mysqli->sqlstate.' - '. $mysqli->error );
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
 * This function gets list of all testregistrations from database
 */
function getAllTestregistrations($mysqli, $testssessionsid) {
	try {
		$query = "SELECT ctsr.id, memberid, cm.firstname, cm.lastname
							FROM cpa_tests_sessions_registrations ctsr
							JOIN cpa_members cm ON cm.id = ctsr.memberid
							-- JOIN cpa_tests_sessions cts ON cts.id = ctsr.testssessionsid
							-- WHERE curdate() between cts.registrationstartdate and cts.registrationenddate
							WHERE ctsr.testssessionsid = $testssessionsid
							order by cm.lastname";
		$result = $mysqli->query($query );
		$data = array();
		while ($row = $result->fetch_assoc()) {
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
 * This function gets the details of all tests for a testregistration from database
 */
function getTestregistrationTests($mysqli, $testssessionsregistrationsid = '') {
	try {
		$query = "SELECT ctsrt.*, ctd.type testtype
							FROM cpa_tests_sessions_registrations_tests ctsrt
							JOIN cpa_tests ct ON ct.id = ctsrt.testsid
							JOIN cpa_tests_definitions ctd ON ctd.id = ct.testsdefinitionsid
							WHERE testssessionsregistrationsid = $testssessionsregistrationsid
							AND ctd.version = 1";
		$result = $mysqli->query($query );
		$data = array();
		$data['data'] = array();
		while ($row = $result->fetch_assoc()) {
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
 * This function gets the details of one testregistration from database
 */
function getTestregistrationDetails($mysqli, $id, $language) {
	try {
		$query = "SELECT ctsr.*, ctsr.id as testssessionsregistrationsid, cm.firstname, cm.lastname, cm.skatecanadano, cm.homeclub, getTextLabel(cts.label, '$language') as testsessionname
							FROM cpa_tests_sessions_registrations ctsr
							JOIN cpa_tests_sessions cts ON cts.id = ctsr.testssessionsid
							JOIN cpa_members cm ON cm.id = ctsr.memberid
							WHERE ctsr.id = $id";
		$result = $mysqli->query($query );
		$data = array();
		while ($row = $result->fetch_assoc()) {
			$row['tests'] = getTestregistrationTests($mysqli, $row['testssessionsregistrationsid'])['data'];
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
 * This function gets the details of one testsession from database
 */
function getTestSessionDetails($mysqli, $id, $language) {
	try {
		$query = "SELECT cts.*, getCodeDescription('extrafeesoptions', extrafeesoption, '$language') extrafeesoptionlabel
							FROM cpa_tests_sessions cts
							WHERE id = $id";
		$result = $mysqli->query($query );
		$data = array();
		while ($row = $result->fetch_assoc()) {
			$row['periods'] = getTestsessionPeriods($mysqli, $id, $language)['data'];
			$row['charges'] = getTestsessionCharges($mysqli, $id, $language)['data'];
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
 * This function gets the details of all days for a testsession from database
 */
function getTestsessionDays($mysqli, $testssessionsid, $language) {
	try {
		$query = "SELECT id, testssessionsid, testdate testdatestr
							FROM cpa_tests_sessions_days
							WHERE testssessionsid = $testssessionsid
							order by testdate";
		$result = $mysqli->query($query );
		$data = array();
		$data['data'] = array();
		while ($row = $result->fetch_assoc()) {
			$row['periods'] = getTestsessionDaysPeriods($mysqli, $testssessionsid, $row['id'], $language)['data'];
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
 * This function gets the details of all periods for a day for a testsession from database
 */
function getTestsessionDaysPeriods($mysqli, $testssessionsid, $testsdaysid, $language) {
	try {
		$query = "SELECT ctp.*, (select getTextLabel(ca.label, '$language') from cpa_arenas ca where ca.id = ctp.arenaid) arenalabel,
											(select getTextLabel(cai.label, '$language') from cpa_arenas_ices cai where cai.arenaid = ctp.arenaid and cai.id = ctp.iceid) icelabel
							FROM cpa_tests_sessions_days_periods ctp
							WHERE testsdaysid = $testsdaysid";
		$result = $mysqli->query($query );
		$data = array();
		$data['data'] = array();
		while ($row = $result->fetch_assoc()) {
			// $row['groups'] = getTestsessionPeriodGroups($mysqli, $testssessionsid, $row['id'], $language)['data'];
			// $row['judges'] = getTestsessionPeriodJudges($mysqli, $row['id'], $language)['data'];
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
 * This function gets the details of all periods for a testsession from database
 */
function getTestsessionPeriods($mysqli, $testssessionsid, $language) {
	$query = "SELECT ctp.*, ctsd.testdate, (select getTextLabel(ca.label, '$language') from cpa_arenas ca where ca.id = ctp.arenaid) arenalabel,
										(select getTextLabel(cai.label, '$language') from cpa_arenas_ices cai where cai.arenaid = ctp.arenaid and cai.id = ctp.iceid) icelabel
						FROM cpa_tests_sessions_days_periods ctp
						JOIN cpa_tests_sessions_days ctsd ON ctsd.id = ctp.testsdaysid
						WHERE ctsd.testssessionsid = $testssessionsid";
	$result = $mysqli->query($query );
	$data = array();
	$data['data'] = array();
	while ($row = $result->fetch_assoc()) {
		$data['data'][] = $row;
	}
	$data['success'] = true;
	return $data;
	exit;
};

/**
 * This function gets the details of all charges for a testsession from database
 */
function getTestsessionCharges($mysqli, $testssessionsid, $language) {
	$query = "SELECT ctsc.*, getTextLabel(cc.label, '$language') chargelabel
						FROM cpa_tests_sessions_charges ctsc
						JOIN cpa_charges cc ON cc.code = ctsc.chargecode
						WHERE ctsc.testssessionsid = $testssessionsid";
	$result = $mysqli->query($query );
	$data = array();
	$data['data'] = array();
	while ($row = $result->fetch_assoc()) {
		$data['data'][] = $row;
	}
	$data['success'] = true;
	return $data;
	exit;
};

/**
 * This function will handle insert/update/delete of a test in DB
 * @throws Exception
 */
function updateEntireTests($mysqli, $testssessionsregistrationsid, $tests) {
	$data = array();
	for($x = 0; $x < count($tests); $x++) {
		$id 						= $mysqli->real_escape_string(isset($tests[$x]['id'] )						? $tests[$x]['id'] : '');
		$testsid 				= $mysqli->real_escape_string(isset($tests[$x]['testsid'] )				? $tests[$x]['testsid'] : '');
		$partnerid 			= $mysqli->real_escape_string(isset($tests[$x]['partnerid']) && !empty($tests[$x]['partnerid']) 			? $tests[$x]['partnerid'] : 0);
		$partnersteps 	= $mysqli->real_escape_string(isset($tests[$x]['partnersteps'] ) 	? (int)$tests[$x]['partnersteps'] : 0);
		$musicid 				= $mysqli->real_escape_string(isset($tests[$x]['musicid']) && !empty($tests[$x]['musicid']) 			? $tests[$x]['musicid'] : 0);
		$comments 			= $mysqli->real_escape_string(isset($tests[$x]['comments'] ) 			? $tests[$x]['comments'] : '');
		$canceled 			= $mysqli->real_escape_string(isset($tests[$x]['canceled'] ) 			?(int) $tests[$x]['canceled'] : 0);

		if ($mysqli->real_escape_string(isset($tests[$x]['status'])) and $tests[$x]['status'] == 'New') {
			$query = "INSERT INTO cpa_tests_sessions_registrations_tests (testssessionsregistrationsid, testsid, partnerid, partnersteps, musicid,  comments)
								VALUES ($testssessionsregistrationsid, $testsid, '$partnerid', '$partnersteps', '$musicid', '$comments')";
			if ($mysqli->query($query ) ) {
			} else {
				throw new Exception($mysqli->sqlstate.' - '. $mysqli->error );
			}
		}

		if ($mysqli->real_escape_string(isset($tests[$x]['status'])) and $tests[$x]['status'] == 'Modified') {
			$query = "UPDATE cpa_tests_sessions_registrations_tests set testsid = $testsid, musicid = $musicid, partnerid = $partnerid, partnersteps = $partnersteps, canceled = $canceled, comments = '$comments' where id = $id";
			if ($mysqli->query($query ) ) {
				$data['success'] = true;
				$data['message'] = 'Tests updated successfully.';
			} else {
				throw new Exception($mysqli->sqlstate.' - '. $mysqli->error );
			}
		}

		if ($mysqli->real_escape_string(isset($tests[$x]['status'])) and $tests[$x]['status'] == 'Deleted') {
			$query = "DELETE FROM cpa_tests_sessions_registrations_tests WHERE id = '$id'";
			if ($mysqli->query($query ) ) {
			} else {
				throw new Exception($mysqli->sqlstate.' - '. $mysqli->error );
			}
		}
	}
	$data['success'] = true;
	return $data;
};

function updateEntireTestregistration($mysqli, $testregistration) {
	try {
		$data = array();
		$id 				= $mysqli->real_escape_string(isset($testregistration['id'] ) 				? $testregistration['id'] : '');
		$extrafees 	= $mysqli->real_escape_string(isset($testregistration['extrafees'] ) ? $testregistration['extrafees'] : '');
		$fees 			= $mysqli->real_escape_string(isset($testregistration['fees'] ) 			? $testregistration['fees'] : '');

		update_testregistration($mysqli, $testregistration);
		if ($mysqli->real_escape_string(isset($testregistration['tests']))) {
			$data['successtests'] = updateEntireTests($mysqli, $id, $testregistration['tests']);
		}
		$mysqli->close();

		$data['success'] = true;
		$data['message'] = 'Testregistration updated successfully.';
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
