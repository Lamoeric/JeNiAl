<?php
//============================================================+
// File name   : sessionSchedule.php
// Begin       : 2016-07-07
// Last Update :
//
// Description : session schedule per arena/ice/day
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

$data = getSchedule($mysqli, $language, $sessionid);
$schedules = $data['data'];
$index = 0;
$pageno = 1;
$primarykey = '';

if ($language == 'en-ca') {
	$title = "Schedule";
	$separator = " to ";
} else {
	$title = "Horaire";
	$separator = " &agrave; ";
}

$primarykey = $schedules[$index]['arenaid'].$schedules[$index]['iceid'].$schedules[$index]['day'];

while ($index < count($schedules)) {
	// Page header
	$html = '<h1><p align="center">'.$title.'</p></h1><br><h2><p align="center">'.$schedules[$index]['arenalabel'];
	if ($schedules[$index]['arenaicelabel'] != '') {
		$html .= ' - ' .$schedules[$index]['arenaicelabel'];
	}
	$html .= '</p></h2><br><h3>'.$schedules[$index]['daylabel'].'</h3><br>
	<table cellspacing="3" cellpadding="4" >';

	while ($index < count($schedules) && $schedules[$index]['arenaid'].$schedules[$index]['iceid'].$schedules[$index]['day'] == $primarykey) {
		$html .= '<tr><td style="border-bottom:1pt solid black;">'.$schedules[$index]['courselabel'].' '.$schedules[$index]['courselevellabel'].'</td><td style="border-bottom:1pt solid black;">'.$schedules[$index]['starttime'] . $separator . $schedules[$index]['endtime'].'</td></tr>';
		$index++;
	}
	if ($index < count($schedules)) {
		$primarykey = $schedules[$index]['arenaid'].$schedules[$index]['iceid'].$schedules[$index]['day'];
	}
	$html = $html .'</table>';
	$pdf->AddPage();
	$pageno++;
	$pdf->writeHTMLCell(0, 0, '', '', utf8_decode($html), 0, 1, 0, true, '', true);
}

// ---------------------------------------------------------

// Close and output PDF document
// This method has several options, check the source code documentation for more information.
$pdf->Output('sessionSchedule.pdf', 'I');

//============================================================+

/**
 * This function gets the list of all courses' schedule
 */
function getSchedule($mysqli, $language, $sessionid) {
	try {
		$data = array();
		$data['success'] = null;
		$query = "select cscs.*, substr(starttime FROM 1 FOR 5) starttime, substr(endtime FROM 1 FOR 5) endtime, getTextLabel((select label from cpa_arenas where id = arenaid), '$language') arenalabel, getTextLabel((select label from cpa_arenas_ices where id = iceid), '$language') arenaicelabel, getTextLabel((select description from cpa_codetable where ctname = 'days' and code = day), '$language') daylabel, getTextLabel(csc.label, '$language') courselabel, getTextLabel((select label from cpa_courses_levels where coursecode = csc.coursecode and code = csc.courselevel), '$language') courselevellabel
							from cpa_sessions_courses_schedule cscs
							join cpa_sessions_courses csc ON csc.id = cscs.sessionscoursesid
							join cpa_sessions cs ON cs.id = csc.sessionid
							where cs.id = $sessionid
							order by arenaid, iceid, day, starttime";
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
