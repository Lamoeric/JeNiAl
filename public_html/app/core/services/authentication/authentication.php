<?php
/*
Author : Eric Lamoureux
*/
require_once('../../../../../private/' . $_SERVER['HTTP_HOST'] . '/include/config.php');
require_once('../../../../include/nocache.php');
require_once('../../../../backend/insertintoaudittrail.php');
require_once('../../../reports/sendemail.php');

if (isset($_POST['type']) && !empty(isset($_POST['type']))) {
	$type = $_POST['type'];

	switch ($type) {
		case "logout":
			logout();
			break;
		case "validatelogin":
			validatelogin($mysqli, $_POST['userid'], $_POST['password']);
			break;
		case "changepassword":
			changepassword($mysqli, $_POST['userid'], $_POST['oldpassword'], $_POST['newpassword']);
			break;
		case "generatepassword":
			generatepassword($_POST['length']);
			break;
		case "validateuseroremail":
			validateuseroremail($mysqli, $_POST['emailorusercode']);
			break;
		case "resetPasswordAndSendEmail":
			resetPasswordAndSendEmail($mysqli, $_POST['emailorusercode']);
			break;
		case "setPasswordAndSendWelcomeEmail":
			setPasswordAndSendWelcomeEmail($mysqli, $_POST['emailorusercode'], $_POST['language']);
			break;
		case "validateUserRoutingPrivilege":
			validateUserRoutingPrivilege($mysqli, $_POST['userid'], $_POST['progname']);
			break;
		default:
			invalidRequest();
	}
} else {
	invalidRequest();
};

// function random_str($length, $keyspace = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ') {
// Removed 0,1,l,L,i,I from list.
function random_str($length, $keyspace = '23456789abcdefghjkmnpqrstuvwxyzABCDEFGHJKMNPQRSTUVWXYZ')
{
	$str = '';
	$max = mb_strlen($keyspace, '8bit') - 1;
	if ($max < 1) {
		throw new Exception('$keyspace must be at least two characters long');
	}
	for ($i = 0; $i < $length; ++$i) {
		$str .= $keyspace[rand(0, $max)];
	}
	return $str;
};

function generateRandomPassword($length)
{
	$str = '';
	$str = random_str($length - 1);
	$str .= random_str(1, '!$%?&*');
	return $str;
};

/**
 * This function generates a random password
 */
function generatepassword($length)
{
	try {
		$data = array();
		$data['password'] = generateRandomPassword($length);
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
 * This function gets list of all charges from database
 */
function getAllPrivileges($mysqli, $userid)
{
	try {
		$query = "SELECT crp.id, crp.roleid, crp.privilegeid, cp.code
							FROM cpa_roles_privileges crp
							JOIN cpa_users_roles cur ON cur.roleid = crp.roleid
							JOIN cpa_privileges cp ON cp.id = crp.privilegeid
							JOIN cpa_users cu ON cu.id = cur.userid
							WHERE cu.userid = '$userid'";
		$result = $mysqli->query($query);
		$data = array();
		$data['data'] = array();
		while ($row = $result->fetch_assoc()) {
			$data['data'][] = $row;
		}
		$data['success'] = true;
		return $data;
	} catch (Exception $e) {
		$data = array();
		$data['success'] = false;
		$data['message'] = $e->getMessage();
		return $data;
	}
};

/**
 * This function sets the variables and starts the session
 */
function validatelogin($mysqli, $userid, $password)
{
	try {
		$data = array();
		$query = "SELECT id, userid, fullname, preferedlanguage, passwordexpired, contactid, 
						 (select supportfrench from cpa_ws_contactinfo) supportfrench,
						 (select supportenglish from cpa_ws_contactinfo) supportenglish
					FROM cpa_users
					WHERE userid = '$userid'
					AND password = '$password'
					AND active = 1";
		$result = $mysqli->query($query);
		$row = $result->fetch_assoc();
		if (isset($row) && count($row) > 0) {
			$row['supportfrench']		= (int)$row['supportfrench'];
			$row['supportenglish']		= (int)$row['supportenglish'];
			$data['user']				= $row;
			$data['user']['privileges'] = getAllPrivileges($mysqli, $userid)['data'];

			insertIntoAuditTrail($mysqli, $userid, 'LOGIN', 'LOGGING');
			$data['success'] = true;
		} else {
			// Invalid login info
			$data['success'] = false;
		}
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
 * This function validates if the user has the proper privileges to open the program
 */
function validateUserRoutingPrivilege($mysqli, $userid, $progname)
{
	try {
		$data = array();
		$query = "SELECT cpp.*
					FROM cpa_programs_privileges cpp
					JOIN cpa_roles_privileges crp ON crp.privilegeid = cpp.privilegeid
					JOIN cpa_users_roles cur ON cur.roleid = crp.roleid
					JOIN cpa_users cu ON cu.id = cur.userid
					WHERE cu.userid = '$userid'
					AND cpp.progname = '$progname'
					AND (cpp.cpa + cpp.cds*2) & (select clubtype from cpa_configuration) != 0";
		$result = $mysqli->query($query);
		$row = $result->fetch_assoc();
		if (isset($row) && count($row) > 0) {
			insertIntoAuditTrail($mysqli, $userid, $progname, 'ACCESS');
			$data['success'] = true;
		} else {
			// Invalid login info
			insertIntoAuditTrail($mysqli, $userid, $progname, 'ACCESS', $details = "DENIED");
			$data['success'] = false;
		}
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

/**
 * This function resets the user password, set the passwordexpired flag and sends an email
 */
function resetPasswordAndSendEmail($mysqli, $emailorusercode, $language = "fr-ca")
{
	try {
		$data = array();
		$data['success'] = false;
		// Let's revalidate the user
		if (validateuseroremailinternal($mysqli, $emailorusercode) == true) {
			$newPassword = generateRandomPassword(8);
			$query = "UPDATE cpa_users
								SET password = '$newPassword', passwordexpired = 1
								WHERE userid = '$emailorusercode'
								OR email = '$emailorusercode'";
			if ($mysqli->query($query)) {
				$query = "SELECT id, userid, fullname, email, preferedlanguage, passwordexpired
									FROM cpa_users
									WHERE (userid = '$emailorusercode'
									OR email = '$emailorusercode')
									AND active = 1";
				$result = $mysqli->query($query);
				$row = $result->fetch_assoc();
				// $data['user'] = $row;
				if ($row['preferedlanguage'] == 'en-ca') {
					$title = "Password changed";
					$body = "<p>You received this email because your JeNiAl password has been reset.</p><p>User: <b>" . $row['userid'] . "</b></p><p>Password: <b>" . $newPassword . "</b></p>";
				} else {
					$title = "Mot de passe changé";
					$body = "<p>Vous recevez ce courriel parce que votre mot de passe JeNiAl a été changé.</p><p>Usager: <b>" . $row['userid'] . "</b></p><p>Mot de passe: <b>" . $newPassword . "</b></p>";
				}

				// Send email
				sendoneemail($mysqli, $row['email'], $row['fullname'], $title, $body, '../../../../images', null, $language);
				$data['success'] = true;
			}
		}
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
 * This function sets the user password, set the passwordexpired flag and sends the welcome email
 */
function setPasswordAndSendWelcomeEmail($mysqli, $emailorusercode, $language = "fr-ca")
{
	try {
		$data = array();
		$data['success'] = false;
		// Let's revalidate the user
		if (validateuseroremailinternal($mysqli, $emailorusercode) == true) {
			$newPassword = generateRandomPassword(8);
			$query = "UPDATE cpa_users
								SET password = '$newPassword', passwordexpired = 1
								WHERE userid = '$emailorusercode'
								OR email = '$emailorusercode'";
			if ($mysqli->query($query)) {
				$query = "SELECT id, userid, fullname, email, preferedlanguage, passwordexpired
									FROM cpa_users
									WHERE (userid = '$emailorusercode'
									OR email = '$emailorusercode')
									AND active = 1";
				$result = $mysqli->query($query);
				$row = $result->fetch_assoc();
				// $data['user'] = $row;
				if ($row['preferedlanguage'] == 'en-ca') {
					$title = "Welcome to MY SKATING SPACE";
					$body = "<p>Your account to access MY SKATING SPACE has been created.</p><p>User: <b>" . $row['userid'] . "</b></p><p>Temporary Password: <b>" . $newPassword . "</b></p><br><p>To access MY SKATING SPACE:</p><p>%url%/app/index.html#!/ccwelcomeview</p>";
				} else {
					$title = "Bienvenue sur MON ESPACE PATIN";
					$body = "<p>Votre compte pour accéder à MON ESPACE PATIN a été créé.</p><p>Usager: <b>" . $row['userid'] . "</b></p><p>Mot de passe temporaire: <b>" . $newPassword . "</b></p><br><p>Pour accéder à MON ESPACE PATIN :</p><p>%url%/#!/ccwelcomeview</p>";
				}
				// Send email
				sendoneemail($mysqli, $row['email'], $row['fullname'], $title, $body, '../../../../images', null, $language);
				$data['success'] = true;
			}
		}
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
 * This function validates that the given email address or user code is in the database ONLY ONCE
 */
function validateuseroremailinternal($mysqli, $emailorusercode)
{
	$ret = false;
	$query = "SELECT count(*) cnt
						FROM cpa_users
						WHERE userid = '$emailorusercode'
						OR email = '$emailorusercode'
						AND active = 1";
	$result = $mysqli->query($query);
	$row = $result->fetch_assoc();
	if ($row['cnt'] == 1) {		// We accept one and only one user for this to be true.
		$ret = true;
	}
	return $ret;
};

/**
 * This function validates that the given email address or user code is in the database
 */
function validateuseroremail($mysqli, $emailorusercode)
{
	try {
		$data = array();
		$data['success'] = validateuseroremailinternal($mysqli, $emailorusercode);
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
 * This function updates the password in the database
 */
function changepassword($mysqli, $userid, $oldPassword, $newPassword)
{
	try {
		$data = array();
		$data['success'] = false;
		$query = "SELECT count(*) cnt
							FROM cpa_users
							WHERE userid = '$userid'
							AND password = '$oldPassword'
							AND active = 1";
		$result = $mysqli->query($query);
		$row = $result->fetch_assoc();
		$data['count'] = $row['cnt'];

		if ($row['cnt'] > 0) {
			$query = "UPDATE cpa_users
								SET password = '$newPassword', passwordexpired = 0
								WHERE userid = '$userid'";
			if ($mysqli->query($query)) {
				$data['success'] = true;
			}
		}
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
 * This function unsets the variables and destroys the session
 */
function logout()
{
	try {
		$data = array();
		session_unset();
		session_destroy();
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

function invalidRequest()
{
	$data = array();
	$data['success'] = false;
	$data['message'] = "Invalid request.";
	echo json_encode($data);
	exit;
};
