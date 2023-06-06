<?php
/*
Author : Eric Lamoureux
*/
require_once('../../../private/'. $_SERVER['HTTP_HOST'].'/include/config.php');
require_once('../../include/nocache.php');
require_once('../core/directives/testregistration/testregistration.php');

if (isset($_POST['type']) && !empty(isset($_POST['type']))) {
	$type = $_POST['type'];

	switch ($type) {
		case "updateEntireRegistration":
			updateEntireRegistration($mysqli, $_POST['registration'], $_POST['userid'], $_POST['language']);
			break;
		case "getAllPeriods":
			getAllPeriods($mysqli, $_POST['language']);
			break;
		default:
			invalidRequest();
	}
} else {
	invalidRequest();
};

/**
 * This function gets list of all periods from database
 */
function getAllPeriods($mysqli, $language) {
	try{
		$query = "SELECT cnsp.*, cns.nbofdaysprior,
											(select getTextLabel(cai.label, '$language') from cpa_arenas_ices cai where cai.arenaid = cnsp.arenaid and cai.id = cnsp.iceid) icelabel
							FROM cpa_newtests_sessions_periods cnsp
							JOIN cpa_newtests_sessions cns ON cns.id = cnsp.newtestssessionsid
							WHERE perioddate >= curdate()
							AND cnsp.canceled = 0
							ORDER BY perioddate, arenaid, iceid";
		$result = $mysqli->query($query);
		$data = array();
		while ($row = $result->fetch_assoc()) {
			$row['registrations'] = getPeriodRegistrations($mysqli, $row['id'], $language, false)['data'];
			$data['data'][] = $row;
		}
		$data['success'] = true;
		echo json_encode($data);exit;
	}catch (Exception $e) {
		$data = array();
		$data['success'] = false;
		$data['message'] = $e->getMessage();
		echo json_encode($data);
		exit;
	}
};

function updateEntireRegistration($mysqli, $registration, $userid, $language) {
	try{
		$data = array();

		if ($mysqli->real_escape_string(isset($registration['periods']))) {
			$data['periods'] = array();
			for($x = 0; $x < count($registration['periods']); $x++) {
				$period = $registration['periods'][$x];
				if ($mysqli->real_escape_string(isset($period['registrations']))) {
					$data['successperiods'] = updateEntireRegistrations($mysqli, $period['registrations'], $userid, $period['perioddate'], null, $language);
				}
			}
		}
		$mysqli->close();

		$data['success'] = true;
		$data['message'] = 'Registration updated successfully.';
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
