<?php
/*
Author : Eric Lamoureux
*/
require_once('../../../private/'. $_SERVER['HTTP_HOST'].'/include/config.php');
require_once('../../include/nocache.php');

if( isset($_POST['type']) && !empty( isset($_POST['type']) ) ){
	$type = $_POST['type'];

	switch ($type) {
		case "getSkaterDetails":
			getSkaterDetails($mysqli, $_POST['userid'], $_POST['skaterid'], $_POST['language']);
			break;
		case "saveSkaterDetails":
			saveSkaterDetails($mysqli, $_POST['currentSkater']);
			break;
		default:
			invalidRequest();
	}
} else {
	invalidRequest();
};

/**
 * This function gets the tests of one member FROM database
 */
function getMemberTests($mysqli, $memberid, $type, $language) {
	if (empty($memberid)) throw new Exception( "Invalid Member ID.");
	$query = "SELECT ct.id as testid, testdate as testdatestr, success, ctd.level, ctd.type, ctd.subtype, ct.subsubtype, cmt.id as id,
							getTextLabel(ct.label, '$language') testlabel, getCodeDescription('testlevels', ctd.level, '$language') testlevellabel,
							getCodeDescription('testsubtypes', ctd.type, '$language') testsubtypelabel
						FROM cpa_tests ct
						JOIN cpa_members_tests cmt on cmt.testid = ct.id AND (memberid = $memberid || memberid is null)
						JOIN cpa_tests_definitions ctd on ctd.id = ct.testsdefinitionsid
						WHERE ctd.type = '$type'
						AND ctd.version = 1
						ORDER BY ct.sequence, cmt.testdate desc";
	$result = $mysqli->query($query);
	$data = array();
	$data['data'] = array();
	while ($row = $result->fetch_assoc()) {
		$data['data'][] = $row;
	}
	$data['success'] = true;
	return $data;
};

/**
 * This function gets the new STAR tests of one member FROM database
 */
function getMemberStarTests($mysqli, $memberid, $type, $language) {
	if (empty($memberid)) throw new Exception( "Invalid Member ID.");
	$query = "SELECT ct.id as testid, testdate as testdatestr, success, ctd.level, ctd.type, ctd.subtype, ct.subsubtype, cmt.id as id,
							getTextLabel(ct.label, '$language') testlabel, getCodeDescription('testlevels', ctd.level, '$language') testlevellabel,
							getCodeDescription('testsubtypes', ctd.type, '$language') testsubtypelabel
						FROM cpa_tests ct
						left JOIN cpa_members_tests cmt on cmt.testid = ct.id AND (memberid = $memberid || memberid is null)
						JOIN cpa_tests_definitions ctd on ctd.id = ct.testsdefinitionsid
						WHERE ctd.type = '$type'
						AND ctd.version = 2
						ORDER BY ct.sequence, cmt.testdate desc";
	$result = $mysqli->query($query);
	$data = array();
	$data['data'] = array();
	while ($row = $result->fetch_assoc()) {
		$data['data'][] = $row;
	}
	$data['success'] = true;
	return $data;
};

/**
 * This function gets the courses of one member FROM database
 */
function getMemberCourses($mysqli, $memberid, $language) {
	if (empty($memberid)) throw new Exception( "Invalid Member ID.");
	$query = "SELECT csc.name, cscm.registrationenddate,
									 getTextLabel(csc.label, '$language') courselabel,
									 getTextLabel((SELECT label FROM cpa_courses_levels WHERE coursecode = csc.coursecode and code = csc.courselevel), '$language') courselevellabel,
									 getTextLabel(cs.label, '$language') sessionlabel,
									 cs.coursesstartdate, cs.coursesenddate,
									 getCourseSchedule(csc.id, '$language') AS schedule
				FROM cpa_sessions_courses_members cscm
				JOIN cpa_sessions_courses csc ON csc.id = cscm.sessionscoursesid
				JOIN cpa_sessions cs ON cs.id = csc.sessionid
				JOIN cpa_courses cc on cc.code = csc.coursecode
				WHERE cscm.memberid = $memberid
				ORDER BY cs.startdate DESC, name";
	$result = $mysqli->query($query);
	$data = array();
	$data['data'] = array();
	while ($row = $result->fetch_assoc()) {
		$data['data'][] = $row;
	}
	$data['success'] = true;
	return $data;
};

/**
 * This function gets the numbers of one member FROM database
 */
function getMemberNumbers($mysqli, $memberid, $language) {
	if (empty($memberid)) throw new Exception( "Invalid Member ID.");
	$query = "	SELECT	csn.name, csnm.registrationenddate,	getTextLabel(csn.label, '$language') numberlabel, getTextLabel(cs.label, '$language') sessionlabel,
						cs.practicesstartdate, cs.practicesenddate,
						(SELECT group_concat(concat(getTextLabel((SELECT 	label FROM cpa_arenas WHERE id = arenaid), '$language'),
																			IF((iceid is null or iceid = 0), ', ', concat(' (' , getTextLabel((SELECT label FROM cpa_arenas_ices WHERE id = iceid), '$language'), '), ')),
																				getTextLabel((SELECT description FROM cpa_codetable WHERE ctname = 'days' and code = day), '$language'),
																				' - ',
																				substr(starttime FROM 1 FOR 5),
																				' - ',
																				substr(endtime FROM 1 FOR 5))
																				SEPARATOR ', ') schedule
						FROM cpa_shows_numbers_schedule
						WHERE numberid = csn.id) schedule
				FROM cpa_shows_numbers_members csnm
				JOIN cpa_shows_numbers csn ON csn.id = csnm.numberid
				JOIN cpa_shows cs ON cs.id = csn.showid
				WHERE csnm.memberid = $memberid
				ORDER BY cs.id DESC, name";
	$result = $mysqli->query($query);
	$data = array();
	$data['data'] = array();
	while ($row = $result->fetch_assoc()) {
		$data['data'][] = $row;
	}
	$data['success'] = true;
	return $data;
};

/**
 * This function gets the details of the profile.
 * Info can come FROM cpa_contacts or cpa_members, in this order.
 * @throws Exception
 */
function getSkaterDetails($mysqli, $userid, $skaterid, $language){
	try{
		$data = array();

		$query = "	SELECT cm.*
					FROM cpa_members cm
					JOIN cpa_members_contacts cmc ON cmc.memberid = cm.id
					JOIN cpa_users cu ON cu.contactid = cmc.contactid
					WHERE cu.userid = '$userid' and cm.id = $skaterid";
		$result = $mysqli->query( $query );
		$row = $result->fetch_assoc();
		$id = $row['id'];
		$row['dances'] 			= getMemberTests($mysqli, $id, 'DANCE', $language)['data'];
		$row['abilities'] 		= getMemberTests($mysqli, $id, 'SKILLS', $language)['data'];
		$row['freestyles'] 		= getMemberTests($mysqli, $id, 'FREE', $language)['data'];
		$row['interpretives'] 	= getMemberTests($mysqli, $id, 'INTER', $language)['data'];
		$row['competitives'] 	= getMemberTests($mysqli, $id, 'COMP', $language)['data'];
		$row['stardances'] 		= getMemberStarTests($mysqli, $id, 'DANCE', $language)['data'];
		$row['starabilities'] 	= getMemberStarTests($mysqli, $id, 'SKILLS', $language)['data'];
		$row['starfreestyles'] 	= getMemberStarTests($mysqli, $id, 'FREE', $language)['data'];
		$row['starartistics'] 	= getMemberStarTests($mysqli, $id, 'ARTISTIC', $language)['data'];
		$row['starsynchros'] 	= getMemberStarTests($mysqli, $id, 'SYNCHRO', $language)['data'];
		$row['courses'] 		= getMemberCourses($mysqli, $id, $language)['data'];
		$row['numbers'] 		= getMemberNumbers($mysqli, $id, $language)['data'];
		$data['data'][] = $row;

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


/**
 * This function will handle insert/update/delete of a ice in DB
 * @throws Exception
 */
function saveSkaterDetails($mysqli, $currentSkater){
	try{
		$data = array();
		$id = 						$mysqli->real_escape_string(isset($currentSkater['id']) 						? $currentSkater['id'] : '');
		$firstname = 			$mysqli->real_escape_string(isset($currentSkater['firstname']) 			? $currentSkater['firstname'] : '');
		$lastname = 			$mysqli->real_escape_string(isset($currentSkater['lastname']) 			? $currentSkater['lastname'] : '');
		$skatecanadano = 	$mysqli->real_escape_string(isset($currentSkater['skatecanadano'])	? $currentSkater['skatecanadano'] : '');
		$language = 			$mysqli->real_escape_string(isset($currentSkater['language']) 			? $currentSkater['language'] : '');
		$gender = 				$mysqli->real_escape_string(isset($currentSkater['gender']) 				? $currentSkater['gender'] : '');
		$birthday = 			$mysqli->real_escape_string(isset($currentSkater['birthday']) 			? $currentSkater['birthday'] : '');
		$healthcareno = 	$mysqli->real_escape_string(isset($currentSkater['healthcareno']) 	? $currentSkater['healthcareno'] : '');
		$healthcareexp = 	$mysqli->real_escape_string(isset($currentSkater['healthcareexp']) 	? $currentSkater['healthcareexp'] : '');
		$address2 = 			$mysqli->real_escape_string(isset($currentSkater['address2']) 			? $currentSkater['address2'] : '');
		$address1 = 			$mysqli->real_escape_string(isset($currentSkater['address1']) 			? $currentSkater['address1'] : '');
		$town = 					$mysqli->real_escape_string(isset($currentSkater['town']) 					? $currentSkater['town'] : '');
		$province = 			$mysqli->real_escape_string(isset($currentSkater['province']) 			? $currentSkater['province'] : '');
		$postalcode = 		$mysqli->real_escape_string(isset($currentSkater['postalcode']) 		? $currentSkater['postalcode'] : '');
		$country = 				$mysqli->real_escape_string(isset($currentSkater['country']) 				? $currentSkater['country'] : '');
		$homephone = 			$mysqli->real_escape_string(isset($currentSkater['homephone']) 			? $currentSkater['homephone'] : '');
		$cellphone = 			$mysqli->real_escape_string(isset($currentSkater['cellphone']) 			? $currentSkater['cellphone'] : '');
		$otherphone = 		$mysqli->real_escape_string(isset($currentSkater['otherphone']) 		? $currentSkater['otherphone'] : '');
		$email = 					$mysqli->real_escape_string(isset($currentSkater['email']) 					? $currentSkater['email'] : '');
		$email2 = 				$mysqli->real_escape_string(isset($currentSkater['email2']) 				? $currentSkater['email2'] : '');

		if ($firstname == '' || $lastname == '') {
			throw new Exception( "Required fields missing. Please enter and submit");
		}
		// Update skater
		$query = "UPDATE cpa_members
							SET firstname = '$firstname', lastname = '$lastname', skatecanadano = '$skatecanadano',
							language = '$language', gender = '$gender', birthday = '$birthday', healthcareno = '$healthcareno',
							healthcareexp = '$healthcareexp', address2 = '$address2', address1 = '$address1', town = '$town',
							province = '$province', postalcode = '$postalcode', country = '$country', homephone = '$homephone', cellphone = '$cellphone',
							otherphone = '$otherphone', email = '$email', email2 = '$email2'
							WHERE id = $id";
		if( $mysqli->query( $query ) ){
		} else {
			throw new Exception( $mysqli->sqlstate.' - '. $mysqli->error );
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
