<?php
/*
Author : Eric Lamoureux
*/
require_once('../../../private/'. $_SERVER['HTTP_HOST'].'/include/config.php');
require_once('../../include/nocache.php');
require_once('../core/directives/contacts/contacts.php');
require_once('../core/directives/testsummary/manageTestSummary.php');
require_once('../core/directives/billing/bills.php');

if ( isset($_POST['type']) && !empty( isset($_POST['type']))) {
	$type = $_POST['type'];

	switch ($type) {
		case "insert_member":
			insert_member($mysqli, $_POST['member']);
			break;
		case "updateEntireMember":
			updateEntireMember($mysqli, json_decode($_POST['member'], true));
			break;
		case "delete_member":
			delete_member($mysqli, $_POST['id']);
			break;
		case "getAllMembers":
			getAllMembers($mysqli, $_POST['filter']);
			break;
		case "getMemberDetails":
			getMemberDetails($mysqli, $_POST['id'], $_POST['language']);
			break;
		default:
			invalidRequest($type);
	}
} else {
	invalidRequest(null);
};

function updateEntireMember($mysqli, $member) {
	try {
		$data = array();
		$id = $mysqli->real_escape_string(isset($member['id']) ? $member['id'] : '');

		$data['successmember'] = update_member($mysqli, $member);
		if ($mysqli->real_escape_string(isset($member['contacts']))) {
			$data['successcontacts'] = updateEntireContacts($mysqli, $id, $member['contacts']);
		}
		if ($mysqli->real_escape_string(isset($member['coaches']))) {
			$data['successcoaches'] = updateEntireCoaches($mysqli, $id, $member['coaches']);
		}
		if ($mysqli->real_escape_string(isset($member['dances']))) {
			$data['successdances'] = updateEntireTests($mysqli, $id, $member['dances']);
		}
		if ($mysqli->real_escape_string(isset($member['abilities']))) {
			$data['successabilities'] = updateEntireTests($mysqli, $id, $member['abilities']);
		}
		if ($mysqli->real_escape_string(isset($member['freestyles']))) {
			$data['successfreestyles'] = updateEntireTests($mysqli, $id, $member['freestyles']);
		}
		if ($mysqli->real_escape_string(isset($member['interpretives']))) {
			$data['successinterpretives'] = updateEntireTests($mysqli, $id, $member['interpretives']);
		}
		if ($mysqli->real_escape_string(isset($member['competitives']))) {
			$data['successcompetitives'] = updateEntireTests($mysqli, $id, $member['competitives']);
		}
		if ($mysqli->real_escape_string(isset($member['stardances']))) {
			$data['successstardances'] = updateEntireTests($mysqli, $id, $member['stardances']);
		}
		if ($mysqli->real_escape_string(isset($member['starabilities']))) {
			$data['successstarabilities'] = updateEntireTests($mysqli, $id, $member['starabilities']);
		}
		if ($mysqli->real_escape_string(isset($member['starfreestyles']))) {
			$data['successstarfreestyles'] = updateEntireTests($mysqli, $id, $member['starfreestyles']);
		}
		if ($mysqli->real_escape_string(isset($member['starartistics']))) {
			$data['successstarartistics'] = updateEntireTests($mysqli, $id, $member['starartistics']);
		}
		if ($mysqli->real_escape_string(isset($member['starsynchros']))) {
			$data['successstarsynchros'] = updateEntireTests($mysqli, $id, $member['starsynchros']);
		}
		if ($mysqli->real_escape_string(isset($member['csbalancetests']))) {
			$data['successbalancetests'] = updateEntireCanskateTests($mysqli, $id, $member['csbalancetests']);
		}
		if ($mysqli->real_escape_string(isset($member['cscontroltests']))) {
			$data['successcontroltests'] = updateEntireCanskateTests($mysqli, $id, $member['cscontroltests']);
		}
		if ($mysqli->real_escape_string(isset($member['csagilitytests']))) {
			$data['successagilitytests'] = updateEntireCanskateTests($mysqli, $id, $member['csagilitytests']);
		}
		if ($mysqli->real_escape_string(isset($member['precstests']))) {
			$data['successprecstests'] = updateEntireCanskateTests($mysqli, $id, $member['precstests']);
		}

		if ($mysqli->real_escape_string(isset($member['csbalanceribbons']))) {
			$data['successbalanceribbons'] = updateEntireCanskateRibbons($mysqli, $id, $member['csbalanceribbons']);
		}
		if ($mysqli->real_escape_string(isset($member['cscontrolribbons']))) {
			$data['successcontrolribbons'] = updateEntireCanskateRibbons($mysqli, $id, $member['cscontrolribbons']);
		}
		if ($mysqli->real_escape_string(isset($member['csagilityribbons']))) {
			$data['successagilityribbons'] = updateEntireCanskateRibbons($mysqli, $id, $member['csagilityribbons']);
		}
		if ($mysqli->real_escape_string(isset($member['precsribbons']))) {
			$data['successprecsribbons'] = updateEntireCanskateRibbons($mysqli, $id, $member['precsribbons']);
		}

		if ($mysqli->real_escape_string(isset($member['csstagebadges']))) {
			$data['successstagebadges'] = updateEntireCanskateBadges($mysqli, $id, $member['csstagebadges']);
		}

		$mysqli->close();

		$data['success'] = true;
		$data['message'] = 'Member updated successfully.';
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

function insert_member($mysqli, $member) {
	try {
		$data = array();
		$data = update_member($mysqli, $member);
		$data['success'] = true;
		$data['message'] = 'Member inserted successfully.';
		echo json_encode($data);
		exit;
	}catch (Exception $e) {
		$data = array();
		$data['success'] = false;
		$data['message'] = $e->getMessage();
		echo json_encode($data);
		exit;
	}
}

/**
 * This function will handle user update functionality
 * @throws Exception
 */
function update_member($mysqli, $member) {
	$data = array();
	$id = 				$mysqli->real_escape_string(isset($member['id']) 				? $member['id'] : '');
	$firstname = 		$mysqli->real_escape_string(isset($member['firstname']) 		? $member['firstname'] : '');
	$lastname = 		$mysqli->real_escape_string(isset($member['lastname']) 			? $member['lastname'] : '');
	$skatecanadano = 	$mysqli->real_escape_string(isset($member['skatecanadano'])		? $member['skatecanadano'] : '');
	$initial = 			$mysqli->real_escape_string(isset($member['initial']) 			? $member['initial'] : '');
	$language = 		$mysqli->real_escape_string(isset($member['language']) 			? $member['language'] : '');
	$gender = 			$mysqli->real_escape_string(isset($member['gender']) 			? $member['gender'] : '');
	$familyrank = 		$mysqli->real_escape_string(isset($member['familyrank']) 		? $member['familyrank'] : 0);
	$healthcareno = 	$mysqli->real_escape_string(isset($member['healthcareno']) 		? $member['healthcareno'] : '');
	$healthcareexp = 	$mysqli->real_escape_string(isset($member['healthcareexp']) 	? $member['healthcareexp'] : '');
	$healthcomments =	$mysqli->real_escape_string(isset($member['healthcomments']) 	? $member['healthcomments'] : '');
	$qualifications =	$mysqli->real_escape_string(isset($member['qualifications']) 	? $member['qualifications'] : '');
	$address2 = 		$mysqli->real_escape_string(isset($member['address2']) 			? $member['address2'] : '');
	$address1 = 		$mysqli->real_escape_string(isset($member['address1']) 			? $member['address1'] : '');
	$town = 			$mysqli->real_escape_string(isset($member['town']) 				? $member['town'] : '');
	$province = 		$mysqli->real_escape_string(isset($member['province']) 			? $member['province'] : '');
	$postalcode = 		$mysqli->real_escape_string(isset($member['postalcode']) 		? $member['postalcode'] : '');
	$country = 			$mysqli->real_escape_string(isset($member['country']) 			? $member['country'] : '');
	$homephone = 		$mysqli->real_escape_string(isset($member['homephone']) 		? $member['homephone'] : '');
	$cellphone = 		$mysqli->real_escape_string(isset($member['cellphone']) 		? $member['cellphone'] : '');
	$otherphone = 		$mysqli->real_escape_string(isset($member['otherphone']) 		? $member['otherphone'] : '');
	$email = 			$mysqli->real_escape_string(isset($member['email']) 			? $member['email'] : '');
	$email2 = 			$mysqli->real_escape_string(isset($member['email2']) 			? $member['email2'] : '');
	$reportsc = 		$mysqli->real_escape_string(isset($member['reportsc']) 			? (int)$member['reportsc'] : 0);
	$homeclub = 		$mysqli->real_escape_string(isset($member['homeclub']) 			? $member['homeclub'] : '');
	$skaterlevel = 		$mysqli->real_escape_string(isset($member['skaterlevel']) 		? $member['skaterlevel'] : '');
	$mainprogram = 		$mysqli->real_escape_string(isset($member['mainprogram']) 		? $member['mainprogram'] : '');
	$secondprogram = 	$mysqli->real_escape_string(isset($member['secondprogram'])		? $member['secondprogram'] : '');
	$comments = 		$mysqli->real_escape_string(isset($member['comments']) 			? $member['comments'] : '');
	$birthday = 		$mysqli->real_escape_string(isset($member['birthday']) && $member['birthday'] !='' ? $member['birthday'] : '0000-01-01');

	if ($firstname == '' || $lastname == '') {
		throw new Exception( "Required fields missing. Please enter and submit");
	}

	if (empty($id)) {
		$data['insert'] = true;
		$query = "INSERT INTO cpa_members (id, firstname, lastname) VALUES (NULL, '$firstname', '$lastname')";
	} else {
		$query = "UPDATE cpa_members SET firstname = '$firstname', lastname = '$lastname', skatecanadano = '$skatecanadano',
							initial = '$initial', language = '$language', gender = '$gender', familyrank = '$familyrank', birthday = '$birthday', healthcareno = '$healthcareno',
							healthcareexp = '$healthcareexp', healthcomments = '$healthcomments', qualifications = '$qualifications', address2 = '$address2', address1 = '$address1', town = '$town',
							province = '$province', postalcode = '$postalcode', country = '$country', homephone = '$homephone', cellphone = '$cellphone',
							otherphone = '$otherphone', email = '$email', email2 = '$email2', reportsc = $reportsc, homeclub = '$homeclub', skaterlevel = '$skaterlevel', mainprogram = '$mainprogram', secondprogram = '$secondprogram', comments = '$comments'
							WHERE id = $id";
	}

	if ($mysqli->query($query)) {
		$data['success'] = true;
		if (!empty($id))$data['message'] = 'Member updated successfully.';
		else $data['message'] = 'Member inserted successfully.';
		if (empty($id))$data['id'] = (int) $mysqli->insert_id;
		else $data['id'] = (int) $id;
	} else {
		throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
	}
	return $data;
};

/**
 * This function will handle user deletion
 * @param object $member
 * @throws Exception
 */
function delete_member($mysqli, $id = '') {
	try {
		if (empty($id)) throw new Exception( "Invalid Member.");
		$query = "DELETE FROM cpa_members WHERE id = '$id'";
		if ($mysqli->query($query)) {
			$data['success'] = true;
			$data['message'] = 'Member deleted successfully.';
			echo json_encode($data);
			exit;
		} else {
			throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
		}
	}catch (Exception $e) {
		$data = array();
		$data['success'] = false;
		$data['message'] = $e->getMessage();
		echo json_encode($data);
		exit;
	}
};

/**
 * This function gets list of all members from database
 * [lamoeric 2018/09/11] Added the BINARY clause when comparing qualifications to make the comparison case sensitive
 */
function getAllMembers($mysqli, $filter) {
	try {
		$data = array();
		$data['success'] = null;
		$whereclause = " where 1=1 ";
		if (!empty($filter['firstname'])) {	
			$whereclause .= " and cm.firstname like '" . $filter['firstname'] . "'";
		}
		if (!empty($filter['lastname'])) {
			$whereclause .= " and cm.lastname like '" .  $filter['lastname']  . "'";
		}
		if (!empty($filter['qualification'])) {
			$whereclause .= " and cm.qualifications like BINARY '%" .  $filter['qualification']  . "%'";
		}
		if (!empty($filter['course']) && (empty($filter['onlyactivemembers']) || $filter['onlyactivemembers'] == 0)) {
			$whereclause .= " and cm.id in (select memberid from cpa_sessions_courses_members where sessionscoursesid = '" . $filter['course']  . "')" ;
		}
		if (!empty($filter['course']) && (isset($filter['onlyactivemembers']) && $filter['onlyactivemembers'] == 1)) {
			$whereclause .= " and cm.id in (select memberid from cpa_sessions_courses_members where sessionscoursesid = '" . $filter['course']  . "' and (registrationenddate is null or registrationenddate > now()))" ;
		}
		if (!empty($filter['registration']) && $filter['registration'] == 'REGISTERED') {
			$whereclause .= " and cm.id in (select memberid from cpa_sessions_courses_members where sessionscoursesid in (select id from cpa_sessions_courses where sessionid = (select id from cpa_sessions where active = '1')))" ;
		}
		if (!empty($filter['registration']) && $filter['registration'] == 'NOTREGISTERED') {
			$whereclause .= " and cm.id not in (select memberid from cpa_sessions_courses_members where sessionscoursesid in (select id from cpa_sessions_courses where sessionid = (select id from cpa_sessions where active = '1')))" ;
		}
//		$query = "SELECT id, lastname, firstname, skatecanadano FROM cpa_members order by lastname, firstname LIMIT $nbrows OFFSET $offset";
		$query = "SELECT cm.id, cm.lastname, cm.firstname, cm.skatecanadano
							FROM cpa_members cm ". $whereclause ."
							ORDER by lastname, firstname";
		$result = $mysqli->query($query);

		while ($row = $result->fetch_assoc()) {
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

/**
 * This function gets the bills of a member
 */
function getMemberBills($mysqli, $memberid, $language){
		$data = array();
		$data['data'] = array();
		$query = "SELECT distinct cb.billingdate, cb.*, cs.id sessionid, gettextLabel(cs.label, '$language') sessionlabel
							FROM cpa_bills cb
							JOIN cpa_bills_registrations cbr ON cbr.billid = cb.id
							JOIN cpa_registrations cr ON cr.id = cbr.registrationid AND memberid = $memberid
							JOIN cpa_sessions cs ON cs.id = cr.sessionid
							WHERE cb.relatednewbillid is null
							UNION
							SELECT distinct cb.billingdate, cb.*, null, gettextLabel(ct.label, '$language') sessionlabel
							FROM cpa_bills cb
							JOIN cpa_bills_testsessions cbt ON cbt.billid = cb.id
							JOIN cpa_newtests_sessions_periods_registrations cnspr ON cnspr.id = cbt.testssessionsid AND memberid = $memberid
							JOIN cpa_tests ct ON ct.id = cnspr.testid
							WHERE cb.relatednewbillid is null
							UNION
							SELECT distinct cb.billingdate, cb.*, cs.id sessionid, gettextLabel(cs.label, '$language') sessionlabel
							FROM cpa_bills cb
							JOIN cpa_bills_registrations cbr ON cbr.billid = cb.id
							JOIN cpa_registrations cr ON cr.id = cbr.registrationid AND memberid = $memberid
							JOIN cpa_shows cs ON cs.id = cr.showid
							WHERE cb.relatednewbillid is null
							ORDER BY 1 DESC";
		$result = $mysqli->query( $query );
		while ($row = $result->fetch_assoc()) {
			$data['data'][] = $row;
		}
		$data['success'] = true;
		return $data;
		exit;
};

/**
 * This function gets the details of one member from database
 */
function getMemberDetails($mysqli, $id, $language) {
	try {
		if (empty($id)) throw new Exception( "Invalid User.");
		$query = "SELECT * FROM cpa_members WHERE id = '$id'";
		$result = $mysqli->query($query);
		$data = array();
		while ($row = $result->fetch_assoc()) {
			$row['id'] = (int) $row['id'];
			$row['birthday'] = $row['birthday'] == '0000-00-00' ? '0000-01-01' : $row['birthday'];
			$row['contacts'] 		= getMemberContacts($mysqli, $id, $language)['data'];
			$row['coaches'] 		= getMemberCoaches($mysqli, $id)['data'];
			$row['dances'] 			= getMemberTests($mysqli, $id, 'DANCE', $language)['data'];
			$row['abilities'] 		= getMemberTests($mysqli, $id, 'SKILLS', $language)['data'];
			$row['freestyles'] 		= getMemberTests($mysqli, $id, 'FREE', $language)['data'];
			$row['interpretives'] 	= getMemberTests($mysqli, $id, 'INTER', $language)['data'];
			$row['competitives'] 	= getMemberTests($mysqli, $id, 'COMP', $language)['data'];
			$row['stardances'] 		= getMemberStarTests($mysqli, $id, 'DANCE', $language)['data'];
			$row['starabilities'] 	= getMemberStarTests($mysqli, $id, 'SKILLS', $language)['data'];
			$row['starfreestyles']	= getMemberStarTests($mysqli, $id, 'FREE', $language)['data'];
			$row['starartistics']	= getMemberStarTests($mysqli, $id, 'ARTISTIC', $language)['data'];
			$row['starsynchros']		= getMemberStarTests($mysqli, $id, 'SYNCHRO', $language)['data'];

			$row['summary'] 				= getAllTestSummary($mysqli, $id, $language);

			$row['csbalancetests'] 		= getMemberCanskateTests($mysqli, $id, 'BALANCE')['data'];
			$row['cscontroltests'] 		= getMemberCanskateTests($mysqli, $id, 'CONTROL')['data'];
			$row['csagilitytests'] 		= getMemberCanskateTests($mysqli, $id, 'AGILITY')['data'];
			$row['precstests'] 				= getMemberCanskateTests($mysqli, $id, 'PRESKATE')['data'];

			$row['csbalanceribbons'] 	= getMemberCanskateStageRibbons($mysqli, $id, 'BALANCE')['data'];
			$row['cscontrolribbons'] 	= getMemberCanskateStageRibbons($mysqli, $id, 'CONTROL')['data'];
			$row['csagilityribbons'] 	= getMemberCanskateStageRibbons($mysqli, $id, 'AGILITY')['data'];
			$row['precsribbons'] 			= getMemberCanskateStageRibbons($mysqli, $id, 'PRESKATE')['data'];

			$row['csstagebadges'] 		= getMemberCanskateStageBadges($mysqli, $id, 'BALANCE')['data'];

			$row['activecourses'] 		= getMemberActiveCourses($mysqli, $id, $language)['data'];

			$row['bills'] 						= getMemberBills($mysqli, $id, $language)['data'];

			$tmpBillData    					= getMemberBillInt($mysqli, $id, $language)['data'];
			if (count($tmpBillData) > 0) {
				$row['bill'] = $tmpBillData[0];
			}

			$data['data'][] = $row;
		}
		$data['success'] = true;
		echo json_encode($data);exit;
	}catch (Exception $e) {
		$data = array();
		$data['success'] = false;
		$data['message'] = $e->getMessage();
		echo json_encode($data);
		exit;
	}
};

/**
 * This function gets the contacts of one member from database
 */
//function getMemberContacts($mysqli, $memberid = '') {
//	try {
//		if (empty($memberid)) throw new Exception( "Invalid Member ID.");
//		$query = "SELECT cc.*, cmc.id cmcid, cmc.contacttype, cmc.incaseofemergency
//							FROM cpa_contacts cc
//							JOIN cpa_members_contacts cmc ON cmc.contactid = cc.id
//							WHERE cmc.memberid = '$memberid'";
//		$result = $mysqli->query($query);
//		$data = array();
//		$data['data'] = array();
//		while ($row = $result->fetch_assoc()) {
//			$data['data'][] = $row;
//		}
//		$data['success'] = true;
//		return $data;
//	}catch (Exception $e) {
//		$data = array();
//		$data['success'] = false;
//		$data['message'] = $e->getMessage();
//		return $data;
//	}
//};

/**
 * This function gets the coaches of one member from database
 */
function getMemberCoaches($mysqli, $memberid = '') {
	try {
		if (empty($memberid)) throw new Exception( "Invalid Member ID.");
		$query = "SELECT * FROM cpa_members_coaches WHERE memberid = '$memberid'";
		$result = $mysqli->query($query);
		$data = array();
		$data['data'] = array();
		while ($row = $result->fetch_assoc()) {
			$data['data'][] = $row;
		}
		$data['success'] = true;
		return $data;
	}catch (Exception $e) {
		$data = array();
		$data['success'] = false;
		$data['message'] = $e->getMessage();
		return $data;
	}
};

/**
 * This function gets the tests of one member from database
 */
function getMemberTests($mysqli, $memberid, $type, $language) {
	try {
		if (empty($memberid)) throw new Exception( "Invalid Member ID.");
		$query = "SELECT ct.id as testid, testdate as testdatestr, success, ctd.level, ctd.type, ctd.subtype, ct.subsubtype, cmt.id as id,
								getTextLabel(ct.label, '$language') testlabel, getCodeDescription('testlevels', ctd.level, '$language') testlevellabel,
								getCodeDescription('testsubtypes', ctd.type, '$language') testsubtypelabel
							FROM cpa_tests ct
							left join cpa_members_tests cmt on cmt.testid = ct.id AND (memberid = $memberid || memberid is null)
							join cpa_tests_definitions ctd on ctd.id = ct.testsdefinitionsid
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
	}catch (Exception $e) {
		$data = array();
		$data['success'] = false;
		$data['message'] = $e->getMessage();
		return $data;
	}
};

/**
 * This function gets the new STAR tests of one member from database
 */
function getMemberStarTests($mysqli, $memberid, $type, $language) {
	try {
		if (empty($memberid)) throw new Exception( "Invalid Member ID.");
		$query = "SELECT ct.id as testid, testdate as testdatestr, success, ctd.level, ctd.type, ctd.subtype, ct.subsubtype, cmt.id as id,
								getTextLabel(ct.label, '$language') testlabel, getCodeDescription('testlevels', ctd.level, '$language') testlevellabel,
								getCodeDescription('testsubtypes', ctd.type, '$language') testsubtypelabel
							FROM cpa_tests ct
							left join cpa_members_tests cmt on cmt.testid = ct.id AND (memberid = $memberid || memberid is null)
							join cpa_tests_definitions ctd on ctd.id = ct.testsdefinitionsid
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
	}catch (Exception $e) {
		$data = array();
		$data['success'] = false;
		$data['message'] = $e->getMessage();
		return $data;
	}
};

///**
// * This function gets the canskate ribbons of one member from database
// */
//function getMemberCanskateStageRibbons($mysqli, $memberid, $category) {
//	try {
//		if (empty($memberid)) throw new Exception( "Invalid Member ID.");
//		$query = "SELECT cc.id canskateid, cmcr.ribbondate, cmcr.id
//							FROM cpa_canskate cc
//							left join cpa_members_canskate_ribbons cmcr on cc.id = cmcr.canskateid AND (memberid = $memberid || memberid is null)
//							WHERE cc.category = '$category'
//							ORDER BY cc.stage";
//		$result = $mysqli->query($query);
//		$data = array();
//		$data['data'] = array();
//		while ($row = $result->fetch_assoc()) {
//			$data['data'][] = $row;
//		}
//		$data['success'] = true;
//		return $data;
//	}catch (Exception $e) {
//		$data = array();
//		$data['success'] = false;
//		$data['message'] = $e->getMessage();
//		return $data;
//	}
//};
//
///**
// * This function gets the canskate badges of one member from database
// * (we use BALANCE as a category to get the stages 1 to 6. We could have used any of the 3 categories)
// */
//function getMemberCanskateStageBadges($mysqli, $memberid = '') {
//	try {
//		if (empty($memberid)) throw new Exception( "Invalid Member ID.");
//		$query = "SELECT cc.stage canskatestage, cmcb.badgedate, cmcb.id
//							FROM cpa_canskate cc
//							LEFT JOIN cpa_members_canskate_badges cmcb on cc.stage = cmcb.canskatestage AND (memberid = $memberid || memberid is null)
//							WHERE cc.category = 'BALANCE'
//							ORDER BY cc.stage";
//		$result = $mysqli->query($query);
//		$data = array();
//		$data['data'] = array();
//		while ($row = $result->fetch_assoc()) {
//			$data['data'][] = $row;
//		}
//		$data['success'] = true;
//		return $data;
//	}catch (Exception $e) {
//		$data = array();
//		$data['success'] = false;
//		$data['message'] = $e->getMessage();
//		return $data;
//	}
//};
//
///**
// * This function gets the canskate elements of one member from database
// */
//function getMemberCanskateStageTests($mysqli, $memberid, $category, $stage) {
//	try {
//		if (empty($memberid)) throw new Exception( "Invalid Member ID.");
//		$language = $_POST['language'];
//		$query = "SELECT ccst.id canskatetestid, testdate as testdatestr, success, ccst.canskateid, ccst.type, cmcst.id as id, ccs.stage, getTextLabel(ccst.label, '$language') text
//							FROM cpa_canskate_tests ccst
//							left join cpa_members_canskate_tests cmcst on cmcst.canskatetestid = ccst.id AND (memberid = '$memberid' || memberid is null)
//							left join cpa_canskate ccs on ccs.id = ccst.canskateid
//							WHERE (ccst.type = 'TEST' || ccst.type = 'SUBTEST') and ccs.category = '$category' and ccs.stage = $stage
//							ORDER BY ccst.sequence, cmcst.testdate desc";
//		$result = $mysqli->query($query);
//		$data = array();
//		$data['data'] = array();
//		while ($row = $result->fetch_assoc()) {
//			$data['data'][] = $row;
//		}
//		$data['success'] = true;
//		return $data;
//	}catch (Exception $e) {
//		$data = array();
//		$data['success'] = false;
//		$data['message'] = $e->getMessage();
//		return $data;
//	}
//};

/**
 * This function gets the dance tests of one member from database
 */
function getMemberCanskateTests($mysqli, $memberid, $category) {
	try {
		$data = array();
		$data['data'] = array();
		$data['data']['1'] = getMemberCanskateStageTests($mysqli, $memberid, $category, 1)['data'];
		$data['data']['2'] = getMemberCanskateStageTests($mysqli, $memberid, $category, 2)['data'];
		$data['data']['3'] = getMemberCanskateStageTests($mysqli, $memberid, $category, 3)['data'];
		$data['data']['4'] = getMemberCanskateStageTests($mysqli, $memberid, $category, 4)['data'];
		$data['data']['5'] = getMemberCanskateStageTests($mysqli, $memberid, $category, 5)['data'];
		$data['data']['6'] = getMemberCanskateStageTests($mysqli, $memberid, $category, 6)['data'];
		$data['success'] = true;
		return $data;
	}catch (Exception $e) {
		$data = array();
		$data['success'] = false;
		$data['message'] = $e->getMessage();
		return $data;
	}
};
/**
 * This function gets the contacts of one member from database
 */
function getMemberActiveCoursesDates($mysqli, $memberid, $sessionscoursesid, $registrationenddate, $language) {
	try {
		if (empty($memberid)) throw new Exception( "Invalid Member ID.");
		$query = "SELECT cscd.*, if ('$registrationenddate' != '' AND cscd.coursedate >= '$registrationenddate', 'XXX', if (cscd.canceled, '---', cscp.ispresent)) ispresent
							FROM cpa_sessions_courses_dates cscd
							LEFT JOIN cpa_sessions_courses_presences cscp ON cscp.sessionscoursesdatesid = cscd.id and cscp.memberid = '$memberid'
							WHERE sessionscoursesid = $sessionscoursesid
							ORDER BY coursedate";
		$result = $mysqli->query($query);
		$data = array();
		$data['data'] = array();
		while ($row = $result->fetch_assoc()) {
			$data['data'][] = $row;
		}
		$data['success'] = true;
		return $data;
	}catch (Exception $e) {
		$data = array();
		$data['success'] = false;
		$data['message'] = $e->getMessage();
		return $data;
	}
};

/**
 * This function gets the contacts of one member from database
 */
function getMemberActiveCourses($mysqli, $memberid, $language) {
	try {
		if (empty($memberid)) throw new Exception( "Invalid Member ID.");
		$query = "SELECT cscm.*, csc.coursecode, csc.courselevel, csc.name, csc.fees, getTextLabel(cs.label, '$language') sessionlabel,
							getTextLabel(csc.label, '$language') courselabel,
							( select group_concat(concat(getTextLabel((select label from cpa_arenas where id = arenaid), '$language'),
								IF((iceid is null or iceid = 0), ', ', concat(' (' , getTextLabel((select label from cpa_arenas_ices where id = iceid), '$language'), '), ')),
								getTextLabel((select description from cpa_codetable where ctname = 'days' and code = day), '$language'),
								' - ',
								substr(starttime FROM 1 FOR 5),
								' - ',
								substr(endtime FROM 1 FOR 5))SEPARATOR ', ')
							  from cpa_sessions_courses_schedule where sessionscoursesid = csc.id) schedule
							FROM cpa_sessions_courses_members cscm
							JOIN cpa_sessions_courses csc ON csc.id = cscm.sessionscoursesid
							JOIN cpa_sessions cs ON cs.id = csc.sessionid and cs.active = '1'
							WHERE cscm.memberid = '$memberid'";
		$result = $mysqli->query($query);
		$data = array();
		$data['data'] = array();
		while ($row = $result->fetch_assoc()) {
			$row['dates'] = getMemberActiveCoursesDates($mysqli, $memberid, $row['sessionscoursesid'], $row['registrationenddate'], $language)['data'];;
			$data['data'][] = $row;
		}
		$data['success'] = true;
		return $data;
	}catch (Exception $e) {
		$data = array();
		$data['success'] = false;
		$data['message'] = $e->getMessage();
		return $data;
	}
};

///**
// * This function will handle insert/update/delete of a contact in DB
// * @throws Exception
// */
//function updateEntireContacts($mysqli, $memberid, $contacts) {
//	try {
//		$data = array();
//		$data['insert'] = 0;
//		$data['update'] = 0;
//		for ($x = 0; $x < count($contacts); $x++) {
//			$id = 								$mysqli->real_escape_string(isset($contacts[$x]['id']) 								? $contacts[$x]['id'] : '');
//			$cmcid = 							$mysqli->real_escape_string(isset($contacts[$x]['cmcid']) 						? $contacts[$x]['cmcid'] : '');
//			$firstname = 					$mysqli->real_escape_string(isset($contacts[$x]['firstname']) 				? $contacts[$x]['firstname'] : '');
//			$lastname = 					$mysqli->real_escape_string(isset($contacts[$x]['lastname']) 					? $contacts[$x]['lastname'] : '');
//			$homephone = 					$mysqli->real_escape_string(isset($contacts[$x]['homephone']) 				? $contacts[$x]['homephone'] : '');
//			$cellphone = 					$mysqli->real_escape_string(isset($contacts[$x]['cellphone']) 				? $contacts[$x]['cellphone'] : '');
//			$officephone = 				$mysqli->real_escape_string(isset($contacts[$x]['officephone']) 			? $contacts[$x]['officephone'] : '');
//			$officeext = 					$mysqli->real_escape_string(isset($contacts[$x]['officeext']) 				? $contacts[$x]['officeext'] : '');
//			$contacttype = 				$mysqli->real_escape_string(isset($contacts[$x]['contacttype']) 			? $contacts[$x]['contacttype'] : '');
//			$incaseofemergency = 	$mysqli->real_escape_string(isset($contacts[$x]['incaseofemergency'])	? $contacts[$x]['incaseofemergency'] : '');
//			$email = 							$mysqli->real_escape_string(isset($contacts[$x]['email'])							? $contacts[$x]['email'] : '');
//			$status = 						$mysqli->real_escape_string(isset($contacts[$x]['status'])						? $contacts[$x]['status'] : '');
//
//			// We now need to check if contact is new or if only the relation between contact and member is new.
//			// If contact is new, contact must be inserted else update it
//			if ($id == '') {
//				$query = "INSERT into cpa_contacts
//									(firstname, lastname, homephone, cellphone, officephone, officeext, email)
//									VALUES ('$firstname', '$lastname', '$homephone', '$cellphone', '$officephone', '$officeext', '$email')";
//				if ($mysqli->query($query)) {
//					$id = (int) $mysqli->insert_id;
//				} else {
//					throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
//				}
//			} else {
//				$query = "update cpa_contacts
//									set firstname = '$firstname', lastname = '$lastname', homephone = '$homephone', cellphone = '$cellphone',
//									officephone = '$officephone', officeext = '$officeext', email = '$email'
//									where id = '$id'";
//				if ($mysqli->query($query)) {
//				} else {
//					throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
//				}
//			}
//
//			if ($status == 'Modified' or $status == 'New') {
//				if ($cmcid == '') {
//					$query = "INSERT into cpa_members_contacts
//										(memberid, contactid, contacttype, incaseofemergency)
//										VALUES ($memberid, $id, '$contacttype', '$incaseofemergency')";
//					if ($mysqli->query($query)) {
//						$data['insert']++;
//						$contacts[$x]['cmcid'] = (int) $mysqli->insert_id;
//					} else {
//						throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
//					}
//				} else {
//					$query = "update cpa_members_contacts
//										set contacttype = '$contacttype', incaseofemergency = '$incaseofemergency'
//										where id = $cmcid";
//					if ($mysqli->query($query)) {
//						$data['update']++;
//					} else {
//						throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
//					}
//				}
//			}
//
//			// We should ony delete the relation and keep the contact alive
//			if ($mysqli->real_escape_string(isset($contacts[$x]['status'])) and $contacts[$x]['status'] == 'Deleted') {
//				$query = "DELETE FROM cpa_members_contacts WHERE id = $cmcid";
//				if ($mysqli->query($query)) {
//				} else {
//					throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
//				}
//			}
//		}
//		$data['success'] = true;
//		return $data;
//	}catch (Exception $e) {
//		$data = array();
//		$data['success'] = false;
//		$data['message'] = $e->getMessage();
//		return $data;
//	}
//};

/**
 * This function will handle insert/update/delete of a coach/partner in DB
 * @throws Exception
 */
function updateEntireCoaches($mysqli, $memberid, $coaches) {
	try {
		$data = array();
		for ($x = 0; $x < count($coaches); $x++) {
			$id = 				$mysqli->real_escape_string(isset($coaches[$x]['id']) 							? $coaches[$x]['id'] : '');
			$coachtype = 	$mysqli->real_escape_string(isset($coaches[$x]['coachtype']) 				? $coaches[$x]['coachtype'] : '');
			$coachid = 		$mysqli->real_escape_string(isset($coaches[$x]['coachid']) 					? $coaches[$x]['coachid'] : '');

			if ($mysqli->real_escape_string(isset($coaches[$x]['status'])) and $coaches[$x]['status'] == 'New') {
				$query = "INSERT into cpa_members_coaches	(memberid, coachtype, coachid)
									VALUES ('$memberid', '$coachtype', '$coachid')";
				if (!$mysqli->query($query)) {
					throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
				}
			}

			if ($mysqli->real_escape_string(isset($coaches[$x]['status'])) and $coaches[$x]['status'] == 'Modified') {
				$query = "update cpa_members_coaches
									set coachtype = '$coachtype', coachid = '$coachid'
									where id = '$id'";
				if (!$mysqli->query($query)) {
					throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
				}
			}

			if ($mysqli->real_escape_string(isset($coaches[$x]['status'])) and $coaches[$x]['status'] == 'Deleted') {
//				$id = $mysqli->real_escape_string(isset($contacts[$x]['id'])	? $contacts[$x]['id'] : '');
				$query = "DELETE FROM cpa_members_coaches WHERE id = '$id'";
				if (!$mysqli->query($query)) {
					throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
				}
			}
		}
		$data['success'] = true;
		return $data;
	}catch (Exception $e) {
		$data = array();
		$data['success'] = false;
		$data['message'] = $e->getMessage();
		return $data;
	}
};

/**
 * This function will handle insert/update/delete of a dance test in DB
 * @throws Exception
 */
function updateEntireTests($mysqli, $memberid, $tests) {
	try {
		$data = array();
		for ($x = 0; $x < count($tests); $x++) {
			$id = 			$mysqli->real_escape_string(isset($tests[$x]['id']) 						? $tests[$x]['id'] : '');
			$testid = 	$mysqli->real_escape_string(isset($tests[$x]['testid']) 				? $tests[$x]['testid'] : '');
//			$testdate = $mysqli->real_escape_string(isset($tests[$x]['testdate']) 		? $tests[$x]['testdate'] : '');
			$testdate = $mysqli->real_escape_string(isset($tests[$x]['testdatestr']) 	? $tests[$x]['testdatestr'] : '');
			$success = 	$mysqli->real_escape_string(isset($tests[$x]['success']) 			? (int)$tests[$x]['success'] : 0);

			if ($mysqli->real_escape_string(isset($tests[$x]['status'])) and $tests[$x]['status'] == 'New') {
				$query = "INSERT into cpa_members_tests	(memberid, testid, testdate, success)
									VALUES ('$memberid', '$testid', '$testdate', $success)";
				if (!$mysqli->query($query)) {
					throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
				}
			}

			if ($mysqli->real_escape_string(isset($tests[$x]['status'])) and $tests[$x]['status'] == 'Modified') {
				$query = "update cpa_members_tests
									set testdate = '$testdate', success = $success
									where id = '$id'";
				if (!$mysqli->query($query)) {
					throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
				}
			}

			if ($mysqli->real_escape_string(isset($tests[$x]['status'])) and $tests[$x]['status'] == 'Deleted') {
				$query = "DELETE FROM cpa_members_tests WHERE id = '$id'";
				if (!$mysqli->query($query)) {
					throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
				}
			}
		}
		$data['success'] = true;
		return $data;
	}catch (Exception $e) {
		$data = array();
		$data['success'] = false;
		$data['message'] = $e->getMessage();
		return $data;
	}
};

function updateOneCanskateTests($mysqli, $memberid, $test) {
	$id = 							$mysqli->real_escape_string(isset($test['id']) 								? $test['id'] : '');
	$canskatetestid = 	$mysqli->real_escape_string(isset($test['canskatetestid']) 		? $test['canskatetestid'] : '');
	$testdate = 				$mysqli->real_escape_string(isset($test['testdatestr']) 			? $test['testdatestr'] : '0000-01-01');
	$success = 					$mysqli->real_escape_string(isset($test['success']) 					? (int)$test['success'] : 0);

	if ($id == '' && $success == 1) {
		$query = "INSERT into cpa_members_canskate_tests (memberid, canskatetestid, testdate, success)
							VALUES ($memberid, $canskatetestid, '$testdate', $success)";
		if (!$mysqli->query($query)) {
			throw new Exception('updateOneCanskateTests - ' . $mysqli->sqlstate.' - '. $mysqli->error);
		}
	}

	if ($mysqli->real_escape_string($id != '')) {
		$query = "update cpa_members_canskate_tests
							set testdate = '$testdate', success = $success
							where id = $id";
		if (!$mysqli->query($query)) {
			throw new Exception('updateOneCanskateTests - ' . $mysqli->sqlstate.' - '. $mysqli->error);
		}
	}
	$data['success'] = true;
	return $data;
};

/**
 * This function will handle insert/update/delete of a canskate test in DB
 * @throws Exception
 */
function updateEntireCanskateTests($mysqli, $memberid, $testss) {
	$data = array();
	for ($y = 1; $y < 7; $y++) {
		$tests = $testss[$y];
		for ($x = 0; $x < count($tests); $x++) {
			$data = updateOneCanskateTests($mysqli, $memberid, $tests[$x]);
		}
	}
	$data['success'] = true;
	return $data;
};

/**
 * This function will handle insert/update/delete of canskate ribbons in DB
 * @throws Exception
 */
function updateEntireCanskateRibbons($mysqli, $memberid, $tests) {
	$data = array();
	$query = null;
	for ($x = 0; $x < count($tests); $x++) {
		$id = 							$mysqli->real_escape_string(isset($tests[$x]['id']) 						? $tests[$x]['id'] : '');
		$canskateid = 			$mysqli->real_escape_string(isset($tests[$x]['canskateid']) 		? $tests[$x]['canskateid'] : '');
		$ribbondate = 			$mysqli->real_escape_string(isset($tests[$x]['ribbondatestr'])	? $tests[$x]['ribbondatestr'] : '');

		if (isset($id) && empty($id)) {
			if (isset($ribbondate) && !empty($ribbondate) && $ribbondate != '0000-00-00') {
				$query = "INSERT into cpa_members_canskate_ribbons (memberid, canskateid, ribbondate)
									VALUES ($memberid, $canskateid, '$ribbondate')";
				if (!$mysqli->query($query)) {
					throw new Exception('updateEntireCanskateRibbons - ' . $mysqli->sqlstate.' - '. $mysqli->error);
				}
			}
		}

		if (!empty($id)) {
			if (!isset($ribbondate) || empty($ribbondate) || $ribbondate == '0000-00-00') {
					$query = "DELETE FROM cpa_members_canskate_ribbons WHERE id = $id ";
			} else {
				$query = "update cpa_members_canskate_ribbons
									set ribbondate = '$ribbondate'
									where id = $id";
			}
			if (!$mysqli->query($query)) {
				throw new Exception('updateEntireCanskateRibbons - ' . $mysqli->sqlstate.' - '. $mysqli->error);
			}
		}
	}
	$data['success'] = true;
	return $data;
};

/**
 * This function will handle insert/update/delete of canskate badges in DB
 * @throws Exception
 */
function updateEntireCanskateBadges($mysqli, $memberid, $tests) {
	$data = array();
	for ($x = 0; $x < count($tests); $x++) {
		$id = 						$mysqli->real_escape_string(isset($tests[$x]['id']) 						? $tests[$x]['id'] : '');
		$canskatestage = 	$mysqli->real_escape_string(isset($tests[$x]['canskatestage']) 	? $tests[$x]['canskatestage'] : '');
		$badgedate = 			$mysqli->real_escape_string(isset($tests[$x]['badgedatestr'])		? $tests[$x]['badgedatestr'] : '');

		if (isset($id) && empty($id)) {
			if (isset($badgedate) && !empty($badgedate) && $badgedate != '0000-00-00') {
				$query = "INSERT into cpa_members_canskate_badges (memberid, canskatestage, badgedate)
									VALUES ($memberid, $canskatestage, '$badgedate')";
				if (!$mysqli->query($query)) {
					throw new Exception('updateEntireCanskateBadges - ' . $mysqli->sqlstate.' - '. $mysqli->error);
				}
			}
		}

		if (!empty($id)) {
			if (!isset($badgedate) || empty($badgedate) || $badgedate == '0000-00-00') {
					$query = "DELETE FROM cpa_members_canskate_badges WHERE id = $id ";
			} else {
				$query = "update cpa_members_canskate_badges
									set badgedate = '$badgedate'
									where id = $id";
			}
			if (!$mysqli->query($query)) {
				throw new Exception('updateEntireCanskateBadges - ' . $mysqli->sqlstate.' - '. $mysqli->error);
			}
		}
	}
	$data['success'] = true;
	return $data;
};
function invalidRequest($type) {
	$data = array();
	$data['success'] = false;
	$data['type'] = $type;
	$data['message'] = "Invalid request.";
	echo json_encode($data);
	exit;
};

?>
