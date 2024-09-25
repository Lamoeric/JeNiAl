<?php
/*
Author : Eric Lamoureux
*/

/**
 * This function returns the supported languages of the website
 * 
 * $mysqli          
 * 
 * Returns a $data structure
 */
function getWSSupportedLanguages($mysqli)
{
	try {
		$data = array();
		$query = "	SELECT supportfrench, supportenglish
					FROM cpa_ws_contactinfo";
		$result = $mysqli->query($query);
		$row = $result->fetch_assoc();
		$row['supportfrench'] = isset($row['supportfrench']) ? (int)$row['supportfrench'] : 0;
		$row['supportenglish'] = isset($row['supportenglish']) ? (int)$row['supportenglish'] : 0;
        $data['data'] = $row;
		$data['success'] = true;
		return $data;
	} catch (Exception $e) {
		$data = array();
		$data['success'] = false;
		$data['message'] = $e->getMessage();
		return $data;
	}
};
?>
