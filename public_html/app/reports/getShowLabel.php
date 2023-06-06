<?php
/**
 * This function gets the label of the session
 */
function getShowLabel($mysqli, $showid, $language){
	try{
		$query = "SELECT getTextLabel(label, '$language') showlabel
							FROM cpa_shows
							WHERE id = $showid";
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
