<?php
/*
Author : Eric Lamoureux
*/
require_once('../../../private/'. $_SERVER['HTTP_HOST'].'/include/config.php');
require_once('../../include/nocache.php');

if (isset($_POST['type']) && !empty(isset($_POST['type']))) {
	$type = $_POST['type'];

	switch ($type) {
		case "getAllDashboardInfo":
			getAllDashboardInfo($mysqli, $_POST['sessionid'], $_POST['language']);
			break;
		default:
			invalidRequest();
	}
} else {
	invalidRequest();
};


/**
 * This function gets list of all sessions from database
 */
function getAllDashboardInfo($mysqli, $sessionid, $language) {
	$data = array();
	try{
		// Summary for all courses
		$query = "SELECT count(*) numberofskaterscourses,
							       (SELECT sum(maxnumberskater) FROM cpa_sessions_courses csc2 WHERE csc2.sessionid = $sessionid) totalnbofplaces
							FROM cpa_sessions_courses csc
							JOIN cpa_sessions_courses_members cscm ON csc.id = cscm.sessionscoursesid AND cscm.membertype = 3
							JOIN cpa_courses cc ON cc.code = csc.coursecode
							WHERE csc.sessionid = $sessionid
							AND cc.acceptregistrations = 1
							ORDER BY code";
		$result = $mysqli->query($query);
		while ($row = $result->fetch_assoc()) {
			$data['data']['summaryskaterscourses']['numberofskaterscourses'] = $row['numberofskaterscourses'];
			$data['data']['summaryskaterscourses']['totalnbofplaces'] = $row['totalnbofplaces'];
			$data['data']['summaryskaterscourses']['nbofplacesleft'] = (int)$row['totalnbofplaces'] - (int)$row['numberofskaterscourses'];
		}
		// Per courses
		$query = "SELECT cc.code, csc.name, getTextLabel(csc.label, '$language') courselabel, csc.maxnumberskater, getTextLabel(ccl.label, '$language') courselevellabel,
											(SELECT count(*) FROM cpa_sessions_courses_members cscm WHERE cscm.sessionscoursesid = csc.id AND cscm.membertype = 3) numberofskaters
							FROM cpa_sessions_courses csc
							JOIN cpa_courses cc ON cc.code = csc.coursecode
							LEFT JOIN cpa_courses_levels ccl ON ccl.coursecode = cc.code and ccl.code = csc.courselevel
							WHERE sessionid = $sessionid
							AND cc.acceptregistrations = 1
							ORDER BY name";
		$result = $mysqli->query($query);
		while ($row = $result->fetch_assoc()) {
			$data['data']['courses'][] = $row;
		}
		// Course codes
		$query = "SELECT DISTINCT cc.code, getTextLabel(cc.label, '$language') codelabel
							FROM cpa_sessions_courses csc
							JOIN cpa_courses cc ON cc.code = csc.coursecode
							LEFT JOIN cpa_courses_levels ccl ON ccl.coursecode = cc.code and ccl.code = csc.courselevel
							WHERE sessionid = $sessionid
							AND cc.acceptregistrations = 1
							ORDER BY code";
		$result = $mysqli->query($query);
		while ($row = $result->fetch_assoc()) {
			$data['data']['coursecodes'][] = $row;
		}
		// Summary per course
		$queryTmp = "SELECT code, codelabel, sum(numberofskaters) numberofskaters, maxnumberskater
							FROM
							(SELECT cc.code,  getTextLabel(cc.label, '$language') codelabel, (SELECT count(*) FROM cpa_sessions_courses_members cscm WHERE cscm.sessionscoursesid = csc.id AND cscm.membertype = 3) numberofskaters, (SELECT sum(maxnumberskater) FROM cpa_sessions_courses csc2 WHERE csc2.sessionid = csc.sessionid) maxnumberskater
							FROM cpa_sessions_courses csc
							JOIN cpa_courses cc ON cc.code = csc.coursecode
							WHERE csc.sessionid = $sessionid
							AND cc.acceptregistrations = 1) A
							GROUP BY code, codelabel, maxnumberskater";
		$query = $queryTmp	. " ORDER BY code";
		$result = $mysqli->query($query);
		while ($row = $result->fetch_assoc()) {
			$data['data']['summarycourses'][] = $row;
		}
		$query = $queryTmp	. " ORDER BY numberofskaters DESC";
		$result = $mysqli->query($query);
		while ($row = $result->fetch_assoc()) {
			$data['data']['summaryskaterscoursespertype']['data'][] = $row['numberofskaters'];
			$data['data']['summaryskaterscoursespertype']['labels'][] = $row['codelabel'];
		}
		$data['data']['summaryskaterscoursespertype']['totalnbofplaces'] = $data['data']['summaryskaterscourses']['totalnbofplaces'];
		$data['data']['summaryskaterscoursespertype']['numberofskaterscourses'] = $data['data']['summaryskaterscourses']['numberofskaterscourses'];
		// Summary per course, previous session
		$query = "SELECT distinct code, getTextLabel(cc.label, '$language') codelabel,
					      (SELECT count(*)
					       FROM cpa_sessions_courses_members cscm
					       JOIN cpa_sessions_courses csc2 ON csc2.id = cscm.sessionscoursesid
					       JOIN cpa_courses cc2 ON cc2.code = csc2.coursecode
					       WHERE cc2.code = cc.code
					       AND cscm.membertype = 3
					       AND csc2.sessionid = csc.sessionid) numberofskaters,
					      (SELECT sum(maxnumberskater)
					       FROM cpa_sessions_courses csc3
					       WHERE csc3.sessionid = csc.sessionid) maxnumberskaters,
					      (SELECT count(*)
					       FROM cpa_sessions_courses_members cscm2
					       JOIN cpa_sessions_courses csc4 ON csc4.id = cscm2.sessionscoursesid
					       JOIN cpa_courses cc3 ON cc3.code = csc4.coursecode
					       WHERE cc3.code = cc.code
					       AND cscm2.membertype = 3
					       AND csc4.sessionid = (SELECT previoussessionid FROM cpa_sessions WHERE id = csc.sessionid)) numberofskatersprevious,
					      (SELECT sum(maxnumberskater)
					       FROM cpa_sessions_courses csc5
					       WHERE csc5.sessionid = (SELECT previoussessionid FROM cpa_sessions WHERE id = csc.sessionid)) maxnumberskatersprevious,
					      (SELECT getTextLabel(cs.label, '$language') FROM cpa_sessions cs WHERE cs.id = csc.sessionid) sessionlabel,
					      (SELECT getTextLabel(cs.label, '$language') FROM cpa_sessions cs WHERE cs.id = (SELECT previoussessionid FROM cpa_sessions WHERE id = csc.sessionid)) sessionlabelprevious
							FROM cpa_sessions_courses csc
							JOIN cpa_courses cc ON cc.code = csc.coursecode
							WHERE csc.sessionid = $sessionid
							AND cc.acceptregistrations = 1
							ORDER BY numberofskaters DESC";
		$result = $mysqli->query($query);
		$tempdata = array();
		$tempdata2 = array();
		$serie1 = '';
		$serie2 = '';
		while ($row = $result->fetch_assoc()) {
			$serie1 = $row['sessionlabel'];
			$serie2 = $row['sessionlabelprevious'];
			$tempdata[]  = $row['numberofskaters'];
			$tempdata2[] = $row['numberofskatersprevious'];
			$data['data']['summaryskaterscoursespertypecomp']['labels'][] = $row['codelabel'];
		}
		$data['data']['summaryskaterscoursespertypecomp']['data'][] = $tempdata;
		$data['data']['summaryskaterscoursespertypecomp']['data'][] = $tempdata2;
		$data['data']['summaryskaterscoursespertypecomp']['series'][] = $serie1;
		$data['data']['summaryskaterscoursespertypecomp']['series'][] = $serie2;
		// returning skaters
		$query = "SELECT count(DISTINCT memberid) numberreturningskaters,
							      (SELECT count(distinct memberid) numberofskaters
							      FROM cpa_sessions_courses_members cscm
							      JOIN cpa_sessions_courses csc ON csc. id = cscm.sessionscoursesid AND csc.sessionid = $sessionid
							      WHERE cscm.membertype = 3) numberofskaters
							FROM cpa_sessions_courses_members csm
							JOIN cpa_sessions_courses csc ON csc.id = csm.sessionscoursesid
							WHERE sessionid = $sessionid
							AND csm.membertype = 3
							AND memberid IN (SELECT memberid
							                  FROM cpa_sessions_courses_members csm
							                  JOIN cpa_sessions_courses csc ON csc.id = csm.sessionscoursesid
							                  WHERE sessionid = (SELECT previoussessionid FROM cpa_sessions WHERE id = $sessionid)
							                  AND csm.membertype = 3
							                )";
		$result = $mysqli->query($query);
		$row = $result->fetch_assoc();
		$data['data']['returningskaters']['numberreturningskaters'] = $row['numberreturningskaters'];
		$data['data']['returningskaters']['numberofskaters'] = $row['numberofskaters'];
		$data['data']['returningskaters']['numbernewskaters'] = (int)$row['numberofskaters'] - (int)$row['numberreturningskaters'];
		// skaters per nb of courses selected
		$query = "SELECT cnt numberofcourses, count(*) numberofskaters
							FROM (SELECT memberid, count(*) cnt
										FROM cpa_sessions_courses_members cscm
										JOIN cpa_sessions_courses csc ON csc. id = cscm.sessionscoursesid AND csc.sessionid = $sessionid
										WHERE cscm.membertype = 3
										GROUP BY memberid) A
							GROUP BY cnt
							ORDER BY cnt";
		$result = $mysqli->query($query);
		while ($row = $result->fetch_assoc()) {
			$data['data']['skaterspernbofcourses']['data'][] = $row['numberofskaters'];
			$data['data']['skaterspernbofcourses']['labels'][] = $row['numberofcourses'];
		}
		// financial revenue per course code
		$query = "SELECT cc.code, sum(amount) amount, getTextLabel(cc.label, '$language') codelabel
							FROM cpa_bills_details cbd
							JOIN cpa_bills cb ON cb.id = cbd.billid AND (cb.relatednewbillid is null or cb.relatednewbillid = 0)
							JOIN cpa_registrations cr ON cr.id = cbd.registrationid AND (cr.relatednewregistrationid is null or cr.relatednewregistrationid = 0)
							JOIN cpa_sessions_courses csc ON csc.id = cbd.itemid
							JOIN cpa_courses cc ON cc.code = csc.coursecode
							WHERE cbd.itemtype = 'COURSE'
							AND cr.sessionid = $sessionid
							GROUP BY cc.code
							ORDER BY sum(amount) DESC";
		$result = $mysqli->query($query);
		$data['data']['financialrevenuespertype']['totalrevenues'] = 0;
		while ($row = $result->fetch_assoc()) {
			$data['data']['financialrevenuespertype']['data'][] = $row['amount'];
			$data['data']['financialrevenuespertype']['labels'][] = $row['codelabel'];
			$data['data']['financialrevenuespertype']['totalrevenues'] += $row['amount'];
		}
		// financial revenue per session
		$query = "SELECT cs.id, (SELECT sum(a.paidamount)
                FROM (
                        SELECT cb.paidamount, cb.id, (SELECT cs.id FROM cpa_sessions cs
                                                JOIN cpa_registrations cr ON cr.sessionid = cs.id
                                                JOIN cpa_bills_registrations cbr ON  cbr.registrationid = cr.id
                                                where cbr.billid = cb.id limit 1) sessionid
                        FROM cpa_bills cb
                        WHERE cb.relatednewbillid is null
                        ORDER BY sessionid) a
                WHERE a.sessionid = cs.id
                GROUP BY a.sessionid) paidamount,
              (SELECT sum(a.totalamount)
                FROM (
                        SELECT cb.totalamount, cb.id, (SELECT cs.id FROM cpa_sessions cs
                                                JOIN cpa_registrations cr ON cr.sessionid = cs.id
                                                JOIN cpa_bills_registrations cbr ON  cbr.registrationid = cr.id
                                                where cbr.billid = cb.id limit 1) sessionid
                        FROM cpa_bills cb
                        WHERE cb.relatednewbillid is null
                        ORDER BY sessionid) a
                WHERE a.sessionid = cs.id
                GROUP BY a.sessionid) totalamount,
                getTextLabel(cs.label, 'fr-ca') sessionlabel
						      FROM cpa_sessions cs";
		$result = $mysqli->query($query);
		$data['data']['financialrevenuespersession']['totalrevenues'] = 0;
		while ($row = $result->fetch_assoc()) {
			$data['data']['financialrevenuespersession']['data'][] = $row['totalamount'];
			$data['data']['financialrevenuespersession']['data2'][] = $row['paidamount']*-1;
			$data['data']['financialrevenuespersession']['labels'][] = $row['sessionlabel'];
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


function invalidRequest() {
	$data = array();
	$data['success'] = false;
	$data['message'] = "Invalid request.";
	echo json_encode($data);
	exit;
};

?>
