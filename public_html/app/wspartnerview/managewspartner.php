<?php
/*
Author : Eric Lamoureux
*/
require_once('../../../private/'. $_SERVER['HTTP_HOST'].'/include/config.php');
require_once('../../include/nocache.php');

if (isset($_POST['type']) && !empty(isset($_POST['type']))) {
	$type = $_POST['type'];

	switch ($type) {
		case "insert_partner":
			insert_partner($mysqli, $_POST['partner']);
			break;
		case "updateEntirePartner":
			updateEntirePartner($mysqli, $_POST['partner']);
			break;
		case "delete_partner":
			delete_partner($mysqli, $_POST['partner']);
			break;
		case "getAllPartners":
			getAllPartners($mysqli);
			break;
		case "getPartnerDetails":
			getPartnerDetails($mysqli, $_POST['id']);
			break;
		default:
			invalidRequest();
	}
} else {
	invalidRequest();
};

/**
 * This function will handle partner add functionality
 * @throws Exception
 */
function insert_partner($mysqli, $partner) {
	try {
		$data = array();
		$name =							$mysqli->real_escape_string(isset($partner['name']) 						? $partner['name'] : '');
		$publish =					$mysqli->real_escape_string(isset($partner['publish']) 					? (int)$partner['publish'] : 0);
		$partnerindex =			$mysqli->real_escape_string(isset($partner['partnerindex']) 		? (int)$partner['partnerindex'] : 0);
		$imagefilename =		$mysqli->real_escape_string(isset($partner['imagefilename']) 		? (int)$partner['imagefilename'] : 0);
		$imagefilename_fr =	$mysqli->real_escape_string(isset($partner['imagefilename_fr']) ? $partner['imagefilename_fr'] : '');
		$imagefilename_en =	$mysqli->real_escape_string(isset($partner['imagefilename_en']) ? $partner['imagefilename_en'] : '');
		$link =							$mysqli->real_escape_string(isset($partner['link']) 						? (int)$partner['link'] : 0);
		$link_fr =					$mysqli->real_escape_string(isset($partner['link_fr']) 					? $partner['link_fr'] : '');
		$link_en =					$mysqli->real_escape_string(isset($partner['link_en']) 					? $partner['link_en'] : '');

		$query = "INSERT INTO cpa_ws_partners (name, publish, partnerindex, imagefilename, link)
							VALUES ('$name', $publish, $partnerindex, create_wsText('$imagefilename_en', '$imagefilename_fr'), create_wsText('$link_en', '$link_fr'))";
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
 * This function will handle partner update functionality
 * @throws Exception
 */
function update_partner($mysqli, $partner) {
	$data = array();
	$id =							$mysqli->real_escape_string(isset($partner['id']) 								? (int)$partner['id'] : 0);
	$name =							$mysqli->real_escape_string(isset($partner['name']) 						? $partner['name'] : '');
	$publish =					$mysqli->real_escape_string(isset($partner['publish']) 					? (int)$partner['publish'] : 0);
	$partnerindex =			$mysqli->real_escape_string(isset($partner['partnerindex']) 		? (int)$partner['partnerindex'] : 0);
	$imagefilename =		$mysqli->real_escape_string(isset($partner['imagefilename']) 		? (int)$partner['imagefilename'] : 0);
	$imagefilename_fr =	$mysqli->real_escape_string(isset($partner['imagefilename_fr']) ? $partner['imagefilename_fr'] : '');
	$imagefilename_en =	$mysqli->real_escape_string(isset($partner['imagefilename_en']) ? $partner['imagefilename_en'] : '');
	$link =							$mysqli->real_escape_string(isset($partner['link']) 						? (int)$partner['link'] : 0);
	$link_fr =					$mysqli->real_escape_string(isset($partner['link_fr']) 					? $partner['link_fr'] : '');
	$link_en =					$mysqli->real_escape_string(isset($partner['link_en']) 					? $partner['link_en'] : '');

	$query = "UPDATE cpa_ws_partners SET name = '$name', publish = $publish, partnerindex = $partnerindex WHERE id = $id";
	if ($mysqli->query($query)) {
		$query = "UPDATE cpa_ws_text SET text = '$imagefilename_fr' WHERE id = $imagefilename and language = 'fr-ca'";
		if ($mysqli->query($query)) {
			$query = "UPDATE cpa_ws_text SET text = '$imagefilename_en' WHERE id = $imagefilename and language = 'en-ca'";
			if ($mysqli->query($query)) {
				$query = "UPDATE cpa_ws_text SET text = '$link_fr' WHERE id = $link and language = 'fr-ca'";
				if ($mysqli->query($query)) {
					$query = "UPDATE cpa_ws_text SET text = '$link_en' WHERE id = $link and language = 'en-ca'";
					if ($mysqli->query($query)) {
						$data['success'] = true;
						$data['message'] = 'Partner updated successfully.';
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
function delete_partner($mysqli, $partner) {
	try {
		$id = 							$mysqli->real_escape_string(isset($partner['id']) 								? (int)$partner['id'] : 0);
		$imagefilename =		$mysqli->real_escape_string(isset($partner['imagefilename']) 			? (int)$partner['imagefilename'] : 0);
		$link =							$mysqli->real_escape_string(isset($partner['link']) 							? (int)$partner['link'] : 0);
		$imagefilename_fr =	$mysqli->real_escape_string(isset($partner['imagefilename_fr']) 	? $partner['imagefilename_fr'] : '');
		$imagefilename_en =	$mysqli->real_escape_string(isset($partner['imagefilename_en']) 	? $partner['imagefilename_en'] : '');

		if (empty($id)) throw new Exception("Invalid partner id.");
		// Delete the filename related to the partner (french)
		$filename = '../../../private/'. $_SERVER['HTTP_HOST'].'/website/images/partners/'.$imagefilename_fr;
		if (isset($imagefilename_fr) && !empty($imagefilename_fr) && file_exists($filename)) {
			unlink($filename);
			$data['unlink_fr'] = true;
		}
		// Delete the filename related to the partner (english)
		$filename = '../../../private/'. $_SERVER['HTTP_HOST'].'/website/images/partners/'.$imagefilename_en;
		if (isset($imagefilename_en) && !empty($imagefilename_en) && file_exists($filename)) {
			unlink($filename);
			$data['unlink_en'] = true;
		}
		$query = "DELETE FROM cpa_ws_partners WHERE id = $id";
		if ($mysqli->query($query)) {
			$query = "DELETE FROM cpa_ws_text WHERE id IN ($imagefilename, $link)";
			if ($mysqli->query($query)) {
				$mysqli->close();
				$data['filename'] = $filename;
				$data['success'] = true;
				$data['message'] = 'Partner deleted successfully.';
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
 * This function gets list of all partners from database
 */
function getAllPartners($mysqli) {
	try {
		$query = "SELECT id, name, publish FROM cpa_ws_partners ORDER BY partnerindex";
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
 * This function gets the details of one partner from database
 */
function getPartnerDetails($mysqli, $id = '') {
	try {
		$query = "SELECT cwp.*, getWSTextLabel(imagefilename, 'fr-ca') imagefilename_fr, getWSTextLabel(imagefilename, 'en-ca') imagefilename_en,
										 getWSTextLabel(link, 'fr-ca') link_fr, getWSTextLabel(link, 'en-ca') link_en
							FROM cpa_ws_partners cwp
							WHERE cwp.id = $id";
		$result = $mysqli->query($query);
		$data = array();
		$data['imageinfo'] = null;
		while ($row = $result->fetch_assoc()) {
			$data['data'][] = $row;
			$filename = '../../../private/'. $_SERVER['HTTP_HOST'].'/website/images/partners/'.$row['imagefilename_fr'];
			if (isset($row['imagefilename_fr']) && !empty($row['imagefilename_fr']) && file_exists($filename)) {
				$data['imageinfo_fr'] = getimagesize('../../../private/'. $_SERVER['HTTP_HOST'].'/website/images/partners/'.$row['imagefilename_fr']);
			}
			$filename = '../../../private/'. $_SERVER['HTTP_HOST'].'/website/images/partners/'.$row['imagefilename_en'];
			if (isset($row['imagefilename_en']) && !empty($row['imagefilename_en']) && file_exists($filename)) {
				$data['imageinfo_en'] = getimagesize('../../../private/'. $_SERVER['HTTP_HOST'].'/website/images/partners/'.$row['imagefilename_en']);
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

function updateEntirePartner($mysqli, $partner) {
	try {
		$data = array();

		$data['successpartner'] = update_partner($mysqli, $partner);
		$mysqli->close();

		$data['success'] = true;
		$data['message'] = 'Partner updated successfully.';
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
