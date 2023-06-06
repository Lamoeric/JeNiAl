<?php
/*
* Author : Eric Lamoureux
*
* File contains function to get the rules for the active session
*
*/
require_once('../../../private/'. $_SERVER['HTTP_HOST'].'/include/config.php');

getActiveSessionRules($mysqli, $_POST['language']);

/**
 * This function gets the rules for the active session
 * @throws Exception
 */
function getActiveSessionRules($mysqli, $language){
	try{
		$data = array();
		$query = "SELECT rules
							FROM cpa_sessions_rules csr
              JOIN cpa_sessions cs ON cs.id = csr.sessionid
							WHERE cs.active = 1
							AND language = '$language'";
		$result = $mysqli->query( $query );
		$row = $result->fetch_assoc();
		header("Content-type: text/text;charset=iso-8859-1");
    echo $row['rules'];
		exit;
	}catch (Exception $e){
		$data = array();
		$data['success'] = false;
		$data['message'] = $e->getMessage();
		echo json_encode($data);
		exit;
	}
};
