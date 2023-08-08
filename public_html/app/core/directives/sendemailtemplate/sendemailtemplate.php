<?php
/*
Author : Eric Lamoureux
*/
require_once('../../../../../private/'. $_SERVER['HTTP_HOST'].'/include/config.php');
require_once('../../../../include/nocache.php');
require_once('../../../reports/sendemail.php');

if( isset($_POST['type']) && !empty( isset($_POST['type']) ) ){
	$type = $_POST['type'];

	switch ($type) {
		case "getEmailtemplateDetails":
			getEmailtemplateDetails($mysqli, $_POST['id'], $_POST['language']);
			break;
		case "sendTestEmail":
			sendTestEmail($mysqli, $_POST['newEmail']);
			break;
		default:
			invalidRequest();
	}
} else {
	invalidRequest();
};

/**
 * This function gets the details of an email template from database
 */
function getEmailtemplateDetails($mysqli, $templateid, $language){
	try{
		$query = "SELECT cet.*, getEmailTemplateText(title, '$language') title, getEmailTemplateText(mainmessage, '$language') paragraphtext
							FROM cpa_emails_templates cet
							WHERE cet.id = $templateid";
		$result = $mysqli->query( $query );
		$data = array();
		while ($row = $result->fetch_assoc()) {
			$row['id'] = (int) $row['id'];
			$data['data'][] = $row;
		}
		$data['success'] = true;
		echo json_encode($data);
		exit;
	}catch (Exception $e){
		$data = array();
		$data['success'] = false;
		$data['message'] = $e->getMessage();
		echo json_encode($data);
		exit;
	}
};

/**
 * This function will handle sending a test email
 */
function sendTestEmail($mysqli, $email) {
	try {
		$data = array();
		$data['success'] = true;
//		$data['message'] = 'Disabled';
//		$data['message'] = 'Cette fonctionnalité est désactivée temporairement';
		// We need to send a test email
		$data = sendoneemail($mysqli, $email['emailaddress'], null, $email['title'], $email['mainmessage'], '../../../../images', null, $email['language'], null, null);
		echo json_encode($data);
		exit;
	}catch (Exception $e) {
		$data = array();
		$data['success'] = false;
		$data['message'] = $e->getMessage();
		echo json_encode($data);
		exit;
	}
}

function invalidRequest(){
	$data = array();
	$data['success'] = false;
	$data['message'] = "Invalid request.";
	echo json_encode($data);
	exit;
};

?>
