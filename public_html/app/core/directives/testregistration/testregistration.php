<?php
/*
Author : Eric Lamoureux
*/
require_once('../reports/sendemail.php');
require_once('../core/directives/billing/bills.php');

/**
 * This function gets the member details of a registration for a period from database
 */
function getPeriodRegistrationMember($mysqli, $memberid) {
	$data = array();
	$data['data'] = array();
	$query = "SELECT cm.* FROM cpa_members cm	WHERE id = $memberid";
	$result = $mysqli->query( $query );
	while ($row = $result->fetch_assoc()) {
		$data['data'][] = $row;
	}
	$data['success'] = true;
	return $data;
};

/**
 * This function gets the details of all registrations for a period from database
 */
function getPeriodRegistrations($mysqli, $periodid, $language, $includeDeleted = true) {
	$data = array();
	$query = "SELECT cnspr.*, cm1.firstname skaterfirstname, cm1.lastname skaterlastname, cm2.firstname coachfirstname, cm2.lastname coachlastname,
										cm3.firstname partnerfirstname, cm3.lastname partnerlastname,
										getTextLabel(ct.label, '$language') testlabel, getCodeDescription('startesttypes', ctd.type, '$language') testtypelabel,
										ctd.type testtype, ct.summarycode, cu.fullname createdbyfullname, cbt.billid billid, cb.paidinfull
						FROM cpa_newtests_sessions_periods_registrations cnspr
						JOIN cpa_members cm1 ON cm1.id = cnspr.memberid
						JOIN cpa_members cm2 ON cm2.id = cnspr.coachid
						left JOIN cpa_members cm3 ON cm3.id = cnspr.partnerid
						JOIN cpa_tests ct ON ct.id = cnspr.testid
						JOIN cpa_tests_definitions ctd ON ctd.id = ct.testsdefinitionsid
						left JOIN cpa_users cu ON cu.userid = cnspr.createdby
						left JOIN cpa_bills_testsessions cbt ON cbt.testssessionsid = cnspr.id
						left JOIN cpa_bills cb ON cb.id = cbt.billid and cb.relatednewbillid is null
						WHERE newtestssessionsperiodsid = $periodid";
	$result = $mysqli->query( $query );
	$data['data'] = array();
	while ($row = $result->fetch_assoc()) {
		// Filter out deleted registration if not required
		if ($row['isdeleted'] == '0' || ($row['isdeleted'] == '1' && $includeDeleted == true)) {
			$row['paidinfull'] = (int) $row['paidinfull'];
			$row['member'] = getPeriodRegistrationMember($mysqli, $row['memberid'])['data'][0];
			$data['data'][] = $row;
		}
	}
	$data['success'] = true;
	return $data;
};

/**
 * This function returns the description of one registration from the database
 */
function getOneRegistration($mysqli, $id, $language) {
	$data = array();
	$query = "SELECT cnspr.*, cm1.firstname skaterfirstname, cm1.lastname skaterlastname, cm2.firstname coachfirstname, cm2.lastname coachlastname,
										getTextLabel(ct.label, '$language') testlabel, getCodeDescription('startesttypes', ctd.type, '$language') testtypelabel,
										ctd.type testtype, cnsp.perioddate, cnsp.starttime, cnsp.endtime,
										(select getTextLabel(cai.label, '$language') from cpa_arenas_ices cai where cai.arenaid = cnsp.arenaid and cai.id = cnsp.iceid) icelabel,
										(select getTextLabel(ca.label, '$language') from cpa_arenas ca where ca.id = cnsp.arenaid) arenalabel,
										getCodeDescription('approbationstatus', cnspr.approbationstatus, '$language') approbationstatuslabel, approvedby, approvedon, cu.fullname
						FROM cpa_newtests_sessions_periods_registrations cnspr
						JOIN cpa_newtests_sessions_periods cnsp ON cnsp.id = cnspr.	newtestssessionsperiodsid
						JOIN cpa_members cm1 ON cm1.id = cnspr.memberid
						JOIN cpa_members cm2 ON cm2.id = cnspr.coachid
						JOIN cpa_tests ct ON ct.id = cnspr.testid
						JOIN cpa_tests_definitions ctd ON ctd.id = ct.testsdefinitionsid
						left JOIN cpa_users cu ON cu.userid = cnspr.approvedby
						WHERE cnspr.id = $id";
	$result = $mysqli->query($query);
	$data['data'] = array();
	while ($row = $result->fetch_assoc()) {
		$row['member'] = getPeriodRegistrationMember($mysqli, $row['memberid'])['data'][0];
		$data['data'][] = $row;
	}
	$data['success'] = true;
	return $data;
};

/**
 * This function returns the html code for the body of the email with the registration details
 */
function getOneRegistrationDetails($mysqli, $id, $language) {
	// Get the registration details from the database
	$registration = getOneRegistration($mysqli, $id, $language)['data'][0];
	if ($language == 'en-ca') {
		$body  = "<p><b>Test Date: </b>" . $registration['perioddate'] . ' ' . $registration['starttime'] . '-'. $registration['endtime'] . ' ' . $registration['arenalabel'] . ' ' . $registration['icelabel'] . "</p>";
		$body .= "<p><b>Coach: </b>" . $registration['coachfirstname'] . ' ' . $registration['coachlastname'] . "</p>";
		$body .= "<p><b>Skater: </b>" . $registration['skaterfirstname'] . ' ' . $registration['skaterlastname'] . "</p>";
		$body .= "<p><b>Test: </b>" . $registration['testlabel'] . "</p>";
		$body .= "<p><b>Approbation Status: </b>" . $registration['approbationstatuslabel'] . "</p>";
		if ($mysqli->real_escape_string(isset($registration['approvedby']))) {
			$body .= "<p><b>Approved By: </b>" . $registration['fullname'] . "</p>";
		}
		if ($mysqli->real_escape_string(isset($registration['approvedon']))) {
			$body .= "<p><b>Approved On: </b>" . $registration['approvedon'] . "</p>";
		}
		$body .= "<p> You can see the registration here : %url%/#!/teststarregistrationview</p>";
	} else {
		$body  = "<p><b>Date du test : </b>" . $registration['perioddate'] . ' ' . $registration['starttime'] . '-'. $registration['endtime'] . ' ' . $registration['arenalabel'] . ' ' . $registration['icelabel'] . "</p>";
		$body .= "<p><b>Entraîneur : </b>" . $registration['coachfirstname'] . ' ' . $registration['coachlastname'] . "</p>";
		$body .= "<p><b>Patineur : </b>" . $registration['skaterfirstname'] . ' ' . $registration['skaterlastname'] . "</p>";
		$body .= "<p><b>Test : </b>" . $registration['testlabel'] . "</p>";
		$body .= "<p><b>Approbation : </b>" . $registration['approbationstatuslabel'] . "</p>";
		if ($mysqli->real_escape_string(isset($registration['approvedby']))) {
			$body .= "<p><b>Approuvé par : </b>" . $registration['fullname'] . "</p>";
		}
		if ($mysqli->real_escape_string(isset($registration['approvedon']))) {
			$body .= "<p><b>Approuvé le : </b>" . $registration['approvedon'] . "</p>";
		}
		$body .= "<p> Vous pouvez consulter l'inscription ici : %url%/#!/teststarregistrationview</p>";
	}
	return $body;
}

/**
 * This function gets the test director details for the test session
 */
 function getTestDirectorInfo($mysqli, $newtestssessionsid) {
	// Get the test director email and language
	$query = "SELECT cm.language, cm.email, concat(cm.firstname, ' ', cm.lastname) fullname
						FROM cpa_members cm
						JOIN cpa_newtests_sessions cns ON cns.testdirectorid = cm.id
						WHERE cns.id = $newtestssessionsid";
	$result = $mysqli->query($query);
	$testdirectorinfo = $result->fetch_assoc();
	if ($testdirectorinfo['language'] == 'F') {
		$testdirectorinfo['language'] = 'fr-ca';
	} else {
		$testdirectorinfo['language'] = 'en-ca';
	}
	return $testdirectorinfo;
}

/**
 * This function gets the coach details
 */
 function getTestCoachInfo($mysqli, $coachid) {
	// Get the coach email and language
	$query = "SELECT cm.language, cm.email, concat(cm.firstname, ' ', cm.lastname) fullname
						FROM cpa_members cm
						WHERE cm.id = $coachid";
	$result = $mysqli->query($query);
	$coachinfo = $result->fetch_assoc();
	if ($coachinfo['language'] == 'F') {
		$coachinfo['language'] = 'fr-ca';
	} else {
		$coachinfo['language'] = 'en-ca';
	}
	return $coachinfo;
}

/**
 * This function gets the details of all charges for a testsession from database
 */
function getTestsessionCharges($mysqli, $newtestssessionsid, $language) {
	$query = "SELECT ctsc.*, getTextLabel(cc.label, '$language') chargelabel
						FROM cpa_newtests_sessions_charges ctsc
						JOIN cpa_charges cc ON cc.code = ctsc.chargecode
						WHERE newtestssessionsid = $newtestssessionsid";
	$result = $mysqli->query($query );
	$data = array();
	$data['data'] = array();
	while ($row = $result->fetch_assoc()) {
		$data['data'][] = $row;
	}
	$data['success'] = true;
	return $data;
	exit;
};

function sendEmailToTestDirector($mysqli, $newtestssessionsid, $registrationid, $status) {
	// We need to send an email to the test director
	$testdirectorinfo = getTestDirectorInfo($mysqli, $newtestssessionsid);
	if ($status == 'New') {
		if ($testdirectorinfo['language'] == 'en-ca') {
			$title = "New STAR test registration";
			$body =  "<p>You received this email because a new registration to the STAR test has been made.</p>";
		} else {
			$title = "Nouvelle inscription test STAR";
			$body =  "<p>Vous recevez ce courriel parce qu'une nouvelle inscription aux tests STAR a été faite.</p>";
		}
	} else {
		if ($testdirectorinfo['language'] == 'en-ca') {
			$title = "Modified STAR test registration";
			$body =  "<p>You received this email because a registration to the STAR test has been modified.</p>";
		} else {
			$title = "Inscription test STAR modifiée";
			$body =  "<p>Vous recevez ce courriel parce qu'une inscription aux tests STAR a été modifiée.</p>";
		}
	}
	// Get the registration details
	$body .= getOneRegistrationDetails($mysqli, $registrationid, $testdirectorinfo['language']);
	// Send email
	sendoneemail($mysqli, $testdirectorinfo['email'], $testdirectorinfo['fullname'], $title, $body, '../../images', null, $testdirectorinfo['language']);
}

function sendEmailToCoach($mysqli, $newtestssessionsid, $registrationid, $coachid, $status) {
	// We need to send an email to the test director
	$coachinfo = getTestCoachInfo($mysqli, $coachid);
	if ($status == 'Modified') {
		if ($coachinfo['language'] == 'en-ca') {
			$title = "Modified STAR test registration";
			$body =  "<p>You received this email because a registration to the STAR test has been modified.</p>";
		} else {
			$title = "Inscription test STAR modifiée";
			$body =  "<p>Vous recevez ce courriel parce qu'une inscription aux tests STAR a été modifiée.</p>";
		}
	}
	// Get the registration details
	$body .= getOneRegistrationDetails($mysqli, $registrationid, $coachinfo['language']);
	// Send email
	sendoneemail($mysqli, $coachinfo['email'], $coachinfo['fullname'], $title, $body, '../../images', null, $coachinfo['language']);
}

/**
 * This function will handle insert/update/delete of all registrations in DB
 * Normally, only one registration should have a status (New or Modified) at a time.
 * @throws Exception
 */
function updateEntireRegistrations($mysqli, $registrations, $userid, $perioddate, $charge, $language) {
	$data = array();
	$data['inserted'] = 0;
	$data['updated'] 	= 0;
	$data['deleted'] 	= 0;
	$data['count'] 		= count($registrations);



	for($x = 0; $x < count($registrations); $x++) {
		$id = 												$mysqli->real_escape_string(isset($registrations[$x]['id'])													? (int)$registrations[$x]['id'] : '');
		$newtestssessionsid = 				$mysqli->real_escape_string(isset($registrations[$x]['newtestssessionsid'])					? (int)$registrations[$x]['newtestssessionsid'] : 0);
		$newtestssessionsperiodsid = 	$mysqli->real_escape_string(isset($registrations[$x]['newtestssessionsperiodsid'])	? (int)$registrations[$x]['newtestssessionsperiodsid'] : 0);
		$coachid = 										$mysqli->real_escape_string(isset($registrations[$x]['coachid'])										? (int)$registrations[$x]['coachid'] : 0);
		$memberid = 									$mysqli->real_escape_string(isset($registrations[$x]['member']['id']) 							? (int)$registrations[$x]['member']['id'] : 0);
		$testid = 										$mysqli->real_escape_string(isset($registrations[$x]['testid']) 										? (int)$registrations[$x]['testid'] : 0);
		$partnerid = 									$mysqli->real_escape_string(isset($registrations[$x]['partnerid']) 									? (int)$registrations[$x]['partnerid'] : 0);
		$musicid = 										$mysqli->real_escape_string(isset($registrations[$x]['musicid']) 										? (int)$registrations[$x]['musicid'] : 0);
		$approbationstatus = 					$mysqli->real_escape_string(isset($registrations[$x]['approbationstatus']) 					? (int)$registrations[$x]['approbationstatus'] : 2);
		$billid = 										$mysqli->real_escape_string(isset($registrations[$x]['billid']) 										? (int)$registrations[$x]['billid'] : 0);
		$approvedby = 								$mysqli->real_escape_string(isset($registrations[$x]['approvedby']) 								? $registrations[$x]['approvedby'] : '');
		$approvedon = 								$mysqli->real_escape_string(isset($registrations[$x]['approvedonstr']) 							? $registrations[$x]['approvedonstr'] : '');
		$result = 										$mysqli->real_escape_string(isset($registrations[$x]['result']) 										? $registrations[$x]['result'] : '');

		// If we couldn't get the charge, go get it now.
		if ($charge == null) {
			$charge = getTestsessionCharges($mysqli, $newtestssessionsid, $language)['data'][0]['amount'];
		}

		if ($mysqli->real_escape_string(isset($registrations[$x]['status'])) and $registrations[$x]['status'] == 'New') {
			if ($approbationstatus == 2) { // Approbation pending
				$query = "INSERT INTO cpa_newtests_sessions_periods_registrations(id, newtestssessionsid, newtestssessionsperiodsid, coachid, memberid, testid, partnerid, musicid, createdby, result)
									VALUES (null, $newtestssessionsid, $newtestssessionsperiodsid, $coachid, $memberid, $testid, $partnerid, $musicid, '$userid', '$result')";
			} else {
				$query = "INSERT INTO cpa_newtests_sessions_periods_registrations(id, newtestssessionsid, newtestssessionsperiodsid, coachid, memberid, testid, partnerid, musicid, createdby, result, approbationstatus, approvedby, approvedon)
									VALUES (null, $newtestssessionsid, $newtestssessionsperiodsid, $coachid, $memberid, $testid, $partnerid, $musicid, '$userid', '$result', $approbationstatus, '$approvedby', '$approvedon')";
			}

			if ($mysqli->query($query)) {
				$data['inserted']++;
				// Get the id of the last inserted registration
				if (empty($id)) $id = (int)$mysqli->insert_id;
				$registrations[$x]['id'] = $id;
				// Registration is inserted with an Approved status, insert an unpaid bill
				if ($approbationstatus == 1) {
					$billid = createSingleTestUnpaidBill($mysqli, $registrations[$x], $charge, $language)['billid'];
					$registrations[$x]['billid'] = $billid;
					$query = "UPDATE cpa_newtests_sessions_periods_registrations SET billid = $billid WHERE id = $id";
					if (!$mysqli->query($query)) {
						throw new Exception($mysqli->sqlstate.' - '. $mysqli->error.' - '.$query);
					}
				}
				// We need to send an email to the test director
				sendEmailToTestDirector($mysqli, $newtestssessionsid, $id, $registrations[$x]['status']);
			} else {
				throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
			}
		}

		if ($mysqli->real_escape_string(isset($registrations[$x]['status'])) and $registrations[$x]['status'] == 'Modified') {
			if ($approbationstatus == 2) { // Approbation pending
				$query = "update cpa_newtests_sessions_periods_registrations
									set coachid = $coachid,
											memberid = $memberid,
											testid = $testid,
											partnerid = $partnerid,
											musicid = $musicid,
											approbationstatus = $approbationstatus,
											approvedon = null,
											approvedby = null,
											result = '$result'
									where id = $id";
			} else {
				$query = "update cpa_newtests_sessions_periods_registrations
									set coachid = $coachid,
											memberid = $memberid,
											testid = $testid,
											partnerid = $partnerid,
											musicid = $musicid,
											approbationstatus = $approbationstatus,
											approvedon = '$approvedon',
											approvedby = '$approvedby',
											result = '$result'
									where id = $id";
			}
			if ($mysqli->query($query)) {
				$data['updated']++;
				// Registration is modified with an Approved status, insert an unpaid bill if no bill exists
				if ($billid == 0) {
					if ($approbationstatus == 1) {
						$billid = createSingleTestUnpaidBill($mysqli, $registrations[$x], $charge, $language)['billid'];
						$registrations[$x]['billid'] = $billid;
						$query = "UPDATE cpa_newtests_sessions_periods_registrations SET billid = $billid WHERE id = $id";
						if (!$mysqli->query($query)) {
							throw new Exception($mysqli->sqlstate.' - '. $mysqli->error.' - '.$query);
						}
					}
				} else {
					// If bill exists, modify it in case the test has changed
					$query = "UPDATE cpa_bills_details SET itemid = $testid WHERE billid = $billid";
					if (!$mysqli->query($query)) {
						throw new Exception($mysqli->sqlstate.' - '. $mysqli->error.' - '.$query);
					}
				}
				if ($approbationstatus == 2) { // Approbation pending
					// We need to send an email to the test director, registration has been modified
					sendEmailToTestDirector($mysqli, $newtestssessionsid, $id, $registrations[$x]['status']);
				} else {	// Approved or refused
					// We need to send an email to the coach
					sendEmailToCoach($mysqli, $newtestssessionsid, $id, $coachid, $registrations[$x]['status']);
				}
			} else {
				throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
			}
		}

		// Special status for the result. We don't want to send email for this modification.
		if ($mysqli->real_escape_string(isset($registrations[$x]['status2'])) and $registrations[$x]['status2'] == 'ResultModified') {
			$query = "update cpa_newtests_sessions_periods_registrations
								set result = '$result'
								where id = $id";
			if (!$mysqli->query($query)) {
				throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
			}
		}

		if ($mysqli->real_escape_string(isset($registrations[$x]['status'])) and $registrations[$x]['status'] == 'Deleted') {
			// TODO : we need to check the bill status.
			// If bill is present,
			// 			we cannot delete the bill or the registration
			// 			we must set the registration to "deleted" (new concept)
			// 			we must reset the result to "Not evaluated"
			// 			if bill is paid
			// 					we need to change the price of the test to 0$.
			// 			If bill is not paid
			// 					we need to change the price of the test to 0$.
			// 					set the bill to "canceled" (new concept)
			// If bill is absent,
			// 			we can delete the registration
			if ($billid && !empty($billid) && $billid != 0) {
				// Bill exists
				$query = "UPDATE cpa_newtests_sessions_periods_registrations SET isdeleted = 1, result = 0 WHERE id = $id";
				if (!$mysqli->query($query)) {
					throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
				}
				$query = "UPDATE cpa_bills_details SET amount = '0.00' WHERE billid = $billid AND itemtype = 'TEST'";
				if (!$mysqli->query($query)) {
					throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
				}
			} else {
				// Bill doesn't exist
				$query = "DELETE FROM cpa_newtests_sessions_periods_registrations WHERE id = $id";
				if ($mysqli->query($query)) {
					$data['deleted']++;
				} else {
					throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
				}
			}
		}

		// We need to manage the result. We can only have one instance of a memberid-testid-testsessionid in the cpa_members_tests table.
		// Start by deleting the test result
		// TODO : what happens if testid was changed ? We need to go get the original testid of the registration?NO, this is impossible....
		$query = "DELETE FROM cpa_members_tests WHERE memberid = $memberid AND testid = $testid AND testssessionsid = $newtestssessionsid AND testdate = '$perioddate'";
		if (!$mysqli->query($query)) {
			throw new Exception($memberid . ' - ' . $testid . ' - ' . $newtestssessionsid. ' - ' . $mysqli->sqlstate.' - '. $mysqli->error );
		}

		// If registration is not deleted and result is "Pass", "Pass with Honnor" or "Retry", save test result in cpa_members_tests
		if (($mysqli->real_escape_string(isset($registrations[$x]['status'])) and $registrations[$x]['status'] != 'Deleted') ||
				($mysqli->real_escape_string(isset($registrations[$x]['status2'])) and $registrations[$x]['status2'] == 'ResultModified')) {
			if ($result == 1 || $result == 2 || $result == 5) {
				$query = "INSERT INTO cpa_members_tests(id, memberid, testid, testssessionsid, testdate, success)
									VALUES (null, $memberid, $testid, $newtestssessionsid, '$perioddate', $result)";
				if (!$mysqli->query($query)) {
					throw new Exception($mysqli->sqlstate.' - '. $mysqli->error );
				}
			}
		}
	}
	$data['success'] = true;
	return $data;
};

?>
