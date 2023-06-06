<?php
//============================================================+
// File name   : sessionCourseCSProgress.php
//
// Description : session course canskate name tag
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

// Input parameters
$sessionscoursesid = null;
$language = 'fr-ca';
$sessionid = null;
$sessionscoursesid = null;
if (isset($_GET['language']) && !empty(isset($_GET['language']))) {
	$language = $_GET['language'];
}
if (isset($_GET['sessionid']) && !empty(isset($_GET['sessionid']))) {
	$sessionid = $_GET['sessionid'];
}
if (isset($_GET['sessionscoursesid']) && !empty(isset($_GET['sessionscoursesid']))) {
	$sessionscoursesid = $_GET['sessionscoursesid'];
}

// create new PDF document
$pdf = new MYPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

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

$data = getSessionCourseMembers($mysqli, $sessionscoursesid, $language);
$courseMembers = $data['data'];
$ynames = [12,71,130.5,190];
// First line, first card $y = 27, 33.5, 40, 47, 53.5, 60.5
$ytests = [27, 33.5, 40, 47, 53.5, 60.5];
$ydelta = [0,59,118.5,178];
$xtests = [119, 135, 152, 168];
$xtestdelta = [0,0,0,0];
// The french and english version don't align.... so for the english version, we'll move the fields to the right a bit
if ($language == 'en-ca') {
	$xtestdelta = [.7,2,1.5,2];
}

$position = 0;
for ($z = 0; $z < count($courseMembers); $z++) {
	if ($z == 0 || $position == 4) {
		// add a page
		$pdf->AddPage();
		// get the current page break margin
		$bMargin = $pdf->getBreakMargin();
		// get current auto-page-break mode
		$auto_page_break = $pdf->getAutoPageBreak();
		// disable auto-page-break
		$pdf->SetAutoPageBreak(false, 0);
		// set bacground image
		$img_file = K_PATH_IMAGES.'reports/canskate/'.'canskate_progress_report_'.$language.'.jpg';
		$pdf->Image($img_file, 0, 0, 210, 297, '', '', '', false, 300, '', false, false, 0);
		// restore auto-page-break status
		$pdf->SetAutoPageBreak($auto_page_break, $bMargin);
		// set the starting point for the page content
		$pdf->setPageMark();
		$position = 0;
		// set color for background
		$pdf->SetFillColor(255, 255, 255);

		$w_page = isset($pdf->l['w_page']) ? $pdf->l['w_page'].' ' : '';
		if (empty($pdf->pagegroups)) {
			$pagenumtxt = $w_page.$pdf->getAliasNumPage().' / '.$pdf->getAliasNbPages();
		} else {
			$pagenumtxt = $w_page.$pdf->getPageNumGroupAlias().' / '.$pdfs->getPageGroupAlias();
		}
		$html = '<table><tr><td width="33%">JeNiAl</td><td width="33%" align="center">'.$courseMembers[$z]['courselabel'].' ('.$courseMembers[$z]['name'].')'.'</td><td align="right">'.$pagenumtxt.'</td></tr></table>';
		$pdf->writeHTMLCell(195, 0, 10, 265, $html, 0, 1, 1, true, 'L', true);
		$pdf->setPageMark();
	}
	// set color for background
	$pdf->SetFillColor(255, 255, 255);

	// Name
	$fullname = $courseMembers[$z]['firstname'].' '.$courseMembers[$z]['lastname'];
	if (strlen($fullname) <= 13) {
		$pdf->SetFont('courier', '', 28);
	} else if (strlen($fullname) <= 14) {
		$pdf->SetFont('courier', '', 26);
	} else if (strlen($fullname) <= 15) {
		$pdf->SetFont('courier', '', 24);
	} else if (strlen($fullname) <= 16) {
		$pdf->SetFont('courier', '', 23);
	} else if (strlen($fullname) <= 17) {
		$pdf->SetFont('courier', '', 21);
	} else {
		$pdf->SetFont('courier', '', 20);
	}
	// $html = '<p style="font-size:20px">'.$courseMembers[$z]['firstname'].' '.$courseMembers[$z]['lastname'].'</p>';
	$html = '<p>'.$fullname.'</p>';
	$pdf->writeHTMLCell(80, 0, 15, $ynames[$position], $html, 0, 1, 1, true, 'L', true);
	$html = '<p style="font-size:36px" align="center">'.$courseMembers[$z]['sublevellabel'].'</p>';
	$pdf->writeHTMLCell(80, 0, 15, $ynames[$position]+21, $html, 0, 1, 1, true, 'L', true);
	$html = '<p style="font-size:12px" align="center">'.$courseMembers[$z]['name'].'</p>';
	$pdf->writeHTMLCell(80, 0, 15, $ynames[$position]+35, $html, 0, 1, 1, true, 'L', true);

	$pdf->SetFont('times', '', 8);
	for ($x = 0; $x < count($courseMembers[$z]['csbalanceribbons']); $x++) {
		$html = '<p style="font-size:9px">'.$courseMembers[$z]['csbalanceribbons'][$x]['ribbondate'].'</p>';
		$pdf->writeHTMLCell(14, 0, $xtests[0] + $xtestdelta[0], $ytests[$x] + $ydelta[$position], $html, 0, 1, 1, true, 'L', true);
	}
	for ($x = 0; $x < count($courseMembers[$z]['cscontrolribbons']); $x++) {
		$html = '<p style="font-size:9px">'.$courseMembers[$z]['cscontrolribbons'][$x]['ribbondate'].'</p>';
		$pdf->writeHTMLCell(14, 0, $xtests[1] + $xtestdelta[1], $ytests[$x] + $ydelta[$position], $html, 0, 1, 1, true, 'L', true);
	}
	for ($x = 0; $x < count($courseMembers[$z]['csagilityribbons']); $x++) {
		$html = '<p style="font-size:9px">'.$courseMembers[$z]['csagilityribbons'][$x]['ribbondate'].'</p>';
		$pdf->writeHTMLCell(14, 0, $xtests[2] + $xtestdelta[2], $ytests[$x] + $ydelta[$position], $html, 0, 1, 1, true, 'L', true);
	}
	// set color for background
	$pdf->SetFillColor(217, 217, 217);
	for ($x = 0; $x < count($courseMembers[$z]['csstagebadges']); $x++) {
		$html = '<p style="font-size:9px">'.$courseMembers[$z]['csstagebadges'][$x]['badgedate'].'</p>';
		$pdf->writeHTMLCell(14, 0, $xtests[3] + $xtestdelta[3], $ytests[$x] + $ydelta[$position], $html, 0, 1, 1, true, 'L', true);
	}

	$position++;
}

// ---------------------------------------------------------

//Close and output PDF document
$pdf->Output('sessionCourseCSProgress.pdf', 'I');

//============================================================+

/**
 * This function gets the canskate ribbons of one member from database
 */
function getMemberCanskateStageRibbons($mysqli, $memberid, $category) {
	try {
		if (empty($memberid)) throw new Exception("Invalid Member ID.");
		$query = "SELECT cc.id canskateid, cmcr.ribbondate, cmcr.id
							FROM cpa_canskate cc
							left join cpa_members_canskate_ribbons cmcr on cc.id = cmcr.canskateid AND (memberid = $memberid || memberid is null)
							WHERE cc.category = '$category'
							ORDER BY cc.stage";
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
 * This function gets the canskate badges of one member from database
 * (we use BALANCE as a category to get the stages 1 to 6. We could have used any of the 3 categories)
 */
function getMemberCanskateStageBadges($mysqli, $memberid = '') {
	try {
		if (empty($memberid)) throw new Exception("Invalid Member ID.");
		$query = "SELECT cc.stage canskatestage, cmcb.badgedate, cmcb.id
							FROM cpa_canskate cc
							LEFT JOIN cpa_members_canskate_badges cmcb on cc.stage = cmcb.canskatestage AND (memberid = $memberid || memberid is null)
							WHERE cc.category = 'BALANCE'
							ORDER BY cc.stage";
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
 * [lamoeric 2018/09/11] corrected SQL command to remove unregistered skaters from the select
 */
function getSessionCourseMembers($mysqli, $sessionscoursesid, $language) {
	try {
		if (empty($sessionscoursesid)) throw new Exception("Invalid session course.");
		$query = "SELECT cscm.*, cm.firstname, cm.lastname, cm.id memberid, getTextLabel(csc.label, '$language') courselabel, csc.name,
										(select getTextLabel(label, '$language') FROM cpa_sessions_courses_sublevels cscs WHERE cscs.sessionscoursesid = cscm.sessionscoursesid AND cscs.code = cscm.sublevelcode) sublevellabel
							FROM cpa_sessions_courses_members cscm
							JOIN cpa_members cm ON cm.id = cscm.memberid
							JOIN cpa_sessions_courses csc ON csc.id = cscm.sessionscoursesid
							WHERE sessionscoursesid = $sessionscoursesid
							AND (cscm.registrationenddate is null or cscm.registrationenddate > curdate())
							order by cm.lastname";
		$result = $mysqli->query($query);
		$data = array();
		$data['data'] = array();
		while ($row = $result->fetch_assoc()) {
			$row['csbalanceribbons'] 	= getMemberCanskateStageRibbons($mysqli, $row['memberid'], 'BALANCE')['data'];
			$row['cscontrolribbons'] 	= getMemberCanskateStageRibbons($mysqli, $row['memberid'], 'CONTROL')['data'];
			$row['csagilityribbons'] 	= getMemberCanskateStageRibbons($mysqli, $row['memberid'], 'AGILITY')['data'];

			$row['csstagebadges'] 	= getMemberCanskateStageBadges($mysqli, $row['memberid'], 'BALANCE')['data'];
//			$row['dates'] = getSessionCourseMembersDates($mysqli, $row['memberid'], $sessionscoursesid, $row['registrationenddate'])['data'];
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
