<?php
/*
Author : Eric Lamoureux
*/
require_once('../../../private/' . $_SERVER['HTTP_HOST'] . '/include/config.php');
require_once('../../include/nocache.php');
require_once('../../backend/invalidrequest.php');
require_once('../../backend/removefile.php');
require_once('../../backend/getwssupportedlanguages.php');
require_once('../../backend/getimagefileinfo.php');
require_once('../../backend/getimagefilename.php');
require_once('../../backend/createuploadsubdirectory.php');
require_once('../../backend/deleteuploadsubdirectory.php');

if (isset($_POST['type']) && !empty(isset($_POST['type']))) {
	$type = $_POST['type'];

	switch ($type) {
		case "insertElement":
			insert_classifiedadd($mysqli, $_POST['language'], $_POST['classifiedadd']);
			break;
		case "updateEntireClassifiedadd":
			updateEntireClassifiedadd($mysqli, $_POST['classifiedadd']);
			break;
		case "delete_classifiedadd":
			delete_classifiedadd($mysqli, $_POST['classifiedadd']);
			break;
		case "getAllClassifiedadds":
			getAllClassifiedadds($mysqli, $_POST['language']);
			break;
		case "getClassifiedaddDetails":
			getClassifiedaddDetails($mysqli, $_POST['language'], $_POST['id']);
			break;
		default:
			invalidRequest($type);
	}
} else {
	invalidRequest();
};

/**
 * This function will handle classifiedadd add functionality
 * @throws Exception
 */
function insert_classifiedadd($mysqli, $language, $classifiedadd)
{
	try {
		$data = array();
		$name =				$mysqli->real_escape_string(isset($classifiedadd['name'])				? $classifiedadd['name'] : '');
		$description_fr =	$mysqli->real_escape_string(isset($classifiedadd['description_fr'])		? $classifiedadd['description_fr'] : '');
		$description_en =	$mysqli->real_escape_string(isset($classifiedadd['description_en'])		? $classifiedadd['description_en'] : '');
		$price =			$mysqli->real_escape_string(isset($classifiedadd['price'])				? (float)$classifiedadd['price'] : '0.00');
		$publish =			$mysqli->real_escape_string(isset($classifiedadd['publish'])			? (int)$classifiedadd['publish'] : 0);

		$query = "	INSERT INTO cpa_ws_classifiedadds (name, label, description, price, publish)
					VALUES ('$name', create_wsText('$name', '$name'), create_wsText('$description_en', '$description_fr'), '$price', $publish)";
		if ($mysqli->query($query)) {
			$data['success'] = true;
			if (empty($id)) $data['id'] = $id = (int) $mysqli->insert_id;
			$dirname = createUploadSubDirectory('/website/images/classifiedadds/addid' . $id);
			$dirname = createUploadSubDirectory('/website/images/classifiedadds/addid' . $id . '/thumbnails');
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
 * This function will handle classifiedadd update functionality
 * @throws Exception
 */
function update_classifiedadd($mysqli, $classifiedadd)
{
	$data = array();
	$id =				$mysqli->real_escape_string(isset($classifiedadd['id'])				? (int)$classifiedadd['id'] : 0);
	$name =				$mysqli->real_escape_string(isset($classifiedadd['name'])			? $classifiedadd['name'] : '');
	$label =			$mysqli->real_escape_string(isset($classifiedadd['label'])			? (int)$classifiedadd['label'] : 0);
	$label_fr =			$mysqli->real_escape_string(isset($classifiedadd['label_fr'])		? $classifiedadd['label_fr'] : '');
	$label_en =			$mysqli->real_escape_string(isset($classifiedadd['label_en'])		? $classifiedadd['label_en'] : '');
	$description =		$mysqli->real_escape_string(isset($classifiedadd['description'])	? (int)$classifiedadd['description'] : 0);
	$description_fr =	$mysqli->real_escape_string(isset($classifiedadd['description_fr'])	? $classifiedadd['description_fr'] : '');
	$description_en =	$mysqli->real_escape_string(isset($classifiedadd['description_en'])	? $classifiedadd['description_en'] : '');
	$price =			$mysqli->real_escape_string(isset($classifiedadd['price'])			?	(float)$classifiedadd['price'] : '0.00');
	$publish =			$mysqli->real_escape_string(isset($classifiedadd['publish'])		? (int)$classifiedadd['publish'] : 0);

	$query = "UPDATE cpa_ws_classifiedadds SET name = '$name', price = '$price', publish = $publish WHERE id = $id";
	if ($mysqli->query($query)) {
		$mysqli->query("call update_wsText($label, '$label_en', '$label_fr')");
		$mysqli->query("call update_wsText($description, '$description_en', '$description_fr')");
		$data['success'] = true;
		$data['message'] = 'Classifiedadd updated successfully.';
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
function delete_classifiedadd($mysqli, $classifiedadd)
{
	try {
		$id =			$mysqli->real_escape_string(isset($classifiedadd['id'])				? (int)$classifiedadd['id'] : 0);
		$label =		$mysqli->real_escape_string(isset($classifiedadd['label'])			? (int)$classifiedadd['label'] : 0);
		$description =	$mysqli->real_escape_string(isset($classifiedadd['description'])	? (int)$classifiedadd['description'] : 0);

		if (empty($id)) throw new Exception("Invalid classifiedadd id.");
		$query = "DELETE FROM cpa_ws_text WHERE id IN ($label, $description)";
		if ($mysqli->query($query)) {
			$query = "DELETE FROM cpa_ws_classifiedadds WHERE id = $id";
			if ($mysqli->query($query)) {
				// Delete the directory related to the classifiedadd
				$data['deletedDirectory'] = deleteUploadSubDirectory('/website/images/classifiedadds/addid' . $id);
				$mysqli->close();
				$data['success'] = true;
				$data['message'] = 'Classifiedadd deleted successfully.';
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
 * This function gets list of all classifiedadds from database
 */
function getAllClassifiedadds($mysqli, $language)
{
	try {
		$data = array();
		$query = "	SELECT id, name, publish, getWSTextLabel(label, '$language') mainlabel,
						getCodeDescription('YESNO',publish, '$language') ispublish,
						getCodeDescription('YESNO', if (imagefilename is not null and imagefilename!='', 1, 0), '$language') isimage 
					FROM cpa_ws_classifiedadds 
					ORDER BY publish DESC, name";
		$result = $mysqli->query($query);
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
 * This function gets the pictures of one classifiedadd from database
 */
function getClassifiedaddsPictures($mysqli, $classifiedaddid = '')
{
	$query = "SELECT cwcp.*	FROM cpa_ws_classifiedadds_pictures cwcp WHERE cwcp.classifiedaddid = $classifiedaddid ORDER BY cwcp.pictureindex";
	$result = $mysqli->query($query);
	$data = array();
	$data['data'] = array();
	while ($row = $result->fetch_assoc()) {
		$row['pictureindex'] = (int)$row['pictureindex'];
		$filename = getImageFileName('/website/images/classifiedadds/addid' . $classifiedaddid . '/', $row['imagefilename']);
		$row['imageinfo'] = getImageFileInfo($filename);
		$data['data'][] = $row;
	}
	$data['success'] = true;
	return $data;
	exit;
};

/**
 * This function gets the details of one classifiedadd from database
 */
function getClassifiedaddDetails($mysqli, $language, $id = '')
{
	try {
		$query = "	SELECT cwc.*, getWSTextLabel(label, '$language') maintitle, 
							getWSTextLabel(label, 'fr-ca') label_fr, getWSTextLabel(label, 'en-ca') label_en,
							getWSTextLabel(description, 'fr-ca') description_fr, getWSTextLabel(description, 'en-ca') description_en
					FROM cpa_ws_classifiedadds cwc
					WHERE cwc.id = $id";
		$result = $mysqli->query($query);
		$data = array();
		$data['imageinfo'] = null;
		while ($row = $result->fetch_assoc()) {
			$row['pictures'] = getClassifiedaddsPictures($mysqli, $id)['data'];
			$data['data'][] = $row;
			$filename = getImageFileName('/website/images/classifiedadds/addid' . $id . '/', $row['imagefilename']);
			$data['imageinfo'] = getImageFileInfo($filename);
			$data['filename'] = $filename;
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

/**
 * This function will handle insert/update/delete of pictures in DB
 * @throws Exception
 */
function updateEntirePictures($mysqli, $classifiedaddid, $pictures)
{
	$data = array();
	for ($x = 0; $pictures && $x < count($pictures); $x++) {
		$id =				$mysqli->real_escape_string(isset($pictures[$x]['id'])				? (int)$pictures[$x]['id'] : 0);
		$pictureindex =		$mysqli->real_escape_string(isset($pictures[$x]['pictureindex'])	? (int)$pictures[$x]['pictureindex'] : 0);
		$imagefilename =	$mysqli->real_escape_string(isset($pictures[$x]['imagefilename'])	? $pictures[$x]['imagefilename'] : '');

		// This should not happen
		if ($mysqli->real_escape_string(isset($pictures[$x]['status'])) and $pictures[$x]['status'] == 'New') {
			throw new Exception("A picture with the NEW status should never happen");
		}

		if ($mysqli->real_escape_string(isset($pictures[$x]['status'])) and $pictures[$x]['status'] == 'Modified') {
			$query = "UPDATE cpa_ws_classifiedadds_pictures SET pictureindex = $pictureindex WHERE id = $id";
			if ($mysqli->query($query)) {
				$data['success'] = true;
			} else {
				throw new Exception($mysqli->sqlstate . ' - ' . $mysqli->error);
			}
		}

		if ($mysqli->real_escape_string(isset($pictures[$x]['status'])) and $pictures[$x]['status'] == 'Deleted') {
			$query = "DELETE FROM cpa_ws_classifiedadds_pictures WHERE id = $id";
			if ($mysqli->query($query)) {
				// Now delete the image and the thumbnail
				$data['oldfilename']  = removeFile('/website/images/classifiedadds/addid' . $classifiedaddid . '/', $imagefilename, false);
				$data['oldthumbnail'] = removeFile('/website/images/classifiedadds/addid' . $classifiedaddid . '/thumbnails'.'/', $imagefilename, false);
			} else {
				throw new Exception($mysqli->sqlstate . ' - ' . $mysqli->error);
			}
		}
	}
	$data['success'] = true;
	return $data;
};

function updateEntireClassifiedadd($mysqli, $classifiedadd)
{
	try {
		$data = array();

		$data['successclassifiedadd'] = update_classifiedadd($mysqli, $classifiedadd);
		if ($mysqli->real_escape_string(isset($classifiedadd['pictures']))) {
			$data['successpictures'] = updateEntirePictures($mysqli, $classifiedadd['id'], $classifiedadd['pictures']);
		}
		$mysqli->close();

		$data['success'] = true;
		$data['message'] = 'Classifiedadd updated successfully.';
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
