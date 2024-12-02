<?php
/*
Author : Eric Lamoureux
*/
require_once('../../../private/' . $_SERVER['HTTP_HOST'] . '/include/config.php');
require_once('../../include/nocache.php');
require_once('../../backend/invalidrequest.php'); //
require_once('../../backend/removefile.php');
require_once('../../backend/getwssupportedlanguages.php');
require_once('../../backend/getimagefileinfo.php');
require_once('../../backend/getimagefilename.php');

if (isset($_POST['type']) && !empty(isset($_POST['type']))) {
	$type = $_POST['type'];

	switch ($type) {
		case "insertElement":
			insertElement($mysqli, $_POST['language'], $_POST['element']);
			break;
		case "updateEntireBoardmember":
			updateEntireBoardmember($mysqli, $_POST['language'], $_POST['boardmember']);
			break;
		case "delete_boardmember":
			delete_boardmember($mysqli, $_POST['boardmember']);
			break;
		case "getAllBoardmembers":
			getAllBoardmembers($mysqli, $_POST['language']);
			break;
		case "getBoardmemberDetails":
			getBoardmemberDetails($mysqli, $_POST['id']);
			break;
		default:
			invalidRequest($type);
	}
} else {
	invalidRequest();
};

function getOneBoardMemberInfo($mysqli, $language, $id) {
	$data = null;
	$query = "	SELECT id, firstname, lastname, publish, getCodeDescription('YESNO',publish, '$language') ispublish, memberindex, 
				getCodeDescription('YESNO', if (imagefilename is not null and imagefilename!='', 1, 0), '$language') isimage 
				FROM cpa_ws_boardmembers 
				WHERE id = $id";
	$result = $mysqli->query($query);
	$data = $result->fetch_assoc();
	return $data;
}

/**
 * This function will handle boardmember add functionality
 * @throws Exception
 */
function insertElement($mysqli, $language, $boardmember)
{
	try {
		$data = array();
		$firstname =		$mysqli->real_escape_string(isset($boardmember['firstname']) 		? $boardmember['firstname'] : '');
		$lastname =			$mysqli->real_escape_string(isset($boardmember['lastname']) 		? $boardmember['lastname'] : '');
		$imagefilename =	$mysqli->real_escape_string(isset($boardmember['imagefilename']) 	? $boardmember['imagefilename'] : '');
		$memberindex =		$mysqli->real_escape_string(isset($boardmember['memberindex']) 		? (int)$boardmember['memberindex'] : 0);
		$publish =			$mysqli->real_escape_string(isset($boardmember['publish']) 			? (int)$boardmember['publish'] : 0);
		$memberrole_fr =	$mysqli->real_escape_string(isset($boardmember['memberrole_fr']) 	? $boardmember['memberrole_fr'] : 'Admin');
		$memberrole_en =	$mysqli->real_escape_string(isset($boardmember['memberrole_en']) 	? $boardmember['memberrole_en'] : 'Admin');
		$description_fr =	$mysqli->real_escape_string(isset($boardmember['description_fr']) 	? $boardmember['description_fr'] : '');
		$description_en =	$mysqli->real_escape_string(isset($boardmember['description_en']) 	? $boardmember['description_en'] : '');

		$query = "	INSERT INTO cpa_ws_boardmembers (firstname, lastname, imagefilename, publish, memberindex, memberrole, description)
					VALUES ('$firstname', '$lastname', '$imagefilename', $publish, $memberindex, 
							create_wsText('$memberrole_en', '$memberrole_fr'), create_wsText('$description_en', '$description_fr'))";
		if ($mysqli->query($query)) {
			$data['success'] = true;
			if (empty($id)) $data['id'] = $id = (int) $mysqli->insert_id;
			$query = "	SELECT id, firstname, lastname, publish, getCodeDescription('YESNO',publish, '$language') ispublish, memberindex, 
								getCodeDescription('YESNO', if (imagefilename is not null and imagefilename!='', 1, 0), '$language') isimage 
						FROM cpa_ws_boardmembers 
						WHERE id = $id";
			$result = $mysqli->query($query);
			$data['element'] = getOneBoardMemberInfo($mysqli, $language, $id);
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
 * This function will handle boardmember update functionality
 * @throws Exception
 */
function update_boardmember($mysqli, $language, $boardmember)
{
	$data = array();
	$id =				$mysqli->real_escape_string(isset($boardmember['id']) 				? (int)$boardmember['id'] : 0);
	$firstname =		$mysqli->real_escape_string(isset($boardmember['firstname']) 		? $boardmember['firstname'] : '');
	$lastname =			$mysqli->real_escape_string(isset($boardmember['lastname']) 		? $boardmember['lastname'] : '');
	$email =			$mysqli->real_escape_string(isset($boardmember['email']) 			? $boardmember['email'] : '');
	$phone =			$mysqli->real_escape_string(isset($boardmember['phone']) 			? $boardmember['phone'] : '');
	$memberindex =		$mysqli->real_escape_string(isset($boardmember['memberindex']) 		? (int)$boardmember['memberindex'] : 0);
	$publish =			$mysqli->real_escape_string(isset($boardmember['publish']) 			? (int)$boardmember['publish'] : 0);
	$memberrole =		$mysqli->real_escape_string(isset($boardmember['memberrole']) 		? (int)$boardmember['memberrole'] : 0);
	$memberrole_fr =	$mysqli->real_escape_string(isset($boardmember['memberrole_fr']) 	? $boardmember['memberrole_fr'] : '');
	$memberrole_en =	$mysqli->real_escape_string(isset($boardmember['memberrole_en']) 	? $boardmember['memberrole_en'] : '');
	$description =		$mysqli->real_escape_string(isset($boardmember['description']) 		? (int)$boardmember['description'] : 0);
	$description_fr =	$mysqli->real_escape_string(isset($boardmember['description_fr']) 	? $boardmember['description_fr'] : '');
	$description_en =	$mysqli->real_escape_string(isset($boardmember['description_en']) 	? $boardmember['description_en'] : '');

	$query = "	UPDATE cpa_ws_boardmembers 
				SET firstname = '$firstname', lastname = '$lastname', publish = $publish, memberindex = $memberindex, email = '$email', phone = '$phone' 
				WHERE id = $id";
	if ($mysqli->query($query)) {
		$mysqli->query("call update_wsText($memberrole, '$memberrole_en', '$memberrole_fr')");
		$mysqli->query("call update_wsText($description, '$description_en', '$description_fr')");
		$data['success'] = true;
		$data['message'] = 'Boardmember updated successfully.';
	} else {
		throw new Exception($mysqli->sqlstate . ' - ' . $mysqli->error);
	}
	return $data;
	exit;
};

/**
 * This function will handle user deletion
 * @throws Exception
 */
function delete_boardmember($mysqli, $boardmember)
{
	try {
		$id = 				$mysqli->real_escape_string(isset($boardmember['id']) 				? (int)$boardmember['id'] : 0);
		$imagefilename =	$mysqli->real_escape_string(isset($boardmember['imagefilename']) 	? $boardmember['imagefilename'] : '');
		$memberrole =		$mysqli->real_escape_string(isset($boardmember['memberrole']) 		? (int)$boardmember['memberrole'] : 0);
		$description =		$mysqli->real_escape_string(isset($boardmember['description']) 		? (int)$boardmember['description'] : 0);

		if (empty($id)) throw new Exception("Invalid boardmember id.");
		$query = "DELETE FROM cpa_ws_text WHERE id IN ($memberrole, $description)";
		if ($mysqli->query($query)) {
			$query = "DELETE FROM cpa_ws_boardmembers WHERE id = $id";
			if ($mysqli->query($query)) {
				$mysqli->close();
				// Delete the filename related to the object
				$filename = removeFile('/website/images/boardmembers/', $imagefilename, false);
				$data['filename'] = $filename;
				$data['success'] = true;
				$data['message'] = 'Boardmember deleted successfully.';
				echo json_encode($data);
				exit;
			} else {
				throw new Exception($mysqli->sqlstate . ' - ' . $mysqli->error);
			}
		} else {
			throw new Exception($mysqli->sqlstate . ' - ' . $mysqli->error);
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
 * This function gets list of all boardmembers from database
 */
function getAllBoardmembers($mysqli, $language)
{
	try {
		$query = "	SELECT id, firstname, lastname, publish, getCodeDescription('YESNO',publish, '$language') ispublish, memberindex, 
							getCodeDescription('YESNO', if (imagefilename is not null and imagefilename!='', 1, 0), '$language') isimage 
					FROM cpa_ws_boardmembers 
					ORDER BY publish DESC, memberindex";
		$result = $mysqli->query($query);
		$data = array();
		while ($row = $result->fetch_assoc()) {
			$data['data'][] = $row;
		}
		$data['config'] = getWSSupportedLanguages($mysqli)['data'];
		$mysqli->close();
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
 * This function gets the details of one boardmember from database
 */
function getBoardmemberDetails($mysqli, $id = '')
{
	try {
		$query = "	SELECT cwb.*, 
							getWSTextLabel(memberrole, 'fr-ca') memberrole_fr, getWSTextLabel(memberrole, 'en-ca') memberrole_en, 
							getWSTextLabel(description, 'fr-ca') description_fr, getWSTextLabel(description, 'en-ca') description_en
					FROM cpa_ws_boardmembers cwb
					WHERE cwb.id = $id";
		$result = $mysqli->query($query);
		$data = array();
		$data['imageinfo'] = null;
		while ($row = $result->fetch_assoc()) {
			$row['memberindex'] = (int)$row['memberindex'];
			$filename = getImageFileName('/website/images/boardmembers/', $row['imagefilename']);
			$row['imageinfo'] = getImageFileInfo($filename);
			$data['data'][] = $row;
		}
		$mysqli->close();
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

function updateEntireBoardmember($mysqli, $language, $boardmember)
{
	try {
		$data = array();
		$id = $boardmember['id'];
		$data['successboardmember'] = update_boardmember($mysqli, $language, $boardmember);
		$data['element'] = getOneBoardMemberInfo($mysqli, $language, $id);
		$mysqli->close();

		$data['success'] = true;
		$data['message'] = 'Boardmember updated successfully.';
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
?>
