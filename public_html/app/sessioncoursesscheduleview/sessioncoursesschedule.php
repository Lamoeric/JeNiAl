<?php
/*
Author : Eric Lamoureux
*/
require_once('../../../private/'. $_SERVER['HTTP_HOST'].'/include/config.php');
require_once('../../include/nocache.php');

if( isset($_POST['type']) && !empty( isset($_POST['type']) ) ){
	$type = $_POST['type'];

	switch ($type) {
		case "getSessionDetails":
			getSessionDetails($mysqli, $_POST['sessionid'], $_POST['language']);
			break;
		default:
			invalidRequest();
	}
} else {
	invalidRequest();
};

/**
 * This function gets the details of all ice time for a session, arena, ice and day
 */
function getSessionArenaDayIcetimes($mysqli, $sessionid, $arenaid, $iceid, $dayid, $language){
	if(empty($sessionid)) throw new Exception( "Invalid session." );
	$query = "SELECT csi.day, csi.starttime, csi.endtime, csi.duration
					  FROM cpa_sessions_icetimes csi
					  JOIN cpa_sessions cs ON cs.id = csi.sessionid
					  WHERE cs.id = $sessionid
					  AND csi.arenaid = $arenaid
					  AND (csi.iceid is null OR csi.iceid = $iceid)
					  AND csi.day = $dayid
					  ORDER BY csi.day, csi.starttime, csi.endtime";
	$result = $mysqli->query( $query );
	$data = array();
	$data['data'] = array();
	while ($row = $result->fetch_assoc()) {
		$data['data'][] = $row;
	}
	$data['success'] = true;
	return $data;
};

/**
 * This function gets the details of all courses for a session, arena, ice and day
 */
function getSessionArenaDayCourses($mysqli, $sessionid, $arenaid, $iceid, $dayid, $language){
	if(empty($sessionid)) throw new Exception( "Invalid session." );
	$query = "SELECT csc.name, getTextLabel(csc.label, '$language') courselabel,
						getTextLabel((select label from cpa_courses_levels where coursecode = csc.coursecode and code = csc.courselevel), '$language') courselevellabel,
					  cscs.day, cscs.starttime, cscs.endtime, cscs.duration, csc.coursecode, csc.courselevel, ccl.schedulecolor levelcolor, cc.schedulecolor coursecolor
					  FROM cpa_sessions_courses_schedule cscs
					  JOIN cpa_sessions_courses csc ON csc.id = cscs.sessionscoursesid
						JOIN cpa_courses cc ON cc.code = csc.coursecode
						LEFT JOIN cpa_courses_levels ccl ON ccl.code = csc.courselevel AND ccl.coursecode = csc.coursecode
					  JOIN cpa_sessions cs ON cs.id = csc.sessionid
					  WHERE cs.id = $sessionid
					  AND cscs.arenaid = $arenaid
					  AND (cscs.iceid is null OR cscs.iceid = $iceid)
					  AND cscs.day = $dayid
					  ORDER BY cscs.starttime";
	$result = $mysqli->query( $query );
	$data = array();
	$data['data'] = array();
	while ($row = $result->fetch_assoc()) {
		$data['data'][] = $row;
	}
	$data['success'] = true;
	return $data;
};

/**
 * This function gets the details of all days for a session, arena and ice
 */
function getSessionArenaDays($mysqli, $sessionid, $arenaid, $iceid, $language){
	if(empty($sessionid)) throw new Exception( "Invalid session." );
	$query = "SELECT DISTINCT day, getCodeDescription('days', day, '$language') daylabel
					  FROM cpa_sessions_courses_schedule cscs
					  JOIN cpa_sessions_courses csc ON csc.id = cscs.sessionscoursesid
					  JOIN cpa_sessions cs ON cs.id = csc.sessionid
					  WHERE cs.id = $sessionid
					  AND cscs.arenaid = $arenaid
					  AND (cscs.iceid is null OR cscs.iceid = $iceid)
					  ORDER BY cscs.day";
	$result = $mysqli->query( $query );
	$data = array();
	$data['data'] = array();
	while ($row = $result->fetch_assoc()) {
		$row['courses'] 	= getSessionArenaDayCourses($mysqli, $sessionid, $arenaid, $iceid, $row['day'], $language)['data'];
		$row['icetimes'] 	= getSessionArenaDayIcetimes($mysqli, $sessionid, $arenaid, $iceid, $row['day'], $language)['data'];
		$data['data'][] = $row;
	}
	$data['success'] = true;
	return $data;
};

/**
 * This function gets the details of all arenas for a session
 */
function getSessionArenas($mysqli, $sessionid, $language){
	if(empty($sessionid)) throw new Exception( "Invalid session." );
	$query = "SELECT distinct cscs.arenaid, cscs.iceid, getTextLabel(ca.label, 'fr-ca') arenalabel, getTextLabel(cai.label, 'fr-ca') icelabel,
          	(select min(cscs2.starttime) minstarttime from cpa_sessions_courses_schedule cscs2 where cscs2.arenaid = cscs.arenaid and cscs2.iceid = cscs.iceid) minstarttime,
          	(select max(cscs2.endtime) maxendtime from cpa_sessions_courses_schedule cscs2 where cscs2.arenaid = cscs.arenaid and cscs2.iceid = cscs.iceid) maxendtime
  					FROM cpa_sessions_courses_schedule cscs
  					JOIN cpa_arenas ca ON ca.id = cscs.arenaid
  					LEFT JOIN cpa_arenas_ices cai ON cai.arenaid = cscs.arenaid AND cai.id = cscs.iceid
  					JOIN cpa_sessions_courses csc ON csc.id = cscs.sessionscoursesid
  					JOIN cpa_sessions cs ON cs.id = csc.sessionid
  					WHERE cs.id = $sessionid
  					ORDER BY cscs.arenaid, cscs.iceid";
	$result = $mysqli->query( $query );
	$data = array();
	$data['data'] = array();
	while ($row = $result->fetch_assoc()) {
		$row['days'] 	= getSessionArenaDays($mysqli, $sessionid, $row['arenaid'], $row['iceid'], $language)['data'];
		$data['data'][] = $row;
	}
	$data['success'] = true;
	return $data;
};

/**
 * This function gets the details of one session from database
 */
function getSessionDetails($mysqli, $sessionid, $language){
	try{
		if ($sessionid && !empty($sessionid)) {
			$query = "SELECT *, getTextLabel(label, '$language') as label_txt
								FROM cpa_sessions
								WHERE id = $sessionid";
		} else {
			$query = "SELECT *, getTextLabel(label, '$language') as label_txt
								FROM cpa_sessions
								WHERE active = 1";
		}
		$result = $mysqli->query( $query );
		$data = array();
		while ($row = $result->fetch_assoc()) {
			$row['arenas'] 	= getSessionArenas($mysqli, $row['id'], $language)['data'];
			$data['data'][] = $row;
		}
		$data['success'] = true;
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

function invalidRequest(){
	$data = array();
	$data['success'] = false;
	$data['message'] = "Invalid request.";
	echo json_encode($data);
	exit;
};

?>
