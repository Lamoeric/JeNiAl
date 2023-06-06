<?php
/*
Author : Eric Lamoureux
*/
require_once('../../../private/'. $_SERVER['HTTP_HOST'].'/include/config.php');
require_once('../../include/nocache.php');

if (isset($_POST['type']) && !empty(isset($_POST['type']))) {
	$type = $_POST['type'];

	switch ($type) {
		case "insert_document":
			insert_document($mysqli, $_POST['document']);
			break;
		case "updateEntireDocument":
			updateEntireDocument($mysqli, $_POST['document']);
			break;
		case "delete_document":
			delete_document($mysqli, $_POST['document']);
			break;
		case "getAllDocuments":
			getAllDocuments($mysqli);
			break;
		case "getDocumentDetails":
			getDocumentDetails($mysqli, $_POST['id']);
			break;
		default:
			invalidRequest();
	}
} else {
	invalidRequest();
};

/**
 * This function will handle document add functionality
 * @throws Exception
 */
function insert_document($mysqli, $document) {
	try {
		$data = array();
		$documentname =			$mysqli->real_escape_string(isset($document['documentname']) 			? $document['documentname'] : '');
		$publish =					$mysqli->real_escape_string(isset($document['publish']) 					? (int)$document['publish'] : 0);
		$publishon =				$mysqli->real_escape_string(isset($document['publishonstr']) 				? $document['publishonstr'] : '');
		$filename =					$mysqli->real_escape_string(isset($document['filename']) 					? (int)$document['filename'] : 0);
		$filename_fr =			$mysqli->real_escape_string(isset($document['filename_fr']) 			? $document['filename_fr'] : '');
		$filename_en =			$mysqli->real_escape_string(isset($document['filename_en']) 			? $document['filename_en'] : '');
		$description =			$mysqli->real_escape_string(isset($document['description']) 			? (int)$document['description'] : 0);
		$description_fr =		$mysqli->real_escape_string(isset($document['description_fr']) 		? $document['description_fr'] : '');
		$description_en =		$mysqli->real_escape_string(isset($document['description_en']) 		? $document['description_en'] : '');

		$query = "INSERT INTO cpa_ws_documents (documentname, publish, publishon, filename, description)
							VALUES ('$documentname', $publish, curdate(), create_wsText('$filename_en', '$filename_fr'), create_wsText('$description_en', '$description_fr'))";
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
 * This function will handle document update functionality
 * @throws Exception
 */
function update_document($mysqli, $document) {
	$data = array();
	$id =								$mysqli->real_escape_string(isset($document['id']) 								? (int)$document['id'] : 0);
	$documentname =			$mysqli->real_escape_string(isset($document['documentname']) 			? $document['documentname'] : '');
	$publish =					$mysqli->real_escape_string(isset($document['publish']) 					? (int)$document['publish'] : 0);
	$publishon =				$mysqli->real_escape_string(isset($document['publishonstr']) 			? $document['publishonstr'] : '');
	$filename =					$mysqli->real_escape_string(isset($document['filename']) 					? (int)$document['filename'] : 0);
	$filename_fr =			$mysqli->real_escape_string(isset($document['filename_fr']) 			? $document['filename_fr'] : '');
	$filename_en =			$mysqli->real_escape_string(isset($document['filename_en']) 			? $document['filename_en'] : '');
	$description =			$mysqli->real_escape_string(isset($document['description']) 			? (int)$document['description'] : 0);
	$description_fr =		$mysqli->real_escape_string(isset($document['description_fr']) 		? $document['description_fr'] : '');
	$description_en =		$mysqli->real_escape_string(isset($document['description_en']) 		? $document['description_en'] : '');

	$query = "UPDATE cpa_ws_documents SET documentname = '$documentname', publish = $publish, publishon = '$publishon' WHERE id = $id";
	if ($mysqli->query($query)) {
		$query = "UPDATE cpa_ws_text SET text = '$description_fr' WHERE id = $description and language = 'fr-ca'";
		if ($mysqli->query($query)) {
			$query = "UPDATE cpa_ws_text SET text = '$description_en' WHERE id = $description and language = 'en-ca'";
			if ($mysqli->query($query)) {
				$data['success'] = true;
				$data['publishon'] = $publishon;
				$data['message'] = 'Document updated successfully.';
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
 * This function will handle document deletion
 * @throws Exception
 */
function delete_document($mysqli, $document) {
	try {
		$id = 					$mysqli->real_escape_string(isset($document['id']) 						? (int)$document['id'] : 0);
		$description =	$mysqli->real_escape_string(isset($document['description']) 	? (int)$document['description'] : 0);
		$filename =			$mysqli->real_escape_string(isset($document['filename']) 			? (int)$document['filename'] : 0);
		$filename_fr =	$mysqli->real_escape_string(isset($document['filename_fr']) 	? $document['filename_fr'] : '');
		$filename_en =	$mysqli->real_escape_string(isset($document['filename_en']) 	? $document['filename_en'] : '');

		if (empty($id)) throw new Exception("Invalid document id.");
		// Delete the filename related to the document (french)
		$oldfilename = '../../../private/'. $_SERVER['HTTP_HOST'].'/website/documents/fr-ca/'.$filename_fr;
		if (isset($filename_fr) && !empty($filename_fr) && file_exists($oldfilename)) {
			unlink($oldfilename);
		}
		// Delete the filename related to the document (english)
		$oldfilename = '../../../private/'. $_SERVER['HTTP_HOST'].'/website/documents/en-ca/'.$filename_en;
		if (isset($filename_en) && !empty($filename_en) && file_exists($oldfilename)) {
			unlink($oldfilename);
		}
		$query = "DELETE FROM cpa_ws_documents WHERE id = $id";
		if ($mysqli->query($query)) {
			$query = "DELETE FROM cpa_ws_text WHERE id IN ($filename, $description)";
			if ($mysqli->query($query)) {
				$mysqli->close();
				$data['success'] = true;
				$data['message'] = 'Document deleted successfully.';
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
 * This function gets list of all documents from database
 */
function getAllDocuments($mysqli) {
	try {
		$query = "SELECT id, documentname FROM cpa_ws_documents ORDER BY publishon";
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
 * This function gets the details of one document from database
 */
function getDocumentDetails($mysqli, $id = '') {
	try {
		$query = "SELECT cwp.*, getWSTextLabel(filename, 'fr-ca') filename_fr, getWSTextLabel(filename, 'en-ca') filename_en,
										 getWSTextLabel(description, 'fr-ca') description_fr, getWSTextLabel(description, 'en-ca') description_en
							FROM cpa_ws_documents cwp
							WHERE cwp.id = $id";
		$result = $mysqli->query($query);
		$data = array();
		while ($row = $result->fetch_assoc()) {
			$data['data'][] = $row;
			$filename = '../../../private/'. $_SERVER['HTTP_HOST'].'/website/documents/fr-ca/'.$row['filename_fr'];
			if (isset($row['filename_fr']) && !empty($row['filename_fr']) && file_exists($filename)) {
				$data['fileinfo_fr'] = filesize($filename);
			}
			$filename = '../../../private/'. $_SERVER['HTTP_HOST'].'/website/documents/en-ca/'.$row['filename_en'];
			if (isset($row['filename_en']) && !empty($row['filename_en']) && file_exists($filename)) {
				$data['fileinfo_en'] = filesize($filename);
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

function updateEntireDocument($mysqli, $document) {
	try {
		$data = array();

		$data['successdocument'] = update_document($mysqli, $document);
		$mysqli->close();

		$data['success'] = true;
		$data['message'] = 'Document updated successfully.';
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
