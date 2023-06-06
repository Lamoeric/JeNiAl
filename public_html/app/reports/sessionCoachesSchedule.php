<?php
//============================================================+
// File name   : sessionCoachesSchedule.php
// Begin       : 2016-09-01
// Last Update :
//
// Description : session coaches and APs schedule
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

//echo K_PATH_PRIVATEIMAGES;

$language = 'fr-ca';
$sessionid = null;
if (isset($_GET['language']) && !empty(isset($_GET['language']))) {
	$language = $_GET['language'];
}
if (isset($_GET['sessionid']) && !empty(isset($_GET['sessionid']))) {
	$sessionid = $_GET['sessionid'];
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

// TODO : could we use this for the language dependant configuration ??????

// set some language-dependent strings (optional)
if (@file_exists(dirname(__FILE__).'/lang/'.$language.'/'.basename(__FILE__))) {
	require_once(dirname(__FILE__).'/lang/'.$language.'/'.basename(__FILE__));
	$pdf->setLanguageArray($l);
}

// ---------------------------------------------------------

// set default font subsetting mode
$pdf->setFontSubsetting(true);

// Set font
// dejavusans is a UTF-8 Unicode font, if you only need to
// print standard ASCII chars, you can use core fonts like
// helvetica or times to reduce file size.
$pdf->SetFont('times', '', 10, '', true);

$nboflinefirstpage = 47;
$nboflinenextpage  = 47;
$nboflineonpage  = 0;
$sessionlabel = getSessionLabel($mysqli, $sessionid, $language)['data'][0]['sessionlabel'];
$data = getSchedule($mysqli, $sessionid, $language);
$coursesList = $data['data'];

$tableheader = '<table border="1"><tr><td width="25%"><b>'.$l['w_schedule'].'</b></td><td width="25%"><b>'.$l['w_coursename'].'</b></td><td width="25%"><b>'.$l['w_coachname'].'</b></td><td width="25%"><b>'.$l['w_PAname'].'</b></td></tr>';
$pageheader  = '<p align="center" style="font-size:20px"><b>'.$sessionlabel.' - '.$l['w_title'].'</h1></b>';
$html = $pageheader.$tableheader;
for ($x = 0; $x < count($coursesList); $x++) {
	$pageno = 1;
	$addnoofpages = 0;
	$coaches = '';
	$pas = '';
	$schedule = $coursesList[$x]['daylabel'].'<br>'.$coursesList[$x]['arenalabel'].' '.$coursesList[$x]['arenaicelabel'].'<br>'.$coursesList[$x]['starttime'].$l['w_separator'].$coursesList[$x]['endtime'] ;
	for ($y = 0; $y < count($coursesList[$x]['coaches']); $y++) {
		if ($y != 0) {
			$coaches .= '<br>';
		}
		$coaches .= $coursesList[$x]['coaches'][$y]['firstname'].' '.$coursesList[$x]['coaches'][$y]['lastname'];
	}
	for ($y = 0; $y < count($coursesList[$x]['aps']); $y++) {
		if ($y != 0) {
			$pas .= '<br>';
		}
		$pas .= $coursesList[$x]['aps'][$y]['firstname'].' '.$coursesList[$x]['aps'][$y]['lastname'];
	}
	$addnoofpages = max(count($coursesList[$x]['coaches']), count($coursesList[$x]['aps']), 3);

	if ($nboflineonpage != 0 && (($pageno == 1 && $nboflineonpage + $addnoofpages > $nboflinefirstpage /*fmod($nboflineonpage + $addnoofpages, $nboflinefirstpage) == 0*/) || ($pageno > 1 && $nboflineonpage + $addnoofpages > $nboflinenextpage /*fmod($nboflineonpage + $addnoofpages, $nboflinenextpage) == 0*/))) {
		$html = $html .'</table>';
		$pdf->AddPage('P');
		$pdf->writeHTMLCell(0, 0, '', '', utf8_decode($html), 0, 1, 0, true, '', true);
		$html = $pageheader.$tableheader;
		$pageno++;
		$nboflineonpage  = 0;
	}
	$nboflineonpage += $addnoofpages;
	$html .= '<tr><td width="25%">'.$schedule.'</td><td width="25%">'.$coursesList[$x]['courselabel'].' '.$coursesList[$x]['courselevellabel'].'<br>('.$coursesList[$x]['coursename'].')</td><td width="25%">'.$coaches.'</td><td width="25%">'.$pas.'</td></tr>';
}
$html = $html .'</table>';
$pdf->AddPage('P');
$pdf->writeHTMLCell(0, 0, '', '', utf8_decode($html), 0, 1, 0, true, '', true);

// ---------------------------------------------------------

// Close and output PDF document
// This method has several options, check the source code documentation for more information.
$pdf->Output('sessionCoursesListOfSkaters.pdf', 'I');

//============================================================+
// END OF FILE
//============================================================+
/**
 * This function gets the list of all coaches for a session course
 */
function getSessionCourseCoaches($mysqli, $sessionscoursesid, $language) {
	try{
		if (empty($sessionscoursesid)) throw new Exception("Invalid session course id.");
		$query = "SELECT cscs.*, cm.firstname, cm.lastname, cm.id memberid, cm.birthday, cm.skatecanadano
							FROM cpa_sessions_courses_staffs cscs
							JOIN cpa_members cm ON cm.id = cscs.memberid
							JOIN cpa_sessions_courses csc ON csc.id = cscs.sessionscoursesid
							WHERE sessionscoursesid = $sessionscoursesid
							AND cscs.STAFFCODE = 'COACH'
							order by cm.lastname";
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
 * This function gets the list of all APs for a session course
 */
function getSessionCourseAPs($mysqli, $sessionscoursesid, $language) {
	try{
		if (empty($sessionscoursesid)) throw new Exception("Invalid session course id.");
		$query = "SELECT cscs.*, cm.firstname, cm.lastname, cm.id memberid, cm.birthday, cm.skatecanadano
							FROM cpa_sessions_courses_staffs cscs
							JOIN cpa_members cm ON cm.id = cscs.memberid
							JOIN cpa_sessions_courses csc ON csc.id = cscs.sessionscoursesid
							WHERE sessionscoursesid = $sessionscoursesid
							AND cscs.STAFFCODE = 'PA'
							order by cm.lastname";
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
 * This function gets the list of all courses' schedule
 */
function getSchedule($mysqli, $sessionid, $language) {
	try{
		$data = array();
		$data['success'] = null;
		$query = "select cscs.*, csc.name coursename, substr(starttime FROM 1 FOR 5) starttime, substr(endtime FROM 1 FOR 5) endtime,
									getTextLabel((select label from cpa_arenas where id = arenaid), '$language') arenalabel,
									getTextLabel((select label from cpa_arenas_ices where id = iceid), '$language') arenaicelabel,
									getTextLabel((select description from cpa_codetable where ctname = 'days' and code = day), '$language') daylabel,
									getTextLabel(csc.label, '$language') courselabel,
									getTextLabel((select label from cpa_courses_levels where coursecode = csc.coursecode and code = csc.courselevel), '$language') courselevellabel
							from cpa_sessions_courses_schedule cscs
							join cpa_sessions_courses csc ON csc.id = cscs.sessionscoursesid
							join cpa_sessions cs ON cs.id = csc.sessionid
							where cs.id = $sessionid
							order by arenaid, iceid, day, starttime";
		$result = $mysqli->query($query);

		while ($row = $result->fetch_assoc()) {
			$row['coaches'] = getSessionCourseCoaches($mysqli, $row['sessionscoursesid'], $language)['data'];
			$row['aps'] = getSessionCourseAPs($mysqli, $row['sessionscoursesid'], $language)['data'];
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
