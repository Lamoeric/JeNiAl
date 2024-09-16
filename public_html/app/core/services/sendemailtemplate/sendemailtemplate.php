<?php
/*
Author : Eric Lamoureux
*/
require_once('../../../../../private/' . $_SERVER['HTTP_HOST'] . '/include/config.php');
require_once('../../../../include/nocache.php');
require_once('../../../../include/invalidrequest.php');
require_once('../../../reports/sendemail.php');

if (isset($_POST['type']) && !empty(isset($_POST['type']))) {
	$type = $_POST['type'];

	switch ($type) {
		case "getEmailtemplateDetails":
			getEmailtemplateDetails($mysqli, $_POST['id'], $_POST['language']);
			break;
		case "sendEmail":
			sendEmail($mysqli, $_POST['title'], $_POST['emailbody'], $_POST['emailaddress'], $_POST['language']);
			break;
		default:
			invalidRequest($type);
	}
} else {
	invalidRequest();
};

/**
 * This function gets the details of an email template from database
 */
function getEmailtemplateDetails($mysqli, $templateid, $language) {
	try {
		$query = "	SELECT cet.*, getEmailTemplateText(title, '$language') title, getEmailTemplateText(mainmessage, '$language') paragraphtext
					FROM cpa_emails_templates cet
					WHERE cet.id = $templateid";
		$result = $mysqli->query($query);
		$data = array();
		while ($row = $result->fetch_assoc()) {
			$row['id'] = (int) $row['id'];
			$data['data'][] = $row;
		}
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
 * This function will handle sending an email
 */
function sendEmail($mysqli, $title, $emailbody, $emailaddress, $language) {
	try {
		$data = array();
		$data['success'] = true;
		//		$data['message'] = 'Disabled';
		//		$data['message'] = 'Cette fonctionnalit� est d�sactiv�e temporairement';
		// We need to send an email
		$data = sendoneemail($mysqli, $emailaddress, null, $title, $emailbody, '../../../../images', null, $language, null, null);
		echo json_encode($data);
		exit;
	} catch (Exception $e) {
		$data = array();
		$data['success'] = false;
		$data['message'] = $e->getMessage();
		echo json_encode($data);
		exit;
	}
}
