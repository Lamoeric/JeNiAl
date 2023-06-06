<?php
/*
Author : Eric Lamoureux
*/

require_once('../../../private/'. $_SERVER['HTTP_HOST'].'/include/config.php');


try {
  $filter = array();
  if (isset($_GET['language']) && !empty(isset($_GET['language']))) {
    $language = $_GET['language'];
  } else {
    echo "Language not set";
    die;
  }
  $data = getMembersInfo($mysqli, $language, $filter);
  $fp = fopen('php://output', 'w');
  if ($fp && $data['success'] == true) {
    header('Content-Type: text/csv; charset=Windows-1252');
    header('Content-Disposition: attachment; filename="export.csv"; charset=Windows-1252');
    header("Content-Encoding: Windows-1252");
    header("Content-Transfer-Encoding: Windows-1252");
    header('Pragma: no-cache');
    header('Expires: 0');

    fputcsv($fp, ['sep=,']);
    fputcsv($fp, array_map("utf8_decode", $data['headers']));
    for ($i = 0; $i < sizeof($data['data']); $i++) {
      $row = array_map("utf8_decode", $data['data'][$i]);
      fputcsv($fp, array_values($row));
    }
    die;
  }
  // $fp = fopen('php://output', 'w');
  // if ($fp && $result) {
  //     // header('Content-Type: text/csv');
  //     // header('Content-Type: text/csv; charset=UTF-8');
  //     header('Content-Type: text/csv; charset=Windows-1252');
  //     header('Content-Disposition: attachment; filename="export.csv"; charset=Windows-1252');
  //     header("Content-Encoding: Windows-1252");
  //     // header("Content-Transfer-Encoding: binary");
  //     // header("Content-Transfer-Encoding: binary");
  //     header("Content-Transfer-Encoding: Windows-1252");
  //     header('Pragma: no-cache');
  //     header('Expires: 0');
  //     // fprintf($fp, chr(0xEF).chr(0xBB).chr(0xBF));
  //     // fprintf($fp, chr(255) . chr(254));
  //     // fprintf($fp, 'sep=,\n');
  //     fputcsv($fp, ['sep=,']);
  //     fputcsv($fp, $headers);
  //     while ($row = $result->fetch_assoc()) {
  //       $row = array_map("utf8_decode", $row);
  //       fputcsv($fp, array_values($row));
  //     }
  //     // $fp = mb_convert_encoding($fp, 'Windows-1252', 'UTF-8');
  //     // print "\xEF\xBB\xBF";
  //     // print chr(255) . chr(254) . mb_convert_encoding($fp, 'UTF-16LE', 'UTF-8');
  //     // print chr(255) . chr(254) . mb_convert_encoding($fp, 'Windows-1252', 'UTF-8');
  //     // $fp =  mb_convert_encoding($fp, 'Windows-1252', 'UTF-8');
  //     // mb_convert_encoding($value, 'Windows-1252');
  //     // $fp = iconv(mbq_detect_encoding($fp), 'Windows-1252//TRANSLIT', $fp);
  //     die;
  // }
}catch (Exception $e) {
  $message = $e->getMessage();
  echo $message;
  die;
}

/*
 * This function returns the column's header in the proper language
 * TODO : switch this for a array filled by one SQL command instead of one command per column name
 */
function convertColumnName($mysqli, $columnname, $language) {
  $query = "select getcodedescription('exportmemberscolheaders', '$columnname', '$language') label FROM dual";
	$result = $mysqli->query($query);
  $data = $result->fetch_assoc();
  return $data['label'] && !empty($data['label']) ? $data['label'] : $columnname;
}

/*
 * This function returns the column's name from the query
 */
function getColumnName($result, $field_offset) {
    $properties = mysqli_fetch_field_direct($result, $field_offset);
    return is_object($properties) ? $properties->name : null;
}

/**
 * This function gets the canskate ribbons of one member from database
 */
function getMemberCanskateStageRibbons($mysqli, $memberid, $category){
	if(empty($memberid)) throw new Exception("Invalid Member ID.");
	$query = "SELECT cc.id canskateid, cmcr.ribbondate, cmcr.id
						FROM cpa_canskate cc
						left join cpa_members_canskate_ribbons cmcr on cc.id = cmcr.canskateid AND (memberid = $memberid || memberid is null)
						WHERE cc.category = '$category'
						ORDER BY cc.stage";
	$result = $mysqli->query($query);
	$data = array();
	// $data['data'] = array();
  $data['data'] = $result->fetch_assoc();

	// while ($row = $result->fetch_assoc()) {
	// 	$data['data'][] = $row;
	// }
	$data['success'] = true;
	return $data;
};

/**
* This function gets the highest test for one type from database
 */
function getOneTestSummary($mysqli, $memberid, $testtype, $language){
	$data = array();
	$data['data'] = array();
	$data['success'] = null;
	$query = "SELECT testlevellabel, testsubtypelabel, if(membersuccess >= minimumnbtests, 1, 0) levelsuccess
						FROM
						(SELECT ctd.id as testdefid, ctd.level, ctd.type, ctd.subtype, getCodeDescription('testlevels', ctd.level, '$language') testlevellabel,
							getCodeDescription('testsubtypes', ctd.subtype, '$language') testsubtypelabel, minimumnbtests,
						    (SELECT count(*)
						     FROM cpa_members_tests
						     cmt join cpa_tests ct on ct.id = cmt.testid
						     WHERE ct.testsdefinitionsid = ctd.id
						     AND success IN (1,5)
						     AND memberid = $memberid) membersuccess
						FROM cpa_tests_definitions ctd
						WHERE ctd.type = '$testtype'
						AND ctd.version = 1) AA
						WHERE membersuccess >= minimumnbtests
						ORDER BY level desc
						LIMIT 1";
	$result = $mysqli->query($query);

	$data['data'] = $result->fetch_assoc();
	$data['success'] = true;
	return $data;
};

/**
 * This function gets the highest test for one type from database
 */
function getOneStarTestSummary($mysqli, $memberid, $testtype, $language) {
	$data = array();
	$data['data'] = array();
	$data['success'] = null;
	$query = "SELECT testlevellabel, if(membersuccess >= minimumnbtests, 1, 0) levelsuccess
						FROM
						(SELECT ctd.id as testdefid, ctd.level, ctd.type, ctd.subtype, concat('STAR ', ctd.level) testlevellabel,
							getCodeDescription('testsubtypes', ctd.subtype, '$language') testsubtypelabel, minimumnbtests,
						    (SELECT count(*)
						     FROM cpa_members_tests
						     cmt join cpa_tests ct on ct.id = cmt.testid
						     WHERE ct.testsdefinitionsid = ctd.id
						     AND success IN (1,5)
						     AND memberid = $memberid) membersuccess
						FROM cpa_tests_definitions ctd
						WHERE ctd.type = '$testtype'
						AND ctd.version = 2) AA
						WHERE membersuccess >= minimumnbtests
						ORDER BY level desc
						LIMIT 1";
	$result = $mysqli->query($query);

	$data['data'] = $result->fetch_assoc();
	return $data;
};

function getMembersInfo($mysqli, $language, $filter) {
  try {
		$data = array();
		$data['success'] = null;
		$whereclause = " where 1=1 ";
		// if (!empty($filter['qualification']))	$whereclause .= " and cm.qualifications like BINARY '%" .  $filter['qualification']  . "%'";
		if (!empty($filter['course']))	  $whereclause .= " and cm.id in (select memberid from cpa_sessions_courses_members where sessionscoursesid = '" . $filter['course']  . "')" ;
		if (!empty($filter['registration']) && $filter['registration'] == 'REGISTERED')	  $whereclause .= " and cm.id in (select memberid from cpa_sessions_courses_members where sessionscoursesid in (select id from cpa_sessions_courses where sessionid = (select id from cpa_sessions where active = '1')))" ;
		if (!empty($filter['registration']) && $filter['registration'] == 'NOTREGISTERED')	  $whereclause .= " and cm.id not in (select memberid from cpa_sessions_courses_members where sessionscoursesid in (select id from cpa_sessions_courses where sessionid = (select id from cpa_sessions where active = '1')))" ;
    $query = "SELECT cm.id, cm.firstname, cm.lastname, cm.skatecanadano, /*`initial`,*/ getCodeDescription('languages', cm.`language`, 'fr-ca') `language`, getCodeDescription('genders', cm.gender, 'fr-ca') gender,
                     cm.birthday, floor(DATEDIFF(curdate(), cm.birthday) / 365.25) as age,
                     cm.healthcareno, cm.healthcareexp, cm.healthcomments, qualifications,
                     cm.address1, cm.address2, cm.town, getCodeDescription('provinces', cm.province, 'fr-ca') province, cm.postalcode, cm.country, cm.homephone, cm.cellphone, cm.otherphone, cm.email, cm.email2,
                     /*reportsc,*/ cm.homeclub, /*skaterlevel, mainprogram, secondprogram,*/ cm.comments,
                     (select max(canskatestage) from cpa_members_canskate_badges cmcb where cmcb.memberid = cm.id) HighestBadge,
                     (select 1 from cpa_sessions_courses_members cscm
                      join cpa_sessions_courses csc ON csc.id = cscm.sessionscoursesid
                      join cpa_sessions cs ON cs.id = csc.sessionid
                      join cpa_sessions cs2 ON cs2.previoussessionid = cs.id
                      where cs2.active = 1 and cscm.memberid = cm.id limit 1) registered_previous,
                     (select 1 from cpa_sessions_courses_members cscm
                      join cpa_sessions_courses csc ON csc.id = cscm.sessionscoursesid
                      join cpa_sessions cs ON cs.id = csc.sessionid
                      where cs.active = 1 and cscm.memberid = cm.id limit 1) registered,
                     (select group_concat(csc.name)
                      from cpa_sessions_courses_members cscm
                      join cpa_sessions_courses csc ON csc.id = cscm.sessionscoursesid
                      join cpa_sessions cs ON cs.id = csc.sessionid
                      where cs.active = 1 and cscm.memberid = cm.id and cscm.registrationenddate is null) courselist
              FROM cpa_members cm " . $whereclause .
              "	ORDER by lastname, firstname";
		$result = $mysqli->query($query);

    $number_of_fields = mysqli_num_fields($result);
    $data['headers'] = array();
    for ($i = 0; $i < $number_of_fields; $i++) {
      $data['headers'][] = convertColumnName($mysqli, getColumnName($result, $i), $language);
    }
    $data['headers'][] = convertColumnName($mysqli, 'STAR_dance', $language);
    $data['headers'][] = convertColumnName($mysqli, 'STAR_skills', $language);
    $data['headers'][] = convertColumnName($mysqli, 'STAR_free', $language);

    $data['headers'][] = convertColumnName($mysqli, 'dance', $language);
    $data['headers'][] = convertColumnName($mysqli, 'skills', $language);
    $data['headers'][] = convertColumnName($mysqli, 'free', $language);

    $data['headers'][] = convertColumnName($mysqli, 'cs_balance', $language);
    $data['headers'][] = convertColumnName($mysqli, 'cs_control', $language);
    $data['headers'][] = convertColumnName($mysqli, 'cs_agility', $language);
    $data['headers'][] = convertColumnName($mysqli, 'cs_preskate', $language);

		while ($row = $result->fetch_assoc()) {
      $row['STAR_dance'] = getOneStarTestSummary($mysqli, $row['id'], 'DANCE', $language)['data']['testlevellabel'];
      $row['STAR_skills'] = getOneStarTestSummary($mysqli, $row['id'], 'SKILLS', $language)['data']['testlevellabel'];
      $row['STAR_free'] = getOneStarTestSummary($mysqli, $row['id'], 'FREE', $language)['data']['testlevellabel'];

      $row['dance'] = getOneTestSummary($mysqli, $row['id'], 'DANCE', $language)['data']['testlevellabel'];
      $row['skills'] = getOneTestSummary($mysqli, $row['id'], 'SKILLS', $language)['data']['testlevellabel'];
      $row['free'] = getOneTestSummary($mysqli, $row['id'], 'FREE', $language)['data']['testlevellabel'];

      $row['cs_balance'] 	= getMemberCanskateStageRibbons($mysqli, $row['id'], 'BALANCE')['data']['ribbondate'];
			$row['cs_control'] 	= getMemberCanskateStageRibbons($mysqli, $row['id'], 'CONTROL')['data']['ribbondate'];
			$row['cs_agility'] 	= getMemberCanskateStageRibbons($mysqli, $row['id'], 'AGILITY')['data']['ribbondate'];
			$row['cs_preskate'] = getMemberCanskateStageRibbons($mysqli, $row['id'], 'PRESKATE')['data']['ribbondate'];

			$data['data'][] = $row;
		}
		$data['success'] = true;
		return $data;
		exit;
	}catch (Exception $e) {
		$data = array();
		$data['success'] = false;
		$data['message'] = $e->getMessage();
		echo json_encode($data);
		exit;
	}
}
?>
