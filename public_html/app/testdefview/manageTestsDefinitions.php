<?php
/*
Author : Eric Lamoureux
*/
require_once('../../../private/'. $_SERVER['HTTP_HOST'].'/include/config.php');
require_once('../../include/nocache.php');

if (isset($_POST['type']) && !empty(isset($_POST['type']))) {
	$type = $_POST['type'];

	switch ($type) {
		case "insert_test":
			insert_test($mysqli);
			break;
		case "updateEntireTest":
			updateEntireTest($mysqli);
			break;
		case "delete_test":
			delete_test($mysqli, $_POST['test']);
			break;
		case "getAllTests":
			getAllTests($mysqli);
			break;
		case "getTestDetails":
			getTestDetails($mysqli, $_POST['id']);
			break;
		default:
			invalidRequest();
	}
} else {
	invalidRequest();
};

function updateEntireTest($mysqli) {
	try{
		$data = array();
		$id = $mysqli->real_escape_string(isset($_POST['test']['id']) ? $_POST['test']['id'] : '');

		update_test($mysqli);
		$mysqli->close();

		$data['success'] = true;
		$data['message'] = 'Test updated successfully.';
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

function insert_test($mysqli) {
	echo json_encode(update_test($mysqli));
	exit;
}

/**
 * This function will handle test update functionality
 * @throws Exception
 */
function update_test($mysqli) {
	try{
		$data = array();
		$id = 								$mysqli->real_escape_string(isset($_POST['test']['id']) 									? $_POST['test']['id'] : '');
		$type = 							$mysqli->real_escape_string(isset($_POST['test']['type']) 								? $_POST['test']['type'] : '');
		$level = 							$mysqli->real_escape_string(isset($_POST['test']['level']) 								? $_POST['test']['level'] : '');
		$subtype = 						$mysqli->real_escape_string(isset($_POST['test']['subtype']) 							? $_POST['test']['subtype'] : '');
		$sequence = 					$mysqli->real_escape_string(isset($_POST['test']['sequence']) 						? $_POST['test']['sequence'] : '');
		$name = 							$mysqli->real_escape_string(isset($_POST['test']['name'])									? $_POST['test']['name'] : '');
		$score = 							$mysqli->real_escape_string(isset($_POST['test']['score']) 								? $_POST['test']['score'] : 0);
		$minimumnbtests = 		$mysqli->real_escape_string(isset($_POST['test']['minimumnbtests']) 			? $_POST['test']['minimumnbtests'] : 0);
		$warmupduration = 		$mysqli->real_escape_string(isset($_POST['test']['warmupduration']) 			? $_POST['test']['warmupduration'] : 0);
		$testduration = 			$mysqli->real_escape_string(isset($_POST['test']['testduration']) 				? $_POST['test']['testduration'] : 0);
		$version = 						$mysqli->real_escape_string(isset($_POST['test']['version']) 							? $_POST['test']['version'] : 1);

		if ($level == '' || $type == '') {
			throw new Exception("Required fields missing, Please enter and submit");
		}

		if (empty($id)) {
			$data['insert'] = true;
			$query = "INSERT INTO cpa_tests_definitions (id, level, type, subtype, name)
								VALUES (NULL, '$level', '$type', '$subtype', '$name')";
			if ($mysqli->query($query)) {
				$data['success'] = true;
				$data['id'] = (int) $mysqli->insert_id;
			} else {
				throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
			}
		} else {
			$query = "UPDATE cpa_tests_definitions
								SET level = '$level', type = '$type', subtype = '$subtype', name = '$name', sequence = $sequence, score = $score, 
										minimumnbtests = $minimumnbtests, warmupduration = $warmupduration, testduration = $testduration, version = $version
								WHERE id = $id";
			if ($mysqli->query($query)) {
			} else {
				throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
			}
		}
		return $data;
	} catch (Exception $e) {
		$data = array();
		$data['success'] = false;
		$data['message'] = $e->getMessage();
		return $data;
	}
};

/**
 * This function will handle user deletion
 * @param string $id
 * @throws Exception
 */
function delete_test($mysqli, $test) {
	try{
		$id = $mysqli->real_escape_string(isset($test['id']) 									? $test['id'] : '');

		if (empty($id)) throw new Exception("Invalid Test.");
		$query = "DELETE FROM cpa_tests_definitions WHERE id = $id";
		if ($mysqli->query($query)) {
			$data['success'] = true;
			$data['message'] = 'Test deleted successfully.';
			echo json_encode($data);
			exit;
		} else {
			throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
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
 * This function gets list of all tests from database
 */
function getAllTests($mysqli) {
	try{
		$query = "SELECT id, level, type, name, version FROM cpa_tests_definitions order by version, type, level, sequence";
		$result = $mysqli->query($query);
		$data = array();
		$data['data'] = array();
		while ($row = $result->fetch_assoc()) {
			$row['id'] = (int) $row['id'];
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

/**
 * This function gets the details of one test from database
 */
function getTestDetails($mysqli, $id = '') {
	try{
		if (empty($id)) throw new Exception("Invalid User.");
		$query = "SELECT * FROM cpa_tests_definitions WHERE id = $id";
		$result = $mysqli->query($query);
		$data = array();
		while ($row = $result->fetch_assoc()) {
			$row['id'] = (int) $row['id'];
			$row['sequence'] = (int) $row['sequence'];
			$row['score'] = (int) $row['score'];
			$row['minimumnbtests'] = (int) $row['minimumnbtests'];
			$row['warmupduration'] = (int) $row['warmupduration'];
			$row['testduration'] 	 = (int) $row['testduration'];
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

function invalidRequest() {
	$data = array();
	$data['success'] = false;
	$data['message'] = "Invalid request.";
	echo json_encode($data);
	exit;
};

?>
