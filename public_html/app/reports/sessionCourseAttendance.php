<?php
//============================================================+
// File name   : sessionCourseAttendance.php
// Begin       : 2016-07-07
// Last Update :
//
// Description : session course attendance
//
// Author: Eric Lamoureux
//
//============================================================+

// Include the main TCPDF library (search for installation path).
require_once('../../include/tcpdf_include.php');
require_once('../../../private/'. $_SERVER['HTTP_HOST'].'/include/config.php');
require_once('../../include/nocache.php');
require_once('customheader.php');
require_once('mypdf_footer.php');

$language = 'fr-ca';
$reportType = 1; // 1 - all, 2 - for a course / number
$eventType = 1; // 1 - Session 2 - Show
$bysubgroup = null;
$sessionscoursesid = null;
$sessionid = null;
$showid = null;
$showsnumbersid = null;
if (isset($_GET['language']) && !empty(isset($_GET['language']))) {
	$language = $_GET['language'];
}
if (isset($_GET['sessionid']) && !empty(isset($_GET['sessionid']))) {
	$sessionid = $_GET['sessionid'];
}
if (isset($_GET['sessionscoursesid']) && !empty(isset($_GET['sessionscoursesid']))) {
	$sessionscoursesid = $_GET['sessionscoursesid'];
	$reportType = 2;
}
if (isset($_GET['bysubgroup']) && !empty(isset($_GET['bysubgroup']))) {
	$bysubgroup = $_GET['bysubgroup'];
}
if (isset($_GET['showid']) && !empty(isset($_GET['showid']))) {
	$showid = $_GET['showid'];
	$eventType = 2;
}
if (isset($_GET['showsnumbersid']) && !empty(isset($_GET['showsnumbersid']))) {
	$showsnumbersid = $_GET['showsnumbersid'];
	$reportType = 2;
}

// create new PDF document
$pdf = new MYPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, false, 'ISO-8859-1', false);

// set document information
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor(PDF_AUTHOR);
$pdf->SetTitle('');
$pdf->SetSubject('');
$pdf->SetKeywords('');

// set default header data
// addCustomHeader($mysqli, $pdf, $language);

// set header and footer fonts
$pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
$pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));

// set default monospaced font
$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

// set margins
$pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
$pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
$pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

// set auto page breaks
$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

// set image scale factor
$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

// set some language-dependent strings (optional)
if (@file_exists(dirname(__FILE__).'/lang/'.$language.'/'.basename(__FILE__))) {
	require_once(dirname(__FILE__).'/lang/'.$language.'/'.basename(__FILE__));
	$pdf->setLanguageArray($l);
}

// ---------------------------------------------------------
// set default font subsetting mode
$pdf->setFontSubsetting(true);

// Set font
// dejavusans is a UTF-8 Unicode font, if you only need to
// print standard ASCII chars, you can use core fonts like
// helvetica or times to reduce file size.
$pdf->SetFont('times', '', 10, '', true);

// ---------------------------------------------------------
// Print only the required course
if ($reportType == 2) {
	if (isset($bysubgroup)) {
		$data = getSessionCourseSublevels($mysqli, $sessionscoursesid, $language)['data'];
		for ($x = 0; $x < count($data); $x++) {
			printReport($mysqli, $eventType, $sessionscoursesid, $showsnumbersid, $language, $l, $data[$x]['code'], $data[$x]['text'], $pdf);
		}
		printReport($mysqli, $eventType, $sessionscoursesid, $showsnumbersid, $language, $l, '[None]', $l['w_none'], $pdf);
	} else {
		printReport($mysqli, $eventType, $sessionscoursesid, $showsnumbersid, $language, $l, null, null, $pdf);
	}
} else {
	// Let's print them all for the session
	$data = getSessionCourses($mysqli, $sessionid, $language)['data'];
	for ($x = 0; $x < count($data); $x++) {
		printReport($mysqli, $eventType, $data[$x]['id'], $showsnumbersid, $language, $l, null, null, $pdf);
	}
}
// Close and output PDF document
// This method has several options, check the source code documentation for more information.
$pdf->Output('sessionCourseAttendance.pdf', 'I');

//============================================================+
// END OF FILE
//============================================================+
function printReport($mysqli, $eventType, $sessionscoursesid, $showsnumbersid, $language, $l, $sublevelcode, $subleveltext, $pdf) {

	$nboflines = 50;									// Nb of lines per pages
//	$nbofdates = 12;									// Nb of dates (columns) per pages  // count($courseDates)/2;
	$nbofdates = 20;									// Nb of dates (columns) per pages  // count($courseDates)/2;
	$width = (100 - 25)/$nbofdates;		// Width of attendance columns

	if ($eventType == 1) { // Session
		$data = getSessionCourseMembers($mysqli, $sessionscoursesid, $sublevelcode, $language);
		$courseMembersPresences = $data['data'];
		if (count($courseMembersPresences) == 0) {
		 	return;
		}
		$data = getSessionCourseDates($mysqli, $sessionscoursesid, $language);
	}
	if ($eventType == 2) { // Show
		$data = getShowNumberMembers($mysqli, $showsnumbersid, $language);
		$courseMembersPresences = $data['data'];
		if (count($courseMembersPresences) == 0) {
		 	return;
		}
		$data = getShowNumberDates($mysqli, $showsnumbersid, $language);
	}
	$courseDates = $data['data'];
	$index = 0;
	$indexCourseDates = 0;
	$pageno = 1;											// Nb of pages
	$lastdateindex = 0;
	$months = Array();								// Array of months for the course
	$monthnbdates = Array();					// Nb of dates per months

	fillMonthArrays($months, $monthnbdates, $courseDates);

	$lastmonthforpage = 0;
	$lastmonth = 0;
	while ($indexCourseDates < count($courseDates)) {
		while ($index < count($courseMembersPresences)) {
			$lastindex = $index + $nboflines;
			$lastdateindex = $indexCourseDates + $nbofdates; // Maximum nb of dates we can have in this page

			// Page header
			$html = createPageHeader($courseDates[0], $subleveltext, $l);

			$html .= '<table border="1">';
			$headerrow1 = '<tr><td width="25%">&nbsp;</td>';
			// Let's create the first two rows of the table. Only add a new month name if all dates fit in the second row
			// and let's hope we never get a course that is given every day of a month
			$nbofdatesadded = 0;
			$calculatedlastdateindex = $indexCourseDates;
			for($x = $lastmonth; $x < count($months); $x++) {
				$tempnbdatesadded = $nbofdatesadded + $monthnbdates[$x];
				if ($tempnbdatesadded <= $nbofdates) {
					// We are below the limit, add the month to row 1
					$headerrow1 .= '<td width="'.$monthnbdates[$x]*$width.'%" align="center">'.$l['w_monthNames'][$months[$x]/1].'</td>';
					$calculatedlastdateindex += $monthnbdates[$x];
					$nbofdatesadded += $monthnbdates[$x];
				} else {		// This is where we could decide that enough dates of the month fit in the page to insert part of the month
					$lastmonthforpage = $x;
					break;
				}
			}
			$headerrow1 .= '</tr>';

			// Even if the page allows for $nbofdates, if an entire month could not fit, the $calculatedlastdateindex will be different than $lastdateindex
			if ($calculatedlastdateindex != $lastdateindex) {
				$lastdateindex = $calculatedlastdateindex;
			}

			// Add the dates to row 2
			$headerrow2 = '<tr><td width="25%"><b>'.$l['w_name'].'</b></td>';
			for($y = $indexCourseDates; $y < $lastdateindex && $y < count($courseDates); $y++) {
				$headerrow2 .= '<td width="'.$width.'%" align="center">'.trim(substr($courseDates[$y]['coursedate'], 8, 2)).'</td>';
			}
			$headerrow2 .= '</tr>';

			$html .= $headerrow1.$headerrow2;

			// Add members and their attendance
			for($x = $index; $x < $lastindex && $x < count($courseMembersPresences); $x++) {
				$member = $courseMembersPresences[$x];
				$html .= '<tr>';
				$html .= '<td width="25%" nowrap>'.$member['lastname'].', '.$member['firstname'].'</td>';
				for($y = $indexCourseDates; $y < $lastdateindex && $y < count($courseDates); $y++) {
					if ($member['dates'][$y]['ispresent'] == "1") {
						$html .= '<td align="center">X</td>';
					} else if ($member['dates'][$y]['ispresent'] == "XXX") {
						$html .= '<td align="center">//</td>';
					} else if ($member['dates'][$y]['ispresent'] == "---") {
						$html .= '<td align="center">--</td>';
					} else {
						$html .= '<td>&nbsp;</td>';
	//					$html .= '<td>'.$member['dates'][$y]['ispresent'].'</td>';
					}
				}
				$html .= '</tr>';
			}

			$pdf->setPrintHeader(false);
			$pdf->SetMargins(5, 5, 5);

			$html = $html .'</table>';//.basename(__FILE__); ;//.$indexCourseDates.' '. $lastdateindex.' '.$width.' '.serialize($months).' '.serialize($monthnbdates);
			$pdf->AddPage('P');
			$pageno++;
			$pdf->writeHTMLCell(0, 0, '', '', utf8_decode($html), 0, 1, 0, true, '', true);
			$index = $lastindex;
		}
		$index = 0;
		$indexCourseDates = $lastdateindex;
		$lastmonth = $lastmonthforpage;
	}
}

function fillMonthArrays(&$months, &$monthnbdates, $courseDates) {
	// Fill the arrays of months and dates per months
	$month = substr($courseDates[0]['coursedate'], 5, 2);
	array_push($months, $month);
	$monthindex = 0;
	$monthnbdates[$monthindex] = 1;
	for ($x = 1; $x < count($courseDates); $x++) {
		if (substr($courseDates[$x]['coursedate'], 5, 2) == $month) {
			$monthnbdates[$monthindex]++;
		}
		if (substr($courseDates[$x]['coursedate'], 5, 2) != $month) {
			$month = substr($courseDates[$x]['coursedate'], 5, 2);
			array_push($months, $month);
			$monthindex++;
			$monthnbdates[$monthindex] = 1;
		}
	}
}

function createPageHeader($courseDate, $subleveltext, $l) {
	$pageheader = '';
	if ($courseDate && $courseDate['courselabel'] && $courseDate['name']) {
		$pageheader .= '<p align="center" style="font-size:20px"><b>'.$l['w_title'].' - '.$courseDate['sessionlabel'].'</b></p>';
		$pageheader .= '<p align="center" style="font-size:16px"><b>'.$courseDate['courselabel'].' ('.$courseDate['name'].')';
		if ($subleveltext != null) {
			$pageheader .= ' - '.$subleveltext;
		}
		$pageheader .= '</b></p>';
	}
	return $pageheader;
}

/**
 * This function gets the list of all courses for a session
 */
function getSessionCourses($mysqli, $sessionid, $language) {
	try {
		if (empty($sessionid)) throw new Exception("Invalid session id.");
		$query = "SELECT csc.id
							FROM cpa_sessions_courses csc
							WHERE sessionid = $sessionid
							AND datesgenerated = 1
							order by name";
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
 * This function gets the list of all sub levels for a course for a session
 */
function getSessionCourseSublevels($mysqli, $sessionscoursesid, $language) {
	try {
		if (empty($sessionscoursesid)) throw new Exception("Invalid course session id.");
		$query = "SELECT code, getTextLabel(label, '$language') text
							FROM cpa_sessions_courses_sublevels
							WHERE sessionscoursesid = $sessionscoursesid
							order by sequence";
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
 * This function gets the details of all members for a session course
 * Do not return members whose end date is before the start date of the session
 */
function getSessionCourseMembers($mysqli, $sessionscoursesid, $sublevelcode, $language) {
	try {
		if (empty($sessionscoursesid)) throw new Exception("Invalid session course.");
		$query = "SELECT cscm.*, cm.firstname, cm.lastname, cm.id memberid
							FROM cpa_sessions_courses_members cscm
							JOIN cpa_members cm ON cm.id = cscm.memberid
							JOIN cpa_sessions_courses csc ON csc.id = cscm.sessionscoursesid
							JOIN cpa_sessions cs on cs.id = csc.sessionid
							WHERE sessionscoursesid = $sessionscoursesid
							AND (cscm.registrationenddate is null OR cscm.registrationenddate > cs.coursesstartdate)";
		if ($sublevelcode != null && $sublevelcode != '[None]') {
			$query .= " AND sublevelcode = '$sublevelcode'";
		} else if ($sublevelcode != null && $sublevelcode == '[None]') {
			$query .= " AND sublevelcode is null";
		}
		$query .= " order by cm.lastname";
		$result = $mysqli->query($query);
		$data = array();
		$data['data'] = array();
		while ($row = $result->fetch_assoc()) {
			$row['dates'] = getSessionCourseMembersDates($mysqli, $row['memberid'], $sessionscoursesid, $row['registrationstartdate'], $row['registrationenddate'])['data'];
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
 * This function gets the details of all members for a show number
 */
function getShowNumberMembers($mysqli, $showsnumbersid, $language) {
	try {
		if (empty($showsnumbersid)) throw new Exception("Invalid show number id.");
		$query = "SELECT csnm.*, cm.firstname, cm.lastname, cm.id memberid
							FROM cpa_shows_numbers_members csnm
							JOIN cpa_members cm ON cm.id = csnm.memberid
							JOIN cpa_shows_numbers csn ON csn.id = csnm.numberid
							JOIN cpa_shows cs on cs.id = csn.showid
							WHERE csnm.numberid = $showsnumbersid
						  ORDER BY cm.lastname";
		$result = $mysqli->query($query);
		$data = array();
		$data['data'] = array();
		while ($row = $result->fetch_assoc()) {
			$row['dates'] = getShowNumberMembersDates($mysqli, $row['memberid'], $showsnumbersid, $row['registrationstartdate'], $row['registrationenddate'])['data'];
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
 * This function gets the list of presences for a member for a course from database
 */
function getSessionCourseMembersDates($mysqli, $memberid, $sessionscoursesid, $registrationstartdate, $registrationenddate) {
	try {
		if (empty($memberid)) throw new Exception("Invalid Member ID.");
		$query = "SELECT cscd.*, 
										 if ('$registrationenddate' != '' and cscd.coursedate >= '$registrationenddate', 'XXX', if ('$registrationstartdate' != '' and cscd.coursedate <= '$registrationstartdate', 'XXX', if (cscd.canceled, '---', cscp.ispresent))) ispresent
							FROM cpa_sessions_courses_dates cscd
							LEFT JOIN cpa_sessions_courses_presences cscp ON cscp.sessionscoursesdatesid = cscd.id and cscp.memberid = '$memberid'
							WHERE sessionscoursesid = $sessionscoursesid
							AND canceled = 0
							ORDER BY coursedate";
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
 * This function gets the list of presences for a member for a show number from database
 */
function getShowNumberMembersDates($mysqli, $memberid, $showsnumbersid, $registrationstartdate, $registrationenddate) {
	try {
		if (empty($memberid)) throw new Exception("Invalid Member ID.");
		$query = "SELECT csnd.*, practicedate as coursedate, 
										if ('$registrationenddate' != '' and csnd.practicedate >= '$registrationenddate', 'XXX', if ('$registrationstartdate' != '' and csnd.practicedate <= '$registrationstartdate', 'XXX', if (csnd.canceled, '---', csnp.ispresent))) ispresent
							FROM cpa_shows_numbers_dates csnd
							LEFT JOIN cpa_shows_numbers_presences csnp ON csnp.showsnumbersdatesid = csnd.id and csnp.memberid = '$memberid'
							WHERE csnd.numberid = $showsnumbersid
							ORDER BY practicedate";
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
 * This function gets the list of dates for a course from database
 */
function getSessionCourseDates($mysqli, $sessionscoursesid, $language) {
	try {
		if (empty($sessionscoursesid)) throw new Exception("Invalid session course.");
		$query = "SELECT cscd.coursedate, getTextLabel(cs.label, '$language') sessionlabel, getTextLabel(csc.label, '$language') courselabel, csc.name
							FROM cpa_sessions_courses_dates cscd
							JOIN cpa_sessions_courses csc ON csc.id = cscd.sessionscoursesid
							JOIN cpa_sessions cs ON cs.id = csc.sessionid
							WHERE sessionscoursesid = '$sessionscoursesid'
							AND cscd.canceled = 0
							order by coursedate";
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
 * This function gets the list of dates for a show number from database
 */
function getShowNumberDates($mysqli, $showsnumbersid, $language) {
	try {
		if (empty($showsnumbersid)) throw new Exception("Invalid show number.");
		$query = "SELECT cscd.practicedate as coursedate, getTextLabel(cs.label, '$language') sessionlabel, getTextLabel(csn.label, '$language') courselabel, csn.name
							FROM cpa_shows_numbers_dates cscd
							JOIN cpa_shows_numbers csn ON csn.id = cscd.numberid
							JOIN cpa_shows cs ON cs.id = csn.showid
							WHERE numberid = $showsnumbersid
							order by practicedate";
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
