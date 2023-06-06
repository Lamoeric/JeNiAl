<?php
/*
Author : Eric Lamoureux
*/
require_once('../../../private/'. $_SERVER['HTTP_HOST'].'/include/config.php');
require_once('../../include/nocache.php');

if( isset($_POST['type']) && !empty( isset($_POST['type']) ) ){
	$type = $_POST['type'];
	
	switch ($type) {
		case "insert_test":
			insert_test($mysqli);
			break;
		case "updateEntireTest":
			updateEntireTest($mysqli);
			break;
		case "delete_test":
			delete_test($mysqli, $_POST['test']);
			break;
		case "getAllTests":
			getAllTests($mysqli);
			break;
		case "getTestDetails":
			getTestDetails($mysqli, $_POST['id']);
			break;
		default:
			invalidRequest();
	}
} else {
	invalidRequest();
};

function updateEntireTest($mysqli){
	echo json_encode(update_test($mysqli));
	exit;
};

function insert_test($mysqli){
	echo json_encode(update_test($mysqli));
	exit;
}

/**
 * This function will handle test update functionality
 * @throws Exception
 */
function update_test($mysqli){
	try{
		$data = array();
		$id = 								$mysqli->real_escape_string(isset( $_POST['test']['id'] ) 									? $_POST['test']['id'] : '');
		$canskateid = 				$mysqli->real_escape_string(isset( $_POST['test']['canskateid'] ) 					? $_POST['test']['canskateid'] : '');
		$type = 							$mysqli->real_escape_string(isset( $_POST['test']['type'] ) 								? $_POST['test']['type'] : '');
		$sequence = 					$mysqli->real_escape_string(isset( $_POST['test']['sequence'] ) 						? $_POST['test']['sequence'] : '');
		$name = 							$mysqli->real_escape_string(isset( $_POST['test']['name'] )									? $_POST['test']['name'] : '');
		$label = 							$mysqli->real_escape_string(isset( $_POST['test']['label'] ) 								? $_POST['test']['label'] : '');
		$label_fr = 					$mysqli->real_escape_string(isset( $_POST['test']['label_fr'] ) 						? $_POST['test']['label_fr'] : '');
		$label_en = 					$mysqli->real_escape_string(isset( $_POST['test']['label_en'] ) 						? $_POST['test']['label_en'] : '');
	
		if(empty($id)){
			$data['insert'] = true;
			$query = "INSERT INTO cpa_canskate_tests (id, canskateid, type, sequence, name, label) 
								VALUES (NULL, '$canskateid', '$type', '$sequence', '$name', create_systemText('$label_en', '$label_fr'))";
			if( $mysqli->query( $query ) ){
				$data['success'] = true;
				if(!empty($id))$data['message'] = 'Test updated successfully.';
				else $data['message'] = 'Test inserted successfully.';
				if(empty($id))$data['id'] = (int) $mysqli->insert_id;
				else $data['id'] = (int) $id;
			} else {
				throw new Exception( $mysqli->sqlstate.' - '. $mysqli->error );
			}
		} else {
			$query = "UPDATE cpa_canskate_tests 
								SET canskateid = '$canskateid', type = '$type', sequence = '$sequence', name = '$name', sequence = '$sequence' 
								WHERE id = $id";
			if( $mysqli->query( $query ) ){
				$query = "UPDATE cpa_text set text = '$label_fr' where id = $label and language = 'fr-ca'";
				if( $mysqli->query( $query ) ){
					$data['success'] = true;
					$query = "UPDATE cpa_text set text = '$label_en' where id = $label and language = 'en-ca'";
					if( $mysqli->query( $query ) ){
						$data['success'] = true;
					} else {
						throw new Exception( $mysqli->sqlstate.' - '. $mysqli->error );
					}
				} else {
					throw new Exception( $mysqli->sqlstate.' - '. $mysqli->error );
				}
			} else {
				throw new Exception( $mysqli->sqlstate.' - '. $mysqli->error );
			}
		}
		return $data;
	}catch (Exception $e){
		$data = array();
		$data['success'] = false;
		$data['message'] = $e->getMessage();
		return $data;
	}
};

/**
 * This function will handle test deletion
 * @param string $test
 * @throws Exception
 */
function delete_test($mysqli, $test){
	try{
		$id 		= $mysqli->real_escape_string(isset( $test['id'] ) 				? $test['id'] : '');
		$label 	= $mysqli->real_escape_string(isset( $test['label'] ) 		? $test['label'] : '');

		if(empty($id)) throw new Exception( "Invalid Test." );
		$query = "DELETE FROM cpa_text WHERE id = '$label'";
		if($mysqli->query( $query )){
			$query = "DELETE FROM cpa_canskate_tests WHERE id = '$id'";
			if($mysqli->query( $query )){
				$data['success'] = true;
				$data['message'] = 'Test deleted successfully.';
				echo json_encode($data);
				exit;
			} else {
				throw new Exception( $mysqli->sqlstate.' - '. $mysqli->error );
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
 * This function gets list of all tests from database
 */
function getAllTests($mysqli){
	try{
		$query = "SELECT ccst.id, ccst.canskateid, ccst.type, ccst.name, ccst.sequence, ccs.category, ccs.stage
						 	FROM cpa_canskate_tests ccst
						 	JOIN cpa_canskate ccs ON ccs.id = ccst.canskateid
						 	order by canskateid, sequence";
		$result = $mysqli->query( $query );
		$data = array();
		$data['data'] = array();
		while ($row = $result->fetch_assoc()) {
			$row['id'] = (int) $row['id'];
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
 * This function gets the details of one test from database
 */
function getTestDetails($mysqli, $id = ''){
	try{
		if(empty($id)) throw new Exception( "Invalid User." );
		$query = "SELECT *, getEnglishTextLabel(label) as label_en, getFrenchTextLabel(label) as label_fr FROM cpa_canskate_tests WHERE id = '$id'";
		$result = $mysqli->query( $query );
		$data = array();
		while ($row = $result->fetch_assoc()) {
			$row['id'] = (int) $row['id'];
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