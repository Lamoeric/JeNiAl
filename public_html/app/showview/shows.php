<?php
/*
Author : Eric Lamoureux
*/
require_once('../../../private/'. $_SERVER['HTTP_HOST'].'/include/config.php');
require_once('../../include/nocache.php');
require_once('../../backend/invalidrequest.php');

if (isset($_POST['type']) && !empty(isset($_POST['type']))) {
	$type = $_POST['type'];

	switch ($type) {
		case "updateEntireShow":
			updateEntireShow($mysqli, json_decode($_POST['show'], true));
			break;
		case "insert_show":
			insert_show($mysqli, $_POST['show']);
			break;
		case "delete_show":
			delete_show($mysqli, json_decode($_POST['show'], true));
			break;
		case "getAllShows":
			getAllShows($mysqli);
			break;
		case "getShowDetails":
			getShowDetails($mysqli, $_POST['id'], $_POST['language']);
			break;
		case "insertPracticeDate":
			insertPracticeDate($mysqli, $_POST['practicedates'], $_POST['language']);
			break;
		default:
			invalidRequest();
	}
} else {
	invalidRequest();
};

/**
 * 
 * @param integer $showid	The id of the show from cpa_shows
 * @param integer $numberid	The id of the show from cpa_shows_numbers
 */
function updateEntireShowNumberSchedule($mysqli, $showid, $numberid, $schedules) {
	$data = array();
	$data['inserted'] = 0;
	$data['updated'] = 0;
	$data['deleted'] = 0;
	for ($x = 0; $x < count($schedules); $x++) {
		$id =			$mysqli->real_escape_string(isset($schedules[$x]['id'])				? (int)$schedules[$x]['id'] : '');
		$arenaid =		$mysqli->real_escape_string(isset($schedules[$x]['arenaid'])		? (int)$schedules[$x]['arenaid'] : '');
		$iceid =		$mysqli->real_escape_string(isset($schedules[$x]['iceid'])			? (int)$schedules[$x]['iceid'] : '0');
		$day =			$mysqli->real_escape_string(isset($schedules[$x]['day'])			? $schedules[$x]['day'] : '');
		$starttime =	$mysqli->real_escape_string(isset($schedules[$x]['starttimestr'])	? $schedules[$x]['starttimestr'] : '');
		$endtime =		$mysqli->real_escape_string(isset($schedules[$x]['endtimestr'])		? $schedules[$x]['endtimestr'] : '');
		$duration =		$mysqli->real_escape_string(isset($schedules[$x]['duration'])		? $schedules[$x]['duration'] : '');

		if ($mysqli->real_escape_string(isset($schedules[$x]['status'])) and $schedules[$x]['status'] == 'New') {
			$query = "	INSERT into cpa_shows_numbers_schedule (showid, numberid, arenaid, iceid, day, starttime, endtime, duration)
						VALUES ($showid, $numberid, $arenaid, $iceid, '$day', '$starttime', '$endtime', '$duration')";
			if ($mysqli->query($query)) {
				$schedules[$x]['id'] = $mysqli->insert_id;
				$data['inserted']++;
			} else {
				throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
			}
		}

		if ($mysqli->real_escape_string(isset($schedules[$x]['status'])) and $schedules[$x]['status'] == 'Modified') {
			$query = "	UPDATE cpa_shows_numbers_schedule 
						SET arenaid = $arenaid, iceid = $iceid, day = '$day', starttime = '$starttime', endtime = '$endtime', duration = '$duration' 
						WHERE id = $id";
			if ($mysqli->query($query)) {
				$data['updated']++;
			} else {
				throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
			}
		}

		if ($mysqli->real_escape_string(isset($schedules[$x]['status'])) and $schedules[$x]['status'] == 'Deleted') {
			$query = "DELETE FROM cpa_shows_numbers_schedule WHERE id = $id";
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
 * @param integer $showid	The id of the show from cpa_shows
 * @param integer $numberid	The id of the show from cpa_shows_numbers
 */
function updateEntireShowNumberDates($mysqli, $showid, $numberid, $dates) {
	$data = array();
	$data['inserted'] = 0;
	$data['updated'] = 0;
	$data['deleted'] = 0;
	for ($x = 0; $x < count($dates); $x++) {
		$id = 			$mysqli->real_escape_string(isset($dates[$x]['id']) 				? (int)$dates[$x]['id'] : 0);
		$practicedate =	$mysqli->real_escape_string(isset($dates[$x]['practicedatestr']) 	? $dates[$x]['practicedatestr'] : '');
		$arenaid = 		$mysqli->real_escape_string(isset($dates[$x]['arenaid']) 			? (int)$dates[$x]['arenaid'] : 0);
		$iceid = 		$mysqli->real_escape_string(isset($dates[$x]['iceid']) 				? (int)$dates[$x]['iceid'] : 0);
		$starttime = 	$mysqli->real_escape_string(isset($dates[$x]['starttimestr']) 		? $dates[$x]['starttimestr'] : '');
		$endtime = 		$mysqli->real_escape_string(isset($dates[$x]['endtimestr']) 		? $dates[$x]['endtimestr'] : '');
		$duration = 	$mysqli->real_escape_string(isset($dates[$x]['duration']) 			? (int)$dates[$x]['duration'] : 0);
		$canceled = 	$mysqli->real_escape_string(isset($dates[$x]['canceled']) && !empty($dates[$x]['canceled'])	? (int)$dates[$x]['canceled'] : 0);
		$manual = 		$mysqli->real_escape_string(isset($dates[$x]['manual']) 			? (int)$dates[$x]['manual'] : 0);
		$day =			$mysqli->real_escape_string(isset($dates[$x]['day']) 				? (int)$dates[$x]['day'] : 0);

		if ($mysqli->real_escape_string(isset($dates[$x]['status'])) and $dates[$x]['status'] == 'New') {
			$query = "	INSERT into cpa_shows_numbers_dates (showid, numberid, arenaid, iceid, practicedate, starttime, endtime, duration, canceled, manual, day)
						VALUES ($showid, $numberid, $arenaid, $iceid, '$practicedate', '$starttime', '$endtime', $duration, $canceled, $manual, $day)";
			if ($mysqli->query($query)) {
				$schedules[$x]['id'] = $mysqli->insert_id;
				$data['inserted']++;
			} else {
				throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
			}
		}

		if ($mysqli->real_escape_string(isset($dates[$x]['status'])) and $dates[$x]['status'] == 'Modified') {
			$query = "	UPDATE cpa_shows_numbers_dates 
						SET canceled = $canceled, manual = $manual, practicedate = '$practicedate', starttime = '$starttime', endtime = '$endtime', duration = $duration 
						WHERE id = $id";
			if (!$mysqli->query($query)) {
				throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
			}
		}

		if ($mysqli->real_escape_string(isset($dates[$x]['status'])) and $dates[$x]['status'] == 'Deleted') {
			$query = "DELETE FROM cpa_shows_numbers_dates WHERE id = $id";
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
 * @param integer $showid	The id of the show from cpa_shows
 * @param integer $numberid	The id of the show from cpa_shows_numbers
 */
function updateEntireShowNumberStaff($mysqli, $showid, $numberid, $staffs) {
	$data = array();
	$data['inserted'] = 0;
	$data['updated'] = 0;
	$data['deleted'] = 0;
	for ($x = 0; $x < count($staffs); $x++) {
		$id =			$mysqli->real_escape_string(isset($staffs[$x]['id'])		? (int)$staffs[$x]['id'] : 0);
		$memberid =		$mysqli->real_escape_string(isset($staffs[$x]['memberid'])	? (int)$staffs[$x]['memberid'] : 0);
		$staffcode =	$mysqli->real_escape_string(isset($staffs[$x]['staffcode'])	? $staffs[$x]['staffcode'] : '');

		if ($mysqli->real_escape_string(isset($staffs[$x]['status'])) and $staffs[$x]['status'] == 'New') {
			$query = "	INSERT into cpa_shows_numbers_staffs (numberid, showid, memberid, staffcode)
						VALUES ($numberid, $showid, $memberid, '$staffcode')";
			if ($mysqli->query($query)) {
				$staffs[$x]['id'] = $mysqli->insert_id;
				$data['inserted']++;
			} else {
				throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
			}
		}

		if ($mysqli->real_escape_string(isset($staffs[$x]['status'])) and $staffs[$x]['status'] == 'Modified') {
			$query = "UPDATE cpa_shows_numbers_staffs SET memberid = $memberid, staffcode = '$staffcode' WHERE id = $id";
			if ($mysqli->query($query)) {
				$data['updated']++;
			} else {
				throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
			}
		}

		if ($mysqli->real_escape_string(isset($staffs[$x]['status'])) and $staffs[$x]['status'] == 'Deleted') {
			$query = "DELETE FROM cpa_shows_numbers_staffs WHERE id = $id";
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
 * @param integer $showid	The id of the show from cpa_shows
 */
function updateEntireShowNumberInvite($mysqli, $showid, $number, $members) {
	$data = array();
	$data['inserted'] = 0;
	if (isset($number['membersdirty']) && $number['membersdirty'] == 1) {
		$numberid = (int)$number['id'];
		// Delete all invites for a show number
		$query = "DELETE FROM cpa_shows_numbers_invites WHERE showid = $showid and numberid = $numberid";
		if (!$mysqli->query($query)) {
			throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
		}

		// Insert all invites
		for ($x = 0; $x < count($members); $x++) {
			$groupormemberid = 	$mysqli->real_escape_string(isset($members[$x]['id']) 	? (int)$members[$x]['id'] : 0);
			$query = "	INSERT into cpa_shows_numbers_invites (numberid, showid, groupormemberid, type)
						VALUES ($numberid, $showid, $groupormemberid, 2)";
			if ($mysqli->query($query)) {
				$data['inserted']++;
			} else {
				throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
			}
		}
	}
	return $data;
};

/**
 * 
 * @param integer $showid	The id of the show from cpa_shows
 */
function updateEntireShowCharges($mysqli, $showid, $showCharges) {
	$data = array();
	for ($x = 0; $x < count($showCharges); $x++) {
		$id = 			isset($showCharges[$x]['id'])			? $mysqli->real_escape_string($showCharges[$x]['id']) : '';
		$chargecode =	isset($showCharges[$x]['chargecode'])	? $mysqli->real_escape_string($showCharges[$x]['chargecode']) : '';
		$amount = 		isset($showCharges[$x]['amount'])		? $mysqli->real_escape_string($showCharges[$x]['amount']) : '0.00';
		$startdate = 	isset($showCharges[$x]['startdate'])	? $mysqli->real_escape_string($showCharges[$x]['startdate']) : null;
		$enddate = 		isset($showCharges[$x]['enddate'])		? $mysqli->real_escape_string($showCharges[$x]['enddate']) : null;

		if ($mysqli->real_escape_string(isset($showCharges[$x]['status'])) and $showCharges[$x]['status'] == 'New') {
			$query = "	INSERT into cpa_shows_charges (showid, chargecode, amount, startdate, enddate)
						VALUES ('$showid', '$chargecode', '$amount'";
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
				$showCharges[$x]['id'] = $mysqli->insert_id;
			} else {
				throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
			}
		}

		if ($mysqli->real_escape_string(isset($showCharges[$x]['status'])) and $showCharges[$x]['status'] == 'Modified') {
			$query = "UPDATE cpa_shows_charges SET chargecode = '$chargecode', amount =  '$amount'";
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

			if ($mysqli->query($query)) {
			} else {
				throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
			}
		}

		if ($mysqli->real_escape_string(isset($showCharges[$x]['status'])) and $showCharges[$x]['status'] == 'Deleted') {
			$query = "DELETE FROM cpa_shows_charges WHERE id = '$id'";
			if ($mysqli->query($query)) {
			} else {
				throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
			}
		}
	}
	return $data;
};

/**
 * 
 * @param integer $showid	The id of the show from cpa_shows
 */
function updateEntireShowParagraphs($mysqli, $showid, $paragraphs) {
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
			$query = "	INSERT INTO cpa_shows_paragraphs (id, showid, paragraphindex, publish, visiblepreview, title, subtitle, paragraphtext)
						VALUES (null, $showid, $paragraphindex, $publish, $visiblepreview, create_wsText('$title_en', '$title_fr'), 
								create_wsText('$subtitle_en', '$subtitle_fr'), create_wsText('$paragraphtext_en', '$paragraphtext_fr'))";
			if (!$mysqli->query($query)) {
				throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
			}
			$paragraphs[$x]['id'] = (int) $mysqli->insert_id;
		}

		// If no status or (status != deleted AND status != New)
		if ($mysqli->real_escape_string(isset($paragraphs[$x]['status'])) and $paragraphs[$x]['status'] == 'Modified') {
			$query = "UPDATE cpa_shows_paragraphs SET publish = $publish, visiblepreview = $visiblepreview	WHERE id = $id";
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
			$query = "DELETE FROM cpa_shows_paragraphs WHERE id = $id";
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
			$query = "UPDATE cpa_shows_paragraphs SET paragraphindex = $realIndex WHERE id = $id";
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
 * 
 * @param integer $showid	The id of the show from cpa_shows
 */
function updateEntireShowRules($mysqli, $showid, $paragraphs) {
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
			$query = "	INSERT INTO cpa_shows_rules (id, showid, paragraphindex, publish, visiblepreview, title, subtitle, paragraphtext)
						VALUES (null, $showid, $paragraphindex, $publish, $visiblepreview, create_wsText('$title_en', '$title_fr'), 
								create_wsText('$subtitle_en', '$subtitle_fr'), create_wsText('$paragraphtext_en', '$paragraphtext_fr'))";
			if (!$mysqli->query($query)) {
				throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
			}
			$paragraphs[$x]['id'] = (int) $mysqli->insert_id;
		}

		// If no status or (status != deleted AND status != New)
		if ($mysqli->real_escape_string(isset($paragraphs[$x]['status'])) and $paragraphs[$x]['status'] == 'Modified') {
			$query = "UPDATE cpa_shows_rules SET publish = $publish, visiblepreview = $visiblepreview WHERE id = $id";
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
			$query = "DELETE FROM cpa_shows_rules WHERE id = $id";
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
			$query = "UPDATE cpa_shows_rules SET paragraphindex = $realIndex WHERE id = $id";
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
 * 
 * @param integer $showid	The id of the show from cpa_shows
 */
function updateEntireShowInterventions($mysqli, $showid, $interventions) {
	$data = array();
	for ($x = 0; $x < count($interventions); $x++) {
		$id =		$mysqli->real_escape_string(isset($interventions[$x]['id'])			? (int)$interventions[$x]['id'] : 0);
		$name =		$mysqli->real_escape_string(isset($interventions[$x]['name'])		? $interventions[$x]['name'] : '');
		$label =	$mysqli->real_escape_string(isset($interventions[$x]['label'])		? $interventions[$x]['label'] : '');
		$label_fr =	$mysqli->real_escape_string(isset($interventions[$x]['label_fr'])	? $interventions[$x]['label_fr'] : '');
		$label_en = $mysqli->real_escape_string(isset($interventions[$x]['label_en'])	? $interventions[$x]['label_en'] : '');
		$duration = $mysqli->real_escape_string(isset($interventions[$x]['duration'])	? (int)$interventions[$x]['duration'] : 0);
		$music = 	$mysqli->real_escape_string(isset($interventions[$x]['music'])		? $interventions[$x]['music'] : '');
		$lights = 	$mysqli->real_escape_string(isset($interventions[$x]['lights'])		? $interventions[$x]['lights'] : '');
		$comments = $mysqli->real_escape_string(isset($interventions[$x]['comments'])	? $interventions[$x]['comments'] : '');

		if ($mysqli->real_escape_string(isset($interventions[$x]['status'])) and $interventions[$x]['status'] == 'New') {
			$query = "	INSERT into cpa_shows_numbers (`id`, `showid`, `type`, `name`, `label`, `duration`, `music`, `lights`, `comments`)
						VALUES ('$id', '$showid', 2, '$name', create_systemText('$label_en', '$label_fr'), $duration, '$music', '$lights', '$comments')";
			if ($mysqli->query($query)) {
				$numbers[$x]['id'] = $mysqli->insert_id;
			} else {
				throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
			}
		}

		if ($mysqli->real_escape_string(isset($interventions[$x]['status'])) and $interventions[$x]['status'] == 'Modified') {
			$query = "UPDATE cpa_shows_numbers SET name = '$name', duration = $duration, music = '$music', lights = '$lights', comments = '$comments' WHERE id = $id";
			if ($mysqli->query($query)) {
				$query = "UPDATE cpa_text set text = '$label_fr' where id = $label and language = 'fr-ca'";
				if ($mysqli->query($query)) {
					$query = "UPDATE cpa_text set text = '$label_en' where id = $label and language = 'en-ca'";
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

		if ($mysqli->real_escape_string(isset($interventions[$x]['status'])) and $interventions[$x]['status'] == 'Deleted') {
			$query = "DELETE FROM cpa_text WHERE id = '$label'";
			if ($mysqli->query($query)) {
				$query = "DELETE FROM cpa_shows_numbers WHERE id = $id";
				if ($mysqli->query($query)) {
				} else {
					throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
				}
			} else {
				throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
			}
		}
	}
	return $data;
};

/**
 * 
 * @param integer $showid	The id of the show from cpa_shows
 */
function updateEntireShowNumbers($mysqli, $showid, $numbers) {
	$data = array();
	for ($x = 0; $x < count($numbers); $x++) {
		$id = 					$mysqli->real_escape_string(isset($numbers[$x]['id']) 						? (int)$numbers[$x]['id'] : 0);
		$name = 				$mysqli->real_escape_string(isset($numbers[$x]['name']) 					? $numbers[$x]['name'] : '');
		$label = 				$mysqli->real_escape_string(isset($numbers[$x]['label']) 					? $numbers[$x]['label'] : '');
		$label_fr = 			$mysqli->real_escape_string(isset($numbers[$x]['label_fr']) 				? $numbers[$x]['label_fr'] : '');
		$label_en = 			$mysqli->real_escape_string(isset($numbers[$x]['label_en']) 				? $numbers[$x]['label_en'] : '');
		$fees = 				$mysqli->real_escape_string(isset($numbers[$x]['fees']) 					? $numbers[$x]['fees'] : '0.00');
		$duration = 			$mysqli->real_escape_string(isset($numbers[$x]['duration']) 				? (int)$numbers[$x]['duration'] : 0);
		$music = 				$mysqli->real_escape_string(isset($numbers[$x]['music']) 					? $numbers[$x]['music'] : '');
		$lights = 				$mysqli->real_escape_string(isset($numbers[$x]['lights']) 					? $numbers[$x]['lights'] : '');
		$comments = 			$mysqli->real_escape_string(isset($numbers[$x]['comments']) 				? $numbers[$x]['comments'] : '');
		$datesgenerated = 		$mysqli->real_escape_string(isset($numbers[$x]['datesgenerated']) 			? (int)$numbers[$x]['datesgenerated'] : 0);
		$practicesstartdate =	$mysqli->real_escape_string(isset($numbers[$x]['practicesstartdatestr'])	? $numbers[$x]['practicesstartdatestr'] : '');
		$practicesenddate =		$mysqli->real_escape_string(isset($numbers[$x]['practicesenddatestr']) 		? $numbers[$x]['practicesenddatestr'] : '');
		$registrationtype = 	$mysqli->real_escape_string(isset($numbers[$x]['registrationtype']) 		? (int)$numbers[$x]['registrationtype'] : 0);
		$canbeinperformance =	$mysqli->real_escape_string(isset($numbers[$x]['canbeinperformance']) 		? (int)$numbers[$x]['canbeinperformance'] : 1);
		$mandatory = 			$mysqli->real_escape_string(isset($numbers[$x]['mandatory']) 				? (int)$numbers[$x]['mandatory'] : 0);
																									
		if ($mysqli->real_escape_string(isset($numbers[$x]['status'])) and $numbers[$x]['status'] == 'New') {
			$query = "	INSERT into cpa_shows_numbers (`id`, `showid`, `type`,`name`, `fees`, `label`, `duration`, `music`, `lights`, `comments`, `registrationtype`, `canbeinperformance`,`mandatory`, `practicesstartdate`, `practicesenddate`)
						VALUES ('$id', '$showid', 1, '$name', '$fees', create_systemText('$label_en', '$label_fr'), $duration, '$music', '$lights', '$comments', $registrationtype, $canbeinperformance, $mandatory, "
								.($practicesstartdate == '' ? "null, " : "'$practicesstartdate', ")
								.($practicesenddate == '' ? "null" : "'$practicesenddate'")
								.")";
			if ($mysqli->query($query)) {
				$numbers[$x]['id'] = $mysqli->insert_id;
			} else {
				throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
			}
		}

		if ($mysqli->real_escape_string(isset($numbers[$x]['status'])) and $numbers[$x]['status'] == 'Modified') {
			$query = "update cpa_shows_numbers set name = '$name', fees = $fees, duration = $duration, music = '$music', lights = '$lights', comments = '$comments',
								registrationtype = $registrationtype, canbeinperformance = $canbeinperformance, mandatory = $mandatory,
								practicesstartdate = "
								.($practicesstartdate == '' ? "null" : "'$practicesstartdate'")
								.", practicesenddate = "
								.($practicesenddate == '' ? "null" : "'$practicesenddate'")
								." where id = $id";
			if ($mysqli->query($query)) {
				$query = "UPDATE cpa_text set text = '$label_fr' where id = $label and language = 'fr-ca'";
				if ($mysqli->query($query)) {
					$query = "UPDATE cpa_text set text = '$label_en' where id = $label and language = 'en-ca'";
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

		if ($mysqli->real_escape_string(isset($numbers[$x]['status'])) and $numbers[$x]['status'] == 'Deleted') {
			$query = "DELETE FROM cpa_text WHERE id = '$label'";
			if ($mysqli->query($query)) {
				$query = "DELETE FROM cpa_shows_numbers WHERE id = $id";
				if ($mysqli->query($query)) {
				} else {
					throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
				}
			} else {
				throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
			}
		}
	}

	for ($x = 0; $x < count($numbers); $x++) {
		if (!$mysqli->real_escape_string(isset($numbers[$x]['status'])) || ($mysqli->real_escape_string(isset($numbers[$x]['status'])) && $numbers[$x]['status'] !== 'Deleted')) {
			if ($mysqli->real_escape_string(isset($numbers[$x]['schedules']))) {
				$data['schedules'] = updateEntireShowNumberSchedule($mysqli, $showid, $numbers[$x]['id'], $numbers[$x]['schedules']);
			}
			if ($mysqli->real_escape_string(isset($numbers[$x]['staffs']))) {
				$data['staffs'] = updateEntireShowNumberStaff($mysqli, $showid, $numbers[$x]['id'], $numbers[$x]['staffs']);
			}
			if ($mysqli->real_escape_string(isset($numbers[$x]['invites']))) {
				$data['invites'] = updateEntireShowNumberInvite($mysqli, $showid, $numbers[$x], $numbers[$x]['invites']);
			}
			if ($mysqli->real_escape_string(isset($numbers[$x]['dates']))) {
				$data['dates'] = updateEntireShowNumberDates($mysqli, $showid, $numbers[$x]['id'], $numbers[$x]['dates']);
			}
		}
	}
	return $data;
};

/**
 * 
 * @param integer $showid	The id of the show from cpa_shows
 */
function updateEntireShowPerformanceNumbers($mysqli, $showid, $performanceid, $numbers) {
	$data = array();

	// Delete everything
	$query = "DELETE FROM cpa_shows_performances_numbers WHERE performanceid = $performanceid";
	if (!$mysqli->query($query)) {
		throw new Exception('updateEntireShowPerformanceNumbers - '.$mysqli->sqlstate.' - '. $mysqli->error);
	}
	// Insert everything back	
	for ($x = 0; $x < count($numbers); $x++) {
		$numberid = $mysqli->real_escape_string(isset($numbers[$x]['numberObj']['id']) ? (int)$numbers[$x]['numberObj']['id'] : 0);
		$roomid = $mysqli->real_escape_string(isset($numbers[$x]['roomid']) ? (int)$numbers[$x]['roomid'] : null);

		$query = "	INSERT into cpa_shows_performances_numbers (performanceid, numberid, showid, sequence, roomid)
					VALUES ($performanceid, $numberid, $showid, $x, " . ($roomid==null? "null" : "$roomid") . ")";
		if (!$mysqli->query($query)) {
			throw new Exception('updateEntireShowPerformanceNumbers - '.$mysqli->sqlstate.' - '. $mysqli->error);
		}
	}
	return $data;
};

/**
 * 
 * @param integer $showid	The id of the show from cpa_shows
 */
function updateEntireShowPerformancePrices($mysqli, $showid, $performanceid, $prices) {
	$data = array();
	for ($x = 0; $x < count($prices); $x++) {
		$id = 			isset($prices[$x]['id']) 		? $mysqli->real_escape_string($prices[$x]['id']) : '';
		$pricetype =	isset($prices[$x]['pricetype'])	? $mysqli->real_escape_string($prices[$x]['pricetype']) : '';
		$amount = 		isset($prices[$x]['amount']) 	? $mysqli->real_escape_string($prices[$x]['amount']) : '0.00';
		$agemin = 		isset($prices[$x]['agemin']) 	? $mysqli->real_escape_string($prices[$x]['agemin']) : null;
		$agemax = 		isset($prices[$x]['agemax']) 	? $mysqli->real_escape_string($prices[$x]['agemax']) : null;

		if ($mysqli->real_escape_string(isset($prices[$x]['status'])) and $prices[$x]['status'] == 'New') {
			$query = "	INSERT into cpa_shows_performances_prices (showid, performanceid, pricetype, amount, agemin, agemax)
						VALUES ('$showid', '$performanceid', '$pricetype', '$amount'";
			if ($agemin) {
				$query .= ", '$agemin'";
			} else {
				$query .= ", null";
			}

			if ($agemax) {
				$query .= ", '$agemax'";
			} else {
				$query .= ", null";
			}

			$query .= ")";

			if ($mysqli->query($query)) {
				$prices[$x]['id'] = $mysqli->insert_id;
			} else {
				throw new Exception('updateEntireShowPerformancePrices - '.$mysqli->sqlstate.' - '. $mysqli->error);
			}
		}

		if ($mysqli->real_escape_string(isset($prices[$x]['status'])) and $prices[$x]['status'] == 'Modified') {
			$query = "UPDATE cpa_shows_performances_prices SET amount =  '$amount'";

			if ($agemin) {
				$query .= ", agemin = '$agemin'";
			} else {
				$query .= ", agemin = null";
			}

			if ($agemax) {
				$query .= ", agemax = '$agemax'";
			} else {
				$query .= ", agemax = null";
			}

			$query .= " WHERE id = '$id'";

			if ($mysqli->query($query)) {
			} else {
				throw new Exception('updateEntireShowPerformancePrices - '.$mysqli->sqlstate.' - '. $mysqli->error);
			}
		}

		if ($mysqli->real_escape_string(isset($prices[$x]['status'])) and $prices[$x]['status'] == 'Deleted') {
				$query = "DELETE FROM cpa_shows_performances_prices WHERE id = '$id'";
				if ($mysqli->query($query)) {
				} else {
					throw new Exception('updateEntireShowPerformancePrices - '.$mysqli->sqlstate.' - '. $mysqli->error);
				}
		}
	}
	return $data;
};

/**
 * 
 * @param integer $showid	The id of the show from cpa_shows
 */
function updateEntireShowPerformanceTicket($mysqli, $showid, $performanceid, $ticket) {
	$data = array();
	$id = 				$mysqli->real_escape_string(isset($ticket['id']) 				? (int)$ticket['id'] : 0);
	$showid = 			$mysqli->real_escape_string(isset($ticket['showid']) 			? (int)$ticket['showid'] : 0);
	$performanceid = 	$mysqli->real_escape_string(isset($ticket['performanceid']) 	? (int)$ticket['performanceid'] : 0);
	$language = 		$mysqli->real_escape_string(isset($ticket['language']) 			? $ticket['language'] : '');
	$showstub = 		$mysqli->real_escape_string(isset($ticket['showstub']) 			? (int)$ticket['showstub'] : 0);
	$showstubinfo = 	$mysqli->real_escape_string(isset($ticket['showstubinfo']) 		? (int)$ticket['showstubinfo'] : 0);
	$showimage = 		$mysqli->real_escape_string(isset($ticket['showimage']) 		? (int)$ticket['showimage'] : 0);
	$showstandardinfo = $mysqli->real_escape_string(isset($ticket['showstandardinfo'])	? (int)$ticket['showstandardinfo'] : 0);
	$ticketcolor = 		$mysqli->real_escape_string(isset($ticket['ticketcolor']) 		? $ticket['ticketcolor'] : '');
	$stubcolor = 		$mysqli->real_escape_string(isset($ticket['stubcolor']) 		? $ticket['stubcolor'] : '');
	$imagefilename = 	$mysqli->real_escape_string(isset($ticket['imagefilename']) 	? $ticket['imagefilename'] : '');
	$notes = 			$mysqli->real_escape_string(isset($ticket['notes']) 			? $ticket['notes'] : '');

	if ($id == null || $id == 0) {
		$query = "	INSERT into cpa_shows_performances_tickets (showid, performanceid, language, showstub, showstubinfo, showimage, showstandardinfo, 
																ticketcolor, stubcolor, imagefilename, notes)
					VALUES ($showid, $performanceid, '$language', $showstub, $showstubinfo, $showimage, $showstandardinfo, 
							'$ticketcolor', '$stubcolor', '$imagefilename', '$notes')";
		if ($mysqli->query($query)) {
			$ticket['id'] = $mysqli->insert_id;
		} else {
			throw new Exception('updateEntireShowPerformanceTicket - insert - '.$mysqli->sqlstate.' - '.$mysqli->error);
		}
	} else {
		$query = "	UPDATE cpa_shows_performances_tickets 
					SET language = '$language', showstub = $showstub, showstubinfo = $showstubinfo, showimage = $showimage, showstandardinfo = $showstandardinfo, 
						ticketcolor = '$ticketcolor', stubcolor = '$stubcolor', imagefilename = '$imagefilename', notes = '$notes'
					WHERE performanceid = $performanceid";
		if ($mysqli->query($query)) {
			$ticket['id'] = $mysqli->insert_id;
		} else {
			throw new Exception('updateEntireShowPerformanceTicket - update - '.$mysqli->sqlstate.' - '.$mysqli->error);
		}
	}
	return $data;
};

/**
 * 
 * @param integer $showid	The id of the show from cpa_shows
 */
function updateEntireShowPerformanceAssigns($mysqli, $showid, $performanceid, $assigns) {
	$data = array();
	for ($x = 0; $x < count($assigns); $x++) {
		$id = 			$mysqli->real_escape_string(isset($assigns[$x]['id'])			? $assigns[$x]['id'] : '');
		$contactid =	$mysqli->real_escape_string(isset($assigns[$x]['contactid'])	? (int)$assigns[$x]['contactid'] : 0);
		$taskid = 		$mysqli->real_escape_string(isset($assigns[$x]['taskid'])		? (int)$assigns[$x]['taskid'] : 0);
		$addinfo = 		$mysqli->real_escape_string(isset($assigns[$x]['addinfo'])		? $assigns[$x]['addinfo'] : '');
		$comments = 	$mysqli->real_escape_string(isset($assigns[$x]['comments'])		? $assigns[$x]['comments'] : '');

		if ($mysqli->real_escape_string(isset($assigns[$x]['status'])) and $assigns[$x]['status'] == 'New') {
			$query = "	INSERT into cpa_shows_performances_assigns (showid, performanceid, contactid, taskid, addinfo, comments)
						VALUES ($showid, $performanceid, $contactid, $taskid, '$addinfo', '$comments')";
			if ($mysqli->query($query)) {
				$assigns[$x]['id'] = $mysqli->insert_id;
			} else {
				throw new Exception('updateEntireShowPerformanceAssigns - '.$mysqli->sqlstate.' - '. $mysqli->error);
			}
		}

		if ($mysqli->real_escape_string(isset($assigns[$x]['status'])) and $assigns[$x]['status'] == 'Modified') {
			$query = "UPDATE cpa_shows_performances_assigns set contactid = $contactid, taskid = $taskid, addinfo = '$addinfo', comments = '$comments' where id = '$id'";
			if (!$mysqli->query($query)) {
				throw new Exception('updateEntireShowPerformanceAssigns - '.$mysqli->sqlstate.' - '. $mysqli->error);
			}
		}

		if ($mysqli->real_escape_string(isset($assigns[$x]['status'])) and $assigns[$x]['status'] == 'Deleted') {
			$query = "DELETE FROM cpa_shows_performances_assigns WHERE id = '$id'";
			if (!$mysqli->query($query)) {
				throw new Exception('updateEntireShowPerformanceAssigns - '.$mysqli->sqlstate.' - '. $mysqli->error);
			}
		}
	}
	return $data;
};

/**
 * 
 * @param integer $showid	The id of the show from cpa_shows
 */
function updateEntireShowPerformanceExceptions($mysqli, $showid, $performanceid, $exceptions) {
	$data = array();
	for ($x = 0; $x < count($exceptions); $x++) {
		$id = 		$mysqli->real_escape_string(isset($exceptions[$x]['id']) 		? (int)$exceptions[$x]['id'] : 0);
		$section =	$mysqli->real_escape_string(isset($exceptions[$x]['section']) 	? $exceptions[$x]['section'] : '');
		$row = 		$mysqli->real_escape_string(isset($exceptions[$x]['row']) 		? $exceptions[$x]['row'] : '');
		$seat = 	$mysqli->real_escape_string(isset($exceptions[$x]['seat']) 		? $exceptions[$x]['seat'] : '');
		$reason = 	$mysqli->real_escape_string(isset($exceptions[$x]['reason']) 	? (int)$exceptions[$x]['reason'] : 0);

		if ($mysqli->real_escape_string(isset($exceptions[$x]['status'])) and $exceptions[$x]['status'] == 'New') {
			$query = "	INSERT into cpa_shows_performances_exceptions (showid, performanceid, section, row, seat, reason)
						VALUES ($showid, $performanceid, '$section', '$row', '$seat', $reason)";
			if ($mysqli->query($query)) {
				$assigns[$x]['id'] = $mysqli->insert_id;
			} else {
				throw new Exception('updateEntireShowPerformanceExceptions - '.$mysqli->sqlstate.' - '. $mysqli->error);
			}
		}

		if ($mysqli->real_escape_string(isset($exceptions[$x]['status'])) and $exceptions[$x]['status'] == 'Modified') {
			$query = "UPDATE cpa_shows_performances_exceptions SET section = '$section', row = '$row', seat = '$seat', reason = $reason WHERE id = '$id'";
			if (!$mysqli->query($query)) {
				throw new Exception('updateEntireShowPerformanceExceptions - '.$mysqli->sqlstate.' - '. $mysqli->error);
			}
		}

		if ($mysqli->real_escape_string(isset($exceptions[$x]['status'])) and $exceptions[$x]['status'] == 'Deleted') {
			$query = "DELETE FROM cpa_shows_performances_exceptions WHERE id = '$id'";
			if (!$mysqli->query($query)) {
				throw new Exception('updateEntireShowPerformanceExceptions - '.$mysqli->sqlstate.' - '. $mysqli->error);
			}
		}
	}
	return $data;
};

/**
 * 
 * @param integer $showid	The id of the show from cpa_shows
 */
function updateEntireShowPerformances($mysqli, $showid, $performances) {
	$data = array();
	$data['inserted'] = 0;
	$data['updated'] = 0;
	$data['deleted'] = 0;
	for ($x = 0; $x < count($performances); $x++) {
		$id = 						$mysqli->real_escape_string(isset($performances[$x]['id']) 							? (int)$performances[$x]['id'] : 0);
		$type = 					$mysqli->real_escape_string(isset($performances[$x]['type']) 						? $performances[$x]['type'] : '');
		$eventdate = 				$mysqli->real_escape_string(isset($performances[$x]['eventdate']) 					? $performances[$x]['eventdate'] : '');
		$label = 					$mysqli->real_escape_string(isset($performances[$x]['label']) 						? $performances[$x]['label'] : '');
		$label_en = 				$mysqli->real_escape_string(isset($performances[$x]['label_en']) 					? $performances[$x]['label_en'] : '');
		$label_fr = 				$mysqli->real_escape_string(isset($performances[$x]['label_fr']) 					? $performances[$x]['label_fr'] : '');
		$websitedesc = 				$mysqli->real_escape_string(isset($performances[$x]['websitedesc']) 				? $performances[$x]['websitedesc'] : '');
		$websitedesc_en = 			$mysqli->real_escape_string(isset($performances[$x]['websitedesc_en']) 				? $performances[$x]['websitedesc_en'] : '');
		$websitedesc_fr = 			$mysqli->real_escape_string(isset($performances[$x]['websitedesc_fr']) 				? $performances[$x]['websitedesc_fr'] : '');
		$arenaid = 					$mysqli->real_escape_string(isset($performances[$x]['arenaid']) 					? (int)$performances[$x]['arenaid'] : 0);
		$iceid = 					$mysqli->real_escape_string(isset($performances[$x]['iceid']) 						? (int)$performances[$x]['iceid'] : 0);
		$type = 					$mysqli->real_escape_string(isset($performances[$x]['type']) 						? $performances[$x]['type'] : '');
		$perfdate = 				$mysqli->real_escape_string(isset($performances[$x]['perfdatestr']) 				? $performances[$x]['perfdatestr'] : '');
		$starttime = 				$mysqli->real_escape_string(isset($performances[$x]['starttimestr']) 				? $performances[$x]['starttimestr'] : '');
		$endtime = 					$mysqli->real_escape_string(isset($performances[$x]['endtimestr']) 					? $performances[$x]['endtimestr'] : '');
		$skatersarrivaltime = 		$mysqli->real_escape_string(isset($performances[$x]['skatersarrivaltimestr']) 		? $performances[$x]['skatersarrivaltimestr'] : '');
		$skatersdeparturetime = 	$mysqli->real_escape_string(isset($performances[$x]['skatersdeparturetimestr']) 	? $performances[$x]['skatersdeparturetimestr'] : '');
		$volunteersarrivaltime = 	$mysqli->real_escape_string(isset($performances[$x]['volunteersarrivaltimestr']) 	? $performances[$x]['volunteersarrivaltimestr'] : '');
		$volunteersdeparturetime =	$mysqli->real_escape_string(isset($performances[$x]['volunteersdeparturetimestr']) 	? $performances[$x]['volunteersdeparturetimestr'] : '');
		$publish = 					$mysqli->real_escape_string(isset($performances[$x]['publish']) 					? (int)$performances[$x]['publish'] : 0);
		$iscanceled = 				$mysqli->real_escape_string(isset($performances[$x]['iscanceled']) 					? (int)$performances[$x]['iscanceled'] : 0);

		if ($mysqli->real_escape_string(isset($performances[$x]['status'])) and $performances[$x]['status'] == 'New') {
			$query = "	INSERT into cpa_shows_performances (showid, label, websitedesc, arenaid, iceid, type, perfdate, starttime, endtime, 
															skatersarrivaltime, skatersdeparturetime, volunteersarrivaltime, volunteersdeparturetime, publish, 
															iscanceled)
						VALUES ($showid, create_systemtext('$label_en', '$label_fr'), create_systemtext('$websitedesc_en', '$websitedesc_fr'),
								$arenaid, $iceid, '$type', '$perfdate', '$starttime', '$endtime', '$skatersarrivaltime', '$skatersdeparturetime', 
								'$volunteersarrivaltime', '$volunteersdeparturetime', $publish, $iscanceled)";
			if ($mysqli->query($query)) {
				$performances[$x]['id'] = $mysqli->insert_id;
				$data['inserted']++;
			} else {
				throw new Exception('updateEntireShowPerformances - '.$mysqli->sqlstate.' - '. $mysqli->error);
			}
		}

		if ($mysqli->real_escape_string(isset($performances[$x]['status'])) and $performances[$x]['status'] == 'Modified') {
			$query = "	UPDATE cpa_shows_performances 
						SET type = '$type', arenaid = $arenaid, iceid = $iceid, perfdate = '$perfdate', starttime = '$starttime', endtime = '$endtime',  
							skatersarrivaltime = '$skatersarrivaltime', skatersdeparturetime = '$skatersdeparturetime', volunteersarrivaltime = '$volunteersarrivaltime', 
							volunteersdeparturetime = '$volunteersdeparturetime', publish = $publish, iscanceled = $iscanceled
						WHERE id = $id";
			if ($mysqli->query($query)) {
				$query = "UPDATE cpa_text set text = '$label_en' where id = $label and language = 'en-ca'";
				if ($mysqli->query($query)) {
					$query = "UPDATE cpa_text set text = '$label_fr' where id = $label and language = 'fr-ca'";
					if ($mysqli->query($query)) {
						$query = "UPDATE cpa_text set text = '$websitedesc_en' where id = $websitedesc and language = 'en-ca'";
						if ($mysqli->query($query)) {
							$query = "UPDATE cpa_text set text = '$websitedesc_fr' where id = $websitedesc and language = 'fr-ca'";
							if ($mysqli->query($query)) {
								$data['updated']++;
							} else {
								throw new Exception('updateEntireShowPerformances - '.$mysqli->sqlstate.' - '. $mysqli->error);
							}
						} else {
							throw new Exception('updateEntireShowPerformances - '.$mysqli->sqlstate.' - '. $mysqli->error);
						}
					} else {
						throw new Exception('updateEntireShowPerformances - '.$mysqli->sqlstate.' - '. $mysqli->error);
					}
				} else {
					throw new Exception('updateEntireShowPerformances - '.$mysqli->sqlstate.' - '. $mysqli->error);
				}
			} else {
				throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
			}
		}

		if ($mysqli->real_escape_string(isset($performances[$x]['status'])) and $performances[$x]['status'] == 'Deleted') {
			$query = "DELETE FROM cpa_text WHERE id = $label OR id = $websitedesc";
			if ($mysqli->query($query)) {
				$query = "DELETE FROM cpa_shows_performances WHERE id = $id";
				if ($mysqli->query($query)) {
					$data['deleted']++;
				} else {
					throw new Exception('updateEntireShowPerformances - '.$mysqli->sqlstate.' - '. $mysqli->error);
				}
			} else {
				throw new Exception('updateEntireShowPerformances - '.$mysqli->sqlstate.' - '. $mysqli->error);
			}
		}
	}

	for ($x = 0; $x < count($performances); $x++) {
		if (!$mysqli->real_escape_string(isset($performances[$x]['status'])) || ($mysqli->real_escape_string(isset($performances[$x]['status'])) && $performances[$x]['status'] !== 'Deleted')) {
			if ($mysqli->real_escape_string(isset($performances[$x]['numberlist'])) && ($mysqli->real_escape_string(isset($performances[$x]['numberstatus'])) && $performances[$x]['numberstatus'] == 'Modified')) {
				$data['numbers'] = updateEntireShowPerformanceNumbers($mysqli, $showid, $performances[$x]['id'], $performances[$x]['numberlist']);
			}
			if ($mysqli->real_escape_string(isset($performances[$x]['prices']))) {
				$data['prices'] = updateEntireShowPerformancePrices($mysqli, $showid, $performances[$x]['id'], $performances[$x]['prices']);
			}
			if ($mysqli->real_escape_string(isset($performances[$x]['assigns']))) {
				$data['assigns'] = updateEntireShowPerformanceAssigns($mysqli, $showid, $performances[$x]['id'], $performances[$x]['assigns']);
			}
			if ($mysqli->real_escape_string(isset($performances[$x]['exceptions']))) {
				$data['exceptions'] = updateEntireShowPerformanceExceptions($mysqli, $showid, $performances[$x]['id'], $performances[$x]['exceptions']);
			}
			if (isset($performances[$x]['ticket'])) {
				$data['ticket'] = updateEntireShowPerformanceTicket($mysqli, $showid, $performances[$x]['id'], $performances[$x]['ticket']);
			}
		}
	}
	return $data;
};

/**
 * This function will handle show add, update functionality
 * 
 */
function updateEntireShow($mysqli, $show) {
	try {
		$data = array();
		$showid = $mysqli->real_escape_string(isset($show['id']) ? $show['id'] : '');

		update_show($mysqli, $show);
		
		if ($mysqli->real_escape_string(isset($show['numbers']))) {
			$data['numbers'] = updateEntireShowNumbers($mysqli, $showid, $show['numbers']);
		}

		if ($mysqli->real_escape_string(isset($show['interventions']))) {
			$data['interventions'] = updateEntireShowInterventions($mysqli, $showid, $show['interventions']);
		}

		if ($mysqli->real_escape_string(isset($show['paragraphs']))) {
			$data['paragraphs'] = updateEntireShowParagraphs($mysqli, $showid, $show['paragraphs']);
		}
		if ($mysqli->real_escape_string(isset($show['showCharges']))) {
			$data['showCharges'] = updateEntireShowCharges($mysqli, $showid, $show['showCharges']);
		}
		if ($mysqli->real_escape_string(isset($show['rules']))) {
			$data['rules'] = updateEntireShowRules($mysqli, $showid, $show['rules']);
		}

		if ($mysqli->real_escape_string(isset($show['performances']))) {
			$data['events'] = updateEntireShowPerformances($mysqli, $showid, $show['performances']);
		}

		$mysqli->close();
		$data['success'] = true;
		$data['message'] = 'Show updated successfully.';
		echo json_encode($data);
		exit;
	} catch (Exception $e) {
		$data = array();
		$data['success'] = false;
		$data['message'] = 'fudge - '.$e->getMessage();
		echo json_encode($data);
		exit;
	}
};

/**
 * This function will handle show add, update functionality
 * 
 */
function insert_show($mysqli, $show) {
	try {
		$data = array();
		$id =			$mysqli->real_escape_string(isset($show['id'])			? (int)$show['id'] : '');
		$name =			$mysqli->real_escape_string(isset($show['name'])		? $show['name'] : '');
		$label =		$mysqli->real_escape_string(isset($show['label'])		? (int)$show['label'] : 0);
		$label_fr =		$mysqli->real_escape_string(isset($show['label_fr'])	? $show['label_fr'] : '');
		$label_en =		$mysqli->real_escape_string(isset($show['label_en'])	? $show['label_en'] : '');
		$sessionid =	$mysqli->real_escape_string(isset($show['sessionid'])	? (int)$show['sessionid'] : 0);
		$active =		$mysqli->real_escape_string(isset($show['active'])		? (int)$show['active'] : 0);
		$publish =		$mysqli->real_escape_string(isset($show['publish'])		? (int)$show['publish'] : 0);

		$query = "	INSERT INTO cpa_shows (name, label, websitedesc, sessionid, active, publish)
					VALUES ('$name', create_systemText('$label_en', '$label_fr'), create_systemText('$label_en', '$label_fr'),
							(select max(id) from cpa_sessions), $active, $publish)";
		if ($mysqli->query($query)) {
			if (empty($id)) {
				$id = (int) $mysqli->insert_id;
				$data['id'] = $id;
			}
			$query = "INSERT INTO cpa_shows_charges (showid, chargecode, amount)
								VALUES ($id, 'SPECCHARGE', 0)";
			if (!$mysqli->query($query)) {
				throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
			}
			$query = "INSERT INTO cpa_shows_charges (showid, chargecode, amount)
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
 * This function will handle show add, update functionality
 * 
 */
function update_show($mysqli, $details) {
	$data = array();
	$id =						$mysqli->real_escape_string(isset($details['id']) 							? (int)$details['id'] : '');
	$name =						$mysqli->real_escape_string(isset($details['name']) 						? $details['name'] : '');
	$label =					$mysqli->real_escape_string(isset($details['label']) 						? (int)$details['label'] : 0);
	$label_fr =					$mysqli->real_escape_string(isset($details['label_fr']) 					? $details['label_fr'] : '');
	$label_en =					$mysqli->real_escape_string(isset($details['label_en']) 					? $details['label_en'] : '');
	$sessionid =				$mysqli->real_escape_string(isset($details['sessionid']) 					? (int)$details['sessionid'] : 0);
	$practicesstartdate =		$mysqli->real_escape_string(isset($details['practicesstartdatestr']) 		? $details['practicesstartdatestr'] : '');
	$practicesenddate =			$mysqli->real_escape_string(isset($details['practicesenddatestr']) 			? $details['practicesenddatestr'] : '');
	$onlineregiststartdate =	$mysqli->real_escape_string(isset($details['onlineregiststartdatestr']) 	? $details['onlineregiststartdatestr'] : '');
	$onlineregistenddate =	 	$mysqli->real_escape_string(isset($details['onlineregistenddatestr']) 		? $details['onlineregistenddatestr'] : '');
	$active =					$mysqli->real_escape_string(isset($details['active']) 						? (int)$details['active'] : 0);
	$publish =					$mysqli->real_escape_string(isset($details['publish']) 						? (int)$details['publish'] : 0);

	$query = "	UPDATE cpa_shows 
				SET name = '$name', sessionid = $sessionid, active = $active, publish = $publish, practicesstartdate = '$practicesstartdate', 
					practicesenddate = '$practicesenddate', onlineregiststartdate = '$onlineregiststartdate', onlineregistenddate = '$onlineregistenddate'  
				WHERE id = $id";
	if ($mysqli->query($query)) {
		$query = "UPDATE cpa_text set text = '$label_fr' where id = $label and language = 'fr-ca'";
		if ($mysqli->query($query)) {
			$query = "UPDATE cpa_text set text = '$label_en' where id = $label and language = 'en-ca'";
			if ($mysqli->query($query)) {
			} else {
				throw new Exception('update_show - '.$mysqli->sqlstate.' - '. $mysqli->error);
			}
		} else {
			throw new Exception('update_show - '.$mysqli->sqlstate.' - '. $mysqli->error);
		}
	} else {
		throw new Exception('update_show - '.$mysqli->sqlstate.' - '. $mysqli->error);
	}
	return;
	exit;
};

/**
 * This function will handle show deletion
 * 
 * @param object $show
 */
function delete_show($mysqli, $show) {
	try {
		$id =			$mysqli->real_escape_string(isset($show['id'])			? (int)$show['id'] : '');
		$label =		$mysqli->real_escape_string(isset($show['label'])		? $show['label'] : '');
		$websitedesc = 	$mysqli->real_escape_string(isset($show['websitedesc']) ? $show['websitedesc'] : '');

		if (empty($id)) throw new Exception("Invalid Show.");
		$query = "DELETE FROM cpa_text WHERE id = $label";
		if ($mysqli->query($query)) {
			$query = "DELETE FROM cpa_text WHERE id = $websitedesc";
			if ($mysqli->query($query)) {
				$query = "DELETE FROM cpa_text WHERE id IN (SELECT label FROM cpa_shows_numbers WHERE showid = $id)";
				if ($mysqli->query($query)) {
					$query = "DELETE FROM cpa_text WHERE id IN (SELECT label FROM cpa_shows_performances WHERE showid = $id) OR id IN (SELECT websitedesc FROM cpa_shows_performances WHERE showid = $id)";
					if ($mysqli->query($query)) {
						$query = "DELETE FROM cpa_text WHERE id IN (SELECT title FROM cpa_shows_paragraphs WHERE showid = $id) OR id IN (SELECT subtitle FROM cpa_shows_paragraphs WHERE showid = $id) OR id IN (SELECT paragraphtext FROM cpa_shows_paragraphs WHERE showid = $id)";
						if ($mysqli->query($query)) {
							$query = "DELETE FROM cpa_shows WHERE id = $id";
							if ($mysqli->query($query)) {
								$data['success'] = true;
								$data['message'] = 'Show deleted successfully.';
								echo json_encode($data);
								exit;
							}
						}
					}
				}
			}
		}
		throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
	} catch (Exception $e) {
		$data = array();
		$data['success'] = false;
		$data['message'] = $e->getMessage();
		echo json_encode($data);
		exit;
	}
};

/**
 * This function gets the list of all shows from database
 */
function getAllShows($mysqli) {
	try {
		$query = "SELECT id, name, active FROM cpa_shows order by id desc";
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
 * This function gets the details of all interventions for a show from the database
 * 
 * @param integer $showid	The id of the show from cpa_shows
 */
function getShowInterventions($mysqli, $showid, $language) {
	if (empty($showid)) throw new Exception("Invalid show.");
	$query = "	SELECT csn.*, getTextLabel(csn.label, '$language') interventionlabel, getEnglishTextLabel(csn.label) as label_en, getFrenchTextLabel(csn.label) as label_fr
				FROM cpa_shows_numbers csn
				WHERE csn.showid = $showid
				AND type = 2
				ORDER BY csn.id";
	$result = $mysqli->query($query);
	$data = array();
	$data['data'] = array();
	while ($row = $result->fetch_assoc()) {
		$row['id'] = (int)$row['id'];
		$row['type'] = (int)$row['type'];
		$row['duration'] = (int)$row['duration'];
		$data['data'][] = $row;
	}
	$data['success'] = true;
	return $data;
	exit;
};

/**
 * This function gets the details of all numbers for a show from the database
 * 
 * @param integer $showid	The id of the show from cpa_shows
 */
function getShowNumbers($mysqli, $showid, $language) {
	if (empty($showid)) throw new Exception("Invalid show.");
	$query = "	SELECT csn.*, getTextLabel(csn.label, '$language') numberlabel, getEnglishTextLabel(csn.label) as label_en, getFrenchTextLabel(csn.label) as label_fr,
						getCodeDescription('yesno', csn.datesgenerated, '$language') datesgeneratedlabel, getCodeDescription('yesno', csn.canbeinperformance, '$language') canbeinperformancelabel, 
						getCodeDescription('yesno', csn.mandatory, '$language') mandatorylabel, getCodeDescription('numberregistrationtypes', csn.registrationtype, '$language') registrationtypelabel, 
						0 as membersdirty, (select count(*) from cpa_shows_numbers_members where numberid = csn.id) as registrationcount
				FROM cpa_shows_numbers csn
				WHERE csn.showid = $showid
				AND type = 1
				ORDER BY csn.id";
	$result = $mysqli->query($query);
	$data = array();
	$data['data'] = array();
	while ($row = $result->fetch_assoc()) {
		$row['id'] = 					(int)$row['id'];
		$row['type'] = 					(int)$row['type'];
		$row['duration'] = 				(int)$row['duration'];
		$row['canbeinperformance'] =	(int)$row['canbeinperformance'];
		$row['mandatory'] = 			(int)$row['mandatory'];
		$row['membersdirty'] = 			(int)$row['membersdirty'];
		$row['staffs'] = 				getShowNumberStaffs($mysqli, $row['id'], $language)['data'];
		$row['invites'] = 				getShowNumberInvites($mysqli, $row['id'], $language)['data'];
		$row['schedules'] = 			getShowNumberSchedules($mysqli, $row['id'], $language)['data'];
		$row['dates'] = 				getShowNumberDates($mysqli, $row['id'], $language)['data'];
		$data['data'][] = 				$row;
	}
	$data['success'] = true;
	return $data;
	exit;
};

/**
 * This function gets the details of all schedules for a show number
 * 
 * @param integer $numberid	The id of the show from cpa_shows_numbers
 */
function getShowNumberSchedules($mysqli, $numberid, $language) {
	if (empty($numberid)) throw new Exception("Invalid show number.");
	$query = "	SELECT *, (select getTextLabel(cai.label, '$language') from cpa_arenas_ices cai where cai.arenaid = csns.arenaid and cai.id = csns.iceid) icelabel
				FROM cpa_shows_numbers_schedule csns
				WHERE numberid = $numberid
				ORDER BY day, starttime";
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
 * This function gets the details of all practice dates for a show number
 * 
 * @param integer $numberid	The id of the show from cpa_shows_numbers
 */
function getShowNumberDates($mysqli, $numberid, $language) {
	if (empty($numberid)) throw new Exception("Invalid show number.");
		$query = "	SELECT csnd.*, getTextLabel(csn.label, '$language') as numberlabel,
							(select getTextLabel(cai.label, '$language') from cpa_arenas_ices cai where cai.arenaid = csnd.arenaid and cai.id = csnd.iceid) icelabel,
							(select getTextLabel(ca.label, '$language') from cpa_arenas ca where ca.id = csnd.arenaid) arenalabel,
							getCodeDescription('days', csnd.day, '$language') daylabel,
							getCodeDescription('yesno', csnd.canceled, '$language') canceledlabel, csn.name numbername
					FROM cpa_shows_numbers_dates csnd
					JOIN cpa_shows_numbers csn ON csn.id = $numberid
					WHERE csnd.numberid = $numberid
					ORDER BY csnd.practicedate";
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
 * This function gets the details of all staffs for a show number
 * 
 * @param integer $numberid	The id of the show from cpa_shows_numbers
 */
function getShowNumberStaffs($mysqli, $numberid, $language) {
	if (empty($numberid)) throw new Exception("Invalid show number.");
	$query = "	SELECT cscs.*, concat(cm.lastname, ', ', cm.firstname) name, getCodeDescription('numberstaffcodes', cscs.staffcode, '$language') staffcodelabel
				FROM cpa_shows_numbers_staffs cscs
				JOIN cpa_members cm ON cm.id = cscs.memberid
				WHERE numberid = $numberid
				ORDER BY staffcode, cm.lastname, cm.firstname";
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
 * This function gets the details of all invites for a show course
 * 
 * @param integer $numberid	The id of the show from cpa_shows_numbers
 */
function getShowNumberInvites($mysqli, $numberid, $language) {
	if (empty($numberid)) throw new Exception("Invalid show number.");
	$query = "	SELECT csci.groupormemberid id, cm.lastname, cm.firstname, if(csnm.id is null or (csnm.registrationenddate is not null and csnm.registrationenddate < curdate()), 0, 1) as registered
				FROM cpa_shows_numbers_invites csci
				JOIN cpa_members cm ON cm.id = csci.groupormemberid
				LEFT JOIN cpa_shows_numbers_members csnm ON csnm.numberid = csci.numberid AND csnm.memberid = csci.groupormemberid
				WHERE csci.numberid = $numberid
				AND csci.type = 2
				ORDER BY registered DESC, cm.lastname, cm.firstname";
	$result = $mysqli->query($query);
	$data = array();
	$data['data'] = array();
	while ($row = $result->fetch_assoc()) {
		$row['registered'] = (int)$row['registered'];
		$data['data'][] = $row;
	}
	$data['success'] = true;
	return $data;
};

/**
 * This function gets the lists of all numbers for a performance
 */
function getShowPerformanceNumbers($mysqli, $performanceid, $language) {
	if (empty($performanceid)) throw new Exception("Invalid performance.");
	$query = "	SELECT cspn.*
				FROM cpa_shows_performances_numbers cspn
				WHERE cspn.performanceid = $performanceid
				ORDER BY cspn.sequence";
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
 * This function gets the lists of all prices for a performance
 */
function getShowPerformancePrices($mysqli, $performanceid, $language) {
	if (empty($performanceid)) throw new Exception("Invalid performance.");
	$query = "	SELECT cspp.*, getCodeDescription('showpricetypes', cspp.pricetype, '$language') pricetypelabel
				FROM cpa_shows_performances_prices cspp
				JOIN cpa_codetable cct ON cct.ctname = 'showpricetypes' AND cct.code = cspp.pricetype
				WHERE cspp.performanceid = $performanceid
				ORDER BY cct.sequence";
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
 * This function gets the ticket definition for a performance
 * 
 * @param integer $showid	The id of the show from cpa_shows
 */
function getShowPerformanceTicket($mysqli, $showid, $performanceid, $language) {
	if (empty($performanceid)) throw new Exception("Invalid performance.");
	$query = "	SELECT cspt.*
				FROM cpa_shows_performances_tickets cspt
				WHERE cspt.performanceid = $performanceid";
	$result = $mysqli->query($query);
	$data = array();
	$data['data'] = array();
	while ($row = $result->fetch_assoc()) {
		$row['showid'] = 			(int)$row['showid'];
		$row['performanceid'] = 	(int)$row['performanceid'];
		$row['showstub'] = 			(int)$row['showstub'];
		$row['showstubinfo'] = 		(int)$row['showstubinfo'];
		$row['showimage'] =			(int)$row['showimage'];
		$row['showstandardinfo'] = 	(int)$row['showstandardinfo'];
		$filename = 				'../../../private/'. $_SERVER['HTTP_HOST'].'/website/images/shows/'.$row['imagefilename'];
		$row['filename'] = 			$filename;
		if (isset($row['imagefilename']) && !empty($row['imagefilename']) && file_exists($filename)) {
			$row['imageinfo'] = getimagesize($filename);
		}
		$data['data'][] = $row;
	}
	if (count($data['data']) == 0) {
		$row['showid'] = (int)$showid;
		$row['performanceid'] = (int)$performanceid;
		$row['language'] = 1;
		$row['showstub'] = 0;
		$row['showstubinfo'] = 0;
		$row['showimage'] = 0;
		$row['showstandardinfo'] = 1;
		$row['stubcolor'] = '#ffffff';
		$row['ticketcolor'] = '#ffffff';
		$data['data'][] = $row;
	}
	$data['success'] = true;
	return $data;
};

/**
 * This function gets the lists of all exceptions for a performance
 */
function getShowPerformanceExceptions($mysqli, $performanceid, $language) {
	if (empty($performanceid)) throw new Exception("Invalid performance.");
	$query = "	SELECT cspe.*
				FROM cpa_shows_performances_exceptions cspe
				WHERE cspe.performanceid = $performanceid";
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
 * This function gets the lists of all assignations for a performance
 */
function getShowPerformanceAssigns($mysqli, $performanceid, $language) {
	if (empty($performanceid)) throw new Exception("Invalid performance.");
	$query = "	SELECT cspa.*, getTextLabel(cst.label, '$language') tasklabel, concat(cc.firstname, ' ', cc.lastname) contactfullname
				FROM cpa_shows_performances_assigns cspa
				JOIN cpa_contacts cc ON cc.id = cspa.contactid
				JOIN cpa_shows_tasks cst ON cst.id = cspa.taskid
				JOIN cpa_codetable cct ON cct.ctname = 'taskcategories' AND cct.code = cst.category
				WHERE cspa.performanceid = $performanceid
				ORDER BY cct.sequence";
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
 * This function gets the details of all performances for a show
 * 
 * @param integer $showid	The id of the show from cpa_shows
 */
function getShowPerformances($mysqli, $showid, $language) {
	if (empty($showid)) throw new Exception("Invalid show.");
	$query = "	SELECT csp.*, getEnglishTextLabel(csp.label) as label_en, getFrenchTextLabel(csp.label) as label_fr,
						getEnglishTextLabel(csp.websitedesc) as websitedesc_en, getFrenchTextLabel(csp.websitedesc) as websitedesc_fr,
						(select getTextLabel(ca.label, '$language') from cpa_arenas ca where ca.id = csp.arenaid) arenalabel,
						(select getTextLabel(cai.label, '$language') from cpa_arenas_ices cai where cai.arenaid = csp.arenaid and cai.id = csp.iceid) icelabel,
						getCodeDescription('performancetypes', csp.type, '$language') typelabel, getTextLabel(csp.label, '$language') performancelabel
				FROM cpa_shows_performances csp
				WHERE csp.showid = $showid
				ORDER BY csp.perfdate";
	$result = $mysqli->query($query);
	$data = array();
	$data['data'] = array();
	while ($row = $result->fetch_assoc()) {
		$row['numberlist'] =	getShowPerformanceNumbers($mysqli, $row['id'], $language)['data'];
		$row['prices'] =		getShowPerformancePrices($mysqli, $row['id'], $language)['data'];
		$row['assigns'] =		getShowPerformanceAssigns($mysqli, $row['id'], $language)['data'];
		$row['exceptions'] =	getShowPerformanceExceptions($mysqli, $row['id'], $language)['data'];
		$row['ticket'] =		getShowPerformanceTicket($mysqli, $showid, $row['id'], $language)['data'][0];
		$data['data'][] = $row;
	}
	$data['success'] = true;
	return $data;
};

/**
 * This function gets the details of all show courses for a show
 * 
 * @param integer $showid	The id of the show from cpa_shows
 */
function getShowParagraphs($mysqli, $showid, $language) {
	if (empty($showid)) throw new Exception("Invalid show.");
	$query = "	SELECT csp.*, getWSTextLabel(csp.paragraphtext, 'fr-ca') paragraphtext_fr, getWSTextLabel(csp.paragraphtext, 'en-ca') paragraphtext_en,
					 	getWSTextLabel(csp.title, 'fr-ca') title_fr, getWSTextLabel(csp.title, 'en-ca') title_en,
						 getWSTextLabel(csp.subtitle, 'fr-ca') subtitle_fr, getWSTextLabel(csp.subtitle, 'en-ca') subtitle_en
				FROM cpa_shows_paragraphs csp
				WHERE csp.showid = $showid
				ORDER BY paragraphindex";
	$result = $mysqli->query($query);
	$data = array();
	$data['data'] = array();
	while ($row = $result->fetch_assoc()) {
		$row['id'] =				(int)$row['id'];
		$row['showid'] =			(int)$row['showid'];
		$row['paragraphindex'] =	(int)$row['paragraphindex'];
		$data['data'][] = 			$row;
	}
	$data['success'] = true;
	return $data;
};

/**
 * This function gets the details of ONE show number
 * 
 * @param integer $numberid	The id of the show from cpa_shows_numbers
 */
function getOneShowNumber($mysqli, $numberid, $language) {
	if (empty($numberid)) throw new Exception("Invalid show number id.");
	$query = "	SELECT csn.*, getTextLabel(csn.label, '$language') numberlabel, getEnglishTextLabel(csn.label) as label_en, getFrenchTextLabel(csn.label) as label_fr
				FROM cpa_shows_numbers csn
				WHERE csn.id = $numberid";
	$result = $mysqli->query($query);
	$data = array();
	$data['data'] = array();
	while ($row = $result->fetch_assoc()) {
		$row['id'] = 					(int)$row['id'];
		$row['type'] = 					(int)$row['type'];
		$row['duration'] = 				(int)$row['duration'];
		$row['canbeinperformance'] =	(int)$row['canbeinperformance'];
		$row['staffs'] = 				getShowNumberStaffs($mysqli, $row['id'], $language)['data'];
		$row['invites'] = 				getShowNumberInvites($mysqli, $row['id'], $language)['data'];
		$row['schedules'] = 			getShowNumberSchedules($mysqli, $row['id'], $language)['data'];
		$row['dates'] = 				getShowNumberDates($mysqli, $numberid, $language)['data'];
		$data['data'][] = 				$row;
	}
	$data['success'] = true;
	return $data;
};

/**
 * This function gets the details of all registrations for a show
 * 
 * @param integer $showid	The id of the show from cpa_shows
 */
// function getShowRegistrations($mysqli, $showid, $language) {
// 	try {
// 		if (empty($showid)) throw new Exception("Invalid show.");
// 		$query = "	SELECT *
// 					FROM cpa_shows_registrations
// 					WHERE showid = '$showid'
// 					ORDER BY registrationdate, starttime";
// 		$result = $mysqli->query($query);
// 		$data = array();
// 		$data['data'] = array();
// 		while ($row = $result->fetch_assoc()) {
// 			$data['data'][] = $row;
// 		}
// 		$data['success'] = true;
// 		return $data;
// 	} catch (Exception $e) {
// 		$data = array();
// 		$data['success'] = false;
// 		$data['message'] = $e->getMessage();
// 		return $data;
// 	}
// };

/**
 * This function gets the details of all events for a show
 * 
 * @param integer $showid	The id of the show from cpa_shows
 */
// function getShowEvents($mysqli, $showid, $language) {
// 	if (empty($showid)) throw new Exception("Invalid show.");
// 	$query = "SELECT id, eventdate, type, label, getEnglishTextLabel(label) label_en, getFrenchTextLabel(label) label_fr
// 						FROM cpa_shows_dates
// 						WHERE showid = '$showid'
// 						ORDER BY eventdate";
// 	$result = $mysqli->query($query);
// 	$data = array();
// 	$data['data'] = array();
// 	while ($row = $result->fetch_assoc()) {
// 		$data['data'][] = $row;
// 	}
// 	$data['success'] = true;
// 	return $data;
// };

/**
 * This function gets the details of all show charges for a show
 * 
 * @param integer $showid	The id of the show from cpa_shows
 */
function getShowCharges($mysqli, $showid, $language) {
	if (empty($showid)) throw new Exception("Invalid show.");
	$query = "	SELECT csc.*, getTextLabel(cc.label, '$language') chargelabel
				FROM cpa_shows_charges csc
				JOIN cpa_charges cc ON cc.code = csc.chargecode
				WHERE showid = $showid
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
};

/**
 * This function gets the details of all rules for a show
 * 
 * @param integer $showid	The id of the show from cpa_shows
 */
function getShowRules($mysqli, $showid, $language) {
	if (empty($showid)) throw new Exception("Invalid show.");
	$query = "	SELECT 	csp.*, getWSTextLabel(csp.paragraphtext, 'fr-ca') paragraphtext_fr, getWSTextLabel(csp.paragraphtext, 'en-ca') paragraphtext_en,
					 	getWSTextLabel(csp.title, 'fr-ca') title_fr, getWSTextLabel(csp.title, 'en-ca') title_en,
						getWSTextLabel(csp.subtitle, 'fr-ca') subtitle_fr, getWSTextLabel(csp.subtitle, 'en-ca') subtitle_en
				FROM cpa_shows_rules csp
				WHERE csp.showid = $showid
				ORDER BY paragraphindex";
	$result = $mysqli->query($query);
	$data = array();
	$data['data'] = array();
	while ($row = $result->fetch_assoc()) {
		$row['id'] = (int)$row['id'];
		$row['showid'] = (int)$row['showid'];
		$row['paragraphindex'] = (int)$row['paragraphindex'];
		$data['data'][] = $row;
	}
	$data['success'] = true;
	return $data;
};

/**
 * This function gets the details of one show from database
 * 
 * @param integer $id	The id of the show from cpa_shows
 */
function getShowDetails($mysqli, $id, $language) {
	try {
		$query = "	SELECT 	*, getEnglishTextLabel(label) as label_en, getFrenchTextLabel(label) as label_fr,
							getEnglishTextLabel(websitedesc) as websitedesc_en, getFrenchTextLabel(websitedesc) as websitedesc_fr
					FROM cpa_shows
					WHERE id = $id";
		$result = $mysqli->query($query);
		$data = array();
		while ($row = $result->fetch_assoc()) {
			$row['numbers'] = 		getShowNumbers($mysqli, $id, $language)['data'];
			$row['performances'] = 	getShowPerformances($mysqli, $id, $language)['data'];
			$row['interventions'] = getShowInterventions($mysqli, $id, $language)['data'];
			$row['paragraphs'] = 	getShowParagraphs($mysqli, $id, $language)['data'];
  			$row['showCharges'] =	getShowCharges($mysqli, $id, $language)['data'];
			$row['rules'] = 		getShowRules($mysqli, $id, $language)['data'];
			$filename = 			'../../../private/'. $_SERVER['HTTP_HOST'].'/website/images/shows/'.$row['imagefilename'];
			$row['filename'] = 		$filename;
			if (isset($row['imagefilename']) && !empty($row['imagefilename']) && file_exists($filename)) {
				$row['imageinfo'] = getimagesize($filename);
			}
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
 * 
 * 
 * @param integer $numberid	The id of the show from cpa_shows_numbers
 */
function deletePracticeDates($mysqli, $numberid) {
	$data = array();
	$query = "	DELETE FROM cpa_shows_numbers_dates 
				WHERE numberid = $numberid
				AND NOT EXISTS (SELECT * FROM cpa_shows_numbers_presences csnp WHERE csnp.showsnumbersdatesid = cpa_shows_numbers_dates.id AND ispresent = 1)";
	$result = $mysqli->query($query);
	$data['deleted'] = $mysqli -> affected_rows;
	$data['success'] = true;
	return $data;
	exit;
};

/**
 * This function will handle course date insert functionality
 * 
 */
function insertPracticeDate($mysqli, $numberPracticeDates, $language) {
	try {
		$data = array();
		$data['count'] = count($numberPracticeDates);
		$data['inserted'] = 0;
		if (count($numberPracticeDates) > 0) {
			$numberid = $mysqli->real_escape_string(isset($numberPracticeDates[0]['numberid'])	? (int) $numberPracticeDates[0]['numberid'] : 0);
			$showid = 	$mysqli->real_escape_string(isset($numberPracticeDates[0]['showid']) 	? (int) $numberPracticeDates[0]['showid'] : 0);
			$data['deletedates'] = deletePracticeDates($mysqli, $numberid);
			for ($x = 0; $x < count($numberPracticeDates); $x++) {
				$practicedate =	$mysqli->real_escape_string(isset($numberPracticeDates[$x]['practicedatestr'])	? $numberPracticeDates[$x]['practicedatestr'] : '');
				$arenaid =		$mysqli->real_escape_string(isset($numberPracticeDates[$x]['arenaid']) 			? $numberPracticeDates[$x]['arenaid'] : '');
				$iceid =		$mysqli->real_escape_string(isset($numberPracticeDates[$x]['iceid']) 			? $numberPracticeDates[$x]['iceid'] : '0');
				$starttime =	$mysqli->real_escape_string(isset($numberPracticeDates[$x]['starttime']) 		? $numberPracticeDates[$x]['starttime'] : '');
				$endtime =		$mysqli->real_escape_string(isset($numberPracticeDates[$x]['endtime']) 			? $numberPracticeDates[$x]['endtime'] : '');
				$duration =		$mysqli->real_escape_string(isset($numberPracticeDates[$x]['duration']) 		? $numberPracticeDates[$x]['duration'] : '');
				$day =			$mysqli->real_escape_string(isset($numberPracticeDates[$x]['day']) 				? $numberPracticeDates[$x]['day'] : '');

				// We need to check if there is already a practice's date with the same info
				// Remember, there is already a practice at that date because there were attendances for this practice in the DB
				$query = "	SELECT * 
							FROM cpa_shows_numbers_dates 
							WHERE numberid = $numberid AND practicedate = '$practicedate'";
				$result = $mysqli->query($query);
				$row = $result->fetch_assoc();
				if (!isset($row['numberid'])) {
					// Insert only if there is not already a course on this date
					$query = "	INSERT INTO cpa_shows_numbers_dates (numberid, showid, practicedate, arenaid, iceid, starttime, endtime, duration, day)
								VALUES ($numberid, $showid, '$practicedate', '$arenaid', '$iceid', '$starttime', '$endtime', '$duration', $day)";
					if ($mysqli->query($query)) {
						$data['inserted']++;
					} else {
						throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
					}
				}
			}
			$data['updateflag'] = updateShowNumberGeneratedFlag($mysqli, $numberid);
			$data['dates'] 		= getShowNumberDates($mysqli, $numberid, $language)['data'];
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
 * This function set the datesgenerated flag to true for a show course
 * @throws Exception
 */
function updateShowNumberGeneratedFlag($mysqli, $showsnumbersid) {
	$data = array();
	$query = "UPDATE cpa_shows_numbers SET datesgenerated = 1 WHERE id = $showsnumbersid";
	if ($mysqli->query($query)) {
		$data['success'] = true;
	} else {
		throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
	}
	return $data;
};

?>
