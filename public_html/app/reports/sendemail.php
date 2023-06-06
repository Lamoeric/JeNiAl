<?php
/**
 * Send email
 */

//Import PHPMailer classes into the global namespace
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\OAuth;
use League\OAuth2\Client\Provider\Google;

//foreach ($_SERVER as $parm => $value)  echo "$parm = '$value'\n";
//Load Composer's autoloader
//require '../../../../vendor/autoload.php';
require dirname(__FILE__) . '/../../vendor/autoload.php';

require_once('getbaseurl.php');

function initEmail($mysqli) {
	//SMTP needs accurate times, and the PHP time zone MUST be set
	//This should be done in your php.ini, but this is how to do it if you don't have access to that
	date_default_timezone_set('Etc/UTC');
	$mail = new PHPMailer(true);

	$data = getEmailConfiguration($mysqli);

	//Tell PHPMailer to use SMTP
	if ($data['issmtp']) {
		$mail->isSMTP();
	}

	//Enable SMTP debugging
	// 0 = off (for production use)
	// 1 = client messages
	// 2 = client and server messages
	$mail->SMTPDebug = $data['smtpdebuglevel'];

	//Ask for HTML-friendly debug output
	$mail->Debugoutput = $data['smtpdebugoutputformat'];
		
	//Set the hostname of the mail server
	$mail->Host = $data['smtphost'];

	//Set the SMTP port number - 587 for authenticated TLS, a.k.a. RFC4409 SMTP submission
	$mail->Port = $data['smtpport'];

	//Set the encryption system to use - ssl (deprecated) or tls
	$mail->SMTPSecure = $data['smtpsecure'];

	//Type of authentication to use
	if ($data['authtype'] == 2) {
		$mail->SMTPAuth = true;

		//Username to use for SMTP authentication - use full email address for gmail
		$mail->Username = $data['smtpusername'];

		//Password to use for SMTP authentication
		$mail->Password = $data['smtppassword'];
	} else if ($data['authtype'] == 3) {
		$mail->SMTPAuth = true;
		$mail->AuthType = 'XOAUTH2';

		//Use league/oauth2-client as OAuth2 token provider
		//Fill in authentication details here
		//Either the gmail account owner, or the user that gave consent
		$mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
		$email = $data['smtpusername'];
		$clientId = $data['oauthclientid'];
		$clientSecret = $data['oauthclientsecret'];

		//Obtained by configuring and running get_oauth_token.php
		//after setting up an app in Google Developer Console.
		$refreshToken = $data['oauthrefreshtoken'];
		
		if ($data['oauthprovider'] == 'Google') {
			//Create a new OAuth2 provider instance
			$provider = new Google(
					[
							'clientId' => $clientId,
							'clientSecret' => $clientSecret,
					]
			);
		}

		//Pass the OAuth provider instance to PHPMailer
		$mail->setOAuth(
				new OAuth(
						[
								'provider' => $provider,
								'clientId' => $clientId,
								'clientSecret' => $clientSecret,
								'refreshToken' => $refreshToken,
								'userName' => $email,
						]
				)
		);
	} else {
		$mail->SMTPAuth = false;
	}

	//Set who the message is to be sent from
	$mail->setFrom($data['smtpsetfrom'], $data['smtpsetfullnamefrom']);

	//Set an alternative reply-to address
	// TODO : we could configure this in the configuration table
	if (isset($data['smtpaddreplyto']) && !empty($data['smtpaddreplyto'])) {
		$mail->addReplyTo($data['smtpaddreplyto'], $data['smtpfullnamereplyto']);
	}

	$mail->CharSet="UTF-8";

	return $mail;
}

function getClubShortName($mysqli, $language) {
	$retVal = "";
	$query = "SELECT getTextLabel(cpashortname, '$language') shortname FROM cpa_configuration where id = 1";
	$result = $mysqli->query($query);
	while ($row = $result->fetch_assoc()) {
		$retVal = $row['shortname'];
	}
	return $retVal;
}

function getEmailConfiguration($mysqli) {
	$data = array();
	$query = "SELECT issmtp, smtpdebuglevel, smtpdebugoutputformat, smtpsecure, smtphost, smtpport, authtype, smtpusername, smtppassword, oauthprovider, 
									oauthclientid, oauthclientsecret, oauthrefreshtoken, smtpsetfrom, smtpsetfullnamefrom, smtpaddreplyto, smtpfullnamereplyto, smtptestemailaddress
						FROM cpa_configuration where id = 1";
	$result = $mysqli->query($query);
	while ($row = $result->fetch_assoc()) {
		$data['data'][] = $row;
	}
	return $data['data'][0];
}

/**
 * This function sends an email
 */
function sendoneemail($mysqli, $address, $fullname, $subject, $body, $relativepath, $attachmentfilename, $language = "fr-ca", $ccaddress = null, $ccfullname = "") {
	global $privateimages;

	$data = array();
	$data['success'] = true;
	$mail = initEmail($mysqli);
	try{

		//Set who the message is to be sent to
		$mail->addAddress($address, $fullname);
		if (isset($ccaddress)) {
			$mail->addcc($ccaddress, $ccfullname);
		}

		//Set the subject line
		// $mail->Subject = 'JeNiAl test';
		$mail->Subject = $subject;

		//Read an HTML message body from an external file, convert referenced images to embedded,
		//convert HTML into a basic plain-text alternative body
		$temp = file_get_contents($relativepath.'/emailtemplates/contents.html');

		$temp = str_replace("%title%", $subject, $temp);
		$temp = str_replace('%body%', $body, $temp);
		$temp = str_replace('%clubshortname%', getClubShortName($mysqli, $language), $temp);
		$temp = str_replace('%url%', getBaseUrl($mysqli), $temp);
		$mail->IsHTML(true);
		$mail->Body = $temp;
		//Replace the plain text body with one created manually
		// $mail->AltBody = 'This is a plain-text message body';

		//Attach an image file
		if ($attachmentfilename) $mail->addAttachment($attachmentfilename);
		// $mail->AddEmbeddedImage($relativepath.'/cpa_logo.jpg', 'cpa_logo');
		// $mail->AddEmbeddedImage('../../../private/' . $_SERVER['HTTP_HOST'] . '/images/cpa_logo.jpg', 'cpa_logo');
		// $mail->AddEmbeddedImage(dirname(__FILE__) . '/../../../private/' . $_SERVER['HTTP_HOST'] . '/images/cpa_logo.jpg', 'cpa_logo');
		// Use new variable defined in private/[hostname]/include/config.php
		$mail->AddEmbeddedImage(dirname(__FILE__) . '/../../../' . $privateimages . 'cpa_logo.jpg', 'cpa_logo');
		$mail->AddEmbeddedImage($relativepath.'/emailtemplates/header.jpg', 'header');

		//send the message, check for errors
		if (!$mail->send()) {
			// echo "Mailer Error: " . $mail->ErrorInfo;
			$data['success'] = false;
			$data['message'] = "Mailer Error: " . $mail->ErrorInfo;
			return $data;
			exit;
		} else {
			$data['success'] = true;
			$data['message'] = "Email sent!";
			$data['body'] = $temp;
			return $data;
			exit;
		}
	}catch (phpmailerException $e) {
		$data = array();
		$data['success'] = false;
		$data['message'] = $e->errorMessage();
		return $data;
		exit;
	}catch (Exception $e) {
		$data = array();
		$data['success'] = false;
		$data['message'] = $e->getMessage();
		return $data;
		exit;
	}
}
