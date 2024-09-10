<?php
/*
Author : Eric Lamoureux
*/
require_once('../../../private/'. $_SERVER['HTTP_HOST'].'/include/config.php');
require_once('../../include/nocache.php');

if (isset($_POST['type']) && !empty(isset($_POST['type']))) {
	$type = $_POST['type'];

	switch ($type) {
		case "readSessionConfig":
			readSessionConfig($mysqli);
			break;
		default:
			invalidRequest();
	}
} else {
	invalidRequest();
};

/**
 * This function will read the current active session config
 * @throws Exception
 */
function readSessionConfig($mysqli) {
	try{
		
		$query = "SELECT onlinepreregiststartdate, onlinepreregistenddate, if (isonlinepreregistactive = 1 AND curdate() between onlinepreregiststartdate AND onlinepreregistenddate, 1 , 0) preregistrationok FROM cpa_sessions WHERE active = 1";
		$result = $mysqli->query($query);
		while ($row = $result->fetch_assoc()) {
			$row['preregistrationok'] = (int)$row['preregistrationok'];
			$data['data'][] = $row;
		}

		$data['success'] = true;
		echo json_encode($data);
		exit;
	}catch (Exception $e) {
		$data = array();
		$data['success'] = false;
		$data['message'] = $e->getMessage();
		echo json_encode($data);
		exit;
	}
};



function invalidRequest() {
	$data = array();
	$data['success'] = false;
	$data['message'] = "Invalid request.";
	echo json_encode($data);
	exit;
};

?>
