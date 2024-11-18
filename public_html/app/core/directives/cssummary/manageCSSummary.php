<?php
/*
Author : Eric Lamoureux
*/
//require_once('../../../private/'. $_SERVER['HTTP_HOST'].'/include/config.php');
//require_once('../../include/nocache.php');

//if( isset($_POST['type']) && !empty( isset($_POST['type']) ) ){
//	$type = $_POST['type'];
//
//	switch ($type) {
//		case "getAllTestSummary":
//			getAllTestSummary($mysqli, $_POST['memberid'], $_POST['language']);
//			break;
//		default:
//			invalidRequest();
//	}
//} else {
//	invalidRequest();
//};

/**
 * This function gets list of all test summary from database
 */
function getOneTestSummary($mysqli, $memberid, $testtype, $language){
	try{
		$data = array();
		$data['data'] = array();
		$data['success'] = null;
		$query = "SELECT testdefid, level, type, subtype, testlevellabel, testsubtypelabel, minimumnbtests, membersuccess , levelsuccess
							FROM
							(SELECT ctd.id as testdefid, ctd.level, ctd.type, ctd.subtype, getCodeDescription('testlevels', ctd.level, '$language') testlevellabel,
								getCodeDescription('testsubtypes', ctd.subtype, '$language') testsubtypelabel, minimumnbtests,
							    (SELECT count(distinct memberid, testid, success)
							     FROM cpa_members_tests
							     cmt join cpa_tests ct on ct.id = cmt.testid
							     WHERE ct.testsdefinitionsid = ctd.id
							     AND success = 1
							     AND memberid = $memberid) membersuccess,
							    if((SELECT count(distinct memberid, testid, success)
							    		FROM cpa_members_tests cmt
							    		JOIN cpa_tests ct ON ct.id = cmt.testid
							    		WHERE ct.testsdefinitionsid = ctd.id
							    		AND success = 1
							    		AND memberid = $memberid) >= minimumnbtests, 1, 0) levelsuccess
							FROM cpa_tests_definitions ctd
							WHERE ctd.type = '$testtype'
							AND ctd.version = 1) AA
							WHERE levelsuccess = 1
							ORDER BY level desc
							LIMIT 1";
		$result = $mysqli->query( $query );

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

/**
 * This function gets list of all test summary from database
 */
function getOneSubTestSummary($mysqli, $memberid, $testtype, $testSubType, $language){
	try{
		$data = array();
		$data['data'] = array();
		$data['success'] = null;
		$query = "SELECT testdefid, level, type, subtype, testlevellabel, testsubtypelabel, minimumnbtests, membersuccess , levelsuccess
							FROM
							(SELECT ctd.id as testdefid, ctd.level, ctd.type, ctd.subtype, getCodeDescription('testlevels', ctd.level, '$language') testlevellabel,
								getCodeDescription('testsubtypes', ctd.subtype, '$language') testsubtypelabel, minimumnbtests,
							    (SELECT count(distinct memberid, testid, success)
							     FROM cpa_members_tests
							     cmt join cpa_tests ct on ct.id = cmt.testid
							     WHERE ct.testsdefinitionsid = ctd.id
							     AND success = 1
							     AND memberid = $memberid) membersuccess,
							    if((SELECT count(distinct memberid, testid, success)
							    		FROM cpa_members_tests cmt
							    		JOIN cpa_tests ct ON ct.id = cmt.testid
							    		WHERE ct.testsdefinitionsid = ctd.id
							    		AND success = 1
							    		AND memberid = $memberid) >= minimumnbtests, 1, 0) levelsuccess
							FROM cpa_tests_definitions ctd
							WHERE ctd.type = '$testtype'
							AND ctd.version = 1
							AND ctd.subtype = '$testSubType') AA
							WHERE levelsuccess = 1
							ORDER BY level desc
							LIMIT 1";
		$result = $mysqli->query( $query );

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

/**
 * This function gets the lists of all test summary from database
 */
function getAllTestSummary($mysqli, $memberid, $language){
	try{
		$data = array();
			$data['summarydances'] 				= getOneTestSummary($mysqli, $memberid, 'DANCE', $language)['data'];
			$data['summaryabilities'] 			= getOneTestSummary($mysqli, $memberid, 'SKILLS', $language)['data'];
			$data['summaryfreestyles'] 		= getOneTestSummary($mysqli, $memberid, 'FREE', $language)['data'];
//			$data['summaryinterpretives'] 	= getOneTestSummary($mysqli, $memberid, 'INTER', $language)['data'];
			$data['summaryinterpretives']['SINGLE'] 	= getOneSubTestSummary($mysqli, $memberid, 'INTER', 'SINGLE', $language)['data'];
			$data['summaryinterpretives']['COUPLE'] 	= getOneSubTestSummary($mysqli, $memberid, 'INTER', 'COUPLE', $language)['data'];
//			$data['summarycompetitives'] 	= getOneTestSummary($mysqli, $memberid, 'COMP', $language)['data'];
			$data['summarycompetitives']['SINGLE'] 	= getOneSubTestSummary($mysqli, $memberid, 'COMP', 'SINGLE', $language)['data'];
			$data['summarycompetitives']['COUPLE'] 	= getOneSubTestSummary($mysqli, $memberid, 'COMP', 'COUPLE', $language)['data'];
			$data['summarycompetitives']['DANCE'] 	= getOneSubTestSummary($mysqli, $memberid, 'COMP', 'DANCE', $language)['data'];
		$data['success'] = true;
		return $data;
	}catch (Exception $e){
		$data = array();
		$data['success'] = false;
		$data['message'] = $e->getMessage();
		return $data;
	}
};

/**
 * This function gets the canskate ribbons of one member from database
 */
function getMemberCanskateStageRibbons($mysqli, $memberid, $category){
	try{
		if(empty($memberid)) throw new Exception( "Invalid Member ID." );
		$query = "SELECT cc.id canskateid, cmcr.ribbondate, cmcr.id
							FROM cpa_canskate cc
							left join cpa_members_canskate_ribbons cmcr on cc.id = cmcr.canskateid AND (memberid = $memberid || memberid is null)
							WHERE cc.category = '$category'
							ORDER BY cc.stage";
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

/**
 * This function gets the canskate badges of one member from database
 * (we use BALANCE as a category to get the stages 1 to 6. We could have used any of the 3 categories)
 */
function getMemberCanskateStageBadges($mysqli, $memberid = ''){
	try{
		if(empty($memberid)) throw new Exception( "Invalid Member ID." );
		$query = "SELECT cc.stage canskatestage, cmcb.badgedate, cmcb.id
							FROM cpa_canskate cc
							LEFT JOIN cpa_members_canskate_badges cmcb on cc.stage = cmcb.canskatestage AND (memberid = $memberid || memberid is null)
							WHERE cc.category = 'BALANCE'
							ORDER BY cc.stage";
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

/**
 * This function gets the canskate elements of one member from database
 */
function getMemberCanskateStageTests($mysqli, $memberid, $category, $stage){
	try{
		if(empty($memberid)) throw new Exception( "Invalid Member ID." );
		$language = $_POST['language'];
		$query = "SELECT ccst.id canskatetestid, testdate as testdatestr, success, ccst.canskateid, ccst.type, cmcst.id as id, ccs.stage, getTextLabel(ccst.label, '$language') text
							FROM cpa_canskate_tests ccst
							left join cpa_members_canskate_tests cmcst on cmcst.canskatetestid = ccst.id AND (memberid = '$memberid' || memberid is null)
							left join cpa_canskate ccs on ccs.id = ccst.canskateid
							WHERE (ccst.type = 'TEST' || ccst.type = 'SUBTEST') and ccs.category = '$category' and ccs.stage = $stage
							ORDER BY ccst.sequence, cmcst.testdate desc";
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


//function invalidRequest(){
//	$data = array();
//	$data['success'] = false;
//	$data['message'] = "Invalid request.";
//	echo json_encode($data);
//	exit;
//};

?>
