<?php
/**
 * This function gets the long name and address of the club from the configuration table
 */
function getClubNameAndAddress($mysqli, $language){
	try{
		$query = "SELECT getTextLabel(cpalongname, '$language') cpalongname, getTextLabel(cpaaddress, '$language') cpaaddress
							FROM cpa_configuration
							WHERE id = 1";
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
