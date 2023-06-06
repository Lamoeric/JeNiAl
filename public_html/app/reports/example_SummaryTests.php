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

$data = getTestSessionAllTestList($mysqli, $testsessionid, $language);
$testSessionSummaryTestList = $data['data'];

$border = 0;	// Set to 1 to display borders of all cells
$lineYCoord = [43,64,85,105.5,126.5,147.5,168,189,210,231];
$days = array();
for ($z = 0; $z < count($testSessionSummaryTestList); $z++) {
	$lineNo = -1;
	// $lineDeltas = 21;
	$totalPageFees = 0.0;
	array_push($days, array());
	//For all tests of a day
	for ($x = 0; $x < count($testSessionSummaryTestList[$z]['days']); $x++) {
		$lineNo++;
		if ($lineNo == 0 || $lineNo == 10) {
			// Reset everything and change page
			$lineNo = 0;
			$totalPageFees = 0.0;
			// add a page
			$pdf->AddPage();
			// get the current page break margin
			$bMargin = $pdf->getBreakMargin();
			// get current auto-page-break mode
			$auto_page_break = $pdf->getAutoPageBreak();
			// disable auto-page-break
			$pdf->SetAutoPageBreak(false, 0);
			// set bacground image
			$img_file = K_PATH_IMAGES.'reports/tests/summary/test_summary_'.$language.'.jpg';
			$pdf->Image($img_file, 0, 0, 210, 297, '', '', '', false, 300, '', false, false, 0);
			// restore auto-page-break status
			$pdf->SetAutoPageBreak($auto_page_break, $bMargin);
			// set the starting point for the page content
			$pdf->setPageMark();
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
		}
		// set font
		$pdf->SetFont('times', '', 10);

		// Instead of a footer, let's do a header
		$html = '<table><tr><td width="33%">JeNiAl</td><td width="33%" align="center"></td><td align="right">'.$pagenumtxt.'</td></tr></table>';
		$pdf->writeHTMLCell(0, 0, PDF_MARGIN_RIGHT, 5, utf8_decode($html), $border, 1, 1, true, 'L', true);

		$test = $testSessionSummaryTestList[$z]['days'][$x];
		$totalPageFees += (float) $test['fees'];
		// First line
		// # skate Canada
		$html = '<b>'.$test['skatecanadano'].'</b>';
		$pdf->writeHTMLCell(40, 0, 12, $lineYCoord[$lineNo], utf8_decode($html), $border, 1, 1, true, 'L', true);

		// Home Club
		$html = '<b>'.$test['candidatehomeclublabel'].'</b>';
		$pdf->writeHTMLCell(40, 0, 59, $lineYCoord[$lineNo], utf8_decode($html), $border, 1, 1, true, 'L', true);

		// Test code
		// Need to check for partner steps code
		// Steps executed
		$steps = '';
		if ($test['type'] == 'DANCE' && $test['partnersteps'] == 1) {
			$html = '<b>'.$test['partnerstepscode'].'</b>';
			$pdf->writeHTMLCell(25, 0, 103, $lineYCoord[$lineNo], utf8_decode($html), $border, 1, 1, true, 'L', true);
		} else {
			$html = '<b>'.$test['summarycode'].'</b>';
			$pdf->writeHTMLCell(25, 0, 103, $lineYCoord[$lineNo], utf8_decode($html), $border, 1, 1, true, 'L', true);
		}
		// Judge No
		$html = '<b>'.$test['judgeno'].'</b>';
		$pdf->writeHTMLCell(30, 0, 140, $lineYCoord[$lineNo], utf8_decode($html), $border, 1, 1, true, 'L', true);

		// Test date
		$html = '<b>'.$test['testday'].'/'.$test['testmonth'].'/'.$test['testyear'].'</b>';
		$pdf->writeHTMLCell(20, 0, 178, $lineYCoord[$lineNo], utf8_decode($html), $border, 1, 1, true, 'L', true);

		// Second line
		// Candidate
		$html = '<b>'.$test['canfirstname'].' '.$test['canlastname'].'</b>';
		$pdf->writeHTMLCell(50, 0, 12, $lineYCoord[$lineNo] + 8, utf8_decode($html), $border, 1, 1, true, 'L', true);

		// Organization number
		$html = '<b>'.$test['candidateorgno'].'</b>';
		$pdf->writeHTMLCell(30, 0, 70, $lineYCoord[$lineNo] + 8, utf8_decode($html), $border, 1, 1, true, 'L', true);

		// Fees
		$html = '<b>'.$test['fees'].'</b>';
		$pdf->writeHTMLCell(20, 0, 178, $lineYCoord[$lineNo] + 8, utf8_decode($html), $border, 1, 1, true, 'L', true);

		if ($lineNo == 9 || $x == count($testSessionSummaryTestList[$z]['days'])-1) {
			array_push($days[$z], $totalPageFees);
			//Page is done, write total
			// set font
			$pdf->SetFont('times', '', 12);
			// $html = '<b>'.$totalPageFees.'</b>';
			$html = '<b>'.'$'.number_format($totalPageFees, 2).'</b>';
			$pdf->writeHTMLCell(23, 0, 176, 248, utf8_decode($html), $border, 1, 1, true, 'L', true);
			$pdf->SetFont('times', '', 10);
		}

		if ($x == count($testSessionSummaryTestList[$z]['days'])-1) {
			// Day is done, write submission information
			// add a page
			$pdf->AddPage();
			// get the current page break margin
			$bMargin = $pdf->getBreakMargin();
			// get current auto-page-break mode
			$auto_page_break = $pdf->getAutoPageBreak();
			// disable auto-page-break
			$pdf->SetAutoPageBreak(false, 0);
			// set bacground image
			$img_file = K_PATH_IMAGES.'reports/tests/summary/test_submission_information_'.$language.'.jpg';
			$pdf->Image($img_file, 0, 0, 210, 297, '', '', '', false, 300, '', false, false, 0);
			// restore auto-page-break status
			$pdf->SetAutoPageBreak($auto_page_break, $bMargin);
			// set the starting point for the page content
			$pdf->setPageMark();
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

			// Test date
			$html = '<b>'.$test['testday'].'/'.$test['testmonth'].'/'.$test['testyear'].'</b>';
			$pdf->writeHTMLCell(32, 0, 165, 32, utf8_decode($html), $border, 1, 1, true, 'L', true);

			// Organization number
			$html = '<b>'.$test['orgno'].'</b>';
			$pdf->writeHTMLCell(30, 0, 60, 32, utf8_decode($html), $border, 1, 1, true, 'L', true);

			// Organization name
			$html = '<b>'.$test['homeclublabel'].'</b>';
			$pdf->writeHTMLCell(45, 0, 60, 38, utf8_decode($html), $border, 1, 1, true, 'L', true);

			for ($y = 0; $y < count($testSessionSummaryTestList[$z]['judges']); $y++) {
				$judge = $testSessionSummaryTestList[$z]['judges'][$y];
				// Jugde Skate Canada No
				$html = '<b>'.$judge ['skatecanadano'].'</b>';
				$pdf->writeHTMLCell(30, 0, 21, 61 + ($y * 5), utf8_decode($html), $border, 1, 1, true, 'L', true);
				// Jugde name
				$html = '<b>'.$judge ['firstname'].' '.$judge ['lastname'].'</b>';
				$pdf->writeHTMLCell(30, 0, 61, 61 + ($y * 5), utf8_decode($html), $border, 1, 1, true, 'L', true);
			}
			$testDirectorInfo = getTestSessionDirectorInfo($mysqli, $testsessionid, $language)['data'][0];

			$html = '<b>'.$testDirectorInfo['skatecanadano'].'</b>';
			$pdf->writeHTMLCell(30, 0, 162, 55.5, utf8_decode($html), $border, 1, 1, true, 'L', true);

			$html = '<b>'.$testDirectorInfo['firstname'].' '.$testDirectorInfo['lastname'].'</b>';
			$pdf->writeHTMLCell(35, 0, 162, 60.5, utf8_decode($html), $border, 1, 1, true, 'L', true);

			$html = '<b>'.$testDirectorInfo['homephone'].'</b>';
			$pdf->writeHTMLCell(35, 0, 162, 65.5, utf8_decode($html), $border, 1, 1, true, 'L', true);

			$html = '<b>'.$testDirectorInfo['email'].'</b>';
			$pdf->writeHTMLCell(35, 0, 162, 71, utf8_decode($html), $border, 1, 1, true, 'L', true);

			$totalDayFees = 0.0;
			for ($y = 0; $y < count($days[$z]); $y++) {
				$fees = $days[$z][$y];
				$totalDayFees += $fees;
				// Jugde Skate Canada No
				$html = '<b>'.'$'.number_format($fees, 2).'</b>';
				$pdf->writeHTMLCell(30, 0, 39, 98.5 + ($y * 5), utf8_decode($html), $border, 1, 1, true, 'L', true);
			}
			$html = '<b>'.'$'.number_format($totalDayFees, 2).'</b>';
			$pdf->writeHTMLCell(30, 0, 162, 151.5, utf8_decode($html), $border, 1, 1, true, 'L', true);
		}
	}
}


// ---------------------------------------------------------

//Close and output PDF document
$pdf->Output('TestSessionSummaryTestReport.pdf', 'I');

//============================================================+
//============================================================+

/**
 * This function gets the test director info for a specidic test session
 */
function getTestSessionDirectorInfo($mysqli, $testsessionid, $language){
	try{
		if(empty($testsessionid)) throw new Exception( "Invalid test session." );
		$query = "SELECT cts.*, director.firstname firstname, director.lastname lastname, director.skatecanadano, director.homephone, director.email
							FROM cpa_tests_sessions cts
							JOIN cpa_members director ON director.id = cts.testdirectorid
							WHERE cts.id = $testsessionid";
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
 * This function gets the list of judges for a specidic test session and test day
 */
function getTestSessionJudgeList($mysqli, $testdaysid, $language){
	try{
		if(empty($testdaysid)) throw new Exception( "Invalid test day." );
		$query = "SELECT ctdj.*, judge.firstname, judge.lastname, judge.skatecanadano
							FROM cpa_tests_sessions_days_periods_judges ctdj
							JOIN cpa_members judge ON judge.id = ctdj.judgesid
							WHERE testsdaysid = $testdaysid
							order by judgeno";
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
 * This function gets the tests for a specidic test session and test day
 */
function getTestSessionDayTestList($mysqli, $testsessionid, $testdaysid, $language){
	try{
		if(empty($testsessionid)) throw new Exception( "Invalid test session." );
		$query = "SELECT ctsgs.*, ctsg.testid, ct.name, ctd.type, ctd.level, getTextLabel(ct.label, '$language') testlabel, ct.reportfilename, getCodeDescription('homeclubs', cts.homeclub, '$language') homeclublabel, cc.orgno,
											candidate.firstname canfirstname, candidate.lastname canlastname, getCodeDescription('homeclubs', candidate.homeclub, '$language') candidatehomeclublabel,
											getCodeDescription('genders', candidate.gender, '$language') candidategenderlabel, candidate.gender cangender, candidate.skatecanadano,
											judge.firstname judfirstname, judge.lastname judlastname,
											DATE_FORMAT(ctsd.testdate,'%d') testday, DATE_FORMAT(ctsd.testdate, '%m') testmonth, DATE_FORMAT(ctsd.testdate, '%Y') testyear,
											coach.firstname coafirstname, coach.lastname coalastname, partner.firstname parfirstname, partner.lastname parlastname,
											ctsrt.partnersteps, ct.summarycode, ct.partnerstepscode, ctdj.judgeno, ctsrt.fees, cc2.orgno candidateorgno
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
							JOIN cpa_clubs cc2 ON cc2.code = candidate.homeclub
							JOIN cpa_tests_sessions_days_periods_judges ctdj ON ctdj.id = ctp.testsdaysjudgeid
							JOIN cpa_members judge ON judge.id = ctdj.judgesid
							JOIN cpa_members coach ON coach.id = ctsr.coachid
							left JOIN cpa_members partner ON partner.id = ctsrt.partnerid
							WHERE cts.id = $testsessionid
							AND ctp.testsdaysid = $testdaysid
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
 * This function gets the details of all test for all days for a test session
 */
function getTestSessionAllTestList($mysqli, $testsessionid, $language){
	try{
		if(empty($testsessionid)) throw new Exception( "Invalid test session." );
		$query = "SELECT *
							FROM cpa_tests_sessions_days
							WHERE testssessionsid = $testsessionid";
		$result = $mysqli->query( $query );
		$data = array();
		$data['data'] = array();
		while ($row = $result->fetch_assoc()) {
			$row['days'] = getTestSessionDayTestList($mysqli, $testsessionid, (int) $row['id'], $language)['data'];
			$row['judges'] = getTestSessionJudgeList($mysqli, (int) $row['id'], $language)['data'];
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
