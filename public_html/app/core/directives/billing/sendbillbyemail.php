<?php
require_once('../../../../../private/'. $_SERVER['HTTP_HOST'].'/include/config.php');
require_once('../../../../include/nocache.php');
require_once('../../../reports/sendemail.php');

/**
 * This function sends the bill by email
 */
function sendBillByEmail($mysqli, $email, $fullname, $filename, $language="fr-ca") {
	try{
		$data = array();
		$data['success'] = false;
		if ($language == 'en-ca') {
			$title = "Your invoice";
			$body = "<p>Please find attached your invoice.</p>";
		} else {
			$title = "Votre facture";
			$body = "<p>Veuillez trouver ci-joint votre facture.</p>";
		}
		// Send email
		$data = sendoneemail($mysqli, $email, $fullname, $title, $body, '../../../../images', $filename, $language);
		// $data['success'] = true;
		echo json_encode($data);
		exit;
	}catch (Exception $e) {
		$data = array();
		$data['success'] = false;
		$data['message'] = $e->getMessage();
		echo json_encode($data);
		exit;
	}
};

sendBillByEmail($mysqli, $_POST['email'], $_POST['fullname'], $_POST['filename'], $_POST['language']);
?>
