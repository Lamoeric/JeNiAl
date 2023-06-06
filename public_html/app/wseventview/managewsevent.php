<?php
/*
Author : Eric Lamoureux
*/
require_once('../../../private/'. $_SERVER['HTTP_HOST'].'/include/config.php');
require_once('../../include/nocache.php');

if (isset($_POST['type']) && !empty(isset($_POST['type']))) {
	$type = $_POST['type'];

	switch ($type) {
		case "insert_event":
			insert_event($mysqli, $_POST['event']);
			break;
		case "updateEntireEvent":
			updateEntireEvent($mysqli, json_decode($_POST['event'], true));
			break;
		case "delete_event":
			delete_event($mysqli, $_POST['event']);
			break;
		case "getAllEvents":
			getAllEvents($mysqli);
			break;
		case "getEventDetails":
			getEventDetails($mysqli, $_POST['id']);
			break;
		default:
			invalidRequest();
	}
} else {
	invalidRequest();
};

/**
 * This function will handle event add functionality
 * @throws Exception
 */
function insert_event($mysqli, $event) {
	try {
		$data = array();
		$name =						$mysqli->real_escape_string(isset($event['name']) 						? $event['name'] : '');
		$eventlist =			$mysqli->real_escape_string(isset($event['eventlist']) 				? (int)$event['eventlist'] : 0);
		$eventdate =			$mysqli->real_escape_string(isset($event['eventdatestr']) 		? $event['eventdatestr'] : '');
		$label =					$mysqli->real_escape_string(isset($event['label']) 						? (int)$event['label'] : 0);
		$label_fr =				$mysqli->real_escape_string(isset($event['label_fr']) 				? $event['label_fr'] : '');
		$label_en =				$mysqli->real_escape_string(isset($event['label_en']) 				? $event['label_en'] : '');
		$imagefilename =	$mysqli->real_escape_string(isset($event['imagefilename']) 		? $event['imagefilename'] : '');
		$publish =				$mysqli->real_escape_string(isset($event['publish']) 					? (int)$event['publish'] : 0);

		$query = "INSERT INTO cpa_ws_events (name, eventdate, imagefilename, publish, eventlist, label)
							VALUES ('$name', curdate(), '', $publish, 1, create_wsText('$name', '$name'))";
		if ($mysqli->query($query)) {
			$data['success'] = true;
			if (empty($id)) $id = $data['id'] = (int) $mysqli->insert_id;
			$dirname = '../../../private/'. $_SERVER['HTTP_HOST'].'/website/images/gallery/eventid'.$id;
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
 * This function will handle event update functionality
 * @throws Exception
 */
function update_event($mysqli, $event) {
	$data = array();
	$id =							$mysqli->real_escape_string(isset($event['id']) 							? (int)$event['id'] : 0);
	$name =						$mysqli->real_escape_string(isset($event['name']) 						? $event['name'] : '');
	$eventlist =			$mysqli->real_escape_string(isset($event['eventlist']) 				? (int)$event['eventlist'] : 0);
	$eventdate =			$mysqli->real_escape_string(isset($event['eventdatestr']) 				? $event['eventdatestr'] : '');
	$label =					$mysqli->real_escape_string(isset($event['label']) 						? (int)$event['label'] : 0);
	$label_fr =				$mysqli->real_escape_string(isset($event['label_fr']) 				? $event['label_fr'] : '');
	$label_en =				$mysqli->real_escape_string(isset($event['label_en']) 				? $event['label_en'] : '');
	$imagefilename =	$mysqli->real_escape_string(isset($event['imagefilename']) 		? $event['imagefilename'] : '');
	$publish =				$mysqli->real_escape_string(isset($event['publish']) 					? (int)$event['publish'] : 0);

	$query = "UPDATE cpa_ws_events SET name = '$name', eventdate = '$eventdate', eventlist = $eventlist, publish = $publish WHERE id = $id";
	if ($mysqli->query($query)) {
		$query = "UPDATE cpa_ws_text SET text = '$label_fr' WHERE id = $label and language = 'fr-ca'";
		if ($mysqli->query($query)) {
			$query = "UPDATE cpa_ws_text SET text = '$label_en' WHERE id = $label and language = 'en-ca'";
			if ($mysqli->query($query)) {
				$data['success'] = true;
				$data['message'] = 'Event updated successfully.';
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

// function rrmdir($src) {
// 	$dir = opendir($src);
// 	while (false !== ($file = readdir($dir))) {
// 		if (($file != '.') && ($file != '..')) {
// 			$full = $src . '/' . $file;
// 			if (is_dir($full)) {
// 				rrmdir($full);
// 			} else {
// 				unlink($full);
// 			}
// 		}
// 	}
// 	closedir($dir);
// 	rmdir($src);
// }

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
 * This function will handle user deletion
 * @throws Exception
 */
function delete_event($mysqli, $event) {
	try {
		$id = 						$mysqli->real_escape_string(isset($event['id']) 							? (int)$event['id'] : 0);
		$label = 					$mysqli->real_escape_string(isset($event['label']) 						? (int)$event['label'] : 0);

		if (empty($id)) throw new Exception("Invalid event id.");
		// Delete the filename related to the event
		$dirname = '../../../private/'. $_SERVER['HTTP_HOST'].'/website/images/gallery/eventid'.$id;
		if (file_exists($dirname)) {
			rrmdir($dirname);
		}
		$query = "DELETE FROM cpa_ws_events WHERE id = $id";
		if ($mysqli->query($query)) {
			$query = "DELETE FROM cpa_ws_text WHERE id = $label";
			if ($mysqli->query($query)) {
				$mysqli->close();
				$data['success'] = true;
				$data['message'] = 'Event deleted successfully.';
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
 * This function gets list of all events from database
 */
function getAllEvents($mysqli) {
	try {
		$query = "SELECT id, name, eventdate, eventlist FROM cpa_ws_events ORDER BY eventdate DESC";
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
 * This function gets the pictures of one event from database
 */
function getEventPictures($mysqli, $eventid = '') {
	$query = "SELECT cwep.*
						FROM cpa_ws_events_pictures cwep
						WHERE cwep.eventid = $eventid
						ORDER BY cwep.pictureindex";
	$result = $mysqli->query($query);
	$data = array();
	$data['data'] = array();
	while ($row = $result->fetch_assoc()) {
		$row['pictureindex'] = (int)$row['pictureindex'];
		$filename = '../../../private/'. $_SERVER['HTTP_HOST'].'/website/images/gallery/eventid'.$eventid."/".$row['imagefilename'];
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
 * This function gets the details of one event from database
 */
function getEventDetails($mysqli, $id = '') {
	try {
		$query = "SELECT cwe.*, getWSTextLabel(label, 'fr-ca') label_fr, getWSTextLabel(label, 'en-ca') label_en
							FROM cpa_ws_events cwe
							WHERE cwe.id = $id";
		$result = $mysqli->query($query);
		$data = array();
		$data['imageinfo'] = null;
		while ($row = $result->fetch_assoc()) {
			$row['pictures'] = getEventPictures($mysqli, $id)['data'];
			$data['data'][] = $row;
			$filename = '../../../private/'. $_SERVER['HTTP_HOST'].'/website/images/gallery/eventid'.$id."/".$row['imagefilename'];
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
function updateEntirePictures($mysqli, $eventid, $pictures) {
	$data = array();
	for ($x = 0; $pictures && $x < count($pictures); $x++) {
		$id = 							$mysqli->real_escape_string(isset($pictures[$x]['id'])								? (int)$pictures[$x]['id'] : 0);
		$pictureindex = 		$mysqli->real_escape_string(isset($pictures[$x]['pictureindex'])			? (int)$pictures[$x]['pictureindex'] : 0);
		$imagefilename = 		$mysqli->real_escape_string(isset($pictures[$x]['imagefilename'])			? $pictures[$x]['imagefilename'] : '');
		// Mayve later ?
		// $publish = 					$mysqli->real_escape_string(isset($pictures[$x]['publish'])						? (int)$pictures[$x]['publish'] : 0);
		// $title = 						$mysqli->real_escape_string(isset($pictures[$x]['title']) 						? (int)$pictures[$x]['title'] : 0);
		// $title_en =	 				$mysqli->real_escape_string(isset($pictures[$x]['title_en']) 					? $pictures[$x]['title_en'] : '');
		// $title_fr = 				$mysqli->real_escape_string(isset($pictures[$x]['title_fr']) 					? $pictures[$x]['title_fr'] : '');

		$image_dir = '../../../private/'. $_SERVER['HTTP_HOST'].'/website/images/gallery/eventid' . $eventid . '/';
		$thumbnail_dir = '../../../private/'. $_SERVER['HTTP_HOST'].'/website/images/gallery/eventid' . $eventid . '/thumbnails'.'/';

		// This should not happen
		if ($mysqli->real_escape_string(isset($pictures[$x]['status'])) and $pictures[$x]['status'] == 'New') {
			// $query = "INSERT INTO cpa_ws_events_pictures(eventid, pictureindex, imagefilename)
	    //           VALUES ($eventid, 0, '')";
			// if (!$mysqli->query($query)) {
			// 	throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
			// }
		}

		if ($mysqli->real_escape_string(isset($pictures[$x]['status'])) and $pictures[$x]['status'] == 'Modified') {
			$query = "UPDATE cpa_ws_events_pictures SET pictureindex = $pictureindex WHERE id = $id";
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
			$query = "DELETE FROM cpa_ws_events_pictures WHERE id = $id";
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

function updateEntireEvent($mysqli, $event) {
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
