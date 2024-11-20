<?php
/*
Author : Eric Lamoureux
*/
require_once('../../../private/' . $_SERVER['HTTP_HOST'] . '/include/config.php');
require_once('../../include/nocache.php');
require_once('../../backend/invalidrequest.php');
require_once('../core/directives/testregistration/testregistration.php');

if (isset($_POST['type']) && !empty(isset($_POST['type']))) {
	$type = $_POST['type'];

	switch ($type) {
		case "insert_testsession":
			insert_testsession($mysqli);
			break;
		case "updateEntireTestsession":
			updateEntireTestsession($mysqli, $_POST['testsession'], $_POST['language'], $_POST['userid']);
			break;
		case "delete_testsession":
			delete_testsession($mysqli,  $_POST['testsession']);
			break;
		case "getAllTestsessions":
			getAllTestsessions($mysqli, $_POST['language']);
			break;
		case "getTestsessionDetails":
			getTestsessionDetails($mysqli, $_POST['id'], $_POST['language']);
			break;
		case "insertPeriods":
			insertPeriods($mysqli, $_POST['periods']);
			break;
		case "createGroups":
			createGroups($mysqli, $_POST['testsession'], $_POST['language']);
			break;
		case "copySession":
			copySession($mysqli, $_POST['testsessionid']);
			break;
		case "lockSession":
			lockSession($mysqli, $_POST['testsessionid']);
			break;
		case "unlockSession":
			unlockSession($mysqli, $_POST['testsessionid']);
			break;
		default:
			invalidRequest($type);
	}
} else {
	invalidRequest(null);
};

/**
 * This function will handle testsession add functionality
 * @throws Exception
 */
function insert_testsession($mysqli)
{
	try {
		$data = array();
		$id						= $mysqli->real_escape_string(isset($_POST['testsession']['id'])					? $_POST['testsession']['id'] : '');
		$name					= $mysqli->real_escape_string(isset($_POST['testsession']['name'])					? $_POST['testsession']['name'] : '');
		$label					= $mysqli->real_escape_string(isset($_POST['testsession']['label'])					? $_POST['testsession']['label'] : '');
		$label_fr				= $mysqli->real_escape_string(isset($_POST['testsession']['label_fr'])				? $_POST['testsession']['label_fr'] : $name);
		$label_en				= $mysqli->real_escape_string(isset($_POST['testsession']['label_en'])				? $_POST['testsession']['label_en'] : $name);
		$testsessionstartdate	= $mysqli->real_escape_string(isset($_POST['testsession']['testsessionstartdate'])	? $_POST['testsession']['testsessionstartdate'] : '2039-01-01');
		$testsessionenddate		= $mysqli->real_escape_string(isset($_POST['testsession']['testsessionenddate'])	? $_POST['testsession']['testsessionenddate'] : '2039-01-01');
		$nbofdaysprior			= $mysqli->real_escape_string(isset($_POST['testsession']['nbofdaysprior'])			? (int)$_POST['testsession']['nbofdaysprior'] : 0);

		$query = "INSERT INTO cpa_newtests_sessions (name, label,  testsessionstartdate, testsessionenddate, nbofdaysprior)
				  VALUES ('$name', create_systemText('$label_en', '$label_fr'), '$testsessionstartdate', '$testsessionenddate', $nbofdaysprior)";
		if ($mysqli->query($query)) {
			if (empty($id)) $id = (int) $mysqli->insert_id;
			$data['id'] = $id;
			$query = "INSERT INTO cpa_newtests_sessions_charges (id, newtestssessionsid, chargecode, amount)
					  VALUES (null, $id, 'TEST', 12.00)";
			if ($mysqli->query($query)) {
				$data['success'] = true;
				$data['message'] = 'Testsession inserted successfully.';
			} else {
				throw new Exception($mysqli->sqlstate . ' - ' . $mysqli->error);
			}
		} else {
			throw new Exception($mysqli->sqlstate . ' - ' . $mysqli->error);
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
function update_testsession($mysqli, $testsession)
{
	$data = array();
	$id						= $mysqli->real_escape_string(isset($_POST['testsession']['id'])						? $_POST['testsession']['id'] : '');
	$name					= $mysqli->real_escape_string(isset($_POST['testsession']['name'])						? $_POST['testsession']['name'] : '');
	$label					= $mysqli->real_escape_string(isset($_POST['testsession']['label'])						? $_POST['testsession']['label'] : '');
	$label_fr				= $mysqli->real_escape_string(isset($_POST['testsession']['label_fr'])					? $_POST['testsession']['label_fr'] : $name);
	$label_en				= $mysqli->real_escape_string(isset($_POST['testsession']['label_en'])					? $_POST['testsession']['label_en'] : $name);
	$testsessionstartdate	= $mysqli->real_escape_string(isset($_POST['testsession']['testsessionstartdatestr'])	? $_POST['testsession']['testsessionstartdatestr'] : '');
	$testsessionenddate		= $mysqli->real_escape_string(isset($_POST['testsession']['testsessionenddatestr'])		? $_POST['testsession']['testsessionenddatestr'] : '');
	$nbofdaysprior			= $mysqli->real_escape_string(isset($_POST['testsession']['nbofdaysprior'])				? (int)$_POST['testsession']['nbofdaysprior'] : 0);
	$testdirectorid			= $mysqli->real_escape_string(isset($_POST['testsession']['testdirectorid'])			? (int)$_POST['testsession']['testdirectorid'] : 0);
	$homeclub				= $mysqli->real_escape_string(isset($_POST['testsession']['homeclub'])					? $_POST['testsession']['homeclub'] : '');

	if ($id == '') {
		throw new Exception("Required fields missing, Please enter and submit");
	}

	$query = "UPDATE cpa_newtests_sessions
			  SET name = '$name', testsessionstartdate = '$testsessionstartdate', testsessionenddate = '$testsessionenddate',
				  nbofdaysprior = $nbofdaysprior, testdirectorid = $testdirectorid, homeclub = '$homeclub' 
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
				throw new Exception($mysqli->sqlstate . ' - ' . $mysqli->error);
			}
		} else {
			throw new Exception($mysqli->sqlstate . ' - ' . $mysqli->error);
		}
	} else {
		throw new Exception($mysqli->sqlstate . ' - ' . $mysqli->error);
	}
	return $data;
	exit;
};

/**
 * This function will handle testsession deletion
 * @throws Exception
 */
function delete_testsession($mysqli, $testsession)
{
	try {
		$id		= $mysqli->real_escape_string(isset($testsession['id'])		? $testsession['id'] : '');
		$label 	= $mysqli->real_escape_string(isset($testsession['label']) 	? $testsession['label'] : '');

		if (empty($id)) throw new Exception("Invalid Testsession.");
		// Delete test session text
		$query = "DELETE FROM cpa_text WHERE id = '$label'";
		if ($mysqli->query($query)) {
			// Delete test session
			$query = "DELETE FROM cpa_newtests_sessions WHERE id = $id";
			if ($mysqli->query($query)) {
				$data['success'] = true;
				$data['message'] = 'Testsession deleted successfully.';
				echo json_encode($data);
				exit;
			}
		}
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
function getAllTestsessions($mysqli, $language)
{
	try {
		$query = "SELECT id, getTextLabel(label, '$language') title, islock, getCodeDescription('yesno', islock, '$language') islocklabel, 
		                 testsessionstartdate, testsessionenddate 
				  FROM cpa_newtests_sessions 
				  ORDER BY id DESC";
		$result = $mysqli->query($query);
		$data = array();
		while ($row = $result->fetch_assoc()) {
			$row['islock'] = (int)$row['islock'];
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
 * This function gets the details of all schedules for a testsession from database
 */
function getTestsessionSchedules($mysqli, $newtestssessionsid, $language)
{
	$query = "SELECT cnss.*, (select getTextLabel(cai.label, '$language') from cpa_arenas_ices cai where cai.arenaid = cnss.arenaid and cai.id = cnss.iceid) icelabel
			  FROM cpa_newtests_sessions_schedule cnss
			  WHERE newtestssessionsid = $newtestssessionsid
			  ORDER BY day, starttime";
	$result = $mysqli->query($query);
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
 * This function gets the details of all periods for a testsession from database
 */
function getTestsessionPeriods($mysqli, $newtestssessionsid, $language)
{
	$query = "SELECT cnsp.*, getEnglishTextLabel(label) as label_en, getFrenchTextLabel(label) as label_fr,
					 (select getTextLabel(cai.label, '$language') from cpa_arenas_ices cai where cai.arenaid = cnsp.arenaid and cai.id = cnsp.iceid) icelabel
			  FROM cpa_newtests_sessions_periods cnsp
			  WHERE newtestssessionsid = $newtestssessionsid
			  ORDER BY perioddate, arenaid, iceid";
	$result = $mysqli->query($query);
	$data = array();
	$data['data'] = array();
	while ($row = $result->fetch_assoc()) {
		$row['registrations'] = getPeriodRegistrations($mysqli, $row['id'], $language)['data'];
		$data['data'][] = $row;
	}
	$data['success'] = true;
	return $data;
	exit;
};

/**
 * This function gets the details of one testsession from database
 */
function getTestsessionDetails($mysqli, $id, $language)
{
	try {
		if (empty($id) || $id == '') $id = 0;
		$query = "SELECT *, getEnglishTextLabel(label) as label_en, getFrenchTextLabel(label) as label_fr
				  FROM cpa_newtests_sessions
				  WHERE id = $id";
		$result = $mysqli->query($query);
		$data = array();
		while ($row = $result->fetch_assoc()) {
			$row['islock'] = (int)$row['islock'];
			$row['nbofdaysprior'] = (int)$row['nbofdaysprior'];
			$row['charges'] 	= getTestsessionCharges($mysqli, $id, $language)['data'];
			$row['schedules'] = getTestsessionSchedules($mysqli, $id, $language)['data'];
			$row['periods'] 	= getTestsessionPeriods($mysqli, $id, $language)['data'];
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

function deletePeriods($mysqli, $newtestssessionsid)
{
	$data = array();
	$query = "DELETE FROM cpa_text WHERE id IN (SELECT label FROM cpa_newtests_sessions_periods WHERE newtestssessionsid = $newtestssessionsid)";
	$result = $mysqli->query($query);
	$query = "DELETE FROM cpa_newtests_sessions_periods WHERE newtestssessionsid = $newtestssessionsid";
	$result = $mysqli->query($query);
	$data['success'] = true;
	return $data;
};
/**
 * This function will handle period date insert functionality
 * @throws Exception
 */
function insertPeriods($mysqli, $testsessionPeriods)
{
	try {
		$data = array();
		$data['insert'] = 0;
		if (count($testsessionPeriods) > 0) {
			$newtestssessionsid = $mysqli->real_escape_string(isset($testsessionPeriods[0]['newtestssessionsid']) ? (int) $testsessionPeriods[0]['newtestssessionsid'] : 0);
			$data['deleteperiods'] = deletePeriods($mysqli, $newtestssessionsid);
			for ($x = 0; $x < count($testsessionPeriods); $x++) {
				$newtestssessionsid	= $mysqli->real_escape_string(isset($testsessionPeriods[$x]['newtestssessionsid'])	? $testsessionPeriods[$x]['newtestssessionsid'] : '');
				$perioddate			= $mysqli->real_escape_string(isset($testsessionPeriods[$x]['perioddatestr'])		? $testsessionPeriods[$x]['perioddatestr'] : '');
				$arenaid			= $mysqli->real_escape_string(isset($testsessionPeriods[$x]['arenaid'])				? $testsessionPeriods[$x]['arenaid'] : '');
				$iceid				= $mysqli->real_escape_string(isset($testsessionPeriods[$x]['iceid'])				? $testsessionPeriods[$x]['iceid'] : '');
				$starttime			= $mysqli->real_escape_string(isset($testsessionPeriods[$x]['starttime'])			? $testsessionPeriods[$x]['starttime'] : '');
				$endtime			= $mysqli->real_escape_string(isset($testsessionPeriods[$x]['endtime'])				? $testsessionPeriods[$x]['endtime'] : '');
				$day				= $mysqli->real_escape_string(isset($testsessionPeriods[$x]['day'])					? $testsessionPeriods[$x]['day'] : '');
				// $duration =						$mysqli->real_escape_string(isset($testsessionPeriods[$x]['duration']) 							? $testsessionPeriods[$x]['duration'] : '');

				$query = "INSERT INTO cpa_newtests_sessions_periods (newtestssessionsid, perioddate, arenaid, iceid, starttime, endtime, day, canceled)
						  VALUES ('$newtestssessionsid', '$perioddate', '$arenaid', '$iceid', '$starttime', '$endtime', $day, 0)";

				if ($mysqli->query($query)) {
					$data['insert']++;
				} else {
					throw new Exception($mysqli->sqlstate . ' - ' . $mysqli->error);
				}
			}
			$query = "UPDATE cpa_newtests_sessions SET periodsgenerated = 1 WHERE id = $newtestssessionsid";
			$result = $mysqli->query($query);
			$data['success'] = true;
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
 * This function will handle insert/update/delete of a charge in DB
 * @throws Exception
 */
function updateEntireCharges($mysqli, $newtestssessionsid, $charges, $language)
{
	$data = array();
	for ($x = 0; $x < count($charges); $x++) {
		$id		=	$mysqli->real_escape_string(isset($charges[$x]['id'])		? $charges[$x]['id'] : '');
		$code	=	$mysqli->real_escape_string(isset($charges[$x]['code'])		? $charges[$x]['code'] : '');
		$amount	=	$mysqli->real_escape_string(isset($charges[$x]['amount'])	? (float) $charges[$x]['amount'] : 0);

		if ($mysqli->real_escape_string(isset($charges[$x]['status'])) and $charges[$x]['status'] == 'New') {
			$query = "INSERT INTO cpa_newtests_sessions_charges (newtestssessionsid, code, amount)
					  VALUES ($newtestssessionsid, '$code', $amount)";
			if ($mysqli->query($query)) {
				$charges[$x]['id'] = (int) $mysqli->insert_id;
			} else {
				throw new Exception($mysqli->sqlstate . ' - ' . $mysqli->error);
			}
		}

		if ($mysqli->real_escape_string(isset($charges[$x]['status'])) and $charges[$x]['status'] == 'Modified') {
			$query = "UPDATE cpa_newtests_sessions_charges SET amount = $amount WHERE id = $id";
			if (!$mysqli->query($query)) {
				throw new Exception($mysqli->sqlstate . ' - ' . $mysqli->error);
			}
		}

		if ($mysqli->real_escape_string(isset($charges[$x]['status'])) and $charges[$x]['status'] == 'Deleted') {
			$query = "DELETE FROM cpa_newtests_sessions_charges WHERE id = $id";
			if (!$mysqli->query($query)) {
				throw new Exception($mysqli->sqlstate . ' - ' . $mysqli->error);
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
function updateEntirePeriods($mysqli, $newtestssessionsid, $periods, $language, $userid, $charge)
{
	$data = array();
	for ($x = 0; $x < count($periods); $x++) {
		$id			=	$mysqli->real_escape_string(isset($periods[$x]['id'])			? $periods[$x]['id'] : '');
		$arenaid	=	$mysqli->real_escape_string(isset($periods[$x]['arenaid'])		? (int)$periods[$x]['arenaid'] : 0);
		$iceid		=	$mysqli->real_escape_string(isset($periods[$x]['iceid'])		? (int)$periods[$x]['iceid'] : 0);
		$perioddate	=	$mysqli->real_escape_string(isset($periods[$x]['perioddate'])	? $periods[$x]['perioddate'] : '');
		$day		=	$mysqli->real_escape_string(isset($periods[$x]['day'])			? (int)$periods[$x]['day'] : 0);
		$starttime	=	$mysqli->real_escape_string(isset($periods[$x]['starttime'])	? $periods[$x]['starttime'] : '');
		$endtime	=	$mysqli->real_escape_string(isset($periods[$x]['endtime'])		? $periods[$x]['endtime'] : '');
		$duration	=	$mysqli->real_escape_string(isset($periods[$x]['duration'])		? (int)$periods[$x]['duration'] : 0);
		$canceled	=	$mysqli->real_escape_string(isset($periods[$x]['canceled'])		? (int)$periods[$x]['canceled'] : 0);
		$label		=	$mysqli->real_escape_string(isset($periods[$x]['label'])		? $periods[$x]['label'] : '');
		$label_fr	=	$mysqli->real_escape_string(isset($periods[$x]['label_fr'])		? $periods[$x]['label_fr'] : '');
		$label_en	=	$mysqli->real_escape_string(isset($periods[$x]['label_en'])		? $periods[$x]['label_en'] : '');
		$manual		=	$mysqli->real_escape_string(isset($periods[$x]['manual'])		? (int)$periods[$x]['manual'] : 0);

		if ($mysqli->real_escape_string(isset($periods[$x]['status'])) and $periods[$x]['status'] == 'New') {
			$query = "INSERT INTO cpa_newtests_sessions_periods(id, newtestssessionsid, arenaid, iceid, perioddate, day, starttime, endtime, duration, canceled, label, manual)
					  VALUES (null, $newtestssessionsid, $arenaid, $iceid, '$perioddate', $day, '$starttime', '$endtime', $duration, $canceled, create_systemtext('$label_en','$label_fr'), $manual)";
			if ($mysqli->query($query)) {
				$periods[$x]['id'] = (int) $mysqli->insert_id;
			} else {
				throw new Exception($mysqli->sqlstate . ' - ' . $mysqli->error);
			}
		}

		if ($mysqli->real_escape_string(isset($periods[$x]['status'])) and $periods[$x]['status'] == 'Modified') {
			if (!$mysqli->real_escape_string(isset($periods[$x]['label'])) || empty($periods[$x]['label']) || $periods[$x]['label'] == "") {
				$query = "UPDATE cpa_newtests_sessions_periods SET arenaid=$arenaid, iceid=$iceid, perioddate='$perioddate', day=$day, starttime='$starttime', endtime='$endtime', duration=$duration, canceled=$canceled, manual=$manual, label = create_systemText('$label_en', '$label_fr')  WHERE id = $id";
				if ($mysqli->query($query)) {
				} else {
					throw new Exception($mysqli->sqlstate . ' - ' . $mysqli->error);
				}
			} else {
				$query = "UPDATE cpa_newtests_sessions_periods SET arenaid=$arenaid, iceid=$iceid, perioddate='$perioddate', day=$day, starttime='$starttime', endtime='$endtime', duration=$duration, canceled=$canceled, manual=$manual WHERE id = $id";
				if ($mysqli->query($query)) {
					$query = "UPDATE cpa_text set text = '$label_fr' where id = $label and language = 'fr-ca'";
					if ($mysqli->query($query)) {
						$query = "UPDATE cpa_text set text = '$label_en' where id = $label and language = 'en-ca'";
						if ($mysqli->query($query)) {
						} else {
							throw new Exception($mysqli->sqlstate . ' - ' . $mysqli->error);
						}
					} else {
						throw new Exception($mysqli->sqlstate . ' - ' . $mysqli->error);
					}
				} else {
					throw new Exception($mysqli->sqlstate . ' - ' . $mysqli->error);
				}
			}
		}

		// We cannot delete a period
		// if ($mysqli->real_escape_string(isset($periods[$x]['status'])) and $periods[$x]['status'] == 'Deleted') {
		// 	$query = "DELETE FROM cpa_newtests_sessions_periods WHERE id = $id";
		// 	if (!$mysqli->query($query)) {
		// 		throw new Exception($mysqli->sqlstate.' - '. $mysqli->error );
		// 	}
		// }

		// For all registrations in a period
		if ($mysqli->real_escape_string(isset($periods[$x]['registrations']))) {
			$data['successregistrations'] = updateEntireRegistrations($mysqli, $periods[$x]['registrations'], $userid, $perioddate, $charge, $language);
		}
	}
	$data['success'] = true;
	return $data;
};

/**
 * This function will handle insert/update/delete of schedules in DB
 * @throws Exception
 */
function updateEntireSchedules($mysqli, $newtestssessionsid, $schedules, $language)
{
	$data = array();
	for ($x = 0; $x < count($schedules); $x++) {
		$id			=	$mysqli->real_escape_string(isset($schedules[$x]['id'])			? $schedules[$x]['id'] : '');
		$arenaid	=	$mysqli->real_escape_string(isset($schedules[$x]['arenaid'])	? (int)$schedules[$x]['arenaid'] : '');
		$iceid		=	$mysqli->real_escape_string(isset($schedules[$x]['iceid'])		? (int)$schedules[$x]['iceid'] : '');
		$day		=	$mysqli->real_escape_string(isset($schedules[$x]['day'])		? (int)$schedules[$x]['day'] : '');
		$starttime	=	$mysqli->real_escape_string(isset($schedules[$x]['starttime'])	? $schedules[$x]['starttime'] : '');
		$endtime	=	$mysqli->real_escape_string(isset($schedules[$x]['endtime'])	? $schedules[$x]['endtime'] : '');

		if ($mysqli->real_escape_string(isset($schedules[$x]['status'])) and $schedules[$x]['status'] == 'New') {
			$query = "INSERT INTO cpa_newtests_sessions_schedule(id, newtestssessionsid, arenaid, iceid, day, starttime, endtime)
					  VALUES (null, $newtestssessionsid, $arenaid, $iceid, $day, '$starttime', '$endtime')";
			if ($mysqli->query($query)) {
				$schedules[$x]['id'] = (int) $mysqli->insert_id;
			} else {
				throw new Exception($mysqli->sqlstate . ' - ' . $mysqli->error);
			}
		}

		if ($mysqli->real_escape_string(isset($schedules[$x]['status'])) and $schedules[$x]['status'] == 'Modified') {
			$query = "UPDATE cpa_newtests_sessions_schedule SET arenaid=$arenaid, iceid=$iceid, day=$day, starttime='$starttime', endtime='$endtime' WHERE id = $id";
			if (!$mysqli->query($query)) {
				throw new Exception($mysqli->sqlstate . ' - ' . $mysqli->error);
			}
		}

		if ($mysqli->real_escape_string(isset($schedules[$x]['status'])) and $schedules[$x]['status'] == 'Deleted') {
			$query = "DELETE FROM cpa_newtests_sessions_schedule WHERE id = $id";
			if (!$mysqli->query($query)) {
				throw new Exception($mysqli->sqlstate . ' - ' . $mysqli->error);
			}
		}
	}
	$data['success'] = true;
	return $data;
};

function updateEntireTestsession($mysqli, $testsession, $language, $userid)
{
	try {
		$data = array();
		$id = $mysqli->real_escape_string(isset($testsession['id']) ? $testsession['id'] : '');

		update_testsession($mysqli, $testsession);
		$data['successcharges'] 	= updateEntireCharges($mysqli, $id, $testsession['charges'], $language);
		if ($mysqli->real_escape_string(isset($testsession['schedules'])) && count($testsession['schedules']) != 0) {
			$data['successschedules'] = updateEntireSchedules($mysqli, $id, $testsession['schedules'], $language);
		}
		if ($mysqli->real_escape_string(isset($testsession['periods'])) && count($testsession['periods']) != 0) {
			$data['successperiods'] 	= updateEntirePeriods($mysqli, $id, $testsession['periods'], $language, $userid, $testsession['charges'][0]['amount']);
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
 * This function will handle the copy test session operation.
 * @throws Exception
 */
function copySession($mysqli, $testsessionid)
{
	try {
		$data = array();
		$id = "";
		$query = "INSERT INTO cpa_newtests_sessions(id, name, label, testsessionstartdate, testsessionenddate, nbofdaysprior, testdirectorid, homeclub, periodsgenerated)
				  SELECT null, 'name/nom', create_systemtext(getEnglishTextLabel(label), getFrenchTextLabel(label)), testsessionstartdate, testsessionenddate, nbofdaysprior, testdirectorid, homeclub, 0
				  FROM cpa_newtests_sessions 
				  WHERE id = $testsessionid";
		if ($mysqli->query($query)) {
			$id = (int) $mysqli->insert_id;
			$query = "UPDATE cpa_newtests_sessions SET name = concat(name, ' ',  $id) WHERE id = $id";
			if ($mysqli->query($query)) {
				$query = "INSERT INTO `cpa_newtests_sessions_charges`(`id`, `newtestssessionsid`, `chargecode`, `amount`)
						  SELECT null, $id, `chargecode`, `amount`
						  FROM cpa_newtests_sessions_charges 
						  WHERE newtestssessionsid = $testsessionid";
				if ($mysqli->query($query)) {
					$data['success'] = true;
					$query = "INSERT INTO `cpa_newtests_sessions_schedule`(`id`, `newtestssessionsid`, `arenaid`, `iceid`, `day`, `starttime`, `endtime`)
							  SELECT null, $id, `arenaid`, `iceid`, `day`, `starttime`, `endtime`
							  FROM `cpa_newtests_sessions_schedule` 
							  WHERE newtestssessionsid = $testsessionid";
					if ($mysqli->query($query)) {
						$data['success'] = true;
					} else {
						throw new Exception($mysqli->sqlstate . ' - ' . $mysqli->error);
					}
				} else {
					throw new Exception($mysqli->sqlstate . ' - ' . $mysqli->error);
				}
			} else {
				throw new Exception($mysqli->sqlstate . ' - ' . $mysqli->error);
			}
		} else {
			throw new Exception($mysqli->sqlstate . ' - ' . $mysqli->error);
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
 * This function will handle the lock test session operation.
 * @throws Exception
 */
function lockSession($mysqli, $testsessionid)
{
	try {
		$data = array();
		$data['success'] = true;
		$id = "";
		$query = "UPDATE cpa_newtests_sessions SET islock = 1  WHERE id = $testsessionid";
		if (!$mysqli->query($query)) {
			throw new Exception($mysqli->sqlstate . ' - ' . $mysqli->error);
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
 * This function will handle the unlock test session operation.
 * @throws Exception
 */
function unlockSession($mysqli, $testsessionid)
{
	try {
		$data = array();
		$data['success'] = true;
		$id = "";
		$query = "UPDATE cpa_newtests_sessions SET islock = 0  WHERE id = $testsessionid";
		if (!$mysqli->query($query)) {
			throw new Exception($mysqli->sqlstate . ' - ' . $mysqli->error);
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
