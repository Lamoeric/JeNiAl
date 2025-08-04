<?php
/*
* Author : Eric Lamoureux
*
* File contains function to get the "My Skating Space" real name
*
*/
require_once(__DIR__ .'/../../private/'. $_SERVER['HTTP_HOST'].'/include/config.php');
require_once(__DIR__ .'/../include/nocache.php');
require_once(__DIR__ .'/invalidrequest.php');
require_once(__DIR__ .'/getmyspacerealnameint.php');

if (isset($_POST['type']) && !empty(isset($_POST['type']))) {
	$type = $_POST['type'];

	switch ($type) {
		case "getMySpaceRealName":
			echo json_encode(getMySpaceRealName($mysqli, $_POST['language']));
			break;
		default:
			invalidRequest();
	}
} else {
	invalidRequest($type);
};



// echo json_encode(getMySpaceRealName($mysqli, $_POST['language']));
?>
