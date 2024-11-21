<?php
/*
Author : Eric Lamoureux
*/
require_once('../../../private/' . $_SERVER['HTTP_HOST'] . '/include/config.php');
require_once('../../include/nocache.php');
require_once('../../backend/invalidrequest.php'); //
require_once('../../backend/getwssupportedlanguages.php');
require_once('../../backend/getimagefileinfo.php');
require_once('../../backend/getimagefilename.php');
require_once('../../backend/removefile.php');

if (isset($_POST['type']) && !empty(isset($_POST['type']))) {
	$type = $_POST['type'];

	switch ($type) {
		case "insertElement":
			insert_coach($mysqli, $_POST['language'], $_POST['element']);
			break;
		case "updateEntireCoach":
			updateEntireCoach($mysqli, $_POST['coach']);
			break;
		case "delete_coach":
			delete_coach($mysqli, $_POST['coach']);
			break;
		case "getAllCoachs":
			getAllCoachs($mysqli, $_POST['language']);
			break;
		case "getCoachDetails":
			getCoachDetails($mysqli, $_POST['id']);
			break;
		default:
			invalidRequest($type);
	}
} else {
	invalidRequest();
};

/**
 * This function will handle coach add functionality
 * @throws Exception
 */
function insert_coach($mysqli, $language, $coach)
{
	try {
		$data = array();
		$firstname =			$mysqli->real_escape_string(isset($coach['firstname']) 					? $coach['firstname'] : '');
		$lastname =				$mysqli->real_escape_string(isset($coach['lastname']) 					? $coach['lastname'] : '');
		$imagefilename =		$mysqli->real_escape_string(isset($coach['imagefilename']) 				? $coach['imagefilename'] : '');
		$coachindex =			$mysqli->real_escape_string(isset($coach['coachindex']) 				? (int)$coach['coachindex'] : 0);
		$publish =				$mysqli->real_escape_string(isset($coach['publish']) 					? (int)$coach['publish'] : 0);
		$availabilitytext =		$mysqli->real_escape_string(isset($coach['availabilitytext']) 			? (int)$coach['availabilitytext'] : 0);
		$availabilitytext_fr =	$mysqli->real_escape_string(isset($coach['availabilitytext_fr']) 		? $coach['availabilitytext_fr'] : '');
		$availabilitytext_en =	$mysqli->real_escape_string(isset($coach['availabilitytext_en']) 		? $coach['availabilitytext_en'] : '');
		$competitivetext =		$mysqli->real_escape_string(isset($coach['competitivetext']) 			? (int)$coach['competitivetext'] : 0);
		$competitivetext_fr =	$mysqli->real_escape_string(isset($coach['competitivetext_fr']) 		? $coach['competitivetext_fr'] : '');
		$competitivetext_en =	$mysqli->real_escape_string(isset($coach['competitivetext_en']) 		? $coach['competitivetext_en'] : '');

		$query = "	INSERT INTO cpa_ws_coaches (firstname, lastname, imagefilename, publish, coachindex, availabilitytext, competitivetext)
					VALUES ('$firstname', '$lastname', '$imagefilename', $publish, $coachindex, create_wsText('$availabilitytext_en', '$availabilitytext_fr'), create_wsText('$competitivetext_en', '$competitivetext_fr'))";
		if ($mysqli->query($query)) {
			$data['success'] = true;
			if (empty($id)) $data['id'] = (int) $mysqli->insert_id;
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
 * This function will handle coach update functionality
 * @throws Exception
 */
function update_coach($mysqli, $coach)
{
	$data = array();
	$id =							$mysqli->real_escape_string(isset($coach['id']) 						? (int)$coach['id'] : 0);
	$firstname =					$mysqli->real_escape_string(isset($coach['firstname']) 					? $coach['firstname'] : '');
	$lastname =						$mysqli->real_escape_string(isset($coach['lastname']) 					? $coach['lastname'] : '');
	$phone =						$mysqli->real_escape_string(isset($coach['phone']) 						? $coach['phone'] : '');
	$email =						$mysqli->real_escape_string(isset($coach['email']) 						? $coach['email'] : '');
	$imagefilename =				$mysqli->real_escape_string(isset($coach['imagefilename']) 				? $coach['imagefilename'] : '');
	$coachlevel =					$mysqli->real_escape_string(isset($coach['coachlevel']) 				? $coach['coachlevel'] : '');
	$coachsince =					$mysqli->real_escape_string(isset($coach['coachsince']) 				? $coach['coachsince'] : '');
	$coachindex =					$mysqli->real_escape_string(isset($coach['coachindex']) 				? (int)$coach['coachindex'] : 0);
	$availabilitytext =				$mysqli->real_escape_string(isset($coach['availabilitytext']) 			? (int)$coach['availabilitytext'] : 0);
	$availabilitytext_fr =			$mysqli->real_escape_string(isset($coach['availabilitytext_fr']) 		? $coach['availabilitytext_fr'] : '');
	$availabilitytext_en =			$mysqli->real_escape_string(isset($coach['availabilitytext_en']) 		? $coach['availabilitytext_en'] : '');
	$competitivetext =				$mysqli->real_escape_string(isset($coach['competitivetext']) 			? (int)$coach['competitivetext'] : 0);
	$competitivetext_fr =			$mysqli->real_escape_string(isset($coach['competitivetext_fr']) 		? $coach['competitivetext_fr'] : '');
	$competitivetext_en =			$mysqli->real_escape_string(isset($coach['competitivetext_en']) 		? $coach['competitivetext_en'] : '');
	$publish =						$mysqli->real_escape_string(isset($coach['publish']) 					? (int)$coach['publish'] : 0);
	$starversion =					$mysqli->real_escape_string(isset($coach['starversion']) 				? (int)$coach['starversion'] : 1);
	$dancelevel =					$mysqli->real_escape_string(isset($coach['dancelevel']) 				? $coach['dancelevel'] : '-1');
	$skillslevel =					$mysqli->real_escape_string(isset($coach['skillslevel']) 				? $coach['skillslevel'] : '-1');
	$freestylelevel =				$mysqli->real_escape_string(isset($coach['freestylelevel']) 			? $coach['freestylelevel'] : '-1');
	if ($starversion == 2) {
		$artisticlevel =				$mysqli->real_escape_string(isset($coach['artisticlevel']) 				? $coach['artisticlevel'] : '-1');
		$synchrolevel =					$mysqli->real_escape_string(isset($coach['synchrolevel']) 				? $coach['synchrolevel'] : '-1');
		$interpretativesinglelevel = -1;
		$interpretativecouplelevel = -1;
		$competitivesinglelevel = -1;
		$competitivecouplelevel = -1;
		$competitivedancelevel = -1;
		$competitivesynchrolevel = -1;
	} else {
		$artisticlevel = -1;
		$synchrolevel = -1;
		$interpretativesinglelevel =	$mysqli->real_escape_string(isset($coach['interpretativesinglelevel'])	? $coach['interpretativesinglelevel'] : '-1');
		$interpretativecouplelevel =	$mysqli->real_escape_string(isset($coach['interpretativecouplelevel']) 	? $coach['interpretativecouplelevel'] : '-1');
		$competitivesinglelevel =		$mysqli->real_escape_string(isset($coach['competitivesinglelevel']) 	? $coach['competitivesinglelevel'] : '-1');
		$competitivecouplelevel =		$mysqli->real_escape_string(isset($coach['competitivecouplelevel']) 	? $coach['competitivecouplelevel'] : '-1');
		$competitivedancelevel =		$mysqli->real_escape_string(isset($coach['competitivedancelevel']) 		? $coach['competitivedancelevel'] : '-1');
		$competitivesynchrolevel =		$mysqli->real_escape_string(isset($coach['competitivesynchrolevel']) 	? $coach['competitivesynchrolevel'] : '-1');
	}

	$query = "	UPDATE cpa_ws_coaches 
				SET firstname = '$firstname', lastname = '$lastname', phone = '$phone',
					email='$email', coachsince = '$coachsince', coachlevel = '$coachlevel', dancelevel = '$dancelevel', skillslevel = '$skillslevel', freestylelevel = '$freestylelevel',
					interpretativesinglelevel = '$interpretativesinglelevel', interpretativecouplelevel = '$interpretativecouplelevel', competitivesinglelevel = '$competitivesinglelevel',
					competitivecouplelevel = '$competitivecouplelevel', competitivedancelevel = '$competitivedancelevel', competitivesynchrolevel = '$competitivesynchrolevel',
					publish = $publish, coachindex = $coachindex, artisticlevel = '$artisticlevel', synchrolevel = '$synchrolevel', starversion = $starversion
				WHERE id = $id";
	if ($mysqli->query($query)) {
		$mysqli->query("call update_wsText($availabilitytext, '$availabilitytext_en', '$availabilitytext_fr')");
		$mysqli->query("call update_wsText($competitivetext, '$competitivetext_en', '$competitivetext_fr')");
		$data['success'] = true;
		$data['message'] = 'Coach updated successfully.';
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
function delete_coach($mysqli, $coach)
{
	try {
		$id = 				$mysqli->real_escape_string(isset($coach['id']) 				? (int)$coach['id'] : 0);
		$imagefilename =	$mysqli->real_escape_string(isset($coach['imagefilename']) 		? $coach['imagefilename'] : '');
		$availabilitytext =	$mysqli->real_escape_string(isset($coach['availabilitytext']) 	? (int)$coach['availabilitytext'] : 0);
		$competitivetext =	$mysqli->real_escape_string(isset($coach['competitivetext']) 	? (int)$coach['competitivetext'] : 0);

		if (empty($id)) throw new Exception("Invalid coach id.");
		$query = "DELETE FROM cpa_ws_text WHERE id IN ($availabilitytext, $competitivetext)";
		if ($mysqli->query($query)) {
			$query = "DELETE FROM cpa_ws_coaches WHERE id = $id";
			if ($mysqli->query($query)) {
				$mysqli->close();
				// Delete the filename related to the object
				$filename = removeFile('/website/images/coaches/', $imagefilename, false);
				$data['filename'] = $filename;
				$data['success'] = true;
				$data['message'] = 'Coach deleted successfully.';
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
 * This function gets list of all coaches from database
 */
function getAllCoachs($mysqli, $language)
{
	try {
		$query = "	SELECT id, firstname, lastname, publish, getCodeDescription('YESNO',publish, '$language') ispublish, coachindex, 
							getCodeDescription('YESNO', if (imagefilename is not null and imagefilename!='', 1, 0), '$language') isimage 
					FROM cpa_ws_coaches 
					ORDER BY publish DESC, coachindex";
		$result = $mysqli->query($query);
		$data = array();
		while ($row = $result->fetch_assoc()) {
			$row['publish'] = (int)$row['publish'];
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
 * This function gets the details of one coach from database
 */
function getCoachDetails($mysqli, $id = '')
{
	try {
		$query = "	SELECT cwc.*, getWsTextLabel(availabilitytext, 'fr-ca') availabilitytext_fr, getWsTextLabel(availabilitytext, 'en-ca') availabilitytext_en, getWsTextLabel(competitivetext, 'fr-ca') competitivetext_fr, getWsTextLabel(competitivetext, 'en-ca') competitivetext_en
					FROM cpa_ws_coaches cwc
					WHERE cwc.id = $id";
		$result = $mysqli->query($query);
		$data = array();
		$data['imageinfo'] = null;
		while ($row = $result->fetch_assoc()) {
			$row['coachindex'] = (int)$row['coachindex'];
			$data['data'][] = $row;
			$filename = getImageFileName('/website/images/coaches/', $row['imagefilename']);
			$data['imageinfo'] = getImageFileInfo($filename);
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

function updateEntireCoach($mysqli, $coach)
{
	try {
		$data = array();

		$data['successcoach'] = update_coach($mysqli, $coach);
		$mysqli->close();

		$data['success'] = true;
		$data['message'] = 'Coach updated successfully.';
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
