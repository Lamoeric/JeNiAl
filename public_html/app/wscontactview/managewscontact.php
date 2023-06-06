<?php
/*
Author : Eric Lamoureux
*/
require_once('../../../private/'. $_SERVER['HTTP_HOST'].'/include/config.php');
require_once('../../include/nocache.php');

if (isset($_POST['type']) && !empty(isset($_POST['type']))) {
	$type = $_POST['type'];

	switch ($type) {
		// case "insert_contact":
		// 	insert_contact($mysqli, $_POST['contact']);
		// 	break;
		case "updateEntireContact":
			updateEntireContact($mysqli, $_POST['contact']);
			break;
		// case "delete_contact":
		// 	delete_contact($mysqli, $_POST['contact']);
		// 	break;
		case "getAllContacts":
			getAllContacts($mysqli);
			break;
		case "getContactDetails":
			getContactDetails($mysqli, $_POST['fscname']);
			break;
		default:
			invalidRequest();
	}
} else {
	invalidRequest();
};

/**
 * This function will handle contact add functionality
 * @throws Exception
 */
function insert_contact($mysqli, $contact) {
	try {
		$data = array();
		$fscname =			$mysqli->real_escape_string(isset($contact['fscname']) 		? $contact['fscname'] : '');
		$label =				$mysqli->real_escape_string(isset($contact['label']) 			? (int)$contact['label'] : 0);
		$label_fr =			$mysqli->real_escape_string(isset($contact['label_fr']) 	? $contact['label_fr'] : '');
		$label_en =			$mysqli->real_escape_string(isset($contact['label_en']) 	? $contact['label_en'] : '');
		$address1 =			$mysqli->real_escape_string(isset($contact['address1']) 	? $contact['address1'] : '');
		$address2 =			$mysqli->real_escape_string(isset($contact['address2']) 	? $contact['address2'] : '');
		$email1 =				$mysqli->real_escape_string(isset($contact['email1']) 		? $contact['email1'] : '');
		$email2 =				$mysqli->real_escape_string(isset($contact['email2']) 		? $contact['email2'] : '');
		$phone1 =				$mysqli->real_escape_string(isset($contact['phone1']) 		? $contact['phone1'] : '');
		$phone2 =				$mysqli->real_escape_string(isset($contact['phone2']) 		? $contact['phone2'] : '');

		$query = "INSERT INTO cpa_ws_contactinfo (fscname, address1, address2, email1, email2, phone1, phone2, label)
							VALUES ('$fscname', '$address1', '$address2', '$email1', '$email2', '$phone1', '$phone2', create_wsText('$fscname','$fscname'))";
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
 * This function will handle contact update functionality
 * @throws Exception
 */
function update_contact($mysqli, $contact) {
	$data = array();
	$fscname =								$mysqli->real_escape_string(isset($contact['fscname']) 									? $contact['fscname'] : '');
	$label =									$mysqli->real_escape_string(isset($contact['label']) 										? (int)$contact['label'] : 0);
	$label_fr =								$mysqli->real_escape_string(isset($contact['label_fr']) 								? $contact['label_fr'] : '');
	$label_en =								$mysqli->real_escape_string(isset($contact['label_en']) 								? $contact['label_en'] : '');
	$address1 =								$mysqli->real_escape_string(isset($contact['address1']) 								? $contact['address1'] : '');
	$address2 =								$mysqli->real_escape_string(isset($contact['address2']) 								? $contact['address2'] : '');
	$email1 =									$mysqli->real_escape_string(isset($contact['email1']) 									? $contact['email1'] : '');
	$email2 =									$mysqli->real_escape_string(isset($contact['email2']) 									? $contact['email2'] : '');
	$phone1 =									$mysqli->real_escape_string(isset($contact['phone1']) 									? $contact['phone1'] : '');
	$phone2 =									$mysqli->real_escape_string(isset($contact['phone2']) 									? $contact['phone2'] : '');
	$orgno =									$mysqli->real_escape_string(isset($contact['orgno']) 										? $contact['orgno'] : '');
	$facebookgroup =					$mysqli->real_escape_string(isset($contact['facebookgroup']) 						? $contact['facebookgroup'] : '');
	$twitteraccount =					$mysqli->real_escape_string(isset($contact['twitteraccount']) 					? $contact['twitteraccount'] : '');
	$supportfrench =					$mysqli->real_escape_string(isset($contact['supportfrench']) 						? (int)$contact['supportfrench'] : 0);
	$supportenglish =					$mysqli->real_escape_string(isset($contact['supportenglish']) 					? (int)$contact['supportenglish'] : 0);
	$supportmotion =					$mysqli->real_escape_string(isset($contact['supportmotion']) 						? (int)$contact['supportmotion'] : 0);
	$supportmyskatingspace =	$mysqli->real_escape_string(isset($contact['supportmyskatingspace']) 		? (int)$contact['supportmyskatingspace'] : 0);

	$query = "UPDATE cpa_ws_contactinfo
						SET fscname = '$fscname', address1 = '$address1', address2 = '$address2', email1 = '$email1',email2 = '$email2', phone1 = '$phone1',
								phone2 = '$phone2', orgno = '$orgno', facebookgroup = '$facebookgroup', twitteraccount = '$twitteraccount', supportfrench = $supportfrench,
								supportenglish = $supportenglish, supportmotion = $supportmotion, supportmyskatingspace = $supportmyskatingspace 
						WHERE fscname = '$fscname'";
	if ($mysqli->query($query)) {
		$query = "UPDATE cpa_ws_text SET text = '$label_fr' WHERE id = $label AND language = 'fr-ca'";
		if ($mysqli->query($query)) {
			$query = "UPDATE cpa_ws_text SET text = '$label_en' WHERE id = $label AND language = 'en-ca'";
			if ($mysqli->query($query)) {
				$data['success'] = true;
				$data['message'] = 'Contact updated successfully.';
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
// function delete_contact($mysqli, $contact) {
// 	try {
// 		$id = $mysqli->real_escape_string(isset($contact['id']) ? (int)$contact['id'] : 0);
//
// 		if (empty($id)) throw new Exception("Invalid contact id.");
// 		$query = "DELETE FROM cpa_ws_contactinfo WHERE id = $id";
// 		if ($mysqli->query($query)) {
// 			$mysqli->close();
// 			$data['success'] = true;
// 			$data['message'] = 'Contact deleted successfully.';
// 			echo json_encode($data);
// 			exit;
// 		} else {
// 			throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
// 		}
// 	} catch(Exception $e) {
// 		$data = array();
// 		$data['success'] = false;
// 		$data['message'] = $e->getMessage();
// 		echo json_encode($data);
// 		exit;
// 	}
// };

/**
 * This function gets list of all contacts from database
 */
function getAllContacts($mysqli) {
	try {
		$query = "SELECT fscname FROM cpa_ws_contactinfo ORDER BY fscname";
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
 * This function gets the details of one contact from database
 */
function getContactDetails($mysqli, $fscname = '') {
	try {
		$query = "SELECT cwc.*, getWSTextLabel(label, 'fr-ca') label_fr, getWSTextLabel(label, 'en-ca') label_en
							FROM cpa_ws_contactinfo cwc
							WHERE cwc.fscname = '$fscname'";
		$result = $mysqli->query($query);
		$data = array();
		while ($row = $result->fetch_assoc()) {
			$data['data'][] = $row;
			$filename = "../../website/images/".$row['logofilename'];
			if (isset($row['logofilename']) && !empty($row['logofilename']) && file_exists($filename)) {
				$data['logoinfo'] = getimagesize($filename);
			}
			$filename = "../../website/images/".$row['sliderfilename'];
			if (isset($row['sliderfilename']) && !empty($row['sliderfilename']) && file_exists($filename)) {
				$data['sliderinfo'] = getimagesize($filename);
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

function updateEntireContact($mysqli, $contact) {
	try {
		$data = array();

		$data['successcontact'] = update_contact($mysqli, $contact);
		$mysqli->close();

		$data['success'] = true;
		$data['message'] = 'Contact updated successfully.';
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
