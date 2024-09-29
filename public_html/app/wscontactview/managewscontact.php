<?php
/*
Author : Eric Lamoureux
*/
require_once('../../../private/' . $_SERVER['HTTP_HOST'] . '/include/config.php');
require_once('../../include/nocache.php');
require_once('../../backend/invalidrequest.php');
require_once('../../backend/getimagefileinfo.php');
require_once('../../backend/getimagefileName.php');

if (isset($_POST['type']) && !empty(isset($_POST['type']))) {
	$type = $_POST['type'];

	switch ($type) {
		case "updateEntireContact":
			updateEntireContact($mysqli, $_POST['contact']);
			break;
		case "getAllContacts":
			getAllContacts($mysqli);
			break;
		case "getContactDetails":
			getContactDetails($mysqli, $_POST['fscname']);
			break;
		default:
			invalidRequest($type);
	}
} else {
	invalidRequest();
};

/**
 * This function will handle contact update functionality
 * @throws Exception
 */
function update_contact($mysqli, $contact)
{
	$data = array();
	$fscname =					$mysqli->real_escape_string(isset($contact['fscname'])					? $contact['fscname'] : '');
	$label =					$mysqli->real_escape_string(isset($contact['label'])					? (int)$contact['label'] : 0);
	$label_fr =					$mysqli->real_escape_string(isset($contact['label_fr'])					? $contact['label_fr'] : '');
	$label_en =					$mysqli->real_escape_string(isset($contact['label_en'])					? $contact['label_en'] : '');
	$address1 =					$mysqli->real_escape_string(isset($contact['address1'])					? $contact['address1'] : '');
	$address2 =					$mysqli->real_escape_string(isset($contact['address2'])					? $contact['address2'] : '');
	$email1 =					$mysqli->real_escape_string(isset($contact['email1'])					? $contact['email1'] : '');
	$email2 =					$mysqli->real_escape_string(isset($contact['email2'])					? $contact['email2'] : '');
	$phone1 =					$mysqli->real_escape_string(isset($contact['phone1'])					? $contact['phone1'] : '');
	$phone2 =					$mysqli->real_escape_string(isset($contact['phone2'])					? $contact['phone2'] : '');
	$orgno =					$mysqli->real_escape_string(isset($contact['orgno'])					? $contact['orgno'] : '');
	$facebookgroup =			$mysqli->real_escape_string(isset($contact['facebookgroup'])			? $contact['facebookgroup'] : '');
	$twitteraccount =			$mysqli->real_escape_string(isset($contact['twitteraccount'])			? $contact['twitteraccount'] : '');
	$supportfrench =			$mysqli->real_escape_string(isset($contact['supportfrench'])			? (int)$contact['supportfrench'] : 0);
	$supportenglish =			$mysqli->real_escape_string(isset($contact['supportenglish'])			? (int)$contact['supportenglish'] : 0);
	$supportmotion =			$mysqli->real_escape_string(isset($contact['supportmotion'])			? (int)$contact['supportmotion'] : 0);
	$supportmyskatingspace =	$mysqli->real_escape_string(isset($contact['supportmyskatingspace'])	? (int)$contact['supportmyskatingspace'] : 0);

	$query = "	UPDATE cpa_ws_contactinfo
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
 * This function gets list of all contacts from database
 */
function getAllContacts($mysqli)
{
	try {
		$query = "SELECT fscname FROM cpa_ws_contactinfo ORDER BY fscname";
		$result = $mysqli->query($query);
		$data = array();
		while ($row = $result->fetch_assoc()) {
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

/**
 * This function gets the details of one contact from database
 */
function getContactDetails($mysqli, $fscname = '')
{
	try {
		$query = "	SELECT cwc.*, getWSTextLabel(label, 'fr-ca') label_fr, getWSTextLabel(label, 'en-ca') label_en
				  	FROM cpa_ws_contactinfo cwc
				  	WHERE cwc.fscname = '$fscname'";
		$result = $mysqli->query($query);
		$data = array();
		while ($row = $result->fetch_assoc()) {
			$filename = getImageFileName('/website/images/', $row['logofilename']);
			$row['logoinfo'] = getImageFileInfo($filename);

			$filename = getImageFileName('/website/images/', $row['sliderfilename']);
			$row['sliderinfo'] = getImageFileInfo($filename);
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

function updateEntireContact($mysqli, $contact)
{
	try {
		$data = array();

		$data['successcontact'] = update_contact($mysqli, $contact);
		$mysqli->close();
		$data['success'] = true;
		$data['message'] = 'Contact updated successfully.';
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
