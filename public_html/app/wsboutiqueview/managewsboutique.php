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
require_once('../../backend/getimagefileName.php');
require_once('../../backend/createuploadsubdirectory.php');
require_once('../../backend/deleteuploadsubdirectory.php');

if (isset($_POST['type']) && !empty(isset($_POST['type']))) {
	$type = $_POST['type'];

	switch ($type) {
		case "insertElement":
			insert_good($mysqli, $_POST['language'], $_POST['element']);
			break;
		case "updateEntireGood":
			updateEntireGood($mysqli, $_POST['good']);
			break;
		case "delete_good":
			delete_good($mysqli, $_POST['good']);
			break;
		case "getAllGoods":
			getAllGoods($mysqli, $_POST['language']);
			break;
		case "getGoodDetails":
			getGoodDetails($mysqli, $_POST['id']);
			break;
		default:
			invalidRequest($type);
	}
} else {
	invalidRequest();
};

/**
 * This function will handle good add functionality
 * @throws Exception
 */
function insert_good($mysqli, $language, $good)
{
	try {
		$data = array();
		$name =	$mysqli->real_escape_string(isset($good['name'])			? $good['name'] : '');

		$query = "	INSERT INTO cpa_ws_goods (name, label, description, quantity, priceperunit, publish)
					VALUES ('$name', create_wsText('$name', '$name'), create_wsText('', ''), 0, '0.00', 0)";
		if ($mysqli->query($query)) {
			$data['success'] = true;
			if (empty($id)) $data['id'] = $id = (int) $mysqli->insert_id;
			$dirname = createUploadSubDirectory('/website/images/goods/goodid' . $id);
			$dirname = createUploadSubDirectory('/website/images/goods/goodid' . $id . '/thumbnails');
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
 * This function will handle good update functionality
 * @throws Exception
 */
function update_good($mysqli, $good)
{
	$data = array();
	$id =				$mysqli->real_escape_string(isset($good['id'])				? (int)$good['id'] : 0);
	$name =				$mysqli->real_escape_string(isset($good['name'])			? $good['name'] : '');
	$label =			$mysqli->real_escape_string(isset($good['label'])			? (int)$good['label'] : 0);
	$label_fr =			$mysqli->real_escape_string(isset($good['label_fr'])		? $good['label_fr'] : '');
	$label_en =			$mysqli->real_escape_string(isset($good['label_en'])		? $good['label_en'] : '');
	$description =		$mysqli->real_escape_string(isset($good['description'])		? (int)$good['description'] : 0);
	$description_fr =	$mysqli->real_escape_string(isset($good['description_fr'])	? $good['description_fr'] : '');
	$description_en =	$mysqli->real_escape_string(isset($good['description_en'])	? $good['description_en'] : '');
	$quantity =			$mysqli->real_escape_string(isset($good['quantity'])		? (int)$good['quantity'] : 0);
	$priceperunit =		$mysqli->real_escape_string(isset($good['priceperunit'])	? (float)$good['priceperunit'] : '0.00');
	$publish =			$mysqli->real_escape_string(isset($good['publish'])			? (int)$good['publish'] : 0);

	$query = "UPDATE cpa_ws_goods SET name = '$name', quantity = $quantity, priceperunit = '$priceperunit', publish = $publish WHERE id = $id";
	if ($mysqli->query($query)) {
		$query = "UPDATE cpa_ws_text SET text = '$label_fr' WHERE id = $label and language = 'fr-ca'";
		if ($mysqli->query($query)) {
			$query = "UPDATE cpa_ws_text SET text = '$label_en' WHERE id = $label and language = 'en-ca'";
			if ($mysqli->query($query)) {
				$query = "UPDATE cpa_ws_text SET text = '$description_fr' WHERE id = $description and language = 'fr-ca'";
				if ($mysqli->query($query)) {
					$query = "UPDATE cpa_ws_text SET text = '$description_en' WHERE id = $description and language = 'en-ca'";
					if ($mysqli->query($query)) {
						$data['success'] = true;
						$data['message'] = 'Good updated successfully.';
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
function delete_good($mysqli, $good)
{
	try {
		$id =			$mysqli->real_escape_string(isset($good['id'])			? (int)$good['id'] : 0);
		$label =		$mysqli->real_escape_string(isset($good['label'])		? (int)$good['label'] : 0);
		$description =	$mysqli->real_escape_string(isset($good['description'])	? (int)$good['description'] : 0);

		if (empty($id)) throw new Exception("Invalid good id.");
		$query = "DELETE FROM cpa_ws_text WHERE id IN ($label, $description)";
		if ($mysqli->query($query)) {
			$query = "DELETE FROM cpa_ws_goods WHERE id = $id";
			if ($mysqli->query($query)) {
				// Delete the directory related to the good
				$data['deletedDirectory'] = deleteUploadSubDirectory('/website/images/goods/goodid' . $id);
				$mysqli->close();
				$data['success'] = true;
				$data['message'] = 'Good deleted successfully.';
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
 * This function gets list of all goods from database
 */
function getAllGoods($mysqli, $language)
{
	try {
		$data = array();
		$query = "	SELECT 	id, publish, getWSTextLabel(label, '$language') mainlabel,
							getCodeDescription('YESNO',publish, '$language') ispublish,
							getCodeDescription('YESNO', if (imagefilename is not null and imagefilename!='', 1, 0), '$language') isimage  
					FROM cpa_ws_goods 
					ORDER BY name";
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
 * This function gets the pictures of one good from database
 */
function getGoodsPictures($mysqli, $goodid = '')
{
	$query = "SELECT cwcp.* FROM cpa_ws_goods_pictures cwcp WHERE cwcp.goodid = $goodid ORDER BY cwcp.pictureindex";
	$result = $mysqli->query($query);
	$data = array();
	$data['data'] = array();
	while ($row = $result->fetch_assoc()) {
		$row['pictureindex'] = (int)$row['pictureindex'];
		$filename = getImageFileName('/website/images/goods/goodid' . $goodid . '/', $row['imagefilename']);
		$row['imageinfo'] = getImageFileInfo($filename);
		$data['data'][] = $row;
	}
	$data['success'] = true;
	return $data;
	exit;
};

/**
 * This function gets the details of one good from database
 */
function getGoodDetails($mysqli, $id = '')
{
	try {
		$query = "	SELECT cwb.*, getWSTextLabel(label, 'fr-ca') label_fr, getWSTextLabel(label, 'en-ca') label_en,
							getWSTextLabel(description, 'fr-ca') description_fr, getWSTextLabel(description, 'en-ca') description_en
					FROM cpa_ws_goods cwb
					WHERE cwb.id = $id";
		$result = $mysqli->query($query);
		$data = array();
		$data['imageinfo'] = null;
		while ($row = $result->fetch_assoc()) {
			$row['quantity'] = (int)$row['quantity'];
			$row['pictures'] = getGoodsPictures($mysqli, $id)['data'];
			$data['data'][] = $row;
			$filename = getImageFileName('/website/images/goods/goodid' . $id . '/', $row['imagefilename']);
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
function updateEntirePictures($mysqli, $goodid, $pictures)
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
			$query = "UPDATE cpa_ws_goods_pictures SET pictureindex = $pictureindex WHERE id = $id";
			if ($mysqli->query($query)) {
				$data['success'] = true;
			} else {
				throw new Exception($mysqli->sqlstate . ' - ' . $mysqli->error);
			}
		}

		if ($mysqli->real_escape_string(isset($pictures[$x]['status'])) and $pictures[$x]['status'] == 'Deleted') {
			$query = "DELETE FROM cpa_ws_goods_pictures WHERE id = $id";
			if ($mysqli->query($query)) {
				// Now delete the image and the thumbnail
				$data['oldfilename']  = removeFile('/website/images/goods/goodid' . $goodid . '/', $imagefilename, false);
				$data['oldthumbnail'] = removeFile('/website/images/goods/goodid' . $goodid . '/thumbnails'.'/', $imagefilename, false);
			} else {
				throw new Exception($mysqli->sqlstate . ' - ' . $mysqli->error);
			}
		}
	}
	$data['success'] = true;
	return $data;
};

function updateEntireGood($mysqli, $good)
{
	try {
		$data = array();

		$data['successgood'] = update_good($mysqli, $good);
		if ($mysqli->real_escape_string(isset($good['pictures']))) {
			$data['successpictures'] = updateEntirePictures($mysqli, $good['id'], $good['pictures']);
		}
		$mysqli->close();

		$data['success'] = true;
		$data['message'] = 'Good updated successfully.';
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
