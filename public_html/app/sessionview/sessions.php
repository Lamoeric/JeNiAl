<?php
/*
Author : Eric Lamoureux
*/
require_once('../../../private/'. $_SERVER['HTTP_HOST'].'/include/config.php');
require_once('../../include/nocache.php');
require_once('../../include/invalidrequest.php');

if (isset($_POST['type']) && !empty(isset($_POST['type']))) {
	$type = $_POST['type'];

	switch ($type) {
		case "updateEntireSession":
			updateEntireSession($mysqli, json_decode($_POST['session'], true));
			break;
		case "insert_session":
			insert_session($mysqli, $_POST['session']);
			break;
		case "delete_session":
			delete_session($mysqli, json_decode($_POST['session'], true));
			break;
		case "getAllSessions":
			getAllSessions($mysqli);
			break;
		case "getSessionDetails":
			getSessionDetails($mysqli, $_POST['id'], $_POST['language']);
			break;
		case "insertCourseDate":
			insertCourseDate($mysqli, $_POST['coursedate'], $_POST['language']);
			break;
		case "getCourseLevels":
			getCourseLevels($mysqli, $_POST['code'], $_POST['language']);
			break;
		case "activateSession":
			activateSession($mysqli, $_POST['sessionid']);
			break;
		case "copySession":
			copySession($mysqli, $_POST['sessionid'], $_POST['copyicetimes'], $_POST['copycourses'], $_POST['copycharges'], $_POST['copyrules']);
			break;
		default:
			invalidRequest();
	}
} else {
	invalidRequest();
};

/**
 * Try to get the course codes for the filter
 * @param integer $sessionid	The id of the session from cpa_sessions
 * @param string $language	The language string, 'fr-ca' or 'en-ca'
 *  
 */
 function getSessionCourseCodes($mysqli, $sessionid, $language) {
	$data = array();
	$data['data'] = array();
	$query = "select distinct cc.code, getTextLabel(cc.label, '$language') coursecodelabel
						from cpa_sessions_courses csc
						join cpa_courses cc ON cc.code = csc.coursecode
						WHERE csc.sessionid = $sessionid
						AND cc.active = 1
						AND cc.acceptregistrations = 1
						order by cc.code";
	$result = $mysqli->query($query);
	while ($row = $result->fetch_assoc()) {
		$data['data'][] = $row;
	}
	$data['success'] = true;
	return $data;
};

/**
 * This function will handle the copy session operation.
 * @param integer $sessionid	The id of the session from cpa_sessions
 * @throws Exception
 */
function copySession($mysqli, $sessionid, $copyicetimes, $copycourses, $copycharges, $copyrules) {
	try {
		$data = array();
		$id = "";
		$query = "INSERT INTO cpa_sessions(id, name, label, startdate, enddate, coursesstartdate, coursesenddate, reimbursementdate, active)
					SELECT null, 'name/nom', create_systemtext(getEnglishTextLabel(label), getFrenchTextLabel(label)), startdate, enddate, coursesstartdate, coursesenddate, reimbursementdate, 0
					FROM cpa_sessions WHERE id = $sessionid";
		if ($mysqli->query($query)) {
			$id = (int) $mysqli->insert_id;
			$query = "UPDATE cpa_sessions SET name = concat(name, ' ',  $id) WHERE id = $id";
			if ($mysqli->query($query)) {
				if ($copyicetimes == false && $copycourses == false && $copycharges == false) {
					$data['success'] = true;
				} else {
					if ($copyicetimes == true) {
						$query = "INSERT INTO cpa_sessions_icetimes(id, sessionid, arenaid, day, starttime, endtime, duration, iceid, comment)
									SELECT null, $id, arenaid, day, starttime, endtime, duration, iceid, comment
									FROM cpa_sessions_icetimes WHERE sessionid = $sessionid";
						if ($mysqli->query($query)) {
							$data['success'] = true;
						} else {
		 			 		throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
		 			 	}
					}
					if ($copycharges == true) {
						$query = "INSERT INTO cpa_sessions_charges(id, sessionid, chargecode, amount)
									SELECT null, $id, chargecode, amount
									FROM cpa_sessions_charges WHERE sessionid = $sessionid";
						if ($mysqli->query($query)) {
							$data['success'] = true;
						} else {
		 			 		throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
		 			 	}
	 			 	}
					if ($copyrules == true) {
						$query = "INSERT INTO cpa_sessions_rules(id, sessionid, language, rules)
									SELECT null, $id, language, rules
									FROM cpa_sessions_rules WHERE sessionid = $sessionid";
						if ($mysqli->query($query)) {
							$data['success'] = true;
						} else {
		 			 		throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
		 			 	}
	 			 	}
					if ($copycourses == true) {
						$query = "SELECT id, coursecode, courselevel, name, label, fees, minnumberskater, maxnumberskater, availableonline 
									FROM cpa_sessions_courses 
									WHERE sessionid = $sessionid";
						$result = $mysqli->query($query);
						while ($row = $result->fetch_assoc()) {
							$sessionscoursesid = $row['id'];
							$label = $row['label'];
							$coursecode = $row['coursecode'];
							$courselevel = $row['courselevel'];
							$name = $row['name'];
							$fees = $row['fees'];
							$minnumberskater = $row['minnumberskater'];
							$maxnumberskater = $row['maxnumberskater'];
							$availableonline = $row['availableonline'];
							$query = "INSERT INTO cpa_sessions_courses(id, sessionid, coursecode, courselevel, name, fees, minnumberskater, maxnumberskater, availableonline, datesgenerated, label)
										VALUES (null, $id, '$coursecode', '$courselevel', '$name', $fees, $minnumberskater, $maxnumberskater, $availableonline, 0, create_systemtext(getEnglishTextLabel($label), getFrenchTextLabel($label)))";
							if ($mysqli->query($query)) {
								$newsessionscoursesid = (int) $mysqli->insert_id;

								$query = "INSERT INTO cpa_sessions_courses_schedule(id, sessionscoursesid, arenaid, iceid, day, starttime, endtime, duration)
											SELECT null, $newsessionscoursesid, arenaid, iceid, day, starttime, endtime, duration
											FROM cpa_sessions_courses_schedule WHERE sessionscoursesid = $sessionscoursesid";
								if ($mysqli->query($query)) {
								} else {
									throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
								}
							} else {
			 			 		throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
			 			 	}
						}
					}
				}
			} else {
				throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
			}
		} else {
			throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
		}
		echo json_encode($data);
		exit;
	} catch (Exception $e) {
		$data = array();
		$data['success'] = false;
		$data['message'] = $e->getMessage();
		echo json_encode($data);
		exit;
	}
};

/**
 * This function will handle the activation of the session.
 * There can only be one active session at all time.
 * @param integer $sessionid	The id of the session from cpa_sessions
 * @throws Exception
 */
function activateSession($mysqli, $sessionid) {
	try {
		$data = array();
		$query = "UPDATE cpa_sessions SET active = 0";
		if ($mysqli->query($query)) {
			$query = "UPDATE cpa_sessions SET active = 1 WHERE id = '$sessionid'";
			if ($mysqli->query($query)) {
				$data['success'] = true;
			} else {
				throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
			}
		} else {
			throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
		}
		echo json_encode($data);
		exit;
	} catch (Exception $e) {
		$data = array();
		$data['success'] = false;
		$data['message'] = $e->getMessage();
		echo json_encode($data);
		exit;
	}
};

/**
 * This function will handle insertion of a new icetime in DB
 * @param integer $sessionid	The id of the session from cpa_sessions
 * @throws Exception
 */
function updateEntireIcetimes($mysqli, $sessionid, $icetimes) {
	try {
		$data = array();
		for ($x = 0; $x < count($icetimes); $x++) {
			$id = 				$mysqli->real_escape_string(isset($icetimes[$x]['id']) 				? $icetimes[$x]['id'] : '');
			$arenaid = 		$mysqli->real_escape_string(isset($icetimes[$x]['arenaid']) 		? $icetimes[$x]['arenaid'] : '');
			$day = 				$mysqli->real_escape_string(isset($icetimes[$x]['day']) 				? $icetimes[$x]['day'] : '');
			$starttime = 	$mysqli->real_escape_string(isset($icetimes[$x]['starttime']) 	? $icetimes[$x]['starttime'] : '');
			$endtime = 		$mysqli->real_escape_string(isset($icetimes[$x]['endtime']) 		? $icetimes[$x]['endtime'] : '');
			$duration = 	$mysqli->real_escape_string(isset($icetimes[$x]['duration']) 	? $icetimes[$x]['duration'] : 0);
			$iceid = 			$mysqli->real_escape_string(isset($icetimes[$x]['iceid']) 			? $icetimes[$x]['iceid'] : '0');
			$comment = 		$mysqli->real_escape_string(isset($icetimes[$x]['comment']) 		? $icetimes[$x]['comment'] : '');

			if ($mysqli->real_escape_string(isset($icetimes[$x]['status'])) && $icetimes[$x]['status'] == 'New') {
				$query = "INSERT into cpa_sessions_icetimes (sessionid, arenaid, day, starttime, endtime, duration, iceid, comment)
									VALUES ('$sessionid', '$arenaid', $day, '$starttime', '$endtime', $duration, '$iceid', '$comment')";
				if ($mysqli->query($query)) {
				} else {
					throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
				}
			}

			if ($mysqli->real_escape_string(isset($icetimes[$x]['status'])) && $icetimes[$x]['status'] == 'Modified') {
				$query = "UPDATE cpa_sessions_icetimes
									SET arenaid = '$arenaid', day = $day, starttime = '$starttime', endtime = '$endtime', duration = $duration, iceid = '$iceid', comment = '$comment'
									WHERE id = '$id'";
				if ($mysqli->query($query)) {
				} else {
					throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
				}
			}

			if ($mysqli->real_escape_string(isset($icetimes[$x]['status'])) && $icetimes[$x]['status'] == 'Deleted') {
				$query = "DELETE FROM cpa_sessions_icetimes WHERE id = '$id'";
				if ($mysqli->query($query)) {
				} else {
					throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
				}
			}
		}
	} catch (Exception $e) {
		$data = array();
		$data['success'] = false;
		$data['message'] = $e->getMessage();
		echo json_encode($data);
		exit;
	}
};

/**
 * 
 * @param integer $sessionid	The id of the session from cpa_sessions
 * @param integer $sessionscoursesid	The course id in the session
 */
function updateEntireSessionCourseSchedule($mysqli, $sessionid, $sessionscoursesid, $schedules) {
	try {
		$data = array();
		$data['inserted'] = 0;
		$data['updated'] = 0;
		$data['deleted'] = 0;
		for ($x = 0; $x < count($schedules); $x++) {
			$id = 			$mysqli->real_escape_string(isset($schedules[$x]['id']) 		? $schedules[$x]['id'] : '');
			$arenaid = 		$mysqli->real_escape_string(isset($schedules[$x]['arenaid']) 	? $schedules[$x]['arenaid'] : '');
			$iceid = 		$mysqli->real_escape_string(isset($schedules[$x]['iceid']) 		? $schedules[$x]['iceid'] : '0');
			$day = 			$mysqli->real_escape_string(isset($schedules[$x]['day']) 		? $schedules[$x]['day'] : '');
			$starttime =	$mysqli->real_escape_string(isset($schedules[$x]['starttime']) 	? $schedules[$x]['starttime'] : '');
			$endtime = 		$mysqli->real_escape_string(isset($schedules[$x]['endtime']) 	? $schedules[$x]['endtime'] : '');
			$duration = 	$mysqli->real_escape_string(isset($schedules[$x]['duration'])	? $schedules[$x]['duration'] : '');

			if ($mysqli->real_escape_string(isset($schedules[$x]['status'])) && $schedules[$x]['status'] == 'New') {
				$query = "	INSERT into cpa_sessions_courses_schedule (sessionscoursesid, arenaid, iceid, day, starttime, endtime, duration)
							VALUES ('$sessionscoursesid', '$arenaid', '$iceid', '$day', '$starttime', '$endtime', '$duration')";

				if ($mysqli->query($query)) {
					$schedules[$x]['id'] = $mysqli->insert_id;
					$data['inserted']++;
				} else {
					throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
				}
			}
			if ($mysqli->real_escape_string(isset($schedules[$x]['status'])) && $schedules[$x]['status'] == 'Modified') {
				$query = "update cpa_sessions_courses_schedule set arenaid = '$arenaid', iceid = '$iceid', day = '$day', starttime = '$starttime', endtime = '$endtime', duration = '$duration' WHERE id = '$id'";

				if ($mysqli->query($query)) {
					$data['updated']++;
				} else {
					throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
				}
			}
			if ($mysqli->real_escape_string(isset($schedules[$x]['status'])) && $schedules[$x]['status'] == 'Deleted') {
				$query = "DELETE FROM cpa_sessions_courses_schedule WHERE id = '$id'";

				if ($mysqli->query($query)) {
					$data['deleted']++;
				} else {
					throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
				}
			}
		}
		return $data;
	} catch (Exception $e) {
		$data = array();
		$data['success'] = false;
		$data['message'] = $e->getMessage();
		echo json_encode($data);
		exit;
	}
};

/**
 * 
 * @param integer $sessionid	The id of the session from cpa_sessions
 * @param integer $sessionscoursesid	The course id in the session
 */
function updateEntireSessionCourseSublevel($mysqli, $sessionid, $sessionscoursesid, $sublevels) {
	try {
		$data = array();
		$data['inserted'] = 0;
		$data['updated'] = 0;
		$data['deleted'] = 0;
		for ($x = 0; $x < count($sublevels); $x++) {
			$id = 								$mysqli->real_escape_string(isset($sublevels[$x]['id']) 							? (int)$sublevels[$x]['id'] : '');
			$code = 							$mysqli->real_escape_string(isset($sublevels[$x]['code']) 						? $sublevels[$x]['code'] : '');
			$label = 							$mysqli->real_escape_string(isset($sublevels[$x]['label']) 						? $sublevels[$x]['label'] : '');
			$label_en = 					$mysqli->real_escape_string(isset($sublevels[$x]['label_en']) 				? $sublevels[$x]['label_en'] : '');
			$label_fr = 					$mysqli->real_escape_string(isset($sublevels[$x]['label_fr']) 				? $sublevels[$x]['label_fr'] : '');
			$sequence = 					$mysqli->real_escape_string(isset($sublevels[$x]['sequence']) 				? (int)$sublevels[$x]['sequence'] : 0);

			if ($mysqli->real_escape_string(isset($sublevels[$x]['status'])) && $sublevels[$x]['status'] == 'New') {
				$query = "INSERT into cpa_sessions_courses_sublevels (sessionscoursesid, code, label, sequence)
									VALUES ('$sessionscoursesid', '$code', create_systemtext('$label_en', '$label_fr'), $sequence)";
				if ($mysqli->query($query)) {
					$sublevels[$x]['id'] = $mysqli->insert_id;
					$data['inserted']++;
				} else {
					throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
				}
			}

			if ($mysqli->real_escape_string(isset($sublevels[$x]['status'])) && $sublevels[$x]['status'] == 'Modified') {
				$query = "UPDATE cpa_text set text = '$label_fr' WHERE id = '$label' AND language = 'fr-ca'";
				if ($mysqli->query($query)) {
					$data['success'] = true;
					$query = "UPDATE cpa_text set text = '$label_en' WHERE id = '$label' AND language = 'en-ca'";
					if ($mysqli->query($query)) {
						$query = "update cpa_sessions_courses_sublevels set sequence = $sequence WHERE id = $id";
						if ($mysqli->query($query)) {
							$data['updated']++;
						} else {
							throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
						}
					} else {
						throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
					}
				} else {
					throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
				}
			}

			if ($mysqli->real_escape_string(isset($sublevels[$x]['status'])) && $sublevels[$x]['status'] == 'Deleted') {
				$query = "DELETE FROM cpa_text WHERE id = '$label'";
				if ($mysqli->query($query)) {
					$query = "DELETE FROM cpa_sessions_courses_sublevels WHERE id = $id";
					if ($mysqli->query($query)) {
						$data['deleted']++;
					} else {
						throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
					}
				} else {
					throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
				}
			}
		}
		return $data;
	} catch (Exception $e) {
		$data = array();
		$data['success'] = false;
		$data['message'] = $e->getMessage();
		echo json_encode($data);
		exit;
	}
};

/**
 * 
 * @param integer $sessionid	The id of the session from cpa_sessions
 * @param integer $sessionscoursesid	The course id in the session
 *
 */
function updateEntireSessionCourseStaff($mysqli, $sessionid, $sessionscoursesid, $staffs) {
	try {
		$data = array();
		$data['inserted'] = 0;
		$data['updated'] = 0;
		$data['deleted'] = 0;
		for ($x = 0; $x < count($staffs); $x++) {
			$id = 								$mysqli->real_escape_string(isset($staffs[$x]['id']) 							? $staffs[$x]['id'] : '');
			$memberid = 					$mysqli->real_escape_string(isset($staffs[$x]['memberid']) 				? $staffs[$x]['memberid'] : '');
			$staffcode = 					$mysqli->real_escape_string(isset($staffs[$x]['staffcode']) 			? $staffs[$x]['staffcode'] : '');
			$statuscode = 				$mysqli->real_escape_string(isset($staffs[$x]['statuscode']) 			? $staffs[$x]['statuscode'] : '');

			if ($mysqli->real_escape_string(isset($staffs[$x]['status'])) && $staffs[$x]['status'] == 'New') {
				$query = "INSERT into cpa_sessions_courses_staffs (sessionscoursesid, memberid, staffcode, statuscode)
									VALUES ('$sessionscoursesid', '$memberid', '$staffcode', '$statuscode')";

				if ($mysqli->query($query)) {
					$staffs[$x]['id'] = $mysqli->insert_id;
					$data['inserted']++;
				} else {
					throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
				}
			}
			if ($mysqli->real_escape_string(isset($staffs[$x]['status'])) && $staffs[$x]['status'] == 'Modified') {
				$query = "update cpa_sessions_courses_staffs set memberid = '$memberid', staffcode = '$staffcode', statuscode = '$statuscode' WHERE id = '$id'";

				if ($mysqli->query($query)) {
					$data['updated']++;
				} else {
					throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
				}
			}
			if ($mysqli->real_escape_string(isset($staffs[$x]['status'])) && $staffs[$x]['status'] == 'Deleted') {
				$query = "DELETE FROM cpa_sessions_courses_staffs WHERE id = '$id'";

				if ($mysqli->query($query)) {
					$data['deleted']++;
				} else {
					throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
				}
			}
		}
		return $data;
	} catch (Exception $e) {
		$data = array();
		$data['success'] = false;
		$data['message'] = $e->getMessage();
		echo json_encode($data);
		exit;
	}
};

/**
 *
 * @param integer $sessionscoursesid	The course id in the session
 *
 */
function updateEntireSessionCourseDates($mysqli, $sessionscoursesid, $dates) {
	try {
		$data = array();
		$data['inserted'] = 0;
		$data['updated'] = 0;
		$data['deleted'] = 0;
		for ($x = 0; $x < count($dates); $x++) {
			$id = 			$mysqli->real_escape_string(isset($dates[$x]['id']) 		? $dates[$x]['id'] : '');
			$coursedate =	$mysqli->real_escape_string(isset($dates[$x]['coursedate'])	? $dates[$x]['coursedate'] : '');
			$arenaid = 		$mysqli->real_escape_string(isset($dates[$x]['arenaid']) 	? $dates[$x]['arenaid'] : '');
			$iceid = 		$mysqli->real_escape_string(isset($dates[$x]['iceid']) 		? $dates[$x]['iceid'] : '0');
			$starttime = 	$mysqli->real_escape_string(isset($dates[$x]['starttime']) 	? $dates[$x]['starttime'] : '');
			$endtime = 		$mysqli->real_escape_string(isset($dates[$x]['endtime']) 	? $dates[$x]['endtime'] : '');
			$duration = 	$mysqli->real_escape_string(isset($dates[$x]['duration']) 	? $dates[$x]['duration'] : '');
			$canceled = 	$mysqli->real_escape_string(isset($dates[$x]['canceled']) && !empty($dates[$x]['canceled']) 			? $dates[$x]['canceled'] : '0');
			$manual = 		$mysqli->real_escape_string(isset($dates[$x]['manual']) 	? $dates[$x]['manual'] : '0');
			$day =			$mysqli->real_escape_string(isset($dates[$x]['day']) 		? $dates[$x]['day'] : '');
			$label = 		$mysqli->real_escape_string(isset($dates[$x]['label']) 		? $dates[$x]['label'] : '');
			$label_fr = 	$mysqli->real_escape_string(isset($dates[$x]['label_fr']) 	? $dates[$x]['label_fr'] : '');
			$label_en = 	$mysqli->real_escape_string(isset($dates[$x]['label_en']) 	? $dates[$x]['label_en'] : '');

			if ($mysqli->real_escape_string(isset($dates[$x]['status'])) && $dates[$x]['status'] == 'New') {
				$query = "	INSERT INTO cpa_sessions_courses_dates (sessionscoursesid, arenaid, iceid, coursedate, starttime, endtime, duration, canceled, manual, day, label)
							VALUES ('$sessionscoursesid', '$arenaid', '$iceid', '$coursedate', '$starttime', '$endtime', '$duration', '$canceled', '$manual', $day, create_systemText('$label_en', '$label_fr'))";

				if ($mysqli->query($query)) {
					$schedules[$x]['id'] = $mysqli->insert_id;
					$data['inserted']++;
				} else {
					throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
				}
			}

			if ($mysqli->real_escape_string(isset($dates[$x]['status'])) && $dates[$x]['status'] == 'Modified') {
				if (!$mysqli->real_escape_string(isset($dates[$x]['label'])) || empty($dates[$x]['label']) || $dates[$x]['label']=="") {
					$query = "UPDATE cpa_sessions_courses_dates SET canceled = '$canceled', manual = '$manual', coursedate = '$coursedate', starttime = '$starttime', endtime = '$endtime', duration = '$duration', label = create_systemText('$label_en', '$label_fr') WHERE id = '$id'";
					if ($mysqli->query($query)) {
						$data['updated']++;
					} else {
						throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
					}
				} else {
					$query = "UPDATE cpa_sessions_courses_dates SET canceled = '$canceled', manual = '$manual', coursedate = '$coursedate', starttime = '$starttime', endtime = '$endtime', duration = '$duration' WHERE id = '$id'";
					if ($mysqli->query($query)) {
						$query = "UPDATE cpa_text set text = '$label_fr' WHERE id = $label AND language = 'fr-ca'";
						if ($mysqli->query($query)) {
							$query = "UPDATE cpa_text set text = '$label_en' WHERE id = $label AND language = 'en-ca'";
							if ($mysqli->query($query)) {
								$data['updated']++;
							} else {
								throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
							}
						} else {
							throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
						}
					} else {
						throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
					}
				}
			}

			if ($mysqli->real_escape_string(isset($dates[$x]['status'])) && $dates[$x]['status'] == 'Deleted') {
				$query = "DELETE FROM cpa_sessions_courses_dates WHERE id = '$id'";
				if ($mysqli->query($query)) {
					$data['deleted']++;
				} else {
					throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
				}
			}
		}
		return $data;
	} catch (Exception $e) {
		$data = array();
		$data['success'] = false;
		$data['message'] = $e->getMessage();
		echo json_encode($data);
		exit;
	}
};

/**
 * 
 * @param integer $sessionid	The id of the session from cpa_sessions
 */
function updateEntireSessionCharges($mysqli, $sessionid, $sessionCharges) {
	try {
		$data = array();
		for ($x = 0; $x < count($sessionCharges); $x++) {
			$id = 			$mysqli->real_escape_string(isset($sessionCharges[$x]['id']) 			? $sessionCharges[$x]['id'] : '');
			$chargecode =	$mysqli->real_escape_string(isset($sessionCharges[$x]['chargecode'])	? $sessionCharges[$x]['chargecode'] : '');
			$amount = 		$mysqli->real_escape_string(isset($sessionCharges[$x]['amount']) 		? $sessionCharges[$x]['amount'] : '0.00');
			$startdate = 	$mysqli->real_escape_string(isset($sessionCharges[$x]['startdate']) 	? $sessionCharges[$x]['startdate'] : '');
			$enddate = 		$mysqli->real_escape_string(isset($sessionCharges[$x]['enddate']) 		? $sessionCharges[$x]['enddate'] : '');

			if ($mysqli->real_escape_string(isset($sessionCharges[$x]['status'])) && $sessionCharges[$x]['status'] == 'New') {
				$query = "	INSERT INTO cpa_sessions_charges (sessionid, chargecode, amount, startdate, enddate)
							VALUES ('$sessionid', '$chargecode', '$amount'";
				if ($startdate) {
					$query .= ", '$startdate'";
				} else {
					$query .= ", null";
				}

				if ($enddate) {
					$query .= ", '$enddate'";
				} else {
					$query .= ", null";
				}

				$query .= ")";
	
				if ($mysqli->query($query)) {
					$sessionCharges[$x]['id'] = $mysqli->insert_id;
				} else {
					throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
				}
			}

			if ($mysqli->real_escape_string(isset($sessionCharges[$x]['status'])) && $sessionCharges[$x]['status'] == 'Modified') {
				$query = "UPDATE cpa_sessions_charges SET chargecode = '$chargecode', amount =  '$amount'";
				if ($startdate) {
					$query .= ", startdate = '$startdate'";
				} else {
					$query .= ", startdate = null";
				}

				if ($enddate) {
					$query .= ", enddate = '$enddate'";
				} else {
					$query .= ", enddate = null";
				}
				$query .= " WHERE id = '$id'";

				if (!$mysqli->query($query)) {
					throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
				}
			}

			if ($mysqli->real_escape_string(isset($sessionCharges[$x]['status'])) && $sessionCharges[$x]['status'] == 'Deleted') {
				$query = "DELETE FROM cpa_sessions_charges WHERE id = '$id'";
				if (!$mysqli->query($query)) {
					throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
				}
			}
		}
		return $data;
	} catch (Exception $e) {
		$data = array();
		$data['success'] = false;
		$data['message'] = $e->getMessage();
		echo json_encode($data);
		exit;
	}
};

/**
 * 
 * @param integer $sessionid	The id of the session from cpa_sessions
 */
function updateEntireSessionCourses($mysqli, $sessionid, $sessionCourses) {
	try {
		$data = array();
		for ($x = 0; $x < count($sessionCourses); $x++) {
			$id = 					$mysqli->real_escape_string(isset($sessionCourses[$x]['id']) 					? (int)$sessionCourses[$x]['id'] : 0);
			$coursecode = 			$mysqli->real_escape_string(isset($sessionCourses[$x]['coursecode']) 			? $sessionCourses[$x]['coursecode'] : '');
			$courselevel = 			$mysqli->real_escape_string(isset($sessionCourses[$x]['courselevel']) 			? $sessionCourses[$x]['courselevel'] : '');
			$name = 				$mysqli->real_escape_string(isset($sessionCourses[$x]['name']) 					? $sessionCourses[$x]['name'] : '');
			$label = 				$mysqli->real_escape_string(isset($sessionCourses[$x]['label']) 				? $sessionCourses[$x]['label'] : '');
			$label_fr = 			$mysqli->real_escape_string(isset($sessionCourses[$x]['label_fr']) 				? $sessionCourses[$x]['label_fr'] : '');
			$label_en = 			$mysqli->real_escape_string(isset($sessionCourses[$x]['label_en']) 				? $sessionCourses[$x]['label_en'] : '');
			$fees = 				$mysqli->real_escape_string(isset($sessionCourses[$x]['fees']) 					? $sessionCourses[$x]['fees'] : '0.00');
			$minnumberskater =		$mysqli->real_escape_string(isset($sessionCourses[$x]['minnumberskater'])		? $sessionCourses[$x]['minnumberskater'] : '');
			$maxnumberskater = 		$mysqli->real_escape_string(isset($sessionCourses[$x]['maxnumberskater']) 		? $sessionCourses[$x]['maxnumberskater'] : '');
			$availableonline = 		$mysqli->real_escape_string(isset($sessionCourses[$x]['availableonline']) 		? (int)$sessionCourses[$x]['availableonline'] : 1);
			$isschedule = 			$mysqli->real_escape_string(isset($sessionCourses[$x]['isschedule']) 			? (int)$sessionCourses[$x]['isschedule'] : 0);
			$datesgenerated = 		$mysqli->real_escape_string(isset($sessionCourses[$x]['datesgenerated']) 		? (int)$sessionCourses[$x]['datesgenerated'] : 0);
			$startdate = 			$mysqli->real_escape_string(isset($sessionCourses[$x]['startdate']) 			? $sessionCourses[$x]['startdate'] : '');
			$enddate = 				$mysqli->real_escape_string(isset($sessionCourses[$x]['enddate']) 				? $sessionCourses[$x]['enddate'] : '');
			$prereqcanskatebadgemin = 	$mysqli->real_escape_string(isset($sessionCourses[$x]['prereqcanskatebadgemin']) 	? (int)$sessionCourses[$x]['prereqcanskatebadgemin'] : 0);
			$prereqcanskatebadgemax = 	$mysqli->real_escape_string(isset($sessionCourses[$x]['prereqcanskatebadgemax']) 	? (int)$sessionCourses[$x]['prereqcanskatebadgemax'] : 0);
			$prereqagemin = 		$mysqli->real_escape_string(isset($sessionCourses[$x]['prereqagemin']) 			? (int)$sessionCourses[$x]['prereqagemin'] : 0);
			$prereqagemax = 		$mysqli->real_escape_string(isset($sessionCourses[$x]['prereqagemax']) 			? (int)$sessionCourses[$x]['prereqagemax'] : 0);

			if ($mysqli->real_escape_string(isset($sessionCourses[$x]['status'])) && $sessionCourses[$x]['status'] == 'New') {
				$query = "	INSERT INTO cpa_sessions_courses (sessionid, coursecode, courselevel, name, fees, minnumberskater, maxnumberskater, availableonline, label, isschedule, datesgenerated, prereqcanskatebadgemin, prereqcanskatebadgemax, startdate, enddate, prereqagemin, prereqagemax)
							VALUES ('$sessionid', '$coursecode', '$courselevel', '$name', '$fees', '$minnumberskater', '$maxnumberskater', $availableonline, create_systemText('$label_en', '$label_fr'), $isschedule, $datesgenerated, $prereqcanskatebadgemin, $prereqcanskatebadgemax,"
									.($startdate == '' ? "null, " : "'$startdate', ")
									.($enddate == '' ? "null," : "'$enddate',")
									.($prereqagemin == 0 ? "null," : "'$prereqagemin',")
									.($prereqagemax == 0 ? "null" : "'$prereqagemax'")
									.")";
				if ($mysqli->query($query)) {
					$sessionCourses[$x]['id'] = $mysqli->insert_id;
				} else {
					throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
				}
			}

			if ($mysqli->real_escape_string(isset($sessionCourses[$x]['status'])) && $sessionCourses[$x]['status'] == 'Modified') {
				$query = "	UPDATE cpa_sessions_courses 
							SET coursecode = '$coursecode', courselevel =  '$courselevel', name = '$name', fees = $fees, 
								minnumberskater = '$minnumberskater', maxnumberskater = '$maxnumberskater', 
								availableonline = $availableonline, isschedule = $isschedule, datesgenerated = $datesgenerated, 
								prereqcanskatebadgemin = $prereqcanskatebadgemin, 
								prereqcanskatebadgemax = $prereqcanskatebadgemax, 
								startdate = ".($startdate == '' ? "null" : "'$startdate'")
								.", enddate = ".($enddate == '' ? "null" : "'$enddate'")
								.", prereqagemin = ".($prereqagemin == 0 ? "null" : "'$prereqagemin'")
								.", prereqagemax = ".($prereqagemax == 0 ? "null" : "'$prereqagemax'")
							." WHERE id = $id";
				if ($mysqli->query($query)) {
					$query = "UPDATE cpa_text SET text = '$label_fr' WHERE id = $label AND language = 'fr-ca'";
					if ($mysqli->query($query)) {
						$query = "UPDATE cpa_text set text = '$label_en' WHERE id = $label AND language = 'en-ca'";
						if ($mysqli->query($query)) {
						} else {
							throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
						}
					} else {
						throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
					}
				} else {
					throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
				}
			}

			if ($mysqli->real_escape_string(isset($sessionCourses[$x]['status'])) && $sessionCourses[$x]['status'] == 'Deleted') {
				$query = "DELETE FROM cpa_text WHERE id = $label";
				if ($mysqli->query($query)) {
					$query = "DELETE FROM cpa_text WHERE id IN (select label from cpa_sessions_courses_dates WHERE sessionscoursesid = $id)";
					if ($mysqli->query($query)) {
						$query = "DELETE FROM cpa_sessions_courses WHERE id = $id";
						if (!$mysqli->query($query)) {
							throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
						}
					} else {
						throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
					}
				} else {
					throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
				}
			}
		}

		for ($x = 0; $x < count($sessionCourses); $x++) {
			if (!$mysqli->real_escape_string(isset($sessionCourses[$x]['status'])) || ($mysqli->real_escape_string(isset($sessionCourses[$x]['status'])) && $sessionCourses[$x]['status'] !== 'Deleted')) {
				if ($mysqli->real_escape_string(isset($sessionCourses[$x]['schedules']))) {
					$data['schedules'] = updateEntireSessionCourseSchedule($mysqli, $sessionid, $sessionCourses[$x]['id'], $sessionCourses[$x]['schedules']);
				}
			}
		}

		for ($x = 0; $x < count($sessionCourses); $x++) {
			if (!$mysqli->real_escape_string(isset($sessionCourses[$x]['status'])) || ($mysqli->real_escape_string(isset($sessionCourses[$x]['status'])) && $sessionCourses[$x]['status'] !== 'Deleted')) {
				if ($mysqli->real_escape_string(isset($sessionCourses[$x]['staffs']))) {
					$data['staffs'] = updateEntireSessionCourseStaff($mysqli, $sessionid, $sessionCourses[$x]['id'], $sessionCourses[$x]['staffs']);
				}
			}
		}

		for ($x = 0; $x < count($sessionCourses); $x++) {
			if (!$mysqli->real_escape_string(isset($sessionCourses[$x]['status'])) || ($mysqli->real_escape_string(isset($sessionCourses[$x]['status'])) && $sessionCourses[$x]['status'] !== 'Deleted')) {
				if ($mysqli->real_escape_string(isset($sessionCourses[$x]['sublevels']))) {
					$data['sublevels'] = updateEntireSessionCourseSublevel($mysqli, $sessionid, $sessionCourses[$x]['id'], $sessionCourses[$x]['sublevels']);
				}
			}
		}

		for ($x = 0; $x < count($sessionCourses); $x++) {
			if (!$mysqli->real_escape_string(isset($sessionCourses[$x]['status'])) || ($mysqli->real_escape_string(isset($sessionCourses[$x]['status'])) && $sessionCourses[$x]['status'] !== 'Deleted')) {
				if ($mysqli->real_escape_string(isset($sessionCourses[$x]['dates']))) {
					$data['schedules'] = updateEntireSessionCourseDates($mysqli, $sessionCourses[$x]['id'], $sessionCourses[$x]['dates']);
				}
			}
		}
		return $data;
	} catch (Exception $e) {
		$data = array();
		$data['success'] = false;
		$data['message'] = $e->getMessage();
		echo json_encode($data);
		exit;
	}
};
/**
 * 
 * @param integer $sessionid	The id of the session from cpa_sessions
 */
function updateEntireSessionRegistrations($mysqli, $sessionid, $registrations) {
	try {
		$data = array();
		$data['inserted'] = 0;
		$data['updated'] = 0;
		$data['deleted'] = 0;
		for ($x = 0; $x < count($registrations); $x++) {
			$id = 				$mysqli->real_escape_string(isset($registrations[$x]['id']) 				? $registrations[$x]['id'] : '');
			$location = 		$mysqli->real_escape_string(isset($registrations[$x]['location']) 			? $registrations[$x]['location'] : '');
			$registrationdate =	$mysqli->real_escape_string(isset($registrations[$x]['registrationdate'])	? $registrations[$x]['registrationdate'] : '');
			$starttime = 		$mysqli->real_escape_string(isset($registrations[$x]['starttime']) 			? $registrations[$x]['starttime'] : '');
			$endtime = 			$mysqli->real_escape_string(isset($registrations[$x]['endtime']) 			? $registrations[$x]['endtime'] : '');
			$comments = 		$mysqli->real_escape_string(isset($registrations[$x]['comments']) 			? $registrations[$x]['comments'] : '');

			if ($mysqli->real_escape_string(isset($registrations[$x]['status'])) && $registrations[$x]['status'] == 'New') {
				$query = "	INSERT into cpa_sessions_registrations (sessionid, location, registrationdate, starttime, endtime, comments)
							VALUES ('$sessionid', '$location', '$registrationdate', '$starttime', '$endtime', '$comments')";

				if ($mysqli->query($query)) {
					$registrations[$x]['id'] = $mysqli->insert_id;
					$data['inserted']++;
				} else {
					throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
				}
			}

			if ($mysqli->real_escape_string(isset($registrations[$x]['status'])) && $registrations[$x]['status'] == 'Modified') {
				$query = "UPDATE cpa_sessions_registrations SET location = '$location', registrationdate = '$registrationdate', starttime = '$starttime', endtime = '$endtime', comments = '$comments' WHERE id = '$id'";
				if ($mysqli->query($query)) {
					$data['updated']++;
				} else {
					throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
				}
			}

			if ($mysqli->real_escape_string(isset($registrations[$x]['status'])) && $registrations[$x]['status'] == 'Deleted') {
				$query = "DELETE FROM cpa_sessions_registrations WHERE id = '$id'";

				if ($mysqli->query($query)) {
					$data['deleted']++;
				} else {
					throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
				}
			}
		}
		return $data;
	} catch (Exception $e) {
		$data = array();
		$data['success'] = false;
		$data['message'] = $e->getMessage();
		echo json_encode($data);
		exit;
	}
};

/**
 * 
 * @param integer $sessionid	The id of the session from cpa_sessions
 */
function updateEntireSessionEvents($mysqli, $sessionid, $events) {
	$data = array();
	$data['inserted'] = 0;
	$data['updated'] = 0;
	$data['deleted'] = 0;
	for ($x = 0; $x < count($events); $x++) {
		$id = 			$mysqli->real_escape_string(isset($events[$x]['id']) 			? $events[$x]['id'] : '');
		$type = 		$mysqli->real_escape_string(isset($events[$x]['type']) 			? $events[$x]['type'] : '');
		$eventdate =	$mysqli->real_escape_string(isset($events[$x]['eventdate']) 	? $events[$x]['eventdate'] : '');
		$label = 		$mysqli->real_escape_string(isset($events[$x]['label']) 		? $events[$x]['label'] : '');
		$label_en = 	$mysqli->real_escape_string(isset($events[$x]['label_en']) 		? $events[$x]['label_en'] : '');
		$label_fr = 	$mysqli->real_escape_string(isset($events[$x]['label_fr']) 		? $events[$x]['label_fr'] : '');

		if ($mysqli->real_escape_string(isset($events[$x]['status'])) && $events[$x]['status'] == 'New') {
			$query = "	INSERT into cpa_sessions_dates (sessionid, type, eventdate, label)
						VALUES ($sessionid, '$type', '$eventdate', create_systemtext('$label_en', '$label_fr'))";
			if ($mysqli->query($query)) {
				$events[$x]['id'] = $mysqli->insert_id;
				$data['inserted']++;
			} else {
				throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
			}
		}

		if ($mysqli->real_escape_string(isset($events[$x]['status'])) && $events[$x]['status'] == 'Modified') {
			$query = "update cpa_sessions_dates set type = '$type', eventdate = '$eventdate' WHERE id = '$id'";
			if ($mysqli->query($query)) {
				$query = "UPDATE cpa_text SET text = '$label_en' WHERE id = $label AND language = 'en-ca'";
				if ($mysqli->query($query)) {
					$query = "UPDATE cpa_text SET text = '$label_fr' WHERE id = $label AND language = 'fr-ca'";
					if ($mysqli->query($query)) {
						$data['updated']++;
					} else {
						throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
					}
				} else {
					throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
				}
			} else {
				throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
			}
		}

		if ($mysqli->real_escape_string(isset($events[$x]['status'])) && $events[$x]['status'] == 'Deleted') {
			$query = "DELETE FROM cpa_sessions_dates WHERE id = '$id'";
			if ($mysqli->query($query)) {
				$data['deleted']++;
			} else {
				throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
			}
		}
	}
	return $data;
};

/**
 * 
 * @param integer $sessionid	The id of the session from cpa_sessions
 */
function updateEntireRulesParagraphs($mysqli, $sessionid, $paragraphs) {
	$data = array();
	for ($x = 0; $paragraphs && $x < count($paragraphs); $x++) {
		$id =				$mysqli->real_escape_string(isset($paragraphs[$x]['id'])				? (int)$paragraphs[$x]['id'] : 0);
		$paragraphindex = 	$mysqli->real_escape_string(isset($paragraphs[$x]['paragraphindex'])	? (int)$paragraphs[$x]['paragraphindex'] : 0);
		$publish =			$mysqli->real_escape_string(isset($paragraphs[$x]['publish'])			? (int)$paragraphs[$x]['publish'] : 0);
		$visiblepreview = 	$mysqli->real_escape_string(isset($paragraphs[$x]['visiblepreview'])	? (int)$paragraphs[$x]['visiblepreview'] : 0);
		$title =			$mysqli->real_escape_string(isset($paragraphs[$x]['title'])				? (int)$paragraphs[$x]['title'] : 0);
		$subtitle =			$mysqli->real_escape_string(isset($paragraphs[$x]['subtitle'])			? (int)$paragraphs[$x]['subtitle'] : 0);
		$paragraphtext =	$mysqli->real_escape_string(isset($paragraphs[$x]['paragraphtext'])		? (int)$paragraphs[$x]['paragraphtext'] : 0);
		$title_en =			$mysqli->real_escape_string(isset($paragraphs[$x]['title_en'])			? $paragraphs[$x]['title_en'] : '');
		$title_fr =			$mysqli->real_escape_string(isset($paragraphs[$x]['title_fr'])			? $paragraphs[$x]['title_fr'] : '');
		$subtitle_en =		$mysqli->real_escape_string(isset($paragraphs[$x]['subtitle_en'])		? $paragraphs[$x]['subtitle_en'] : '');
		$subtitle_fr =		$mysqli->real_escape_string(isset($paragraphs[$x]['subtitle_fr'])		? $paragraphs[$x]['subtitle_fr'] : '');
		$paragraphtext_en = $mysqli->real_escape_string(isset($paragraphs[$x]['paragraphtext_en'])	? $paragraphs[$x]['paragraphtext_en'] : '');
		$paragraphtext_fr = $mysqli->real_escape_string(isset($paragraphs[$x]['paragraphtext_fr']) 	? $paragraphs[$x]['paragraphtext_fr'] : '');

		if ($mysqli->real_escape_string(isset($paragraphs[$x]['status'])) and $paragraphs[$x]['status'] == 'New') {
			$query = "	INSERT INTO cpa_sessions_rules2 (id, sessionid, paragraphindex, publish, visiblepreview, title, subtitle, paragraphtext)
						VALUES (null, $sessionid, $paragraphindex, $publish, $visiblepreview, create_wsText('$title_en', '$title_fr'), 
								create_wsText('$subtitle_en', '$subtitle_fr'), create_wsText('$paragraphtext_en', '$paragraphtext_fr'))";
			if (!$mysqli->query($query)) {
				throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
			}
			$paragraphs[$x]['id'] = (int) $mysqli->insert_id;
		}

		// If no status or (status != deleted AND status != New)
		if ($mysqli->real_escape_string(isset($paragraphs[$x]['status'])) and $paragraphs[$x]['status'] == 'Modified') {
			$query = "UPDATE cpa_sessions_rules2 SET publish = $publish, visiblepreview = $visiblepreview	WHERE id = $id";
			if ($mysqli->query($query)) {
				$query = "UPDATE cpa_ws_text SET text = '$title_fr' WHERE id = $title AND language = 'fr-ca'";
				if ($mysqli->query($query)) {
					$query = "UPDATE cpa_ws_text SET text = '$title_en' WHERE id = $title AND language = 'en-ca'";
					if ($mysqli->query($query)) {
						$query = "UPDATE cpa_ws_text SET text = '$subtitle_fr' WHERE id = $subtitle AND language = 'fr-ca'";
						if ($mysqli->query($query)) {
							$query = "UPDATE cpa_ws_text SET text = '$subtitle_en' WHERE id = $subtitle AND language = 'en-ca'";
							if ($mysqli->query($query)) {
								$query = "UPDATE cpa_ws_text SET text = '$paragraphtext_fr' WHERE id = $paragraphtext AND language = 'fr-ca'";
								if ($mysqli->query($query)) {
									$query = "UPDATE cpa_ws_text SET text = '$paragraphtext_en' WHERE id = $paragraphtext AND language = 'en-ca'";
									if ($mysqli->query($query)) {
									} else {
										throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
									}
								} else {
									throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
								}
							} else {
								throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
							}
						} else {
							throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
						}
					} else {
						throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
					}
				} else {
					throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
				}
			} else {
				throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
			}
		}

		if ($mysqli->real_escape_string(isset($paragraphs[$x]['status'])) and $paragraphs[$x]['status'] == 'Deleted') {
			$query = "DELETE FROM cpa_sessions_rules2 WHERE id = $id";
			if ($mysqli->query($query)) {
				$query = "DELETE FROM cpa_ws_text WHERE id = $title";
				if ($mysqli->query($query)) {
					$query = "DELETE FROM cpa_ws_text WHERE id = $subtitle";
					if ($mysqli->query($query)) {
						$query = "DELETE FROM cpa_ws_text WHERE id = $paragraphtext";
						if ($mysqli->query($query)) {
						} else {
							throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
						}
					} else {
						throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
					}
				} else {
					throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
				}
			} else {
				throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
			}
		}
	}

	// We need to reorder everything
	$realIndex = 0;
	for ($x = 0; $paragraphs && $x < count($paragraphs); $x++) {
		$id = $mysqli->real_escape_string(isset($paragraphs[$x]['id']) ? (int)$paragraphs[$x]['id'] : 0);
		if ($mysqli->real_escape_string(!isset($paragraphs[$x]['status'])) or $paragraphs[$x]['status'] != 'Deleted') {
			$query = "UPDATE cpa_sessions_rules2 SET paragraphindex = $realIndex WHERE id = $id";
			if (!$mysqli->query($query)) {
				throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
			}
			$realIndex = $realIndex + 1;
		}
	}
	$data['success'] = true;
	return $data;
};

/**
 * This function will handle session add, update functionality
 * @throws Exception
 */
function updateEntireSession($mysqli, $session) {
	try {
		$data = array();
		$id = $mysqli->real_escape_string(isset($session['id']) ? $session['id'] : '');

		update_session($mysqli, $session);
		if ($mysqli->real_escape_string(isset($session['icetimes']))) {
			updateEntireIcetimes($mysqli, $id, $session['icetimes']);
		}
		if ($mysqli->real_escape_string(isset($session['sessionCourses']))) {
			$data['sessionCourses'] = updateEntireSessionCourses($mysqli, $id, $session['sessionCourses']);
		}

		if ($mysqli->real_escape_string(isset($session['sessionCharges']))) {
			$data['sessionCharges'] = updateEntireSessionCharges($mysqli, $id, $session['sessionCharges']);
		}

		if ($mysqli->real_escape_string(isset($session['registrations']))) {
			$data['registrations'] = updateEntireSessionRegistrations($mysqli, $id, $session['registrations']);
		}

		if ($mysqli->real_escape_string(isset($session['events']))) {
			$data['events'] = updateEntireSessionEvents($mysqli, $id, $session['events']);
		}

		if ($mysqli->real_escape_string(isset($session['rules2']))) {
			$data['rules2'] = updateEntireRulesParagraphs($mysqli, $id, $session['rules2']);
		}

		$mysqli->close();
		$data['success'] = true;
		$data['message'] = 'Session updated successfully.';
		echo json_encode($data);
		exit;
	} catch (Exception $e) {
		$data = array();
		$data['success'] = false;
		$data['message'] = $e->getMessage();
		echo json_encode($data);
		exit;
	}
};

/**
 * This function will handle session add, update functionality
 * @throws Exception
 */
function insert_session($mysqli, $session) {
	try {
		$data = array();
		$id =			$mysqli->real_escape_string(isset($session['id']) 			? $session['id'] : '');
		$name =			$mysqli->real_escape_string(isset($session['name']) 		? $session['name'] : '');
		$label =		$mysqli->real_escape_string(isset($session['label']) 		? $session['label'] : '');
		$label_fr =		$mysqli->real_escape_string(isset($session['label_fr']) 	? $session['label_fr'] : '');
		$label_en =		$mysqli->real_escape_string(isset($session['label_en']) 	? $session['label_en'] : '');
		$startdate =	$mysqli->real_escape_string(isset($session['startdatestr'])	? $session['startdatestr'] : '0000-01-01');
		$enddate =		$mysqli->real_escape_string(isset($session['enddatestr']) 	? $session['enddatestr']   : '0000-01-01');
		$active =		$mysqli->real_escape_string(isset($session['active']) 		? (int)$session['active'] : 0);
		$coursesstartdate =	'0000-01-01';
		$coursesenddate = '0000-01-01';
		$onlineregiststartdate = '0000-01-01';
		$onlineregistenddate = '0000-01-01';
		$onlinepreregiststartdate =	'0000-01-01';
		$onlinepreregistenddate = '0000-01-01';
		$reimbursementdate = '0000-01-01';

		if ($name == '') {
			throw new Exception("Required fields missing. Please enter and submit");
		}

		$query = "	INSERT INTO cpa_sessions (name, label, startdate, enddate, coursesstartdate, coursesenddate, onlineregiststartdate, 
												onlineregistenddate, onlinepreregiststartdate, onlinepreregistenddate, reimbursementdate, active)
					VALUES ('$name', create_systemText('$label_en', '$label_fr'), '$startdate', '$enddate', '$coursesstartdate', '$coursesenddate', '$onlineregiststartdate', 
							'$onlineregistenddate', '$onlinepreregiststartdate', '$onlinepreregistenddate', '$reimbursementdate', $active)";
		if ($mysqli->query($query)) {
			if (empty($id)) {
				$id = $data['id'] = (int) $mysqli->insert_id;
			}
			$query = "	INSERT INTO cpa_sessions_charges (sessionid, chargecode, amount)
						VALUES ($id, 'SPECCHARGE', 0)";
			if (!$mysqli->query($query)) {
				throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
			}
			$query = "	INSERT INTO cpa_sessions_charges (sessionid, chargecode, amount)
						VALUES ($id, 'SPECDISCNT', 0)";
			if (!$mysqli->query($query)) {
				throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
			}
			$data['success'] = true;
		} else {
			throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
		}
		$mysqli->close();
		echo json_encode($data);
		exit;
	} catch (Exception $e) {
		$data = array();
		$data['success'] = false;
		$data['message'] = $e->getMessage();
		echo json_encode($data);
		exit;
	}
};

/**
 * This function will handle session add, update functionality
 * @throws Exception
 */
function update_session($mysqli, $details) {
	try {
		$data = array();
		$id =						$mysqli->real_escape_string(isset($details['id']) 							? $details['id'] : '');
		$name =						$mysqli->real_escape_string(isset($details['name']) 						? $details['name'] : '');
		$label =					$mysqli->real_escape_string(isset($details['label']) 						? $details['label'] : '');
		$label_fr =					$mysqli->real_escape_string(isset($details['label_fr']) 					? $details['label_fr'] : '');
		$label_en =					$mysqli->real_escape_string(isset($details['label_en']) 					? $details['label_en'] : '');
		$startdate =				$mysqli->real_escape_string(isset($details['startdatestr']) 				? $details['startdatestr'] : '');
		$enddate =					$mysqli->real_escape_string(isset($details['enddatestr']) 					? $details['enddatestr'] : '');
		$coursesstartdate =			$mysqli->real_escape_string(isset($details['coursesstartdatestr']) 			? $details['coursesstartdatestr'] : '');
		$coursesenddate =			$mysqli->real_escape_string(isset($details['coursesenddatestr']) 			? $details['coursesenddatestr'] : '');
		$onlineregiststartdate =	$mysqli->real_escape_string(isset($details['onlineregiststartdatestr']) 	? $details['onlineregiststartdatestr'] : '');
		$onlineregistenddate =		$mysqli->real_escape_string(isset($details['onlineregistenddatestr']) 		? $details['onlineregistenddatestr'] : '');
		$onlinepreregiststartdate =	$mysqli->real_escape_string(isset($details['onlinepreregiststartdatestr']) 	? $details['onlinepreregiststartdatestr'] : '');
		$onlinepreregistenddate =	$mysqli->real_escape_string(isset($details['onlinepreregistenddatestr']) 	? $details['onlinepreregistenddatestr'] : '');
		$reimbursementdate =		$mysqli->real_escape_string(isset($details['reimbursementdatestr']) 		? $details['reimbursementdatestr'] : '');
		$agereferencedate =			$mysqli->real_escape_string(isset($details['agereferencedatestr']) 			? $details['agereferencedatestr'] : '');
		$proratastartdate =			$mysqli->real_escape_string(isset($details['proratastartdatestr']) 			? $details['proratastartdatestr'] : '');
		$prorataoptions =			$mysqli->real_escape_string(isset($details['prorataoptions']) 				? (int)$details['prorataoptions'] : -1);
		$attendancepaidinfull =		$mysqli->real_escape_string(isset($details['attendancepaidinfull']) 		? (int)$details['attendancepaidinfull'] : 0);
		$previoussessionid =		$mysqli->real_escape_string(isset($details['previoussessionid']) 			? (int)$details['previoussessionid'] : 0);
		$active =					$mysqli->real_escape_string(isset($details['active']) 						? (int)$details['active'] : 0);
		$isonlineregistactive =		$mysqli->real_escape_string(isset($details['isonlineregistactive']) 		? (int)$details['isonlineregistactive'] : 0);
		$isonlinepreregistactive =	$mysqli->real_escape_string(isset($details['isonlinepreregistactive']) 		? (int)$details['isonlinepreregistactive'] : 0);
		$isonlinepreregistemail =	$mysqli->real_escape_string(isset($details['isonlinepreregistemail']) 		? (int)$details['isonlinepreregistemail'] : 0);
		$onlinepreregistemailtpl =	$mysqli->real_escape_string(isset($details['onlinepreregistemailtpl']) 		? (int)$details['onlinepreregistemailtpl'] : 0);
		$onlinepaymentoption =		$mysqli->real_escape_string(isset($details['onlinepaymentoption']) 			? (int)$details['onlinepaymentoption'] : 0);
		$isonlineregistemail =		$mysqli->real_escape_string(isset($details['isonlineregistemail']) 			? (int)$details['isonlineregistemail'] : 0);
		$onlineregistemailtpl =		$mysqli->real_escape_string(isset($details['onlineregistemailtpl']) 		? (int)$details['onlineregistemailtpl'] : 0);
		$isonlineregistemailinclbill =	$mysqli->real_escape_string(isset($details['isonlineregistemailinclbill']) ? (int)$details['isonlineregistemailinclbill'] : 0);


		$query = "	UPDATE cpa_sessions
					SET name = '$name', startdate = '$startdate', enddate = '$enddate',	coursesstartdate = '$coursesstartdate', 
						coursesenddate = '$coursesenddate',	
						onlineregiststartdate = '$onlineregiststartdate', onlineregistenddate = '$onlineregistenddate',	
						onlinepreregiststartdate = '$onlinepreregiststartdate', onlinepreregistenddate = '$onlinepreregistenddate',
						reimbursementdate = '$reimbursementdate', proratastartdate = '$proratastartdate', prorataoptions = '$prorataoptions', 
						attendancepaidinfull = '$attendancepaidinfull', active = '$active', previoussessionid = $previoussessionid,
						isonlineregistactive = $isonlineregistactive, isonlinepreregistactive = $isonlinepreregistactive, 
						isonlinepreregistemail = $isonlinepreregistemail, onlinepreregistemailtpl = $onlinepreregistemailtpl, 
						onlinepaymentoption = $onlinepaymentoption, isonlineregistemail = $isonlineregistemail, 
						onlineregistemailtpl = $onlineregistemailtpl, isonlineregistemailinclbill = $isonlineregistemailinclbill, 
						agereferencedate = '$agereferencedate'
					WHERE id = '$id'";
		if ($mysqli->query($query)) {
			$query = "UPDATE cpa_text SET text = '$label_fr' WHERE id = '$label' AND language = 'fr-ca'";
			if ($mysqli->query($query)) {
				$data['success'] = true;
				$query = "UPDATE cpa_text SET text = '$label_en' WHERE id = '$label' AND language = 'en-ca'";
				if (!$mysqli->query($query)) {
					throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
				}
			} else {
				throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
			}
		} else {
			throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
		}
	} catch (Exception $e) {
		$data = array();
		$data['success'] = false;
		$data['message'] = $e->getMessage();
		echo json_encode($data);
		exit;
	}
};

/**
 * This function will handle session deletion 
 * 
 * @param string $id
 * @throws Exception
 */
function delete_session($mysqli, $session) {
	try {
		$id = 		$mysqli->real_escape_string(isset($session['id'])		? $session['id'] : '');
		$label =	$mysqli->real_escape_string(isset($session['label'])	? $session['label'] : '');

		if (empty($id)) throw new Exception("Invalid Session.");
		// Delete all bills (and child) related to registrations from this session
		$query = "DELETE FROM `cpa_bills` WHERE id IN (SELECT billid FROM cpa_bills_registrations WHERE registrationid IN (SELECT id FROM cpa_registrations WHERE sessionid = '$id'))";
		if (!$mysqli->query($query)) throw new Exception('delete_session - delete bills - '.$mysqli->sqlstate.' - '. $mysqli->error);
		// Delete all registrations (and child) from this session
		$query = "DELETE FROM cpa_registrations WHERE sessionid = '$id'";
		if (!$mysqli->query($query)) throw new Exception('delete_session - delete registrations - '.$mysqli->sqlstate.' - '. $mysqli->error);
		// Delete all dates for the session's courses (and child) from this session
		$query = "DELETE FROM `cpa_sessions_courses_dates` WHERE sessionscoursesid IN (SELECT id FROM cpa_sessions_courses WHERE sessionid = '$id')";
		if (!$mysqli->query($query)) throw new Exception('delete_session - delete courses dates - '.$mysqli->sqlstate.' - '. $mysqli->error);
		// Delete all courses (and child) from this session
		$query = "DELETE FROM cpa_sessions_courses WHERE sessionid ='$id'";
		if (!$mysqli->query($query)) throw new Exception('delete_session - delete courses - '.$mysqli->sqlstate.' - '. $mysqli->error);
		// Delete all texts from rules
		$query = "DELETE FROM cpa_ws_text WHERE id IN (SELECT title FROM cpa_sessions_rules2 WHERE sessionid = $id) OR id IN (SELECT subtitle FROM cpa_sessions_rules2 WHERE sessionid = $id) OR id IN (SELECT paragraphtext FROM cpa_sessions_rules2 WHERE sessionid = $id)";
		if (!$mysqli->query($query)) throw new Exception('delete_session - delete rules texts - '.$mysqli->sqlstate.' - '. $mysqli->error);
		// Delete the session
		$query = "DELETE FROM cpa_sessions WHERE id = '$id'";
		if ($mysqli->query($query)) {
			$data['success'] = true;
			$data['message'] = 'Session deleted successfully.';
			echo json_encode($data);
			exit;
		} else {
			throw new Exception('delete_session - delete sessions - '.$mysqli->sqlstate.' - '. $mysqli->error);
		}
	} catch (Exception $e) {
		$data = array();
		$data['success'] = false;
		$data['message'] = $e->getMessage();
		echo json_encode($data);
		exit;
	}
};

/**
 * This function gets list of all sessions from database
 */
function getAllSessions($mysqli) {
	try {
		$query = "SELECT id, name, active FROM cpa_sessions ORDER BY startdate desc";
		$result = $mysqli->query($query);
		$data = array();
		while ($row = $result->fetch_assoc()) {
			$data['data'][] = $row;
		}
		$data['success'] = true;
		echo json_encode($data);exit;
	} catch (Exception $e) {
		$data = array();
		$data['success'] = false;
		$data['message'] = $e->getMessage();
		echo json_encode($data);
		exit;
	}
};

/**
 * This function gets the session ice times from database
 * @param integer $sessionid	The id of the session from cpa_sessions
 * @param string $language	The language string, 'fr-ca' or 'en-ca'
 */
function getSessionIcetimes($mysqli, $sessionid, $language) {
	try {
		if (empty($sessionid)) throw new Exception("Invalid session.");
		$query = "	SELECT csi.*, (select getTextLabel(cai.label, '$language') from cpa_arenas_ices cai WHERE cai.arenaid = csi.arenaid AND cai.id = csi.iceid) icelabel
					FROM cpa_sessions_icetimes csi
					WHERE sessionid = $sessionid
					ORDER BY day, starttime, arenaid, iceid";
		$result = $mysqli->query($query);
		$data = array();
		$data['data'] = array();
		while ($row = $result->fetch_assoc()) {
			$data['data'][] = $row;
		}
		$data['success'] = true;
		return $data;
		exit;
	} catch (Exception $e) {
		$data = array();
		$data['success'] = false;
		$data['message'] = $e->getMessage();
		echo json_encode($data);
		exit;
	}
};

/**
 * This function gets the details of all schedules for a session course
 * @param integer $sessionscoursesid	The course id in the session
 * @param string $language	The language string, 'fr-ca' or 'en-ca'
 */
function getSessionCourseSchedule($mysqli, $sessionscoursesid, $language) {
	try {
		if (empty($sessionscoursesid)) throw new Exception("Invalid session course.");
		$query = "	SELECT *, (select getTextLabel(cai.label, '$language') from cpa_arenas_ices cai WHERE cai.arenaid = cscs.arenaid AND cai.id = cscs.iceid) icelabel
					FROM cpa_sessions_courses_schedule cscs
					WHERE sessionscoursesid = $sessionscoursesid
					ORDER BY day, starttime";
		$result = $mysqli->query($query);
		$data = array();
		$data['data'] = array();
		while ($row = $result->fetch_assoc()) {
			$data['data'][] = $row;
		}
		$data['success'] = true;
		return $data;
	} catch (Exception $e) {
		$data = array();
		$data['success'] = false;
		$data['message'] = $e->getMessage();
		return $data;
	}
};

/**
 * This function gets the details of all sublevels for a session course
 * @param integer $sessionscoursesid	The course id in the session
 */
function getSessionCourseSublevels($mysqli, $sessionscoursesid) {
	try {
		if (empty($sessionscoursesid)) throw new Exception("Invalid session course.");
		$query = "	SELECT *, getEnglishTextLabel(label) label_en, getFrenchTextLabel(label) label_fr
					FROM cpa_sessions_courses_sublevels cscs
					WHERE sessionscoursesid = $sessionscoursesid
					ORDER BY sequence";
		$result = $mysqli->query($query);
		$data = array();
		$data['data'] = array();
		while ($row = $result->fetch_assoc()) {
			$row['sequence'] = (int)$row['sequence'];
			$data['data'][] = $row;
		}
		$data['success'] = true;
		return $data;
	} catch (Exception $e) {
		$data = array();
		$data['success'] = false;
		$data['message'] = $e->getMessage();
		return $data;
	}
};

/**
 * This function gets the details of all staffs for a session course
 * @param integer $sessionscoursesid	The course id in the session
 * @param string $language	The language string, 'fr-ca' or 'en-ca'
 */
function getSessionCourseStaffs($mysqli, $sessionscoursesid, $language) {
	try {
		if (empty($sessionscoursesid)) throw new Exception("Invalid session course.");
		$query = "	SELECT 	cscs.*, concat(cm.lastname, ', ', cm.firstname) name, getCodeDescription('staffcodes', cscs.staffcode, '$language') staffcodelabel, 
							getCodeDescription('personnelstatus', cscs.statuscode, '$language') statuscodelabel
					FROM cpa_sessions_courses_staffs cscs
					JOIN cpa_members cm ON cm.id = cscs.memberid
					WHERE sessionscoursesid = $sessionscoursesid
					ORDER BY staffcode, cm.lastname, cm.firstname";
		$result = $mysqli->query($query);
		$data = array();
		$data['data'] = array();
		while ($row = $result->fetch_assoc()) {
			$data['data'][] = $row;
		}
		$data['success'] = true;
		return $data;
	} catch (Exception $e) {
		$data = array();
		$data['success'] = false;
		$data['message'] = $e->getMessage();
		return $data;
	}
};

/**
 * This function gets the details of all dates for a session course
 * @param integer $sessionscoursesid	The course id in the session
 * @param string $language	The language string, 'fr-ca' or 'en-ca'
 */
function getSessionCourseDates($mysqli, $sessionscoursesid, $coursename, $language) {
	try {
		if (empty($sessionscoursesid)) throw new Exception("Invalid session course.");
		$query = "	SELECT 	cscd.*, getTextLabel(label, 'fr-ca') label_fr, getTextLabel(label, 'en-ca') label_en,
							(select getTextLabel(cai.label, '$language') from cpa_arenas_ices cai WHERE cai.arenaid = cscd.arenaid AND cai.id = cscd.iceid) icelabel,
							(select getTextLabel(ca.label, '$language') from cpa_arenas ca WHERE ca.id = cscd.arenaid) arenalabel,
							getCodeDescription('days', cscd.day, '$language') daylabel,
							getCodeDescription('yesno', cscd.canceled, '$language') canceledlabel
					FROM cpa_sessions_courses_dates cscd
					WHERE cscd.sessionscoursesid = $sessionscoursesid
					ORDER BY cscd.coursedate";
		$result = $mysqli->query($query);
		$data = array();
		$data['data'] = array();
		while ($row = $result->fetch_assoc()) {
			$row['coursename'] = $coursename;
			$data['data'][] = $row;
		}
		$data['success'] = true;
		return $data;
	} catch (Exception $e) {
		$data = array();
		$data['success'] = false;
		$data['message'] = $e->getMessage();
		return $data;
	}
};

/**
 * This function gets the details of all session's charges
 * @param integer $sessionid	The id of the session from cpa_sessions
 * @param string $language	The language string, 'fr-ca' or 'en-ca'
 */
function getSessionCharges($mysqli, $sessionid, $language) {
	try {
		if (empty($sessionid)) throw new Exception("Invalid session.");
		$query = "	SELECT 	csc.*, getTextLabel(cc.label, '$language') chargelabel
					FROM cpa_sessions_charges csc
					JOIN cpa_charges cc ON cc.code = csc.chargecode
					WHERE sessionid = $sessionid
					AND cc.issystem = 0
					ORDER BY chargecode";
		$result = $mysqli->query($query);
		$data = array();
		$data['data'] = array();
		while ($row = $result->fetch_assoc()) {
			$data['data'][] = $row;
		}
		$data['success'] = true;
		return $data;
	} catch (Exception $e) {
		$data = array();
		$data['success'] = false;
		$data['message'] = $e->getMessage();
		return $data;
	}
};

/**
 * This function gets the details of all session's courses
 * @param integer $sessionid	The id of the session from cpa_sessions
 * @param string $language	The language string, 'fr-ca' or 'en-ca'
 */
function getSessionCourses($mysqli, $sessionid, $language) {
	try {
		if (empty($sessionid)) throw new Exception("Invalid session.");
		$query = "	SELECT 	csc.*, getTextLabel(csc.label, '$language') courselabel, getTextLabel(csc.label, 'en-ca') label_en, getTextLabel(csc.label, 'fr-ca') label_fr, getTextLabel(ccl.label, '$language') levellabel, 
							csc.availableonline, getCodeDescription('yesno', csc.availableonline, '$language') availableonlinelabel,
							getCodeDescription('yesno', csc.datesgenerated, '$language') datesgeneratedlabel
					FROM cpa_sessions_courses csc
					JOIN cpa_courses cc ON cc.code = csc.coursecode
					LEFT JOIN cpa_courses_levels ccl ON ccl.coursecode = cc.code AND ccl.code = csc.courselevel
					WHERE sessionid = '$sessionid'
					ORDER BY name";
		$result = $mysqli->query($query);
		$data = array();
		$data['data'] = array();
		while ($row = $result->fetch_assoc()) {
			$row['prereqagemin'] = isset($row['prereqagemin']) ? (int)$row['prereqagemin'] : null;
			$row['prereqagemax'] = isset($row['prereqagemax']) ? (int)$row['prereqagemax'] : null;
			$row['schedules'] = getSessionCourseSchedule($mysqli, $row['id'], $language)['data'];
			$row['dates'] = 	getSessionCourseDates($mysqli, $row['id'], $row['name'], $language)['data'];
			$row['staffs'] = 	getSessionCourseStaffs($mysqli, $row['id'], $language)['data'];
			$row['sublevels'] = getSessionCourseSublevels($mysqli, $row['id'])['data'];
			$row['fees'] = (float)$row['fees'];
			$data['data'][] = $row;
		}
		$data['success'] = true;
		return $data;
	} catch (Exception $e) {
		$data = array();
		$data['success'] = false;
		$data['message'] = $e->getMessage();
		return $data;
	}
};

/**
 * This function gets the details of ONE session course
 * @param integer $sessionscoursesid	The course id in the session
 * @param string $language	The language string, 'fr-ca' or 'en-ca'
 */
function getOneSessionCourse($mysqli, $sessionscoursesid, $language) {
	if (empty($sessionscoursesid)) throw new Exception("Invalid session course id.");
	$query = "	SELECT 	csc.*, getTextLabel(csc.label, '$language') courselabel, getTextLabel(csc.label, 'en-ca') label_en, getTextLabel(csc.label, 'fr-ca') label_fr, getTextLabel(ccl.label, '$language') levellabel, 
						csc.availableonline, getCodeDescription('yesno', csc.availableonline, '$language') availableonlinelabel,
						getCodeDescription('yesno', csc.datesgenerated, '$language') datesgeneratedlabel
				FROM cpa_sessions_courses csc
				JOIN cpa_courses cc ON cc.code = csc.coursecode
				LEFT JOIN cpa_courses_levels ccl ON ccl.coursecode = cc.code AND ccl.code = csc.courselevel
				WHERE csc.id = '$sessionscoursesid'
				ORDER BY name";
	$result = $mysqli->query($query);
	$data = array();
	$data['data'] = array();
	while ($row = $result->fetch_assoc()) {
		$row['schedules'] = getSessionCourseSchedule($mysqli, $row['id'], $language)['data'];
		$row['dates'] = 	getSessionCourseDates($mysqli, $row['id'], $row['name'], $language)['data'];
		$row['staffs'] = 	getSessionCourseStaffs($mysqli, $row['id'], $language)['data'];
		$row['sublevels'] = getSessionCourseSublevels($mysqli, $row['id'])['data'];
		$row['fees'] = (float)$row['fees'];
		$data['data'][] = $row;
	}
	$data['success'] = true;
	return $data;
};

/**
 * This function gets the details of all registrations for a session
 * @param integer $sessionid	The id of the session from cpa_sessions
 * @param string $language	The language string, 'fr-ca' or 'en-ca'
 */
function getSessionRegistrations($mysqli, $sessionid, $language) {
	try {
		if (empty($sessionid)) throw new Exception("Invalid session.");
		$query = "	SELECT *
					FROM cpa_sessions_registrations
					WHERE sessionid = '$sessionid'
					ORDER BY registrationdate, starttime";
		$result = $mysqli->query($query);
		$data = array();
		$data['data'] = array();
		while ($row = $result->fetch_assoc()) {
			$data['data'][] = $row;
		}
		$data['success'] = true;
		return $data;
	} catch (Exception $e) {
		$data = array();
		$data['success'] = false;
		$data['message'] = $e->getMessage();
		return $data;
	}
};

/**
 * This function gets the details of all events for a session
 * @param integer $sessionid	The id of the session from cpa_sessions
 * @param string $language	The language string, 'fr-ca' or 'en-ca'
 */
function getSessionEvents($mysqli, $sessionid, $language) {
	if (empty($sessionid)) throw new Exception("Invalid session.");
	$query = "	SELECT id, eventdate, type, label, getEnglishTextLabel(label) label_en, getFrenchTextLabel(label) label_fr
				FROM cpa_sessions_dates
				WHERE sessionid = '$sessionid'
				ORDER BY eventdate";
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
 * This function gets the details of all rules for a session
 * @param integer $sessionid	The id of the session from cpa_sessions
 * @param string $language	The language string, 'fr-ca' or 'en-ca'
 */
function getSessionRules($mysqli, $sessionid, $language) {
	if (empty($sessionid)) throw new Exception("Invalid session.");
	$result = $mysqli->query("SET NAMES utf8");
	$query = "	SELECT convert(rules using cp1256) as rules
				FROM cpa_sessions_rules
				WHERE sessionid = $sessionid
				AND language = '$language'";
	$result = $mysqli->query($query);
	$data = array();
	$data['data'] = array();
	$row = $result->fetch_assoc();
	// $data['data'] = utf8_decode($row['rules']);
	// $data['data'] = mb_convert_encoding($row['rules'], 'UTF-8');
	$data['data'] = (empty($row['rules']) ? null:$row['rules']);
	// $data['data'] = iconv("UTF-8", "CP1252", $row['rules']);
	$data['success'] = true;
	return $data;
};

/**
 * This function gets the details of all rules for a session
 * 
 * @param integer $sessionid	The id of the show from cpa_shows
 */
function getSessionRulesParagraphs($mysqli, $sessionid, $language) {
	if (empty($sessionid)) throw new Exception("Invalid session.");
	$query = "	SELECT 	csr.*, getWSTextLabel(csr.paragraphtext, 'fr-ca') paragraphtext_fr, getWSTextLabel(csr.paragraphtext, 'en-ca') paragraphtext_en,
					 	getWSTextLabel(csr.title, 'fr-ca') title_fr, getWSTextLabel(csr.title, 'en-ca') title_en,
						getWSTextLabel(csr.subtitle, 'fr-ca') subtitle_fr, getWSTextLabel(csr.subtitle, 'en-ca') subtitle_en
				FROM cpa_sessions_rules2 csr
				WHERE csr.sessionid = $sessionid
				ORDER BY paragraphindex";
	$result = $mysqli->query($query);
	$data = array();
	$data['data'] = array();
	while ($row = $result->fetch_assoc()) {
		$row['id'] = (int)$row['id'];
		$row['sessionid'] = (int)$row['sessionid'];
		$row['paragraphindex'] = (int)$row['paragraphindex'];
		$data['data'][] = $row;
	}
	$data['success'] = true;
	return $data;
};

/**
 * This function gets the details of one session from database
 * @param string $language	The language string, 'fr-ca' or 'en-ca'
 */
function getSessionDetails($mysqli, $id, $language) {
	try {
		$query = "	SELECT *, getEnglishTextLabel(label) as label_en, getFrenchTextLabel(label) as label_fr
					FROM cpa_sessions
					WHERE id = $id";
		$result = $mysqli->query($query);
		$data = array();
		while ($row = $result->fetch_assoc()) {
			$row['isonlineregistactive'] 		= (int)$row['isonlineregistactive'];
			$row['isonlinepreregistactive'] 	= (int)$row['isonlinepreregistactive'];
			$row['isonlinepreregistemail'] 		= (int)$row['isonlinepreregistemail'];
			// $row['onlinepreregistemailtpl'] 	= (int)$row['onlinepreregistemailtpl'];
			// $row['onlinepaymentoption'] 		= (int)$row['onlinepaymentoption'];
			$row['isonlineregistemail'] 		= (int)$row['isonlineregistemail'];
			// $row['onlineregistemailtpl'] 		= (int)$row['onlineregistemailtpl'];
			$row['isonlineregistemailinclbill'] = (int)$row['isonlineregistemailinclbill'];
			$row['icetimes'] = 			getSessionIcetimes($mysqli, $id, $language)['data'];
			$row['sessionCharges'] = 	getSessionCharges($mysqli, $id, $language)['data'];
			$row['sessionCourses'] = 	getSessionCourses($mysqli, $id, $language)['data'];
			$row['registrations'] = 	getSessionRegistrations($mysqli, $id, $language)['data'];
			$row['events'] = 			getSessionEvents($mysqli, $id, $language)['data'];
			$row['rulesfr'] = 			getSessionRules($mysqli, $id, 'fr-ca')['data'];
			$row['rulesen'] = 			getSessionRules($mysqli, $id, 'en-ca')['data'];
			$row['coursecodes'] = 		getSessionCourseCodes($mysqli, $id, $language)['data'];
			$row['rules2'] = 			getSessionRulesParagraphs($mysqli, $id, $language)['data'];
			$data['data'][] = $row;
		}
		$data['success'] = true;
		echo json_encode($data);
		exit;
	} catch (Exception $e) {
		$data = array();
		$data['success'] = false;
		$data['message'] = $e->getMessage();
		return $data;
	}
};

/**
 * This function will delete all course's dates that do not already have an attendance.
 * @param integer $sessionscoursesid	The course id in the session
 */
function deleteCourseDates($mysqli, $sessionscoursesid) {
	$data = array();
	$query = "	DELETE FROM cpa_sessions_courses_dates
				WHERE sessionscoursesid = $sessionscoursesid
				AND NOT EXISTS (SELECT * FROM cpa_sessions_courses_presences cscp WHERE cscp.sessionscoursesdatesid = cpa_sessions_courses_dates.id AND ispresent = 1)";
	$result = $mysqli->query($query);
	$data['deleted'] = $mysqli -> affected_rows;
	$data['success'] = true;
	return $data;
	exit;
};

/**
 * This function will handle course date insert functionality. 
 * @param object[] $sessionCourseDates
 * @param string $language	The language string, 'fr-ca' or 'en-ca'
 * @throws Exception
 */
function insertCourseDate($mysqli, $sessionCourseDates, $language) {
	try {
		$data = array();
		$data['count'] = count($sessionCourseDates);
		$data['inserted'] = 0;
	if (count($sessionCourseDates) > 0) {
			$sessionscoursesid = isset($sessionCourseDates[0]['sessionscoursesid']) ? $mysqli->real_escape_string((int) $sessionCourseDates[0]['sessionscoursesid']) : 0;
			$data['deletedates'] = deleteCourseDates($mysqli, $sessionscoursesid);
			for ($x = 0; $x < count($sessionCourseDates); $x++) {
				$sessionscoursesid = isset($sessionCourseDates[$x]['sessionscoursesid'])	? $mysqli->real_escape_string($sessionCourseDates[$x]['sessionscoursesid']) : '';
				$coursedate =		 isset($sessionCourseDates[$x]['coursedatestr']) 		? $mysqli->real_escape_string($sessionCourseDates[$x]['coursedatestr']) : '';
				$arenaid =			 isset($sessionCourseDates[$x]['arenaid']) 				? $mysqli->real_escape_string($sessionCourseDates[$x]['arenaid']) : '';
				$iceid =			 isset($sessionCourseDates[$x]['iceid']) 				? $mysqli->real_escape_string($sessionCourseDates[$x]['iceid']) : '0';
				$starttime =		 isset($sessionCourseDates[$x]['starttime']) 			? $mysqli->real_escape_string($sessionCourseDates[$x]['starttime']) : '';
				$endtime =			 isset($sessionCourseDates[$x]['endtime']) 				? $mysqli->real_escape_string($sessionCourseDates[$x]['endtime']) : '';
				$duration =			 isset($sessionCourseDates[$x]['duration']) 			? $mysqli->real_escape_string($sessionCourseDates[$x]['duration']) : '';
				$day =				 isset($sessionCourseDates[$x]['day']) 					? $mysqli->real_escape_string((int)$sessionCourseDates[$x]['day']) : '';

				// We need to check if there is already a course's date with the same info
				// Remember, there is already a course at that date because there were attendances for this course in the DB
				$query = "	SELECT * 
							FROM cpa_sessions_courses_dates 
							WHERE sessionscoursesid = '$sessionscoursesid' AND coursedate = '$coursedate'";
				$result = $mysqli->query($query);
				$row = $result->fetch_assoc();
				if (!isset($row['sessionscoursesid'])) {
					// Insert only if there is not already a course on this date
					$query = "	INSERT INTO cpa_sessions_courses_dates (sessionscoursesid, coursedate, arenaid, iceid, starttime, endtime, duration, day)
								VALUES ('$sessionscoursesid', '$coursedate', '$arenaid', '$iceid', '$starttime', '$endtime', '$duration', $day)";
					if ($mysqli->query($query)) {
						$data['inserted']++;
					} else {
						throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
					}
				}
			}
			$data['updateflag'] = updateSessionCourseGeneratedFlag($mysqli, $sessionscoursesid);
			$data['course'] = getOneSessionCourse($mysqli, $sessionscoursesid, $language)['data'];
			$data['success'] = true;
		}
		$mysqli->close();
		echo json_encode($data);
		exit;
	} catch (Exception $e) {
		$data = array();
		$data['success'] = false;
		$data['message'] = $e->getMessage();
		echo json_encode($data);
		exit;
	}
};

/**
 * This function set the datesgenerated flag to true for a session course
 * @param integer $sessionscoursesid	The course id in the session
 * @throws Exception
 */
function updateSessionCourseGeneratedFlag($mysqli, $sessionscoursesid) {
	$data = array();
	$query = "UPDATE cpa_sessions_courses SET datesgenerated = 1 WHERE id = $sessionscoursesid";
	if ($mysqli->query($query)) {
		$data['success'] = true;
	} else {
		throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
	}
	return $data;
};

/**
 * This function gets the levels for a course
 * @param string $language	The language string, 'fr-ca' or 'en-ca'
 */
function getCourseLevels($mysqli, $code, $language) {
	try {
		$query = "	SELECT code, getTextLabel(label, '$language') label
					FROM cpa_courses_levels
					WHERE coursecode = '$code'";
		$result = $mysqli->query($query);
		$data = array();
		while ($row = $result->fetch_assoc()) {
			$data['data'][] = $row;
		}
		$data['success'] = true;
		echo json_encode($data);
		exit;
	} catch (Exception $e) {
		$data = array();
		$data['success'] = false;
		$data['message'] = $e->getMessage();
		return $data;
	}
};

?>
