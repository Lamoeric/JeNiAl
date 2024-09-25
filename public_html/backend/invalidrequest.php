<?php
/*
Author : Eric Lamoureux
*/

/**
 * This function simply returns an array indicating there was an invalid request
 * 
 * $type	Original $type that was passed nad that clearly is not supported. Could be null.
 * 			Use this information to increase the amount of usefull data we log in the error log
 */
function invalidRequest($type=null) {
	$data = array();
	$data['success'] = false;
	$data['message'] = "Invalid request." . (isset($type) ? " Request was for : " . $type : "");
	echo json_encode($data);
	exit;
};
?>
