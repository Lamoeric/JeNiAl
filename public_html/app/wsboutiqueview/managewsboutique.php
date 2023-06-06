<?php
/*
Author : Eric Lamoureux
*/
require_once('../../../private/'. $_SERVER['HTTP_HOST'].'/include/config.php');
require_once('../../include/nocache.php');

if (isset($_POST['type']) && !empty(isset($_POST['type']))) {
	$type = $_POST['type'];

	switch ($type) {
		case "insert_good":
			insert_good($mysqli, $_POST['good']);
			break;
		case "updateEntireGood":
			updateEntireGood($mysqli, $_POST['good']);
			break;
		case "delete_good":
			delete_good($mysqli, $_POST['good']);
			break;
		case "getAllGoods":
			getAllGoods($mysqli);
			break;
		case "getGoodDetails":
			getGoodDetails($mysqli, $_POST['id']);
			break;
		default:
			invalidRequest();
	}
} else {
	invalidRequest();
};

function rrmdir($dir) {
	if (is_dir($dir)) {
		$objects = scandir($dir);
		foreach ($objects as $object) {
			if ($object != "." && $object != "..") {
				if (is_dir($dir."/".$object)) {
					rrmdir($dir."/".$object);
				} else {
					unlink($dir."/".$object);
				}
			}
		}
		rmdir($dir);
	}
}

/**
 * This function will handle good add functionality
 * @throws Exception
 */
function insert_good($mysqli, $good) {
	try {
		$data = array();
		$name =								$mysqli->real_escape_string(isset($good['name']) 								? $good['name'] : '');
		$label =							$mysqli->real_escape_string(isset($good['label']) 							? (int)$good['label'] : 0);
		$label_fr =						$mysqli->real_escape_string(isset($good['label_fr']) 						? $good['label_fr'] : '');
		$label_en =						$mysqli->real_escape_string(isset($good['label_en']) 						? $good['label_en'] : '');
		$description =				$mysqli->real_escape_string(isset($good['description']) 				? (int)$good['description'] : 0);
		$description_fr =			$mysqli->real_escape_string(isset($good['description_fr']) 			? $good['description_fr'] : '');
		$description_en =			$mysqli->real_escape_string(isset($good['description_en']) 			? $good['description_en'] : '');
		$quantity =						$mysqli->real_escape_string(isset($good['quantity']) 						? (int)$good['quantity'] : 0);
		$priceperunit =				$mysqli->real_escape_string(isset($good['priceperunit']) 				? (float)$good['priceperunit'] : '0.00');
		$publish =						$mysqli->real_escape_string(isset($good['publish']) 						? (int)$good['publish'] : 0);

		$query = "INSERT INTO cpa_ws_goods (name, label, description, quantity, priceperunit, publish)
							VALUES ('$name', create_wsText('$name', '$name'), create_wsText('$description_en', '$description_fr'), $quantity, '$priceperunit', $publish)";
		if ($mysqli->query($query)) {
			$data['success'] = true;
			if (empty($id))$data['id'] = $id = (int) $mysqli->insert_id;
			$dirname = '../../../private/'. $_SERVER['HTTP_HOST'].'/website/images/goods/goodid'.$id;
			if (!file_exists($dirname)) {
				mkdir($dirname);
			}
			$dirname .= '/thumbnails';
			if (!file_exists($dirname)) {
				mkdir($dirname);
			}
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
 * This function will handle good update functionality
 * @throws Exception
 */
function update_good($mysqli, $good) {
	$data = array();
	$id =									$mysqli->real_escape_string(isset($good['id']) 								? (int)$good['id'] : 0);
	$name =								$mysqli->real_escape_string(isset($good['name']) 							? $good['name'] : '');
	$label =							$mysqli->real_escape_string(isset($good['label']) 						? (int)$good['label'] : 0);
	$label_fr =						$mysqli->real_escape_string(isset($good['label_fr']) 					? $good['label_fr'] : '');
	$label_en =						$mysqli->real_escape_string(isset($good['label_en']) 					? $good['label_en'] : '');
	$description =				$mysqli->real_escape_string(isset($good['description']) 			? (int)$good['description'] : 0);
	$description_fr =			$mysqli->real_escape_string(isset($good['description_fr']) 		? $good['description_fr'] : '');
	$description_en =			$mysqli->real_escape_string(isset($good['description_en']) 		? $good['description_en'] : '');
	$quantity =						$mysqli->real_escape_string(isset($good['quantity']) 					? (int)$good['quantity'] : 0);
	$priceperunit =				$mysqli->real_escape_string(isset($good['priceperunit']) 			? (float)$good['priceperunit'] : '0.00');
	$publish =						$mysqli->real_escape_string(isset($good['publish']) 					? (int)$good['publish'] : 0);

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
function delete_good($mysqli, $good) {
	try {
		$id = 								$mysqli->real_escape_string(isset($good['id']) 							? (int)$good['id'] : 0);
		// $imagefilename =			$mysqli->real_escape_string(isset($good['imagefilename']) 	? $good['imagefilename'] : '');
		$label =							$mysqli->real_escape_string(isset($good['label']) 					? (int)$good['label'] : 0);
		$description =				$mysqli->real_escape_string(isset($good['description']) 		? (int)$good['description'] : 0);

		if (empty($id)) throw new Exception("Invalid good id.");
		// Delete the filename related to the good
		$dirname = '../../../private/'. $_SERVER['HTTP_HOST'].'/website/images/goods/goodid'.$id;
		if (file_exists($dirname)) {
			rrmdir($dirname);
		}
		$query = "DELETE FROM cpa_ws_text WHERE id IN ($label, $description)";
		if ($mysqli->query($query)) {
			$query = "DELETE FROM cpa_ws_goods WHERE id = $id";
			if ($mysqli->query($query)) {
				$mysqli->close();
				$data['success'] = true;
				$data['message'] = 'Good deleted successfully.';
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
 * This function gets list of all goods from database
 */
function getAllGoods($mysqli) {
	try {
		$data = array();
		$query = "SELECT id, name, publish FROM cpa_ws_goods ORDER BY name";
		$result = $mysqli->query($query);
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

/**
 * This function gets the pictures of one good from database
 */
function getGoodsPictures($mysqli, $goodid = '') {
	$query = "SELECT cwcp.*
						FROM cpa_ws_goods_pictures cwcp
						WHERE cwcp.goodid = $goodid
						ORDER BY cwcp.pictureindex";
	$result = $mysqli->query($query);
	$data = array();
	$data['data'] = array();
	while ($row = $result->fetch_assoc()) {
		$row['pictureindex'] = (int)$row['pictureindex'];
		$filename = '../../../private/'. $_SERVER['HTTP_HOST'].'/website/images/goods/goodid'.$goodid."/".$row['imagefilename'];
		if (isset($row['imagefilename']) && !empty($row['imagefilename']) && file_exists($filename)) {
			$row['imageinfo'] = getimagesize($filename);
		}
		$data['data'][] = $row;
	}
	$data['success'] = true;
	return $data;
	exit;
};

/**
 * This function gets the details of one good from database
 */
function getGoodDetails($mysqli, $id = '') {
	try {
		$query = "SELECT cwb.*, getWSTextLabel(label, 'fr-ca') label_fr, getWSTextLabel(label, 'en-ca') label_en,
														getWSTextLabel(description, 'fr-ca') description_fr, getWSTextLabel(description, 'en-ca') description_en
							FROM cpa_ws_goods cwb
							WHERE cwb.id = $id";
		$result = $mysqli->query($query);
		$data = array();
		$data['imageinfo'] = null;
		while ($row = $result->fetch_assoc()) {
			$row['pictures'] = getGoodsPictures($mysqli, $id)['data'];
			$data['data'][] = $row;
			$filename = '../../../private/'. $_SERVER['HTTP_HOST'].'/website/images/goods/goodid'.$id.'/'.$row['imagefilename'];
			if (isset($row['imagefilename']) && !empty($row['imagefilename']) && file_exists($filename)) {
				$data['imageinfo'] = getimagesize($filename);
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

/**
 * This function will handle insert/update/delete of pictures in DB
 * @throws Exception
 */
function updateEntirePictures($mysqli, $goodid, $pictures) {
	$data = array();
	for ($x = 0; $pictures && $x < count($pictures); $x++) {
		$id = 							$mysqli->real_escape_string(isset($pictures[$x]['id'])								? (int)$pictures[$x]['id'] : 0);
		$pictureindex = 		$mysqli->real_escape_string(isset($pictures[$x]['pictureindex'])			? (int)$pictures[$x]['pictureindex'] : 0);
		$imagefilename = 		$mysqli->real_escape_string(isset($pictures[$x]['imagefilename'])			? $pictures[$x]['imagefilename'] : '');

		$image_dir = '../../../private/'. $_SERVER['HTTP_HOST'].'/website/images/goods/goodid' . $goodid . '/';
		$thumbnail_dir = $image_dir . 'thumbnails'.'/';

		// This should not happen
		if ($mysqli->real_escape_string(isset($pictures[$x]['status'])) and $pictures[$x]['status'] == 'New') {
			// $query = "INSERT INTO cpa_ws_goods_pictures(goodid, pictureindex, imagefilename)
	    //           VALUES ($goodid, 0, '')";
			// if (!$mysqli->query($query)) {
			// 	throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
			// }
		}

		if ($mysqli->real_escape_string(isset($pictures[$x]['status'])) and $pictures[$x]['status'] == 'Modified') {
			$query = "UPDATE cpa_ws_goods_pictures SET pictureindex = $pictureindex WHERE id = $id";
			if ($mysqli->query($query)) {
				// $query = "UPDATE cpa_ws_text SET text = '$title_fr' WHERE id = $title AND language = 'fr-ca'";
				// if ($mysqli->query($query)) {
				// 	$query = "UPDATE cpa_ws_text SET text = '$title_en' WHERE id = $title AND language = 'en-ca'";
				// 	if ($mysqli->query($query)) {
						$data['success'] = true;
				// 	} else {
				// 		throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
				// 	}
				// } else {
				// 	throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
				// }
			} else {
				throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
			}
		}

		if ($mysqli->real_escape_string(isset($pictures[$x]['status'])) and $pictures[$x]['status'] == 'Deleted') {
			$query = "DELETE FROM cpa_ws_goods_pictures WHERE id = $id";
			if ($mysqli->query($query)) {
				// $query = "DELETE FROM cpa_ws_text WHERE id = $title";
				// if ($mysqli->query($query)) {
					// Now delete the image and the thumbnail
					if (isset($imagefilename) && !empty($imagefilename)) {
			      $oldfilename = $image_dir . $imagefilename;
						$data['filename'] = $oldfilename;
			      if (file_exists($oldfilename)) {
			          unlink($oldfilename);
			      }
						$oldfilename = $thumbnail_dir . $imagefilename;
			      if (file_exists($oldfilename)) {
			          unlink($oldfilename);
			      }
			    }
				// } else {
				// 	throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
				// }
			} else {
				throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
			}
		}
	}
	$data['success'] = true;
	return $data;
};

function updateEntireGood($mysqli, $good) {
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
