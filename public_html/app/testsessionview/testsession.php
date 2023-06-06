<?php
/*
Author : Eric Lamoureux
*/
require_once('../../../private/'. $_SERVER['HTTP_HOST'].'/include/config.php');
require_once('../../include/nocache.php');

if (isset($_POST['type']) && !empty(isset($_POST['type']))) {
	$type = $_POST['type'];

	switch ($type) {
		case "insert_testsession":
			insert_testsession($mysqli);
			break;
		case "updateEntireTestsession":
			updateEntireTestsession($mysqli, $_POST['testsession'], $_POST['language']);
			break;
		case "delete_testsession":
			delete_testsession($mysqli,  $_POST['testsession']);
			break;
		case "getAllTestsessions":
			getAllTestsessions($mysqli);
			break;
		case "getTestsessionDetails":
			getTestsessionDetails($mysqli, $_POST['id'], $_POST['language']);
			break;
		case "createGroups":
			createGroups($mysqli, $_POST['testsession'], $_POST['language']);
			break;
		case "lockSession":
			lockSession($mysqli, $_POST['testsessionid']);
			break;
		case "unlockSession":
			unlockSession($mysqli, $_POST['testsessionid']);
			break;
		default:
			invalidRequest();
	}
} else {
	invalidRequest();
};

/**
 * This function gets the number of test periods for the test session
 */
function getNbOfTestPeriods($mysqli, $testssessionsid) {
	try{
		$query = "SELECT COUNT(*) nb
							FROM cpa_tests_sessions_days_periods ctp
							JOIN cpa_tests_sessions_days ctsd ON ctsd.id = ctp.testsdaysid
							WHERE ctsd.testssessionsid = $testssessionsid";
		$result = $mysqli->query($query );
		$row = $result->fetch_assoc();
		$count = $row['nb'];
		return $count;
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
 * This function deletes the groups for a test session
 */
function deleteTestSessionGroups($mysqli, $testssessionsid) {
	$data = array();
	$query = "DELETE FROM cpa_text
						WHERE id IN (SELECT ctsg.label
												FROM cpa_tests_sessions_groups ctsg
												JOIN cpa_tests_sessions_days_periods ctp ON ctp.id = ctsg.testperiodsid
												JOIN cpa_tests_sessions_days ctsd ON ctsd.id = ctp.testsdaysid
												WHERE ctsd.testssessionsid = $testssessionsid)";
	if ($mysqli->query($query )) {
		$query = "DELETE FROM cpa_tests_sessions_groups
							WHERE testperiodsid IN (SELECT ctp.id
																			FROM cpa_tests_sessions_days_periods ctp
																			JOIN cpa_tests_sessions_days ctsd ON ctsd.id = ctp.testsdaysid
																			WHERE ctsd.testssessionsid = $testssessionsid)";
		if ($mysqli->query($query )) {
			$data['success'] = true;
			$data['message'] = 'Test session group deleted successfully.';
			return $data;
			exit;
		} else {
			throw new Exception($mysqli->sqlstate.' - '. $mysqli->error );
		}
	} else {
		throw new Exception($mysqli->sqlstate.' - '. $mysqli->error );
	}
};

function getFirstPeriodOfTestSession($mysqli, $testsessionid) {
	try{
		$data = array();
		$query = "SELECT ctp.*
							FROM cpa_tests_sessions_days_periods ctp
							JOIN cpa_tests_sessions_days ctsd ON ctsd.id = ctp.testsdaysid
							WHERE ctsd.testssessionsid = $testsessionid
							ORDER BY ctsd.testdate, ctp.starttime
							LIMIT 1";
		$result = $mysqli->query($query);
		$row = $result->fetch_assoc();
		$data['data'][] = $row;
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
}

function getLastInsertedGroup($mysqli, $groupid) {
	try{
		$data = array();
		$query = "SELECT ctsg.*
							FROM cpa_tests_sessions_groups ctsg
							WHERE ctsg.id = $groupid";
		$result = $mysqli->query($query);
		$row = $result->fetch_assoc();
		$data['data'][] = $row;
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
}

/**
 * This function will handle the creation of the test session's groups
 * @throws Exception
 */
function createGroups($mysqli, $testsession, $language) {
	try{
		$data = array();
		$id = $testsession['id'];
		$data['groupsdeletion'] = deleteTestSessionGroups($mysqli, $id);
		if ($data['groupsdeletion']['success'] == false) throw new Exception('Error while deleting group labels');
		$period = getFirstPeriodOfTestSession($mysqli, $id)['data'][0];
		// $nbTestPeriod = getNbOfTestPeriods($mysqli, $id);
		$query = "SELECT DISTINCT ctsrt.testsid,
							getCodeDescription('testtypes', ctd.type, 'en-ca') testtypelabelen, getTextLabel(ct.label, 'en-ca') testlabelen,
							getCodeDescription('testtypes', ctd.type, 'fr-ca') testtypelabelfr, getTextLabel(ct.label, 'fr-ca') testlabelfr,
							getCodeDescription('testlevels', ctd.level, 'fr-ca') testlevellabelfr,
							getCodeDescription('testlevels', ctd.level, 'en-ca') testlevellabelen,
							-- getCodeDescription('testtypes', ctd.type, '$language') testtypelabel, getCodeDescription('testlevels', ctd.level, '$language') testlevellabel,
							ct.name, ctd.warmupduration, ctd.testduration, ctd.type testtype, (SELECT count(*) FROM cpa_tests_sessions_registrations_tests ctsrt2 JOIN cpa_tests_sessions_registrations ctsr ON ctsr.id = ctsrt2.testssessionsregistrationsid where ctsrt2.testsid = ctsrt.testsid AND testssessionsid = $id) nbskater
						  FROM cpa_tests_sessions_registrations_tests ctsrt
						  JOIN cpa_tests_sessions_registrations ctsr ON ctsr.id = ctsrt.testssessionsregistrationsid
						  JOIN cpa_tests ct ON ct.id = ctsrt.testsid
						  JOIN cpa_tests_definitions ctd ON ctd.id = ct.testsdefinitionsid
						  WHERE ctsr.testssessionsid = $id
							AND ctd.version = 1
						  ORDER BY ctsrt.testsid";
		$result = $mysqli->query($query);
		$index = 0;
		$starttime = $period['starttime'];
		while ($row = $result->fetch_assoc()) {
			$index += 1;
			$firstPart = 'GRP' . str_pad($index, 3, '0', STR_PAD_LEFT);
			$danceLevelfr = $row['testtype'] == 'DANCE' ? ' ' . $row['testlevellabelfr'] . ' ' : ' ';
			$danceLevelen = $row['testtype'] == 'DANCE' ? ' ' . $row['testlevellabelen'] . ' ' : ' ';
			$label_fr = $firstPart . ' - ' . $row['testtypelabelfr'] . $danceLevelfr . $row['testlabelfr'] /*. ' ' . $row['name']*/;
			$label_en = $firstPart . ' - ' . $row['testtypelabelen'] . $danceLevelen . $row['testlabelen'] /*. ' ' . $row['name']*/;
			if ($language == 'fr-ca') {
				$name = $label_fr;
			} else {
				$name = $label_en;
			}
			$testsid = $row['testsid'];
			$warmupduration = $row['warmupduration'];
			$nbskater = $row['nbskater'];
			$testduration = $row['testduration'] * $nbskater;
			$testperiod = $period['id'];
			$totalduration = ($warmupduration + $testduration) * 60 ;
			// If only one test period, assign all groups to that period
			// if ($nbTestPeriod == 1) {
				$query2 = "INSERT INTO cpa_tests_sessions_groups(name, testid, sequence, warmupduration, testduration, label, testperiodsid, starttime, endtime)
										VALUES('$name', $testsid, $index, $warmupduration, $testduration, create_systemtext('$label_en', '$label_fr'), $testperiod, '$starttime', ADDTIME('$starttime', SEC_TO_TIME('$totalduration')))";
			// } else {
			// 	$query2 = "INSERT INTO cpa_tests_sessions_groups(name, testid, sequence, warmupduration, testduration, label)
			// 							VALUES('$name', $testsid, null, $warmupduration, null, create_systemtext(label_en, label_fr))";
			// }
			if ($mysqli->query($query2)) {
				$groupid = (int) $mysqli->insert_id;
				$LastInsertedGroup = getLastInsertedGroup($mysqli, $groupid)['data'][0];
				$starttime = $LastInsertedGroup['endtime'];
				// Now assign all registered skaters to the groups
				$query3 = "SELECT ctsrt.id testssessionsregistrationstestsid
								    FROM cpa_tests_sessions_registrations_tests ctsrt
								    JOIN cpa_tests_sessions_registrations ctsr ON ctsr.id = ctsrt.testssessionsregistrationsid
								    JOIN cpa_tests ct ON ct.id = ctsrt.testsid
								    WHERE ctsr.testssessionsid = $id
								    AND ctsrt.testsid = $testsid
								    ORDER BY testssessionsregistrationsid";
				$result3 = $mysqli->query($query3);
				$index2 = 0;
				while ($row2 = $result3->fetch_assoc()) {
					$testssessionsregistrationstestsid = $row2['testssessionsregistrationstestsid'];
					$index2 += 1;
					$query4 = "INSERT INTO cpa_tests_sessions_groups_skaters(testsessionsgroupsid, testssessionsregistrationstestsid, sequence)
											VALUES($groupid, $testssessionsregistrationstestsid, $index2)";
					if ($mysqli->query($query4)) {
					}
				}
			} else {
				throw new Exception($mysqli->sqlstate.' - '. $mysqli->error );
			}
		}
		$mysqli->close();
		$data['success'] = true;
		$data['message'] = "Test Session Groups created successfully";
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
 * This function will handle testsession add, update functionality
 * @throws Exception
 */
function insert_testsession($mysqli) {
	try{
		$data = array();
		$id 											= $mysqli->real_escape_string(isset($_POST['testsession']['id']) 												? $_POST['testsession']['id'] : '');
		$name 										= $mysqli->real_escape_string(isset($_POST['testsession']['name']) 											? $_POST['testsession']['name'] : '');
		$label 										= $mysqli->real_escape_string(isset($_POST['testsession']['label']) 										? $_POST['testsession']['label'] : '');
		$label_fr 								= $mysqli->real_escape_string(isset($_POST['testsession']['label_fr']) 									? $_POST['testsession']['label_fr'] : $name);
		$label_en 								= $mysqli->real_escape_string(isset($_POST['testsession']['label_en']) 									? $_POST['testsession']['label_en'] : $name);
		$homeclub 								= $mysqli->real_escape_string(isset($_POST['testsession']['homeclub']) 									? $_POST['testsession']['homeclub'] : '');
		$testdirectorid 					= $mysqli->real_escape_string(isset($_POST['testsession']['testdirectorid']) 						? $_POST['testsession']['testdirectorid'] : '');
		$registrationstartdate 		= $mysqli->real_escape_string(isset($_POST['testsession']['registrationstartdatestr']) 	? $_POST['testsession']['registrationstartdatestr'] : '2039-12-24');
		$registrationenddate 			= $mysqli->real_escape_string(isset($_POST['testsession']['registrationenddatestr']) 		? $_POST['testsession']['registrationenddatestr'] : '2039-12-24');
		$cancellationenddate 			= $mysqli->real_escape_string(isset($_POST['testsession']['cancellationenddatestr']) 		? $_POST['testsession']['cancellationenddatestr'] : '2039-12-24');

		if ($homeclub == '' || $testdirectorid == '') {
			throw new Exception("Required fields missing, Please enter and submit" );
		}

		$query = "INSERT INTO cpa_tests_sessions (name, label, homeclub, testdirectorid, registrationstartdate, registrationenddate, cancellationenddate)
							VALUES ('$name', create_systemText('$label_en', '$label_fr'), '$homeclub', '$testdirectorid', '$registrationstartdate', '$registrationenddate', '$cancellationenddate')";
		if ($mysqli->query($query)) {
			if (empty($id)) $id = (int) $mysqli->insert_id;
			$data['id'] = $id;
				$query = "INSERT INTO cpa_tests_sessions_charges (id, testssessionsid, chargecode, amount)
									VALUES (null, $id, 'TEST', 12.00)";
				if ($mysqli->query($query)) {
					$query = "INSERT INTO cpa_tests_sessions_charges (id, testssessionsid, chargecode, amount)
										VALUES (null, $id, 'EXTS', 10.00)";
					if ($mysqli->query($query)) {
						$data['success'] = true;
					} else {
						throw new Exception($mysqli->sqlstate.' - '. $mysqli->error );
					}
				} else {
					throw new Exception($mysqli->sqlstate.' - '. $mysqli->error );
				}
		} else {
			throw new Exception($mysqli->sqlstate.' - '. $mysqli->error );
		}
		$mysqli->close();
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
 * This function will handle testsession add, update functionality
 * @throws Exception
 */
function update_testsession($mysqli, $testsession) {
	$data = array();
	$id 											= $mysqli->real_escape_string(isset($testsession['id']) 												? $testsession['id'] : '');
	$name 										= $mysqli->real_escape_string(isset($testsession['name']) 											? $testsession['name'] : '');
	$label 										= $mysqli->real_escape_string(isset($testsession['label']) 											? $testsession['label'] : '');
	$label_fr 								= $mysqli->real_escape_string(isset($testsession['label_fr']) 									? $testsession['label_fr'] : '');
	$label_en 								= $mysqli->real_escape_string(isset($testsession['label_en']) 									? $testsession['label_en'] : '');
	$homeclub 								= $mysqli->real_escape_string(isset($testsession['homeclub']) 									? $testsession['homeclub'] : '');
	$extrafeesoption 					= $mysqli->real_escape_string(isset($testsession['extrafeesoption']) 						? $testsession['extrafeesoption'] : '');
	$testdirectorid 					= $mysqli->real_escape_string(isset($testsession['testdirectorid']) 						? (int)$testsession['testdirectorid'] : 0);
	$registrationstartdate 		= $mysqli->real_escape_string(isset($testsession['registrationstartdatestr']) 	? $testsession['registrationstartdatestr'] : '');
	$registrationenddate 			= $mysqli->real_escape_string(isset($testsession['registrationenddatestr']) 		? $testsession['registrationenddatestr'] : '');
	$cancellationenddate 			= $mysqli->real_escape_string(isset($testsession['cancellationenddatestr']) 		? $testsession['cancellationenddatestr'] : '');
	$publish 									= $mysqli->real_escape_string(isset($testsession['publish']) 										? (int)$testsession['publish'] : 0);
	$publishschedule 					= $mysqli->real_escape_string(isset($testsession['publishschedule']) 						? (int)$testsession['publishschedule'] : 0);

	if ($id == '') {
		throw new Exception("Required fields missing, Please enter and submit" );
	}

	$query = "UPDATE cpa_tests_sessions
						SET name = '$name', homeclub = '$homeclub', testdirectorid = $testdirectorid, extrafeesoption = $extrafeesoption, registrationstartdate = '$registrationstartdate',
								registrationenddate = '$registrationenddate', cancellationenddate = '$cancellationenddate', publish = $publish, publishschedule = $publishschedule
						WHERE id = $id";
	if ($mysqli->query($query)) {
		$query = "UPDATE cpa_text set text = '$label_fr' where id = $label and language = 'fr-ca'";
		if ($mysqli->query($query)) {
			$data['success'] = true;
			$query = "UPDATE cpa_text set text = '$label_en' where id = $label and language = 'en-ca'";
			if ($mysqli->query($query)) {
				$data['success'] = true;
				$data['message'] = 'Testsession updated successfully.';
			} else {
				throw new Exception($mysqli->sqlstate.' - '. $mysqli->error );
			}
		} else {
			throw new Exception($mysqli->sqlstate.' - '. $mysqli->error );
		}
	} else {
		throw new Exception($mysqli->sqlstate.' - '. $mysqli->error );
	}
	return $data;
	exit;
};

/**
 * This function will handle testsession deletion
 * @throws Exception
 */
function delete_testsession($mysqli, $testsession) {
	try{
		$id 		= $mysqli->real_escape_string(isset($testsession['id']) 		? $testsession['id'] : '');
		$label 	= $mysqli->real_escape_string(isset($testsession['label']) 	? $testsession['label'] : '');

		if (empty($id)) throw new Exception("Invalid Testsession." );
		// Delete relation between cpa_members_tests and test session
		$query = "DELETE FROM cpa_members_tests WHERE testssessionsid = $id";
		if ($mysqli->query($query )) {
			// Delete all group - skaters
			$query = "DELETE FROM cpa_tests_sessions_groups_skaters
								WHERE testsessionsgroupsid in
								(SELECT ctsg.id
								 FROM cpa_tests_sessions_groups ctsg
								 JOIN cpa_tests_sessions_days_periods ctsdp ON ctsdp.id = ctsg.testperiodsid
								 JOIN cpa_tests_sessions_days ctsd ON ctsd.id = ctsdp.testsdaysid
								 WHERE ctsd.testssessionsid = $id)";
			if ($mysqli->query($query )) {
				// Deete all period - groups
				$query = "DELETE FROM cpa_tests_sessions_groups
 								WHERE testperiodsid in
 								(SELECT ctsdp.id
 								 FROM cpa_tests_sessions_days_periods ctsdp
 								 JOIN cpa_tests_sessions_days ctsd ON ctsd.id = ctsdp.testsdaysid
 								 WHERE ctsd.testssessionsid = $id)";
				if ($mysqli->query($query )) {
					// Delete all period - judges
					$query = "DELETE FROM cpa_tests_sessions_days_periods_judges
	 								WHERE testperiodsid in
	 								(SELECT ctsdp.id
	 								 FROM cpa_tests_sessions_days_periods ctsdp
	 								 JOIN cpa_tests_sessions_days ctsd ON ctsd.id = ctsdp.testsdaysid
	 								 WHERE ctsd.testssessionsid = $id)";
						if ($mysqli->query($query )) {
							// Delete all days - period
							$query = "DELETE FROM cpa_tests_sessions_days_periods
		 								WHERE testsdaysid in
		 								(SELECT ctsd.id
		 								 FROM cpa_tests_sessions_days ctsd
		 								 WHERE ctsd.testssessionsid = $id)";
							if ($mysqli->query($query )) {
								// Delete all days
								$query = "DELETE FROM cpa_tests_sessions_days
			 								WHERE testssessionsid = $id";
								if ($mysqli->query($query )) {
									// Delete all registrations - tests
							 		$query = "DELETE FROM cpa_tests_sessions_registrations_tests
				 								WHERE testssessionsregistrationsid in
				 								(SELECT ctsr.id
				 								 FROM cpa_tests_sessions_registrations ctsr
				 								 WHERE ctsr.testssessionsid = $id)";
									if ($mysqli->query($query )) {
										// Delete all registrations
										$query = "DELETE FROM cpa_tests_sessions_registrations
					 										WHERE testssessionsid = $id";
										if ($mysqli->query($query )) {
											// Delete test session text
											$query = "DELETE FROM cpa_text WHERE id = '$label'";
											if ($mysqli->query($query )) {
												// Delete test session
												$query = "DELETE FROM cpa_tests_sessions WHERE id = $id";
												if ($mysqli->query($query )) {
													$data['success'] = true;
													$data['message'] = 'Testsession deleted successfully.';
													echo json_encode($data);
													exit;
												}
											}
										}
									}
								}
							}
						}
					}
				}
			}
		throw new Exception($mysqli->sqlstate.' - '. $mysqli->error );
	} catch (Exception $e) {
		$data = array();
		$data['success'] = false;
		$data['message'] = $e->getMessage();
		echo json_encode($data);
		exit;
	}
};

/**
 * This function gets list of all testsessions from database
 */
function getAllTestsessions($mysqli) {
	try{
		$query = "SELECT id, name FROM cpa_tests_sessions ORDER BY id DESC";
		$result = $mysqli->query($query );
		$data = array();
		while ($row = $result->fetch_assoc()) {
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
/**
 * This function gets the details of all groups for a testsession from database
 */
function getTestsessionPeriodGroups($mysqli, $testssessionsid, $periodid, $language) {
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
						WHERE ctsd.testssessionsid = $testssessionsid
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
 * This function gets the number of groups for a testsession from database
 */
function getTestsessionGroupCount($mysqli, $testssessionsid, $language) {
	$query = "SELECT count(*) nb
						FROM cpa_tests_sessions_groups ctsg
						JOIN cpa_tests_sessions_days_periods ctp ON ctp.id = ctsg.testperiodsid
						JOIN cpa_tests_sessions_days ctsd ON ctsd.id = ctp.testsdaysid
						WHERE ctsd.testssessionsid = $testssessionsid";
	$result = $mysqli->query($query );
	$row = $result->fetch_assoc();
	$count = $row['nb'];
	return $count;
	exit;
};

/**
 * This function gets the details of all groups for a testsession from database
 */
function getTestsessionGroupsUnasigned($mysqli, $testssessionsid, $language) {
	$query = "SELECT ctsg.*, getCodeDescription('testtypes', ctd.type, '$language') testdeflabel, getTextLabel(ct.label, '$language') testlabel
						FROM cpa_tests_sessions_groups ctsg
						JOIN cpa_tests_sessions_days_periods ctp ON ctp.id = ctsg.testperiodsid
						JOIN cpa_tests_sessions_days ctsd ON ctsd.id = ctp.testsdaysid
						JOIN cpa_tests ct ON ct.id = ctsg.testid
						JOIN cpa_tests_definitions ctd ON ctd.id = ct.testsdefinitionsid
						WHERE ctsd.testssessionsid = $testssessionsid
						AND testperiodsid is null
						AND ctd.version = 1
						ORDER BY sequence";
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
 * This function gets the details of all skaters for a group from database
 */
function getTestsessionGroupsSkaters($mysqli, $testsessionsgroupsid, $language) {
	$query = "SELECT ctsgs.*, ctd.type testtype, ct.summarycode, ctsrt.testsid, ctsr.memberid, ctsrt.partnerid, ctsrt.partnersteps, ctsrt.musicid, ctsrt.comments, cm.firstname, cm.lastname, concat(cmu.song, ' - ', cmu.author) musiclabel
						FROM cpa_tests_sessions_groups_skaters ctsgs
						JOIN cpa_tests_sessions_registrations_tests ctsrt ON ctsrt.id = ctsgs.testssessionsregistrationstestsid
						JOIN cpa_tests_sessions_registrations ctsr ON ctsr.id = ctsrt.testssessionsregistrationsid
						JOIN cpa_members cm ON cm.id = ctsr.memberid
						JOIN cpa_tests ct ON ct.id = ctsrt.testsid
						JOIN cpa_tests_definitions ctd ON ctd.id = ct.testsdefinitionsid
						LEFT JOIN cpa_musics cmu ON cmu.id = ctsrt.musicid
						WHERE ctsgs.testsessionsgroupsid = $testsessionsgroupsid
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
 * This function gets the details of all days for a testsession from database
 */
function getTestsessionDays($mysqli, $testssessionsid, $language) {
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
};

/**
 * This function gets the details of all periods for a day for a testsession from database
 */
function getTestsessionDaysPeriods($mysqli, $testssessionsid, $testsdaysid, $language) {
	$query = "SELECT ctp.*, (select getTextLabel(ca.label, '$language') from cpa_arenas ca where ca.id = ctp.arenaid) arenalabel,
										(select getTextLabel(cai.label, '$language') from cpa_arenas_ices cai where cai.arenaid = ctp.arenaid and cai.id = ctp.iceid) icelabel
						FROM cpa_tests_sessions_days_periods ctp
						WHERE testsdaysid = $testsdaysid";
	$result = $mysqli->query($query );
	$data = array();
	$data['data'] = array();
	while ($row = $result->fetch_assoc()) {
		$row['groups'] = getTestsessionPeriodGroups($mysqli, $testssessionsid, $row['id'], $language)['data'];
		$row['judges'] = getTestsessionPeriodJudges($mysqli, $row['id'], $language)['data'];
		$data['data'][] = $row;
	}
	$data['success'] = true;
	return $data;
	exit;
};

/**
 * This function gets the details of all judges for a day for a testsession from database
 */
function getTestsessionPeriodJudges($mysqli, $testperiodsid, $language) {
	$query = "SELECT *
						FROM cpa_tests_sessions_days_periods_judges
						WHERE testperiodsid = $testperiodsid";
	$result = $mysqli->query($query );
	$data = array();
	$data['data'] = array();
	while ($row = $result->fetch_assoc()) {
		$row['judgeno'] = (int) $row['judgeno'];
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
						WHERE testssessionsid = $testssessionsid";
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
 * This function gets the details of one testsession from database
 */
function getTestsessionDetails($mysqli, $id, $language) {
	try{
		if (empty($id) || $id == '') $id = 0;
		$query = "SELECT *, getEnglishTextLabel(label) as label_en, getFrenchTextLabel(label) as label_fr FROM cpa_tests_sessions WHERE id = $id";
		$result = $mysqli->query($query );
		$data = array();
		while ($row = $result->fetch_assoc()) {
			$row['islock'] = (int) $row['islock'];
			$row['days'] = getTestsessionDays($mysqli, $id, $language)['data'];
			// $row['groups'] = getTestsessionGroupsUnasigned($mysqli, $id, $language)['data'];
			$row['groupCount'] = getTestsessionGroupCount($mysqli, $id, $language);
			$row['charges'] = getTestsessionCharges($mysqli, $id, $language)['data'];
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

/**
 * This function will handle insert/update/delete of a charge in DB
 * @throws Exception
 */
function updateEntireCharges($mysqli, $testssessionsid, $charges, $language) {
	$data = array();
	for($x = 0; $x < count($charges); $x++) {
		$id 			=	$mysqli->real_escape_string(isset($charges[$x]['id'])					? $charges[$x]['id'] : '');
		$code 		=	$mysqli->real_escape_string(isset($charges[$x]['code'])				? $charges[$x]['code'] : '');
		$amount 	=	$mysqli->real_escape_string(isset($charges[$x]['amount'])			? (float) $charges[$x]['amount'] : 0);

		if ($mysqli->real_escape_string(isset($charges[$x]['status'])) and $charges[$x]['status'] == 'New') {
			$query = "INSERT INTO cpa_tests_sessions_charges (testssessionsid, code, amount)
								VALUES ($testssessionsid, '$code', $amount)";
			if ($mysqli->query($query)) {
				$charges[$x]['id'] = (int) $mysqli->insert_id;
			} else {
				throw new Exception($mysqli->sqlstate.' - '. $mysqli->error );
			}
		}

		if ($mysqli->real_escape_string(isset($charges[$x]['status'])) and $charges[$x]['status'] == 'Modified') {
			$query = "update cpa_tests_sessions_charges set amount = $amount where id = $id";
			if (!$mysqli->query($query)) {
				throw new Exception($mysqli->sqlstate.' - '. $mysqli->error );
			}
		}

		if ($mysqli->real_escape_string(isset($charges[$x]['status'])) and $charges[$x]['status'] == 'Deleted') {
			$query = "DELETE FROM cpa_tests_sessions_charges WHERE id = $id";
			if (!$mysqli->query($query)) {
				throw new Exception($mysqli->sqlstate.' - '. $mysqli->error );
			}
		}
	}
	$data['success'] = true;
	return $data;
};

/**
 * This function will handle insert/update/delete of a day in DB
 * @throws Exception
 */
function updateEntireDays($mysqli, $testssessionsid, $days, $language) {
	$data = array();
	for($x = 0; $x < count($days); $x++) {
		$id 			=	$mysqli->real_escape_string(isset($days[$x]['id'])					? $days[$x]['id'] : '');
		$testdate =	$mysqli->real_escape_string(isset($days[$x]['testdatestr']) 	? $days[$x]['testdatestr'] : '');

		if ($mysqli->real_escape_string(isset($days[$x]['status'])) and $days[$x]['status'] == 'New') {
			$query = "INSERT INTO cpa_tests_sessions_days (testssessionsid, id, testdate)
								VALUES ($testssessionsid, null, '$testdate')";
			if ($mysqli->query($query)) {
				$days[$x]['id'] = (int) $mysqli->insert_id;
			} else {
				throw new Exception($mysqli->sqlstate.' - '. $mysqli->error );
			}
		}

		if ($mysqli->real_escape_string(isset($days[$x]['status'])) and $days[$x]['status'] == 'Modified') {
			$query = "update cpa_tests_sessions_days set testdate = '$testdate' where id = $id";
			if ($mysqli->query($query)) {
			} else {
				throw new Exception($mysqli->sqlstate.' - '. $mysqli->error );
			}
		}

		if ($mysqli->real_escape_string(isset($days[$x]['status'])) and $days[$x]['status'] == 'Deleted') {
			$query = "DELETE FROM cpa_tests_sessions_days_periods_judges WHERE testperiodsid IN (SELECT id FROM cpa_tests_sessions_days_periods WHERE testsdaysid = $id)";
			if ($mysqli->query($query)) {
				$query = "DELETE FROM cpa_tests_sessions_days_periods WHERE testsdaysid = $id";
				if ($mysqli->query($query)) {
					$query = "DELETE FROM cpa_tests_sessions_days WHERE id = $id";
					if ($mysqli->query($query)) {
					} else {
						throw new Exception($mysqli->sqlstate.' - '. $mysqli->error );
					}
				} else {
					throw new Exception($mysqli->sqlstate.' - '. $mysqli->error );
				}
			} else {
				throw new Exception($mysqli->sqlstate.' - '. $mysqli->error );
			}
		}
	}

	for($x = 0; $x < count($days); $x++) {
		if (!$mysqli->real_escape_string(isset($days[$x]['status'])) || ($mysqli->real_escape_string(isset($days[$x]['status'])) && $days[$x]['status'] !== 'Deleted')) {
			if ($mysqli->real_escape_string(isset($days[$x]['periods']))) {
				$data['successperiods'] = updateEntirePeriods($mysqli, $days[$x]['id'], $days[$x]['periods'], $testssessionsid, $days[$x], $language);
			}
		}
	}
	$data['success'] = true;
	return $data;
};

/**
 * This function will handle insert/update/delete of a period in DB
 * @throws Exception
 */
function updateEntirePeriods($mysqli, $testsdaysid, $periods, $testssessionsid, $day, $language) {
	$data = array();
	for($x = 0; $x < count($periods); $x++) {
		$id 							=	$mysqli->real_escape_string(isset($periods[$x]['id'])									? $periods[$x]['id'] : '');
		$starttime 				=	$mysqli->real_escape_string(isset($periods[$x]['starttime']) 					? $periods[$x]['starttime'] : '');
		$endtime 					=	$mysqli->real_escape_string(isset($periods[$x]['endtime']) 						? $periods[$x]['endtime'] : '');
		$arenaid 					=	$mysqli->real_escape_string(isset($periods[$x]['arenaid']) 						? $periods[$x]['arenaid'] : '');
		$iceid						=	$mysqli->real_escape_string(isset($periods[$x]['iceid']) && $periods[$x]['iceid'] != ''	? $periods[$x]['iceid'] : '0');
		$testsdaysjudgeid	=	$mysqli->real_escape_string(isset($periods[$x]['testsdaysjudgeid']) 	? $periods[$x]['testsdaysjudgeid'] : '');

		if ($mysqli->real_escape_string(isset($periods[$x]['status'])) and $periods[$x]['status'] == 'New') {
			$query = "INSERT INTO cpa_tests_sessions_days_periods (id, testsdaysid, starttime, endtime, arenaid, iceid)
								VALUES (null, $testsdaysid, '$starttime', '$endtime', $arenaid, '$iceid')";
			if ($mysqli->query($query)) {
				$periods[$x]['id'] = (int) $mysqli->insert_id;
			} else {
				throw new Exception($mysqli->sqlstate.' - '. $mysqli->error );
			}
		}

		if ($mysqli->real_escape_string(isset($periods[$x]['status'])) and $periods[$x]['status'] == 'Modified') {
			$query = "update cpa_tests_sessions_days_periods set starttime = '$starttime', endtime = '$endtime', arenaid = $arenaid, iceid = '$iceid' where id = $id";
			if (!$mysqli->query($query)) {
				throw new Exception($mysqli->sqlstate.' - '. $mysqli->error );
			}
		}

		if ($mysqli->real_escape_string(isset($periods[$x]['status'])) and $periods[$x]['status'] == 'Deleted') {
			$query = "DELETE FROM cpa_tests_sessions_days_periods WHERE id = $id";
			if (!$mysqli->query($query)) {
				throw new Exception($mysqli->sqlstate.' - '. $mysqli->error );
			}
		}
	}

	for($x = 0; $x < count($periods); $x++) {
		if (!$mysqli->real_escape_string(isset($periods[$x]['status'])) || ($mysqli->real_escape_string(isset($periods[$x]['status'])) && $periods[$x]['status'] !== 'Deleted')) {
			if ($mysqli->real_escape_string(isset($periods[$x]['groups']))) {
				$data['successgroups'] = updateEntireGroups($mysqli, $periods[$x]['id'], $periods[$x]['groups'], $testssessionsid, $day, $language);
			}
		}
	}

	for($x = 0; $x < count($periods); $x++) {
		if (!$mysqli->real_escape_string(isset($periods[$x]['status'])) || ($mysqli->real_escape_string(isset($periods[$x]['status'])) && $periods[$x]['status'] !== 'Deleted')) {
			if ($mysqli->real_escape_string(isset($periods[$x]['judges']))) {
				$data['successjudges'] = updateEntireJudges($mysqli, $periods[$x]['id'], $periods[$x]['judges']);
			}
		}
	}

	$data['success'] = true;
	return $data;
};

/**
 * This function will handle insert/update/delete of a judge in DB
 * @throws Exception
 */
function updateEntireJudges($mysqli, $testperiodsid, $judges) {
	$data = array();
	for($x = 0; $x < count($judges); $x++) {
		$id 							=	$mysqli->real_escape_string(isset($judges[$x]['id'])								? $judges[$x]['id'] : '');
		$judgesid 				=	$mysqli->real_escape_string(isset($judges[$x]['judgesid']) 					? $judges[$x]['judgesid'] : '');
		$judgeno 					=	$mysqli->real_escape_string(isset($judges[$x]['judgeno']) && $judges[$x]['judgeno'] !=''					? $judges[$x]['judgeno'] : '0');

		if ($mysqli->real_escape_string(isset($judges[$x]['status'])) and $judges[$x]['status'] == 'New') {
			$query = "INSERT INTO cpa_tests_sessions_days_periods_judges (id, testperiodsid, judgesid, judgeno)
								VALUES (null, $testperiodsid, '$judgesid', '$judgeno')";
			if (!$mysqli->query($query)) {
				throw new Exception($mysqli->sqlstate.' - '. $mysqli->error );
			}
		}

		if ($mysqli->real_escape_string(isset($judges[$x]['status'])) and $judges[$x]['status'] == 'Modified') {
			$query = "update cpa_tests_sessions_days_periods_judges set judgesid = '$judgesid', judgeno = '$judgeno' where id = $id";
			if (!$mysqli->query($query)) {
				throw new Exception($mysqli->sqlstate.' - '. $mysqli->error );
			}
		}

		if ($mysqli->real_escape_string(isset($judges[$x]['status'])) and $judges[$x]['status'] == 'Deleted') {
			$query = "DELETE FROM cpa_tests_sessions_days_periods_judges WHERE id = $id";
			if (!$mysqli->query($query)) {
				throw new Exception($mysqli->sqlstate.' - '. $mysqli->error );
			}
		}
	}
	$data['success'] = true;
	return $data;
};

/**
 * This function will handle insert/update/delete of a skater in DB
 * @throws Exception
 */
function updateEntireSkaters($mysqli, $testssessionsid, $group, $skaters, $day) {
	$data = array();
	for($x = 0; $x < count($skaters); $x++) {
		$id 																=	$mysqli->real_escape_string(isset($skaters[$x]['id'])																		? $skaters[$x]['id'] : '');
		$testssessionsregistrationstestsid	=	$mysqli->real_escape_string(isset($skaters[$x]['testssessionsregistrationstestsid'])		? $skaters[$x]['testssessionsregistrationstestsid'] : '');
		$memberid 													=	$mysqli->real_escape_string(isset($skaters[$x]['memberid'])															? $skaters[$x]['memberid'] : '');
		$sequence 													=	$mysqli->real_escape_string(isset($skaters[$x]['sequence'])															? $skaters[$x]['sequence'] : '');
		$result 														=	$mysqli->real_escape_string(isset($skaters[$x]['result'])																? $skaters[$x]['result'] : '');
		$partnerid 													=	$mysqli->real_escape_string(isset($skaters[$x]['partnerid'])														? $skaters[$x]['partnerid'] : '');
		$musicid 														=	$mysqli->real_escape_string(isset($skaters[$x]['musicid'])															? $skaters[$x]['musicid'] : '');
		$partnersteps 											=	$mysqli->real_escape_string(isset($skaters[$x]['partnersteps'])													? (int) $skaters[$x]['partnersteps'] : 0);
		$canceled 													=	$mysqli->real_escape_string(isset($skaters[$x]['canceled'])															? (int) $skaters[$x]['canceled'] : 0);
		$oldTestid 													=	$mysqli->real_escape_string(isset($skaters[$x]['oldTestid'])														? $skaters[$x]['oldTestid'] : '');
		$newTestid 													=	$mysqli->real_escape_string(isset($skaters[$x]['newTestid'])														? $skaters[$x]['newTestid'] : '');
		$groupid														= (int)$group['id'];
		$testid															= (int)$group['testid'];
		$testdate														= $day['testdatestr'];
		$testssessionsid										= (int) $testssessionsid;

		if ($mysqli->real_escape_string(isset($skaters[$x]['status'])) and $skaters[$x]['status'] == 'New') {
			$query = "INSERT INTO cpa_tests_sessions_groups_skaters(id, testsessionsgroupsid, testssessionsregistrationstestsid, sequence)
								VALUES (null, '$groupid', '$testssessionsregistrationstestsid', '$sequence')";
			if ($mysqli->query($query)) {
			} else {
				throw new Exception($mysqli->sqlstate.' - '. $mysqli->error );
			}
		}

		if ($mysqli->real_escape_string(isset($skaters[$x]['status'])) and $skaters[$x]['status'] == 'Modified') {
			$query = "UPDATE cpa_tests_sessions_groups_skaters SET sequence = '$sequence', testsessionsgroupsid = '$groupid', result = '$result' WHERE id = $id";
			if ($mysqli->query($query)) {
				if ($oldTestid != $newTestid) {
					// testid has changed, we have to change the original registration
					// Now, if testtype changes, it's possible that partnerid, musicid and the rest doesn't mean anything anymore
					$query = "UPDATE cpa_tests_sessions_registrations_tests SET testsid = $newTestid WHERE id = $testssessionsregistrationstestsid";
					if (!$mysqli->query($query)) {
						throw new Exception($mysqli->sqlstate.' - '. $mysqli->error );
					}
				}
				$query = "UPDATE cpa_tests_sessions_registrations_tests SET partnerid = $partnerid, musicid = $musicid, partnersteps = $partnersteps, canceled = $canceled WHERE id = $testssessionsregistrationstestsid";
				if (!$mysqli->query($query)) {
					throw new Exception($mysqli->sqlstate.' - '. $mysqli->error );
				}
				// We need to manage the result. We can only have one instance of a memberid-testid-testsessionid in a the cpa_members_tests table.
				// Start by deleting the test result
				$query = "DELETE FROM cpa_members_tests WHERE memberid = $memberid AND testid = $testid AND testssessionsid = $testssessionsid";
				if (!$mysqli->query($query)) {
					throw new Exception($memberid . ' - ' . $testid . ' - ' . $testssessionsid. ' - ' . $mysqli->sqlstate.' - '. $mysqli->error );
				}
				if ($result == 1 || $result == 2 || $result == 5) {
					// if ($result == 1) { // Success
					// 	$result = 1;
					// } else if ($result == 2) { // Failed
					// 	$result = 0;
					// }
					$query = "INSERT INTO cpa_members_tests(id, memberid, testid, testssessionsid, testdate, success)
										VALUES (null, $memberid, $testid, $testssessionsid, '$testdate', $result)";
					if (!$mysqli->query($query)) {
						throw new Exception($mysqli->sqlstate.' - '. $mysqli->error );
					}

				}
			} else {
				throw new Exception($mysqli->sqlstate.' - '. $mysqli->error );
			}
		}

		if ($mysqli->real_escape_string(isset($skaters[$x]['status'])) and $skaters[$x]['status'] == 'Deleted') {
			$query = "DELETE FROM cpa_tests_sessions_groups_skaters WHERE id = $id";
			if (!$mysqli->query($query)) {
				throw new Exception($mysqli->sqlstate.' - '. $mysqli->error );
			}
		}
	}
	$data['success'] = true;
	return $data;
};

/**
 * This function will handle insert/update/delete of a group in DB
 * @throws Exception
 */
function updateEntireGroups($mysqli, $testperiodsid, $groups, $testssessionsid, $day, $language) {
	$data = array();
	for($x = 0; $x < count($groups); $x++) {
		$id 							=	$mysqli->real_escape_string(isset($groups[$x]['id'])									? $groups[$x]['id'] : '');
		$name 						=	$mysqli->real_escape_string(isset($groups[$x]['name'])								? $groups[$x]['name'] : '');
		$testid 					=	$mysqli->real_escape_string(isset($groups[$x]['testid'])							? $groups[$x]['testid'] : '');
		$groupno 					=	$mysqli->real_escape_string(isset($groups[$x]['groupno'])						  ? $groups[$x]['groupno'] : '');
		$sequence 				=	$mysqli->real_escape_string(isset($groups[$x]['sequence'])						? $groups[$x]['sequence'] : '');
		$warmupduration 	=	$mysqli->real_escape_string(isset($groups[$x]['warmupduration'])			? $groups[$x]['warmupduration'] : '');
		$testduration 		=	$mysqli->real_escape_string(isset($groups[$x]['testduration'])				? $groups[$x]['testduration'] : '');
		$starttime 				=	$mysqli->real_escape_string(isset($groups[$x]['starttime']) 					? $groups[$x]['starttime'] : '');
		$endtime 					=	$mysqli->real_escape_string(isset($groups[$x]['endtime']) 						? $groups[$x]['endtime'] : '');

		if ($mysqli->real_escape_string(isset($groups[$x]['status'])) and $groups[$x]['status'] == 'New') {
			if (empty($name)) {
				// For new groups, we need to create the name, french label, english label, warmupduration and test duration
				$query = "SELECT getCodeDescription('testtypes', ctd.type, 'en-ca') testtypelabelen, getTextLabel(ct.label, 'en-ca') testlabelen,
									getCodeDescription('testtypes', ctd.type, 'fr-ca') testtypelabelfr, getTextLabel(ct.label, 'fr-ca') testlabelfr,
									getCodeDescription('testlevels', ctd.level, 'fr-ca') testlevellabelfr, getCodeDescription('testlevels', ctd.level, 'en-ca') testlevellabelen,
									ctd.warmupduration, ctd.testduration, ctd.type testtype
								  FROM cpa_tests ct
								  JOIN cpa_tests_definitions ctd ON ctd.id = ct.testsdefinitionsid
								  WHERE ct.id = $testid
									AND ctd.version = 1";
				$result = $mysqli->query($query);
				$row = $result->fetch_assoc();
				$firstPart = 'GRP' . str_pad($groupno, 3, '0', STR_PAD_LEFT);
				$danceLevelfr = $row['testtype'] == 'DANCE' ? ' ' . $row['testlevellabelfr'] . ' ' : ' ';
				$danceLevelen = $row['testtype'] == 'DANCE' ? ' ' . $row['testlevellabelen'] . ' ' : ' ';
				$label_fr = $firstPart . ' - ' . $row['testtypelabelfr'] . $danceLevelfr . $row['testlabelfr'];
				$label_en = $firstPart . ' - ' . $row['testtypelabelen'] . $danceLevelen . $row['testlabelen'];
				if ($language == 'fr-ca') {
					$name = $label_fr;
				} else {
					$name = $label_en;
				}
				$warmupduration = $row['warmupduration'];
				$nbskater = count($groups[$x]['skaters']);
				$testduration = $row['testduration'] * $nbskater;
				$totalduration = ($warmupduration + $testduration) * 60 ;
			}
			$query = "INSERT INTO cpa_tests_sessions_groups (id, name, testid, testperiodsid, sequence, warmupduration, testduration, starttime, endtime, label)
								VALUES (null, '$name', '$testid', '$testperiodsid', '$sequence', '$warmupduration', '$testduration', '$starttime', ADDTIME('$starttime', SEC_TO_TIME('$totalduration')), create_systemtext('$label_en', '$label_fr'))";
			if ($mysqli->query($query)) {
				$groups[$x]['id'] = (int) $mysqli->insert_id;
			} else {
				throw new Exception($mysqli->sqlstate.' - '. $mysqli->error );
			}
		}

		if ($mysqli->real_escape_string(isset($groups[$x]['status'])) and $groups[$x]['status'] == 'Modified') {
			$query = "UPDATE cpa_tests_sessions_groups
								SET name = '$name', testid = '$testid', testperiodsid = '$testperiodsid', sequence = '$sequence', warmupduration = '$warmupduration',
										testduration = '$testduration', starttime = '$starttime', endtime = '$endtime'
								WHERE id = $id";
			if (!$mysqli->query($query)) {
				throw new Exception($mysqli->sqlstate.' - '. $mysqli->error );
			}
		}

		if ($mysqli->real_escape_string(isset($groups[$x]['status'])) and $groups[$x]['status'] == 'Deleted') {
			$query = "DELETE FROM cpa_tests_sessions_groups WHERE id = $id";
			if (!$mysqli->query($query)) {
				throw new Exception($mysqli->sqlstate.' - '. $mysqli->error );
			}
		}
	}

	for($x = 0; $x < count($groups); $x++) {
		if (!$mysqli->real_escape_string(isset($groups[$x]['status'])) || ($mysqli->real_escape_string(isset($groups[$x]['status'])) && $groups[$x]['status'] !== 'Deleted')) {
			if ($mysqli->real_escape_string(isset($groups[$x]['skaters']))) {
				$data['skaters'] = updateEntireSkaters($mysqli, $testssessionsid, $groups[$x], $groups[$x]['skaters'], $day);
			}
		}
	}
	$data['success'] = true;
	return $data;
};

function updateEntireTestsession($mysqli, $testsession, $language) {
	try{
		$data = array();
		$id = $mysqli->real_escape_string(isset($testsession['id']) ? $testsession['id'] : '');

		update_testsession($mysqli, $testsession);
		if ($mysqli->real_escape_string(isset($testsession['days']))) {
			$data['successdays'] = updateEntireDays($mysqli, $id, $testsession['days'], $language);
			$data['successcharges'] = updateEntireCharges($mysqli, $id, $testsession['charges'], $language);
		}
		$mysqli->close();

		$data['success'] = true;
		$data['message'] = 'Testsession updated successfully.';
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
 * This function will handle the lock test session operation.
 * @throws Exception
 */
function lockSession($mysqli, $testsessionid) {
	try{
		$data = array();
		$data['success'] = true;
		$id = "";
		$query = "UPDATE cpa_tests_sessions SET islock = 1  WHERE id = $testsessionid";
		if (!$mysqli->query($query)) {
			throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
		}
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
 * This function will handle the unlock test session operation.
 * @throws Exception
 */
function unlockSession($mysqli, $testsessionid) {
	try{
		$data = array();
		$data['success'] = true;
		$id = "";
		$query = "UPDATE cpa_tests_sessions SET islock = 0  WHERE id = $testsessionid";
		if (!$mysqli->query($query)) {
			throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
		}
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
