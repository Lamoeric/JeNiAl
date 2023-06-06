<?php
/*
Author : Eric Lamoureux
*/
require_once('../../../private/'. $_SERVER['HTTP_HOST'].'/include/config.php');
require_once('../../include/nocache.php');

if( isset($_POST['type']) && !empty( isset($_POST['type']) ) ){
	$type = $_POST['type'];

	switch ($type) {
		case "getSessionDetails":
			getSessionDetails($mysqli, $_POST['sessionid'], $_POST['language']);
			break;
		default:
			invalidRequest();
	}
} else {
	invalidRequest();
};

/**
 * This function gets the details of all events for a session
 */
function getSessionEvents($mysqli, $sessionid, $language){
	if(empty($sessionid)) throw new Exception( "Invalid session." );
	$query = "SELECT eventdate date, type, getTextLabel(label, '$language') label
						FROM cpa_sessions_dates
						WHERE sessionid = '$sessionid'";
	$result = $mysqli->query( $query );
	$data = array();
	$data['data'] = array();
	while ($row = $result->fetch_assoc()) {
		$data['data'][] = $row;
	}
	$data['success'] = true;
	return $data;
};

/**
 * This function gets the details of all registrations for a session
 */
function getSessionRegistrations($mysqli, $sessionid, $language){
	if(empty($sessionid)) throw new Exception( "Invalid session." );
	$query = "SELECT registrationdate date, 'REGISTRATION' type, concat(location, ' ', starttime, ' - ', endtime) label
						FROM cpa_sessions_registrations
						WHERE sessionid = '$sessionid'
						ORDER BY registrationdate, starttime";
	$result = $mysqli->query( $query );
	$data = array();
	$data['data'] = array();
	while ($row = $result->fetch_assoc()) {
		$data['data'][] = $row;
	}
	$data['success'] = true;
	return $data;
};

/**
 * This function gets the details of one session from database
 */
function getSessionDetails($mysqli, $sessionid, $language){
	try{
		if ($sessionid && !empty($sessionid)) {
			$query = "SELECT *, getTextLabel(label, '$language') as label_txt
								FROM cpa_sessions
								WHERE id = $sessionid";
		} else {
			$query = "SELECT *, getTextLabel(label, '$language') as label_txt
								FROM cpa_sessions
								WHERE active = 1";
		}
		$result = $mysqli->query( $query );
		$data = array();
		while ($row = $result->fetch_assoc()) {
			$row['registrations'] 	= getSessionRegistrations($mysqli, $row['id'], $language)['data'];
			$row['events'] 	= getSessionEvents($mysqli, $row['id'], $language)['data'];
			$data['data'][] = $row;
		}
		$data['success'] = true;
		echo json_encode($data);
		exit;
	}catch (Exception $e){
		$data = array();
		$data['success'] = false;
		$data['message'] = $e->getMessage();
		echo json_encode($data);
		exit;
	}
};

function invalidRequest(){
	$data = array();
	$data['success'] = false;
	$data['message'] = "Invalid request.";
	echo json_encode($data);
	exit;
};

?>
