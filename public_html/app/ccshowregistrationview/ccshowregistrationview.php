<?php
/*
Author : Eric Lamoureux
*/
require_once('../../../private/'. $_SERVER['HTTP_HOST'].'/include/config.php');
require_once('../../include/nocache.php');
require_once('../../include/registration.php');

if( isset($_POST['type']) && !empty( isset($_POST['type']) ) ){
	$type = $_POST['type'];

	switch ($type) {
		case "getSkaterRegistrationDetails":
			getSkaterRegistrationDetails($mysqli, $_POST['userid'], $_POST['skaterid'], $_POST['showid'], $_POST['registrationdate'], $_POST['language']);
			break;
		case "getShowRules":
			getShowRules($mysqli, $_POST['showid'], $_POST['language']);
			break;
		case "acceptRegistration":
			acceptRegistration($mysqli, $_POST['registration'], $_POST['billid'], $_POST['language'], true);
			break;
		default:
			invalidRequest();
	}
} else {
	invalidRequest();
};

/**
 * This function gets the details of one member from database
 */
function getMemberDetailsInt($mysqli, $id, $language) {
	$data = array();
	$data['data'] = array();
	$query = "SELECT * FROM cpa_members WHERE id = '$id'";
	$result = $mysqli->query($query);
	while ($row = $result->fetch_assoc()) {
		$row['id'] = (int) $row['id'];
		$data['data'][] = $row;
	}
	$data['success'] = true;
	return $data;
};

/**
 * This function gets the performances for a show
 * @throws Exception
 */
function getShowPerformances($mysqli, $showid, $language){
	$data = array();
	$query = "SELECT csp.*, getEnglishTextLabel(csp.label) as label_en, getFrenchTextLabel(csp.label) as label_fr,
					  getEnglishTextLabel(csp.websitedesc) as websitedesc_en, getFrenchTextLabel(csp.websitedesc) as websitedesc_fr,
					  (select getTextLabel(ca.label, '$language') from cpa_arenas ca where ca.id = csp.arenaid) arenalabel,
					  (select getTextLabel(cai.label, '$language') from cpa_arenas_ices cai where cai.arenaid = csp.arenaid and cai.id = csp.iceid) icelabel,
					  getCodeDescription('performancetypes', csp.type, '$language') typelabel, getTextLabel(csp.label, '$language') performancelabel
			  FROM cpa_shows_performances csp
			  WHERE csp.showid = $showid
			  ORDER BY csp.perfdate";
	$result = $mysqli->query( $query );
	while ($row = $result->fetch_assoc()) {
		$data['data'][] = $row;
	}
	$data['success'] = true;
	return $data;
}

/**
 * This function gets the registration of the skaters
 * @throws Exception
 */
function getSkaterRegistrationDetails($mysqli, $userid, $skaterid, $showid, $registrationdate, $language){
	try{
		$data = array();
		$lastupdateddate=null;
		// Check if user already has a registration for this show
		$query = "SELECT cr.id, cr.lastupdateddate
				  FROM cpa_registrations cr
				  WHERE cr.memberid = $skaterid
				  AND cr.showid = $showid
				  AND (cr.relatednewregistrationid = null OR cr.relatednewregistrationid = 0)";
		$result = $mysqli->query( $query );
		$row = $result->fetch_assoc();
		if (!empty($row['registrationid'])) {
			// If we are to allow modifications to a skater's registration, change this code.
			// $registrationid = (int)$row['id'];
			// $lastupdateddate = $row['lastupdateddate'];
			$data = array();
			$data['success'] = false;
			$data['errno'] = 997;
			$data['message'] = "Skater is already registered for this show";
			echo json_encode($data);
			exit;
		} else {
			$registrationid = 0;
		}
		$query = "SELECT cs.id showid, getTextLabel(cs.label, '$language') showlabel, $skaterid memberid, (SELECT cs2.onlinepaymentoption FROM cpa_sessions cs2 WHERE cs2.id = cs.sessionid) onlinepaymentoption
				  FROM cpa_shows cs
				  WHERE cs.id = $showid";
		$result = $mysqli->query( $query );
		while ($row = $result->fetch_assoc()) {
			$row['onlinepaymentoption'] = (int)$row['onlinepaymentoption'];
			$temp = getMemberDetailsInt($mysqli, $skaterid, $language)['data'];
			if (count($temp) > 0) {
				$row['member'] = $temp[0];
			}
			$row['shownumbers'] 		= getShowNumbersDetails($mysqli, $registrationid, $registrationdate, $showid, $skaterid, $language)['data'];
			$row['performances']  		= getShowPerformances($mysqli, $showid, $language)['data'];
			$row['charges'] 			= getShowChargesDetails($mysqli, $registrationid, $showid, $language, true)['data'];
			$row['familyMemberCount']	= countShowFamilyMembersRegistrations($mysqli, $showid, $skaterid, $language);
			$data['data'][] = $row;
		}
		$data['data'][0]['id'] = (int)$registrationid;
		$data['data'][0]['originalId'] = (int)$registrationid;
		$data['data'][0]['lastupdateddate'] = $lastupdateddate;
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
 * This function gets the registration of the skaters
 * @throws Exception
 */
function getShowRules($mysqli, $showid, $language){
	try{
		$data = array();
		$query = "SELECT csp.*, getWSTextLabel(csp.paragraphtext, '$language') paragraphtext, 
										 getWSTextLabel(csp.title, '$language') title,
										 getWSTextLabel(csp.subtitle, '$language') subtitle
							FROM cpa_shows_rules csp
							WHERE csp.showid = $showid
							ORDER BY paragraphindex";
		$result = $mysqli->query( $query );
		$data['paragraphs'] = array();
		while ($row = $result->fetch_assoc()) {
			$row['id'] = (int)$row['id'];
			$row['showid'] = (int)$row['showid'];
			$row['paragraphindex'] = (int)$row['paragraphindex'];
			$data['paragraphs'][] = $row;
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
