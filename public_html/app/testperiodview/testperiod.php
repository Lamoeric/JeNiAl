<?php
/*
Author : Eric Lamoureux
*/
require_once('../../../private/'. $_SERVER['HTTP_HOST'].'/include/config.php');
require_once('../../include/nocache.php');

if( isset($_POST['type']) && !empty( isset($_POST['type']) ) ){
	$type = $_POST['type'];

	switch ($type) {
		case "updateEntireTestperiod":
			updateEntireTestperiod($mysqli, $_POST['testperiod']);
			break;
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
 * This function will handle insert/update/delete of a period in DB
 * @throws Exception
 */
function updateEntireTestperiod($mysqli, $period) {
	$data = array();
	try {
		$id 							=	$mysqli->real_escape_string(isset($period['id'])									? $period['id'] : '');
		$teststatus 			=	$mysqli->real_escape_string(isset($period['teststatus']) 					? $period['teststatus'] : '');
		$setclause 				= " set teststatus = '$teststatus'";

		if ($mysqli->real_escape_string(isset($period['realstarttime']))) {
			$realstarttime = $period['realstarttime'];
			if (!empty($realstarttime)) {
				$setclause .= ", realstarttime = '$realstarttime'";
			}
		}
		if ($mysqli->real_escape_string(isset($period['realendtime']))) {
			$realendtime = $period['realendtime'];
			if (!empty($realendtime)) {
				$setclause .= ", realendtime = '$realendtime'";
			}
		}

		if ($mysqli->real_escape_string(isset($period['status'])) and $period['status'] == 'Modified') {
			$query = "update cpa_tests_sessions_days_periods " . $setclause . " where id = $id";
			if (!$mysqli->query($query)) {
				throw new Exception($mysqli->sqlstate.' - '. $mysqli->error );
			}
		}
		if ($mysqli->real_escape_string(isset($period['groups']))) {
			$data['successgroups'] = updateEntireGroups($mysqli, $period['id'], $period['groups']/*, $testssessionsid, $day*/);
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
 * This function will handle insert/update/delete of a group in DB
 * @throws Exception
 */
function updateEntireGroups($mysqli, $testperiodsid, $groups/*, $testssessionsid, $day*/) {
	$data = array();
	for($x = 0; $x < count($groups); $x++) {
		$id 									=	$mysqli->real_escape_string(isset($groups[$x]['id'])									? $groups[$x]['id'] : '');
		$teststatus 					=	$mysqli->real_escape_string(isset($groups[$x]['teststatus']) 					? $groups[$x]['teststatus'] : '');
		$sequence 						=	$mysqli->real_escape_string(isset($groups[$x]['sequence']) 						? (int)$groups[$x]['sequence'] : 0);
		$setclause 						= " set teststatus = '$teststatus', sequence = $sequence";

		if ($mysqli->real_escape_string(isset($groups[$x]['warmuprealstarttime']))) {
			$warmuprealstarttime = $groups[$x]['warmuprealstarttime'];
			if (!empty($warmuprealstarttime)) {
				$setclause .= ", warmuprealstarttime = '$warmuprealstarttime'";
			}
		}

		if ($mysqli->real_escape_string(isset($groups[$x]['warmuprealendtime']))) {
			$warmuprealendtime = $groups[$x]['warmuprealendtime'];
			if (!empty($warmuprealendtime)) {
				$setclause .= ", warmuprealendtime = '$warmuprealendtime'";
			}
		}

		if ($mysqli->real_escape_string(isset($groups[$x]['realendtime']))) {
			$realendtime = $groups[$x]['realendtime'];
			if (!empty($realendtime)) {
				$setclause .= ", realendtime = '$realendtime'";
			}
		}

		if ($mysqli->real_escape_string(isset($groups[$x]['status'])) and $groups[$x]['status'] == 'Modified') {
			$query = "UPDATE cpa_tests_sessions_groups " . $setclause . " WHERE id = $id";
			if (!$mysqli->query($query)) {
				throw new Exception($mysqli->sqlstate.' - '. $mysqli->error );
			}
		}
	}

	for($x = 0; $x < count($groups); $x++) {
		if ($mysqli->real_escape_string(isset($groups[$x]['skaters']))) {
			$data['skaters'] = updateEntireSkaters($mysqli, $groups[$x]['skaters']);
		}
	}
	$data['success'] = true;
	return $data;
};

/**
 * This function will handle insert/update/delete of a skater in DB
 * @throws Exception
 */
function updateEntireSkaters($mysqli, $skaters) {
	$data = array();
	for($x = 0; $x < count($skaters); $x++) {
		$id 									=	$mysqli->real_escape_string(isset($skaters[$x]['id'])														? $skaters[$x]['id'] : '');
		$id 									=	$mysqli->real_escape_string(isset($skaters[$x]['id'])														? $skaters[$x]['id'] : '');
		$teststatus 					=	$mysqli->real_escape_string(isset($skaters[$x]['teststatus']) 									? $skaters[$x]['teststatus'] : '');
		$sequence 						=	$mysqli->real_escape_string(isset($skaters[$x]['sequence']) 										? $skaters[$x]['sequence'] : '');
		$setclause 						= " set teststatus = '$teststatus', sequence = '$sequence'";

		if ($mysqli->real_escape_string(isset($skaters[$x]['realstarttime']))) {
			$realstarttime = $skaters[$x]['realstarttime'];
			if (!empty($realstarttime)) {
				$setclause .= ", realstarttime = '$realstarttime'";
			}
		}

		if ($mysqli->real_escape_string(isset($skaters[$x]['realendtime']))) {
			$realendtime = $skaters[$x]['realendtime'];
			if (!empty($realendtime)) {
				$setclause .= ", realendtime = '$realendtime'";
			}
		}

		if ($mysqli->real_escape_string(isset($skaters[$x]['status'])) and $skaters[$x]['status'] == 'Modified') {
			$query = "UPDATE cpa_tests_sessions_groups_skaters " . $setclause . " WHERE id = $id";
			if ($mysqli->query($query)) {
			} else {
				throw new Exception($mysqli->sqlstate.' - '. $mysqli->error );
			}
		}
	}
	$data['success'] = true;
	return $data;
};


/**
 * This function gets the details of all skaters for a group from database
 */
function getTestsessionGroupsSkaters($mysqli, $testsessionsgroupsid, $language) {
	$query = "SELECT ctsgs.*, ctd.type testtype, ct.summarycode, ctsrt.testsid, ctsr.memberid, ctsrt.partnerid, ctsrt.partnersteps, ctsrt.musicid, ctsrt.comments, cm.firstname, cm.lastname, concat(cmu.song, ' - ', cmu.author) musiclabel, cmpartner.firstname partnerfirstname, cmpartner.lastname partnerlastname
						FROM cpa_tests_sessions_groups_skaters ctsgs
						JOIN cpa_tests_sessions_registrations_tests ctsrt ON ctsrt.id = ctsgs.testssessionsregistrationstestsid
						JOIN cpa_tests_sessions_registrations ctsr ON ctsr.id = ctsrt.testssessionsregistrationsid
						JOIN cpa_members cm ON cm.id = ctsr.memberid
						LEFT JOIN cpa_members cmpartner ON cmpartner.id = ctsrt.partnerid
						JOIN cpa_tests ct ON ct.id = ctsrt.testsid
						JOIN cpa_tests_definitions ctd ON ctd.id = ct.testsdefinitionsid
						LEFT JOIN cpa_musics cmu ON cmu.id = ctsrt.musicid
						WHERE ctsgs.testsessionsgroupsid = $testsessionsgroupsid
						AND ctsrt.canceled != 1
						AND ctd.version = 1
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
	$query = "SELECT ctsg.*, getTextLabel(ctsg.label, '$language') grouplabel, ctd.type testtype, getCodeDescription('testtypes', ctd.type, '$language') testtypelabel,
						getTextLabel(ct.label, '$language') testlabel, getCodeDescription('testlevels', ctd.level, '$language') testlevellabel,
						ctd.testduration testdefduration, ctd.warmupduration testdefwarmupduration
	-- , ctsd.testdate, getTextLabel(ca.label, '$language') arenalabel, getTextLabel(cai.label, '$language') icelabel, ctp.testsdaysjudgeid, ctp.starttime periodstarttime, ctp.endtime periodendtime
						FROM cpa_tests_sessions_groups ctsg
						JOIN cpa_tests_sessions_days_periods ctp ON ctp.id = ctsg.testperiodsid
						JOIN cpa_tests_sessions_days ctsd ON ctsd.id = ctp.testsdaysid
						JOIN cpa_tests ct ON ct.id = ctsg.testid
						JOIN cpa_tests_definitions ctd ON ctd.id = ct.testsdefinitionsid
						-- JOIN cpa_arenas ca ON ca.id = ctp.arenaid
						-- LEFT JOIN cpa_arenas_ices cai ON cai.id = ctp.iceid
						WHERE ctsd.testssessionsid = $testsessionid
						AND ctsg.testperiodsid = $periodid
						AND ctd.version = 1
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
 * This function gets the details of all periods for a testsession from database
 */
function getTestsessionPeriodDetails($mysqli, $testsessionid, $testperiodid, $language) {
	try{
		$query = "SELECT ctsdp.*,
											concat(ctsd.testdate, ' ', getTextLabel(ca.label, '$language'), ' ', if(ctsdp.iceid != 0, getTextLabel(cai.label, '$language'), ''), ' ', ctsdp.starttime, ' - ', ctsdp.endtime) periodlabel
											-- (select getTextLabel(ca.label, '$language') from cpa_arenas ca where ca.id = ctp.arenaid) arenalabel,
											-- (select getTextLabel(cai.label, '$language') from cpa_arenas_ices cai where cai.arenaid = ctp.arenaid and cai.id = ctp.iceid) icelabel
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
			// $row['judges'] = getTestsessionPeriodJudges($mysqli, $testperiodid, $language)['data'];
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
// function getTestsessionDetails($mysqli, $id = 0, $language) {
// 	try{
// 		$query = "SELECT *, getEnglishTextLabel(label) as label_en, getFrenchTextLabel(label) as label_fr FROM cpa_tests_sessions WHERE id = $id";
// 		$result = $mysqli->query($query );
// 		$data = array();
// 		while ($row = $result->fetch_assoc()) {
// 			$row['days'] = getTestsessionDays($mysqli, $id, $language)['data'];
// 			// $row['groups'] = getTestsessionGroupsUnasigned($mysqli, $id, $language)['data'];
// 			// $row['groupCount'] = getTestsessionGroupCount($mysqli, $id, $language);
// 			// $row['charges'] = getTestsessionCharges($mysqli, $id, $language)['data'];
// 			$data['data'][] = $row;
// 		}
// 		$data['success'] = true;
// 		echo json_encode($data);exit;
// 	} catch (Exception $e) {
// 		$data = array();
// 		$data['success'] = false;
// 		$data['message'] = $e->getMessage();
// 		echo json_encode($data);
// 		exit;
// 	}
// };

function invalidRequest(){
	$data = array();
	$data['success'] = false;
	$data['message'] = "Invalid request.";
	echo json_encode($data);
	exit;
};

?>
