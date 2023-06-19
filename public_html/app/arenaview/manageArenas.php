<?php
/*
Author : Eric Lamoureux
*/
require_once('../../../private/'. $_SERVER['HTTP_HOST'].'/include/config.php');
require_once('../../include/nocache.php');

if (isset($_POST['type']) && !empty(isset($_POST['type']))) {
	$type = $_POST['type'];

	switch ($type) {
		case "insert_arena":
			insert_arena($mysqli);
			break;
		case "updateEntireArena":
			updateEntireArena($mysqli, $_POST['arena']);
			break;
		case "delete_arena":
			delete_arena($mysqli, $_POST['arena']);
			break;
		case "getAllArenas":
			getAllArenas($mysqli);
			break;
		case "getArenaDetails":
			getArenaDetails($mysqli, $_POST['id'], $_POST['language']);
			break;
		default:
			invalidRequest();
	}
} else {
	invalidRequest();
};

/**
 * This function will handle arena add, update functionality
 * @throws Exception
 */
function insert_arena($mysqli) {
	try {
		$data = array();
		$id =					$mysqli->real_escape_string(isset($_POST['arena']['id']) 					? $_POST['arena']['id'] : '');
		$name =				$mysqli->real_escape_string(isset($_POST['arena']['name']) 				? $_POST['arena']['name'] : '');
		$label =			$mysqli->real_escape_string(isset($_POST['arena']['label']) 			? $_POST['arena']['label'] : '');
		$label_fr =		$mysqli->real_escape_string(isset($_POST['arena']['label_fr']) 		? $_POST['arena']['label_fr'] : '');
		$label_en =		$mysqli->real_escape_string(isset($_POST['arena']['label_en']) 		? $_POST['arena']['label_en'] : '');
		$address =		$mysqli->real_escape_string(isset($_POST['arena']['address']) 		? $_POST['arena']['address'] : '');
		$active =			$mysqli->real_escape_string(isset($_POST['arena']['active']) 			? (int)$_POST['arena']['active'] : 0);

		if ($name == '') {
			throw new Exception("Required fields missing, Please enter and submit");
		}

		$query = "INSERT INTO cpa_arenas (name, label, address, active)
							VALUES ('$name', create_systemText('$name', '$name'), '$address', $active)";
		if ($mysqli->query($query)) {
			$data['success'] = true;
			if (empty($id))$data['id'] = (int) $mysqli->insert_id;
		} else {
			throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
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
 * This function will handle arena add, update functionality
 * @throws Exception
 */
function update_arena($mysqli, $arena) {
	try {
		$data = array();
		$id =						$mysqli->real_escape_string(isset($arena['id']) 					? (int)$arena['id'] : '');
		$name =					$mysqli->real_escape_string(isset($arena['name']) 				? $arena['name'] : '');
		$label =				$mysqli->real_escape_string(isset($arena['label']) 			? (int)$arena['label'] : '');
		$label_fr =			$mysqli->real_escape_string(isset($arena['label_fr']) 		? $arena['label_fr'] : '');
		$label_en =			$mysqli->real_escape_string(isset($arena['label_en']) 		? $arena['label_en'] : '');
		$address =			$mysqli->real_escape_string(isset($arena['address']) 		? $arena['address'] : '');
		$nbrofice =			$mysqli->real_escape_string(isset($arena['nbrofice']) 		? (int)$arena['nbrofice'] : 1);
		$active =				$mysqli->real_escape_string(isset($arena['active']) 			? (int)$arena['active'] : 0);

		if ($name == '' || $id == '') {
			throw new Exception("Required fields missing, Please enter and submit");
		}

		$query = "UPDATE cpa_arenas SET name = '$name', address = '$address', active = '$active' WHERE id = $id";
		if ($mysqli->query($query)) {
			$query = "UPDATE cpa_text set text = '$label_fr' where id = $label and language = 'fr-ca'";
			if ($mysqli->query($query)) {
				$data['success'] = true;
				$query = "UPDATE cpa_text set text = '$label_en' where id = $label and language = 'en-ca'";
				if ($mysqli->query($query)) {
					$data['success'] = true;
					$data['message'] = 'Arena updated successfully.';
				} else {
					throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
				}
			} else {
				throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
			}
		} else {
			throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
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
 * This function will handle user deletion
 * @throws Exception
 */
function delete_arena($mysqli, $arena) {
	try {
		$id = 		$mysqli->real_escape_string(isset($arena['id']) 		? (int)$arena['id'] : '');
		$label = 	$mysqli->real_escape_string(isset($arena['label']) 	? (int)$arena['label'] : '');

		if (empty($id)) throw new Exception("Invalid arena id.");
		$query = "DELETE FROM cpa_arenas WHERE id = $id";
		if ($mysqli->query($query)) {
			$query = "DELETE FROM cpa_text WHERE id = $label";
			if ($mysqli->query($query)) {
				$data['success'] = true;
				$data['message'] = 'Arena deleted successfully.';
				echo json_encode($data);
				exit;
			} else {
				throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
			}
		} else {
			throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
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
 * This function gets list of all arenas from database
 */
function getAllArenas($mysqli) {
	try {
		$query = "SELECT id, name FROM cpa_arenas ORDER BY name";
		$result = $mysqli->query($query);
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
 * This function gets the details of all rooms for an arena ice from database
 */
function getArenaIceRooms($mysqli, $arenaid, $iceid, $language) {
	$query = "SELECT *, getEnglishTextLabel(label) as label_en, getFrenchTextLabel(label) as label_fr, getTextLabel(label, '$language') as roomlabel 
						FROM cpa_arenas_ices_rooms 
						WHERE arenaid = $arenaid
						AND iceid " . ($iceid == null? "is null" : "= $iceid");
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
 * This function gets the details of all seats for an arena ice from database
 */
function getArenaIceSeats($mysqli, $arenaid, $iceid, $language) {
	$query = "SELECT *
						FROM cpa_arenas_ices_seats 
						WHERE arenaid = $arenaid
						AND iceid = $iceid";
	$result = $mysqli->query($query);
	$data = array();
	$data['data'] = array();
	while ($row = $result->fetch_assoc()) {
		$row['sectionnb'] = 	(int)$row['sectionnb'];
		$row['sectioninc'] = 	(int)$row['sectioninc'];
		$row['rownb'] = 			(int)$row['rownb'];
		$row['rowinc'] = 			(int)$row['rowinc'];
		$row['seatnb'] = 			(int)$row['seatnb'];
		$row['seatinc'] = 		(int)$row['seatinc'];
		$data['data'][] = $row;
	}
	if (!isset($data['data'][0])) {
		$data['data'][0]['id'] = null;
	}
	$data['success'] = true;
	return $data;
	exit;
};

/**
 * This function gets the details of all exceptions for an arena ice from database
 */
function getArenaIceExceptions($mysqli, $arenaid, $iceid, $language) {
	$query = "SELECT *
						FROM cpa_arenas_ices_exceptions 
						WHERE arenaid = $arenaid
						AND iceid " . ($iceid == null? "is null" : "= $iceid");
	$result = $mysqli->query($query);
	$data = array();
	$data['data'] = array();
	while ($row = $result->fetch_assoc()) {
		$data['data'][] = $row;
	}
//	if (!isset($data['data'][0])) {
//		$data['data'][0]['id'] = null;
//	}
	$data['success'] = true;
	return $data;
	exit;
};

/**
 * This function gets the details of all ices for an arena from database
 */
function getArenaIces($mysqli, $arenaid, $language) {
	$query = "SELECT *, getEnglishTextLabel(label) as label_en, getFrenchTextLabel(label) as label_fr, getTextLabel(label, '$language') as icelabel 
						FROM cpa_arenas_ices 
						WHERE arenaid = $arenaid";
	$result = $mysqli->query($query);
	$data = array();
	$data['data'] = array();
	while ($row = $result->fetch_assoc()) {
		$iceid = $row['id'];
		$row['rooms'] = getArenaIceRooms($mysqli, $arenaid, $iceid, $language)['data'];
		$row['seats'] = getArenaIceSeats($mysqli, $arenaid, $iceid, $language)['data'][0];
		$row['exceptions'] = getArenaIceExceptions($mysqli, $arenaid, $iceid, $language)['data'];
		$data['data'][] = $row;
	}
	$data['success'] = true;
	return $data;
	exit;
};

/**
 * This function gets the details of all seats for an arena from database
 */
function getArenaSeats($mysqli, $arenaid, $language) {
	$query = "SELECT *
						FROM cpa_arenas_ices_seats 
						WHERE arenaid = $arenaid
						AND iceid is null";
	$result = $mysqli->query($query);
	$data = array();
	$data['data'] = array();
	while ($row = $result->fetch_assoc()) {
		$row['sectionnb'] = 	(int)$row['sectionnb'];
		$row['sectioninc'] = 	(int)$row['sectioninc'];
		$row['rownb'] = 			(int)$row['rownb'];
		$row['rowinc'] = 			(int)$row['rowinc'];
		$row['seatnb'] = 			(int)$row['seatnb'];
		$row['seatinc'] = 		(int)$row['seatinc'];
		$data['data'][] = $row;
	}
	if (!isset($data['data'][0])) {
		$data['data'][0]['id'] = null;
	}

	$data['success'] = true;
	return $data;
	exit;
};

/**
 * This function gets the details of web site info for an arena from database
 */
function getArenaWebsite($mysqli, $arenaid, $language) {
	try {
		$query = "SELECT cwp.*, getWsTextLabel(label, 'fr-ca') label_fr, getWsTextLabel(label, 'en-ca') label_en
							FROM cpa_ws_arenas cwp
							WHERE cwp.arenaid = $arenaid";
		$result = $mysqli->query($query);
		$data = array();
		$data['data'] = array();
		while ($row = $result->fetch_assoc()) {
			$data['data'][] = $row;
		}
		$data['success'] = true;
		if (!isset($data['data'][0])) {
			$data['data'][0]['id'] = 0;
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
 * This function gets the details of one arena from database
 */
function getArenaDetails($mysqli, $id, $language) {
	try {
		$query = "SELECT *, getEnglishTextLabel(label) as label_en, getFrenchTextLabel(label) as label_fr,
							(SELECT count(*) FROM cpa_newtests_sessions_periods cnsp WHERE cnsp.arenaid = ca.id) +
							(SELECT count(*) FROM cpa_sessions_courses_schedule cscs WHERE cscs.arenaid = ca.id) +
							(SELECT count(*) FROM cpa_sessions_icetimes csi WHERE csi.arenaid = ca.id) +
							(SELECT count(*) FROM cpa_shows_performances csi WHERE csi.arenaid = ca.id) +
							(SELECT count(*) FROM cpa_tests_sessions_days_periods ctsdp WHERE ctsdp.arenaid = ca.id) as isused
							FROM cpa_arenas ca
							WHERE id = $id";
		$result = $mysqli->query($query);
		$data = array();
		while ($row = $result->fetch_assoc()) {
			$row['ices'] = getArenaIces($mysqli, $id, $language)['data'];
			$row['rooms'] = getArenaIceRooms($mysqli, $id, null, $language)['data'];
			$row['seats'] = getArenaSeats($mysqli, $id, $language)['data'][0];
			$row['exceptions'] = getArenaIceExceptions($mysqli, $id, null, $language)['data'];
			$row['website'] = getArenaWebsite($mysqli, $id, $language)['data'][0];
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
 * This function will handle insert/update/delete of ice rooms in DB
 * @throws Exception
 */
function updateEntireIceRooms($mysqli, $arenaid, $iceid, $rooms) {
	$data = array();
	for ($x = 0; $rooms && $x < count($rooms); $x++) {
		$id = 				$mysqli->real_escape_string(isset($rooms[$x]['id'])					? (int)$rooms[$x]['id'] : '');
		$label = 			$mysqli->real_escape_string(isset($rooms[$x]['label']) 			? (int)$rooms[$x]['label'] : '');
		$label_en = 	$mysqli->real_escape_string(isset($rooms[$x]['label_en']) 	? $rooms[$x]['label_en'] : '');
		$label_fr = 	$mysqli->real_escape_string(isset($rooms[$x]['label_fr']) 	? $rooms[$x]['label_fr'] : '');
		$comments = 	$mysqli->real_escape_string(isset($rooms[$x]['comments'])		? $rooms[$x]['comments'] : '');

		if ($mysqli->real_escape_string(isset($rooms[$x]['status'])) and $rooms[$x]['status'] == 'New') {
			$query = "INSERT INTO cpa_arenas_ices_rooms (id, arenaid, iceid, label, comments)
								VALUES (null, $arenaid, " . ($iceid==null?" null" : "$iceid") . ", create_systemText('$label_en', '$label_fr'), '$comments')";
			if (!$mysqli->query($query)) {
				throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
			}
		}

		if ($mysqli->real_escape_string(isset($rooms[$x]['status'])) and $rooms[$x]['status'] == 'Modified') {
			$query = "UPDATE cpa_arenas_ices_rooms SET comments = '$comments'	WHERE id = $id";
			if ($mysqli->query($query)) {
				$query = "UPDATE cpa_text SET text = '$label_fr' WHERE id = $label AND language = 'fr-ca'";
				if ($mysqli->query($query)) {
					$data['success'] = true;
					$query = "UPDATE cpa_text SET text = '$label_en' WHERE id = $label AND language = 'en-ca'";
					if (!$mysqli->query($query)) {
						throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
					}
				} else {
					throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
				}
			} else {
				throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
			}
		}

		if ($mysqli->real_escape_string(isset($rooms[$x]['status'])) and $rooms[$x]['status'] == 'Deleted') {
			$query = "DELETE FROM cpa_arenas_ices_rooms WHERE id = $id";
			if ($mysqli->query($query)) {
				$query = "DELETE FROM cpa_text WHERE id = $label";
				if (!$mysqli->query($query)) {
					throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
				}
			} else {
				throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
			}
		}
	}
	$data['success'] = true;
	return $data;
};

/**
 * This function will handle insert/update/delete of ice seats in DB
 * @throws Exception
 */
function updateSeats($mysqli, $arenaid, $iceid, $seats) {
	$data = array();
	$id = 					$mysqli->real_escape_string(isset($seats['id'])						? (int)$seats['id'] : 0);
	$sectiontype = 	$mysqli->real_escape_string(isset($seats['sectiontype'])	? (int)$seats['sectiontype'] : 0);
	$sectionfirst = $mysqli->real_escape_string(isset($seats['sectionfirst'])	? $seats['sectionfirst'] : 'A');
	$sectionnb = 		$mysqli->real_escape_string(isset($seats['sectionnb'])		? (int)$seats['sectionnb'] : 0);
	$sectioninc = 	$mysqli->real_escape_string(isset($seats['sectioninc'])		? (int)$seats['sectioninc'] : 0);
	$rowtype = 			$mysqli->real_escape_string(isset($seats['rowtype'])			? (int)$seats['rowtype'] : 1);
	$rowfirst = 		$mysqli->real_escape_string(isset($seats['rowfirst'])			? $seats['rowfirst'] : '100');
	$rownb = 				$mysqli->real_escape_string(isset($seats['rownb'])				? (int)$seats['rownb'] : 0);
	$rowinc = 			$mysqli->real_escape_string(isset($seats['rowinc'])				? (int)$seats['rowinc'] : 0);
	$seattype = 		$mysqli->real_escape_string(isset($seats['seattype'])			? (int)$seats['seattype'] : 1);
	$seatfirst = 		$mysqli->real_escape_string(isset($seats['seatfirst'])		? $seats['seatfirst'] : '1');
	$seatnb = 			$mysqli->real_escape_string(isset($seats['seatnb'])				? (int)$seats['seatnb'] : 1);
	$seatinc = 			$mysqli->real_escape_string(isset($seats['seatinc'])			? (int)$seats['seatinc'] : 0);

	if ($id == 0) {
		if ($iceid == null) {
			$query = "INSERT INTO cpa_arenas_ices_seats(id, arenaid, iceid, sectiontype, sectionfirst, sectionnb, sectioninc, rowtype, rowfirst, 
																									rownb, rowinc, seattype, seatfirst, seatnb, seatinc) 
							  VALUES (null, $arenaid, null, $sectiontype, '$sectionfirst', $sectionnb, $sectioninc, $rowtype,
							          '$rowfirst', $rownb, $rowinc, $seattype, '$seatfirst', $seatnb, $seatinc)";
		} else {
			$query = "INSERT INTO cpa_arenas_ices_seats(id, arenaid, iceid, sectiontype, sectionfirst, sectionnb, sectioninc, rowtype, rowfirst, 
																									rownb, rowinc, seattype, seatfirst, seatnb, seatinc) 
							  VALUES (null, $arenaid, $iceid, $sectiontype, '$sectionfirst', $sectionnb, $sectioninc, $rowtype,
							          '$rowfirst', $rownb, $rowinc, $seattype, '$seatfirst', $seatnb, $seatinc)";
		}
		if (!$mysqli->query($query)) {
			throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
		}
		$data['optype'] = 'insert';
	} else {
		$query = "UPDATE cpa_arenas_ices_seats 
						  SET sectiontype='$sectiontype',sectionfirst='$sectionfirst',sectionnb='$sectionnb',sectioninc='$sectioninc',
						      rowtype='$rowtype',rowfirst='$rowfirst',rownb='$rownb',rowinc='$rowinc',
						      seattype='$seattype',seatfirst='$seatfirst',seatnb='$seatnb',seatinc='$seatinc'	
						      WHERE id = $id";
		if (!$mysqli->query($query)) {
			throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
		}
		$data['optype'] = 'update';
	}

	$data['success'] = true;
	return $data;
};

/**
 * This function will handle insert/update/delete of exceptions in the DB
 * @throws Exception
 */
function updateEntireExceptions($mysqli, $arenaid, $iceid, $exceptions) {
	$data = array();
	for ($x = 0; $exceptions && $x < count($exceptions); $x++) {
		$id = 			$mysqli->real_escape_string(isset($exceptions[$x]['id'])				? (int)$exceptions[$x]['id'] : '');
		$section = 	$mysqli->real_escape_string(isset($exceptions[$x]['section'])		? $exceptions[$x]['section'] : '');
		$row = 			$mysqli->real_escape_string(isset($exceptions[$x]['row']) 			? $exceptions[$x]['row'] : null);
		$seat = 		$mysqli->real_escape_string(isset($exceptions[$x]['seat']) 			? $exceptions[$x]['seat'] : null);
		$reason = 	$mysqli->real_escape_string(isset($exceptions[$x]['reason']) 		? $exceptions[$x]['reason'] : 0);

		if ($mysqli->real_escape_string(isset($exceptions[$x]['status'])) and $exceptions[$x]['status'] == 'New') {
			if ($iceid == null) {
				$query = "INSERT INTO cpa_arenas_ices_exceptions (id, arenaid, iceid, section, row, seat, reason)
									VALUES (null, $arenaid, null, $section, $row, $seat, $reason)";
			} else {
				$query = "INSERT INTO cpa_arenas_ices_exceptions (id, arenaid, iceid, section, row, seat, reason)
									VALUES (null, $arenaid, $iceid, '$section', '$row', '$seat', $reason)";
			}
			if (!$mysqli->query($query)) {
				throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
			}
		}

		if ($mysqli->real_escape_string(isset($exceptions[$x]['status'])) and $exceptions[$x]['status'] == 'Modified') {
			$query = "UPDATE cpa_arenas_ices_exceptions SET section = '$section', row = '$row', seat = '$seat', reason = '$reason'	WHERE id = $id";
			if (!$mysqli->query($query)) {
				throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
			}
		}

		if ($mysqli->real_escape_string(isset($exceptions[$x]['status'])) and $exceptions[$x]['status'] == 'Deleted') {
			$query = "DELETE FROM cpa_arenas_ices WHERE id = $id";
			if (!$mysqli->query($query)) {
				throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
			}
		}
	}
	
	$data['success'] = true;
	return $data;
};

/**
 * This function will handle insert/update/delete of a ice in DB
 * @throws Exception
 */
function updateEntireIces($mysqli, $arenaid, $ices) {
	$data = array();
	for ($x = 0; $ices && $x < count($ices); $x++) {
		$id = 				$mysqli->real_escape_string(isset($ices[$x]['id'])				? (int)$ices[$x]['id'] : '');
		$code = 			$mysqli->real_escape_string(isset($ices[$x]['code'])			? $ices[$x]['code'] : '');
		$label = 			$mysqli->real_escape_string(isset($ices[$x]['label']) 		? (int)$ices[$x]['label'] : '');
		$label_en = 	$mysqli->real_escape_string(isset($ices[$x]['label_en']) 	? $ices[$x]['label_en'] : '');
		$label_fr = 	$mysqli->real_escape_string(isset($ices[$x]['label_fr']) 	? $ices[$x]['label_fr'] : '');

		if ($mysqli->real_escape_string(isset($ices[$x]['status'])) and $ices[$x]['status'] == 'New') {
			$query = "INSERT INTO cpa_arenas_ices (id, arenaid, code, label)
								VALUES (null, $arenaid, '$code', create_systemText('$label_en', '$label_fr'))";
			if (!$mysqli->query($query)) {
				throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
			}
		}

		if ($mysqli->real_escape_string(isset($ices[$x]['status'])) and $ices[$x]['status'] == 'Modified') {
			$query = "UPDATE cpa_arenas_ices SET code = '$code'	WHERE id = $id";
			if ($mysqli->query($query)) {
				$query = "UPDATE cpa_text SET text = '$label_fr' WHERE id = $label AND language = 'fr-ca'";
				if ($mysqli->query($query)) {
					$data['success'] = true;
					$query = "UPDATE cpa_text SET text = '$label_en' WHERE id = $label AND language = 'en-ca'";
					if (!$mysqli->query($query)) {
						throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
					}
				} else {
					throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
				}
			} else {
				throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
			}
		}

		if ($mysqli->real_escape_string(isset($ices[$x]['status'])) and $ices[$x]['status'] == 'Deleted') {
			$query = "DELETE FROM cpa_arenas_ices WHERE id = $id";
			if ($mysqli->query($query)) {
				$query = "DELETE FROM cpa_text WHERE id = $label";
				if (!$mysqli->query($query)) {
					throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
				}
			} else {
				throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
			}
		}
	}
	
	for ($x = 0; $x < count($ices); $x++) {
		if (!$mysqli->real_escape_string(isset($ices[$x]['status'])) || ($mysqli->real_escape_string(isset($ices[$x]['status'])) && $ices[$x]['status'] !== 'Deleted')) {
			if ($mysqli->real_escape_string(isset($ices[$x]['rooms']))) {
				$data['rooms'] = updateEntireIceRooms($mysqli, $arenaid, $ices[$x]['id'], $ices[$x]['rooms']);
			}
			if ($mysqli->real_escape_string(isset($ices[$x]['seats']))) {
				$data['seats'] = updateSeats($mysqli, $arenaid, $ices[$x]['id'], $ices[$x]['seats']);
			}
			if ($mysqli->real_escape_string(isset($ices[$x]['exceptions']))) {
				$data['exceptions'] = updateEntireExceptions($mysqli, $arenaid, $ices[$x]['id'], $ices[$x]['exceptions']);
			}
		}
	}
	$data['success'] = true;
	return $data;
};

function updateWebsite($mysqli, $arenaid, $arena) {
	$data = array();
	$id =						$mysqli->real_escape_string(isset($arena['id']) 				? (int)$arena['id'] : 0);
	$label =				$mysqli->real_escape_string(isset($arena['label']) 			? (int)$arena['label'] : 0);
	$label_fr =			$mysqli->real_escape_string(isset($arena['label_fr']) 	? $arena['label_fr'] : '');
	$label_en =			$mysqli->real_escape_string(isset($arena['label_en']) 	? $arena['label_en'] : '');
	$address1 =			$mysqli->real_escape_string(isset($arena['address1']) 	? $arena['address1'] : '');
	$address2 =			$mysqli->real_escape_string(isset($arena['address2']) 	? $arena['address2'] : '');
	$link =					$mysqli->real_escape_string(isset($arena['link']) 			? $arena['link'] : '');
	$arenaindex =		$mysqli->real_escape_string(isset($arena['arenaindex']) ? (int)$arena['arenaindex'] : 0);
	$publish =			$mysqli->real_escape_string(isset($arena['publish']) 		? (int)$arena['publish'] : 0);

	if ($id != 0) { // Arena already defined in cpa_ws_arenas
		$query = "UPDATE cpa_ws_arenas SET address1 = '$address1', address2 = '$address2', link = '$link', arenaindex = $arenaindex, publish = $publish 
							WHERE id = $id";
		if ($mysqli->query($query)) {
			$query = "UPDATE cpa_ws_text SET text = '$label_fr' WHERE id = $label AND language = 'fr-ca'";
			if ($mysqli->query($query)) {
				$query = "UPDATE cpa_ws_text SET text = '$label_en' WHERE id = $label AND language = 'en-ca'";
				if ($mysqli->query($query)) {
					$data['success'] = true;
					$data['message'] = 'Arena updated successfully.';
				} else {
					throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
				}
			} else {
				throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
			}
		} else {
			throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
		}
	} else { // Arena not already defined in cpa_ws_arenas
		$query = "INSERT INTO cpa_ws_arenas (arenaid, name, address1, address2, link, publish, arenaindex, label)
							VALUES ($arenaid, 'name', '$address1', '$address2', '$link', $publish, $arenaindex, create_wsText('$label_en','$label_fr'))";
		if ($mysqli->query($query)) {
			$data['success'] = true;
			$data['id'] = (int) $mysqli->insert_id;
			$data['message'] = 'Arena inserted successfully.';
		} else {
			throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
		}
	}
	return $data;
	exit;
};


function updateEntireArena($mysqli, $arena) {
	try {
		$data = array();
		$id = $mysqli->real_escape_string(isset($arena['id']) ? $arena['id'] : '');

		$data['successarena'] = update_arena($mysqli, $arena);
		if ($mysqli->real_escape_string(isset($arena['ices']))) {
			$data['successices'] = updateEntireIces($mysqli, $id, $arena['ices']);
		}
		if ($mysqli->real_escape_string(isset($arena['rooms']))) {
			$data['rooms'] = updateEntireIceRooms($mysqli, $id, null, $arena['rooms']);
		}
		if ($mysqli->real_escape_string(isset($arena['seats']))) {
			$data['seats'] = updateSeats($mysqli, $id, null, $arena['seats']);
		}
		if ($mysqli->real_escape_string(isset($arena['exceptions']))) {
				$data['exceptions'] = updateEntireExceptions($mysqli, $id, null, $arena['exceptions']);
		}
		if ($mysqli->real_escape_string(isset($arena['website']))) {
			$data['rooms'] = updateWebsite($mysqli, $id, $arena['website']);
		}
		$mysqli->close();

		$data['success'] = true;
		$data['message'] = 'Arena updated successfully.';
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
