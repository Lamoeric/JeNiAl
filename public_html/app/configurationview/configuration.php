<?php
/*
Author : Eric Lamoureux
*/
require_once('../../../private/'. $_SERVER['HTTP_HOST'].'/include/config.php');
require_once('../../include/nocache.php');
require_once('../../backend/invalidrequest.php'); //
require_once('../reports/sendemail.php');

if (isset($_POST['type']) && !empty(isset($_POST['type']))) {
	$type = $_POST['type'];

	switch ($type) {
		case "insert_configuration":
			insert_configuration($mysqli);
			break;
		case "updateEntireConfiguration":
			updateEntireConfiguration($mysqli);
			break;
		case "delete_configuration":
			delete_configuration($mysqli, $_POST['configuration']);
			break;
		case "getAllConfigurations":
			getAllConfigurations($mysqli);
			break;
		case "getConfigurationDetails":
			getConfigurationDetails($mysqli, $_POST['id']);
			break;
		case "sendTestEmail":
			sendTestEmail($mysqli, $_POST['emailaddress']);
			break;
		default:
			invalidRequest($type);
	}
} else {
	invalidRequest();
};

function updateEntireConfiguration($mysqli) {
	try{
		$data = array();
		// $id = $mysqli->real_escape_string(isset($_POST['configuration']['id']) ? $_POST['configuration']['id'] : '');

		update_configuration($mysqli, $_POST['configuration']);
		$mysqli->close();

		$data['success'] = true;
		$data['message'] = 'Configuration updated successfully.';
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

function insert_configuration($mysqli) {
	echo json_encode(update_configuration($mysqli));
	exit;
}

/**
 * This function will handle user update functionality
 * @throws Exception
 */
function update_configuration($mysqli, $configuration) {
	// try{
		$data = array();
		$id = 						$mysqli->real_escape_string(isset($configuration['id']) 					? $configuration['id'] : '');
		$cpalongname = 				$mysqli->real_escape_string(isset($configuration['cpalongname']) 			? $configuration['cpalongname'] : '');
		$cpalongname_fr = 			$mysqli->real_escape_string(isset($configuration['cpalongname_fr']) 		? $configuration['cpalongname_fr'] : '');
		$cpalongname_en = 			$mysqli->real_escape_string(isset($configuration['cpalongname_en']) 		? $configuration['cpalongname_en'] : '');
		$cpashortname = 			$mysqli->real_escape_string(isset($configuration['cpashortname']) 			? $configuration['cpashortname'] : '');
		$cpashortname_fr = 			$mysqli->real_escape_string(isset($configuration['cpashortname_fr']) 		? $configuration['cpashortname_fr'] : '');
		$cpashortname_en = 			$mysqli->real_escape_string(isset($configuration['cpashortname_en']) 		? $configuration['cpashortname_en'] : '');
		$cpaaddress = 				$mysqli->real_escape_string(isset($configuration['cpaaddress']) 			? $configuration['cpaaddress'] : '');
		$cpaaddress_fr = 			$mysqli->real_escape_string(isset($configuration['cpaaddress_fr']) 			? $configuration['cpaaddress_fr'] : '');
		$cpaaddress_en = 			$mysqli->real_escape_string(isset($configuration['cpaaddress_en']) 			? $configuration['cpaaddress_en'] : '');
		$cpaurl = 					$mysqli->real_escape_string(isset($configuration['cpaurl']) 				? $configuration['cpaurl'] : '');
		$issmtp = 					$mysqli->real_escape_string(isset($configuration['issmtp']) 				? (int)$configuration['issmtp'] : 1);
		$smtpdebuglevel = 			$mysqli->real_escape_string(isset($configuration['smtpdebuglevel']) 		? (int)$configuration['smtpdebuglevel'] : 1);
		$smtpdebugoutputformat = 	$mysqli->real_escape_string(isset($configuration['smtpdebugoutputformat'])	? $configuration['smtpdebugoutputformat'] : 'html');
		$smtpsecure = 				$mysqli->real_escape_string(isset($configuration['smtpsecure']) 			? $configuration['smtpsecure'] : 'tls');
		$smtphost = 				$mysqli->real_escape_string(isset($configuration['smtphost']) 				? $configuration['smtphost'] : 'smtp.gmail.com');
		$smtpport = 				$mysqli->real_escape_string(isset($configuration['smtpport']) 				? (int)$configuration['smtpport'] : 587);
//		$smtpauth = 				$mysqli->real_escape_string(isset($configuration['smtpauth']) 				? (int)$configuration['smtpauth'] : 0);
		$authtype = 				$mysqli->real_escape_string(isset($configuration['authtype']) 				? (int)$configuration['authtype'] : 0);
		$oauthprovider = 			$mysqli->real_escape_string(isset($configuration['oauthprovider']) 			? $configuration['oauthprovider'] : '');
		$oauthclientid = 			$mysqli->real_escape_string(isset($configuration['oauthclientid']) 			? $configuration['oauthclientid'] : '');
		$oauthclientsecret = 		$mysqli->real_escape_string(isset($configuration['oauthclientsecret']) 		? $configuration['oauthclientsecret'] : '');
		$oauthrefreshtoken = 		$mysqli->real_escape_string(isset($configuration['oauthrefreshtoken']) 		? $configuration['oauthrefreshtoken'] : '');
		$smtpusername = 			$mysqli->real_escape_string(isset($configuration['smtpusername']) 			? $configuration['smtpusername'] : '');
		$smtppassword = 			$mysqli->real_escape_string(isset($configuration['smtppassword']) 			? $configuration['smtppassword'] : '');
		$smtpsetfrom = 				$mysqli->real_escape_string(isset($configuration['smtpsetfrom']) 			? $configuration['smtpsetfrom'] : '');
		$smtpsetfullnamefrom = 		$mysqli->real_escape_string(isset($configuration['smtpsetfullnamefrom']) 	? $configuration['smtpsetfullnamefrom'] : '');
		$smtpaddreplyto = 			$mysqli->real_escape_string(isset($configuration['smtpaddreplyto']) 		? $configuration['smtpaddreplyto'] : '');
		$smtpfullnamereplyto = 		$mysqli->real_escape_string(isset($configuration['smtpfullnamereplyto']) 	? $configuration['smtpfullnamereplyto'] : '');
		$smtptestemailaddress = 	$mysqli->real_escape_string(isset($configuration['smtptestemailaddress']) 	? $configuration['smtptestemailaddress'] : '');
		$presidentfirstname = 		$mysqli->real_escape_string(isset($configuration['presidentfirstname']) 	? $configuration['presidentfirstname'] : '');
		$presidentlastname = 		$mysqli->real_escape_string(isset($configuration['presidentlastname']) 		? $configuration['presidentlastname'] : '');
		$presidentemail = 			$mysqli->real_escape_string(isset($configuration['presidentemail']) 		? $configuration['presidentemail'] : '');
		$testdirfirstname = 		$mysqli->real_escape_string(isset($configuration['testdirfirstname']) 		? $configuration['testdirfirstname'] : '');
		$testdirlastname = 			$mysqli->real_escape_string(isset($configuration['testdirlastname']) 		? $configuration['testdirlastname'] : '');
		$testdiremail = 			$mysqli->real_escape_string(isset($configuration['testdiremail']) 			? $configuration['testdiremail'] : '');
		$paypal_usesandbox =		$mysqli->real_escape_string(isset($configuration['paypal_usesandbox']) 		? (int)$configuration['paypal_usesandbox'] : 1);
		$paypal_clientid = 			$mysqli->real_escape_string(isset($configuration['paypal_clientid']) 		? $configuration['paypal_clientid'] : '');
		$paypal_clientsecret = 		$mysqli->real_escape_string(isset($configuration['paypal_clientsecret']) 	? $configuration['paypal_clientsecret'] : '');

		if (empty($id)) {
			$data['insert'] = true;
			$query = "	INSERT INTO cpa_configuration (id, cpaurl, cpalongname, cpashortname, cpaaddress)
						VALUES (NULL, cpaurl, create_systemText('$cpalongname_en', '$cpalongname_fr'), create_systemText('$cpashortname_en', '$cpashortname_fr'), create_systemText('$cpaaddress_en', '$cpaaddress_fr'))";
			if ($mysqli->query($query)) {
				$data['success'] = true;
				if (!empty($id))$data['message'] = 'Configuration updated successfully.';
				else $data['message'] = 'Configuration inserted successfully.';
				if (empty($id))$data['id'] = (int) $mysqli->insert_id;
				else $data['id'] = (int) $id;
			} else {
				throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
			}
		} else {
			$query = "	UPDATE cpa_configuration
						SET cpaurl = '$cpaurl', issmtp = $issmtp, smtpdebuglevel = $smtpdebuglevel, smtpdebugoutputformat = '$smtpdebugoutputformat', smtpsecure = '$smtpsecure',
							smtphost = '$smtphost', smtpport = $smtpport, smtpusername = '$smtpusername', smtppassword = '$smtppassword', smtpsetfrom = '$smtpsetfrom',
							smtpsetfullnamefrom = '$smtpsetfullnamefrom', smtpaddreplyto = '$smtpaddreplyto', smtpfullnamereplyto = '$smtpfullnamereplyto',
							smtptestemailaddress = '$smtptestemailaddress', authtype = '$authtype', oauthprovider = '$oauthprovider', oauthclientid = '$oauthclientid', 
							oauthclientsecret = '$oauthclientsecret', oauthrefreshtoken = '$oauthrefreshtoken',	presidentfirstname = '$presidentfirstname', 
							presidentlastname = '$presidentlastname', presidentemail = '$presidentemail', testdirfirstname = '$testdirfirstname',	
							testdirlastname = '$testdirlastname', testdiremail = '$testdiremail', paypal_usesandbox = $paypal_usesandbox, 
							paypal_clientid = '$paypal_clientid', paypal_clientsecret = '$paypal_clientsecret'
						WHERE id = $id";
			if (!$mysqli->query($query)) {
				throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
			}
			if (empty($cpalongname)) {
				$query = "UPDATE cpa_configuration SET cpalongname = create_systemText('$cpalongname_en', '$cpalongname_fr') WHERE id = $id";
				if (!$mysqli->query($query)) {
					throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
				}
			}
			if (empty($cpashortname)) {
				$query = "UPDATE cpa_configuration SET cpashortname = create_systemText('$cpashortname_en', '$cpashortname_fr') WHERE id = $id";
				if (!$mysqli->query($query)) {
					throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
				}
			}
			if (empty($cpaaddress)) {
				$query = "UPDATE cpa_configuration SET cpaaddress = create_systemText('$cpaaddress_en', '$cpaaddress_fr') WHERE id = $id";
				if (!$mysqli->query($query)) {
					throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
				}
			}
			$query = "UPDATE cpa_text set text = '$cpalongname_fr' where id = $cpalongname and language = 'fr-ca'";
			if ($mysqli->query($query)) {
				$query = "UPDATE cpa_text set text = '$cpalongname_en' where id = $cpalongname and language = 'en-ca'";
				if ($mysqli->query($query)) {
					$query = "UPDATE cpa_text set text = '$cpashortname_fr' where id = $cpashortname and language = 'fr-ca'";
					if ($mysqli->query($query)) {
						$query = "UPDATE cpa_text set text = '$cpashortname_en' where id = $cpashortname and language = 'en-ca'";
						if ($mysqli->query($query)) {
							$query = "UPDATE cpa_text set text = '$cpaaddress_fr' where id = $cpaaddress and language = 'fr-ca'";
							if ($mysqli->query($query)) {
								$query = "UPDATE cpa_text set text = '$cpaaddress_en' where id = $cpaaddress and language = 'en-ca'";
								if ($mysqli->query($query)) {
									$data['success'] = true;
								} else {
									throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
								}
							} else {
								throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
							}
						} else {
							throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
						}
					} else {
						throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
					}
				} else {
					throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
				}
			} else {
				throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
			}
		}
		return $data;
	// }catch (Exception $e) {
	// 	$data = array();
	// 	$data['success'] = false;
	// 	$data['message'] = $e->getMessage();
	// 	return $data;
	// }
};

/**
 * This function will handle configuration deletion
 * @param string $id
 * @throws Exception
 */
function delete_configuration($mysqli, $configuration = '') {
	try{
		$id = 								$mysqli->real_escape_string(isset($configuration['id']) 									? $configuration['id'] : '');
		$cpalongname = 				$mysqli->real_escape_string(isset($configuration['cpalongname']) 					? $configuration['cpalongname'] : '');
		$cpashortname = 			$mysqli->real_escape_string(isset($configuration['cpashortname']) 				? $configuration['cpashortname'] : '');
		$cpaaddress = 				$mysqli->real_escape_string(isset($configuration['cpaaddress']) 					? $configuration['cpaaddress'] : '');

		if (empty($id)) throw new Exception("Invalid Configuration.");
		$query = "DELETE FROM cpa_text WHERE id = '$cpalongname'";
		if ($mysqli->query($query)) {
			$query = "DELETE FROM cpa_text WHERE id = '$cpashortname'";
			if ($mysqli->query($query)) {
				$query = "DELETE FROM cpa_text WHERE id = '$cpaaddress'";
				if ($mysqli->query($query)) {
					$query = "DELETE FROM cpa_configuration WHERE id = '$id'";
					if ($mysqli->query($query)) {
						$data['success'] = true;
						$data['message'] = 'Configuration deleted successfully.';
						echo json_encode($data);
						exit;
					} else {
						throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
					}
				} else {
					throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
				}
			} else {
				throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
			}
		} else {
			throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
		}
	}catch (Exception $e) {
		$data = array();
		$data['success'] = false;
		$data['message'] = $e->getMessage();
		echo json_encode($data);
		exit;
	}
};

/**
 * This function gets list of all configuration from database
 */
function getAllConfigurations($mysqli) {
	try{
		$query = "SELECT * FROM cpa_configuration order by id";
		$result = $mysqli->query($query);
		$data = array();
		$data['data'] = array();
		while ($row = $result->fetch_assoc()) {
			$row['id'] = (int) $row['id'];
			$data['data'][] = $row;
		}
		$data['success'] = true;
		echo json_encode($data);exit;
	}catch (Exception $e) {
		$data = array();
		$data['success'] = false;
		$data['message'] = $e->getMessage();
		echo json_encode($data);
		exit;
	}
};

/**
 * This function gets the details of one configuration from database
 */
function getConfigurationDetails($mysqli, $id = '') {
	try{
		if (empty($id)) throw new Exception("Invalid User.");
		$query = "SELECT *, getEnglishTextLabel(cpalongname) as cpalongname_en, getFrenchTextLabel(cpalongname) as cpalongname_fr,
								getEnglishTextLabel(cpashortname) as cpashortname_en, getFrenchTextLabel(cpashortname) as cpashortname_fr,
								getEnglishTextLabel(cpaaddress) as cpaaddress_en, getFrenchTextLabel(cpaaddress) as cpaaddress_fr
							FROM cpa_configuration WHERE id = '$id'";
		$result = $mysqli->query($query);
		$data = array();
		while ($row = $result->fetch_assoc()) {
			$row['id'] = (int) $row['id'];
//			$row['amount'] = (float) $row['amount'];
			$data['data'][] = $row;
		}
		$data['success'] = true;
		echo json_encode($data);exit;
	}catch (Exception $e) {
		$data = array();
		$data['success'] = false;
		$data['message'] = $e->getMessage();
		echo json_encode($data);
		exit;
	}
};

/**
 * This function will handle sending an email to the coach who submited the external test approbation request
 */
function sendTestEmail($mysqli, $emailaddress) {
	try {
		// We need to send an email to the coach
		// Get the approbation details
		$title = 'This is a test';
		$body = 'This is a test';
		// Send email
		$data = sendoneemail($mysqli, $emailaddress, '', $title, $body, '../../images', null, 'fr-ca', null, "");
		echo json_encode($data);
		exit;
	}catch (Exception $e) {
		// $data = array();
		$data['success'] = false;
		$data['message'] = $e->getMessage();
		echo json_encode($data);
		exit;
	}
}

?>
