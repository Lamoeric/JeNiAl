<?php
//============================================================+
// File name   : sessionCourseCSReportCard.php
// Begin       : 2016-07-07
// Last Update :
//
// Description : session course CanSkate report card
//
// Author: Eric Lamoureux
//
//============================================================+

// Include the main TCPDF library (search for installation path).
require_once('../../include/tcpdf_include.php');
require_once('../../../private/'. $_SERVER['HTTP_HOST'].'/include/config.php');
require_once('../../include/nocache.php');
require_once('mypdf_footer.php');

set_time_limit(1500);

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

for ($z = 0; $z < count($courseMembers); $z++) {
	// add a page
	$pdf->AddPage('L');
	// get the current page break margin
	$bMargin = $pdf->getBreakMargin();
	// get current auto-page-break mode
	$auto_page_break = $pdf->getAutoPageBreak();
	// disable auto-page-break
	$pdf->SetAutoPageBreak(false, 0);
	// set bacground image
	$img_file = K_PATH_IMAGES.'reports/canskate/'.'canskate_report_card_empty_'.$language.'.jpg';
	$pdf->Image($img_file, 0, 0, 300, 195, '', '', '', false, 300, '', false, false, 0);
	// restore auto-page-break status
	$pdf->SetAutoPageBreak($auto_page_break, $bMargin);
	// set the starting point for the page content
	$pdf->setPageMark();
	$position = 0;
	// set color for background
	$pdf->SetFillColor(255, 255, 255);

	$w_page = isset($l['w_page']) ? $l['w_page'].' ' : '';
	if (empty($pdf->pagegroups)) {
		$pagenumtxt = $w_page.$pdf->getAliasNumPage().' / '.$pdf->getAliasNbPages();
	} else {
		$pagenumtxt = $w_page.$pdf->getPageNumGroupAlias().' / '.$pdfs->getPageGroupAlias();
	}
	if (!isset($memberid)) {
		$html = '<table><tr><td width="33%">JeNiAl</td><td width="33%" align="center">'.utf8_decode($courseMembers[$z]['courselabel']).' ('.$courseMembers[$z]['name'].')'.'</td><td align="right">'.$pagenumtxt.'</td></tr></table>';
	} else {
		$html = '<table><tr><td width="33%">JeNiAl</td><td width="33%" align="center"></td><td align="right">'.$pagenumtxt.'</td></tr></table>';
	}
	$pdf->writeHTMLCell(0, 0, PDF_MARGIN_RIGHT, 185, $html, 0, 1, 1, true, 'L', true);
	// Causes an error in the PDF
	// $pdf->setPageMark();

	// set color for background
	$pdf->SetFillColor(255, 255, 255);

	// Name
	$pdf->SetFont('times', '', 12);
	$html = '<table><tr><td width="33%">'.$l['w_name'].utf8_decode($courseMembers[$z]['firstname']).' '.utf8_decode($courseMembers[$z]['lastname']).'</td><td width="33%" align="center">'.$l['w_skatecanadano'].$courseMembers[$z]['skatecanadano'].'</td><td align="right">'.$l['w_birthday'].$courseMembers[$z]['birthday'].'</td></tr></table>';
	// $html = '<table><tr><td width="33%">'.$pdf->l['w_name'].$courseMembers[$z]['firstname'].' '.$courseMembers[$z]['lastname'].'</td><td width="33%" align="center">'.$courseMembers[$z]['skatecanadano'].'</td><td align="right">'.$courseMembers[$z]['birthday'].'</td></tr></table>';
	$pdf->writeHTMLCell(260, 0, 20, 15, $html, 0, 1, 1, true, 'L', true);
	$pdf->SetFont('times', '', 6);

	// Balance, stage 1
	$pdf->writeHTMLCell(38.5, 33, 48.5, 31, getHTMLForOneCategoryForOneStage($courseMembers[$z]['csbalancetests'][1]), 0, 1, 1, true, 'L', true);
	// Balance, stage 2
	$pdf->writeHTMLCell(38.5, 33, 88, 31, getHTMLForOneCategoryForOneStage($courseMembers[$z]['csbalancetests'][2]), 0, 1, 1, true, 'L', true);
	// Balance, stage 3
	$pdf->writeHTMLCell(38.5, 33, 128, 31, getHTMLForOneCategoryForOneStage($courseMembers[$z]['csbalancetests'][3]), 0, 1, 1, true, 'L', true);
	// Balance, stage 4
	$pdf->writeHTMLCell(38.5, 33, 168, 31, getHTMLForOneCategoryForOneStage($courseMembers[$z]['csbalancetests'][4]), 0, 1, 1, true, 'L', true);
	// Balance, stage 5
	$pdf->writeHTMLCell(38.5, 33, 207.5, 31, getHTMLForOneCategoryForOneStage($courseMembers[$z]['csbalancetests'][5]), 0, 1, 1, true, 'L', true);
	// Balance, stage 6
	$pdf->writeHTMLCell(38.5, 33, 247, 31, getHTMLForOneCategoryForOneStage($courseMembers[$z]['csbalancetests'][6]), 0, 1, 1, true, 'L', true);

	// control, stage 1
	$pdf->writeHTMLCell(38.5, 33, 48.5, 77, getHTMLForOneCategoryForOneStage($courseMembers[$z]['cscontroltests'][1]), 0, 1, 1, true, 'L', true);
	// control, stage 2
	$pdf->writeHTMLCell(38.5, 33, 88, 77, getHTMLForOneCategoryForOneStage($courseMembers[$z]['cscontroltests'][2]), 0, 1, 1, true, 'L', true);
	// control, stage 3
	$pdf->writeHTMLCell(38.5, 33, 128, 77, getHTMLForOneCategoryForOneStage($courseMembers[$z]['cscontroltests'][3]), 0, 1, 1, true, 'L', true);
	// control, stage 4
	$pdf->writeHTMLCell(38.5, 33, 168, 77, getHTMLForOneCategoryForOneStage($courseMembers[$z]['cscontroltests'][4]), 0, 1, 1, true, 'L', true);
	// control, stage 5
	$pdf->writeHTMLCell(38.5, 33, 207.5, 77, getHTMLForOneCategoryForOneStage($courseMembers[$z]['cscontroltests'][5]), 0, 1, 1, true, 'L', true);
	// control, stage 6
	$pdf->writeHTMLCell(38.5, 33, 247, 77, getHTMLForOneCategoryForOneStage($courseMembers[$z]['cscontroltests'][6]), 0, 1, 1, true, 'L', true);

	// Agility, stage 1
	$pdf->writeHTMLCell(38.5, 33, 48.5, 123, getHTMLForOneCategoryForOneStage($courseMembers[$z]['csagilitytests'][1]), 0, 1, 1, true, 'L', true);
	// Agility, stage 2
	$pdf->writeHTMLCell(38.5, 33, 88, 123, getHTMLForOneCategoryForOneStage($courseMembers[$z]['csagilitytests'][2]), 0, 1, 1, true, 'L', true);
	// Agility, stage 3
	$pdf->writeHTMLCell(38.5, 33, 128, 123, getHTMLForOneCategoryForOneStage($courseMembers[$z]['csagilitytests'][3]), 0, 1, 1, true, 'L', true);
	// Agility, stage 4
	$pdf->writeHTMLCell(38.5, 33, 168, 123, getHTMLForOneCategoryForOneStage($courseMembers[$z]['csagilitytests'][4]), 0, 1, 1, true, 'L', true);
	// Agility, stage 5
	$pdf->writeHTMLCell(38.5, 33, 207.5, 123, getHTMLForOneCategoryForOneStage($courseMembers[$z]['csagilitytests'][5]), 0, 1, 1, true, 'L', true);
	// Agility, stage 6
	$pdf->writeHTMLCell(38.5, 33, 247, 123, getHTMLForOneCategoryForOneStage($courseMembers[$z]['csagilitytests'][6]), 0, 1, 1, true, 'L', true);

	// Ribbons
	$pdf->SetFont('times', '', 7);
	$pdf->writeHTMLCell(38.5, 1, 48.5, 69.5, '<p align="center">'.$courseMembers[$z]['csbalanceribbons'][0]['ribbondate'].'</p>', 0, 1, 1, true, 'L', true);
	$pdf->writeHTMLCell(38.5, 1, 88,   69.5, '<p align="center">'.$courseMembers[$z]['csbalanceribbons'][1]['ribbondate'].'</p>', 0, 1, 1, true, 'L', true);
	$pdf->writeHTMLCell(38.5, 1, 128,  69.5, '<p align="center">'.$courseMembers[$z]['csbalanceribbons'][2]['ribbondate'].'</p>', 0, 1, 1, true, 'L', true);
	$pdf->writeHTMLCell(38.5, 1, 168,  69.5, '<p align="center">'.$courseMembers[$z]['csbalanceribbons'][3]['ribbondate'].'</p>', 0, 1, 1, true, 'L', true);
	$pdf->writeHTMLCell(38.5, 1, 207,  69.5, '<p align="center">'.$courseMembers[$z]['csbalanceribbons'][4]['ribbondate'].'</p>', 0, 1, 1, true, 'L', true);
	$pdf->writeHTMLCell(38.5, 1, 247,  69.5, '<p align="center">'.$courseMembers[$z]['csbalanceribbons'][5]['ribbondate'].'</p>', 0, 1, 1, true, 'L', true);

	$pdf->writeHTMLCell(38.5, 1, 48.5, 115.5, '<p align="center">'.$courseMembers[$z]['cscontrolribbons'][0]['ribbondate'].'</p>', 0, 1, 1, true, 'L', true);
	$pdf->writeHTMLCell(38.5, 1, 88,   115.5, '<p align="center">'.$courseMembers[$z]['cscontrolribbons'][1]['ribbondate'].'</p>', 0, 1, 1, true, 'L', true);
	$pdf->writeHTMLCell(38.5, 1, 128,  115.5, '<p align="center">'.$courseMembers[$z]['cscontrolribbons'][2]['ribbondate'].'</p>', 0, 1, 1, true, 'L', true);
	$pdf->writeHTMLCell(38.5, 1, 168,  115.5, '<p align="center">'.$courseMembers[$z]['cscontrolribbons'][3]['ribbondate'].'</p>', 0, 1, 1, true, 'L', true);
	$pdf->writeHTMLCell(38.5, 1, 207,  115.5, '<p align="center">'.$courseMembers[$z]['cscontrolribbons'][4]['ribbondate'].'</p>', 0, 1, 1, true, 'L', true);
	$pdf->writeHTMLCell(38.5, 1, 247,  115.5, '<p align="center">'.$courseMembers[$z]['cscontrolribbons'][5]['ribbondate'].'</p>', 0, 1, 1, true, 'L', true);

	$pdf->writeHTMLCell(38.5, 1, 48.5, 162.5, '<p align="center">'.$courseMembers[$z]['csagilityribbons'][0]['ribbondate'].'</p>', 0, 1, 1, true, 'L', true);
	$pdf->writeHTMLCell(38.5, 1, 88,   162.5, '<p align="center">'.$courseMembers[$z]['csagilityribbons'][1]['ribbondate'].'</p>', 0, 1, 1, true, 'L', true);
	$pdf->writeHTMLCell(38.5, 1, 128,  162.5, '<p align="center">'.$courseMembers[$z]['csagilityribbons'][2]['ribbondate'].'</p>', 0, 1, 1, true, 'L', true);
	$pdf->writeHTMLCell(38.5, 1, 168,  162.5, '<p align="center">'.$courseMembers[$z]['csagilityribbons'][3]['ribbondate'].'</p>', 0, 1, 1, true, 'L', true);
	$pdf->writeHTMLCell(38.5, 1, 207,  162.5, '<p align="center">'.$courseMembers[$z]['csagilityribbons'][4]['ribbondate'].'</p>', 0, 1, 1, true, 'L', true);
	$pdf->writeHTMLCell(38.5, 1, 247,  162.5, '<p align="center">'.$courseMembers[$z]['csagilityribbons'][5]['ribbondate'].'</p>', 0, 1, 1, true, 'L', true);

	$pdf->writeHTMLCell(38.5, 1, 48.5, 173.5, '<p align="center">'.$courseMembers[$z]['csstagebadges'][0]['badgedate'].'</p>', 0, 1, 1, true, 'L', true);
	$pdf->writeHTMLCell(38.5, 1, 88,   173.5, '<p align="center">'.$courseMembers[$z]['csstagebadges'][1]['badgedate'].'</p>', 0, 1, 1, true, 'L', true);
	$pdf->writeHTMLCell(38.5, 1, 128,  173.5, '<p align="center">'.$courseMembers[$z]['csstagebadges'][2]['badgedate'].'</p>', 0, 1, 1, true, 'L', true);
	$pdf->writeHTMLCell(38.5, 1, 168,  173.5, '<p align="center">'.$courseMembers[$z]['csstagebadges'][3]['badgedate'].'</p>', 0, 1, 1, true, 'L', true);
	$pdf->writeHTMLCell(38.5, 1, 207,  173.5, '<p align="center">'.$courseMembers[$z]['csstagebadges'][4]['badgedate'].'</p>', 0, 1, 1, true, 'L', true);
	$pdf->writeHTMLCell(38.5, 1, 247,  173.5, '<p align="center">'.$courseMembers[$z]['csstagebadges'][5]['badgedate'].'</p>', 0, 1, 1, true, 'L', true);
}

// ---------------------------------------------------------

//Close and output PDF document
$pdf->Output('sessionCourseCSReportCard.pdf', 'I');

//============================================================+
//============================================================+

/**
* This function returns the number of subtest
* return value is the number of subtest
*/
function getNbOfSubTests($memberTests, $firstIndex) {
	$lastIndex = $firstIndex;
	$numberOfTest = 0;
	for ($x = $firstIndex; $x < count($memberTests); $x++) {
		if ($memberTests[$x]['type'] != 'SUBTEST') {
			break;
		}
		$lastIndex = $x;
	}
	$numberOfTest = ($lastIndex - $firstIndex) + 1;
	return $numberOfTest;
}

/**
* This function handles the subtest
* return value is the html for the subtest
*/
function getHTMLForSubTests($memberTests, $firstIndex) {
	$startSubTest = false;
	$html = '<table width="100%" border="1" cellpadding="1"><tr>';
	$numberOfTest = getNbOfSubTests($memberTests, $firstIndex);
	$subTestIndex = 0;

	for ($x = $firstIndex; $x < $firstIndex + $numberOfTest; $x++) {
		$subTestIndex++;
		if ($memberTests[$x]['success'] == 1) {
			$success = 'X';
		} else {
			$success = '';
		}
		if ($numberOfTest == 2) {
			$html .= '<td width="10%" align="center">'.$success.'</td><td width="40%">'.$memberTests[$x]['text'].'</td>';
		} else if ($numberOfTest == 3) {
			$html .= '<td width="10%" align="center">'.$success.'</td><td width="20%">'.$memberTests[$x]['text'].'</td>';
		} else if ($numberOfTest == 4) {
			if ($subTestIndex == 3) {
				$html .= '</tr><tr>';
			}
			$html .= '<td width="10%" align="center">'.$success.'</td><td width="40%">'.$memberTests[$x]['text'].'</td>';
		}
	}
	$html .= '</tr></table>';
	return $html;
}

/**
 * This function returns the html for one stage of one category
 * $memberTests must be an array, like $courseMembers[$z]['csbalancetests'][2]
 */
function getHTMLForOneCategoryForOneStage($memberTests) {
	$startSubTest = false;
	$html = '<table border="1" cellpadding="1">';
	for ($x = 0; $x < count($memberTests); $x++) {
		if ($memberTests[$x]['type'] == 'SUBTEST') {
			// This is the first subtest
			if (!$startSubTest) {
				$html .= '</table>';
				$html .= getHTMLForSubTests($memberTests, $x);
				$startSubTest = true;
			} else {
				continue;
			}
		} else {
			if ($startSubTest) {
				$html .= '<table border="1" cellpadding="1">';
				$startSubTest = false;
			}

			if ($memberTests[$x]['success'] == 1) {
				$success = 'X';
			} else {
				$success = '';
			}
			$html .= '<tr><td width="10%" align="center">'.$success.'</td><td width="90%">'.$memberTests[$x]['text'].'</td></tr>';
		}
	}
	if (!$startSubTest) {
 		$html .= '</table>';
	}
	return utf8_decode($html);
}

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
	$data['data']['2'] = getMemberCanskateStageTests($mysqli, $memberid, $category, 2, $language)['data'];
	$data['data']['3'] = getMemberCanskateStageTests($mysqli, $memberid, $category, 3, $language)['data'];
	$data['data']['4'] = getMemberCanskateStageTests($mysqli, $memberid, $category, 4, $language)['data'];
	$data['data']['5'] = getMemberCanskateStageTests($mysqli, $memberid, $category, 5, $language)['data'];
	$data['data']['6'] = getMemberCanskateStageTests($mysqli, $memberid, $category, 6, $language)['data'];
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
			$row['csbalancetests'] 		= getMemberCanskateTests($mysqli, $row['memberid'], 'BALANCE', $language)['data'];
			$row['cscontroltests'] 		= getMemberCanskateTests($mysqli, $row['memberid'], 'CONTROL', $language)['data'];
			$row['csagilitytests'] 		= getMemberCanskateTests($mysqli, $row['memberid'], 'AGILITY', $language)['data'];

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
			$row['csbalancetests'] 		= getMemberCanskateTests($mysqli, $row['memberid'], 'BALANCE', $language)['data'];
			$row['cscontroltests'] 		= getMemberCanskateTests($mysqli, $row['memberid'], 'CONTROL', $language)['data'];
			$row['csagilitytests'] 		= getMemberCanskateTests($mysqli, $row['memberid'], 'AGILITY', $language)['data'];

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
