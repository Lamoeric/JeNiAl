<?php
/*
Author : Eric Lamoureux
*/

/**
 * This function simply returns an array indicating there was an invalid request
 */
function invalidRequest() {
	$data = array();
	$data['success'] = false;
	$data['message'] = "Invalid request.";
	echo json_encode($data);
	exit;
};
?>
