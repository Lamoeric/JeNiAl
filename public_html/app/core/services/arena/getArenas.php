<?php
/*
Author : Eric Lamoureux
*/
require_once('../../../../../private/'. $_SERVER['HTTP_HOST'].'/include/config.php');
require_once('../../../../include/nocache.php');

if( isset($_POST['type']) && !empty( isset($_POST['type']) ) ){
	$type = $_POST['type'];
	
	switch ($type) {
		case "getArenasDetails":
			getArenasDetails($mysqli, $_POST['language']);
			break;
		default:
			invalidRequest();
	}
} else {
	invalidRequest();
};

/**
 * This function gets the details of all ices for an arena from database
 */
function getArenaIces($mysqli, $arenaid, $language){
	try{
		$query = "SELECT id, getTextLabel(label, '$language') label FROM cpa_arenas_ices WHERE arenaid = '$arenaid'";
		$result = $mysqli->query( $query );
		$data = array();
		$data['data'] = array();
		while ($row = $result->fetch_assoc()) {
			$data['data'][] = $row;
		}
		$data['success'] = true;
		return $data;
		exit;
	}catch (Exception $e){
		$data = array();
		$data['success'] = false;
		$data['message'] = $e->getMessage();
		return $data;
		exit;
	}
};
	
/**
 * This function gets the details of one arena from database
 */
function getArenasDetails($mysqli, $language){
	try{
		$query = "SELECT id, getTextLabel(label, '$language') label FROM cpa_arenas ORDER BY name";
		$result = $mysqli->query( $query );
		$data = array();
		while ($row = $result->fetch_assoc()) {
			$id = $row['id'];
			$row['ices'] = getArenaIces($mysqli, $id, $language)['data'];
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

?>