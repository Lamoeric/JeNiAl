<?php
//============================================================+
// File name   : testSessionDanceTestEvaluation.php
// Begin       : 2016-07-07
// Last Update :
//
// Description : session course list - per course, list of skaters with DOB and sub level.
//
// Author: Eric Lamoureux
//
//============================================================+

require_once('../../../private/'. $_SERVER['HTTP_HOST'].'/include/config.php');
require_once('../../include/nocache.php');
set_time_limit(300);

if( isset($_GET['language']) && !empty( isset($_GET['language']) ) ){
	$language = $_GET['language'];
} else {
	$language = 'en-ca';
}
if( isset($_GET['testsessionid']) && !empty( isset($_GET['testsessionid']) ) ){
	$testsessionid = $_GET['testsessionid'];
}

if( isset($_GET['testsessionsgroupsid']) && !empty( isset($_GET['testsessionsgroupsid']) ) ){
	$testsessionsgroupsid = $_GET['testsessionsgroupsid'];
}

// Include the main TCPDF library (search for installation path).
require_once('../../include/tcpdf_include.php');
require_once('mypdf_footer.php');


// create new PDF document
//$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
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

if (!isset($testsessionsgroupsid)) {
	$data = getTestSessionDanceTestList($mysqli, $testsessionid, $language);
	$testSessionSkillTestList = $data['data'];
} else {
	$data = getTestSessionGroupDanceTestList($mysqli, $testsessionid, $testsessionsgroupsid, $language);
	// $data = getSessionCourseMembers($mysqli, $sessionscoursesid, $language);
	$testSessionSkillTestList = $data['data'];
}

for ($z = 0; $z < count($testSessionSkillTestList); $z++) {
	// add a page
	$pdf->AddPage();
	// get the current page break margin
	$bMargin = $pdf->getBreakMargin();
	// get current auto-page-break mode
	$auto_page_break = $pdf->getAutoPageBreak();
	// disable auto-page-break
	$pdf->SetAutoPageBreak(false, 0);
	// set bacground image
	$img_file = K_PATH_IMAGES.'reports/tests/'.$testSessionSkillTestList[$z]['reportfilename'].'_page1_'.$language.'.jpg';
	$pdf->Image($img_file, 0, 0, 210, 297, '', '', '', false, 300, '', false, false, 0);
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
	$html = '<table><tr><td width="33%">JeNiAl</td><td width="33%" align="center"></td><td align="right">'.$pagenumtxt.'</td></tr></table>';
	// Causes an error in the PDF
	// $pdf->setPageMark();

	// set color for background
	$pdf->SetFillColor(255, 255, 255);

	// set font
	$pdf->SetFont('times', '', 10);

	$pdf->SetFont('times', '', 10);
	$border = 0;

	// Instead of a footer, let's do a header
	$html = '<table><tr><td width="33%">JeNiAl</td><td width="33%" align="center">'.$testSessionSkillTestList[$z]['testlabel'].'</td><td align="right">'.$pagenumtxt.'</td></tr></table>';
	$pdf->writeHTMLCell(0, 0, PDF_MARGIN_RIGHT, 5, utf8_decode($html), $border, 1, 1, true, 'L', true);

	// Test date
	$pdf->SetFont('times', '', 12);
	$html = '<b>'.$testSessionSkillTestList[$z]['testday'].'&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'.$testSessionSkillTestList[$z]['testmonth'].'&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'.$testSessionSkillTestList[$z]['testyear'].'</b>';
	$pdf->writeHTMLCell(35, 0, 168, 28, utf8_decode($html), $border, 1, 1, true, 'L', true);


	$pdf->SetFont('times', '', 10);
	// Home club of the test
	$html = '<b>'.$testSessionSkillTestList[$z]['homeclublabel'].'</b>';
	$pdf->writeHTMLCell(60, 0, 47, 35.5, utf8_decode($html), $border, 1, 1, true, 'L', true);

	// Candidate
	$html = '<b>'.$testSessionSkillTestList[$z]['canfirstname'].' '.$testSessionSkillTestList[$z]['canlastname'].'</b>';
	$pdf->writeHTMLCell(80, 0, 30, 42, utf8_decode($html), $border, 1, 1, true, 'L', true);

	// Home club of the candidate
	$html = '<b>'.$testSessionSkillTestList[$z]['candidatehomeclublabel'].'</b>';
	$pdf->writeHTMLCell(60, 0, 63, 49, utf8_decode($html), $border, 1, 1, true, 'L', true);

	// Coach
	$html = '<b>'.$testSessionSkillTestList[$z]['coafirstname'].' '.$testSessionSkillTestList[$z]['coalastname'].'</b>';
	$pdf->writeHTMLCell(60, 0, 145, 42, utf8_decode($html), $border, 1, 1, true, 'L', true);

	// Evaluator
	$html = '<b>'.$testSessionSkillTestList[$z]['judfirstname'].' '.$testSessionSkillTestList[$z]['judlastname'].'</b>';
	$pdf->writeHTMLCell(60, 0, 145, 49, utf8_decode($html), $border, 1, 1, true, 'L', true);

	// Bottom part of the sheet
	// Candidate
	$html = '<b>'.$testSessionSkillTestList[$z]['canfirstname'].' '.$testSessionSkillTestList[$z]['canlastname'].'</b>';
	$pdf->writeHTMLCell(60, 0, 28, 91, utf8_decode($html), $border, 1, 1, true, 'L', true);

	// Evaluator
	$html = '<b>'.$testSessionSkillTestList[$z]['judfirstname'].' '.$testSessionSkillTestList[$z]['judlastname'].'</b>';
	$pdf->writeHTMLCell(50, 0, 116, 91, utf8_decode($html), $border, 1, 1, true, 'L', true);

	$pdf->SetFont('times', '', 12);
	$html = '<b>'.$testSessionSkillTestList[$z]['testday'].'&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'.$testSessionSkillTestList[$z]['testmonth'].'&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'.$testSessionSkillTestList[$z]['testyear'].'</b>';
	$pdf->writeHTMLCell(35, 0, 168, 90, utf8_decode($html), $border, 1, 1, true, 'L', true);

	// add a page
	$pdf->AddPage();
	// get the current page break margin
	$bMargin = $pdf->getBreakMargin();
	// get current auto-page-break mode
	$auto_page_break = $pdf->getAutoPageBreak();
	// disable auto-page-break
	$pdf->SetAutoPageBreak(false, 0);
	// set bacground image
	$img_file = K_PATH_IMAGES.'reports/tests/'.$testSessionSkillTestList[$z]['reportfilename'].'_page2_'.$language.'.jpg';
	$pdf->Image($img_file, 0, 0, 210, 297, '', '', '', false, 300, '', false, false, 0);
	// restore auto-page-break status
	$pdf->SetAutoPageBreak($auto_page_break, $bMargin);
	// set the starting point for the page content
	$pdf->setPageMark();
}


// ---------------------------------------------------------

//Close and output PDF document
$pdf->Output('TestSessionSkillTestReport.pdf', 'I');

//============================================================+
//============================================================+


/**
 * This function gets the details of all members for a session course
 */
function getTestSessionDanceTestList($mysqli, $testsessionid, $language){
	try{
		if(empty($testsessionid)) throw new Exception( "Invalid test session." );
		$query = "SELECT ctsgs.*, ctsg.testid, ct.name, ctd.type, ctd.level, getTextLabel(ct.label, '$language') testlabel, ct.reportfilename, getCodeDescription('homeclubs', cts.homeclub, '$language') homeclublabel, cc.orgno,
											candidate.firstname canfirstname, candidate.lastname canlastname, getCodeDescription('homeclubs', candidate.homeclub, '$language') candidatehomeclublabel,
											getCodeDescription('genders', candidate.gender, '$language') candidategenderlabel, candidate.gender cangender,
											judge.firstname judfirstname, judge.lastname judlastname,
											DATE_FORMAT(ctsd.testdate,'%d') testday, DATE_FORMAT(ctsd.testdate, '%m') testmonth, DATE_FORMAT(ctsd.testdate, '%Y') testyear,
											coach.firstname coafirstname, coach.lastname coalastname, partner.firstname parfirstname, partner.lastname parlastname,
											ctsrt.partnersteps
							FROM cpa_tests_sessions_groups_skaters ctsgs
							JOIN cpa_tests_sessions_groups ctsg ON ctsg.id = ctsgs.testsessionsgroupsid
							JOIN cpa_tests_sessions_days_periods ctp ON ctp.id = ctsg.testperiodsid
							JOIN cpa_tests_sessions_days ctsd ON ctsd.id = ctp.testsdaysid
							JOIN cpa_tests_sessions cts on cts.id = ctsd.testssessionsid
							JOIN cpa_tests ct ON ct.id = ctsg.testid
							JOIN cpa_tests_definitions ctd ON ctd.id = ct.testsdefinitionsid
							JOIN cpa_clubs cc ON cc.code = cts.homeclub
							JOIN cpa_tests_sessions_registrations_tests ctsrt ON ctsrt.id = ctsgs.testssessionsregistrationstestsid
							JOIN cpa_tests_sessions_registrations ctsr ON ctsr.id = ctsrt.testssessionsregistrationsid
							JOIN cpa_members candidate ON candidate.id = ctsr.memberid
							JOIN cpa_tests_sessions_days_periods_judges ctdj ON ctdj.id = ctp.testsdaysjudgeid
							JOIN cpa_members judge ON judge.id = ctdj.judgesid
							JOIN cpa_members coach ON coach.id = ctsr.coachid
							left JOIN cpa_members partner ON partner.id = ctsrt.partnerid
							WHERE cts.id = $testsessionid
							AND ctd.type = 'SKILLS'
							AND ctd.version = 1
							order by ctsd.testdate, ctsg.starttime, ctsgs.order";
		$result = $mysqli->query( $query );
		$data = array();
		$data['data'] = array();
		while ($row = $result->fetch_assoc()) {
			$data['data'][] = $row;
		}
		$data['success'] = true;
		return $data;
	}catch (Exception $e){
		$data = array();
		$data['success'] = false;
		$data['message'] = $e->getMessage();
		return $data;
	}
};

/**
 * This function gets the details of one member
 */
function getMemberDetails($mysqli, $memberid, $language){
	try{
		if(empty($memberid)) throw new Exception( "Invalid member." );
		$query = "SELECT cm.firstname, cm.lastname, cm.id memberid, cm.skatecanadano
							FROM cpa_members cm
							WHERE id = $memberid";
		$result = $mysqli->query( $query );
		$data = array();
		$data['data'] = array();
		while ($row = $result->fetch_assoc()) {
			$data['data'][] = $row;
		}
		$data['success'] = true;
		return $data;
	}catch (Exception $e){
		$data = array();
		$data['success'] = false;
		$data['message'] = $e->getMessage();
		return $data;
	}
};
