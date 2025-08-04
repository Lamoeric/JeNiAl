<?php
/*
Author : Eric Lamoureux
*/
require_once('../../../private/'. $_SERVER['HTTP_HOST'].'/include/config.php');
require_once('../../include/nocache.php');

ini_set('display_errors', 'On');
error_reporting(E_ALL);


if( isset($_POST['type']) && !empty( isset($_POST['type']) ) ){
	$type = $_POST['type'];

	switch ($type) {
		case "updateEntireCodes":
			updateEntireCodes($mysqli, $_POST['codes']);
			break;
//		case "save_code":
//			save_code($mysqli);
//			break;
//		case "insert_code":
//			insert_code($mysqli);
//			break;
//		case "delete_code":
//			delete_code($mysqli, $_POST['code']);
//			break;
		case "getAllCodeGroups":
			getAllCodeGroups($mysqli);
			break;
		case "getCodeGroupDetails":
			getCodeGroupDetails($mysqli, $_POST['ctname']);
			break;
		default:
			invalidRequest();
	}
} else {
	invalidRequest();
};

function updateEntireCodes($mysqli, $codes){
	try{
		$data = array();
		for($x = 0; $x < count($codes); $x++) {
			$ctname = 			$mysqli->real_escape_string(isset($codes[$x]['ctname'] ) 			? $codes[$x]['ctname'] : '');
			$code = 				$mysqli->real_escape_string(isset($codes[$x]['code'] ) 				? $codes[$x]['code'] : '');
			$active = 			$mysqli->real_escape_string(isset($codes[$x]['active'] ) 			? (int)$codes[$x]['active'] : '');
			$description = 	$mysqli->real_escape_string(isset($codes[$x]['description'] )	? (int)$codes[$x]['description'] : '');
			$label_fr = 		$mysqli->real_escape_string(isset($codes[$x]['label_fr'] ) 		? $codes[$x]['label_fr'] : '');
			$label_en = 		$mysqli->real_escape_string(isset($codes[$x]['label_en'] ) 		? $codes[$x]['label_en'] : '');
			$sequence = 		$mysqli->real_escape_string(isset($codes[$x]['sequence'] ) 		? (int)$codes[$x]['sequence'] : '');

			if ($mysqli->real_escape_string(isset($codes[$x]['status'])) and $codes[$x]['status'] == 'New') {
				$query = "INSERT into cpa_codetable (ctname, code, active, sequence, description)
									VALUES ('$ctname', '$code', '$active', '$sequence', create_systemText('$label_en', '$label_fr'))";
				if( $mysqli->query( $query ) ){
				} else {
					throw new Exception( $mysqli->sqlstate.' - '. $mysqli->error );
				}
			}

			if ($mysqli->real_escape_string(isset($codes[$x]['status'])) and $codes[$x]['status'] == 'Modified') {
				$query = "UPDATE cpa_codetable set active = $active, sequence = $sequence where ctname = '$ctname' and code = '$code'";

				if( $mysqli->query( $query ) ){
					$mysqli->query("call update_text($description, '$label_en', '$label_fr')");
					$data['success'] = true;
				} else {
					throw new Exception( $mysqli->sqlstate.' - '. $mysqli->error );
				}
			}

			if ($mysqli->real_escape_string(isset($codes[$x]['status'])) and $codes[$x]['status'] == 'Deleted') {
				$query = "DELETE FROM cpa_text WHERE id = '$description'";
				if($mysqli->query( $query )){
					$query2 = "DELETE FROM cpa_codetable WHERE ctname = '$ctname' and code = '$code'";
					if($mysqli->query( $query2 )){
						$data['success'] = true;
					}
				} else {
					throw new Exception( $mysqli->sqlstate.' - '. $mysqli->error );
				}
			}
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
 * This function will handle the update of an existing code in DB
 * @throws Exception
 */
function save_code($mysqli){
	try{
		$data = array();
		$ctname = 			$mysqli->real_escape_string(isset( $_POST['code']['ctname'] ) 			? $_POST['code']['ctname'] : '');
		$code = 				$mysqli->real_escape_string(isset( $_POST['code']['code'] ) 				? $_POST['code']['code'] : '');
		$active = 			$mysqli->real_escape_string(isset( $_POST['code']['active'] ) 			? $_POST['code']['active'] : '');
		$description = 	$mysqli->real_escape_string(isset( $_POST['code']['description'] )	? $_POST['code']['description'] : '');
		$description = (int)$description;
		$label_fr = 		$mysqli->real_escape_string(isset( $_POST['code']['label_fr'] ) 		? $_POST['code']['label_fr'] : '');
		$label_en = 		$mysqli->real_escape_string(isset( $_POST['code']['label_en'] ) 		? $_POST['code']['label_en'] : '');

		$query = "UPDATE cpa_codetable set active = $active";

		if( $mysqli->query( $query ) ){
			$mysqli->query("call update_text($description, '$label_en', '$label_fr')");
			$data['success'] = true;
		} else {
			throw new Exception( $mysqli->sqlstate.' - '. $mysqli->error );
		}
		$mysqli->close();
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
 * This function will handle insertion of a new code in DB
 * @throws Exception
 */
function insert_code($mysqli){
	try{
		$data = array();
		$ctname = 			$mysqli->real_escape_string(isset( $_POST['code']['ctname'] ) 			? $_POST['code']['ctname'] : '');
		$code = 				$mysqli->real_escape_string(isset( $_POST['code']['code'] ) 				? $_POST['code']['code'] : '');
		$active = 			$mysqli->real_escape_string(isset( $_POST['code']['active'] ) 			? $_POST['code']['active'] : '');
		$description = 	$mysqli->real_escape_string(isset( $_POST['code']['description'] )	? $_POST['code']['description'] : '');

		$label_fr = 		$mysqli->real_escape_string(isset( $_POST['code']['label_fr'] ) 		? $_POST['code']['label_fr'] : '');
		$label_en = 		$mysqli->real_escape_string(isset( $_POST['code']['label_en'] ) 		? $_POST['code']['label_en'] : '');

		$query = "INSERT into cpa_codetable (ctname, code, active, description) VALUES ('$ctname', '$code', '$active', create_systemText('$label_en', '$label_fr'))";

		if( $mysqli->query( $query ) ){
			$data['success'] = true;
			$query = "SELECT description FROM cpa_codetable cd WHERE ctname = '$ctname' and code = '$code'";
			$result = $mysqli->query( $query );
			$row = $result->fetch_assoc();
			$data['description'] = $row['description'];
//			if(empty($description))$data['description'] = $mysqli->insert_id;
		} else {
			throw new Exception( $mysqli->sqlstate.' - '. $mysqli->error );
		}
		$mysqli->close();
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
 * This function will handle code deletion
 * @param string $id
 * @throws Exception
 */
function delete_code($mysqli, $code = ''){
	try{
		if(empty($code)) throw new Exception( "Invalid code." );
		$ctname = 			$mysqli->real_escape_string(isset( $_POST['code']['ctname'] ) 			? $_POST['code']['ctname'] : '');
		$codename = 		$mysqli->real_escape_string(isset( $_POST['code']['code'] ) 				? $_POST['code']['code'] : '');
		$active = 			$mysqli->real_escape_string(isset( $_POST['code']['active'] ) 			? $_POST['code']['active'] : '');
		$description = 	$mysqli->real_escape_string(isset( $_POST['code']['description'] )	? $_POST['code']['description'] : '');

		$query = "DELETE FROM cpa_text WHERE id = '$description'";
		if($mysqli->query( $query )){
			$query2 = "DELETE FROM cpa_codetable WHERE ctname = '$ctname' and code = '$codename'";
			if($mysqli->query( $query2 )){
				$data['success'] = true;
				$data['message'] = 'Code deleted successfully.';
				echo json_encode($data);
				exit;
			}
		} else {
			throw new Exception( $mysqli->sqlstate.' - '. $mysqli->error );
		}
	}catch (Exception $e){
		$data = array();
		$data['success'] = false;
		$data['message'] = $e->getMessage();
		echo json_encode($data);
		exit;
	}
};

/**
 * This function gets list of all code groups from database
 */
function getAllCodeGroups($mysqli){
	try{
		$query = "SELECT distinct(ctname) FROM cpa_codetable order by ctname";
		$result = $mysqli->query( $query );
		$data = array();
		while ($row = $result->fetch_assoc()) {
			$data['data'][] = $row;
		}
		$data['success'] = true;
		echo json_encode($data);exit;
	}catch (Exception $e){
		$data = array();
		$data['success'] = false;
		$data['message'] = $e->getMessage();
		echo json_encode($data);
		exit;
	}
};

/**
 * This function gets the details of one code group from database
 */
function getCodeGroupDetails($mysqli, $ctname){
	try{
		$query = "SELECT ctname, code, active, description, sequence, 'Old' status, getEnglishTextLabel(description) as label_en, getFrenchTextLabel(description) as label_fr
						 FROM cpa_codetable cd
						 WHERE ctname = '$ctname'
						 ORDER BY sequence";
		$result = $mysqli->query( $query );
		$data = array();
		while ($row = $result->fetch_assoc()) {
			$data['data'][] = $row;
		}
		$data['success'] = true;
		echo json_encode($data);exit;
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
