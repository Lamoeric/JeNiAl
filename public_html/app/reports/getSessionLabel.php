<?php
/**
 * This function gets the label of the session
 */
function getSessionLabel($mysqli, $sessionid, $language){
	try{
		if(!empty($sessionid)) {
			$query = "SELECT getTextLabel(label, '$language') sessionlabel
								FROM cpa_sessions
								WHERE id = $sessionid";
		} else {
			$query = "SELECT getTextLabel(label, '$language') sessionlabel
								FROM cpa_sessions
								WHERE active = 1";
		}
		$result = $mysqli->query( $query );
		$data = array();
		$data['data'] = array();
		while ($row = $result->fetch_assoc()) {
			$data['data'][] = $row;
		}
		$data['success'] = true;
		return $data;
	}catch (Exception $e){
		$data = array();
		$data['success'] = false;
		$data['message'] = $e->getMessage();
		return $data;
	}
};
