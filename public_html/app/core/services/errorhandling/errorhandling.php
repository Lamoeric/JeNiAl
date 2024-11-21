<?php
/*
Author : Eric Lamoureux
*/
require_once('../../../../../private/' . $_SERVER['HTTP_HOST'] . '/include/config.php');
require_once('../../../../backend/invalidrequest.php'); //

if (isset($_POST['type']) && !empty(isset($_POST['type']))) {
	$type = $_POST['type'];

	switch ($type) {
		case "insertLog":
			insertLog($mysqli, $_POST['user'], $_POST['progname'], $_POST['message'], $_POST['stack'], $_POST['cause']);
			break;
		default:
			invalidRequest($type);
	}
} else {
	invalidRequest();
};

/**
 * This function inserts an error log
 */
function insertLog($mysqli, $user, $progname, $message, $stack, $cause) {
	try {
		$data = array();
		$user = isset($user) ? $user : '';
		$progname = isset($progname) ? $progname : '';
		$message = isset($message) ? $mysqli->real_escape_string($message) : '';
		$stack = isset($stack) ? $mysqli->real_escape_string($stack) : '';
		$cause = isset($cause) ? $mysqli->real_escape_string($cause) : '';

		$query = "INSERT INTO cpa_logs (id, user, progname, `message`, stack, cause) 
				  VALUES (NULL, '$user', '$progname', '$message', '$stack', '$cause')";
		if (!$mysqli->query($query)) {
			throw new Exception('insertLog - ' . $mysqli->sqlstate . ' - ' . $mysqli->error);
		}
		$data['success'] = true;
		echo json_encode($data);
		exit;
	} catch (Exception $e) {
		$data['success'] = false;
		$data['message'] = $e->getMessage();
		echo json_encode($data);
		exit;
	}
};
