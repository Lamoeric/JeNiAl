<?php
//============================================================+
// File name   : sessionCoursesList.php
// Begin       : 2016-07-07
// Last Update :
//
// Description : session course list - per course, list of skaters with DOB and sub level.
// 							 show number list - per number, list of skaters with DOB
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
require_once('getSessionLabel.php');
require_once('getShowLabel.php');

// Input parameters
$language = 'fr-ca';
$activeonly = false;
$eventType = 1; // 1 - Session 2 - Show
$sessionid = null;
$sessionscoursesid = null;
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
}
if (isset($_GET['showid']) && !empty(isset($_GET['showid']))) {
	$showid = $_GET['showid'];
	$eventType = 2;
}
if (isset($_GET['showsnumbersid']) && !empty(isset($_GET['showsnumbersid']))) {
	$showsnumbersid = $_GET['showsnumbersid'];
}
if (isset($_GET['activeonly']) && !empty(isset($_GET['activeonly']))) {
	$activeonly = $_GET['activeonly'];
}

//throw new Exception('sessionid : ' . $sessionid . ' - sessionscoursesid : ' . $sessionscoursesid . ' - showid : ' . $showid . ' - showsnumbersid : ' . $showsnumbersid  . ' - eventType : ' . $eventType);

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

// set default font subsetting mode
$pdf->setFontSubsetting(true);

// Set font
// dejavusans is a UTF-8 Unicode font, if you only need to
// print standard ASCII chars, you can use core fonts like
// helvetica or times to reduce file size.
$pdf->SetFont('times', '', 10, '', true);

$nboflinefirstpage = 41;
$nboflinenextpage  = 47;
$nboflineonpage  = 0;
if ($eventType == 1) { // Session
	$data = getSessionLabel($mysqli, $sessionid, $language);
	$sessionlabel = $data['data'][0]['sessionlabel'];
	$data = getSessionCoursesList($mysqli, $sessionid, $sessionscoursesid, $language, $activeonly);
	$coursesList = $data['data'];
	$tableheader = '<table border="1"><tr><td width="5%"><b>#</b></td><td width="25%"><b>'.$l['w_skatecanadano'].'</b></td><td width="25%"><b>'.$l['w_firstname'].'</b></td><td width="25%"><b>'.$l['w_lastname'].'</b></td><td width="15%"><b>'.$l['w_dob'].'</b></td><td width="10%"><b>'.$l['w_subleveltext'].'</b></td></tr>';
}
if ($eventType == 2) { // Show
	$data = getShowLabel($mysqli, $showid, $language);
	$sessionlabel = $data['data'][0]['showlabel'];
	$data = getShowNumberList($mysqli, $showid, $showsnumbersid, $language, $activeonly);
	$coursesList = $data['data'];
	$tableheader = '<table border="1"><tr><td width="5%"><b>#</b></td><td width="25%"><b>'.$l['w_skatecanadano'].'</b></td><td width="25%"><b>'.$l['w_firstname'].'</b></td><td width="25%"><b>'.$l['w_lastname'].'</b></td><td width="15%"><b>'.$l['w_dob'].'</b></td></tr>';
}

for ($x = 0; $x < count($coursesList); $x++) {
	$pageno = 1;
	$nboflineonpage  = 0;
	// Page header
	$pageheader1  = '<p align="center" style="font-size:20px"><b>'.$coursesList[$x]['sessionlabel'].' - '. ($activeonly==true ? $l['w_titleactive'] : $l['w_title']) .'</h1></b>';
	$pageheader2  = '<p align="center"><b>'.$coursesList[$x]['courselabel'].' '.$coursesList[$x]['courselevellabel'].' ('.$coursesList[$x]['name'].')'.'</b></p>';
	$pageheader2b = '<p align="center"><b>'.$coursesList[$x]['courselabel'].' '.$coursesList[$x]['courselevellabel'].' ('.$coursesList[$x]['name'].')'.' (xpagex)</b></p>';
	$pageheader3  = '<p align="center"><b>'.$coursesList[$x]['schedule'].'</b></p>';
	$html = $pageheader1.$pageheader2.$pageheader3;

	$html .= $tableheader;
	for ($y = 0; $y < count($coursesList[$x]['members']); $y++) {
		$index = $y + 1;
		if ($nboflineonpage != 0 && (($pageno == 1 && fmod($nboflineonpage, $nboflinefirstpage) == 0) || ($pageno > 1 && fmod($nboflineonpage, $nboflinenextpage) == 0))) {
			$html = $html .'</table>';
			$pdf->AddPage('P');
			$pdf->writeHTMLCell(0, 0, '', '', utf8_decode($html), 0, 1, 0, true, '', true);
			$pageno++;
			$nboflineonpage  = 0;
			$html = str_replace("xpagex", $pageno, $pageheader2b).$tableheader;
		}
		$nboflineonpage++;
		$member = $coursesList[$x]['members'][$y];
		$html .= '<tr><td width="5%">'.$index.'</td><td width="25%">'.$member['skatecanadano'].'</td><td width="25%">'.$member['firstname'].'</td><td width="25%">'.$member['lastname'].'</td><td width="15%">'.$member['birthday'].'</td>';
		if ($eventType == 1) {
			$html .= '<td width="10%">'.$member['sublevelcodelabel'].'</td></tr>';
		} else {
			$html .= '</tr>';
		}

	}
	$html = $html .'</table>';
	$pdf->AddPage('P');
	$pdf->writeHTMLCell(0, 0, '', '', utf8_decode($html), 0, 1, 0, true, '', true);
}

// ---------------------------------------------------------

// Close and output PDF document
// This method has several options, check the source code documentation for more information.
$filename = ($activeonly==true ? $l['w_filenameactive']  : $l['w_filename']);
$filename .= $sessionlabel . ".pdf";
$pdf->Output($filename, 'I');

//============================================================+
// END OF FILE
//============================================================+

/**
 * This function gets the details of all members for a session course
 */
function getSessionCourseMembers($mysqli, $sessionscoursesid, $language, $activeonly) {
	try{
		if (empty($sessionscoursesid)) throw new Exception("Invalid session course id.");
		$query = "SELECT cscm.*, cm.firstname, cm.lastname, cm.id memberid, cm.birthday, cm.skatecanadano, getTextLabel(cscs.label, '$language') sublevelcodelabel
							FROM cpa_sessions_courses_members cscm
							JOIN cpa_members cm ON cm.id = cscm.memberid
							JOIN cpa_sessions_courses csc ON csc.id = cscm.sessionscoursesid
							LEFT JOIN cpa_sessions_courses_sublevels cscs ON cscs.code = cscm.sublevelcode AND cscs.sessionscoursesid = cscm.sessionscoursesid
							WHERE cscm.sessionscoursesid = $sessionscoursesid
							AND cscm.membertype = 3 "
							. ($activeonly == true ? "AND cscm.registrationenddate is null ":"") .
							"ORDER BY cm.lastname";
		$result = $mysqli->query($query);
		$data = array();
		$data['data'] = array();
		while ($row = $result->fetch_assoc()) {
			$data['data'][] = $row;
		}
		$data['success'] = true;
		return $data;
	}catch (Exception $e) {
		$data = array();
		$data['success'] = false;
		$data['message'] = $e->getMessage();
		return $data;
	}
};

/**
 * This function gets the details of all members for a show number
 */
function getShowNumberMembers($mysqli, $showsnumbersid, $language, $activeonly) {
	try{
		if (empty($showsnumbersid)) throw new Exception("Invalid show number id.");
		$query = "SELECT cscm.*, cm.firstname, cm.lastname, cm.id memberid, cm.birthday, cm.skatecanadano, null as sublevelcodelabel
							FROM cpa_shows_numbers_members cscm
							JOIN cpa_members cm ON cm.id = cscm.memberid
							JOIN cpa_shows_numbers csn ON csn.id = cscm.numberid
							WHERE cscm.numberid = $showsnumbersid "
							. ($activeonly == true ? "AND cscm.registrationenddate is null ":"") .
							"ORDER BY cm.lastname";
		$result = $mysqli->query($query);
		$data = array();
		$data['data'] = array();
		while ($row = $result->fetch_assoc()) {
			$data['data'][] = $row;
		}
		$data['success'] = true;
		return $data;
	}catch (Exception $e) {
		$data = array();
		$data['success'] = false;
		$data['message'] = $e->getMessage();
		return $data;
	}
};

/**
 * This function gets all the numbers for the show
 */
function getShowNumberList($mysqli, $showid, $showsnumbersid, $language, $activeonly) {
	try{
		if (empty($showid)) throw new Exception("Invalid show id.");
		if (!isset($showsnumbersid) || $showsnumbersid == '') {
			$query = "SELECT csn.id, csn.name, '-' as maxnumberskater, getTextLabel(csn.label, '$language') courselabel, '' as courselevellabel, 
							 getTextLabel(cs.label, '$language') sessionlabel, getNumberSchedule(csc.id, '$language') AS schedule,
							 (select count(*) from cpa_shows_numbers_members where numberid = csn.id) nbofskater
						FROM cpa_shows_numbers csn
						JOIN cpa_shows cs ON cs.id = csn.showid
						WHERE cs.id = $showid
						ORDER BY name";
		} else {
			$query = "SELECT csn.id, csn.name, '-' as maxnumberskater, getTextLabel(csn.label, '$language') courselabel, '' as courselevellabel, 
							 getTextLabel(cs.label, '$language') sessionlabel, getNumberSchedule(csc.id, '$language') AS schedule,
							 (select count(*) from cpa_shows_numbers_members where numberid = csn.id) nbofskater
						FROM cpa_shows_numbers csn
						JOIN cpa_shows cs ON cs.id = csn.showid
						WHERE csn.id = $showsnumbersid
						ORDER BY name";
		}
		$result = $mysqli->query($query);
		$data = array();
		$data['data'] = array();
		while ($row = $result->fetch_assoc()) {
			$row['members'] = getShowNumberMembers($mysqli, $row['id'], $language, $activeonly)['data'];
			$data['data'][] = $row;
		}
		$data['success'] = true;
		return $data;
	}catch (Exception $e) {
		$data = array();
		$data['success'] = false;
		$data['message'] = $e->getMessage();
		return $data;
	}
};

/**
 * This function gets all the courses for the session
 */
function getSessionCoursesList($mysqli, $sessionid, $sessionscoursesid, $language, $activeonly) {
	try{
		if (empty($sessionid)) throw new Exception("Invalid session id.");
		if (!isset($sessionscoursesid) || $sessionscoursesid == '') {
			$query = "SELECT csc.id, csc.name, csc.maxnumberskater, getTextLabel(csc.label, '$language') courselabel, 
							 getTextLabel(ccl.label, '$language') courselevellabel, getTextLabel(cs.label, '$language') sessionlabel,
							 getCourseSchedule(csc.id, '$language') AS schedule,
							 (select count(*) from cpa_sessions_courses_members where sessionscoursesid = csc.id) nbofskater
						FROM cpa_sessions_courses csc
						JOIN cpa_courses cc ON cc.code = csc.coursecode
						LEFT JOIN cpa_courses_levels ccl ON ccl.coursecode = csc.coursecode and ccl.code = csc.courselevel
						JOIN cpa_sessions cs ON cs.id = csc.sessionid
						WHERE acceptregistrations = 1
						AND cs.id = $sessionid
						ORDER BY name";
		} else {
			$query = "SELECT csc.id, csc.name, csc.maxnumberskater, getTextLabel(csc.label, '$language') courselabel, 
							 getTextLabel(ccl.label, '$language') courselevellabel, getTextLabel(cs.label, '$language') sessionlabel,
							 getCourseSchedule(csc.id, '$language') AS schedule,
							 (select count(*) from cpa_sessions_courses_members where sessionscoursesid = csc.id) nbofskater
						FROM cpa_sessions_courses csc
						JOIN cpa_courses cc ON cc.code = csc.coursecode
						LEFT JOIN cpa_courses_levels ccl ON ccl.coursecode = csc.coursecode and ccl.code = csc.courselevel
						JOIN cpa_sessions cs ON cs.id = csc.sessionid
						WHERE acceptregistrations = 1
						AND csc.id = $sessionscoursesid
						ORDER BY name";
		}
		$result = $mysqli->query($query);
		$data = array();
		$data['data'] = array();
		while ($row = $result->fetch_assoc()) {
			$row['members'] = getSessionCourseMembers($mysqli, $row['id'], $language, $activeonly)['data'];
			$data['data'][] = $row;
		}
		$data['success'] = true;
		return $data;
	}catch (Exception $e) {
		$data = array();
		$data['success'] = false;
		$data['message'] = $e->getMessage();
		return $data;
	}
};
