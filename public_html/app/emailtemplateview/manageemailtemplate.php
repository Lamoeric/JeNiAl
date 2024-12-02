<?php
/*
Author : Eric Lamoureux
*/
require_once('../../../private/' . $_SERVER['HTTP_HOST'] . '/include/config.php');
require_once('../../include/nocache.php');
require_once('../../backend/invalidrequest.php'); //
require_once('../reports/sendemail.php');

if (isset($_POST['type']) && !empty(isset($_POST['type']))) {
	$type = $_POST['type'];

	switch ($type) {
		case "insert_emailtemplate":
			insert_emailtemplate($mysqli, $_POST['emailtemplate']);
			break;
		case "updateEntireEmailtemplate":
			updateEntireEmailtemplate($mysqli, json_decode($_POST['emailtemplate'], true));
			break;
		case "delete_emailtemplate":
			delete_emailtemplate($mysqli, json_decode($_POST['emailtemplate'], true));
			break;
		case "getAllEmailtemplates":
			getAllEmailtemplates($mysqli);
			break;
		case "getEmailtemplateDetails":
			getEmailtemplateDetails($mysqli, $_POST['id'], $_POST['language']);
			break;
		default:
			invalidRequest($type);
	}
} else {
	invalidRequest();
};

/**
 * This function will handle emailtemplate add functionality
 * @throws Exception
 */
function insert_emailtemplate($mysqli, $emailtemplate) {
	try {
		$data = array();
		$templatename =			$mysqli->real_escape_string(isset($emailtemplate['templatename']) 			? $emailtemplate['templatename'] : '');

		$query = "INSERT INTO cpa_emails_templates (id, templatename, title, mainmessage)	VALUES (null, '$templatename', 0, 0)";
		if ($mysqli->query($query)) {
			if (empty($id)) $id = $data['id'] = (int) $mysqli->insert_id;
			$query = "UPDATE cpa_emails_templates SET title = create_emailtemplatetext($id, '', ''), mainmessage = create_emailtemplatetext($id, '', '') where id = $id";
			if (!$mysqli->query($query)) {
				throw new Exception($mysqli->sqlstate . ' - ' . $mysqli->error);
			}
			$data['success'] = true;
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
 * This function will handle emailtemplate update functionality
 * @throws Exception
 */
function update_emailtemplate($mysqli, $emailtemplate) {
	$data = array();
	$id =							$mysqli->real_escape_string(isset($emailtemplate['id']) 						? (int)$emailtemplate['id'] : 0);
	$templatename =		$mysqli->real_escape_string(isset($emailtemplate['templatename']) 	? $emailtemplate['templatename'] : '');
	$title =					$mysqli->real_escape_string(isset($emailtemplate['title']) 					? (int)$emailtemplate['title'] : 0);
	$mainmessage =		$mysqli->real_escape_string(isset($emailtemplate['mainmessage']) 		? (int)$emailtemplate['mainmessage'] : 0);
	$active =					$mysqli->real_escape_string(isset($emailtemplate['active']) 				? (int)$emailtemplate['active'] : 0);
	$paragraph = 			$emailtemplate['paragraphs'][0];
	$title_fr =				$mysqli->real_escape_string(isset($paragraph['title_fr']) 					? $paragraph['title_fr'] : '');
	$title_en =				$mysqli->real_escape_string(isset($paragraph['title_en']) 					? $paragraph['title_en'] : '');
	$mainmessage_fr =	$mysqli->real_escape_string(isset($paragraph['paragraphtext_fr']) 	? $paragraph['paragraphtext_fr'] : '');
	$mainmessage_en =	$mysqli->real_escape_string(isset($paragraph['paragraphtext_en']) 	? $paragraph['paragraphtext_en'] : '');

	$query = "UPDATE cpa_emails_templates SET templatename = '$templatename', active = $active WHERE id = $id";
	if ($mysqli->query($query)) {
		$query = "UPDATE cpa_emails_templates_texts SET text = '$title_fr' WHERE id = $title and language = 'fr-ca'";
		if ($mysqli->query($query)) {
			$query = "UPDATE cpa_emails_templates_texts SET text = '$title_en' WHERE id = $title and language = 'en-ca'";
			if ($mysqli->query($query)) {
				$query = "UPDATE cpa_emails_templates_texts SET text = '$mainmessage_fr' WHERE id = $mainmessage and language = 'fr-ca'";
				if ($mysqli->query($query)) {
					$query = "UPDATE cpa_emails_templates_texts SET text = '$mainmessage_en' WHERE id = $mainmessage and language = 'en-ca'";
					if ($mysqli->query($query)) {
						$data['success'] = true;
						$data['message'] = 'Emailtemplate updated successfully.';
						$data['paragraph'] = $paragraph;
						$data['$title'] = $title;
						$data['mainmessage'] = $mainmessage;
						$data['mainmessage_fr'] = $mainmessage_fr;
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
 * This function will handle emailtemplate deletion
 * @throws Exception
 */
function delete_emailtemplate($mysqli, $emailtemplate) {
	try {
		$id = 					$mysqli->real_escape_string(isset($emailtemplate['id']) 						? (int)$emailtemplate['id'] : 0);

		if (empty($id)) throw new Exception("Invalid emailtemplate id.");
		/* All other tables have a FK whit drop cascade */
		$query = "DELETE FROM cpa_emails_templates WHERE id = $id";
		if (!$mysqli->query($query)) {
			throw new Exception($mysqli->sqlstate . ' - ' . $mysqli->error);
		}
		$mysqli->close();
		$data['success'] = true;
		$data['message'] = 'Emailtemplate deleted successfully.';
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
 * This function gets list of all emailtemplates from database
 */
function getAllEmailtemplates($mysqli) {
	try {
		$query = "SELECT id, templatename FROM cpa_emails_templates ORDER BY id DESC";
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
 * This function gets the details of one emailtemplate from database
 */
function getEmailtemplateDetails($mysqli, $id, $language) {
	try {
		$query = "	SELECT cet.*, 
							getEmailTemplateText(title, 'fr-ca') title_fr, getEmailTemplateText(title, 'en-ca') title_en, 
							getEmailTemplateText(mainmessage, 'fr-ca') paragraphtext_fr, getEmailTemplateText(mainmessage, 'en-ca') paragraphtext_en
					FROM cpa_emails_templates cet
					WHERE cet.id = $id";
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
 * This function will update the entire email template
 */
function updateEntireEmailtemplate($mysqli, $emailtemplate) {
	try {
		$data = array();

		$data['successemailtemplate'] = update_emailtemplate($mysqli, $emailtemplate);
		$mysqli->close();

		$data['success'] = true;
		$data['message'] = 'Emailtemplate updated successfully.';
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
