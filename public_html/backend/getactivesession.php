<?php
/*
* Author : Eric Lamoureux
*
* File contains function to get active session
*
*/

// Try to get the courses of the member, with the delta from the previous registration already computed
function getActiveSession($mysqli) {
	$data = array();
	$data['data'] = array();
	$query = "SELECT * FROM cpa_sessions WHERE active = 1";
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
    $data['data'][] = $row;
	$data['success'] = true;
	return $data;
};



?>
