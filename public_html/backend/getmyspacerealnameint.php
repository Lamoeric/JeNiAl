<?php
/*
* Author : Eric Lamoureux
*
* File contains function to get the "My Skating Space" real name
*
*/
function getMySpaceRealName($mysqli, $language) {
	$data = array();
	$data['data'] = array();
	$query = "SELECT getTextLabel(spacename, '$language') myspacerealname FROM cpa_configuration";
	$result = $mysqli->query($query);
	$row = $result->fetch_assoc();
    $data['data'][] = $row;
	$data['success'] = true;
	return $data;
};

?>