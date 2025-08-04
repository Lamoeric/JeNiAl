<?php
/*
Author : Eric Lamoureux
*/
require_once('../../../private/'. $_SERVER['HTTP_HOST'].'/include/config.php');
require_once('../../include/nocache.php');
require_once(__DIR__.'/../../backend/getactivesession.php');
require_once('../../backend/invalidrequest.php');

if (isset($_POST['type']) && !empty(isset($_POST['type']))) {
	$type = $_POST['type'];

	switch ($type) {
		case "readSessionConfig":
			echo json_encode(getActiveSession($mysqli));
			break;
		default:
			invalidRequest();
	}
} else {
	invalidRequest($type);
};

?>
