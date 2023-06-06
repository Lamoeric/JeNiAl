<?php
//============================================================+
// File name   : sessionCoursePreCSReportCard.php
// Begin       : 2018-09-20
// Last Update :
//
// Description : session course pre-canSkate report card
//
// Author: Eric Lamoureux
//
//============================================================+

// Include the main TCPDF library (search for installation path).
require_once('../../include/tcpdf_include.php');
require_once('../../../private/'. $_SERVER['HTTP_HOST'].'/include/config.php');
require_once('../../include/nocache.php');
require_once('mypdf_footer.php');
require_once('getSessionLabel.php');
require_once('getClubNameAndAddress.php');

set_time_limit(500);

// Input parameters
$language = 'fr-ca';
$sessionid = null;
$sessionscoursesid = null;
$memberid = null;
if (isset($_GET['language']) && !empty(isset($_GET['language']))) {
	$language = $_GET['language'];
}
if (isset($_GET['sessionid']) && !empty(isset($_GET['sessionid']))) {
	$sessionid = $_GET['sessionid'];
}
if (isset($_GET['sessionscoursesid']) && !empty(isset($_GET['sessionscoursesid']))) {
	$sessionscoursesid = $_GET['sessionscoursesid'];
}
if (isset($_GET['memberid']) && !empty(isset($_GET['memberid']))) {
	$memberid = $_GET['memberid'];
}

// create new PDF document
$pdf = new MYPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, false, 'ISO-8859-1', false);

// set document information
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor(PDF_AUTHOR);
$pdf->SetTitle('');
$pdf->SetSubject('');
$pdf->SetKeywords('');

// set header and footer fonts
$pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));

// set default monospaced font
$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

// set margins
$pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
$pdf->SetHeaderMargin(0);
$pdf->SetFooterMargin(0);

// remove default footer
$pdf->setPrintFooter(false);

// set image scale factor
$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

// set some language-dependent strings (optional)
if (@file_exists(dirname(__FILE__).'/lang/'.$language.'/'.basename(__FILE__))) {
	require_once(dirname(__FILE__).'/lang/'.$language.'/'.basename(__FILE__));
	$pdf->setLanguageArray($l);
}

// ---------------------------------------------------------

// set font
$pdf->SetFont('times', '', 8);

// set color for background
$pdf->SetFillColor(255, 255, 255);

// set color for text
$pdf->SetTextColor(0, 0, 0);

if (!isset($memberid)) {
	$data = getSessionCourseMembers($mysqli, $sessionscoursesid, $language);
	$courseMembers = $data['data'];
} else {
	$data = getMemberDetails($mysqli, $memberid, $language);
	$courseMembers = $data['data'];
}
$data = getSessionLabel($mysqli, $sessionid, $language);
$sessionlabel = $data['data'][0]['sessionlabel'];
$data = getClubNameAndAddress($mysqli, $language);
$cpalongname = $data['data'][0]['cpalongname'];

for ($z = 0; $z < count($courseMembers); $z++) {
	// add a page
	$pdf->AddPage('L');
	// get the current page break margin
	$bMargin = $pdf->getBreakMargin();
	// get current auto-page-break mode
	$auto_page_break = $pdf->getAutoPageBreak();
	// disable auto-page-break
	$pdf->SetAutoPageBreak(false, 0);
	// set background image
	$img_file = K_PATH_IMAGES.'reports/canskate/'.'precanskate_report_card_page1_'.$language.'.jpg';
	$pdf->Image($img_file, 0, 0, 300, 195, '', '', '', false, 300, '', false, false, 0);
	// restore auto-page-break status
	$pdf->SetAutoPageBreak($auto_page_break, $bMargin);
	// set the starting point for the page content
	$pdf->setPageMark();
	// set color for background
	$pdf->SetFillColor(255, 255, 255);

	$pdf->SetFont('times', '', 14);
	// Name
	$fullname = utf8_decode($courseMembers[$z]['firstname']).' '.utf8_decode($courseMembers[$z]['lastname']);
	// Skate Canada number
	$skatecanadano = $courseMembers[$z]['skatecanadano'];

	if ($language == 'fr-ca') {
		$pdf->writeHTMLCell(260, 0, 185, 109, $fullname, 0, 1, 1, true, 'L', true);
		$pdf->writeHTMLCell(260, 0, 185, 121, $skatecanadano, 0, 1, 1, true, 'L', true);
		$pdf->writeHTMLCell(260, 0, 185, 132.5, $sessionlabel, 0, 1, 1, true, 'L', true);
		$pdf->writeHTMLCell(260, 0, 185, 144, $cpalongname, 0, 1, 1, true, 'L', true);
	} else {
		$pdf->writeHTMLCell(260, 0, 185, 111, $fullname, 0, 1, 1, true, 'L', true);
		$pdf->writeHTMLCell(260, 0, 185, 123, $skatecanadano, 0, 1, 1, true, 'L', true);
		$pdf->writeHTMLCell(260, 0, 185, 134.5, $sessionlabel, 0, 1, 1, true, 'L', true);
		$pdf->writeHTMLCell(260, 0, 185, 146, $cpalongname, 0, 1, 1, true, 'L', true);
	}

	// Page 2
	// add a page
	$pdf->AddPage('L');
	// get the current page break margin
	$bMargin = $pdf->getBreakMargin();
	// get current auto-page-break mode
	$auto_page_break = $pdf->getAutoPageBreak();
	// disable auto-page-break
	$pdf->SetAutoPageBreak(false, 0);
	// set bacground image
	$img_file = K_PATH_IMAGES.'reports/canskate/'.'precanskate_report_card_page2_'.$language.'.jpg';
	$pdf->Image($img_file, 0, 0, 300, 195, '', '', '', false, 300, '', false, false, 0);
	// restore auto-page-break status
	$pdf->SetAutoPageBreak($auto_page_break, $bMargin);
	// set the starting point for the page content
	$pdf->setPageMark();
	// set color for background
	$pdf->SetFillColor(255, 255, 255);

	$pdf->SetFont('times', '', 10);
	// Pre skate
	for ($x = 0; $x < count($courseMembers[$z]['preCStests'][1]); $x++) {
		$test = $courseMembers[$z]['preCStests'][1][$x];
		if ($test['success'] == 1) {
			if ($language == 'fr-ca') {
				$pdf->writeHTMLCell(5, 0, 179, 37 + ($x*5), 'X', 0, 1, 1, true, 'L', true);
			} else  {
				$pdf->writeHTMLCell(5, 0, 177, 39 + ($x*5), 'X', 0, 1, 1, true, 'L', true);
			}
		}
	}

	// Date
	$html = date("Y/m/d");
	$pdf->writeHTMLCell(70, 0, 255, 163, $html, 0, 1, 1, true, 'L', true);

	// Footer on page 2 only
	$html = '<table><tr><td width="100%" align="right">JeNiAl - '. date("Y/m/d H:i:s").'</td></tr></table>';
	$pdf->writeHTMLCell(0, 0, PDF_MARGIN_RIGHT, 185, $html, 0, 1, 1, true, 'L', true);
}

// ---------------------------------------------------------

//Close and output PDF document
$pdf->Output('sessionCoursePreCSReportCard.pdf', 'I');

//============================================================+
//============================================================+

/**
 * This function gets the canskate elements of one member from database
 */
function getMemberCanskateStageTests($mysqli, $memberid, $category, $stage, $language) {
	if (empty($memberid)) throw new Exception("Invalid Member ID.");
	$query = "SELECT ccst.id canskatetestid, testdate as testdatestr, success, ccst.canskateid, ccst.type, cmcst.id as id, ccs.stage, getTextLabel(ccst.label, '$language') text
						FROM cpa_canskate_tests ccst
						left join cpa_members_canskate_tests cmcst on cmcst.canskatetestid = ccst.id AND (memberid = '$memberid' || memberid is null)
						left join cpa_canskate ccs on ccs.id = ccst.canskateid
						WHERE (ccst.type = 'TEST' || ccst.type = 'SUBTEST') and ccs.category = '$category' and ccs.stage = $stage
						ORDER BY ccst.sequence, cmcst.testdate desc";
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
 * This function gets the dance tests of one member from database
 */
function getMemberCanskateTests($mysqli, $memberid, $category, $language) {
	$data = array();
	$data['data'] = array();
	$data['data']['1'] = getMemberCanskateStageTests($mysqli, $memberid, $category, 1, $language)['data'];
	$data['success'] = true;
	return $data;
};

/**
 * This function gets the details of all members for a session course
 */
function getSessionCourseMembers($mysqli, $sessionscoursesid, $language) {
	try {
		if (empty($sessionscoursesid)) throw new Exception("Invalid session course.");
		$query = "SELECT cscm.*, cm.firstname, cm.lastname, cm.id memberid, cm.skatecanadano, cm.birthday, getTextLabel(csc.label, '$language') courselabel, csc.name
							FROM cpa_sessions_courses_members cscm
							JOIN cpa_members cm ON cm.id = cscm.memberid
							JOIN cpa_sessions_courses csc ON csc.id = cscm.sessionscoursesid
							WHERE sessionscoursesid = $sessionscoursesid
							order by cm.lastname";
		$result = $mysqli->query($query);
		$data = array();
		$data['data'] = array();
		while ($row = $result->fetch_assoc()) {
			$row['preCStests'] = getMemberCanskateTests($mysqli, $row['memberid'], 'PRESKATE', $language)['data'];
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
 * This function gets the details of one member
 */
function getMemberDetails($mysqli, $memberid, $language) {
	try {
		if (empty($memberid)) throw new Exception("Invalid member.");
		$query = "SELECT cm.firstname, cm.lastname, cm.id memberid, cm.skatecanadano, cm.birthday
							FROM cpa_members cm
							WHERE id = $memberid";
		$result = $mysqli->query($query);
		$data = array();
		$data['data'] = array();
		while ($row = $result->fetch_assoc()) {
			$row['preCStests'] = getMemberCanskateTests($mysqli, $row['memberid'], 'PRESKATE', $language)['data'];
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
