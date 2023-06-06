<?php
//============================================================+
// File name   : testSessionTestSheets.php
// Begin       : 2016-07-07
// Last Update :
//
// Description : Test sheets for STAR test session
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
require_once('testsessiondancetestsheet.php');
require_once('testsessionfreestyletestsheet.php');
require_once('testsessionskilltestsheet.php');
require_once('testsessiontestsummarysheet.php');

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

$data = getTestSessionDays($mysqli, $language, $testsessionid);
if ($data['success'] == true && isset($data['data'])) {
	$days = $data['data'];
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
      $periodid = $period['id'];

      $data = getTestSessionPeriodJudges($mysqli, $language, $testsessionid, $periodid);
      if ($data['success'] == true && isset($data['data'])) {
        $judges = $data['data'];
      } else {
        $judges = null;
      }

      $html = getPeriodPageHeader($testsession, $day, $period, $x, $y, $l);

      $pdf->AddPage();
      $pdf->writeHTMLCell(0, 0, '', '', utf8_decode($html), 0, 1, 0, true, '', true);

      $data = getTestSessionGroups($mysqli, $language, $testsessionid, $periodid);
      if ($data['success'] == true && isset($data['data'])) {
      	$groups = $data['data'];
      } else {
        $groups = null;
      }
      // For every group of the period
      for ($z = 0; $groups && $z < count($groups); $z++) {
        $group = $groups[$z];
        $groupid = $group['id'];
        // one header page per group
        $html = getGroupPageHeader($mysqli, $language, $testsession, $day, $period, $group, $l);
        $pdf->AddPage();
        $pdf->writeHTMLCell(0, 0, '', '', utf8_decode($html), 0, 1, 0, true, '', true);

        // Get the tests for the group and pass to the proper function
        $data = getTestSessionGroupTests($mysqli, $language, $testsessionid, $groupid);
        if ($data['success'] == true && isset($data['data'])) {
        	$tests = $data['data'];
        } else {
          $tests = null;
        }
        for ($a = 0; $tests && $a < count($tests); $a++) {
          $test = $tests[$a];
          if ($test['type'] == 'DANCE') {
            testSessionDanceTestSheet($pdf, $test, $judges, $language, $l);
          }
          if ($test['type'] == 'FREE') {
            testSessionFreeStyleTestSheet($pdf, $test, $judges, $language, $l);
          }
          if ($test['type'] == 'SKILLS') {
            testSessionSkillTestSheet($pdf, $test, $judges, $language, $l, false);
            if ($a == count($tests)-1) {
              // For the last tests, print the second page of the skill test
              testSessionSkillTestSheet($pdf, $test, $judges, $language, $l, true);
            }
          }

        }

      }
      $data = getTestSessionPeriodTests($mysqli, $language, $testsessionid, $periodid);
      if ($data['success'] == true && isset($data['data'])) {
        $tests = $data['data'];
      } else {
        $tests = null;
      }
      // Print test summary for the period
      testSessionTestSummarySheet($pdf, $tests, $judges, $testDirectorInfo, $language, $l);
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
 * This function gets the page header for a period
 */
function getPeriodPageHeader($testsession, $day, $period, $x, $y, $l) {
  $dayNo = $x+1;
  $periodNo = $y+1;
  $fullname = $period['arenalabel'] . ($period['icelabel'] && $period['icelabel'] != '' ? ' ' . $period['icelabel'] : '') . ' - ' . $day['testdatestr'] . ' - ' . $period['starttime'] . ' - ' . $period['endtime'];
  // Page header
  $html = '<p align="center" style="font-size:20px"><b>'.$testsession['testsessionlabel'].'</b><br>';
  $html .= '<b>'.$l['w_title_day'].' '.$dayNo.' '.$l['w_title_period'].' '.$periodNo.'</b><br>';
  $html .= '<b style="font-size:14px">'.$fullname.'</b></p>';
  return $html;
}

/**
 * This function gets the page header for a group
 */
function getGroupPageHeader($mysqli, $language, $testsession, $day, $period, $group, $l) {
   $fullname = $group['starttime'] . ' - ' . $group['endtime'];
  // Page header
  $html = '<p align="center" style="font-size:20px"><b>'.$group['grouplabel'].'</b><br>';
  $html .= '<b style="font-size:14px">'.$fullname.'</b></p>';

  // Get the skaters for the group
  $data = getTestSessionGroupSkaters($mysqli, $language, $group['id']);
  if ($data['success'] == true && isset($data['data'])) {
    $skaters = $data['data'];
  } else {
    $skaters = null;
  }

  $html .= '<b style="font-size:14px">'.$group['starttime']. ' - './*$l['w_title_group'].' '.*/$group['grouplabel'].'</b><br>';
  // $html .= $l['w_title_starttime'].' <b>'.$group['starttime'].'</b><br>';
  if ($group['warmupduration'] > 0) {
    $html .= '<br><b>'.$l['w_title_warmup'].' '.$group['warmupduration'].' min'. '</b><br><br>';
  }

  if ($group['testtype'] == 'DANCE') {
    $html .= '<table border="1" cellspacing="1" cellpadding="3" width="100%">';
    $html .=    '<tr>';
    $html .=      '<th width="5%"></th>';
    $html .=      '<th width="25%"><b>'.$l['w_title_skater'].'</b></th>';
    $html .=      '<th width="25%"><b>'.$l['w_title_music'].'</b></th>';
    $html .=      '<th width="25%"><b>'.$l['w_title_partner'].'</b></th>';
    $html .=      '<th width="20%"><b>'.$l['w_title_coach'].'</b></th>';
    $html .=    '</tr>';
    for ($a = 0; $skaters && $a < count($skaters); $a++) {
      $html .=    '<tr>';
      $html .=      '<td></td>';
      $html .=      '<td>'.$skaters[$a]['skaterfirstname'].' '.$skaters[$a]['skaterlastname'].'</td>';
      $html .=      '<td>'.$skaters[$a]['musiclabel'].'</td>';
      $html .=      '<td>'.$skaters[$a]['partnerfirstname'].' '.$skaters[$a]['partnerlastname'].'</td>';
      $html .=      '<td>'.$skaters[$a]['coachfirstname'].' '.$skaters[$a]['coachlastname'].'</td>';
      $html .=    '</tr>';
    }
    $html .= '</table><br><br>';
  } else {
    $html .= '<table border="1" cellspacing="1" cellpadding="3" width="100%">';
    $html .=    '<tr>';
    $html .=      '<th width="5%"></th>';
    $html .=      '<th width="45%"><b>'.$l['w_title_skater'].'</b></th>';
    $html .=      '<th width="50%"><b>'.$l['w_title_coach'].'</b></th>';
    $html .=    '</tr>';
    for ($a = 0; $skaters && $a < count($skaters); $a++) {
      $html .=  '<tr>';
      $html .=    '<td></td>';
      $html .=    '<td>'.$skaters[$a]['skaterfirstname'].' '.$skaters[$a]['skaterlastname'].'</td>';
      $html .=    '<td>'.$skaters[$a]['coachfirstname'].' '.$skaters[$a]['coachlastname'].'</td>';
      $html .=  '</tr>';
    }
    $html .= '</table><br><br>';
  }

  return $html;
}

/**
 * This function gets the test session
 */
function getTestSession($mysqli, $language, $testssessionid){
  try{
		$data = array();
		$data['success'] = null;
		$query = "SELECT *, getTextLabel(cts.label, '$language') testsessionlabel
							FROM cpa_tests_sessions cts
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
 * This function gets the days of the test session
 */
function getTestSessionDays($mysqli, $language, $testssessionid){
  try{
		$data = array();
		$data['success'] = null;
		$query = "SELECT id, testssessionsid, testdate testdatestr
							FROM cpa_tests_sessions_days
							WHERE testssessionsid = $testssessionid
							order by testdate";
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
 * This function gets the periods of the day of the test session
 */
function getTestSessionPeriods($mysqli, $language, $testsdaysid){
  try{
		$data = array();
		$data['success'] = null;
		$query = "SELECT ctp.*, (select getTextLabel(ca.label, '$language') from cpa_arenas ca where ca.id = ctp.arenaid) arenalabel,
										 (select getTextLabel(cai.label, '$language') from cpa_arenas_ices cai where cai.arenaid = ctp.arenaid and cai.id = ctp.iceid) icelabel
							FROM cpa_tests_sessions_days_periods ctp
							WHERE testsdaysid = $testsdaysid";
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
 * This function gets the schedule of a period of a day of a test session
*/
function getTestSessionGroups($mysqli, $language, $testssessionsid, $periodid){
	try{
		$data = array();
		$data['success'] = null;
		$query = "SELECT ctsg.*, getTextLabel(ctsg.label, '$language') grouplabel, ctd.type testtype, getCodeDescription('testtypes', ctd.type, '$language') testtypelabel,
							getTextLabel(ct.label, '$language') testlabel, getCodeDescription('testlevels', ctd.level, '$language') testlevellabel
							FROM cpa_tests_sessions_groups ctsg
							JOIN cpa_tests_sessions_days_periods ctp ON ctp.id = ctsg.testperiodsid
							JOIN cpa_tests_sessions_days ctsd ON ctsd.id = ctp.testsdaysid
							JOIN cpa_tests ct ON ct.id = ctsg.testid
							JOIN cpa_tests_definitions ctd ON ctd.id = ct.testsdefinitionsid
							WHERE ctsd.testssessionsid = $testssessionsid
							AND ctsg.testperiodsid = $periodid
							AND ctd.version = 1
							ORDER BY ctsg.sequence";
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
 * This function gets the schedule of a period of a day of a test session
*/
function getTestSessionPeriodJudges($mysqli, $language, $testssessionsid, $periodid){
	try{
		$data = array();
		$data['success'] = null;
		$query = "SELECT ctsdpj.*, cm.firstname, cm.lastname, cm.skatecanadano
              FROM cpa_tests_sessions_days_periods_judges ctsdpj
              JOIN cpa_members cm ON cm.id = ctsdpj.judgesid
              WHERE testperiodsid = $periodid;";
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
* This function gets the tests of a group
*/
function getTestSessionGroupTests($mysqli, $language, $testsessionsid, $groupid){
  try{
    $data = array();
    $data['success'] = null;
    $query = "SELECT ctsgs.*, ctsg.testid, ct.name, ctd.type, ctd.level, getTextLabel(ct.label, '$language') testlabel, ct.reportfilename,
											getTextLabel(cc.label, '$language') homeclublabel, cc.orgno,
											candidate.firstname canfirstname, candidate.lastname canlastname,
											getTextLabel(cc2.label, '$language') candidatehomeclublabel,
											getCodeDescription('genders', candidate.gender, '$language') candidategenderlabel, candidate.gender cangender,
											DATE_FORMAT(ctsd.testdate,'%d') testday, DATE_FORMAT(ctsd.testdate, '%m') testmonth, DATE_FORMAT(ctsd.testdate, '%Y') testyear,
											coach.firstname coafirstname, coach.lastname coalastname, partner.firstname parfirstname, partner.lastname parlastname,
											ctsrt.partnersteps
							FROM cpa_tests_sessions_groups_skaters ctsgs
							JOIN cpa_tests_sessions_groups ctsg ON ctsg.id = ctsgs.testsessionsgroupsid
							JOIN cpa_tests_sessions_days_periods ctsdp ON ctsdp.id = ctsg.testperiodsid
							JOIN cpa_tests_sessions_days ctsd ON ctsd.id = ctsdp.testsdaysid
							JOIN cpa_tests_sessions cts on cts.id = ctsd.testssessionsid
							JOIN cpa_tests ct ON ct.id = ctsg.testid
							JOIN cpa_tests_definitions ctd ON ctd.id = ct.testsdefinitionsid
							JOIN cpa_clubs cc ON cc.code = cts.homeclub
							JOIN cpa_tests_sessions_registrations_tests ctsrt ON ctsrt.id = ctsgs.testssessionsregistrationstestsid
							JOIN cpa_tests_sessions_registrations ctsr ON ctsr.id = ctsrt.testssessionsregistrationsid
							JOIN cpa_members candidate ON candidate.id = ctsr.memberid
							JOIN cpa_members coach ON coach.id = ctsr.coachid
							left JOIN cpa_members partner ON partner.id = ctsrt.partnerid
							LEFT JOIN cpa_clubs cc2 ON cc2.code = candidate.homeclub
							WHERE cts.id = $testsessionsid
              and ctsg.id = $groupid
							AND ctd.version = 1
							order by ctsd.testdate, ctsg.sequence, ctsgs.sequence";
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
* This function gets the tests of a group
*/
function getTestSessionPeriodTests($mysqli, $language, $testsessionsid, $periodid){
  try{
    $data = array();
    $data['success'] = null;
    $query = "SELECT ctsgs.*, ctsg.testid, ct.name, ctd.type, ctd.level, getTextLabel(ct.label, '$language') testlabel, ct.reportfilename,
											getTextLabel(cc.label, '$language') homeclublabel, cc.orgno,
											candidate.firstname canfirstname, candidate.lastname canlastname,
											getTextLabel(cc2.label, '$language') candidatehomeclublabel,
											getCodeDescription('genders', candidate.gender, '$language') candidategenderlabel, candidate.gender cangender, candidate.skatecanadano,
											DATE_FORMAT(ctsd.testdate,'%d') testday, DATE_FORMAT(ctsd.testdate, '%m') testmonth, DATE_FORMAT(ctsd.testdate, '%Y') testyear,
											coach.firstname coafirstname, coach.lastname coalastname, partner.firstname parfirstname, partner.lastname parlastname,
											ctsrt.partnersteps, ct.summarycode, ct.partnerstepscode, cc2.orgno candidateorgno, ctsc.amount fees
							FROM cpa_tests_sessions_groups_skaters ctsgs
							JOIN cpa_tests_sessions_groups ctsg ON ctsg.id = ctsgs.testsessionsgroupsid
							JOIN cpa_tests_sessions_days_periods ctsdp ON ctsdp.id = ctsg.testperiodsid
							JOIN cpa_tests_sessions_days ctsd ON ctsd.id = ctsdp.testsdaysid
							JOIN cpa_tests_sessions cts on cts.id = ctsd.testssessionsid
							JOIN cpa_tests ct ON ct.id = ctsg.testid
							JOIN cpa_tests_definitions ctd ON ctd.id = ct.testsdefinitionsid
							JOIN cpa_clubs cc ON cc.code = cts.homeclub
							JOIN cpa_tests_sessions_registrations_tests ctsrt ON ctsrt.id = ctsgs.testssessionsregistrationstestsid
							JOIN cpa_tests_sessions_registrations ctsr ON ctsr.id = ctsrt.testssessionsregistrationsid
							JOIN cpa_members candidate ON candidate.id = ctsr.memberid
              JOIN cpa_clubs cc2 ON cc2.code = candidate.homeclub
							JOIN cpa_members coach ON coach.id = ctsr.coachid
							left JOIN cpa_members partner ON partner.id = ctsrt.partnerid
							JOIN cpa_tests_sessions_charges ctsc ON ctsc.testssessionsid = cts.id AND ctsc.chargecode = 'TEST'
							WHERE cts.id = $testsessionsid
              and ctsdp.id = $periodid
							AND ctd.version = 1
							order by ctsd.testdate, ctsg.sequence, ctsgs.sequence";
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
 * This function gets the skaters of a group
 */
function getTestSessionGroupSkaters($mysqli, $language, $testsessionsgroupsid){
  try{
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


//============================================================+
// END OF FILE
//============================================================+
