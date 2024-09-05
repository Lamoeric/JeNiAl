 <?php
/*
Author : Eric Lamoureux
*/
require_once(__DIR__.'/../../../../backend/getactivesession.php');
/**
 * This function gets the tests for one test session of one bill from database
 * This function is NOT USED for a registration bill.
 */
function getBillTestsessionTests($mysqli, $billid, $testssessionsid, $language) {
	$data = array();
	$data['data'] = array();
	$query = "select ct.name, getTextLabel(ct.label, '$language') testlabel, cbd.amount, cbd.nonrefundable, cbd.comments
						from cpa_tests ct
						JOIN cpa_bills_details cbd ON cbd.itemid = ct.id AND cbd.itemtype = 'TEST'
						WHERE cbd.billid = '$billid' and cbd.registrationid = '$testssessionsid'";
	$result = $mysqli->query($query);
	while ($row = $result->fetch_assoc()) {
		$data['data'][] = $row;
	}
	$data['success'] = true;
	return $data;
};

/**
 * This function gets the courses for one registration of one bill from database
 * This function IS NOT USED for the test session.
 */
function getBillRegistrationsCourses($mysqli, $billid, $registrationid, $language) {
	$data = array();
	$data['data'] = array();
	$query = "select csc.sessionid, csc.name, getTextLabel(cc.label, '$language') courselabel, getTextLabel(ccl.label, '$language') courselevellabel, cbd.amount, cbd.nonrefundable, getTextLabel(cs.label, '$language'), cbd.comments
						from cpa_sessions_courses csc
						JOIN cpa_bills_details cbd ON cbd.itemid = csc.id AND cbd.itemtype = 'COURSE'
						JOIN cpa_courses cc ON cc.code = csc.coursecode
						left join cpa_courses_levels ccl ON ccl.coursecode = cc.code and ccl.code = csc.courselevel
						join cpa_sessions cs ON cs.id = csc.sessionid
						WHERE cbd.billid = '$billid' and cbd.registrationid = '$registrationid'";
	$result = $mysqli->query($query);
	while ($row = $result->fetch_assoc()) {
		$data['data'][] = $row;
	}
	$data['success'] = true;
	return $data;
};

/**
 * This function gets the numbers for one registration of one bill from database
 * This function IS NOT USED for the test session.
 */
function getBillRegistrationsShowNumbers($mysqli, $billid, $registrationid, $language) {
	$data = array();
	$data['data'] = array();
	$query = "select csn.showid, csn.name, getTextLabel(csn.label, '$language') numberlabel, cbd.amount, cbd.nonrefundable, getTextLabel(cs.label, '$language'), cbd.comments
						from cpa_shows_numbers csn
						JOIN cpa_bills_details cbd ON cbd.itemid = csn.id AND cbd.itemtype = 'NUMBER'
						join cpa_shows cs ON cs.id = csn.showid
						WHERE cbd.billid = $billid and cbd.registrationid = $registrationid";
	$result = $mysqli->query($query);
	while ($row = $result->fetch_assoc()) {
		$data['data'][] = $row;
	}
	$data['success'] = true;
	return $data;
};

/**
 * This function gets the charges for one registration of one bill from database
 * This function also works for the test session, in this case, $registrationid is in fact the testssessionsid
 */
function getBillRegistrationsCharges($mysqli, $billid, $registrationid, $language) {
	$data = array();
	$data['data'] = array();
	$query = "select cc.code, getTextLabel(cc.label, '$language') chargelabel, cbd.amount, cbd.nonrefundable,
						getCodeDescription('chargerefundabletypes', cbd.nonrefundable, '$language') nonrefundablelabel, cbd.comments
						from cpa_sessions_charges csc
						JOIN cpa_charges cc ON cc.code = csc.chargecode
						JOIN cpa_bills_details cbd ON cbd.itemid = csc.id AND cbd.itemtype = 'CHARGE'
						WHERE cbd.billid = '$billid' and cbd.registrationid = '$registrationid'";
	$result = $mysqli->query($query);
	while ($row = $result->fetch_assoc()) {
		$data['data'][] = $row;
	}
	$data['success'] = true;
	return $data;
};

/**
 * This function gets the charges for one registration of one bill from database
 * This function works for the show only
 */
function getBillRegistrationsShowCharges($mysqli, $billid, $registrationid, $language) {
	$data = array();
	$data['data'] = array();
	$query = "select cc.code, getTextLabel(cc.label, '$language') chargelabel, cbd.amount, cbd.nonrefundable,
						getCodeDescription('chargerefundabletypes', cbd.nonrefundable, '$language') nonrefundablelabel, cbd.comments
						from cpa_shows_charges csc
						JOIN cpa_charges cc ON cc.code = csc.chargecode
						JOIN cpa_bills_details cbd ON cbd.itemid = csc.id AND cbd.itemtype = 'CHARGE'
						WHERE cbd.billid = '$billid' and cbd.registrationid = '$registrationid'";
	$result = $mysqli->query($query);
	while ($row = $result->fetch_assoc()) {
		$data['data'][] = $row;
	}
	$data['success'] = true;
	return $data;
};

/**
 * This function gets the charges for one registration of one bill from database
 * This function also works for the test session, in this case, $registrationid is in fact the testssessionsid
 */
function getBillRegistrationsDiscounts($mysqli, $billid, $registrationid, $language) {
	$data = array();
	$data['data'] = array();
	$query = "select cc.code, getTextLabel(cc.label, '$language') chargelabel, cbd.amount, cbd.nonrefundable,
						getCodeDescription('chargerefundabletypes', cbd.nonrefundable, '$language') nonrefundablelabel, cbd.comments
						from cpa_sessions_charges csc
						JOIN cpa_charges cc ON cc.code = csc.chargecode
						JOIN cpa_bills_details cbd ON cbd.itemid = csc.id AND cbd.itemtype = 'DISCOUNT'
						WHERE cbd.billid = '$billid' and cbd.registrationid = '$registrationid'";
	$result = $mysqli->query($query);
	while ($row = $result->fetch_assoc()) {
		$data['data'][] = $row;
	}
	$data['success'] = true;
	return $data;
};

/**
 * This function gets the charges for one registration of one bill from database
 * This function works for the show
 */
function getBillRegistrationsShowDiscounts($mysqli, $billid, $registrationid, $language) {
	$data = array();
	$data['data'] = array();
	$query = "select cc.code, getTextLabel(cc.label, '$language') chargelabel, cbd.amount, cbd.nonrefundable,
						getCodeDescription('chargerefundabletypes', cbd.nonrefundable, '$language') nonrefundablelabel, cbd.comments
						from cpa_shows_charges csc
						JOIN cpa_charges cc ON cc.code = csc.chargecode
						JOIN cpa_bills_details cbd ON cbd.itemid = csc.id AND cbd.itemtype = 'DISCOUNT'
						WHERE cbd.billid = '$billid' and cbd.registrationid = '$registrationid'";
	$result = $mysqli->query($query);
	while ($row = $result->fetch_assoc()) {
		$data['data'][] = $row;
	}
	$data['success'] = true;
	return $data;
};

/**
 * This function gets the member for one registration of one bill from database
 */
function getBillRegistrationsMember($mysqli, $billid, $registrationid, $language) {
	$data = array();
	$data['data'] = array();
	$query = "select cm.firstname, cm.lastname, cm.skatecanadano
						from cpa_members cm
						JOIN cpa_registrations cr ON cm.id = cr.memberid
						JOIN cpa_bills_registrations cbr ON  cr.id  = cbr.registrationid
						WHERE cbr.billid = '$billid' and cbr.registrationid = '$registrationid'";
	$result = $mysqli->query($query);
	while ($row = $result->fetch_assoc()) {
		$data['data'][] = $row;
	}
	$data['success'] = true;
	return $data;
};

/**
 * This function gets the member for one test session of one bill from database
 * Depending on the test session type (1 - test session with judge, 2 = new STAR 1 - 5 test session)
 * the member's information are not accessed the same way.
 */
function getBillTestsessionsMember($mysqli, $billid, $testssessionsid, $testsessiontype, $language) {
	$data = array();
	$data['data'] = array();
	$joincmd = "JOIN cpa_registrations cr ON cm.id = cr.memberid ";
	if ($testsessiontype == 1) { // Old star test session ******** NOT TESTED *************
		$query = "select cm.firstname, cm.lastname, cm.skatecanadano
							from cpa_members cm
							JOIN cpa_bills_testsessions cbt ON cbt.testssessionsid = ctsr.id
							JOIN cpa_tests_sessions_registrations ctsr ON cnspr.memberid = cm.memberid
							WHERE cbr.billid = $billid and cbt.testssessionsid = $testssessionsid";
	} else if ($testsessiontype == 2) { // New star 1 - 5 test session
		$query = "select cm.firstname, cm.lastname, cm.skatecanadano
							from cpa_members cm
							JOIN cpa_newtests_sessions_periods_registrations cnspr ON cnspr.memberid = cm.id
							JOIN cpa_bills_testsessions cbt ON cbt.testssessionsid = cnspr.id
							WHERE cbt.billid = $billid and cbt.testssessionsid = $testssessionsid";
	}
	$result = $mysqli->query($query);
	while ($row = $result->fetch_assoc()) {
		$data['data'][] = $row;
	}
	$data['success'] = true;
  if (count($data['data']) != 1) {
    throw new Exception("Erreur, session de test de la facture non trouvée / error, bills's test session not found");
  }
	return $data;
};

/**
 * This function gets all the registrations (and the children) of one bill from database
 * Children include member, courses, numbers, charges and discounts
 */
function getBillRegistrations($mysqli, $billid, $language) {
	$data = array();
	$data['data'] = array();
	$query = "SELECT cbr.*, cr.showid, cr.sessionid 
						FROM cpa_bills_registrations cbr
						JOIN cpa_registrations cr ON cr.id = cbr.registrationid
						WHERE billid = '$billid'";
	$result = $mysqli->query($query);
	while ($row = $result->fetch_assoc()) {
		$registrationid = (int) $row['registrationid'];
		$showid = (int)$row['showid'];
		$sessionid = (int)$row['sessionid'];
		if ($showid != null) {
			$row['member']      = getBillRegistrationsMember($mysqli, $billid, $registrationid, $language)['data'][0];
			$row['shownumbers'] = getBillRegistrationsShowNumbers($mysqli, $billid, $registrationid, $language)['data'];
			$row['charges']     = getBillRegistrationsShowCharges($mysqli, $billid, $registrationid, $language)['data'];
			$row['discounts']   = getBillRegistrationsShowDiscounts($mysqli, $billid, $registrationid, $language)['data'];
		} else {
			$row['member']    = getBillRegistrationsMember($mysqli, $billid, $registrationid, $language)['data'][0];
			$row['courses']   = getBillRegistrationsCourses($mysqli, $billid, $registrationid, $language)['data'];
			$row['charges']   = getBillRegistrationsCharges($mysqli, $billid, $registrationid, $language)['data'];
			$row['discounts'] = getBillRegistrationsDiscounts($mysqli, $billid, $registrationid, $language)['data'];
		}
		$data['data'][] = $row;
	}
	$data['success'] = true;
	return $data;
};

/**
 * This function gets all the test sessions (and the children) of one bill from database
 * Children include member, tests, charges and discounts
 */
function getBillTestsessions($mysqli, $billid, $language) {
	$data = array();
	$data['data'] = array();
	$query = "SELECT * FROM cpa_bills_testsessions WHERE billid = $billid";
	$result = $mysqli->query($query);
	while ($row = $result->fetch_assoc()) {
		$testssessionsid = (int) $row['testssessionsid'];
		$row['member']    = getBillTestsessionsMember($mysqli, $billid, $testssessionsid, $row['testsessiontype'], $language)['data'][0];
		$row['tests']   	= getBillTestsessionTests($mysqli, $billid, $testssessionsid, $language)['data'];
		$row['charges']   = getBillRegistrationsCharges($mysqli, $billid, $testssessionsid, $language)['data'];
		$row['discounts'] = getBillRegistrationsDiscounts($mysqli, $billid, $testssessionsid, $language)['data'];
		$data['data'][] = $row;
	}
	$data['success'] = true;
	return $data;
};

/**
 * This function gets all the transactions for one bill from database
 * The function finds the transactions for all older versions of the bill.
 */
function getBillTransactions($mysqli, $id, $language) {
	$notover = true;
	$data = array();
	$data['data'] = array();
	$data['billlist'] = "'$id'";
	while ($notover) {
		$query = "SELECT id FROM cpa_bills WHERE relatednewbillid = '$id'";
		$result = $mysqli->query($query);
		$row = $result->fetch_assoc();
		if ($row) {
			$id = $row['id'];
			$data['billlist'] .= ",'$id'";
		} else {
			$notover = false;
		}
	}
	$billlist = $data['billlist'];
	$query = "SELECT id, billid, transactiontype, transactionamount, transactiondate, paymentmethod, getCodeDescription('transactiontypes', transactiontype, '$language') transactiontypelabel, getCodeDescription('paymentmethods', paymentmethod, '$language') paymentmethodlabel, comments, iscanceled, cancelreason, canceledby, canceleddate, getCodeDescription('transactioncancels', cancelreason, '$language') cancelreasonlabel
						FROM cpa_bills_transactions
						WHERE billid in ($billlist)";
	$result = $mysqli->query($query);
	while ($row = $result->fetch_assoc()) {
		$row['iscanceled'] = (int)$row['iscanceled'];
		$data['data'][] = $row;
	}
	$data['success'] = true;
	return $data;
};

/**
* This function gets the bill for the given test session
*/
function getTestsessionBill($mysqli, $testssessionsid, $language) {
 echo json_encode(getTestsessionBillInt($mysqli, $testssessionsid, $language));
};

/**
* This function gets the bill for the given test session
*/
function getTestsessionBillInt($mysqli, $testssessionsid, $language) {
 try {
	 $data = array();
	 $data['data'] = array();
	 $query = "SELECT cb.*
						 FROM cpa_bills cb
						 JOIN cpa_bills_testsessions cbt ON cbt.billid = cb.id
						 WHERE testssessionsid = $testssessionsid and cb.relatednewbillid is null";
	 $result = $mysqli->query($query);
	 while ($row = $result->fetch_assoc()) {
		 $id = (int) $row['id'];
		 $row['registrations'] = getBillTestsessions($mysqli, $id, $language)['data'];
		 $row['transactions']  = getBillTransactions($mysqli, $id, $language)['data'];
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
 * This function gets the bill for the given registration
 */
function getRegistrationBill($mysqli, $registrationid, $language) {
	echo json_encode(getRegistrationBillInt($mysqli, $registrationid, $language));
};

/**
 * This function gets the bill for the given registration
 */
function getRegistrationBillInt($mysqli, $registrationid, $language) {
	try {
		$data = array();
		$data['data'] = array();
		$query = "SELECT cb.*, getCodeDescription('yesno', cb.haspaymentagreement, '$language') haspaymentagreementstr
							FROM cpa_bills cb
							JOIN cpa_bills_registrations cbr ON cbr.billid = cb.id
							WHERE registrationid = $registrationid and cb.relatednewbillid is null";
		$result = $mysqli->query($query);
		while ($row = $result->fetch_assoc()) {
			$id = (int) $row['id'];
			$row['registrations'] = getBillRegistrations($mysqli, $id, $language)['data'];
			$row['transactions']  = getBillTransactions($mysqli, $id, $language)['data'];
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
 * This function gets the bill for the current member
 * This function returns all bills, for registrations and test sessions
 */
function getMemberBill($mysqli, $memberid, $language) {
	echo json_encode(getMemberBillInt($mysqli, $memberid, $language));
};

/**
 * This function gets the bill for the current member
 * This function returns all bills, for registrations and test sessions
 */
function getMemberBillInt($mysqli, $memberid, $language) {
	try {
		$data = array();
		$data['data'] = array();
		$query = "SELECT cb.*
							FROM cpa_bills cb
							JOIN cpa_bills_registrations cbr ON cbr.billid = cb.id
							JOIN cpa_registrations cr ON cr.id = cbr.registrationid
							WHERE cr.memberid = $memberid
							AND cb.relatednewbillid is null
							UNION
							SELECT cb.*
							FROM cpa_bills cb
							JOIN cpa_bills_testsessions cbt ON cbt.billid = cb.id
							JOIN cpa_newtests_sessions_periods_registrations cnspr ON cnspr.id = cbt.testssessionsid
							WHERE cnspr.memberid = $memberid
							AND cb.relatednewbillid is null
							ORDER BY billingdate DESC";
		$result = $mysqli->query($query);
		while ($row = $result->fetch_assoc()) {
			$id = (int) $row['id'];
			// First, try a registration bill
			$row['registrations'] = getBillRegistrations($mysqli, $id, $language)['data'];
			if (count($row['registrations']) == 0) {
				// If no result, check for testsession
				$row['registrations'] = getBillTestsessions($mysqli, $id, $language)['data'];
			}
			$row['transactions']  = getBillTransactions($mysqli, $id, $language)['data'];
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
 * This function gets the possible names for the bill. Name of member + names of all contacts.
 */
function getBillingNames($mysqli, $memberid, $language) {
	try {
		$query = "SELECT concat(firstname, ' ', lastname) fullname
							FROM cpa_members
							WHERE id = $memberid
							UNION
							SELECT concat(firstname, ' ', lastname) fullname
							FROM cpa_contacts
							WHERE id in (SELECT contactid FROM cpa_members_contacts Where memberid = $memberid)";
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
		echo json_encode($data);
		exit;
	}
};

/**
 * This function gets the possible emails for the bill. Emails of members + emails of all contacts.
 * Order by is done so contacts email are first in list, because bill are rarely sent to skater themselves
 */
function getBillingEmails($mysqli, $billid, $language) {
	try {
		// First, check using registration
		// Order by is done so contacts email are first in list
		$query = "SELECT concat(cm.firstname, 	' ', cm.lastname) fullname, cm.email, concat(cm.firstname, 	' ', cm.lastname, ' (', cm.email, ')') label, 2 emailtype
							FROM cpa_bills_registrations cbr
							JOIN cpa_registrations cr ON cr.id = cbr.registrationid
							JOIN cpa_members cm ON cm.id = cr.memberid
							WHERE cbr.billid = $billid
							UNION
							SELECT concat(cc.firstname, ' ', cc.lastname) fullname, cc.email, concat(cc.firstname, 	' ', cc.lastname, ' (', cc.email, ')') label, 1 emailtype
							FROM cpa_contacts cc
							WHERE id in (SELECT contactid FROM cpa_members_contacts WHERE memberid IN (SELECT cm.id
							                                                                           FROM cpa_bills_registrations cbr
							                                                                           JOIN cpa_registrations cr ON cr.id = cbr.registrationid
							                                                                           JOIN cpa_members cm ON cm.id = cr.memberid
							                                                                           WHERE cbr.billid = $billid))
							order by 4, 1;";
		$result = $mysqli->query($query);
		$data = array();
		while ($row = $result->fetch_assoc()) {
			$data['data'][] = $row;
		}
		if (!isset($data['data'])) {
			// If no result, check for testsession
			$query = "SELECT concat(cm.firstname, 	' ', cm.lastname) fullname, cm.email, concat(cm.firstname, 	' ', cm.lastname, ' (', cm.email, ')') label, 2 emailtype
								FROM cpa_bills_testsessions cbt
								JOIN cpa_newtests_sessions_periods_registrations cr ON cr.id = cbt.testssessionsid
								JOIN cpa_members cm ON cm.id = cr.memberid
								WHERE cbt.billid = $billid
								UNION
								SELECT concat(cc.firstname, ' ', cc.lastname) fullname, cc.email, concat(cc.firstname, 	' ', cc.lastname, ' (', cc.email, ')') label, 1 emailtype
								FROM cpa_contacts cc
								WHERE id in (SELECT contactid FROM cpa_members_contacts WHERE memberid IN (SELECT cm.id
								                                                                           FROM cpa_bills_testsessions cbt
								                                                                           JOIN cpa_newtests_sessions_periods_registrations cr ON cr.id = cbt.testssessionsid
								                                                                           JOIN cpa_members cm ON cm.id = cr.memberid
								                                                                           WHERE cbt.billid = $billid))
								order by 4, 1;";
			$result = $mysqli->query($query);
			while ($row = $result->fetch_assoc()) {
				$data['data'][] = $row;
			}
		}
		$data['success'] = true;
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
 * This function gets the bill from the billid
 * Structure is always the same even if bill is for a registration or a test session or a show.
 */
function getBill($mysqli, $billid, $language) {
		echo json_encode(getBillInt($mysqli, $billid, $language));
};

/**
 * This function gets the bill from the billid
 * Structure is always the same even if bill is for a registration or a test session or a show
 * Internal version
 */
function getBillInt($mysqli, $billid, $language) {
	try {
		// Validate that the bill really exists
		$query = "SELECT *, getCodeDescription('yesno', haspaymentagreement, '$language') haspaymentagreementstr FROM cpa_bills WHERE id = $billid";
		$result = $mysqli->query($query);
		$data = array();
		while ($row = $result->fetch_assoc()) {
			$id = (int) $row['id'];
			$row['paidinfull'] = (int) $row['paidinfull'];
			// First, try a registration bill
			$row['registrations'] = getBillRegistrations($mysqli, $id, $language)['data'];
			if (count($row['registrations']) == 0) {
				// If no result, check for testsession
				$row['registrations'] = getBillTestsessions($mysqli, $id, $language)['data'];
			}
			// Transactions are the same for both bill types
			$row['transactions']  = getBillTransactions($mysqli, $id, $language)['data'];
			// Get session info
			$row['session'] = getActiveSession($mysqli)['data'][0];
	
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
 * This function updates a bill paidamount by adding the $amount.
 * If totalamount + paidamount <= 0, set paidinfull to true (1)
 * lamouree 9/05/2023 changed the condition for the paidinfull from == 0 to <=0
 */
function updateBillPaidAmountInt($mysqli, $billid, $amount) {
	$query = "UPDATE cpa_bills
						SET paidamount = paidamount + $amount,
						paidinfull = if (totalamount + paidamount <= 0, 1, 0)
						WHERE id = '$billid'";
	if ($mysqli->query($query)) {
	} else {
		throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
	}
	$data['success'] = true;
	return $data;
};

/**
 * This function updates a bill paidamount by adding the $amount.
 * If totalamount + paidamount <= 0, set paidinfull to true (1)
 */
function updateBillPaidAmount($mysqli, $billid, $amount) {
	try {
		$data = updateBillPaidAmountInt($mysqli, $billid, $amount);
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
 * This function gets list of all bills for a session from database
 * This list does not include bills created for test sessions.
 */
function getAllBills($mysqli, $sessionid, $showid) {
	try {
		if (!empty($sessionid) && $sessionid != null && $sessionid != 0) {
			$query = "SELECT distinct cb.id, cb.billingname, cb.billingdate, cb.totalamount, cb.paidamount
								FROM cpa_bills cb
								JOIN cpa_bills_registrations cbr ON cbr.billid = cb.id
								JOIN cpa_registrations cr ON cr.id = cbr.registrationid
								WHERE relatednewbillid is null
								AND cr.sessionid = $sessionid
								ORDER BY billingname";
		} else {
			$query = "SELECT distinct cb.id, cb.billingname, cb.billingdate, cb.totalamount, cb.paidamount
								FROM cpa_bills cb
								JOIN cpa_bills_registrations cbr ON cbr.billid = cb.id
								JOIN cpa_registrations cr ON cr.id = cbr.registrationid
								WHERE relatednewbillid is null
								AND cr.showid = $showid
								ORDER BY billingname";
		}
		$result = $mysqli->query($query);
		$data = array();
		$data['data'] = array();
		while ($row = $result->fetch_assoc()) {
			$row['id'] = (int) $row['id'];
			$data['data'][] = $row;
		}
		$data['success'] = true;
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
 * This function gets the details of one bill, using the bill id, from database
 */
function getBillDetails($mysqli, $id = '') {
	try {
		if (empty($id)) throw new Exception("Invalid Bill.");
		$query = "SELECT * FROM cpa_bills WHERE id = $id";
		$result = $mysqli->query($query);
		$data = array();
		while ($row = $result->fetch_assoc()) {
			$row['id'] = (int) $row['id'];
			$data['data'][] = $row;
		}
		$data['success'] = true;
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
 * This function inserts the header of the bill, i.e. the record in the table cpa_bills
 * This function returns the bill id
 */
function insertBillHeader($mysqli, $billingname, $billingdate, $totalamount, $paidamount, $paidinfull, $contactid, $memberid) {
	$query = "INSERT INTO cpa_bills (id, billingname, billingdate, lastprintdate, paidinfull, paymentduedate, relatedoldbillid, relatednewbillid, totalamount, paidamount, contactid)
						VALUES (NULL, '$billingname', '$billingdate', null, $paidinfull, null, null, null, '$totalamount', '$paidamount', $contactid)";
	if ($mysqli->query($query)) {
		$billid = (int) $mysqli->insert_id;
	} else {
		throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
	}
	return $billid;
}

/**
 * This function creates a single detail entry for a bill
 */
function insertBillDetails($mysqli, $billid, $registrationid, $itemid, $itemtype, $nonrefundable, $amount, $comments) {
	$query = "INSERT into cpa_bills_details	(id, billid, registrationid, itemid, itemtype, nonrefundable, amount, comments)
						VALUES (null, $billid, $registrationid, '$itemid', '$itemtype', '$nonrefundable', '$amount', '$comments')";
	if (!$mysqli->query($query)) {
		throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
	}
	return true;
}

/**
 * This function creates a single test session entry for a bill
 */
function insertBillTestSession($mysqli, $billid, $testssessionsid, $subtotal, $testsessiontype) {
	$query = "INSERT INTO cpa_bills_testsessions (id, billid, testssessionsid, subtotal, testsessiontype)
						VALUES (null, $billid, $testssessionsid, '$subtotal', $testsessiontype)";
	if (!$mysqli->query($query)) {
		throw new Exception($mysqli->sqlstate.' - '. $mysqli->error.' '.$query);
	}
	return true;
}

/**
 * This function creates a single transaction for a bill
 * This function DOES NOT ALTER the bill paidamount
 */
function insertSingleTransactionInt($mysqli, $billid, $transactiontype, $transactionamount, $transactiondate, $paymentmethod, $checkno, $receiptno, $paperreceiptno, $receivedby, $comments) {
	$query = "INSERT INTO cpa_bills_transactions (id, billid, transactiontype, transactionamount, transactiondate, paymentmethod, checkno, receiptno, paperreceiptno, receivedby, comments)
						VALUES (NULL, $billid, '$transactiontype', '$transactionamount', '$transactiondate', '$paymentmethod', $checkno, $receiptno, $paperreceiptno, '$receivedby', '$comments')";
	if (!$mysqli->query($query)) {
    throw new Exception($mysqli->sqlstate.' - '. $mysqli->error.' '.$query);
	}
	return true;
};


/**
 * This function creates a single transaction for a bill
 * This function DOES NOT ALTER the bill paidamount
 */
function insertSingleTransaction($mysqli, $bill) {
  $data = array();
  $data['success'] = false;
  $billid = 			        $mysqli->real_escape_string(isset($bill['billid'])			        ? $bill['billid'] : '');
  $transactiontype = 			$mysqli->real_escape_string(isset($bill['transactiontype'])			? $bill['transactiontype'] : '');
  $transactionamount = 		$mysqli->real_escape_string(isset($bill['transactionamount'])		? $bill['transactionamount'] : '');
	$paymentmethod = 				$mysqli->real_escape_string(isset($bill['paymentmethod'])				? $bill['paymentmethod'] : '');
	$receivedby = 					$mysqli->real_escape_string(isset($bill['receivedby'])					? $bill['receivedby'] : '');
  $billingdate = date('Y/m/d');
  try {
    if (insertSingleTransactionInt($mysqli, $billid, $transactiontype, $transactionamount, $billingdate, $paymentmethod, /*$checkno*/ 0, /*$receiptno*/ 0, /*$paperreceiptno*/ 0, $receivedby, /*$comments*/ '')) {
      if (updateBillPaidAmount($mysqli, $billid, $transactionamount*-1)) {
        $data['success'] = true;
      }
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
}


/**
 * This function creates a bill for a single test.
 * The bill is created on the current date of the server.
 * This function also creates the transaction to pay the bill.
 * The bill is considered paid in full when it is created.
 * Returns the bill id in $data['billid'] if successful
 */
function createSingleTestBill($mysqli, $testregistration, $language) {
	$data = array();
	$memberid = 						$mysqli->real_escape_string(isset($testregistration['memberid']) 						? $testregistration['memberid'] : '');
	$testssessionsid = 			$mysqli->real_escape_string(isset($testregistration['refid']) 							? $testregistration['refid'] : '');
	$memberfullname = 			$mysqli->real_escape_string(isset($testregistration['memberfullname']) 			? $testregistration['memberfullname'] : '');
	$totalamount = 					$mysqli->real_escape_string(isset($testregistration['price']) 							? $testregistration['price'] : '');
	$itemid = 							$mysqli->real_escape_string(isset($testregistration['itemid']) 							? $testregistration['itemid'] : '');
	$itemtype = 						$mysqli->real_escape_string(isset($testregistration['itemtype']) 						? $testregistration['itemtype'] : '');
	$transactiontype = 			$mysqli->real_escape_string(isset($testregistration['transactiontype'])			? $testregistration['transactiontype'] : '');
	$paymentmethod = 				$mysqli->real_escape_string(isset($testregistration['paymentmethod'])				? $testregistration['paymentmethod'] : '');
	$receivedby = 					$mysqli->real_escape_string(isset($testregistration['receivedby'])					? $testregistration['receivedby'] : '');
	$comments = 						$mysqli->real_escape_string(isset($testregistration['productfullname'])			? $testregistration['productfullname'] : '');

	$billingdate = 					date('Y/m/d');
	try {
		$billid = insertBillHeader($mysqli, $memberfullname, $billingdate, $totalamount, $totalamount*-1, 1, 0, $memberid);
		if (insertBillTestSession($mysqli, $billid, $testssessionsid, $totalamount, 2)) {			//$testsessiontype = 2 for new STAR test session
			if (insertBillDetails($mysqli, $billid, $testssessionsid, $itemid, $itemtype, /*$nonrefundable*/ 0, $totalamount, $comments)) {
				if (insertSingleTransactionInt($mysqli, $billid, $transactiontype, $totalamount, $billingdate, $paymentmethod, /*$checkno*/ 0, /*$receiptno*/ 0, /*$paperreceiptno*/ 0, $receivedby, /*$comments*/ '')) {
					$data['success'] = true;
					$data['billid'] = $billid;
				}
			}
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
}

/**
 * This function creates a bill for a single test.
 * The bill is created on the current date of the server.
 * This function does not create the transaction to pay the bill.
 * Returns the bill id in $data['billid'] if successful
 */
function createSingleTestUnpaidBill($mysqli, $testregistration, $charge, $language) {
	$data = array();
	$memberid = 						$mysqli->real_escape_string(isset($testregistration['memberid']) 						? $testregistration['memberid'] : '');
	$testssessionsid = 			$mysqli->real_escape_string(isset($testregistration['id']) 							    ? $testregistration['id'] : '');
  $memberfirstname = 			$mysqli->real_escape_string(isset($testregistration['skaterfirstname']) 	  ? $testregistration['skaterfirstname'] : '');
	$memberlastname = 			$mysqli->real_escape_string(isset($testregistration['skaterlastname']) 			? $testregistration['skaterlastname'] : '');
	// $totalamount = 					$mysqli->real_escape_string(isset($testregistration['charge']['amount']) 		? $testregistration['charge']['amount'] : '');
	$testid = 							$mysqli->real_escape_string(isset($testregistration['testid']) 							? $testregistration['testid'] : '');
	// $itemtype = 						$mysqli->real_escape_string(isset($testregistration['itemtype']) 						? $testregistration['itemtype'] : '');
	$receivedby = 					$mysqli->real_escape_string(isset($testregistration['receivedby'])					? $testregistration['receivedby'] : '');
	$comments = 						$mysqli->real_escape_string(isset($testregistration['productfullname'])			? $testregistration['productfullname'] : '');
  $memberfullname = 			$memberfirstname.' '.$memberlastname;
	$billingdate = 					date('Y/m/d');

	$billid = insertBillHeader($mysqli, $memberfullname, $billingdate, $charge, 0, 0, 0, $memberid);
	if (insertBillTestSession($mysqli, $billid, $testssessionsid, $charge, 2)) {			//$testsessiontype = 2 for new STAR test session
		if (insertBillDetails($mysqli, $billid, $testssessionsid, $testid, /*$itemtype*/'TEST', /*$nonrefundable*/ 0, $charge, $comments)) {
				$data['success'] = true;
				$data['billid'] = $billid;
			// }
		}
	}
	return $data;
	exit;
}

/*
	This function splits an existing bill and returns the new bill id from the copy (not the split)
  @author                 Eric Lamoureux, 2018/09/14
	$billid									The bill id that needs splitting
	$registrationidtosplit 	The registration with this id will be split in it's own bill and will not be copied in the new bill.
	$newbillingdate					The new date for both bills (the split and the copy) (should be today's date)
*/
function splitBill($mysqli, $billid, $registrationidtosplit) {
	$newbillid = null;
	$newbillingname = null;
	$registrationdate = null;
	// First, find information about the registration to split
	$query = "SELECT cr.*, concat(cm.firstname, ' ', cm.lastname) newbillingname, curdate() newbillingdate
						FROM cpa_registrations cr
						JOIN cpa_members cm ON cm.id = cr.memberid
						where cr.id = $registrationidtosplit";
	$result = $mysqli->query($query);
	$row = $result->fetch_assoc();
	$newbillingname = $row['newbillingname'];
	$registrationdate = $row['registrationdate'];
	$newbillingdate = $row['newbillingdate'];
	// Insert new bill for the split, totalamount will be updated later
	$query = "INSERT INTO cpa_bills (id, billingname, billingdate, lastprintdate, paidinfull, paymentduedate, relatedoldbillid, relatednewbillid, splitfrombillid, totalamount)
						VALUES (NULL, '$newbillingname', '$newbillingdate', null, 0, null, null, null, $billid, 0)";
	if ($mysqli->query($query)) {
		// Get the split billid
		$newbillid = (int) $mysqli->insert_id;
		// Copy the details, registration, etc...
		// Copy the registrations to split to the new bill
		$query = "INSERT INTO cpa_bills_registrations(id, billid, registrationid, subtotal)
							SELECT null, '$newbillid', registrationid, subtotal
							FROM cpa_bills_registrations WHERE billid = $billid AND registrationid = $registrationidtosplit";
		if ($mysqli->query($query)) {
			// Copy the bill's details for the registration
			$query = "INSERT INTO cpa_bills_details(id, billid, registrationid, itemid, itemtype, nonrefundable, amount, comments)
								SELECT null, '$newbillid', registrationid, itemid, itemtype, nonrefundable, amount, comments
								FROM cpa_bills_details where billid = $billid AND registrationid = $registrationidtosplit";
			if ($mysqli->query($query)) {
				// Update the bill's totalamount
				$query = "UPDATE cpa_bills SET totalamount = (SELECT sum(subtotal) FROM cpa_bills_registrations WHERE billid = $newbillid)
									WHERE id = $newbillid";
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
	$newbillid = copyBillInt($mysqli, $billid, $registrationidtosplit, $newbillingdate);
	return $newbillid;
}

/*
	This function copies an existing bill and returns the new bill id
  @author                 Eric Lamoureux, 2018/09/14
	$relatedoldregistrationid cannot be null. Use -1 if no relatedoldregistrationid. The registration with this id will not be copied in the new bill.
*/
function copyBillInt($mysqli, $billid, $relatedoldregistrationid, $newbillingdate) {
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
					$query = "UPDATE cpa_bills 
										SET totalamount = (SELECT sum(subtotal) FROM cpa_bills_registrations WHERE billid = '$newbillid')
										WHERE id = '$newbillid'";
					if ($mysqli->query($query)) {
						updateBillPaidAmountInt($mysqli, $newbillid, 0);
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
	return $newbillid;
}

/**
 * This function updates a bill payment agreement
 */
function updatePaymentAgreement($mysqli, $currentbill) {
	$data = array();
	try {
		$id = 										$mysqli->real_escape_string(isset($currentbill['id']) 										? (int)$currentbill['id'] : '');
		$haspaymentagreement = 		$mysqli->real_escape_string(isset($currentbill['haspaymentagreement']) 		? (int)$currentbill['haspaymentagreement'] : 0);
		$paymentagreementnote = 	$mysqli->real_escape_string(isset($currentbill['paymentagreementnote']) 	? $currentbill['paymentagreementnote'] : '');
		$data['id'] = $id;
		
		$query = "UPDATE cpa_bills
							SET haspaymentagreement = $haspaymentagreement,
							paymentagreementnote = '$paymentagreementnote'
							WHERE id = $id";
		if ($mysqli->query($query)) {
		} else {
			throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
		}
		$data['success'] = true;
		echo json_encode($data);
		exit;
	} catch (Exception $e) {
		$data['success'] = false;
		$data['message'] = $e->getMessage();
		echo json_encode($data);
		exit;
	}
};

?>
