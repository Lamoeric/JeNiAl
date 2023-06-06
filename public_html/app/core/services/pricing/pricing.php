<?php
/*
Author : Eric Lamoureux
*/
require_once('../../../../../private/'. $_SERVER['HTTP_HOST'].'/include/config.php');
require_once('../../../../include/nocache.php');

$thisfilename = 'pricing';
if (isset($_POST['type']) && !empty(isset($_POST['type']))) {
	$type = $_POST['type'];

	switch ($type) {
		case "countFamilyMembersRegistrations":
			countFamilyMembersRegistrations($mysqli, $_POST['registration'], $_POST['language']);
			break;
		default:
			invalidRequest();
	}
} else {
	invalidRequest();
};

function countFamilyMembersRegistrations($mysqli, $registration, $language) {
  // try{
  //   $data = array();
  // 	$data['success'] = true;
  //   $memberid = $registration['memberid'];
  //   $sessionid = $registration['sessionid'];
  // 	$query = "SELECT count(*) nb
  //   FROM (
  //         SELECT distinct cr.*
  //         from cpa_registrations cr
  //         join cpa_members_contacts cmc ON cmc.memberid = cr.memberid
  //         join cpa_members_contacts cmc2 ON cmc2.contactid = cmc.contactid
  //         where cr.sessionid = $sessionid
  //         and (cr.status = 'ACCEPTED' or cr.status = 'DRAFT-R' or cr.status = 'PRESENTED-R')
	// 				and cr.relatedoldregistrationid = 0
  //         and cr.memberid != $memberid
  //         and cmc2.memberid = $memberid
  //       ) a";
  // 	$result = $mysqli->query($query);
  //   $row = $result->fetch_assoc();
  //   $data['nb'] = $row['nb'];
  // 	// while ($row = $result->fetch_assoc()) {
  // 	// 	$schedule = $row['schedule'];
  // 	// 	return $schedule;
  // 	// }
  //   echo json_encode($data);
	// 	exit;
  // }catch (Exception $e) {
  //   $data = array();
  //   $data['success'] = false;
  //   $data['message'] = $e->getMessage();
  //   echo json_encode($data);
  //   exit;
  // }
}

function invalidRequest() {
	$data = array();
	$data['success'] = false;
	$data['message'] = "Invalid request.";
	echo json_encode($data);
	exit;
};

?>
