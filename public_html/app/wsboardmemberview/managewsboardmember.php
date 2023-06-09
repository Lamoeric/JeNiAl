<?php
/*
Author : Eric Lamoureux
*/
require_once('../../../private/'. $_SERVER['HTTP_HOST'].'/include/config.php');
require_once('../../include/nocache.php');

if (isset($_POST['type']) && !empty(isset($_POST['type']))) {
	$type = $_POST['type'];

	switch ($type) {
		case "insert_boardmember":
			insert_boardmember($mysqli, $_POST['boardmember']);
			break;
		case "updateEntireBoardmember":
			updateEntireBoardmember($mysqli, $_POST['boardmember']);
			break;
		case "delete_boardmember":
			delete_boardmember($mysqli, $_POST['boardmember']);
			break;
		case "getAllBoardmembers":
			getAllBoardmembers($mysqli);
			break;
		case "getBoardmemberDetails":
			getBoardmemberDetails($mysqli, $_POST['id']);
			break;
		default:
			invalidRequest();
	}
} else {
	invalidRequest();
};

/**
 * This function will handle boardmember add functionality
 * @throws Exception
 */
function insert_boardmember($mysqli, $boardmember) {
	try {
		$data = array();
		$firstname =			$mysqli->real_escape_string(isset($boardmember['firstname']) 				? $boardmember['firstname'] : '');
		$lastname =				$mysqli->real_escape_string(isset($boardmember['lastname']) 				? $boardmember['lastname'] : '');
		$email =					$mysqli->real_escape_string(isset($boardmember['email']) 						? $boardmember['email'] : '');
		$phone =					$mysqli->real_escape_string(isset($boardmember['phone']) 						? $boardmember['phone'] : '');
		$imagefilename =	$mysqli->real_escape_string(isset($boardmember['imagefilename']) 		? $boardmember['imagefilename'] : '');
		$memberindex =		$mysqli->real_escape_string(isset($boardmember['memberindex']) 			? (int)$boardmember['memberindex'] : 0);
		$publish =				$mysqli->real_escape_string(isset($boardmember['publish']) 					? (int)$boardmember['publish'] : 0);
		$memberrole =			$mysqli->real_escape_string(isset($boardmember['memberrole']) 			? (int)$boardmember['memberrole'] : 0);
		$memberrole_fr =	$mysqli->real_escape_string(isset($boardmember['memberrole_fr']) 		? $boardmember['memberrole_fr'] : '');
		$memberrole_en =	$mysqli->real_escape_string(isset($boardmember['memberrole_en']) 		? $boardmember['memberrole_en'] : '');
		$description =		$mysqli->real_escape_string(isset($boardmember['description']) 			? (int)$boardmember['description'] : 0);
		$description_fr =	$mysqli->real_escape_string(isset($boardmember['description_fr']) 	? $boardmember['description_fr'] : '');
		$description_en =	$mysqli->real_escape_string(isset($boardmember['description_en']) 	? $boardmember['description_en'] : '');

		$query = "INSERT INTO cpa_ws_boardmembers (firstname, lastname, imagefilename, publish, memberindex, memberrole, description)
							VALUES ('$firstname', '$lastname', '$imagefilename', $publish, $memberindex, create_wsText('$memberrole_en', '$memberrole_fr'), create_wsText('$description_en', '$description_fr'))";
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
 * This function will handle boardmember update functionality
 * @throws Exception
 */
function update_boardmember($mysqli, $boardmember) {
	$data = array();
	$id =							$mysqli->real_escape_string(isset($boardmember['id']) 							? (int)$boardmember['id'] : 0);
	$firstname =			$mysqli->real_escape_string(isset($boardmember['firstname']) 				? $boardmember['firstname'] : '');
	$lastname =				$mysqli->real_escape_string(isset($boardmember['lastname']) 				? $boardmember['lastname'] : '');
	$email =					$mysqli->real_escape_string(isset($boardmember['email']) 						? $boardmember['email'] : '');
	$phone =					$mysqli->real_escape_string(isset($boardmember['phone']) 						? $boardmember['phone'] : '');
	$imagefilename =	$mysqli->real_escape_string(isset($boardmember['imagefilename']) 		? $boardmember['imagefilename'] : '');
	$memberindex =		$mysqli->real_escape_string(isset($boardmember['memberindex']) 			? (int)$boardmember['memberindex'] : 0);
	$publish =				$mysqli->real_escape_string(isset($boardmember['publish']) 					? (int)$boardmember['publish'] : 0);
	$memberrole =			$mysqli->real_escape_string(isset($boardmember['memberrole']) 			? (int)$boardmember['memberrole'] : 0);
	$memberrole_fr =	$mysqli->real_escape_string(isset($boardmember['memberrole_fr']) 		? $boardmember['memberrole_fr'] : '');
	$memberrole_en =	$mysqli->real_escape_string(isset($boardmember['memberrole_en']) 		? $boardmember['memberrole_en'] : '');
	$description =		$mysqli->real_escape_string(isset($boardmember['description']) 			? (int)$boardmember['description'] : 0);
	$description_fr =	$mysqli->real_escape_string(isset($boardmember['description_fr']) 	? $boardmember['description_fr'] : '');
	$description_en =	$mysqli->real_escape_string(isset($boardmember['description_en']) 	? $boardmember['description_en'] : '');

	$query = "UPDATE cpa_ws_boardmembers SET firstname = '$firstname', lastname = '$lastname', publish = $publish, memberindex = $memberindex, email = '$email', phone = '$phone' WHERE id = $id";
	if ($mysqli->query($query)) {
		$query = "UPDATE cpa_ws_text SET text = '$memberrole_fr' WHERE id = $memberrole and language = 'fr-ca'";
		if ($mysqli->query($query)) {
			$query = "UPDATE cpa_ws_text SET text = '$memberrole_en' WHERE id = $memberrole and language = 'en-ca'";
			if ($mysqli->query($query)) {
				$query = "UPDATE cpa_ws_text SET text = '$description_fr' WHERE id = $description and language = 'fr-ca'";
				if ($mysqli->query($query)) {
					$query = "UPDATE cpa_ws_text SET text = '$description_en' WHERE id = $description and language = 'en-ca'";
					if ($mysqli->query($query)) {
						$data['success'] = true;
						$data['message'] = 'Boardmember updated successfully.';
					} else {
						throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
					}
				} else {
					throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
				}
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
function delete_boardmember($mysqli, $boardmember) {
	try {
		$id = 						$mysqli->real_escape_string(isset($boardmember['id']) 							? (int)$boardmember['id'] : 0);
		$imagefilename =	$mysqli->real_escape_string(isset($boardmember['imagefilename']) 	? $boardmember['imagefilename'] : '');
		$memberrole =			$mysqli->real_escape_string(isset($boardmember['memberrole']) 			? (int)$boardmember['memberrole'] : 0);
		$description =		$mysqli->real_escape_string(isset($boardmember['description']) 			? (int)$boardmember['description'] : 0);

		if (empty($id)) throw new Exception("Invalid boardmember id.");
		// Delete the filename related to the boardmember
		$filename = '../../../private/'. $_SERVER['HTTP_HOST'].'/website/images/boardmembers/'.$imagefilename;
		if (isset($imagefilename) && !empty($imagefilename) && file_exists($filename)) {
			unlink($filename);
			$data['unlink'] = true;
		}
		$query = "DELETE FROM cpa_ws_text WHERE id IN ($memberrole, $description)";
		if ($mysqli->query($query)) {
			$query = "DELETE FROM cpa_ws_boardmembers WHERE id = $id";
			if ($mysqli->query($query)) {
				$mysqli->close();
				$data['filename'] = $filename;
				$data['success'] = true;
				$data['message'] = 'Boardmember deleted successfully.';
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
 * This function gets list of all boardmembers from database
 */
function getAllBoardmembers($mysqli) {
	try {
		$query = "SELECT id, firstname, lastname, publish FROM cpa_ws_boardmembers ORDER BY memberindex";
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
 * This function gets the details of one boardmember from database
 */
function getBoardmemberDetails($mysqli, $id = '') {
	try {
		$query = "SELECT cwb.*, getWSTextLabel(memberrole, 'fr-ca') memberrole_fr, getWSTextLabel(memberrole, 'en-ca') memberrole_en, getWSTextLabel(description, 'fr-ca') description_fr, getWSTextLabel(description, 'fr-ca') description_fr
							FROM cpa_ws_boardmembers cwb
							WHERE cwb.id = $id";
		$result = $mysqli->query($query);
		$data = array();
		$data['imageinfo'] = null;
		while ($row = $result->fetch_assoc()) {
			$data['data'][] = $row;
			$filename = '../../../private/'. $_SERVER['HTTP_HOST'].'/website/images/boardmembers/'.$row['imagefilename'];
			if (isset($row['imagefilename']) && !empty($row['imagefilename']) && file_exists($filename)) {
				$data['imageinfo'] = getimagesize('../../../private/'. $_SERVER['HTTP_HOST'].'/website/images/boardmembers/'.$row['imagefilename']);
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

function updateEntireBoardmember($mysqli, $boardmember) {
	try {
		$data = array();

		$data['successboardmember'] = update_boardmember($mysqli, $boardmember);
		$mysqli->close();

		$data['success'] = true;
		$data['message'] = 'Boardmember updated successfully.';
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
