<?php
//============================================================+
// File name   : testSessionSchedule.php
// Begin       : 2017-02-23
// Last Update :
//
// Description : Test session schedule
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
$language = 'fr-ca';
$testsessionid = null;
if(isset($_GET['language']) && !empty(isset($_GET['language']))) {
	$language = $_GET['language'];
}
if(isset($_GET['testsessionid']) && !empty(isset($_GET['testsessionid']))) {
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
$pdf->SetFont('times', '', 10, '', true);
$data = getTestSession($mysqli, $language, $testsessionid);
if ($data['success'] == true && isset($data['data'])) {
	$testsession = $data['data'][0];
}

$filename = utf8_decode($testsession['testsessionlabel']).$l['w_title'].".pdf";

$data = getTestSessionRegistrations($mysqli, $language, $testsessionid);
if ($data['success'] == true && isset($data['data'])) {
	$registrations = $data['data'];

  $maxnboflines = 36;
  $lineNb = 0;

	$html = printPageHeader($testsession, $l);
	$html .= '<p align="center" style="font-size:14px"><b>'.$l['w_title_per_skater'].'</b></p>';
	$lineNb += 5;

  // For every registration of the test session
  for ($x = 0; $x < count($registrations); $x++) {
    $registration = $registrations[$x];
    $registrationid = $registration['id'];
    $data = getTestSessionRegistrationTests($mysqli, $language, $registrationid);
    if ($data['success'] == true && isset($data['data'])) {
    	$tests = $data['data'];
			// Check if we have enough lines left to print the skater, if not, change page
			if ($tests && $lineNb + 3 /*header*/ + count($tests) + 2 /*table header + 1 empty line */ >= $maxnboflines) {
			  // We need to change page here!
			  $pdf->AddPage();
			  $pdf->writeHTMLCell(0, 0, '', '', utf8_decode($html), 0, 1, 0, true, '', true);
			  $html = "";
			  $lineNb = 0;
			}
			$registration['fees'] = number_format($registration['fees'], 2, ".", " ");
			$registration['extrafees'] = number_format($registration['extrafees'], 2, ".", " ");
			$total = number_format($registration['fees'] + $registration['extrafees'], 2, ".", " ");

			$html .= '<table width="100%" cellspacing="1" cellpadding="3">';
			$html .=    '<tr>';
			$html .=      '<td width="20%"><b>'.$l['w_title_skater'].'</b></td>';
			$html .=      '<td width="20%"><b>'.$l['w_title_skatecanadano'].'</b></td>';
			$html .=      '<td width="17%"><b>'.$l['w_title_coach'].'</b></td>';
			$html .=      '<td width="15%"><b>'.$l['w_title_homeclub'].'</b></td>';
			$html .=      '<td width="9%"><b>'.$l['w_title_fees'].'</b></td>';
			$html .=      '<td width="10%"><b>'.$l['w_title_extrafees'].'</b></td>';
			$html .=      '<td width="9%"><b>'.$l['w_title_totalfees'].'</b></td>';
			$html .=    '</tr>';
			$html .=    '<tr>';
			$html .=      '<td>'.$registration['firstname'] . ' ' . $registration['lastname'].'</td>';
			$html .=      '<td>'.$registration['skatecanadano'].'</td>';
			$html .=      '<td>'.$registration['coachfirstname'].' '.$registration['coachlastname'].'</td>';
			$html .=      '<td>'.$registration['homeclublabel'].'</td>';
			$html .=      '<td>'.$registration['fees'].'</td>';
			$html .=      '<td>'.$registration['extrafees'].'</td>';
			$html .=      '<td>'.$total.'</td>';
			$html .=    '</tr>';
			$html .= '</table><br><br>';
			$lineNb += 3;

			$html .= '<table border="1" cellspacing="1" cellpadding="3" >';
			$html .=    '<tr>';
			$html .=      '<th><b>'.$l['w_title_typetest'].'</b></th>';
			$html .=      '<th><b>'.$l['w_title_test'].'</b></th>';
			$html .=      '<th><b>'.$l['w_title_music'].'</b></th>';
			$html .=      '<th><b>'.$l['w_title_partner'].'</b></th>';
			$html .=    '</tr>';
	    // For every test of the registration
	    for ($y = 0; $tests && $y < count($tests); $y++) {
	      $test = $tests[$y];
	      $testid = $test['id'];

	      $html .=    '<tr>';
	      $html .=    '<td>'.$test['testtypelabel'].($test['testtype'] == 'DANCE' ? ' ' . $test['testlevellabel'] : '').'</td>';
	      $html .=    '<td>'.$test['testlabel'].'</td>';
	      $html .=    '<td>'.($test['testtype'] == 'DANCE' ? $test['song'] : '--').'</td>';
	      $html .=    '<td>'.($test['testtype'] == 'DANCE' ? $test['partnerfirstname'].' '.$test['partnerlastname'] : '--').'</td>';
	      $html .=    '</tr>';
	    }
			$html .= '</table><br><br>';
			$lineNb += count($tests) + 2 /*table header + 1 empty line */ ;
		}
  }
	$pdf->AddPage();
	$pdf->writeHTMLCell(0, 0, '', '', utf8_decode($html), 0, 1, 0, true, '', true);
	$lineNb = 0;
}

$data = getTestSessionCoachesSummary($mysqli, $language, $testsessionid);
if ($data['success'] == true && isset($data['data'])) {
	$coaches = $data['data'];

  $maxnboflines = 37;
  $lineNb = 0;
	$grandtotal = 0.00;
	$totalfees = 0.00;
	$totalextrafees = 0.00;
	$totalnbrskaters = 0;
	$totalnbrtests = 0;
	$html = '';
	$html .= '<p align="center" style="font-size:14px"><b>'.$l['w_title_per_coach'].'</b></p>';

	$html .= '<table width="100%" cellspacing="1" cellpadding="3">';
	$html .=    '<tr>';
	$html .=      '<td width="25%"><b>'.$l['w_title_coach'].'</b></td>';
	$html .=      '<td width="20%" style="text-align:center"><b>'.$l['w_title_nbrskaters'].'</b></td>';
	$html .=      '<td width="10%" style="text-align:center"><b>'.$l['w_title_nbrtests'].'</b></td>';
	$html .=      '<td width="15%" style="text-align:right"><b>'.$l['w_title_fees'].'</b></td>';
	$html .=      '<td width="15%" style="text-align:right"><b>'.$l['w_title_extrafees'].'</b></td>';
	$html .=      '<td width="15%" style="text-align:right"><b>'.$l['w_title_totalfees'].'</b></td>';
	$html .=    '</tr>';
  // For every coach of the test session
  for ($x = 0; $x < count($coaches); $x++) {
    $coach = $coaches[$x];
		$coach['fees'] = number_format($coach['fees'], 2, ".", " ");
		$coach['extrafees'] = number_format($coach['extrafees'], 2, ".", " ");
		$total = number_format($coach['fees'] + $coach['extrafees'], 2, ".", " ");
		$grandtotal += $total;
		$totalfees += $coach['fees'];
		$totalextrafees += $coach['extrafees'];
		$totalnbrskaters += $coach['nbrskaters'];
		$totalnbrtests += $coach['nbrtests'];

		$html .=    '<tr>';
		$html .=      '<td>'.$coach['coachfirstname'].' '.$coach['coachlastname'].'</td>';
		$html .=      '<td style="text-align:center">'.$coach['nbrskaters'].'</td>';
		$html .=      '<td style="text-align:center">'.$coach['nbrtests'].'</td>';
		$html .=      '<td style="text-align:right">'.$coach['fees'].'</td>';
		$html .=      '<td style="text-align:right">'.$coach['extrafees'].'</td>';
		$html .=      '<td style="text-align:right">'.$total.'</td>';
		$html .=    '</tr>';
  }
	$grandtotal = number_format($grandtotal, 2, ".", " ");
	$totalfees = number_format($totalfees, 2, ".", " ");
	$totalextrafees = number_format($totalextrafees, 2, ".", " ");
	$html .=    '<tr>';
	$html .=      '<td><b>'.$l['w_title_total'].'</b></td>';
	$html .=      '<td style="text-align:center"><b>'.$totalnbrskaters.'</b></td>';
	$html .=      '<td style="text-align:center"><b>'.$totalnbrtests.'</b></td>';
	$html .=      '<td style="text-align:right"><b>'.$totalfees.'</b></td>';
	$html .=      '<td style="text-align:right"><b>'.$totalextrafees.'</b></td>';
	$html .=      '<td style="text-align:right"><b>'.$grandtotal.'</b></td>';
	$html .=    '</tr>';
	$html .= '</table><br><br>';
	$lineNb += 3;
	$pdf->AddPage();
	$pdf->writeHTMLCell(0, 0, '', '', utf8_decode($html), 0, 1, 0, true, '', true);
	$lineNb = 0;
}
// ---------------------------------------------------------

// Close and output PDF document
// This method has several options, check the source code documentation for more information.
$pdf->Output($filename, 'I');

//============================================================+
// Functions
//============================================================+
/**
 * This function gets the page header
 */
function printPageHeader($testsession, $l) {
  // Page header
  $html = '<p align="center" style="font-size:20px"><b>'.$testsession['testsessionlabel'].$l['w_title'].'</b></p><br>';
  // $html .= '<b style="font-size:14px">'.$fullname.'</b></p>';
  return $html;
}

/**
 * This function gets the test session
 */
function getTestSession($mysqli, $language, $testssessionid) {
  try {
		$data = array();
		$data['success'] = null;
		$query = "SELECT *, getTextLabel(cts.label, '$language') testsessionlabel
							FROM cpa_tests_sessions cts
							WHERE id = $testssessionid";
		$result = $mysqli->query($query);
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
}

/**
 * This function gets the registrations of the test session
 */
function getTestSessionRegistrations($mysqli, $language, $testssessionid) {
  try {
		$data = array();
		$data['success'] = null;
		$query = "SELECT ctsr.*, cm.firstname, cm.lastname, cm.skatecanadano, cm.homeclub, cm2.firstname coachfirstname, cm2.lastname coachlastname,
							getTextLabel(cc.label, '$language') homeclublabel, fees, extrafees
							FROM cpa_tests_sessions_registrations ctsr
							JOIN cpa_members cm ON cm.id = ctsr.memberid
							JOIN cpa_members cm2 ON cm2.id = ctsr.coachid
							JOIN cpa_clubs cc ON cc.code = cm.homeclub
							WHERE testssessionsid = $testssessionid
							ORDER BY cm.lastname, cm.firstname";
		$result = $mysqli->query($query);
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
}

/**
 * This function gets the tests of the registration of the test session
 */
function getTestSessionRegistrationTests($mysqli, $language, $testssessionsregistrationsid) {
  try {
		$data = array();
		$data['success'] = null;
		$query = "SELECT ctsrt.*, ctd.type testtype, getCodeDescription('testtypes', ctd.type, 'fr-ca') testtypelabel, getCodeDescription('testlevels', ctd.level, 'fr-ca') testlevellabel, getTextLabel(ct.label, 'fr-ca') testlabel, cm.firstname partnerfirstname, cm.lastname partnerlastname, cmu.song
							FROM cpa_tests_sessions_registrations_tests ctsrt
							JOIN cpa_tests ct ON ct.id = ctsrt.testsid
							JOIN cpa_tests_definitions ctd ON ctd.id = ct.testsdefinitionsid
							LEFT JOIN cpa_musics cmu ON cmu.id = ctsrt.musicid
							LEFT JOIN cpa_members cm ON cm.id = ctsrt.partnerid
							WHERE testssessionsregistrationsid = $testssessionsregistrationsid
							AND ctd.version = 1
							ORDER BY ctd.sequence";
		$result = $mysqli->query($query);
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
}

/**
 * This function gets the tests of the registration of the test session
 */
function getTestSessionCoachesSummary($mysqli, $language, $testssessionid) {
  try {
		$data = array();
		$data['success'] = null;
		$query = "select coachid, sum(fees) fees, sum(extrafees) extrafees, count(ctsr.id) nbrskaters, cm.firstname coachfirstname, cm.lastname coachlastname,
							(select count(*)
							 from cpa_tests_sessions_registrations_tests ctsrt
							join cpa_tests_sessions_registrations ctsr2 ON ctsr2.id = ctsrt.testssessionsregistrationsid
							WHERE ctsr2.coachid = ctsr.coachid and ctsr2.testssessionsid = $testssessionid) nbrtests
							FROM cpa_tests_sessions_registrations ctsr
							JOIN cpa_members cm ON cm.id = ctsr.coachid
							WHERE ctsr.testssessionsid = $testssessionid
							GROUP BY coachid
							ORDER BY cm.lastname, cm.firstname";
		$result = $mysqli->query($query);
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
}

//============================================================+
// END OF FILE
//============================================================+
