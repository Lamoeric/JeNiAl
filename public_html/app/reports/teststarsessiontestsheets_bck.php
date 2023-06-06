<?php
//============================================================+
// File name   : testStarSessionTestSheets.php
// Begin       : 2017-10-20
// Last Update :
//
// Description : Test sheets for new STAR 1 - 5 tests
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
// require_once('testSessionDanceTestSheet.php');
// require_once('testSessionFreeStyleTestSheet.php');
// require_once('testSessionSkillTestSheet.php');
require_once('testStarSessionTestSummarySheet.php');

set_time_limit(300);

// Input parameters
$testsessionid = null;
$language = 'fr-ca';
if( isset($_GET['language']) && !empty( isset($_GET['language']) ) ){
	$language = $_GET['language'];
}
if( isset($_GET['testsessionid']) && !empty( isset($_GET['testsessionid']) ) ){
	$testsessionid = $_GET['testsessionid'];
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
addCustomHeader($mysqli, $pdf, $language);

// set header and footer fonts
$pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
$pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));

// set default monospaced font
$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

// set margins
$pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
$pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
$pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

// set image scale factor
$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

// set default font subsetting mode
$pdf->setFontSubsetting(true);

// Set font
$pdf->SetFont('times', '', 10, '', true);

// set some language-dependent strings (optional)
if (@file_exists(dirname(__FILE__).'/lang/'.$language.'/'.basename(__FILE__))) {
	require_once(dirname(__FILE__).'/lang/'.$language.'/'.basename(__FILE__));
	$pdf->setLanguageArray($l);
}

$data = getTestSession($mysqli, $language, $testsessionid);
if ($data['success'] == true && isset($data['data'])) {
	$testsession = $data['data'][0];
}

// Set filename
$filename = utf8_decode($testsession['testsessionlabel']).$l['w_title'].".pdf";

try {
  $testDirectorInfo = getTestSessionDirectorInfo($mysqli, $testsessionid, $language)['data'][0];
} catch (Exception $e) {
  throw new Exception("Must have a member with the test director qualification for this report to work.");
}
$minlimit = 0;
$data = getTestSessionCoaches($mysqli, $language, $testsessionid, $minlimit);
if ($data['success'] == true && isset($data['data']) && count($data['data']) != 0) {
	$coaches = $data['data'];
	// echo $coaches;
	$data = getStarTestSessionTests($mysqli, $language, $testsessionid, $coaches);
	if ($data['success'] == true && isset($data['data'])) {
		$tests = $data['data'];
		// Print test summary for the session for this set of coaches
		testStarSessionTestSummarySheet($pdf, $tests, $coaches, $testDirectorInfo, $language, $l);
	}
}

// Close and output PDF document
// This method has several options, check the source code documentation for more information.
$pdf->Output($filename, 'I');

//============================================================+
// Functions
//============================================================+

/**
 * This function gets the test session
 */
function getTestSession($mysqli, $language, $testssessionid){
  try{
		$data = array();
		$data['success'] = null;
		$query = "SELECT *, getTextLabel(cns.label, '$language') testsessionlabel
							FROM cpa_newtests_sessions cns
							WHERE id = $testssessionid";
		$result = $mysqli->query( $query );
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
}

/**
 * This function gets the test session coaches
 */
function getTestSessionCoaches($mysqli, $language, $testssessionid, $minlimit){
  try{
		$maxlimit = $minlimit + 5;
		$data = array();
		$data['success'] = null;
		$query = "SELECT distinct coachid, skatecanadano, firstname, lastname
							FROM cpa_newtests_sessions_periods_registrations cnspr
							JOIN cpa_members cm ON cm.id = cnspr.coachid
							WHERE cnspr.newtestssessionsid = $testssessionid
							AND approbationstatus = 1
							ORDER BY coachid LIMIT " . $minlimit . ',' . $maxlimit;
		$result = $mysqli->query( $query );
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
}

/**
* This function gets the tests of a session
*/
function getStarTestSessionTests($mysqli, $language, $testsessionsid, $coaches){
  try{
    $data = array();
    $data['success'] = null;
		// $coachesstr = join(",", $coaches);
		$coachesstr = "";
		for ($x = 0; $x < count($coaches); $x++) {
			$coachesstr .= $coaches[$x]['coachid'];
			if ($x != count($coaches) - 1) {
				$coachesstr .= ',';
			}
		}

		$query = "SELECT cnspr.*, ctd.type, candidate.firstname canfirstname, candidate.lastname canlastname, getCodeDescription('homeclubs', candidate.homeclub, '$language') candidatehomeclublabel,
											coach.firstname coafirstname, coach.lastname coalastname,
											getCodeDescription('genders', candidate.gender, '$language') candidategenderlabel, candidate.gender cangender, candidate.skatecanadano,
											DATE_FORMAT(cnsp.perioddate,'%d') testday, DATE_FORMAT(cnsp.perioddate, '%m') testmonth, DATE_FORMAT(cnsp.perioddate, '%Y') testyear,
											partner.firstname parfirstname, partner.lastname parlastname,
											getTextLabel(ct.label, '$language') testlabel, getCodeDescription('startesttypes', ctd.type, '$language') testtypelabel,
											ctd.type testtype, ct.summarycode, ct.reportfilename, cc2.orgno candidateorgno, cnsc.amount fees,
											getCodeDescription('homeclubs', cns.homeclub, '$language') homeclublabel, cc.orgno
							FROM cpa_newtests_sessions_periods_registrations cnspr
							JOIN cpa_newtests_sessions_periods cnsp ON cnsp.id = cnspr.newtestssessionsperiodsid
							JOIN cpa_newtests_sessions cns ON cns.id = cnspr.newtestssessionsid
							JOIN cpa_members candidate ON candidate.id = cnspr.memberid
							JOIN cpa_clubs cc ON cc.code = cns.homeclub
							left JOIN cpa_clubs cc2 ON cc2.code = candidate.homeclub
							JOIN cpa_members coach ON coach.id = cnspr.coachid
							left JOIN cpa_members partner ON partner.id = cnspr.partnerid
							JOIN cpa_tests ct ON ct.id = cnspr.testid
							JOIN cpa_tests_definitions ctd ON ctd.id = ct.testsdefinitionsid
							JOIN cpa_newtests_sessions_charges cnsc ON cnsc.newtestssessionsid = cnspr.newtestssessionsid AND cnsc.chargecode = 'TEST'
							WHERE cnspr.newtestssessionsid = $testsessionsid
							AND approbationstatus = 1
							and cnspr.coachid in (" . $coachesstr . ")
							ORDER BY cnsp.perioddate, canlastname";

    $result = $mysqli->query( $query );
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
 * This function gets the test director info for a specific test session
 */
function getTestSessionDirectorInfo($mysqli, $testsessionid, $language){
	try{
		if(empty($testsessionid)) throw new Exception( "Invalid test session." );
		$query = "SELECT cns.*, director.firstname firstname, director.lastname lastname, director.skatecanadano, director.homephone, director.email
							FROM cpa_newtests_sessions cns
							JOIN cpa_members director ON director.id = cns.testdirectorid
							WHERE cns.id = $testsessionid";
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

//============================================================+
// END OF FILE
//============================================================+
