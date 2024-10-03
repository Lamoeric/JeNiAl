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
			insert_event($mysqli, $_POST['language'], $_POST['element']);
			break;
		case "updateEntireEvent":
			updateEntireEvent($mysqli, json_decode($_POST['event'], true));
			break;
		case "delete_event":
			delete_event($mysqli, $_POST['event']);
			break;
		case "getAllEvents":
			getAllEvents($mysqli, $_POST['language']);
			break;
		case "getEventDetails":
			getEventDetails($mysqli, $_POST['language'], $_POST['id']);
			break;
		default:
			invalidRequest($type);
	}
} else {
	invalidRequest();
};

/**
 * This function will handle event add functionality
 * @throws Exception
 */
function insert_event($mysqli, $language, $event)
{
	try {
		$data = array();
		$name = $mysqli->real_escape_string(isset($event['name'])	? $event['name'] : '');

		$query = "	INSERT INTO cpa_ws_events (name, eventdate, imagefilename, publish, eventlist, label)
					VALUES ('$name', curdate(), '', 0, 1, create_wsText('$name', '$name'))";
		if ($mysqli->query($query)) {
			$data['success'] = true;
			if (empty($id)) $id = $data['id'] = (int) $mysqli->insert_id;
			$dirname = createUploadSubDirectory('/website/images/gallery/eventid' . $id);
			$dirname = createUploadSubDirectory('/website/images/gallery/eventid' . $id . '/thumbnails');
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
 * This function will handle event update functionality
 * @throws Exception
 */
function update_event($mysqli, $event)
{
	$data = array();
	$id =				$mysqli->real_escape_string(isset($event['id']) 			? (int)$event['id'] : 0);
	$name =				$mysqli->real_escape_string(isset($event['name']) 			? $event['name'] : '');
	$eventlist =		$mysqli->real_escape_string(isset($event['eventlist']) 		? (int)$event['eventlist'] : 0);
	$eventdate =		$mysqli->real_escape_string(isset($event['eventdatestr']) 	? $event['eventdatestr'] : '');
	$label =			$mysqli->real_escape_string(isset($event['label']) 			? (int)$event['label'] : 0);
	$label_fr =			$mysqli->real_escape_string(isset($event['label_fr']) 		? $event['label_fr'] : '');
	$label_en =			$mysqli->real_escape_string(isset($event['label_en']) 		? $event['label_en'] : '');
	$imagefilename =	$mysqli->real_escape_string(isset($event['imagefilename'])	? $event['imagefilename'] : '');
	$publish =			$mysqli->real_escape_string(isset($event['publish']) 		? (int)$event['publish'] : 0);

	$query = "UPDATE cpa_ws_events SET name = '$name', eventdate = '$eventdate', eventlist = $eventlist, publish = $publish WHERE id = $id";
	if ($mysqli->query($query)) {
		$mysqli->query("call update_wsText($label, '$label_en', '$label_fr')");
		$data['success'] = true;
		$data['message'] = 'Event updated successfully.';
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
function delete_event($mysqli, $event)
{
	try {
		$id = 	$mysqli->real_escape_string(isset($event['id'])		? (int)$event['id'] : 0);
		$label = $mysqli->real_escape_string(isset($event['label'])	? (int)$event['label'] : 0);

		if (empty($id)) throw new Exception("Invalid event id.");
		$query = "DELETE FROM cpa_ws_events WHERE id = $id";
		if ($mysqli->query($query)) {
			$query = "DELETE FROM cpa_ws_text WHERE id = $label";
			if ($mysqli->query($query)) {
				// Delete the directory related to the event
				$data['deletedDirectory'] = deleteUploadSubDirectory('/website/images/gallery/eventid' . $id);
				$mysqli->close();
				$data['success'] = true;
				$data['message'] = 'Event deleted successfully.';
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
 * This function gets list of all events from database
 */
function getAllEvents($mysqli, $language)
{
	try {
		$query = "	SELECT id, name, eventdate, eventlist, publish, getWSTextLabel(label, '$language') label,
							getCodeDescription('YESNO',publish, '$language') ispublish, 
							getCodeDescription('YESNO', if (imagefilename is not null and imagefilename!='', 1, 0), '$language') isimage,
							getCodeDescription('wseventtypes',eventlist, '$language') listlabel, 
							(select count(*) FROM cpa_ws_events_pictures cwep where cwep.eventid = cwe.id) imagecount
					FROM cpa_ws_events cwe
					ORDER BY eventdate DESC";
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
 * This function gets the pictures of one event from database
 */
function getEventPictures($mysqli, $eventid = '')
{
	$query = "SELECT cwep.*	FROM cpa_ws_events_pictures cwep WHERE cwep.eventid = $eventid	ORDER BY cwep.pictureindex";
	$result = $mysqli->query($query);
	$data = array();
	$data['data'] = array();
	while ($row = $result->fetch_assoc()) {
		$row['pictureindex'] = (int)$row['pictureindex'];
		$filename = getImageFileName('/website/images/gallery/eventid' . $eventid . '/', $row['imagefilename']);
		$row['imageinfo'] = getImageFileInfo($filename);
		$data['data'][] = $row;
	}
	$data['success'] = true;
	return $data;
	exit;
};

/**
 * This function gets the details of one event from database
 */
function getEventDetails($mysqli, $language, $id = '')
{
	try {
		$query = "	SELECT cwe.*, getWSTextLabel(label, 'fr-ca') label_fr, getWSTextLabel(label, 'en-ca') label_en,
							getWSTextLabel(label, '$language') maintitle
					FROM cpa_ws_events cwe
					WHERE cwe.id = $id";
		$result = $mysqli->query($query);
		$data = array();
		$data['imageinfo'] = null;
		while ($row = $result->fetch_assoc()) {
			$row['pictures'] = getEventPictures($mysqli, $id)['data'];
			$data['data'][] = $row;
			$filename = getImageFileName('/website/images/gallery/eventid' . $id . '/', $row['imagefilename']);
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

/**
 * This function will handle insert/update/delete of pictures in DB
 * @throws Exception
 */
function updateEntirePictures($mysqli, $eventid, $pictures)
{
	$data = array();
	for ($x = 0; $pictures && $x < count($pictures); $x++) {
		$id = 				$mysqli->real_escape_string(isset($pictures[$x]['id'])				? (int)$pictures[$x]['id'] : 0);
		$pictureindex = 	$mysqli->real_escape_string(isset($pictures[$x]['pictureindex'])	? (int)$pictures[$x]['pictureindex'] : 0);
		$imagefilename =	$mysqli->real_escape_string(isset($pictures[$x]['imagefilename'])	? $pictures[$x]['imagefilename'] : '');

		// This should not happen
		if ($mysqli->real_escape_string(isset($pictures[$x]['status'])) and $pictures[$x]['status'] == 'New') {
			throw new Exception("A picture with the NEW status should never happen");
		}

		if ($mysqli->real_escape_string(isset($pictures[$x]['status'])) and $pictures[$x]['status'] == 'Modified') {
			$query = "UPDATE cpa_ws_events_pictures SET pictureindex = $pictureindex WHERE id = $id";
			if ($mysqli->query($query)) {
				$data['success'] = true;
			} else {
				throw new Exception($mysqli->sqlstate . ' - ' . $mysqli->error);
			}
		}

		if ($mysqli->real_escape_string(isset($pictures[$x]['status'])) and $pictures[$x]['status'] == 'Deleted') {
			$query = "DELETE FROM cpa_ws_events_pictures WHERE id = $id";
			if ($mysqli->query($query)) {
				$data['oldfilename']  = removeFile('/website/images/gallery/eventid' . $eventid . '/', $imagefilename, false);
				$data['oldthumbnail'] = removeFile('/website/images/gallery/eventid' . $eventid . '/thumbnails'.'/', $imagefilename, false);
			} else {
				throw new Exception($mysqli->sqlstate . ' - ' . $mysqli->error);
			}
		}
	}
	$data['success'] = true;
	return $data;
};

function updateEntireEvent($mysqli, $event)
{
	try {
		$data = array();

		$data['successevent'] = update_event($mysqli, $event);
		if ($mysqli->real_escape_string(isset($event['pictures']))) {
			$data['successpictures'] = updateEntirePictures($mysqli, $event['id'], $event['pictures']);
		}
		$mysqli->close();

		$data['success'] = true;
		$data['message'] = 'Event updated successfully.';
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
