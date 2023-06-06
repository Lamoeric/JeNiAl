<?php
//============================================================+
// File name   : sessionCourseSchedule.php
// Begin       : 2016-07-07
// Last Update :
//
// Description : session course schedule
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
$sessionscoursesid = null;
$eventType = 1; // 1 - Session 2 - Show
$sessionid = null;
$showid = null;
$showsnumbersid = null;
if (isset($_GET['language']) && !empty(isset($_GET['language']))) {
	$language = $_GET['language'];
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

// Add a page
// This method has several options, check the source code documentation for more information.
//$pdf->AddPage();
$filename = 'sessionCourseSchedule.pdf';
if ($eventType == 1) { // Session
	$data = getCourseSchedule($mysqli, $language, $sessionscoursesid);
}

if ($eventType == 2) { // Show
	$data = getNumberSchedule($mysqli, $language, $showsnumbersid);
}

if ($data['success'] == true && isset($data['data'])) {
	$schedules = $data['data'];
	$index = 0;
	$pageno = 1;
	$nboflines = 25;
	$filename = 'sessionCourseSchedule_'.$schedules[0]['name'].'.pdf';

	if ($language == 'en-ca') {
		$title = "Schedule ";
		$separator = " to ";
		$level = " Level ";
	} else {
		$title = "Horaire ";
		$separator = " &agrave; ";
		$level = " niveau ";
	}

	while ($schedules && $index < count($schedules)) {
		$lastindex = $index + $nboflines;

		// Page header
		$html = '<p align="center"><h1>'.$title.$schedules[$index]['courselabel'];
		if ($schedules[$index]['courselevellabel'] != '') {
			$html .= $level .$schedules[$index]['courselevellabel'];
		}
		$html .= ' ('.$schedules[$index]['name'].')</h1></p>'; //<h3>'.$schedules[$index]['daylabel'].'</h3><br>
		$html .= '<table cellspacing="3" cellpadding="4" >';

		for($x = $index; $x < $lastindex && $x < count($schedules); $x++) {
	//	while ($index < count($schedules)) {
			$html .= '<tr>';
			$html .= '<td width="25%" style="border-bottom:1pt solid black;" nowrap>'.$schedules[$x]['arenalabel'].' '.$schedules[$x]['arenaicelabel'].'</td>';
			$html .= '<td width="10%" style="border-bottom:1pt solid black;">'.$schedules[$x]['daylabel'].'</td>';
			$html .= '<td width="15%" style="border-bottom:1pt solid black;">'.$schedules[$x]['coursedate'].'</td>';
			$html .= '<td style="border-bottom:1pt solid black;" NOWRAP>'.$schedules[$x]['starttime'] . $separator . $schedules[$x]['endtime'].'</td>';
	//		$html .= '<td style="border-bottom:1pt solid black;">'.$schedules[$x]['duration'].'</td>';
			$html .= '<td style="border-bottom:1pt solid black;">'.$schedules[$x]['canceledlabel'].'</td>';
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
}
// ---------------------------------------------------------

// Close and output PDF document
// This method has several options, check the source code documentation for more information.
$pdf->Output($filename, 'I');

//============================================================+
// END OF FILE
//============================================================+

/**
 * This function gets the list of dates for a session course from database
 */
function getCourseSchedule($mysqli, $language, $sessionscoursesid) {
	try {
		$data = array();
		$data['success'] = null;
		$query = "SELECT cscd.coursedate, substr(cscd.starttime FROM 1 FOR 5) starttime, substr(cscd.endtime FROM 1 FOR 5) endtime, cscd.duration, cscd.canceled, csc.name,
								getTextLabel(cscd.label, '$language') label, getTextLabel((select label from cpa_arenas where id = arenaid), '$language') arenalabel,
								getTextLabel((select label from cpa_arenas_ices where id = iceid), '$language') arenaicelabel,
								getTextLabel((select description from cpa_codetable where ctname = 'days' and code = day), '$language') daylabel,
								getTextLabel((select description from cpa_codetable where ctname = 'sessioncoursestatus' and code = cscd.canceled), '$language') canceledlabel,
								getTextLabel(csc.label, '$language') courselabel,
								getTextLabel((select label from cpa_courses_levels where coursecode = csc.coursecode and code = csc.courselevel), '$language') courselevellabel
							FROM cpa_sessions_courses_dates cscd
							JOIN cpa_sessions_courses csc ON csc.id = cscd.sessionscoursesid
							WHERE cscd.sessionscoursesid = '$sessionscoursesid'
							order by coursedate";
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
 * This function gets the list of dates for a show number from database
 */
function getNumberSchedule($mysqli, $language, $showsnumbersid) {
	try {
		$data = array();
		$data['success'] = null;
		$query = "SELECT csnd.practicedate as coursedate, substr(csnd.starttime FROM 1 FOR 5) starttime, substr(csnd.endtime FROM 1 FOR 5) endtime, csnd.duration, csnd.canceled, csc.name,
								null as label, getTextLabel((select label from cpa_arenas where id = arenaid), '$language') arenalabel,
								getTextLabel((select label from cpa_arenas_ices where id = iceid), '$language') arenaicelabel,
								getTextLabel((select description from cpa_codetable where ctname = 'days' and code = day), '$language') daylabel,
								getTextLabel((select description from cpa_codetable where ctname = 'sessioncoursestatus' and code = csnd.canceled), '$language') canceledlabel,
								getTextLabel(csc.label, '$language') courselabel, null as courselevellabel
							FROM cpa_shows_numbers_dates csnd
							JOIN cpa_shows_numbers csc ON csc.id = csnd.numberid
							WHERE csnd.numberid = '$showsnumbersid'
							order by practicedate";
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
