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
$sessionscoursesid = null;
$language = 'fr-ca';
$testsessionid = null;
if (isset($_GET['language']) && !empty(isset($_GET['language']))) {
	$language = $_GET['language'];
}
if (isset($_GET['testsessionid']) && !empty(isset($_GET['testsessionid']))) {
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

$data = getTestSessionDays($mysqli, $language, $testsessionid);
if ($data['success'] == true && isset($data['data'])) {
	$days = $data['data'];

  $maxnboflines = 37;
  $lineNb = 0;

  // For every day of the test session
  for ($x = 0; $x < count($days); $x++) {
    $day = $days[$x];
    $daysid = $day['id'];
    $data = getTestSessionPeriods($mysqli, $language, $daysid);
    if ($data['success'] == true && isset($data['data'])) {
    	$periods = $data['data'];
    } else {
      $periods = null;
    }
    // For every period of the day
    for ($y = 0; $periods && $y < count($periods); $y++) {
      $period = $periods[$y];
      $periodsid = $period['id'];

      $html = getPageHeader($testsession, $day, $period, $x, $y, $l);
      $lineNb += 5;

      $data = getTestSessionGroups($mysqli, $language, $testsessionid, $periodsid);
      if ($data['success'] == true && isset($data['data'])) {
      	$groups = $data['data'];
      } else {
        $groups = null;
      }
      // For every group of the period
      for ($z = 0; $groups && $z < count($groups); $z++) {
        $group = $groups[$z];
        $groupid = $group['id'];
        // Get the skaters for the group
        $data = getTestSessionGroupSkaters($mysqli, $language, $groupid);
        if ($data['success'] == true && isset($data['data'])) {
        	$skaters = $data['data'];
        } else {
          $skaters = null;
        }

        // Check if we have enough lines left to print the group, if not, change page
        if ($skaters && $lineNb + 3 /*header*/ + count($skaters) + 2 /*table header + 1 empty line */ > $maxnboflines) {
          // We need to change page here!
          $pdf->AddPage();
          $pdf->writeHTMLCell(0, 0, '', '', utf8_decode($html), 0, 1, 0, true, '', true);
          $html = "";
          $lineNb = 0;
        }

        $html .= '<b style="font-size:14px">'.$group['starttime']. ' - './*$l['w_title_group'].' '.*/$group['grouplabel'].'</b><br>';
        // $html .= $l['w_title_starttime'].' <b>'.$group['starttime'].'</b><br>';
        if ($group['warmupduration'] > 0) {
          $html .= '<br><b>'.$l['w_title_warmup'].' '.$group['warmupduration'].' min'. '</b><br><br>';
        }
        $lineNb += 3;

        if ($group['testtype'] == 'DANCE') {
          $html .= '<table border="1" cellspacing="1" cellpadding="3" >';
          $html .=    '<tr>';
          $html .=      '<th><b>'.$l['w_title_skater'].'</b></th>';
          $html .=      '<th><b>'.$l['w_title_music'].'</b></th>';
          $html .=      '<th><b>'.$l['w_title_partner'].'</b></th>';
          $html .=      '<th><b>'.$l['w_title_coach'].'</b></th>';
          $html .=    '</tr>';
          for ($a = 0; $skaters && $a < count($skaters); $a++) {
            $html .=    '<tr>';
            $html .=    '<td>'.$skaters[$a]['skaterfirstname'].' '.$skaters[$a]['skaterlastname'].'</td>';
            $html .=    '<td>'.$skaters[$a]['musiclabel'].'</td>';
            $html .=    '<td>'.$skaters[$a]['partnerfirstname'].' '.$skaters[$a]['partnerlastname'].'</td>';
            $html .=    '<td>'.$skaters[$a]['coachfirstname'].' '.$skaters[$a]['coachlastname'].'</td>';
            $html .=    '</tr>';
          }
          $lineNb += count($skaters) + 2 /*table header + 1 empty line */ ;
          $html .= '</table><br><br>';
        } else {
          $html .= '<table border="1" cellspacing="1" cellpadding="3" >';
          $html .=    '<tr>';
          $html .=      '<th><b>'.$l['w_title_skater'].'</b></th>';
          $html .=      '<th><b>'.$l['w_title_coach'].'</b></th>';
          $html .=    '</tr>';
          for ($a = 0; $skaters && $a < count($skaters); $a++) {
            $html .=    '<tr>';
            $html .=    '<td>'.$skaters[$a]['skaterfirstname'].' '.$skaters[$a]['skaterlastname'].'</td>';
            $html .=    '<td>'.$skaters[$a]['coachfirstname'].' '.$skaters[$a]['coachlastname'].'</td>';
            $html .=    '</tr>';
          }
          $lineNb += count($skaters) + 2 /*table header + 1 empty line */ ;
          $html .= '</table><br><br>';
        }
      }
      $pdf->AddPage();
      $pdf->writeHTMLCell(0, 0, '', '', utf8_decode($html), 0, 1, 0, true, '', true);
      $lineNb = 0;
    }
  }
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
function getPageHeader($testsession, $day, $period, $x, $y, $l) {
  $dayNo = $x+1;
  $periodNo = $y+1;
  $fullname = $period['arenalabel'] . ($period['icelabel'] && $period['icelabel'] != '' ? ' ' . $period['icelabel'] : '') . ' - ' . $day['testdatestr'] . ' - ' . $period['starttime'] . ' - ' . $period['endtime'];
  // Page header
  $html = '<p align="center" style="font-size:20px"><b>'.$testsession['testsessionlabel'].$l['w_title'].'</b><br>';
  $html .= '<b>'.$l['w_title_day'].' '.$dayNo.' '.$l['w_title_period'].' '.$periodNo.'</b><br>';
  $html .= '<b style="font-size:14px">'.$fullname.'</b></p>';
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
 * This function gets the days of the test session
 */
function getTestSessionDays($mysqli, $language, $testssessionid) {
  try {
		$data = array();
		$data['success'] = null;
		$query = "SELECT id, testssessionsid, testdate testdatestr
							FROM cpa_tests_sessions_days
							WHERE testssessionsid = $testssessionid
							order by testdate";
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
 * This function gets the periods of the day of the test session
 */
function getTestSessionPeriods($mysqli, $language, $testsdaysid) {
  try {
		$data = array();
		$data['success'] = null;
		$query = "SELECT ctp.*, (select getTextLabel(ca.label, '$language') from cpa_arenas ca where ca.id = ctp.arenaid) arenalabel,
										 (select getTextLabel(cai.label, '$language') from cpa_arenas_ices cai where cai.arenaid = ctp.arenaid and cai.id = ctp.iceid) icelabel
							FROM cpa_tests_sessions_days_periods ctp
							WHERE testsdaysid = $testsdaysid";
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
   * This function gets the schedule of a period of a day of a test session
   */
function getTestSessionGroups($mysqli, $language, $testssessionsid, $periodid) {
	try {
		$data = array();
		$data['success'] = null;
		$query = "SELECT ctsg.*, getTextLabel(ctsg.label, '$language') grouplabel, ctd.type testtype, getCodeDescription('testtypes', ctd.type, '$language') testtypelabel,
							getTextLabel(ct.label, '$language') testlabel, getCodeDescription('testlevels', ctd.level, '$language') testlevellabel
		-- , ctsd.testdate, getTextLabel(ca.label, '$language') arenalabel, getTextLabel(cai.label, '$language') icelabel, ctp.testsdaysjudgeid, ctp.starttime periodstarttime, ctp.endtime periodendtime
							FROM cpa_tests_sessions_groups ctsg
							JOIN cpa_tests_sessions_days_periods ctp ON ctp.id = ctsg.testperiodsid
							JOIN cpa_tests_sessions_days ctsd ON ctsd.id = ctp.testsdaysid
							JOIN cpa_tests ct ON ct.id = ctsg.testid
							JOIN cpa_tests_definitions ctd ON ctd.id = ct.testsdefinitionsid
							-- JOIN cpa_arenas ca ON ca.id = ctp.arenaid
							-- LEFT JOIN cpa_arenas_ices cai ON cai.id = ctp.iceid
							WHERE ctsd.testssessionsid = $testssessionsid
							AND ctsg.testperiodsid = $periodid
							ORDER BY ctsg.sequence";
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
};

/**
 * This function gets the skaters of a group
 */
function getTestSessionGroupSkaters($mysqli, $language, $testsessionsgroupsid) {
  try {
    $data = array();
    $data['success'] = null;
    $query = "SELECT ctsgs.*, ctsrt.partnerid, ctsrt.partnersteps, ctsrt.musicid, ctsrt.comments, cm.firstname skaterfirstname, cm.lastname skaterlastname,
                     cm2.firstname partnerfirstname, cm2.lastname partnerlastname, cm3.firstname coachfirstname, cm3.lastname coachlastname,
                     concat(cmu.song, ' - ', cmu.author) musiclabel
              FROM cpa_tests_sessions_groups_skaters ctsgs
              JOIN cpa_tests_sessions_registrations_tests ctsrt ON ctsrt.id = ctsgs.testssessionsregistrationstestsid
              JOIN cpa_tests_sessions_registrations ctsr ON ctsr.id = ctsrt.testssessionsregistrationsid
              JOIN cpa_members cm ON cm.id = ctsr.memberid
              LEFT JOIN cpa_members cm2 ON cm2.id = ctsrt.partnerid
              LEFT JOIN cpa_members cm3 ON cm3.id = ctsr.coachid
              LEFT JOIN cpa_musics cmu ON cmu.id = ctsrt.musicid
              WHERE ctsgs.testsessionsgroupsid = $testsessionsgroupsid
              ORDER BY sequence";
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
};
//============================================================+
// END OF FILE
//============================================================+
