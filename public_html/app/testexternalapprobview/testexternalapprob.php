<?php
/*
Author : Eric Lamoureux
*/
require_once('../../../private/'. $_SERVER['HTTP_HOST'].'/include/config.php');
require_once('../../include/nocache.php');
require_once('../reports/sendemail.php');

if (isset($_POST['type']) && !empty(isset($_POST['type']) ) ) {
	$type = $_POST['type'];

	switch ($type) {
		case "insert_testexternalapprob":
			insert_testexternalapprob($mysqli, $_POST['userid']);
			break;
		case "updatetestexternalapprob":
			updatetestexternalapprob($mysqli, $_POST['testexternalapprob'], $_POST['userinfo']);
			break;
		case "delete_testexternalapprob":
			delete_testexternalapprob($mysqli);
			break;
		case "getAlltestexternalapprobs":
			getAlltestexternalapprobs($mysqli, $_POST['userinfo']);
			break;
		case "gettestexternalapprobDetails":
			gettestexternalapprobDetails($mysqli, $_POST['id'], $_POST['language']);
			break;
		case "validateSkatersTests":
			validateSkatersTests($mysqli, $_POST['memberid'], $_POST['tests'], $_POST['language']);
			break;
		case "sendEmailToCoach":
			sendEmailToCoach($mysqli, $_POST['id'], $_POST['filename'], $_POST['ccToTestDirector'], $_POST['language']);
			break;
		case "sendEmailToTestDirector":
			sendEmailToTestDirector($mysqli, $_POST['id']);
			break;
		default:
			invalidRequest();
	}
} else {
	invalidRequest();
};

/**
 * This function gets the coach details
 */
 function getApprobationCoachInfo($mysqli, $testexternalapprobsid) {
	// Get the coach email and language
	$query = "SELECT cm.language, cm.email, concat(cm.firstname, ' ', cm.lastname) fullname
						FROM cpa_test_external_approbations ctea
						JOIN cpa_members cm ON cm.id = ctea.coachid
						WHERE ctea.id = $testexternalapprobsid";
	$result = $mysqli->query($query);
	$coachinfo = $result->fetch_assoc();
	if ($coachinfo['language'] == 'F') {
		$coachinfo['language'] = 'fr-ca';
	} else {
		$coachinfo['language'] = 'en-ca';
	}
	return $coachinfo;
}

/**
 * This function gets the test director details
 */
 function getApprobationTestDirectorInfo($mysqli, $testexternalapprobsid) {
	// Get the coach email and language
	$query = "SELECT cm.language, cm.email, concat(cm.firstname, ' ', cm.lastname) fullname
						FROM cpa_test_external_approbations ctea
						JOIN cpa_members cm ON cm.id = ctea.testdirectorid
						WHERE ctea.id = $testexternalapprobsid";
	$result = $mysqli->query($query);
	$testdirectorinfo = $result->fetch_assoc();
	return $testdirectorinfo;
}

/**
 * This function gets the test director details
 */
 function getConfigurationTestDirectorInfo($mysqli) {
	// Get the coach email and language
	$query = "SELECT concat(testdirfirstname, ' ', testdirlastname) fullname, testdiremail email
						FROM cpa_configuration
						WHERE id = 1";
	$result = $mysqli->query($query);
	$testdirectorinfo = $result->fetch_assoc();
	return $testdirectorinfo;
}

/**
 * This function returns the html code for the body of the email with the external tests approbation details
 */
function getOneApprobationDetails($mysqli, $testexternalapprobsid, $language) {
	// Get the external test approbation request details from the database
	$approbation = gettestexternalapprobDetailsInt($mysqli, $testexternalapprobsid, $language)['data'][0];
	if ($language == 'en-ca') {
		$body  = "<p><b>Request No: </b>" . $approbation['id'] . "</p>";
		$body .= "<p><b>Skater: </b>" . $approbation['skaterfirstname'] . ' ' . $approbation['skaterlastname'] . "</p>";
		$body .= "<p><b>Test Date: </b>" . $approbation['testdate'] . "</p>";
		if (isset($approbation['clubfullname']) && !empty($approbation['clubfullname'])) {
			$body  .= "<p><b>Location: </b>" . $approbation['clubfullname'] . "</p>";
		} else {
			$body  .= "<p><b>Location: </b>" . $approbation['clubname'] . "</p>";
		}
		$body .= "<p><b>Coach: </b>" . $approbation['coachfirstname'] . ' ' . $approbation['coachlastname'] . "</p>";
		// Need to loop over test - check status : for Deleted, indicate this test was refused
		$body .= "<p><b>Test(s) : </b></p>";
		for ($x = 0; isset($approbation['tests']) && $x < count($approbation['tests']); $x++) {
			$body .= "<p>" . $approbation['tests'][$x]['testtypelabel'] . ' - '. $approbation['tests'][$x]['testlabel'];
			if ($approbation['tests'][$x]['status'] == 'Deleted') {
				$body .= " - Refused";
			}
			$body .= "</p>";
		}
		$body .= "<p><b>General Approbation Status: </b>" . $approbation['approbationstatuslabel'] . "</p>";
		if ($mysqli->real_escape_string(isset($approbation['approvedby']))) {
			$body .= "<p><b>Approved By: </b>" . $approbation['fullname'] . "</p>";
		}
		if ($mysqli->real_escape_string(isset($approbation['approvedon']))) {
			$body .= "<p><b>Approved On: </b>" . $approbation['approvedon'] . "</p>";
		}
		$body .= "<p> You can see the external tests approbation request here : %url%/#!/testexternalapprobview</p>";
	} else {
		$body  = "<p><b>Demande No: </b>" . $approbation['id'] . "</p>";
		$body .= "<p><b>Patineur : </b>" . $approbation['skaterfirstname'] . ' ' . $approbation['skaterlastname'] . "</p>";
		$body .= "<p><b>Date du test : </b>" . $approbation['testdate'] . "</p>";
		if (isset($approbation['clubfullname']) && !empty($approbation['clubfullname'])) {
			$body .= "<p><b>Emplacement: </b>" . $approbation['clubfullname'] . "</p>";
		} else {
			$body .= "<p><b>Emplacement: </b>" . $approbation['clubname'] . "</p>";
		}
		$body .= "<p><b>Entraîneur : </b>" . $approbation['coachfirstname'] . ' ' . $approbation['coachlastname'] . "</p>";
		// Need to loop over test - check status : for Deleted, indicate this test was refused
		$body .= "<p><b>Test(s) : </b></p>";
		for ($x = 0; isset($approbation['tests']) && $x < count($approbation['tests']); $x++) {
			$body .= "<p>" . $approbation['tests'][$x]['testtypelabel'] . ' - '. $approbation['tests'][$x]['testlabel'];
			if ($approbation['tests'][$x]['status'] == 'Deleted') {
				$body .= " - Refusé";
			}
			$body .= "</p>";
		}
		$body .= "<p><b>Approbation générale : </b>" . $approbation['approbationstatuslabel'] . "</p>";
		if ($mysqli->real_escape_string(isset($approbation['approvedby']))) {
			$body .= "<p><b>Approuvé par : </b>" . $approbation['fullname'] . "</p>";
		}
		if ($mysqli->real_escape_string(isset($approbation['approvedon']))) {
			$body .= "<p><b>Approuvé le : </b>" . $approbation['approvedon'] . "</p>";
		}
		$body .= "<p> Vous pouvez consulter la demande d'approbation de tests externes ici : %url%/#!/testexternalapprobview</p>";
	}
	return $body;
}

/**
 * This function will handle sending an email to the test director defined in the configuration
 */
function sendEmailToTestDirector($mysqli, $testexternalapprobsid) {
	try {
		// We need to send an email to the test director
		$testdirectorinfo = getConfigurationTestDirectorInfo($mysqli);
		$language = isset($testdirectorinfo['language']) ? $testdirectorinfo['language'] : 'fr-ca';
		$email = $testdirectorinfo['email'];
		if ($language == 'en-ca') {
			$title = "External Test Approbation Modified";
			$body =  "<p>You received this email because an external test approbation request has been created.</p>";
		} else {
			$title = "Demande d'approbation de tests externes créée";
			$body =  "<p>Vous recevez ce courriel parce qu'une demande d'approbation de tests externes a été créée.</p>";
		}
		// Get the approbation details
		$body .= getOneApprobationDetails($mysqli, $testexternalapprobsid, $language);
		// Send email
		$data = sendoneemail($mysqli, $email, $testdirectorinfo['fullname'], $title, $body, '../../images', null, $language, null, "");
		if ($data['success'] == true) {
			// We need to log the sending of the email in the table
			$query = "UPDATE cpa_test_external_approbations SET emailsenttotestdirectoron = current_timestamp(), testdirectoremailaddress = '$email' WHERE id = $testexternalapprobsid";
			if (!$mysqli->query($query)) {
				throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
			}
		}
		echo json_encode($data);
		exit;
	}catch (Exception $e) {
		// $data = array();
		$data['success'] = false;
		$data['message'] = $e->getMessage();
		echo json_encode($data);
		exit;
	}
}

/**
 * This function will handle sending an email to the coach who submited the external test approbation request
 */
function sendEmailToCoach($mysqli, $testexternalapprobsid, $filename, $ccToTestDirector, $language) {
	try {
		// We need to send an email to the coach
		$coachinfo = getApprobationCoachInfo($mysqli, $testexternalapprobsid);
		if ($coachinfo['language'] == 'en-ca') {
			$title = "External Test Approbation Modified";
			$body =  "<p>You received this email because an external test approbation request you submited has been modified.</p>";
		} else {
			$title = "Demande d'approbation de tests externes modifiée";
			$body =  "<p>Vous recevez ce courriel parce qu'une demande d'approbation de tests externes que vous avez soumise a été modifiée.</p>";
		}
		if ($ccToTestDirector) {
			$testdirectorinfo = getApprobationTestDirectorInfo($mysqli, $testexternalapprobsid);
		}
		// Get the approbation details
		$body .= getOneApprobationDetails($mysqli, $testexternalapprobsid, $coachinfo['language']);
		// echo json_encode($body);
		// Send email
		$data = sendoneemail($mysqli, $coachinfo['email'], $coachinfo['fullname'], $title, $body, '../../images', $filename, $coachinfo['language'], $ccToTestDirector ? $testdirectorinfo['email'] : null, $ccToTestDirector ? $testdirectorinfo['fullname'] : "");
		echo json_encode($data);
		exit;
	}catch (Exception $e) {
		// $data = array();
		$data['success'] = false;
		$data['message'] = $e->getMessage();
		echo json_encode($data);
		exit;
	}
}

/**
 * This function will handle the update of all tests for a test approbation
 * @throws Exception
 */
function updateEntireApprobationTests($mysqli, $testexternalapprobsid, $tests) {
	for ($x = 0; $x < count($tests); $x++) {
		$id = 				$mysqli->real_escape_string(isset($tests[$x]['id']) 				? $tests[$x]['id'] : '');
		$testsid = 		$mysqli->real_escape_string(isset($tests[$x]['testsid']) 		? $tests[$x]['testsid'] : '');
		$status = 		$mysqli->real_escape_string(isset($tests[$x]['status']) 		? $tests[$x]['status'] : '');

		if ($mysqli->real_escape_string(isset($tests[$x]['status'])) and $tests[$x]['status'] == 'New') {
			$query = "INSERT into cpa_test_external_approbations_tests (testexternalapprobsid, testsid, status) VALUES ($testexternalapprobsid, $testsid, null)";
			if (!$mysqli->query($query)) {
				throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
			}
		}

		if ($mysqli->real_escape_string(isset($tests[$x]['status'])) and $tests[$x]['status'] == 'Modified') {
			$query = "UPDATE cpa_test_external_approbations_tests SET testsid = $testsid, status = null WHERE id = '$id'";
			if (!$mysqli->query($query)) {
				throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
			}
		}

		if ($mysqli->real_escape_string(isset($tests[$x]['status'])) and $tests[$x]['status'] == 'Deleted') {
			$query = "UPDATE cpa_test_external_approbations_tests SET testsid = $testsid, status = '$status' WHERE id = '$id'";
			if (!$mysqli->query($query)) {
				throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
			}
		}
	}
};

/**
 * This function will handle testexternalapprob add functionality
 * @throws Exception
 */
function insert_testexternalapprob($mysqli, $userid) {
	try{
		$data = array();
		$coachid 							= $mysqli->real_escape_string(isset($_POST['testexternalapprob']['coachid']) 				? $_POST['testexternalapprob']['coachid'] : '');
		$memberid 						= $mysqli->real_escape_string(isset($_POST['testexternalapprob']['member']['id']) 	? $_POST['testexternalapprob']['member']['id'] : '');
		$clubcode							= $mysqli->real_escape_string(isset($_POST['testexternalapprob']['clubcode']) 			? $_POST['testexternalapprob']['clubcode'] : '');
		$clubname 						= $mysqli->real_escape_string(isset($_POST['testexternalapprob']['clubname']) 			? $_POST['testexternalapprob']['clubname'] : '');
		$testdate 						= $mysqli->real_escape_string(isset($_POST['testexternalapprob']['testdatestr']) 		? $_POST['testexternalapprob']['testdatestr'] : '');
		$tests 								= $_POST['testexternalapprob']['tests'];

		$query = "INSERT INTO `cpa_test_external_approbations`(`id`, `coachid`, `memberid`, `clubcode`, `clubname`, `testdate`,  `approvedby`, `approvedon`, `createdby`, `emailsenttotestdirectoron`, `testdirectoremailaddress`)
							VALUES (null, $coachid, $memberid, '$clubcode', '$clubname', '$testdate', null, null, '$userid', null, null)";

		if ($mysqli->query($query ) ) {
			$data['success'] = true;
			$data['id'] = (int) $mysqli->insert_id;
			updateEntireApprobationTests($mysqli, $data['id'], $tests);
		} else {
			throw new Exception($mysqli->sqlstate.' - '. $mysqli->error );
		}
		$mysqli->close();
		echo json_encode($data);
		exit;
	}catch (Exception $e) {
		// $data = array();
		$data['success'] = false;
		$data['message'] = $e->getMessage();
		echo json_encode($data);
		exit;
	}
};

/**
 * This function will handle testexternalapprob add, update functionality
 * @throws Exception
 */
function update_testexternalapprob($mysqli, $testexternalapprob, $userinfo) {
	try{
		$data = array();
		$id 									= $mysqli->real_escape_string(isset($testexternalapprob['id']) 									? $testexternalapprob['id'] : '');
		$coachid 							= $mysqli->real_escape_string(isset($testexternalapprob['coachid']) 						? $testexternalapprob['coachid'] : '');
		// $memberid 						= $mysqli->real_escape_string(isset($testexternalapprob['member']['id']) 		? $testexternalapprob['member']['id'] : '');
		$clubcode 						= $mysqli->real_escape_string(isset($testexternalapprob['clubcode']) 				 		? $testexternalapprob['clubcode'] : '');
		$clubname 						= $mysqli->real_escape_string(isset($testexternalapprob['clubname']) 						? $testexternalapprob['clubname'] : '');
		$testdate 						= $mysqli->real_escape_string(isset($testexternalapprob['testdatestr']) 				? $testexternalapprob['testdatestr'] : '');
		$approbationstatus 		= $mysqli->real_escape_string(isset($testexternalapprob['approbationstatus']) 	? $testexternalapprob['approbationstatus'] : '');
		$testdirectorid 			= $mysqli->real_escape_string(isset($testexternalapprob['testdirectorid']) 			? $testexternalapprob['testdirectorid'] : '');
		$approvedby 					= $mysqli->real_escape_string(isset($userinfo['userid']) 												? $userinfo['userid'] : '');

		$query = "UPDATE cpa_test_external_approbations
							SET coachid = $coachid, clubcode = '$clubcode', clubname = '$clubname', testdate = '$testdate', approbationstatus = '$approbationstatus', testdirectorid = '$testdirectorid', approvedby = '$approvedby', approvedon = now()
							WHERE id = $id";
		if ($mysqli->query($query ) ) {
			$data['success'] = true;
			$data['message'] = 'testexternalapprob updated successfully.';
		} else {
			throw new Exception($mysqli->sqlstate.' - '. $mysqli->error );
		}
		return $data;
		exit;
	}catch (Exception $e) {
		$data = array();
		$data['success'] = false;
		$data['message'] = $e->getMessage();
		return $data;
		exit;
	}
};

/**
 * This function will handle testexternalapprob deletion
 * @throws Exception
 */
function delete_testexternalapprob($mysqli) {
	try{
		$id = $mysqli->real_escape_string(isset($_POST['testexternalapprob']['id']) ? $_POST['testexternalapprob']['id'] : '');
		if (empty($id)) throw new Exception("Invalid testexternalapprob." );
		$query = "DELETE FROM cpa_test_external_approbations WHERE id = $id";
		if ($mysqli->query($query )) {
			$data['success'] = true;
			$data['message'] = 'testexternalapprob deleted successfully.';
			echo json_encode($data);
			exit;
		} else {
			throw new Exception($mysqli->sqlstate.' - '. $mysqli->error );
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
 * This function gets the list of all testexternalapprobs from database
 * If user has the «testregistration_revise» privilege, user can see all testexternalapprobs.
 * If not, only the testexternalapprobs that were created by the user can be seen.
 */
function getAlltestexternalapprobs($mysqli, $userinfo) {
	try{
		$data = array();
		$userid = isset($userinfo['userid']) ? $userinfo['userid'] : 0;
		$memberid = isset($userinfo['memberid']) ? $userinfo['memberid'] : 0;
		$privilege = 'false';
		for ($x = 0; isset($userinfo['privileges']) && $x < count($userinfo['privileges']); $x++) {
			if ($userinfo['privileges'][$x]['code'] == 'testregistration_revise') {
				$privilege = 'true';
			}
		}
		// $privilege = isset($userinfo['privileges']['testregistration_revise']) ? 'true' : 'false';
		$data['info'] = $privilege . ' ' . $userid . ' ' . $memberid;
		$query = "SELECT ctea.id, ctea.testdate, ctea.approbationstatus, ctea.createdby, ctea.coachid, cm.firstname, cm.lastname
							FROM cpa_test_external_approbations ctea
							JOIN cpa_members cm ON cm.id = ctea.memberid
							WHERE ('$privilege' = 'true') OR (ctea.createdby = '$userid' OR ctea.coachid = $memberid)
							order by ctea.createdon DESC";
		$result = $mysqli->query($query );
		while ($row = $result->fetch_assoc()) {
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
 * This function gets the details of member for a testexternalapprob from database
 */
function gettestexternalapprobMember($mysqli, $memberid = '') {
	$query = "SELECT cm.*
						FROM cpa_members cm
						WHERE id = $memberid";
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
 * This function gets the details of all tests for a testexternalapprob from database
 */
function gettestexternalapprobTests($mysqli, $testexternalapprobsid, $language) {
	$query = "SELECT cteat.*, ctd.type testtype, getTextLabel(ct.label, '$language') testlabel, getCodeDescription('startesttypes', ctd.type, '$language') testtypelabel
						FROM cpa_test_external_approbations_tests cteat
						JOIN cpa_tests ct ON ct.id = cteat.testsid
						JOIN cpa_tests_definitions ctd ON ctd.id = ct.testsdefinitionsid
						WHERE testexternalapprobsid = $testexternalapprobsid";
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
 * This function gets the details of one testexternalapprob from database
 */
function gettestexternalapprobDetailsInt($mysqli, $id, $language) {
	$data = array();
	$query = "SELECT ctea.*, cmcoach.lastname coachlastname, cmcoach.firstname coachfirstname, cmskater.lastname skaterlastname, cmskater.firstname skaterfirstname, getCodeDescription('approbationstatus', ctea.approbationstatus, '$language') approbationstatuslabel, cu.fullname, getTextLabel(cc.label, '$language') clubfullname
						FROM cpa_test_external_approbations ctea
						JOIN cpa_members cmcoach ON cmcoach.id = ctea.coachid
						JOIN cpa_members cmskater ON cmskater.id = ctea.memberid
						LEFT JOIN cpa_clubs cc ON cc.code = ctea.clubcode
						LEFT JOIN cpa_users cu ON cu.userid = ctea.approvedby
						WHERE ctea.id = $id";
	$result = $mysqli->query($query );
	while ($row = $result->fetch_assoc()) {
		$row['tests'] = gettestexternalapprobTests($mysqli, $row['id'], $language)['data'];
		$row['member'] = gettestexternalapprobMember($mysqli, $row['memberid'])['data'][0];
		$data['data'][] = $row;
	}
	$data['success'] = true;
	return $data;
	exit;
};

/**
 * This function gets the details of one testexternalapprob from database
 */
function gettestexternalapprobDetails($mysqli, $id, $language) {
	try{
		$data = gettestexternalapprobDetailsInt($mysqli, $id, $language);
		// $query = "SELECT ctea.*
		// 					FROM cpa_test_external_approbations ctea
		// 					WHERE ctea.id = $id";
		// $result = $mysqli->query($query );
		// $data = array();
		// while ($row = $result->fetch_assoc()) {
		// 	$row['tests'] = gettestexternalapprobTests($mysqli, $row['id'])['data'];
		// 	$row['member'] = gettestexternalapprobMember($mysqli, $row['memberid'])['data'][0];
		// 	$data['data'][] = $row;
		// }
		// $data['success'] = true;
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
 * This function validates that all tests were not passed by the skater
 */
function validateSkatersTests($mysqli, $memberid, $tests, $language) {
	try{
		for ($x = 0; $x < count($tests); $x++) {
			$data = array();
			$testsid = $mysqli->real_escape_string(isset($tests[$x]['testsid']) ? $tests[$x]['testsid'] : '');

			$query = "select getCodeDescription('testtypes', ctd.type, '$language') testtypelabel, getTextLabel(ct.label, '$language') testlabel
								from cpa_members_tests cmt
								join cpa_tests ct ON ct.id = cmt.testid
								join cpa_tests_definitions ctd ON ctd.id = ct.testsdefinitionsid
								where memberid = $memberid
								and (success = 1 or success = 2)
								and cmt.testid = $testsid";
			$result = $mysqli->query($query );
			while ($row = $result->fetch_assoc()) {
				$data['data'][] = $row;
				$data['success'] = true;
				echo json_encode($data);
				exit;
			}
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

function updatetestexternalapprob($mysqli, $testexternalapprob, $userinfo) {
	try{
		$data = array();
		$id = $mysqli->real_escape_string(isset($testexternalapprob['id']) ? $testexternalapprob['id'] : '');

		update_testexternalapprob($mysqli, $testexternalapprob, $userinfo);
		if ($mysqli->real_escape_string(isset($testexternalapprob['tests']))) {
			$data['successtests'] = updateEntireApprobationTests($mysqli, $id, $testexternalapprob['tests']);
		}
		$mysqli->close();

		$data['success'] = true;
		$data['message'] = 'testexternalapprob updated successfully.';
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

function invalidRequest() {
	$data = array();
	$data['success'] = false;
	$data['message'] = "Invalid request.";
	echo json_encode($data);
	exit;
};

?>
