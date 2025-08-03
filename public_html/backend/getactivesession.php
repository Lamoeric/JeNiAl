<?php
/*
* Author : Eric Lamoureux
*
* File contains function to get active session
*
*/

function getActiveSession($mysqli) {
	$data = array();
	$data['data'] = array();
	$query = "SELECT *, if (isonlinepreregistactive = 1 AND curdate() between onlinepreregiststartdate AND onlinepreregistenddate, 1 , 0) preregistrationok FROM cpa_sessions WHERE active = 1";
	$result = $mysqli->query($query);
	$row = $result->fetch_assoc();
    $row['id'] = (int)$row['id'];
    $row['prorataoptions'] = (int)$row['prorataoptions'];
    $row['attendancepaidinfull'] = (int)$row['attendancepaidinfull'];
    $row['active'] = (int)$row['active'];
    $row['previoussessionid'] = (int)$row['previoussessionid'];
    $row['isonlineregistactive'] = (int)$row['isonlineregistactive'];
    $row['isonlinepreregistactive'] = (int)$row['isonlinepreregistactive'];
    $row['isonlinepreregistemail'] = (int)$row['isonlinepreregistemail'];
    $row['onlinepaymentoption'] = (int)$row['onlinepaymentoption'];
    $row['isonlineregistemail'] = (int)$row['isonlineregistemail'];
    $row['onlineregistemailtpl'] = (int)$row['onlineregistemailtpl'];
    $row['isonlineregistemailinclbill'] = (int)$row['isonlineregistemailinclbill'];
    $row['preregistrationok'] = (int)$row['preregistrationok'];
    $data['data'][] = $row;
	$data['success'] = true;
	return $data;
};



?>
