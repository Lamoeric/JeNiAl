<?php
/*
* Author : Eric Lamoureux
*
* File contains function to manage registrations
*
*/

// Get the rules associated with a charge, mainly of type DISCOUNT
function getChargesRules($mysqli, $chargecode, $language) {
	$query = "SELECT ccr.ruletype, ccr.ruleparameters
			FROM cpa_charges_rules ccr
			WHERE ccr.chargecode = '$chargecode'
			ORDER BY 'index'";
	$result = $mysqli->query($query);
	$data = array();
	$data['data'] = array();
	while ($row = $result->fetch_assoc()) {
		$data['data'][] = $row;
	}
	$data['success'] = true;
	return $data;
	exit;
};

// Get the details of all charges for a show/registration
function getShowChargesDetails($mysqli, $registrationid, $showid, $language, $online = false) {
	if ($online == true) {
		$query = "SELECT csc.id, cc.code, cc.alwaysdisplay, cc.alwaysselectedonline, cc.nonrefundable, cc.isonline, cc.issystem, csc.startdate, csc.enddate,
						getCodeDescription('chargerefundabletypes', cc.nonrefundable, '$language') nonrefundablelabel,
						cc.type, getCodeDescription('chargetypes', cc.type, '$language') typelabel, 
						if (crcold.id is not null AND crcold.amount != 0, crcold.amount, if(crc.id is not null AND crc.amount != 0, crc.amount, csc.amount)) amount /*csc.amount*/,
						getTextLabel(cc.label, '$language') label, 
						if (crc.id is not null OR    (cc.alwaysselectedonline AND ((csc.startdate is null OR if(cr.registrationdate is null, curdate()>= csc.startdate, cr.registrationdate >= csc.startdate)) AND (csc.enddate is null OR if(cr.registrationdate is null, curdate()<= csc.enddate, cr.registrationdate <= csc.enddate))) AND (cr.status is null or cr.status = 'DRAFT')), '1', '0') selected,
						if (crcold.id is not null OR (cc.alwaysselectedonline AND ((csc.startdate is null OR if(cr.registrationdate is null, curdate()>= csc.startdate, cr.registrationdate >= csc.startdate)) AND (csc.enddate is null OR if(cr.registrationdate is null, curdate()<= csc.enddate, cr.registrationdate <= csc.enddate))) AND (cr.status is null or cr.status = 'DRAFT')), '1', '0') selected_old,
						if ((csc.startdate is null OR if(cr.registrationdate is null, curdate()>= csc.startdate, cr.registrationdate >= csc.startdate)) AND (csc.enddate is null OR if(cr.registrationdate is null, curdate()<= csc.enddate, cr.registrationdate <= csc.enddate)), '1', '0') active,
						crc.comments, crc.oldchargeid
				FROM cpa_shows_charges csc
				LEFT JOIN cpa_registrations cr ON cr.id = $registrationid
				JOIN cpa_charges cc ON cc.code = csc.chargecode
				LEFT OUTER JOIN cpa_registrations_charges crc ON crc.chargeid = csc.id AND (crc.registrationid = cr.id OR crc.registrationid is null)
				LEFT OUTER JOIN cpa_registrations_charges crcold ON crcold.registrationid = cr.relatedoldregistrationid AND crcold.id = crc.oldchargeid
				WHERE csc.showid = $showid
				AND cc.active = 1
				ORDER BY cc.type, cc.alwaysselectedonline DESC, cc.code";
	} else {
		$query = "SELECT csc.id, cc.code, cc.alwaysdisplay, cc.alwaysselected, cc.nonrefundable, cc.isonline, cc.issystem, csc.startdate, csc.enddate,
						getCodeDescription('chargerefundabletypes', cc.nonrefundable, '$language') nonrefundablelabel,
						cc.type, getCodeDescription('chargetypes', cc.type, '$language') typelabel, 
						if (crcold.id is not null AND crcold.amount != 0, crcold.amount, if(crc.id is not null AND crc.amount != 0, crc.amount, csc.amount)) amount /*csc.amount*/,
						getTextLabel(cc.label, '$language') label, 
						if (crc.id is not null OR    (cc.alwaysselected AND ((csc.startdate is null OR if(cr.registrationdate is null, curdate()>= csc.startdate, cr.registrationdate >= csc.startdate)) AND (csc.enddate is null OR if(cr.registrationdate is null, curdate()<= csc.enddate, cr.registrationdate <= csc.enddate))) AND (cr.status is null or cr.status = 'DRAFT')), '1', '0') selected,
						if (crcold.id is not null OR (cc.alwaysselected AND ((csc.startdate is null OR if(cr.registrationdate is null, curdate()>= csc.startdate, cr.registrationdate >= csc.startdate)) AND (csc.enddate is null OR if(cr.registrationdate is null, curdate()<= csc.enddate, cr.registrationdate <= csc.enddate))) AND (cr.status is null or cr.status = 'DRAFT')), '1', '0') selected_old,
						if ((csc.startdate is null OR if(cr.registrationdate is null, curdate()>= csc.startdate, cr.registrationdate >= csc.startdate)) AND (csc.enddate is null OR if(cr.registrationdate is null, curdate()<= csc.enddate, cr.registrationdate <= csc.enddate)), '1', '0') active,
						crc.comments, crc.oldchargeid
				FROM cpa_shows_charges csc
				LEFT JOIN cpa_registrations cr ON cr.id = $registrationid
				JOIN cpa_charges cc ON cc.code = csc.chargecode
				LEFT OUTER JOIN cpa_registrations_charges crc ON crc.chargeid = csc.id AND (crc.registrationid = cr.id OR crc.registrationid is null)
				LEFT OUTER JOIN cpa_registrations_charges crcold ON crcold.registrationid = cr.relatedoldregistrationid AND crcold.id = crc.oldchargeid
				WHERE csc.showid = $showid
				AND cc.active = 1
				ORDER BY cc.type, cc.alwaysselected DESC, cc.code";
	}
	$result = $mysqli->query($query);
	$data = array();
	$data['data'] = array();
	while ($row = $result->fetch_assoc()) {
		$row['rules'] = getChargesRules($mysqli, $row['code'], $language)['data'];
		$data['data'][] = $row;
	}
	$data['success'] = true;
	return $data;
	exit;
};

// Get the details of all charges for a session/registration
function getChargesDetails($mysqli, $registrationid, $sessionid, $language, $online = false) {
	if ($online == true) {
		$query = "	SELECT csc.id, cc.code, cc.alwaysdisplay, cc.alwaysselectedonline, cc.nonrefundable, cc.isonline, cc.issystem, csc.startdate, csc.enddate,
							getCodeDescription('chargerefundabletypes', cc.nonrefundable, '$language') nonrefundablelabel,
							cc.type, getCodeDescription('chargetypes', cc.type, '$language') typelabel, 
							if (crcold.id is not null AND crcold.amount != 0, crcold.amount, if(crc.id is not null AND crc.amount != 0, crc.amount, csc.amount)) amount /*csc.amount*/,
							getTextLabel(cc.label, '$language') label, 
							if (crc.id is not null OR    (cc.alwaysselectedonline AND ((csc.startdate is null OR if(cr.registrationdate is null, curdate()>= csc.startdate, cr.registrationdate >= csc.startdate)) AND (csc.enddate is null OR if(cr.registrationdate is null, curdate()<= csc.enddate, cr.registrationdate <= csc.enddate))) AND (cr.status is null or cr.status = 'DRAFT')), '1', '0') selected,
							if (crcold.id is not null OR (cc.alwaysselectedonline AND ((csc.startdate is null OR if(cr.registrationdate is null, curdate()>= csc.startdate, cr.registrationdate >= csc.startdate)) AND (csc.enddate is null OR if(cr.registrationdate is null, curdate()<= csc.enddate, cr.registrationdate <= csc.enddate))) AND (cr.status is null or cr.status = 'DRAFT')), '1', '0') selected_old,
							if ((csc.startdate is null OR if(cr.registrationdate is null, curdate()>= csc.startdate, cr.registrationdate >= csc.startdate)) AND (csc.enddate is null OR if(cr.registrationdate is null, curdate()<= csc.enddate, cr.registrationdate <= csc.enddate)), '1', '0') active,
							crc.comments, crc.oldchargeid, cr.registrationdate, csc.startdate, csc.enddate
					FROM cpa_sessions_charges csc
					LEFT JOIN cpa_registrations cr ON cr.id = $registrationid
					JOIN cpa_charges cc ON cc.code = csc.chargecode
					LEFT OUTER JOIN cpa_registrations_charges crc ON crc.chargeid = csc.id AND (crc.registrationid = cr.id OR crc.registrationid is null)
					LEFT OUTER JOIN cpa_registrations_charges crcold ON crcold.registrationid = cr.relatedoldregistrationid AND crcold.id = crc.oldchargeid
					WHERE csc.sessionid = $sessionid
					AND cc.active = 1
					AND cc.isonline = 1
					ORDER BY cc.type, cc.alwaysselectedonline DESC, cc.code";
	} else {
		$query = "	SELECT csc.id, cc.code, cc.alwaysdisplay, cc.alwaysselected, cc.nonrefundable, cc.isonline, cc.issystem, csc.startdate, csc.enddate,
							getCodeDescription('chargerefundabletypes', cc.nonrefundable, '$language') nonrefundablelabel,
							cc.type, getCodeDescription('chargetypes', cc.type, '$language') typelabel, 
							if (crcold.id is not null AND crcold.amount != 0, crcold.amount, if(crc.id is not null AND crc.amount != 0, crc.amount, csc.amount)) amount /*csc.amount*/,
							getTextLabel(cc.label, '$language') label, 
							if (crc.id is not null OR    (cc.alwaysselected AND ((csc.startdate is null OR if(cr.registrationdate is null, curdate()>= csc.startdate, cr.registrationdate >= csc.startdate)) AND (csc.enddate is null OR if(cr.registrationdate is null, curdate()<= csc.enddate, cr.registrationdate <= csc.enddate))) AND (cr.status is null or cr.status = 'DRAFT')), '1', '0') selected,
							if (crcold.id is not null OR (cc.alwaysselected AND ((csc.startdate is null OR if(cr.registrationdate is null, curdate()>= csc.startdate, cr.registrationdate >= csc.startdate)) AND (csc.enddate is null OR if(cr.registrationdate is null, curdate()<= csc.enddate, cr.registrationdate <= csc.enddate))) AND (cr.status is null or cr.status = 'DRAFT')), '1', '0') selected_old,
							if ((csc.startdate is null OR if(cr.registrationdate is null, curdate()>= csc.startdate, cr.registrationdate >= csc.startdate)) AND (csc.enddate is null OR if(cr.registrationdate is null, curdate()<= csc.enddate, cr.registrationdate <= csc.enddate)), '1', '0') active,
							crc.comments, crc.oldchargeid
					FROM cpa_sessions_charges csc
					LEFT JOIN cpa_registrations cr ON cr.id = $registrationid
					JOIN cpa_charges cc ON cc.code = csc.chargecode
					LEFT OUTER JOIN cpa_registrations_charges crc ON crc.chargeid = csc.id AND (crc.registrationid = cr.id OR crc.registrationid is null)
					LEFT OUTER JOIN cpa_registrations_charges crcold ON crcold.registrationid = cr.relatedoldregistrationid AND crcold.id = crc.oldchargeid
					WHERE csc.sessionid = $sessionid
					AND cc.active = 1
					ORDER BY cc.type, cc.alwaysselected DESC, cc.code";
	}
	$result = $mysqli->query($query);
	$data = array();
	$data['data'] = array();
	while ($row = $result->fetch_assoc()) {
		$row['rules'] = getChargesRules($mysqli, $row['code'], $language)['data'];
		$data['data'][] = $row;
	}
	$data['success'] = true;
	return $data;
	exit;
};

// Get the schedule for a course as a big string
function getSessionCourseSchedule($mysqli, $sessionscoursesid, $language) {
	$schedule = '';
	$query = "select group_concat(concat(getTextLabel((select label from cpa_arenas where id = arenaid), '$language'),
																				if ((iceid is null or iceid = 0), ', ', concat(' (' , getTextLabel((select label from cpa_arenas_ices where id = iceid), '$language'), '), ')),
																				getTextLabel((select description from cpa_codetable where ctname = 'days' and code = day), '$language'),
																				' - ',
																				substr(starttime FROM 1 FOR 5),
																				' - ',
																				substr(endtime FROM 1 FOR 5))
																SEPARATOR ', ') schedule
						from cpa_sessions_courses_schedule
						where sessionscoursesid = $sessionscoursesid";
	$result = $mysqli->query($query);
	while ($row = $result->fetch_assoc()) {
		$schedule = $row['schedule'];
		return $schedule;
	}
	return $schedule;
}

// Get the schedule for a course as a big string
function getShowNumberSchedule($mysqli, $sessionscoursesid, $language) {
	$schedule = '';
	$query = "select group_concat(concat(getTextLabel((select label from cpa_arenas where id = arenaid), '$language'),
																				if ((iceid is null or iceid = 0), ', ', concat(' (' , getTextLabel((select label from cpa_arenas_ices where id = iceid), '$language'), '), ')),
																				getTextLabel((select description from cpa_codetable where ctname = 'days' and code = day), '$language'),
																				' - ',
																				substr(starttime FROM 1 FOR 5),
																				' - ',
																				substr(endtime FROM 1 FOR 5))
																SEPARATOR ', ') schedule
						from cpa_shows_numbers_schedule
						where numberid = $sessionscoursesid";
	$result = $mysqli->query($query);
	while ($row = $result->fetch_assoc()) {
		$schedule = $row['schedule'];
		return $schedule;
	}
	return $schedule;
}

// Try to get the numbers of the member, with the delta from the previous registration already computed
 function getShowNumbersDetails($mysqli, $registrationid, $registrationdate, $showid, $memberid, $language) {
	$data = array();
	$data['data'] = array();
	if ($memberid == null) $memberid = 0;
	$query = "select csn.id, 'SHOWNUMBER' as coursecode, csn.name, csn.registrationtype, csn.mandatory, getTextLabel(csn.label, 'fr-ca') label, crc.amount realpaidamount, csn.fees, 
						getCodeDescription('numberregistrationtypes', csn.registrationtype, '$language') registrationtypelabel,
						getCodeDescription('yesnos', csn.mandatory/1, '$language') mandatorylabel,
			            if (crcold.amount is null, 0, crcold.amount) fees_old, 
			            if (csn.mandatory = 1 and crcold.registrationid is null, 1, if (crc.selected is null, 0, crc.selected)) selected,
			            if (crcold.selected is null, 0, crcold.selected) selected_old
			FROM cpa_shows_numbers csn
			left join cpa_registrations_numbers crc on crc.registrationid = $registrationid and crc.numberid = csn.id
			left join cpa_registrations_numbers crcold on crcold.registrationid = (select relatedoldregistrationid from cpa_registrations where id = $registrationid) and crcold.numberid = csn.id
			WHERE csn.showid = $showid
            AND csn.type = 1
            AND csn.registrationtype != 1
            AND (csn.registrationtype = 3 or (csn.registrationtype = 2 and exists (select 1 from cpa_shows_numbers_invites where groupormemberid = $memberid and numberid = csn.id)))
			ORDER BY csn.name";
	$result = $mysqli->query($query);
	while ($row = $result->fetch_assoc()) {
		$row['schedule'] = getShowNumberSchedule($mysqli, $row['id'], $language);
		$data['data'][] = $row;
	}
	$data['success'] = true;
	return $data;
};

// Try to get the courses of the member, with the delta from the previous registration already computed
 function getSessionCoursesDetails($mysqli, $registrationid, $registrationdate, $sessionid, $language) {
	$data = array();
	$data['data'] = array();
	$query = "select csc.id, csc.coursecode, csc.courselevel, csc.name, csc.maxnumberskater, csc.availableonline,
					(select count(*) from cpa_sessions_courses_members cscm where sessionscoursesid = csc.id and membertype = 3 and (cscm.registrationenddate is null or cscm.registrationenddate > curdate())) nbofskaters,
					getTextLabel((select label from cpa_courses_levels where coursecode = csc.coursecode and code = csc.courselevel), '$language') courselevellabel,
					getTextLabel(csc.label, '$language') label, crc.amount realpaidamount,
					csc.fees, if (crcold.amount is null, 0, crcold.amount) fees_old, /*if (crc.id is not null, '1', '0') selected,*/ if (crc.selected is null, 0, crc.selected) selected,  /*if (crcold.id is not null, '1', '0')*/ if (crcold.selected is null, 0, crcold.selected) selected_old,
					(SELECT floor(datediff(coursesenddate, coursesstartdate)/7) FROM cpa_sessions WHERE id = $sessionid) sessionnbofweeks,
					(select count(*) from cpa_sessions_courses_dates where sessionscoursesid = csc.id and canceled = 0 and manual = 0) nbofcourses,
					(select count(*) from cpa_sessions_courses_dates where sessionscoursesid = csc.id and canceled = 0 and manual = 0 and coursedate >= '$registrationdate') nbofcoursesleft
			from cpa_sessions_courses csc
			join cpa_courses cc ON cc.code = csc.coursecode
			left join cpa_registrations_courses crc on crc.registrationid = $registrationid and crc.courseid = csc.id
			left join cpa_registrations_courses crcold on crcold.registrationid = (select relatedoldregistrationid from cpa_registrations where id = $registrationid) and crcold.courseid = csc.id
			where csc.sessionid = $sessionid
			and datesgenerated = 1
			and cc.acceptregistrations = 1
			order by coursecode, courselevel, csc.name";
	$result = $mysqli->query($query);
	while ($row = $result->fetch_assoc()) {
		$row['schedule'] = getSessionCourseSchedule($mysqli, $row['id'], $language);
		$data['data'][] = $row;
	}
	$data['success'] = true;
	return $data;
};

// Count the number of PRESENTED registration for the members of the same family
function countShowFamilyMembersRegistrations($mysqli, $showid, $memberid, $language) {
	$nbofmembers = 0;
	if (!empty($memberid)) {
		$query = "SELECT count(*) nb
				FROM (SELECT distinct cr.*
						from cpa_registrations cr
						join cpa_members_contacts cmc ON cmc.memberid = cr.memberid
						join cpa_members_contacts cmc2 ON cmc2.contactid = cmc.contactid
						where cr.showid = $showid
						and (cr.status = 'ACCEPTED' or cr.status = 'DRAFT-R' or cr.status = 'PRESENTED-R')
						and cr.relatedoldregistrationid = 0
						and cr.memberid != $memberid
						and cmc2.memberid = $memberid) a";
		$result = $mysqli->query($query);
	  $row = $result->fetch_assoc();
		$nbofmembers = $row['nb'];
	}
  return $nbofmembers;
}

// Count the number of PRESENTED registration for the members of the same family
function countFamilyMembersRegistrations($mysqli, $eventtype, $eventid, $memberid, $language) {
	$nbofmembers = 0;
	if (!empty($memberid)) {
		if ($eventtype == 1) { // Session
			$query = "SELECT count(*) nb
					  FROM (SELECT distinct cr.*
							from cpa_registrations cr
							join cpa_members_contacts cmc ON cmc.memberid = cr.memberid
							join cpa_members_contacts cmc2 ON cmc2.contactid = cmc.contactid
							where cr.sessionid = $eventid
							and (cr.status = 'ACCEPTED' or cr.status = 'DRAFT-R' or cr.status = 'PRESENTED-R')
							and cr.relatedoldregistrationid = 0
							and cr.memberid != $memberid
							and cmc2.memberid = $memberid) a";
		} else if ($eventtype == 2) { // Show
			$query = "SELECT count(*) nb
		  			FROM (SELECT distinct cr.*
							from cpa_registrations cr
							join cpa_members_contacts cmc ON cmc.memberid = cr.memberid
							join cpa_members_contacts cmc2 ON cmc2.contactid = cmc.contactid
							where cr.showid = $eventid
							and (cr.status = 'ACCEPTED' or cr.status = 'DRAFT-R' or cr.status = 'PRESENTED-R')
							and cr.relatedoldregistrationid = 0
							and cr.memberid != $memberid
							and cmc2.memberid = $memberid) a";
		}
		$result = $mysqli->query($query);
	  $row = $result->fetch_assoc();
		$nbofmembers = $row['nb'];
	}
  return $nbofmembers;
}

/**
 * Checks if the member already has a registration (apart from the one we are working on)
 */
function memberAlreadyHasARegistration($mysqli, $registration) {
	if ($mysqli->real_escape_string(isset($registration['member']['id']))) {
		$memberid = $mysqli->real_escape_string(isset($registration['member']['id']) ? (int) $registration['member']['id'] : 0);
		$id = $registration['id'];
		$sessionid = isset($registration['sessionid']) ? (int)$registration['sessionid'] : null;
		$showid = isset($registration['showid']) ? (int)$registration['showid'] : null;
		$query = "SELECT count(*) nb
				FROM cpa_registrations
				WHERE (memberid = $memberid AND memberid != 0)
				AND id != $id "
				. ($sessionid == null? "AND showid = $showid " : "AND sessionid = $sessionid ") .
//							AND (($sessionid is not null AND sessionid = $sessionid) OR ($showid is not null AND showid = $showid))
				"AND (relatednewregistrationid is null or relatednewregistrationid = 0)";
		$result = $mysqli->query($query);
		while ($row = $result->fetch_assoc()) {
			if ($row['nb'] != 0) return true;
		}
	}
	return false;
};

/**
 * This function will handle member update functionality
 * @throws Exception
 */
function update_member($mysqli, $member) {
	$data = array();
	$id = 						$mysqli->real_escape_string(isset($member['id']) 						? $member['id'] : '');
	$firstname = 			$mysqli->real_escape_string(isset($member['firstname']) 			? $member['firstname'] : '');
	$lastname = 			$mysqli->real_escape_string(isset($member['lastname']) 			? $member['lastname'] : '');
	$skatecanadano = 	$mysqli->real_escape_string(isset($member['skatecanadano'])	? $member['skatecanadano'] : '');
	$initial = 				$mysqli->real_escape_string(isset($member['initial']) 				? $member['initial'] : '');
	$language = 			$mysqli->real_escape_string(isset($member['language']) 			? $member['language'] : '');
	$gender = 				$mysqli->real_escape_string(isset($member['gender']) 				? $member['gender'] : '');
	$familyrank = 		$mysqli->real_escape_string(isset($member['familyrank']) 		? $member['familyrank'] : 0);
	$birthday = 			$mysqli->real_escape_string(isset($member['birthday']) 			? $member['birthday'] : '0000-01-01');
	$healthcareno = 	$mysqli->real_escape_string(isset($member['healthcareno']) 	? $member['healthcareno'] : '');
	$healthcareexp = 	$mysqli->real_escape_string(isset($member['healthcareexp']) 	? $member['healthcareexp'] : '');
	$healthcomments = $mysqli->real_escape_string(isset($member['healthcomments']) ? $member['healthcomments'] : '');
	$qualifications = $mysqli->real_escape_string(isset($member['qualifications']) ? $member['qualifications'] : '');
	$address2 = 			$mysqli->real_escape_string(isset($member['address2']) 				? $member['address2'] : '');
	$address1 = 			$mysqli->real_escape_string(isset($member['address1']) 		? $member['address1'] : '');
	$town = 					$mysqli->real_escape_string(isset($member['town']) 					? $member['town'] : '');
	$province = 			$mysqli->real_escape_string(isset($member['province']) 			? $member['province'] : '');
	$postalcode = 		$mysqli->real_escape_string(isset($member['postalcode']) 		? $member['postalcode'] : '');
	$country = 				$mysqli->real_escape_string(isset($member['country']) 				? $member['country'] : '');
	$homephone = 			$mysqli->real_escape_string(isset($member['homephone']) 			? $member['homephone'] : '');
	$cellphone = 			$mysqli->real_escape_string(isset($member['cellphone']) 			? $member['cellphone'] : '');
	$otherphone = 		$mysqli->real_escape_string(isset($member['otherphone']) 		? $member['otherphone'] : '');
	$email = 					$mysqli->real_escape_string(isset($member['email']) 					? $member['email'] : '');
	$email2 = 				$mysqli->real_escape_string(isset($member['email2']) 				? $member['email2'] : '');
	$reportsc = 			$mysqli->real_escape_string(isset($member['reportsc']) 			? (int)$member['reportsc'] : 0);
	$homeclub = 			$mysqli->real_escape_string(isset($member['homeclub']) 			? $member['homeclub'] : '');
	$skaterlevel = 		$mysqli->real_escape_string(isset($member['skaterlevel']) 		? $member['skaterlevel'] : '');
	$mainprogram = 		$mysqli->real_escape_string(isset($member['mainprogram']) 		? $member['mainprogram'] : '');
	$secondprogram = 	$mysqli->real_escape_string(isset($member['secondprogram'])	? $member['secondprogram'] : '');
	$comments = 			$mysqli->real_escape_string(isset($member['comments']) 			? $member['comments'] : '');

	if ($firstname == '' || $lastname == '') {
		throw new Exception($GLOBALS['thisfilename'].'\update_member ' ."Required fields missing, Please enter and submit");
	}

	if (empty($id)) {
		$data['insert'] = true;
		$query = "INSERT INTO cpa_members (id, firstname, lastname) VALUES (NULL, '$firstname', '$lastname')";
	} else {
		$query = "UPDATE cpa_members SET firstname = '$firstname', lastname = '$lastname', skatecanadano = '$skatecanadano',
							initial = '$initial', language = '$language', gender = '$gender', birthday = '$birthday', healthcareno = '$healthcareno',
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
		throw new Exception($GLOBALS['thisfilename'].'\update_member ' .$mysqli->sqlstate.' - '. $mysqli->error);
	}
	return $data;
};

function copyMemberContacts($mysqli, $member) {
	$data = array();
	$memberid = 	$mysqli->real_escape_string(isset($member['id']) 					? $member['id'] : '');
	$copiedfrom =	$mysqli->real_escape_string(isset($member['copiedfrom']) 	? $member['copiedfrom'] : '');

	$query = "INSERT INTO cpa_members_contacts (id, memberid, contactid, contacttype, incaseofemergency)
			SELECT null, $memberid, contactid, contacttype, incaseofemergency
			FROM cpa_members_contacts cmc
			WHERE cmc.memberid = $copiedfrom
			AND not exists (SELECT id FROM cpa_members_contacts cmc2 WHERE cmc2.memberid = $memberid AND cmc2.contactid = cmc.contactid)";
	if ($mysqli->query($query)) {
		$data['success'] = true;
		$data['message'] = 'Member contacts copied successfully.'.$memberid.' '.$copiedfrom;
	} else {
		throw new Exception($GLOBALS['thisfilename'].'\update_member ' .$mysqli->sqlstate.' - '. $mysqli->error);
	}
	return $data;
	exit;
}

function updateEntireMember($mysqli, $member) {
	$data = array();
	$id = 				$mysqli->real_escape_string(isset($member['id']) 					? $member['id'] : '');
	$copiedfrom =	$mysqli->real_escape_string(isset($member['copiedfrom']) 	? $member['copiedfrom'] : '');

	if ($id == '') {
		$data['member'] = update_member($mysqli, $member);
		$id = $data['member']['id'];
		$member['id'] = $id;
	}
	if ($id != '') {
		$data['member2'] = update_member($mysqli, $member);
	}
	if (!empty($copiedfrom)) {
		$data['copycontacts'] = copyMemberContacts($mysqli, $member);
	}
	if ($mysqli->real_escape_string(isset($member['contacts']))) {
		$data['successcontacts'] = updateEntireContacts($mysqli, $id, $member['contacts']);
	}

	$data['success'] = true;
	$data['message'] = 'Member updated successfully.';
	return $data;
	exit;
};

/**
 * This function will handle registration update functionality
 * @throws Exception
 */
function update_registration($mysqli, $registration, $newstatus) {
	$data = array();
	$id 											= $mysqli->real_escape_string(isset($registration['id']) 												? $registration['id'] : '');
	$sessionid 								= $mysqli->real_escape_string(isset($registration['sessionid']) 								? (int)$registration['sessionid'] : 0);
	$showid 									= $mysqli->real_escape_string(isset($registration['showid']) 										? (int)$registration['showid'] : 0);
	$registrationdate 				= $mysqli->real_escape_string(isset($registration['registrationdatestr']) 			? $registration['registrationdatestr'] : '');
	$relatednewregistrationid =	$mysqli->real_escape_string(isset($registration['relatednewregistrationid'])	? (int) $registration['relatednewregistrationid'] : 0);
	$relatedoldregistrationid =	$mysqli->real_escape_string(isset($registration['relatedoldregistrationid'])	? (int) $registration['relatedoldregistrationid'] : 0);
	$regulationsread 					= $mysqli->real_escape_string(isset($registration['regulationsread'])						? (int) $registration['regulationsread'] : 0);
	$comments 								=	$mysqli->real_escape_string(isset($registration['comments'])									? $registration['comments'] : '');
	$memberid 								=	$mysqli->real_escape_string(isset($registration['memberid'])									? (int) $registration['memberid'] : 0);
	$familycount 							=	$mysqli->real_escape_string(isset($registration['familyMemberCount'])					? (int) $registration['familyMemberCount'] : 0);
	if ($newstatus != null) {
		$status	= $newstatus;
	} else {
		$status	= $mysqli->real_escape_string(isset($registration['status']) ? $registration['status'] : 'DRAFT');
	}

	if (empty($id)) {
		$data['insert'] = true;
		if ($showid == 0) {
			$eventtype = 1;
			$eventid = $sessionid;
			$query = "INSERT INTO cpa_registrations (id, memberid, sessionid, showid, registrationdate, relatednewregistrationid, relatedoldregistrationid, status, regulationsread, comments, familycount)
					VALUES (NULL, $memberid, $sessionid, null, '$registrationdate', $relatednewregistrationid, $relatedoldregistrationid, '$status', $regulationsread, '$comments', $familycount)";
		} else {
			$eventtype = 2;
			$eventid = $showid;
			$query = "INSERT INTO cpa_registrations (id, memberid, sessionid, showid, registrationdate, relatednewregistrationid, relatedoldregistrationid, status, regulationsread, comments, familycount)
					VALUES (NULL, $memberid, null, $showid, '$registrationdate', $relatednewregistrationid, $relatedoldregistrationid, '$status', $regulationsread, '$comments', $familycount)";
		}
		if ($mysqli->query($query)) {
			$data['success'] = true;
			$data['message'] = 'Registration inserted successfully.';
			$data['id'] = (int) $mysqli->insert_id;
			$data['eventtype'] = (int)$eventtype;
			$data['eventid'] = (int)$eventid;
		} else {
			throw new Exception($GLOBALS['thisfilename'].'\update_registration ' .$mysqli->sqlstate.' - '. $mysqli->error);
		}
	} else {
		if ($showid == 0) {
			$query = "UPDATE cpa_registrations
					SET memberid = '$memberid', sessionid = $sessionid, showid = null, registrationdate = '$registrationdate',
					relatednewregistrationid = '$relatednewregistrationid', relatedoldregistrationid = '$relatedoldregistrationid',
					status = '$status', regulationsread = '$regulationsread' , comments = '$comments', familycount = $familycount
					WHERE id = $id";
		} else {
			$query = "UPDATE cpa_registrations
					SET memberid = '$memberid', sessionid = null, showid = $showid, registrationdate = '$registrationdate',
					relatednewregistrationid = '$relatednewregistrationid', relatedoldregistrationid = '$relatedoldregistrationid',
					status = '$status', regulationsread = '$regulationsread' , comments = '$comments', familycount = $familycount
					WHERE id = $id";
		}
		if ($mysqli->query($query)) {
			$data['success'] = true;
			$data['message'] = 'Registration updated successfully.';
			$data['id'] = (int) $id;
		} else {
			throw new Exception($GLOBALS['thisfilename'].'\update_registration ' .$mysqli->sqlstate.' - '. $mysqli->error);
		}
	}
	return $data;
};

/**
 * This function will handle insert/delete of a registration course in DB
 * @throws Exception
 */
function updateEntireRegistrationCourses($mysqli, $registrationid, $courses) {
	$data = array();
	$query = "DELETE FROM cpa_registrations_courses WHERE registrationid = '$registrationid'";
	if (!$mysqli->query($query)) {
		throw new Exception($GLOBALS['thisfilename'].'\updateEntireRegistrationCourses ' .$mysqli->sqlstate.' - '. $mysqli->error);
	}
	for($x = 0; $x < count($courses); $x++) {
		$courseid 			= $mysqli->real_escape_string(isset($courses[$x]['id']) 						? $courses[$x]['id'] : '');
		$selected 			= $mysqli->real_escape_string(isset($courses[$x]['selected']) 			? $courses[$x]['selected'] : '0');
		$selected_old 	= $mysqli->real_escape_string(isset($courses[$x]['selected_old']) 	? $courses[$x]['selected_old'] : '0');
		$feesbilling		= $mysqli->real_escape_string(isset($courses[$x]['fees_billing']) 	? (float)$courses[$x]['fees_billing'] : '0');
		$deltacode			= $mysqli->real_escape_string(isset($courses[$x]['deltacode']) 			? $courses[$x]['deltacode'] : '0');

		if (($selected and $selected == '1') or ($selected_old and $selected_old == '1') or ($feesbilling and $feesbilling > 0)) {
			$data[$courseid] = true;
			$query = "INSERT into cpa_registrations_courses	(id, registrationid, courseid, amount, selected, deltacode)
					VALUES (null, '$registrationid', '$courseid', '$feesbilling', '$selected', '$deltacode')";
			if (!$mysqli->query($query)) {
				throw new Exception($GLOBALS['thisfilename'].'\updateEntireRegistrationCourses ' .$mysqli->sqlstate.' - '. $mysqli->error);
			}
		}
	}
	$data['successCourses'] = true;
	return $data;
};

/**
 * This function will handle insert/delete of a number in the registration
 * @throws Exception
 */
function updateEntireRegistrationNumbers($mysqli, $registrationid, $numbers) {
	$data = array();
	$query = "DELETE FROM cpa_registrations_numbers WHERE registrationid = '$registrationid'";
	if (!$mysqli->query($query)) {
		throw new Exception($GLOBALS['thisfilename'].'\updateEntireRegistrationNumbers ' .$mysqli->sqlstate.' - '. $mysqli->error);
	}
	for($x = 0; $x < count($numbers); $x++) {
		$numberid 			= $mysqli->real_escape_string(isset($numbers[$x]['id']) 						? $numbers[$x]['id'] : '');
		$selected 			= $mysqli->real_escape_string(isset($numbers[$x]['selected']) 			? $numbers[$x]['selected'] : '0');
		$selected_old 	= $mysqli->real_escape_string(isset($numbers[$x]['selected_old']) 	? $numbers[$x]['selected_old'] : '0');
		$feesbilling		= $mysqli->real_escape_string(isset($numbers[$x]['fees_billing']) 	? (float)$numbers[$x]['fees_billing'] : '0');
		$deltacode			= $mysqli->real_escape_string(isset($numbers[$x]['deltacode']) 			? $numbers[$x]['deltacode'] : '0');

		if (($selected and $selected == '1') or ($selected_old and $selected_old == '1') or ($feesbilling and $feesbilling > 0)) {
			$query = "INSERT into cpa_registrations_numbers (id, registrationid, numberid, amount, selected, deltacode)
					VALUES (null, '$registrationid', '$numberid', '$feesbilling', '$selected', '$deltacode')";
			if (!$mysqli->query($query)) {
				throw new Exception($GLOBALS['thisfilename'].'\updateEntireRegistrationNumbers ' .$mysqli->sqlstate.' - '. $mysqli->error);
			} else {
				$data[$numberid] = true;
			}
		}
	}
	$data['successCourses'] = true;
	return $data;
};

/**
 * This function will handle insert/delete of a registration charge in DB
 * @throws Exception
 */
function updateEntireRegistrationCharges($mysqli, $registrationid, $charges) {
	$data = array();
	$query = "DELETE FROM cpa_registrations_charges WHERE registrationid = '$registrationid'";
	if (!$mysqli->query($query)) {
		throw new Exception($GLOBALS['thisfilename'].'\updateEntireRegistrationCharges ' .$mysqli->sqlstate.' - '. $mysqli->error);
	}
	for($x = 0; $x < count($charges); $x++) {
		$chargeid = 		$mysqli->real_escape_string(isset($charges[$x]['id']) 						? $charges[$x]['id'] : '');
		$comments = 		$mysqli->real_escape_string(isset($charges[$x]['comments']) 			? $charges[$x]['comments'] : '');
		$amount = 			$mysqli->real_escape_string(isset($charges[$x]['amount']) 				? (int) $charges[$x]['amount'] : 0);
		$oldchargeid = 	$mysqli->real_escape_string(isset($charges[$x]['oldchargeid']) 		? (int) $charges[$x]['oldchargeid'] : 0);
		$selected = 		$mysqli->real_escape_string(isset($charges[$x]['selected']) 			? $charges[$x]['selected'] : '0');

		if ($selected and $selected == '1') {
			$data[$chargeid] = true;
			$query = "INSERT into cpa_registrations_charges	(id, registrationid, chargeid, amount, comments, oldchargeid)
					VALUES (null, '$registrationid', '$chargeid', $amount, '$comments', $oldchargeid)";
			if (!$mysqli->query($query)) {
				throw new Exception($GLOBALS['thisfilename'].'\updateEntireRegistrationCharges ' .$mysqli->sqlstate.' - '. $mysqli->error);
			}
		}
	}
	$data['successCharges'] = true;
	return $data;
};

function updateEntireRegistrationInt($mysqli, $registration, $newstatus) {
	$data = array();
	$id = $registration['id'];

	// Check if registration is connected to a member, and if so, if that member already has a registration for the session.
	if ($id == null || memberAlreadyHasARegistration($mysqli, $registration) == false) {
		if ($mysqli->real_escape_string(isset($_POST['registration']['member']))) {
			$data['member'] = updateEntireMember($mysqli,  $registration['member']);
			$registration['memberid'] = $data['member']['member2']['id'];
		}
		$data['registration'] = update_registration($mysqli, $registration, $newstatus);
		// In case the registration is being inserted here, take the registration id from the update_registration function
		if ($id == null) {
			$id = $data['registration']['id'];
		}
		if ($mysqli->real_escape_string(isset($registration['courses']))) {
			$data['courses'] 	= updateEntireRegistrationCourses($mysqli,  $id, $registration['courses']);
		}
		if ($mysqli->real_escape_string(isset($registration['shownumbers']))) {
			$data['shownumbers'] 	= updateEntireRegistrationNumbers($mysqli,  $id, $registration['shownumbers']);
		}
		$data['charges'] 	= updateEntireRegistrationCharges($mysqli,  $id, $registration['charges']);

		$data['success'] = true;
		$data['message'] = 'Registration updated successfully.';
	} else {
		$data['success'] = false;
		$data['errno']   = 9999;
		$data['message'] = 'Member already has a registration.';
	}
	return $data;
};

/**
 * This function validates if there is enough room left in a course for a new registration
 * @throws Exception
 */
function validateSessionCourseMemberCount($mysqli, $registration, $language) {
	$sessioncourses 	= $registration['courses'];
	$registrationdate	= $registration['registrationdatestr'];
//	for($x = 0; $sessioncourses != null && $x < count($sessioncourses); $x++) {
	for($x = 0; $x < count($sessioncourses); $x++) {
		$sessionscoursesid 	= $mysqli->real_escape_string(isset($sessioncourses[$x]['id']) ? $sessioncourses[$x]['id'] : '');

		// Member has selected the course - validate
		if ($mysqli->real_escape_string(isset($sessioncourses[$x]['selected'])) and $mysqli->real_escape_string(isset($sessioncourses[$x]['selected_old'])) and $sessioncourses[$x]['selected'] == '1' and $sessioncourses[$x]['selected_old'] == '0') {
			$query = "SELECT csc.id, csc.coursecode, csc.courselevel, csc.name, csc.maxnumberskater,
							(select count(*) from cpa_sessions_courses_members cscm where cscm.sessionscoursesid = csc.id and cscm.membertype = 3 and (cscm.registrationenddate is null or (cscm.registrationenddate > '$registrationdate' and cscm.registrationenddate != cscm.registrationstartdate))) nbofskaters
					from cpa_sessions_courses csc
					join cpa_courses cc ON cc.code = csc.coursecode
					where csc.id = '$sessionscoursesid'";
			$result = $mysqli->query($query);
			while ($row = $result->fetch_assoc()) {
				if ((int)$row['nbofskaters'] + 1 > (int)$row['maxnumberskater']) {
					throw new Exception('9999 - Course ' . $sessionscoursesid . ' is full');
				}
			}
		}
	}
	return true;
};


/**
 * This function will handle session course member update functionality
 * We need to modify the registration to the different courses differently depending if the courses are already started,
 * based on the start date of the session. This function also reads the nb of skaters and the maximum nb of skaters alowed
 * and returns in the 'data' member of the $data array.
 * @throws Exception
 */
function updateEntireSessionCourseMember($mysqli, $memberid, $registration, $language) {
	$data = array();
	$registrationdate	= $registration['registrationdatestr'];
	$sessioncourses 	= $registration['courses'];
	for($x = 0; $x < count($sessioncourses); $x++) {
		$sessionscoursesid 	= $mysqli->real_escape_string(isset($sessioncourses[$x]['id']) ? $sessioncourses[$x]['id'] : '');

		// Member has selected the course - insert in the cpa_sessions_courses_members table
		if ($mysqli->real_escape_string(isset($sessioncourses[$x]['selected'])) and $mysqli->real_escape_string(isset($sessioncourses[$x]['selected_old'])) and $sessioncourses[$x]['selected'] == '1' and $sessioncourses[$x]['selected_old'] == '0') {
			$query = "INSERT INTO cpa_sessions_courses_members (id, sessionscoursesid, memberid, membertype, registrationstartdate)
					VALUES (NULL, '$sessionscoursesid', '$memberid', '3', '$registrationdate')";
			if ($mysqli->query($query)) {
				$query = "SELECT csc.id, csc.coursecode, csc.courselevel, csc.name, csc.maxnumberskater,
									(select count(*) from cpa_sessions_courses_members cscm where cscm.sessionscoursesid = csc.id and cscm.membertype = 3 and (cscm.registrationenddate is null or (cscm.registrationenddate > '$registrationdate' and cscm.registrationenddate != cscm.registrationstartdate))) nbofskaters,
									getTextLabel((select label from cpa_courses_levels where coursecode = csc.coursecode and code = csc.courselevel), '$language') courselevellabel,
									getTextLabel(csc.label, '$language') label
						from cpa_sessions_courses csc
						join cpa_courses cc ON cc.code = csc.coursecode
						where csc.id = '$sessionscoursesid'";
				$result = $mysqli->query($query);
				while ($row = $result->fetch_assoc()) {
					$data['data'][] = $row;
				}
			} else {
				throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
			}
		}

		// Member has removed the course - set the enddate for the course
		// TODO : if the enddate for the course is before the start date of the courses for the session, we should delete the member from the course entirely.
		if ($mysqli->real_escape_string(isset($sessioncourses[$x]['selected'])) and $mysqli->real_escape_string(isset($sessioncourses[$x]['selected_old'])) and $sessioncourses[$x]['selected'] == '0' and $sessioncourses[$x]['selected_old'] == '1') {
			$query = "UPDATE cpa_sessions_courses_members
					SET registrationenddate = '$registrationdate'
					WHERE sessionscoursesid = '$sessionscoursesid' and memberid = '$memberid'";
			if ($mysqli->query($query)) {
			} else {
				throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
			}
		}
	}
	$data['success'] = true;
	return $data;
};

/**
 * This function will handle show number member update functionality
 * @throws Exception
 */
function updateEntireShowNumberMember($mysqli, $memberid, $registration, $language) {
	$data = array();
	$registrationdate = $registration['registrationdatestr'];
	$shownumbers = $registration['shownumbers'];
	for($x = 0; $x < count($shownumbers); $x++) {
		$showsnumbersid = $mysqli->real_escape_string(isset($shownumbers[$x]['id']) ? (int)$shownumbers[$x]['id'] : 0);
		$showid = $mysqli->real_escape_string(isset($shownumbers[$x]['showid']) ? (int)$shownumbers[$x]['showid'] : 0);

		// Member has selected the number - insert in the cpa_shows_numbers_members table
		if ($mysqli->real_escape_string(isset($shownumbers[$x]['selected'])) and $mysqli->real_escape_string(isset($shownumbers[$x]['selected_old'])) and $shownumbers[$x]['selected'] == '1' and $shownumbers[$x]['selected_old'] == '0') {
			$query = "INSERT INTO cpa_shows_numbers_members (id, showid, numberid, memberid, registrationstartdate)
					VALUES (NULL, (select showid from cpa_shows_numbers where id = $showsnumbersid), $showsnumbersid, $memberid, '$registrationdate')";
			if (!$mysqli->query($query)) {
				throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
			}
		}

		// Member has removed the number - set the enddate for the number
		// TODO : if the enddate for the number is before the start date of the number practice date, we should delete the member from the number entirely.
		if ($mysqli->real_escape_string(isset($shownumbers[$x]['selected'])) and $mysqli->real_escape_string(isset($shownumbers[$x]['selected_old'])) and $shownumbers[$x]['selected'] == '0' and $shownumbers[$x]['selected_old'] == '1') {
			$query = "UPDATE cpa_shows_numbers_members
					SET registrationenddate = '$registrationdate'
					WHERE numberid = $showsnumbersid and memberid = $memberid";
			if (!$mysqli->query($query)) {
				throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
			}
		}
	}
	$data['success'] = true;
	return $data;
};

// Returns the bill id of the original registration
function getRegistrationOriginalBill($mysqli, $registrationid) {
	$id = null;
	$query = "SELECT cb.id
			from cpa_bills cb
			JOIN cpa_bills_registrations cbr ON cbr.billid = cb.id
			WHERE cbr.registrationid = '$registrationid' AND (cb.relatednewbillid is null OR cb.relatednewbillid = 0)";
	$result = $mysqli->query($query);
	$data = array();
	while ($row = $result->fetch_assoc()) {
		$id = (int) $row['id'];
		return $id;
	}
	return $id;
}

// Returns the current bill id for the contact for the session
function getRegistrationContactBill($mysqli, $contactid, $sessionid, $showid) {
	$id = null;
	if ($sessionid != 0 && $showid == 0) {
		$query = "SELECT DISTINCT cb.id
				FROM cpa_bills cb
				JOIN cpa_bills_registrations cbr ON cbr.billid = cb.id
				JOIN cpa_registrations cr ON cr.id = cbr.registrationid
				JOIN cpa_members_contacts cmc ON cmc.memberid = cr.memberid
				WHERE  cmc.contactid = $contactid
				AND cr.sessionid = $sessionid
				AND (cb.relatednewbillid is null OR cb.relatednewbillid = 0)
				ORDER BY cb.id DESC";
	} else {
		$query = "SELECT DISTINCT cb.id
				FROM cpa_bills cb
				JOIN cpa_bills_registrations cbr ON cbr.billid = cb.id
				JOIN cpa_registrations cr ON cr.id = cbr.registrationid
				JOIN cpa_members_contacts cmc ON cmc.memberid = cr.memberid
				WHERE  cmc.contactid = $contactid
				AND cr.showid = $showid
				AND (cb.relatednewbillid is null OR cb.relatednewbillid = 0)
				ORDER BY cb.id DESC";
	}
	$result = $mysqli->query($query);
	// $data = array();
	while ($row = $result->fetch_assoc()) {
		$id = (int) $row['id'];
		return $id;
	}
	return $id;
}

/*
	This function copies an existing bill and returns the new bill id
	$relatedoldregistrationid cannot be null. Use -1 if no relatedoldregistrationid. The registration with this id will not be copied in the new bill.
*/
function copyBill($mysqli, $billid, $relatedoldregistrationid, $newbillingdate) {
	$newbillid = null;
	// Copy the bill in the cpa_bills table
	$query = "INSERT INTO cpa_bills(id, billingname, billingdate,  paidinfull, paymentduedate, relatedoldbillid, totalamount, paidamount, haspaymentagreement, paymentagreementnote)
			SELECT null, billingname, '$newbillingdate',  0, paymentduedate, '$billid', totalamount, paidamount, haspaymentagreement, paymentagreementnote
			FROM cpa_bills WHERE id = '$billid' ";
	if ($mysqli->query($query)) {
		// Get the new billid
		$newbillid = (int) $mysqli->insert_id;
		// Relate the old bill to the new bill
		$query = "UPDATE cpa_bills SET relatednewbillid = '$newbillid' WHERE id = '$billid'";
		if ($mysqli->query($query)) {
			// Copy the old bill registrations to the new bill, without the old registration
			$query = "INSERT INTO cpa_bills_registrations(id, billid, registrationid, subtotal)
					SELECT null, '$newbillid', registrationid, subtotal
					FROM cpa_bills_registrations WHERE billid = '$billid' AND registrationid != '$relatedoldregistrationid'";
			if ($mysqli->query($query)) {
				// Copy the old bill details to the new bill, without the old registration
				$query = "INSERT INTO cpa_bills_details(id, billid, registrationid, itemid, itemtype, nonrefundable, amount, comments)
						SELECT null, '$newbillid', registrationid, itemid, itemtype, nonrefundable, amount, comments
						FROM cpa_bills_details where billid = '$billid' AND registrationid != '$relatedoldregistrationid'";
				if ($mysqli->query($query)) {
					// Changes the total amount of the new bill
					$query = "UPDATE cpa_bills SET totalamount = (SELECT COALESCE(sum(subtotal),0) FROM cpa_bills_registrations WHERE billid = '$newbillid')
							WHERE id = '$newbillid'";
					if (!$mysqli->query($query)) {
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
	return $newbillid;
}

function insertBillRegistration($mysqli, $billid, $registration) {
	$data = array();
	$registrationid = $mysqli->real_escape_string(isset($registration['id'])									? $registration['id'] : '');
	$subtotal = $mysqli->real_escape_string(isset($registration['totalamount'])					? $registration['totalamount'] : '0');

	$query = "INSERT into cpa_bills_registrations (id, billid, registrationid, subtotal)
			VALUES (null, '$billid', '$registrationid', '$subtotal')";
	if ($mysqli->query($query)) {
	} else {
		throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
	}
	$data['success'] = true;
	return $data;
}

function insertBillCharges($mysqli, $billid, $registrationid, $charges) {
	$data = array();
	for($x = 0; $x < count($charges); $x++) {
		$chargeid = 			$mysqli->real_escape_string(isset($charges[$x]['id']) 						? $charges[$x]['id'] : '');
		$amount = 				$mysqli->real_escape_string(isset($charges[$x]['amount']) 				? $charges[$x]['amount'] : '');
		$type = 					$mysqli->real_escape_string(isset($charges[$x]['type']) 					? $charges[$x]['type'] : '');
		$nonrefundable =	$mysqli->real_escape_string(isset($charges[$x]['nonrefundable']) 	? $charges[$x]['nonrefundable'] : '');
		$selected = 			$mysqli->real_escape_string(isset($charges[$x]['selected']) 			? $charges[$x]['selected'] : '0');
		$comments = 			$mysqli->real_escape_string(isset($charges[$x]['comments']) 			? $charges[$x]['comments'] : '');

		if ($selected and $selected == '1') {
			$data[$chargeid] = true;
			$query = "INSERT into cpa_bills_details	(id, billid, registrationid, itemid, itemtype, nonrefundable, amount, comments)
					VALUES (null, '$billid', '$registrationid', '$chargeid', '$type', '$nonrefundable', '$amount', '$comments')";
			if (!$mysqli->query($query)) {
				throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
			}
		}
	}
	$data['success'] = true;
	return $data;
}

function insertBillCourses($mysqli, $billid, $registrationid, $courses) {
	$data = array();
	for($x = 0; $x < count($courses); $x++) {
		$courseid 			= $mysqli->real_escape_string(isset($courses[$x]['id']) 						? $courses[$x]['id'] : '');
		$comments 			= $mysqli->real_escape_string(isset($courses[$x]['comments']) 			? $courses[$x]['comments'] : '');
		$feesbilling 		= $mysqli->real_escape_string(isset($courses[$x]['fees_billing']) 	? (float) $courses[$x]['fees_billing'] : '');
		$selected 			= $mysqli->real_escape_string(isset($courses[$x]['selected']) 			? $courses[$x]['selected'] : '0');
		$selected_old 	= $mysqli->real_escape_string(isset($courses[$x]['selected_old']) 	? $courses[$x]['selected_old'] : '0');

		if (($selected and $selected == '1') or ($selected_old and $selected_old == '1') or ($feesbilling and $feesbilling > 0)) {
			$data[$courseid] = true;
			$query = "INSERT into cpa_bills_details	(id, billid, registrationid, itemid, itemtype, amount, comments)
					VALUES (null, '$billid', '$registrationid', '$courseid', 'COURSE', '$feesbilling', '$comments')";
			if (!$mysqli->query($query)) {
				throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
			}
		}
	}
	$data['success'] = true;
	return $data;
}

function insertBillNumbers($mysqli, $billid, $registrationid, $numbers) {
	$data = array();
	for($x = 0; $x < count($numbers); $x++) {
		$numberid 			= $mysqli->real_escape_string(isset($numbers[$x]['id']) 						? $numbers[$x]['id'] : '');
		$comments 			= $mysqli->real_escape_string(isset($numbers[$x]['comments']) 			? $numbers[$x]['comments'] : '');
		$feesbilling 		= $mysqli->real_escape_string(isset($numbers[$x]['fees_billing']) 	? (float) $numbers[$x]['fees_billing'] : '');
		$selected 			= $mysqli->real_escape_string(isset($numbers[$x]['selected']) 			? $numbers[$x]['selected'] : '0');
		$selected_old 	= $mysqli->real_escape_string(isset($numbers[$x]['selected_old']) 	? $numbers[$x]['selected_old'] : '0');

		if (($selected and $selected == '1') or ($selected_old and $selected_old == '1') or ($feesbilling and $feesbilling > 0)) {
			$data[$numberid] = true;
			$query = "INSERT into cpa_bills_details	(id, billid, registrationid, itemid, itemtype, amount, comments)
					VALUES (null, '$billid', '$registrationid', '$numberid', 'NUMBER', '$feesbilling', '$comments')";
			if (!$mysqli->query($query)) {
				throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
			}
		}
	}
	$data['success'] = true;
	return $data;
}

function insertBill($mysqli, $registration) {
	$data = array();
	$charges					= $registration['charges'];
	$member 					= $registration['member'];
	$courses 					= isset($registration['courses'])							? $registration['courses'] : null;
	$numbers 					= isset($registration['shownumbers'])	? $registration['shownumbers'] : null;
	$registrationid		= $mysqli->real_escape_string(isset($registration['id'])									? $registration['id'] : '');
	$billingname 			= $mysqli->real_escape_string(isset($registration['billingname'])					? $registration['billingname'] : '');
	$totalamount 			= $mysqli->real_escape_string(isset($registration['totalamount'])					? $registration['totalamount'] : '0');
	$registrationdate	=	$mysqli->real_escape_string(isset($registration['registrationdatestr'])	? $registration['registrationdatestr'] : '');
	$contactid				=	$mysqli->real_escape_string(isset($registration['contactid'])						? (int)$registration['contactid'] : 0);

	$query = "INSERT INTO cpa_bills (id, billingname, billingdate, lastprintdate, paidinfull, paymentduedate, relatedoldbillid, relatednewbillid, totalamount, contactid)
						VALUES (NULL, '$billingname', '$registrationdate', null, 0, null, null, null, '$totalamount', $contactid)";
	if ($mysqli->query($query)) {
		$billid = (int) $mysqli->insert_id;
		insertBillRegistration($mysqli, $billid, $registration);
		insertBillCharges($mysqli, $billid, $registrationid, $charges);
		if ($courses != null) {
			insertBillCourses($mysqli, $billid, $registrationid, $courses);
		}
		if ($numbers != null) {
			insertBillNumbers($mysqli, $billid, $registrationid, $numbers);
		}
	} else {
		throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
	}
	$data['success'] = true;
	return $data;
}

/*
	This function updates the bill total amount in the db
	lamouree 9/05/2023 added the paidinfull flag to the SQL command to update it when copying registration.
*/
function updateBillTotal($mysqli, $newbillid, $subtotal) {
	$data = array();
	$query = "UPDATE cpa_bills 
			SET totalamount = totalamount + $subtotal,
			paidinfull = if (totalamount + paidamount <= 0, 1, 0)
			WHERE id = '$newbillid' ";
	if ($mysqli->query($query)) {
	} else {
		throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
	}
	$data['newbillid'] = $newbillid;
	$data['success'] = true;
	$data['message'] = 'Bill updated successfully.';
	return $data;
}

// This function completes a registration by updating/inserting the registration, registering/unregistering the member to his list of courses and setting the bill.
// Insertion of the registration should occur only during online registration. For all other kind of registration, only an update should be necessary.
// Parameter
// $billid null 	- create a new bill
// 					=0		- Find the bill id of the contact for the specified session
//				  >0  	- connect to existing bill
//				  -1 		- Connect to existing bill of old registration version. Need to find the billid first.
function acceptRegistration($mysqli, $registration, $billid, $language, $validcount) {
	try{
		$data 						= array();
		$data['success']	= true;
		$memberid 				= $registration['memberid'];
		$contactid 				= $mysqli->real_escape_string(isset($registration['contactid']) ? $registration['contactid'] : 0);
		$sessionid 				= $mysqli->real_escape_string(isset($registration['sessionid']) ? $registration['sessionid'] : 0);
		$showid 					= $mysqli->real_escape_string(isset($registration['showid']) ? $registration['showid'] : 0);
		$relatedoldregistrationid = isset($registration['relatedoldregistrationid']) ? $registration['relatedoldregistrationid'] : null;
		$id 							= $registration['id'];
		
		$data['validcount'] =  $validcount;
		// We need to validate one last time if the courses have enough room left for a new registration
		if ($validcount == 'true') {
			validateSessionCourseMemberCount($mysqli, $registration, $language);
		}
		// Update the registration
		$data['registration'] = updateEntireRegistrationInt($mysqli, $registration, 'ACCEPTED');
		// If registration was inserted, get the new id
		if ($id == null) {
			$registration['id'] = $data['registration']['registration']['id'];
			$id = $registration['id'];
		}
		// If registrations is for courses, then update the member registration list of courses
		if ($mysqli->real_escape_string(isset($registration['courses'])) && count($registration['courses']) > 0) {
			$data['sessioncoursemember'] 	= updateEntireSessionCourseMember($mysqli, $memberid, $registration, $language);
		}
		// If registrations is for show numbers, then update the member registration list of numbers
		if ($mysqli->real_escape_string(isset($registration['shownumbers'])) && count($registration['shownumbers']) > 0) {
			$data['sessionnumbermember'] 	= updateEntireShowNumberMember($mysqli, $memberid, $registration, $language);
		}
		// When coming from the MY SKATING SPACE, revised registration were not copy from the original one, but simply inserted, 
		// so old registration is not connected to the new one. We need to correct that here.
		if ($relatedoldregistrationid != null) {
			$query = "UPDATE cpa_registrations SET relatednewregistrationid = $id where id = $relatedoldregistrationid";
			if (!$mysqli->query($query)) {
				throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
			}
		}
		// Take care of the bill
		$relatedoldregistrationid			= -1;
		$data['$billid'] 							= $billid;
		if ($billid == 0 && $contactid != 0) {
			// Find the current bill id for the contact for the session
			$billid = getRegistrationContactBill($mysqli, $contactid, $sessionid, $showid);
			$data['$billid'] = $billid;
		}
		if ($billid == null) {
			// Create a new bill
			$data['bill'] = insertBill($mysqli, $registration);
		} else {
			$billid = intval($billid);
			if ($billid == -1) {
				// Reconnect to the same bill of old registration version. Need to find the billid first.
				$billid 									= getRegistrationOriginalBill($mysqli, $registration['relatedoldregistrationid']);
				$data['billid'] 					= $billid;
				$relatedoldregistrationid = $registration['relatedoldregistrationid'];
			}

			if ($billid > 0) {
				// Connect to an existing bill
				$newbillid 					= copyBill($mysqli, $billid, $relatedoldregistrationid, $registration['registrationdatestr']);
				$data['newbillid'] 	= $newbillid;
				$charges 						= $registration['charges'];
				$courses						= isset($registration['courses'])			? $registration['courses'] : null;
//				$numbers						= $mysqli->real_escape_string(isset($registration['shownumbers'])	? $registration['shownumbers'] : null);
				$numbers						= isset($registration['shownumbers'])	? $registration['shownumbers'] : null;
				$registrationid			= $mysqli->real_escape_string(isset($registration['id'])					? $registration['id'] : '');
				$subtotal 					= $mysqli->real_escape_string(isset($registration['totalamount'])	? $registration['totalamount'] : '0');

				insertBillRegistration($mysqli, $newbillid, $registration);
				insertBillCharges($mysqli, $newbillid, $registrationid, $charges);
				if ($courses != null) {
					insertBillCourses($mysqli, $newbillid, $registrationid, $courses);
				}
				if ($numbers != null) {
					insertBillNumbers($mysqli, $newbillid, $registrationid, $numbers);
				}
				updateBillTotal($mysqli, $newbillid, $subtotal);
			} else {
				$data['success'] = false;
				$data['message'] = 'Bill was not created';
			}
		}
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


?>
