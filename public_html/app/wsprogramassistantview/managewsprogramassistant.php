<?php
/*
Author : Eric Lamoureux
*/
require_once('../../../private/'. $_SERVER['HTTP_HOST'].'/include/config.php');
require_once('../../include/nocache.php');

if (isset($_POST['type']) && !empty(isset($_POST['type']))) {
	$type = $_POST['type'];

	switch ($type) {
		case "insert_programassistant":
			insert_programassistant($mysqli, $_POST['programassistant']);
			break;
		case "updateEntireProgramassistant":
			updateEntireProgramassistant($mysqli, $_POST['programassistant']);
			break;
		case "delete_programassistant":
			delete_programassistant($mysqli, $_POST['programassistant']);
			break;
		case "getAllProgramassistants":
			getAllProgramassistants($mysqli);
			break;
		case "getProgramassistantDetails":
			getProgramassistantDetails($mysqli, $_POST['id']);
			break;
		default:
			invalidRequest();
	}
} else {
	invalidRequest();
};

/**
 * This function will handle programassistant add functionality
 * @throws Exception
 */
function insert_programassistant($mysqli, $programassistant) {
	try {
		$data = array();
		$firstname =			$mysqli->real_escape_string(isset($programassistant['firstname']) 				? $programassistant['firstname'] : '');
		$lastname =				$mysqli->real_escape_string(isset($programassistant['lastname']) 					? $programassistant['lastname'] : '');
		$imagefilename =	$mysqli->real_escape_string(isset($programassistant['imagefilename']) 		? $programassistant['imagefilename'] : '');
		$publish =				$mysqli->real_escape_string(isset($programassistant['publish']) 					? (int)$programassistant['publish'] : 0);

		$query = "INSERT INTO cpa_ws_programassistants (firstname, lastname, imagefilename, publish)
							VALUES ('$firstname', '$lastname', '$imagefilename', $publish)";
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
 * This function will handle programassistant update functionality
 * @throws Exception
 */
function update_programassistant($mysqli, $programassistant) {
	$data = array();
	$id =							$mysqli->real_escape_string(isset($programassistant['id']) 								? (int)$programassistant['id'] : 0);
	$firstname =			$mysqli->real_escape_string(isset($programassistant['firstname']) 				? $programassistant['firstname'] : '');
	$lastname =				$mysqli->real_escape_string(isset($programassistant['lastname']) 					? $programassistant['lastname'] : '');
	$imagefilename =	$mysqli->real_escape_string(isset($programassistant['imagefilename']) 		? $programassistant['imagefilename'] : '');
	$publish =				$mysqli->real_escape_string(isset($programassistant['publish']) 					? (int)$programassistant['publish'] : 0);

	$query = "UPDATE cpa_ws_programassistants SET firstname = '$firstname', lastname = '$lastname', publish = $publish WHERE id = $id";
	if ($mysqli->query($query)) {
		$data['success'] = true;
		$data['message'] = 'Programassistant updated successfully.';
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
function delete_programassistant($mysqli, $programassistant) {
	try {
		$id = 						$mysqli->real_escape_string(isset($programassistant['id']) 							? (int)$programassistant['id'] : 0);
		$imagefilename =	$mysqli->real_escape_string(isset($programassistant['imagefilename']) 	? $programassistant['imagefilename'] : '');

		if (empty($id)) throw new Exception("Invalid programassistant id.");
		// Delete the filename related to the programassistant
		$filename = '../../../private/'. $_SERVER['HTTP_HOST'].'/website/images/programassistants/'.$imagefilename;
		if (isset($imagefilename) && !empty($imagefilename) && file_exists($filename)) {
			unlink($filename);
			$data['unlink'] = true;
		}
		$query = "DELETE FROM cpa_ws_programassistants WHERE id = $id";
		if ($mysqli->query($query)) {
			$mysqli->close();
			$data['filename'] = $filename;
			$data['success'] = true;
			$data['message'] = 'Programassistant deleted successfully.';
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
 * This function gets list of all programassistants from database
 */
function getAllProgramassistants($mysqli) {
	try {
		$query = "SELECT id, firstname, lastname, publish FROM cpa_ws_programassistants ORDER BY lastname, firstname";
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
 * This function gets the details of one programassistant from database
 */
function getProgramassistantDetails($mysqli, $id = '') {
	try {
		$query = "SELECT cwp.*
							FROM cpa_ws_programassistants cwp
							WHERE cwp.id = $id";
		$result = $mysqli->query($query);
		$data = array();
		$data['imageinfo'] = null;
		while ($row = $result->fetch_assoc()) {
			$data['data'][] = $row;
			$filename = '../../../private/'. $_SERVER['HTTP_HOST'].'/website/images/programassistants/'.$row['imagefilename'];
			if (isset($row['imagefilename']) && !empty($row['imagefilename']) && file_exists($filename)) {
				$data['imageinfo'] = getimagesize('../../../private/'. $_SERVER['HTTP_HOST'].'/website/images/programassistants/'.$row['imagefilename']);
			}
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

function updateEntireProgramassistant($mysqli, $programassistant) {
	try {
		$data = array();

		$data['successprogramassistant'] = update_programassistant($mysqli, $programassistant);
		$mysqli->close();

		$data['success'] = true;
		$data['message'] = 'Programassistant updated successfully.';
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
