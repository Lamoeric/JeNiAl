<?php
require_once('../../../private/'. $_SERVER['HTTP_HOST'].'/include/config.php');
require_once('../../include/nocache.php');
require_once('../reports/sendemail.php');

/**
 * This function sends the tax receipt by email
 */
function sendtaxreceiptbyemail($mysqli, $sessionlabel, $email, $fullname, $filename, $language="fr-ca") {
	try{
		$data = array();
		$data['success'] = false;
		if ($language == 'en-ca') {
			$title = "Your tax receipt for the session " . $sessionlabel;
			$body = "<p>Please find attached your tax receipt.</p>";
		} else {
			$title = "Votre reçu d'impôt pour la session " . $sessionlabel;
			$body = "<p>Veuillez trouver ci-joint votre reçu d'impôt.</p>";
		}
		// Send email
		sendoneemail($mysqli, $email, $fullname, $title, $body, '../../images', $filename, $language);
		$data['success'] = true;
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

sendtaxreceiptbyemail($mysqli, $_POST['sessionlabel'], $_POST['email'], $_POST['fullname'], $_POST['filename'], $_POST['language']);
?>
