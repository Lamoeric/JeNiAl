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
			insert_arena($mysqli, $_POST['arena']);
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
			getArenaDetails($mysqli, $_POST['id']);
			break;
		default:
			invalidRequest();
	}
} else {
	invalidRequest();
};

/**
 * This function will handle arena add functionality
 * @throws Exception
 */
function insert_arena($mysqli, $arena) {
	try {
		$data = array();
		$id =						$mysqli->real_escape_string(isset($arena['id']) 				? (int)$arena['id'] : 0);
		$name =					$mysqli->real_escape_string(isset($arena['name']) 			? $arena['name'] : '');
		$label =				$mysqli->real_escape_string(isset($arena['label']) 			? (int)$arena['label'] : 0);
		$label_fr =			$mysqli->real_escape_string(isset($arena['label_fr']) 	? $arena['label_fr'] : '');
		$label_en =			$mysqli->real_escape_string(isset($arena['label_en']) 	? $arena['label_en'] : '');
		$address1 =			$mysqli->real_escape_string(isset($arena['address1']) 	? $arena['address1'] : '');
		$address2 =			$mysqli->real_escape_string(isset($arena['address2']) 	? $arena['address2'] : '');
		$link =					$mysqli->real_escape_string(isset($arena['link']) 			? $arena['link'] : '');
		$arenaindex =		$mysqli->real_escape_string(isset($arena['arenaindex']) ? (int)$arena['arenaindex'] : 0);
		$publish =			$mysqli->real_escape_string(isset($arena['publish']) 		? (int)$arena['publish'] : 0);

		$query = "INSERT INTO cpa_ws_arenas (name, address1, address2, link, publish, arenaindex, label)
							VALUES ('$name', '$address1', '$address2', '$link', $publish, 0, create_wsText('$name','$name'))";
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
 * This function will handle arena update functionality
 * @throws Exception
 */
function update_arena($mysqli, $arena) {
	$data = array();
	$id =						$mysqli->real_escape_string(isset($arena['id']) 				? (int)$arena['id'] : 0);
	$name =					$mysqli->real_escape_string(isset($arena['name']) 			? $arena['name'] : '');
	$label =				$mysqli->real_escape_string(isset($arena['label']) 			? (int)$arena['label'] : 0);
	$label_fr =			$mysqli->real_escape_string(isset($arena['label_fr']) 	? $arena['label_fr'] : '');
	$label_en =			$mysqli->real_escape_string(isset($arena['label_en']) 	? $arena['label_en'] : '');
	$address1 =			$mysqli->real_escape_string(isset($arena['address1']) 	? $arena['address1'] : '');
	$address2 =			$mysqli->real_escape_string(isset($arena['address2']) 	? $arena['address2'] : '');
	$link =					$mysqli->real_escape_string(isset($arena['link']) 			? $arena['link'] : '');
	$arenaindex =		$mysqli->real_escape_string(isset($arena['arenaindex']) ? (int)$arena['arenaindex'] : 0);
	$publish =			$mysqli->real_escape_string(isset($arena['publish']) 		? (int)$arena['publish'] : 0);

	$query = "UPDATE cpa_ws_arenas SET name = '$name', address1 = '$address1', address2 = '$address2', link = '$link', arenaindex = $arenaindex, publish = $publish WHERE id = $id";
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
	return $data;
	exit;
};

/**
 * This function will handle user deletion
 * @throws Exception
 */
function delete_arena($mysqli, $arena) {
	try {
		$id = $mysqli->real_escape_string(isset($arena['id']) ? (int)$arena['id'] : 0);

		if (empty($id)) throw new Exception("Invalid arena id.");
		$query = "DELETE FROM cpa_ws_arenas WHERE id = $id";
		if ($mysqli->query($query)) {
			$mysqli->close();
			$data['success'] = true;
			$data['message'] = 'Arena deleted successfully.';
			echo json_encode($data);
			exit;
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
		$query = "SELECT id, name FROM cpa_ws_arenas ORDER BY name";
		$result = $mysqli->query($query);
		$data = array();
		while ($row = $result->fetch_assoc()) {
			$data['data'][] = $row;
		}
		$mysqli->close();
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
 * This function gets the details of one arena from database
 */
function getArenaDetails($mysqli, $id = '') {
	try {
		$query = "SELECT cwp.*, getWsTextLabel(label, 'fr-ca') label_fr, getWsTextLabel(label, 'en-ca') label_en
							FROM cpa_ws_arenas cwp
							WHERE cwp.id = $id";
		$result = $mysqli->query($query);
		$data = array();
		while ($row = $result->fetch_assoc()) {
			$data['data'][] = $row;
		}
		$mysqli->close();
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

function updateEntireArena($mysqli, $arena) {
	try {
		$data = array();

		$data['successarena'] = update_arena($mysqli, $arena);
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
