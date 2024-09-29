<?php
/*
Author : Eric Lamoureux
*/
require_once('../../../private/' . $_SERVER['HTTP_HOST'] . '/include/config.php');
require_once('../../include/nocache.php');
require_once('../../backend/removefile.php');
require_once('../../backend/getwssupportedlanguages.php');
require_once('../../backend/getimagefileName.php');

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
			getAllDocuments($mysqli, $_POST['language']);
			break;
		case "getDocumentDetails":
			getDocumentDetails($mysqli, $_POST['id']);
			break;
		default:
			invalidRequest($type);
	}
} else {
	invalidRequest();
};

/**
 * This function will handle document add functionality
 * @throws Exception
 */
function insert_document($mysqli, $document)
{
	try {
		$data = array();
		$documentname =	$mysqli->real_escape_string(isset($document['documentname'])	? $document['documentname'] : '');

		$query = "	INSERT INTO cpa_ws_documents (documentname, publish, publishon, filename, description)
					VALUES ('$documentname', 0, curdate(), create_wsText('', ''), create_wsText('$documentname', '$documentname'))";
		if ($mysqli->query($query)) {
			$data['success'] = true;
			if (empty($id)) $data['id'] = (int) $mysqli->insert_id;
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
 * This function will handle document update functionality
 * @throws Exception
 */
function update_document($mysqli, $document)
{
	$data = array();
	$id =				$mysqli->real_escape_string(isset($document['id'])				? (int)$document['id'] : 0);
	$documentname =		$mysqli->real_escape_string(isset($document['documentname'])	? $document['documentname'] : '');
	$publish =			$mysqli->real_escape_string(isset($document['publish'])			? (int)$document['publish'] : 0);
	$publishon =		$mysqli->real_escape_string(isset($document['publishonstr'])	? $document['publishonstr'] : '');
	$description =		$mysqli->real_escape_string(isset($document['description'])		? (int)$document['description'] : 0);
	$description_fr =	$mysqli->real_escape_string(isset($document['description_fr'])	? $document['description_fr'] : '');
	$description_en =	$mysqli->real_escape_string(isset($document['description_en'])	? $document['description_en'] : '');

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
 * This function will handle document deletion
 * @throws Exception
 */
function delete_document($mysqli, $document)
{
	try {
		$id =			$mysqli->real_escape_string(isset($document['id'])			? (int)$document['id'] : 0);
		$description =	$mysqli->real_escape_string(isset($document['description'])	? (int)$document['description'] : 0);
		$filename =		$mysqli->real_escape_string(isset($document['filename'])	? (int)$document['filename'] : 0);
		$filename_fr =	$mysqli->real_escape_string(isset($document['filename_fr'])	? $document['filename_fr'] : '');
		$filename_en =	$mysqli->real_escape_string(isset($document['filename_en'])	? $document['filename_en'] : '');

		if (empty($id)) throw new Exception("Invalid document id.");
		$query = "DELETE FROM cpa_ws_documents WHERE id = $id";
		if ($mysqli->query($query)) {
			$query = "DELETE FROM cpa_ws_text WHERE id IN ($filename, $description)";
			if ($mysqli->query($query)) {
				$mysqli->close();
				$data['oldfilenameFr']  = removeFile('/website/documents/fr-ca/', $filename_fr, false);
				$data['oldfilenameEn']  = removeFile('/website/documents/en-ca/', $filename_en, false);
				$data['success'] = true;
				$data['message'] = 'Document deleted successfully.';
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
 * This function gets list of all documents from database
 */
function getAllDocuments($mysqli, $language)
{
	try {
		$query = "	SELECT id, documentname, publish, getCodeDescription('YESNO',publish, '$language') ispublish,
							getCodeDescription('YESNO', if (getWSTextLabel(filename, 'fr-ca')!='', 1, 0), '$language') isimagefr,
							getCodeDescription('YESNO', if (getWSTextLabel(filename, 'en-ca')!='', 1, 0), '$language') isimageen
					FROM cpa_ws_documents 
					ORDER BY publish DESC, publishon";
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
 * This function gets the details of one document from database
 */
function getDocumentDetails($mysqli, $id = '')
{
	try {
		$query = "	SELECT cwp.*, getWSTextLabel(filename, 'fr-ca') filename_fr, getWSTextLabel(filename, 'en-ca') filename_en,
							getWSTextLabel(description, 'fr-ca') description_fr, getWSTextLabel(description, 'en-ca') description_en
					FROM cpa_ws_documents cwp
					WHERE cwp.id = $id";
		$result = $mysqli->query($query);
		$data = array();
		while ($row = $result->fetch_assoc()) {
			$data['data'][] = $row;
			$filename = getImageFileName('/website/documents/fr-ca/', $row['filename_fr']);
			$data['fileinfo_fr'] = isset($filename) ? filesize($filename) : null;
			$filename = getImageFileName('/website/documents/en-ca/', $row['filename_en']);
			$data['fileinfo_en'] = isset($filename) ? filesize($filename) : null;;
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

function updateEntireDocument($mysqli, $document)
{
	try {
		$data = array();

		$data['successdocument'] = update_document($mysqli, $document);
		$mysqli->close();

		$data['success'] = true;
		$data['message'] = 'Document updated successfully.';
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
