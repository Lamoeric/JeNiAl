<?php
//============================================================+
// File name   : sessionCourseSummary.php
// Begin       : 2016-07-07
// Last Update :
//
// Description : session course summary
//
// Author: Eric Lamoureux
//
//============================================================+

// Include the main TCPDF library (search for installation path).
require_once('../../include/tcpdf_include.php');
require_once('../../../private/'. $_SERVER['HTTP_HOST'].'/include/config.php');
require_once('../../include/nocache.php');
require_once('customheader.php');
require_once('getSessionLabel.php');
require_once('mypdf_footer.php');

// Input parameters
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

// set some language-dependent strings (optional)
if (@file_exists(dirname(__FILE__).'/lang/eng.php')) {
	require_once(dirname(__FILE__).'/lang/eng.php');
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

$data = getCoursesSummary($mysqli, $language, $sessionid);
$coursesSummary = $data['data'];
$sessionlabel = getSessionLabel($mysqli, $sessionid, $language)['data'][0]['sessionlabel'];
$index = 0;
$pageno = 1;
$nboflines = 25;

if ($language == 'en-ca') {
	$title = "Session Summary ";
	$separator = " to ";
	$level = " Level ";
	$nbofcourses = "Nb of courses: ";
	$nbofskaters = "Nb of skaters: ";
	$fees = "Fees: ";
} else {
	$title = "Sommaire de la session ";
	$separator = " &agrave; ";
	$level = " niveau ";
	$nbofcourses = "Nb de cours : ";
	$nbofskaters = "Nb patineurs : ";
	$fees = "Frais: ";
}

while ($index < count($coursesSummary)) {
	$lastindex = $index + $nboflines;

	// Page header
	$html = '<p align="center"><h1>'.$title.$coursesSummary[$index]['sessionlabel'];
//	if ($coursesSummary[$index]['courselevellabel'] != '') {
//		$html .= $level .$coursesSummary[$index]['courselevellabel'];
//	}
	$html .= '</h1></p>';
	$html .= '<table cellspacing="3" cellpadding="4" >';

	for ($x = $index; $x < $lastindex && $x < count($coursesSummary); $x++) {
//	while ($index < count($coursesSummary)) {
		$html .= '<tr>';
		$html .= '<td width="25%" nowrap>'.$coursesSummary[$x]['name'].'</td>';
		$html .= '<td width="20%" nowrap>'.$coursesSummary[$x]['label'].' '.$coursesSummary[$index]['courselevellabel'].'</td>';
		$html .= '<td width="20%" nowrap>'.$nbofcourses.$coursesSummary[$x]['nbofcourses'].'</td>';
		$html .= '<td width="20%" nowrap>'.$nbofskaters.$coursesSummary[$x]['nbofskaters'].'/'.$coursesSummary[$x]['maxnumberskater'].'</td>';
		$html .= '<td width="20%" nowrap>'.$fees.$coursesSummary[$x]['fees'].'</td>';
		$html .= '</tr>';
		$html .= '<tr>';
		$html .= '<td width="100%" style="border-bottom:1pt solid black;" nowrap>'.$coursesSummary[$x]['schedule'].'</td>';
		$html .= '</tr>';
		$html .= '<tr>';
		$html .= '<td width="100%"></td>';
		$html .= '</tr>';
		$index++;
	}
	if ($pageno > 1) {
		$pdf->setPrintHeader(false);
//		$pdf->setPrintFooter(false);
		$pdf->SetMargins(PDF_MARGIN_LEFT, 5, PDF_MARGIN_RIGHT);
	}

	$html = $html .'</table>';
	$pdf->AddPage();
	$pageno++;
	$pdf->writeHTMLCell(0, 0, '', '', utf8_decode($html), 0, 1, 0, true, '', true);
	$index = $lastindex;
}

// ---------------------------------------------------------

// Close and output PDF document
// This method has several options, check the source code documentation for more information.
$pdf->Output('sessionCoursesSummary_'.$sessionlabel.'_'.$language.'.pdf', 'I');

//============================================================+
// END OF FILE
//============================================================+

/**
 * This function gets the schedule for a session course from database
 */
function getSessionCoursesSchedule($mysqli, $sessionscoursesid, $language) {
	$schedule = '';
	$query = "SELECT getCourseSchedule($sessionscoursesid, '$language') AS schedule";
	$result = $mysqli->query($query);
	while ($row = $result->fetch_assoc()) {
		$schedule = $row['schedule'];
		return $schedule;
	}
	return $schedule;
}

/**
 * This function gets the list of dates for a session course from database
 */
function getCoursesSummary($mysqli, $language, $sessionid) {
	try {
		$data = array();
		$data['success'] = null;
		$query = "select csc.id, csc.coursecode, csc.courselevel, csc.name, csc.maxnumberskater,
							(select count(*) from cpa_sessions_courses_members where sessionscoursesid = csc.id and membertype = 3) nbofskaters,
							getTextLabel((select label from cpa_courses_levels where coursecode = csc.coursecode and code = csc.courselevel), '$language') courselevellabel,
							getTextLabel(csc.label, '$language') label,
							csc.fees,
							(SELECT floor(datediff(coursesenddate, coursesstartdate)/7) FROM cpa_sessions WHERE id = $sessionid) sessionnbofweeks,
							(select count(*) from cpa_sessions_courses_dates where sessionscoursesid = csc.id and canceled = 0 and manual = 0) nbofcourses,
							getTextLabel(cs.label, '$language') sessionlabel
						from cpa_sessions_courses csc
						join cpa_courses cc ON cc.code = csc.coursecode
						join cpa_sessions cs ON cs.id = csc.sessionid
						where csc.sessionid = $sessionid
						and datesgenerated = 1
						and cc.acceptregistrations = 1
						order by coursecode, courselevel";
		$result = $mysqli->query($query);
	while ($row = $result->fetch_assoc()) {
		$row['schedule'] = getSessionCoursesSchedule($mysqli, $row['id'], $language);
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
