<?php
/*
Author : Eric Lamoureux
*/
require_once('../../../private/'. $_SERVER['HTTP_HOST'].'/include/config.php');
require_once('../../include/nocache.php');

if( isset($_POST['type']) && !empty( isset($_POST['type']) ) ){
	$type = $_POST['type'];

	switch ($type) {
		case "getTestsessionPeriodDetails":
			getTestsessionPeriodDetails($mysqli, $_POST['testsessionid'], $_POST['testperiodid'], $_POST['language']);
			break;
		default:
			invalidRequest();
	}
} else {
	invalidRequest();
};

/**
 * This function gets the details of all skaters for a group from database
 */
function getTestsessionGroupsSkaters($mysqli, $testsessionsgroupsid, $language) {
	$query = "SELECT ctsgs.*, cm.firstname, cm.lastname
						FROM cpa_tests_sessions_groups_skaters ctsgs
						JOIN cpa_tests_sessions_registrations_tests ctsrt ON ctsrt.id = ctsgs.testssessionsregistrationstestsid
						JOIN cpa_tests_sessions_registrations ctsr ON ctsr.id = ctsrt.testssessionsregistrationsid
						JOIN cpa_members cm ON cm.id = ctsr.memberid
						WHERE ctsgs.testsessionsgroupsid = $testsessionsgroupsid
						AND ctsrt.canceled != 1
						ORDER BY sequence";
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
 * This function gets the details of all groups for a testsession period from database
 */
function getTestsessionPeriodGroups($mysqli, $testsessionid, $periodid, $language) {
	$query = "SELECT ctsg.*, getTextLabel(ctsg.label, '$language') grouplabel
						FROM cpa_tests_sessions_groups ctsg
						JOIN cpa_tests_sessions_days_periods ctp ON ctp.id = ctsg.testperiodsid
						JOIN cpa_tests_sessions_days ctsd ON ctsd.id = ctp.testsdaysid
						WHERE ctsd.testssessionsid = $testsessionid
						AND ctsg.testperiodsid = $periodid
						ORDER BY ctsg.sequence";
	$result = $mysqli->query($query );
	$data = array();
	$data['data'] = array();
	while ($row = $result->fetch_assoc()) {
		$row['skaters'] = getTestsessionGroupsSkaters($mysqli, $row['id'], $language)['data'];
		$data['data'][] = $row;
	}
	$data['success'] = true;
	return $data;
	exit;
};

/**
 * This function gets the details of all periods for a day for a testsession from database
 */
function getTestsessionPeriodDetails($mysqli, $testsessionid, $testperiodid, $language) {
	try{
		$query = "SELECT ctsdp.*,
											concat(ctsd.testdate, ' ', getTextLabel(ca.label, '$language'), ' ', if(ctsdp.iceid != 0, getTextLabel(cai.label, '$language'), ''), ' ', ctsdp.starttime, ' - ', ctsdp.endtime) periodlabel
							FROM cpa_tests_sessions_days_periods ctsdp
							JOIN cpa_tests_sessions_days ctsd ON ctsd.id = ctsdp.testsdaysid
							JOIN cpa_arenas ca ON ca.id = ctsdp.arenaid
							LEFT JOIN cpa_arenas_ices cai ON cai.id = ctsdp.iceid
							WHERE ctsdp.id = $testperiodid";
		$result = $mysqli->query($query );
		$data = array();
		$data['data'] = array();
		while ($row = $result->fetch_assoc()) {
			$row['groups'] = getTestsessionPeriodGroups($mysqli, $testsessionid, $testperiodid, $language)['data'];
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
 * This function gets the details of one testsession from database
 */
function getTestsessionDetails($mysqli, $id = 0, $language) {
	try{
		$query = "SELECT *, getEnglishTextLabel(label) as label_en, getFrenchTextLabel(label) as label_fr FROM cpa_tests_sessions WHERE id = $id";
		$result = $mysqli->query($query );
		$data = array();
		while ($row = $result->fetch_assoc()) {
			$row['days'] = getTestsessionDays($mysqli, $id, $language)['data'];
			$data['data'][] = $row;
		}
		$data['success'] = true;
		echo json_encode($data);exit;
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
