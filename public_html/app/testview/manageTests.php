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
			getAllTests($mysqli, $_POST['language']);
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
	try{
		$data = update_test($mysqli);
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

function insert_test($mysqli){
	echo json_encode(update_test($mysqli));
	exit;
}

/**
 * This function will handle user update functionality
 * @throws Exception
 */
function update_test($mysqli){
	try{
		$data = array();
		$id = 								$mysqli->real_escape_string(isset( $_POST['test']['id'] ) 									? $_POST['test']['id'] : '');
		$testsdefinitionsid = $mysqli->real_escape_string(isset( $_POST['test']['testsdefinitionsid'] ) 	? $_POST['test']['testsdefinitionsid'] : '');
		$subsubtype = 				$mysqli->real_escape_string(isset( $_POST['test']['subsubtype'] ) 					? $_POST['test']['subsubtype'] : '');
		$name = 							$mysqli->real_escape_string(isset( $_POST['test']['name'] )									? $_POST['test']['name'] : '');
		$sequence = 					$mysqli->real_escape_string(isset( $_POST['test']['sequence'] ) 						? $_POST['test']['sequence'] : '');
		$equivalence = 				$mysqli->real_escape_string(isset( $_POST['test']['equivalence'] ) 					? $_POST['test']['equivalence'] : '');
		$label = 							$mysqli->real_escape_string(isset( $_POST['test']['label'] ) 								? $_POST['test']['label'] : '');
		$label_fr = 					$mysqli->real_escape_string(isset( $_POST['test']['label_fr'] ) 						? $_POST['test']['label_fr'] : '');
		$label_en = 					$mysqli->real_escape_string(isset( $_POST['test']['label_en'] ) 						? $_POST['test']['label_en'] : '');
		$summarycode = 				$mysqli->real_escape_string(isset( $_POST['test']['summarycode'] ) 					? $_POST['test']['summarycode'] : '');
		$partnerstepscode = 	$mysqli->real_escape_string(isset( $_POST['test']['partnerstepscode'] ) 		? $_POST['test']['partnerstepscode'] : '');

		if(empty($id)){
			$data['insert'] = true;
			$query = "INSERT INTO cpa_tests (id, testsdefinitionsid, subsubtype, name, label)
								VALUES (NULL, '$testsdefinitionsid', '$subsubtype', '$name', create_systemText('$label_en', '$label_fr'))";
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
			$query = "UPDATE cpa_tests SET testsdefinitionsid = '$testsdefinitionsid', subsubtype = '$subsubtype', name = '$name',
								sequence = '$sequence', equivalence = '$equivalence', summarycode = '$summarycode', partnerstepscode = '$partnerstepscode'
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
function delete_test($mysqli, $test = ''){
	try{
		$label 	= $mysqli->real_escape_string(isset( $test['label'] ) ? $test['label'] : '');
		$id 		= $mysqli->real_escape_string(isset( $test['id'] ) 		? $test['id'] : '');

		if(empty($id)) throw new Exception( "Invalid test." );
		$query = "DELETE FROM cpa_text WHERE id = '$label'";
		if($mysqli->query( $query )){
			$query = "DELETE FROM cpa_tests WHERE id = '$id'";
			if($mysqli->query( $query )){
				$data['success'] = true;
				$data['message'] = 'User deleted successfully.';
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
function getAllTests($mysqli, $language){
	try{
		$query = "SELECT ct.id, ct.name, concat(getCodeDescription('testtypes', ctd.type, '$language'), '/',
															  getCodeDescription('testlevels', ctd.level, '$language'),
       				                  if(ctd.subtype != '', concat('/',  getCodeDescription('testsubtypes', ctd.subtype, '$language')), '')) description,
                     ctd.type, ctd.subtype, ctd.level, ctd.version, ct.subsubtype, ct.sequence
							FROM cpa_tests ct
							JOIN cpa_tests_definitions ctd ON ctd.id = ct.testsdefinitionsid
							WHERE ctd.version = 1
							UNION
							SELECT ct.id, ct.name, concat(getCodeDescription('testtypes', ctd.type, '$language'), '/STAR ', ctd.level) description,
										 ctd.type, ctd.subtype, ctd.level, ctd.version, ct.subsubtype, ct.sequence
							FROM cpa_tests ct
							JOIN cpa_tests_definitions ctd ON ctd.id = ct.testsdefinitionsid
							WHERE ctd.version = 2
							ORDER BY version, type, level, subtype, subsubtype, sequence";
		// $query = "SELECT ct.id, ct.name, concat(getCodeDescription('testtypes', ctd.type, '$language'), '/',
		// 													  getCodeDescription('testlevels', ctd.level, '$language'),
    //    				                  if(ctd.subtype != '', concat('/',  getCodeDescription('testsubtypes', ctd.subtype, '$language')), '')) description
		// 					FROM cpa_tests ct
		// 					JOIN cpa_tests_definitions ctd ON ctd.id = ct.testsdefinitionsid
		// 					-- WHERE ctd.version = 1
		// 					order by ctd.version, ctd.type, ctd.level, ctd.subtype, ct.subsubtype, ct.sequence";
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
 * This function gets the musics of one test from database
 */
function getTestMusics($mysqli, $id = ''){
	try{
		if(empty($id)) throw new Exception( "Invalid Test." );
		$query = "SELECT cm.id, cm.song, cm.author, concat(cm.song, ' - ', cm.author) musiclabel
							FROM cpa_musics cm
							JOIN cpa_tests_musics ctm ON ctm.musicsid = cm.id
							WHERE ctm.testsid = '$id'";
		$result = $mysqli->query( $query );
		$data = array();
		$data['data']= array();
		while ($row = $result->fetch_assoc()) {
			$row['id'] = (int) $row['id'];
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
 * This function gets the details of one test from database
 */
function getTestDetails($mysqli, $id = ''){
	try{
		if(empty($id)) throw new Exception( "Invalid Test." );
		$query = "SELECT *, getEnglishTextLabel(label) as label_en, getFrenchTextLabel(label) as label_fr FROM cpa_tests WHERE id = '$id'";
		$result = $mysqli->query( $query );
		$data = array();
		while ($row = $result->fetch_assoc()) {
			$row['id'] = (int) $row['id'];
			$row['musics'] = getTestMusics($mysqli, $id)['data'];
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
