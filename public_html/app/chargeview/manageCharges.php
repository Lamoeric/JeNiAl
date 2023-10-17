<?php
/*
Author : Eric Lamoureux
*/
require_once('../../../private/'. $_SERVER['HTTP_HOST'].'/include/config.php');
require_once('../../include/nocache.php');

if(isset($_POST['type']) && !empty(isset($_POST['type']) ) ){
	$type = $_POST['type'];

	switch ($type) {
		case "insert_charge":
			insert_charge($mysqli);
			break;
		case "updateEntireCharge":
			updateEntireCharge($mysqli, $_POST['charge']);
			break;
		case "delete_charge":
			delete_charge($mysqli, $_POST['charge']);
			break;
		case "getAllCharges":
			getAllCharges($mysqli, $_POST['language']);
			break;
		case "getChargeDetails":
			getChargeDetails($mysqli, $_POST['id'], $_POST['language']);
			break;
		default:
			invalidRequest();
	}
} else {
	invalidRequest();
};

/**
 * This function will handle insertion of a new icetime in DB
 * @throws Exception
 */
function updateEntireRules($mysqli, $chargecode, $rules) {
	$data = array();
	for($x = 0; $rules && $x < count($rules); $x++) {
		$id = 				$mysqli->real_escape_string(isset($rules[$x]['id']) 				? $rules[$x]['id'] : '');
		$ruletype = 		$mysqli->real_escape_string(isset($rules[$x]['ruletype']) 			? $rules[$x]['ruletype'] : '');
		$ruleparameters = 	$mysqli->real_escape_string(isset($rules[$x]['ruleparameters']) 	? $rules[$x]['ruleparameters'] : '');

		if ($mysqli->real_escape_string(isset($rules[$x]['status'])) and $rules[$x]['status'] == 'New') {
			$query = "INSERT INTO cpa_charges_rules (chargecode, ruletype, ruleparameters)
					  VALUES ('$chargecode', '$ruletype', '$ruleparameters')";
			if (!$mysqli->query($query)) {
				throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
			}
		}

		if ($mysqli->real_escape_string(isset($rules[$x]['status'])) and $rules[$x]['status'] == 'Modified') {
			$query = "UPDATE cpa_charges_rules
					  SET ruletype = $ruletype, ruleparameters = '$ruleparameters'
					  WHERE id = $id";
			if (!$mysqli->query($query)) {
				throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
			}
		}

		if ($mysqli->real_escape_string(isset($rules[$x]['status'])) and $rules[$x]['status'] == 'Deleted') {
			$query = "DELETE FROM cpa_charges_rules WHERE id = $id";
			if (!$mysqli->query($query)) {
				throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
			}
		}
	}
};

function updateEntireCharge($mysqli, $charge){
	try{
		$data = array();
		$id = 		  $mysqli->real_escape_string(isset($charge['id'] ) 	? $charge['id'] : '');
		$chargecode = $mysqli->real_escape_string(isset($charge['code'] )	? $charge['code'] : '');

		update_charge($mysqli);
		updateEntireRules($mysqli, $chargecode, isset($charge['rules']) ? $charge['rules'] : null);
		$mysqli->close();

		$data['success'] = true;
		$data['message'] = 'Charge updated successfully.';
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

function insert_charge($mysqli){
	echo json_encode(update_charge($mysqli));
	exit;
}

/**
 * This function will handle user update functionality
 * @throws Exception
 */
function update_charge($mysqli){
	try{
		$data = array();
		$id = 					$mysqli->real_escape_string(isset($_POST['charge']['id'] ) 						? $_POST['charge']['id'] : '');
		$code = 				$mysqli->real_escape_string(isset($_POST['charge']['code'] ) 					? $_POST['charge']['code'] : '');
		$type = 				$mysqli->real_escape_string(isset($_POST['charge']['type'] ) 					? $_POST['charge']['type'] : '');
		$amount = 				$mysqli->real_escape_string(isset($_POST['charge']['amount'] ) 					?(float) $_POST['charge']['amount'] : 0);
		$label = 				$mysqli->real_escape_string(isset($_POST['charge']['label'] ) 					? $_POST['charge']['label'] : '');
		$label_fr = 			$mysqli->real_escape_string(isset($_POST['charge']['label_fr'] ) 				? $_POST['charge']['label_fr'] : '');
		$label_en = 			$mysqli->real_escape_string(isset($_POST['charge']['label_en'] ) 				? $_POST['charge']['label_en'] : '');
		$alwaysdisplay = 		$mysqli->real_escape_string(isset($_POST['charge']['alwaysdisplay'] ) 			? $_POST['charge']['alwaysdisplay'] : 0);
		$alwaysselected = 		$mysqli->real_escape_string(isset($_POST['charge']['alwaysselected'] )			? $_POST['charge']['alwaysselected'] : 0);
		$alwaysselectedonline =	$mysqli->real_escape_string(isset($_POST['charge']['alwaysselectedonline'] )	? $_POST['charge']['alwaysselectedonline'] : 0);
		$nonrefundable = 		$mysqli->real_escape_string(isset($_POST['charge']['nonrefundable'] ) 			? $_POST['charge']['nonrefundable'] : 0);
		$isonline = 			$mysqli->real_escape_string(isset($_POST['charge']['isonline'] ) 				? $_POST['charge']['isonline'] : 0);
		$active = 				$mysqli->real_escape_string(isset($_POST['charge']['active'] ) 					? $_POST['charge']['active'] : 0);

		if(empty($id)){
			$data['insert'] = true;
			$query = "INSERT INTO cpa_charges (id, code, type, amount, label) 
					  VALUES (NULL, '$code', '$type', $amount, create_systemText('$label_en', '$label_fr'))";
			if($mysqli->query($query ) ){
				$data['success'] = true;
				if(!empty($id))$data['message'] = 'Charge updated successfully.';
				else $data['message'] = 'Charge inserted successfully.';
				if(empty($id))$data['id'] = (int) $mysqli->insert_id;
				else $data['id'] = (int) $id;
			} else {
				throw new Exception($mysqli->sqlstate.' - '. $mysqli->error );
			}
		} else {
			$query = "UPDATE cpa_charges 
			          SET code = '$code', type = '$type', amount = '$amount', alwaysdisplay = $alwaysdisplay, alwaysselected = $alwaysselected, 
					  alwaysselectedonline = $alwaysselectedonline, nonrefundable = $nonrefundable, isonline = $isonline, active = $active
					  WHERE id = $id";
			if($mysqli->query($query ) ){
				$query = "UPDATE cpa_text set text = '$label_fr' where id = $label and language = 'fr-ca'";
				if($mysqli->query($query ) ){
					$data['success'] = true;
					$query = "UPDATE cpa_text set text = '$label_en' where id = $label and language = 'en-ca'";
					if($mysqli->query($query ) ){
						$data['success'] = true;
					} else {
						throw new Exception($mysqli->sqlstate.' - '. $mysqli->error );
					}
				} else {
					throw new Exception($mysqli->sqlstate.' - '. $mysqli->error );
				}
			} else {
				throw new Exception($mysqli->sqlstate.' - '. $mysqli->error );
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
 * This function will handle charge deletion
 * @param string $id
 * @param object $charge
 * @throws Exception
 */
function delete_charge($mysqli, $charge = ''){
	try{
		$label 	= $mysqli->real_escape_string(isset($charge['label'] )	? (int)$charge['label'] : '');
		$id 	= $mysqli->real_escape_string(isset($charge['id'] ) 	? (int)$charge['id'] : '');

		if(empty($id)) throw new Exception("Invalid Charge." );
		$query = "DELETE FROM cpa_charges WHERE id = $id";
		if($mysqli->query($query )){
			$query = "DELETE FROM cpa_text WHERE id = $label";
			if($mysqli->query($query )){
				$data['success'] = true;
				$data['message'] = 'Charge deleted successfully.';
				echo json_encode($data);
				exit;
			} else {
				throw new Exception($mysqli->sqlstate.' - '. $mysqli->error );
			}
		} else {
			throw new Exception($mysqli->sqlstate.' - '. $mysqli->error );
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
 * This function gets list of all charges (except system charges) from database
 */
function getAllCharges($mysqli, $language){
	try{
		$query = "SELECT id, code, getCodeDescription('chargetypes', type, '$language') type, amount, getTextLabel(label, '$language') as label
				  FROM cpa_charges
				  WHERE issystem = 0
				  ORDER BY code";
		$result = $mysqli->query($query );
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
 * This function gets the rules of one charge from database
 */
function getChargeRules($mysqli, $chargecode, $language){
	$query = "SELECT ccr.*, getCodeDescription('ruletypes', ruletype, '$language') ruletypelabel
			  FROM cpa_charges_rules ccr
			  WHERE ccr.chargecode = '$chargecode'";
	$result = $mysqli->query($query );
	$data = array();
	$data['data'] = array();
	while ($row = $result->fetch_assoc()) {
		$data['data'][] = $row;
	}
	$data['success'] = true;
	return $data;
	exit;
};

/**
 * This function gets the details of one charge from database
 */
function getChargeDetails($mysqli, $id, $language){
	try{
		if(empty($id)) throw new Exception("Invalid User." );
		$query = "SELECT *, getEnglishTextLabel(label) as label_en, getFrenchTextLabel(label) as label_fr,
						 (SELECT COUNT(*) FROM cpa_sessions_charges csc WHERE csc.chargecode = cc.code) +
						 (SELECT COUNT(*) FROM cpa_newtests_sessions_charges cnsc WHERE cnsc.chargecode = cc.code) +
						 (SELECT COUNT(*) FROM cpa_tests_sessions_charges ctsc WHERE ctsc.chargecode = cc.code) as isused
				  FROM cpa_charges cc
				  WHERE id = $id AND issystem = 0";
		$result = $mysqli->query($query );
		$data = array();
		while ($row = $result->fetch_assoc()) {
			$row['id'] = (int) $row['id'];
			$code = $row['code'];
			$row['rules'] = getChargeRules($mysqli, $code, $language)['data'];
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
