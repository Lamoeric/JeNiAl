<?php
/*
Author : Eric Lamoureux
*/
require_once('../../../../../private/'. $_SERVER['HTTP_HOST'].'/include/config.php');
require_once('../../../../include/nocache.php');

if( isset($_POST['type']) && !empty( isset($_POST['type']) ) ){
	$type = $_POST['type'];

	switch ($type) {
		case "getAllObjects":
			getAllMembers($mysqli, $_POST['filter']);
			break;
		case "getObjectDetails":
			getMemberDetails($mysqli, $_POST['id'], $_POST['language']);
			break;
		default:
			invalidRequest();
	}
} else {
	invalidRequest();
};

/**
 * This function gets the list of all members from database
 */
function getAllMembers($mysqli, $filter){
	try{
		$whereclause = " where 1=1 ";
		if (!empty($filter['firstname']))	$whereclause .= " and cm.firstname like '" . $filter['firstname'] . "'";
		if (!empty($filter['lastname']))	$whereclause .= " and cm.lastname like '" .  $filter['lastname']  . "'";
		if (!empty($filter['qualification']))	$whereclause .= " and cm.qualifications like BINARY '%" .  $filter['qualification']  . "%'";
		if (!empty($filter['course']))	  $whereclause .= " and cm.id in (select memberid from cpa_sessions_courses_members where sessionscoursesid = '" . $filter['course']  . "' and (registrationenddate is null or registrationenddate > now()))" ;
		if (!empty($filter['registration']) && $filter['registration'] == 'REGISTERED')	  $whereclause .= " and cm.id in (select memberid from cpa_sessions_courses_members where sessionscoursesid in (select id from cpa_sessions_courses where sessionid = (select id from cpa_sessions where active = '1')))" ;
		if (!empty($filter['registration']) && $filter['registration'] == 'NOTREGISTERED')	  $whereclause .= " and cm.id not in (select memberid from cpa_sessions_courses_members where sessionscoursesid in (select id from cpa_sessions_courses where sessionid = (select id from cpa_sessions where active = '1')))" ;
		$query = "SELECT cm.id, cm.firstname, cm.lastname, cm.skatecanadano, 0 as registered
							FROM cpa_members cm ". $whereclause ."
							ORDER BY lastname, firstname";
		$result = $mysqli->query( $query );
		$data = array();
		while ($row = $result->fetch_assoc()) {
			$row['id'] = (int) $row['id'];
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

/**
 * This function gets the details of one member from database
 */
function getMemberDetailsInt($mysqli, $id, $language){
	$data = array();
	$data['data'] = array();
	$query = "SELECT *, getCodeDescription('provinces', province, '$language') provincetext, getCodeDescription('genders', gender, '$language') gendertext
						FROM cpa_members
						WHERE id = $id";
	$result = $mysqli->query( $query );
	while ($row = $result->fetch_assoc()) {
		$row['id'] = (int) $row['id'];
		$data['data'][] = $row;
	}
	$data['success'] = true;
	return $data;
};

/**
 * This function gets the details of one member from database
 */
function getMemberDetails($mysqli, $id, $language){
	echo json_encode(getMemberDetailsInt($mysqli, $id, $language));
	exit;
};

function invalidRequest(){
	$data = array();
	$data['success'] = false;
	$data['message'] = "Invalid request.";
	echo json_encode($data);
	exit;
};

?>
