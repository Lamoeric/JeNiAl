<?php
/*
Author : Eric Lamoureux
*/
require_once('../../../private/'. $_SERVER['HTTP_HOST'].'/include/config.php');
require_once('../../include/nocache.php');

if (isset($_POST['type']) && !empty(isset($_POST['type']))) {
	$type = $_POST['type'];

	switch ($type) {
		case "insert_costume":
			insert_costume($mysqli, $_POST['costume']);
			break;
		case "updateEntireCostume":
			updateEntireCostume($mysqli, $_POST['costume']);
			break;
		case "delete_costume":
			delete_costume($mysqli, $_POST['costume']);
			break;
		case "getAllCostumes":
			getAllCostumes($mysqli);
			break;
		case "getCostumeDetails":
			getCostumeDetails($mysqli, $_POST['id']);
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
 * This function will handle costume add functionality
 * @throws Exception
 */
function insert_costume($mysqli, $costume) {
	try {
		$data = array();
		$name =								$mysqli->real_escape_string(isset($costume['name']) 								? $costume['name'] : '');
		$label =							$mysqli->real_escape_string(isset($costume['label']) 								? (int)$costume['label'] : 0);
		$label_fr =						$mysqli->real_escape_string(isset($costume['label_fr']) 						? $costume['label_fr'] : '');
		$label_en =						$mysqli->real_escape_string(isset($costume['label_en']) 						? $costume['label_en'] : '');
		$girldescription =		$mysqli->real_escape_string(isset($costume['girldescription']) 			? (int)$costume['girldescription'] : 0);
		$girldescription_fr =	$mysqli->real_escape_string(isset($costume['girldescription_fr']) 	? $costume['girldescription_fr'] : '');
		$girldescription_en =	$mysqli->real_escape_string(isset($costume['girldescription_en']) 	? $costume['girldescription_en'] : '');
		$boydescription =			$mysqli->real_escape_string(isset($costume['boydescription']) 			? (int)$costume['boydescription'] : 0);
		$boydescription_fr =	$mysqli->real_escape_string(isset($costume['boydescription_fr']) 		? $costume['boydescription_fr'] : '');
		$boydescription_en =	$mysqli->real_escape_string(isset($costume['boydescription_en']) 		? $costume['boydescription_en'] : '');
		$solodescription =		$mysqli->real_escape_string(isset($costume['solodescription']) 			? (int)$costume['solodescription'] : 0);
		$solodescription_fr =	$mysqli->real_escape_string(isset($costume['solodescription_fr']) 	? $costume['solodescription_fr'] : '');
		$solodescription_en =	$mysqli->real_escape_string(isset($costume['solodescription_en']) 	? $costume['solodescription_en'] : '');
		$girlamount =					$mysqli->real_escape_string(isset($costume['girlamount']) 					? (int)$costume['girlamount'] : 0);
		$boyamount =					$mysqli->real_escape_string(isset($costume['boyamount']) 						? (int)$costume['boyamount'] : 0);
		$soloamount =					$mysqli->real_escape_string(isset($costume['soloamount']) 					? (int)$costume['soloamount'] : 0);
		$totalamount =				$mysqli->real_escape_string(isset($costume['totalamount']) 					? (int)$costume['totalamount'] : 0);
		$priceperunit =				$mysqli->real_escape_string(isset($costume['priceperunit']) 				? (float)$costume['priceperunit'] : '0.00');
		$publish =						$mysqli->real_escape_string(isset($costume['publish']) 							? (int)$costume['publish'] : 0);

		$query = "INSERT INTO cpa_ws_costumes (name, label, girldescription, boydescription, solodescription, girlamount, boyamount, soloamount, totalamount, priceperunit, publish)
							VALUES ('$name', create_wsText('$name', '$name'), create_wsText('$girldescription_en', '$girldescription_fr'), create_wsText('$boydescription_en', '$boydescription_fr'),
											create_wsText('$solodescription_en', '$solodescription_fr'), $girlamount, $boyamount, $soloamount, $totalamount, '$priceperunit', $publish)";
		if ($mysqli->query($query)) {
			$data['success'] = true;
			if (empty($id))$data['id'] = $id = (int) $mysqli->insert_id;
			$dirname = '../../../private/'. $_SERVER['HTTP_HOST'].'/website/images/costumes/costumeid'.$id;
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
 * This function will handle costume update functionality
 * @throws Exception
 */
function update_costume($mysqli, $costume) {
	$data = array();
	$id =							$mysqli->real_escape_string(isset($costume['id']) 							? (int)$costume['id'] : 0);
	$name =								$mysqli->real_escape_string(isset($costume['name']) 								? $costume['name'] : '');
	$label =							$mysqli->real_escape_string(isset($costume['label']) 								? (int)$costume['label'] : 0);
	$label_fr =						$mysqli->real_escape_string(isset($costume['label_fr']) 						? $costume['label_fr'] : '');
	$label_en =						$mysqli->real_escape_string(isset($costume['label_en']) 						? $costume['label_en'] : '');
	$girldescription =		$mysqli->real_escape_string(isset($costume['girldescription']) 			? (int)$costume['girldescription'] : 0);
	$girldescription_fr =	$mysqli->real_escape_string(isset($costume['girldescription_fr']) 	? $costume['girldescription_fr'] : '');
	$girldescription_en =	$mysqli->real_escape_string(isset($costume['girldescription_en']) 	? $costume['girldescription_en'] : '');
	$boydescription =			$mysqli->real_escape_string(isset($costume['boydescription']) 			? (int)$costume['boydescription'] : 0);
	$boydescription_fr =	$mysqli->real_escape_string(isset($costume['boydescription_fr']) 		? $costume['boydescription_fr'] : '');
	$boydescription_en =	$mysqli->real_escape_string(isset($costume['boydescription_en']) 		? $costume['boydescription_en'] : '');
	$solodescription =		$mysqli->real_escape_string(isset($costume['solodescription']) 			? (int)$costume['solodescription'] : 0);
	$solodescription_fr =	$mysqli->real_escape_string(isset($costume['solodescription_fr']) 	? $costume['solodescription_fr'] : '');
	$solodescription_en =	$mysqli->real_escape_string(isset($costume['solodescription_en']) 	? $costume['solodescription_en'] : '');
	$girlamount =					$mysqli->real_escape_string(isset($costume['girlamount']) 					? (int)$costume['girlamount'] : 0);
	$boyamount =					$mysqli->real_escape_string(isset($costume['boyamount']) 						? (int)$costume['boyamount'] : 0);
	$soloamount =					$mysqli->real_escape_string(isset($costume['soloamount']) 					? (int)$costume['soloamount'] : 0);
	$totalamount =				$mysqli->real_escape_string(isset($costume['totalamount']) 					? (int)$costume['totalamount'] : 0);
	$priceperunit =				$mysqli->real_escape_string(isset($costume['priceperunit']) 				? (float)$costume['priceperunit'] : '0.00');
	$publish =						$mysqli->real_escape_string(isset($costume['publish']) 							? (int)$costume['publish'] : 0);

	$query = "UPDATE cpa_ws_costumes SET name = '$name', girlamount = $girlamount, boyamount = $boyamount, soloamount = $soloamount, totalamount = $totalamount, priceperunit = '$priceperunit', publish = $publish WHERE id = $id";
	if ($mysqli->query($query)) {
		$query = "UPDATE cpa_ws_text SET text = '$label_fr' WHERE id = $label and language = 'fr-ca'";
		if ($mysqli->query($query)) {
			$query = "UPDATE cpa_ws_text SET text = '$label_en' WHERE id = $label and language = 'en-ca'";
			if ($mysqli->query($query)) {
				$query = "UPDATE cpa_ws_text SET text = '$girldescription_fr' WHERE id = $girldescription and language = 'fr-ca'";
				if ($mysqli->query($query)) {
					$query = "UPDATE cpa_ws_text SET text = '$girldescription_en' WHERE id = $girldescription and language = 'en-ca'";
					if ($mysqli->query($query)) {
						$query = "UPDATE cpa_ws_text SET text = '$boydescription_fr' WHERE id = $boydescription and language = 'fr-ca'";
						if ($mysqli->query($query)) {
							$query = "UPDATE cpa_ws_text SET text = '$boydescription_en' WHERE id = $boydescription and language = 'en-ca'";
							if ($mysqli->query($query)) {
								$query = "UPDATE cpa_ws_text SET text = '$solodescription_fr' WHERE id = $solodescription and language = 'fr-ca'";
								if ($mysqli->query($query)) {
									$query = "UPDATE cpa_ws_text SET text = '$solodescription_en' WHERE id = $solodescription and language = 'en-ca'";
									if ($mysqli->query($query)) {
										$data['success'] = true;
										$data['message'] = 'Costume updated successfully.';
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
function delete_costume($mysqli, $costume) {
	try {
		$id = 								$mysqli->real_escape_string(isset($costume['id']) 							? (int)$costume['id'] : 0);
		$label =							$mysqli->real_escape_string(isset($costume['label']) 						? (int)$costume['label'] : 0);
		$girldescription =		$mysqli->real_escape_string(isset($costume['girldescription']) 	? (int)$costume['girldescription'] : 0);
		$boydescription =			$mysqli->real_escape_string(isset($costume['boydescription']) 	? (int)$costume['boydescription'] : 0);
		$solodescription =		$mysqli->real_escape_string(isset($costume['solodescription']) 	? (int)$costume['solodescription'] : 0);

		if (empty($id)) throw new Exception("Invalid costume id.");
		// Delete the filename related to the costume
		$dirname = '../../../private/'. $_SERVER['HTTP_HOST'].'/website/images/costumes/costumeid'.$id;
		if (file_exists($dirname)) {
			rrmdir($dirname);
		}
		$query = "DELETE FROM cpa_ws_text WHERE id IN ($label, $girldescription, $boydescription, $solodescription)";
		if ($mysqli->query($query)) {
			$query = "DELETE FROM cpa_ws_costumes WHERE id = $id";
			if ($mysqli->query($query)) {
				$mysqli->close();
				$data['success'] = true;
				$data['message'] = 'Costume deleted successfully.';
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
 * This function gets list of all costumes from database
 */
function getAllCostumes($mysqli) {
	try {
		$data = array();
		$query = "SELECT id, name, publish FROM cpa_ws_costumes ORDER BY name";
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
 * This function gets the pictures of one costume from database
 */
function getCostumesPictures($mysqli, $costumeid = '') {
	$query = "SELECT cwcp.*
						FROM cpa_ws_costumes_pictures cwcp
						WHERE cwcp.costumeid = $costumeid
						ORDER BY cwcp.pictureindex";
	$result = $mysqli->query($query);
	$data = array();
	$data['data'] = array();
	while ($row = $result->fetch_assoc()) {
		$row['pictureindex'] = (int)$row['pictureindex'];
		$filename = '../../../private/'. $_SERVER['HTTP_HOST'].'/website/images/costumes/costumeid'.$costumeid."/".$row['imagefilename'];
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
 * This function gets the details of one costume from database
 */
function getCostumeDetails($mysqli, $id = '') {
	try {
		$query = "SELECT cwb.*, getWSTextLabel(label, 'fr-ca') label_fr, getWSTextLabel(label, 'en-ca') label_en,
														getWSTextLabel(girldescription, 'fr-ca') girldescription_fr, getWSTextLabel(girldescription, 'en-ca') girldescription_en,
														getWSTextLabel(boydescription, 'fr-ca') boydescription_fr, getWSTextLabel(boydescription, 'en-ca') boydescription_en,
														getWSTextLabel(solodescription, 'fr-ca') solodescription_fr, getWSTextLabel(solodescription, 'en-ca') solodescription_en
							FROM cpa_ws_costumes cwb
							WHERE cwb.id = $id";
		$result = $mysqli->query($query);
		$data = array();
		$data['imageinfo'] = null;
		while ($row = $result->fetch_assoc()) {
			$row['pictures'] = getCostumesPictures($mysqli, $id)['data'];
			$data['data'][] = $row;
			$filename = '../../../private/'. $_SERVER['HTTP_HOST'].'/website/images/costumes/costumeid'.$row['id']."/".$row['imagefilename'];
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
function updateEntirePictures($mysqli, $costumeid, $pictures) {
	$data = array();
	for ($x = 0; $pictures && $x < count($pictures); $x++) {
		$id = 							$mysqli->real_escape_string(isset($pictures[$x]['id'])								? (int)$pictures[$x]['id'] : 0);
		$pictureindex = 		$mysqli->real_escape_string(isset($pictures[$x]['pictureindex'])			? (int)$pictures[$x]['pictureindex'] : 0);
		$imagefilename = 		$mysqli->real_escape_string(isset($pictures[$x]['imagefilename'])			? $pictures[$x]['imagefilename'] : '');

		$image_dir = '../../../private/'. $_SERVER['HTTP_HOST'].'/website/images/costumes/costumeid' . $costumeid . '/';
		$thumbnail_dir = $image_dir . 'thumbnails'.'/';

		// This should not happen
		if ($mysqli->real_escape_string(isset($pictures[$x]['status'])) and $pictures[$x]['status'] == 'New') {
		}

		if ($mysqli->real_escape_string(isset($pictures[$x]['status'])) and $pictures[$x]['status'] == 'Modified') {
			$query = "UPDATE cpa_ws_costumes_pictures SET pictureindex = $pictureindex WHERE id = $id";
			if ($mysqli->query($query)) {
				$data['success'] = true;
			} else {
				throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
			}
		}

		if ($mysqli->real_escape_string(isset($pictures[$x]['status'])) and $pictures[$x]['status'] == 'Deleted') {
			$query = "DELETE FROM cpa_ws_costumes_pictures WHERE id = $id";
			if ($mysqli->query($query)) {
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
			} else {
				throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
			}
		}
	}
	$data['success'] = true;
	return $data;
};

function updateEntireCostume($mysqli, $costume) {
	try {
		$data = array();

		$data['successcostume'] = update_costume($mysqli, $costume);
		if ($mysqli->real_escape_string(isset($costume['pictures']))) {
			$data['successpictures'] = updateEntirePictures($mysqli, $costume['id'], $costume['pictures']);
		}
		$mysqli->close();

		$data['success'] = true;
		$data['message'] = 'Costume updated successfully.';
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
