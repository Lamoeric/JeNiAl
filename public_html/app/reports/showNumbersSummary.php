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
require_once('getShowLabel.php');
require_once('mypdf_footer.php');

// Input parameters
$language = 'fr-ca';
$showid = null;
if (isset($_GET['language']) && !empty(isset($_GET['language']))) {
	$language = $_GET['language'];
}
if (isset($_GET['showid']) && !empty(isset($_GET['showid']))) {
	$showid = $_GET['showid'];
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

$data = getnumbersSummary($mysqli, $showid, $language);
$numbersSummary = $data['data'];
$data = getShowLabel($mysqli, $showid, $language);
$showlabel = $data['data'][0]['showlabel'];
$index = 0;
$pageno = 1;
$nboflines = 25;

if ($language == 'en-ca') {
	$title = "Show Summary ";
	$separator = " to ";
	$level = " Level ";
	$nbofcourses = "Nb of practices: ";
	$nbofskaters = "Nb of skaters: ";
	$fees = "Fees: ";
} else {
	$title = "Sommaire du spectacle ";
	$separator = " &agrave; ";
	$level = " niveau ";
	$nbofcourses = "Nb de pratiques : ";
	$nbofskaters = "Nb patineurs : ";
	$fees = "Frais : ";
}

while ($index < count($numbersSummary)) {
	$lastindex = $index + $nboflines;

	// Page header
	$html = '<p align="center"><h1>'.$title.$showlabel;
//	if ($numbersSummary[$index]['courselevellabel'] != '') {
//		$html .= $level .$numbersSummary[$index]['courselevellabel'];
//	}
	$html .= '</h1></p>';
	$html .= '<table cellspacing="3" cellpadding="4" >';

	for ($x = $index; $x < $lastindex && $x < count($numbersSummary); $x++) {
//	while ($index < count($numbersSummary)) {
		$html .= '<tr>';
		$html .= '<td width="25%" nowrap>'.$numbersSummary[$x]['name'].'</td>';
		$html .= '<td width="20%" nowrap>'.$numbersSummary[$x]['label'].'</td>';
		$html .= '<td width="20%" nowrap>'.$nbofcourses.$numbersSummary[$x]['nbofpractices'].'</td>';
		$html .= '<td width="20%" nowrap>'.$nbofskaters.$numbersSummary[$x]['nbofskaters'].'/'.$numbersSummary[$x]['nbofinvites'].'</td>';
		$html .= '<td width="20%" nowrap>'.$fees.$numbersSummary[$x]['fees'].'</td>';
		$html .= '</tr>';
		$html .= '<tr>';
		$html .= '<td width="100%" style="border-bottom:1pt solid black;" nowrap>'.$numbersSummary[$x]['schedule'].'</td>';
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
$pdf->Output('sessionnumbersSummary_'.$showlabel.'_'.$language.'.pdf', 'I');

//============================================================+
// END OF FILE
//============================================================+

/**
 * This function gets the schedule for a session course from database
 */
function getShowNumbersSchedule($mysqli, $numberid, $language) {
	$schedule = '';
	$query = "select group_concat(concat(getTextLabel((select label from cpa_arenas where id = arenaid), '$language'),
																				IF((iceid is null or iceid = 0), ', ', concat(' (' , getTextLabel((select label from cpa_arenas_ices where id = iceid), '$language'), '), ')),
																				getTextLabel((select description from cpa_codetable where ctname = 'days' and code = day), '$language'),
																				' - ',
																				substr(starttime FROM 1 FOR 5),
																				' - ',
																				substr(endtime FROM 1 FOR 5))
																SEPARATOR ', ') schedule
						from cpa_shows_numbers_schedule
						where numberid = $numberid";
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
function getnumbersSummary($mysqli, $showid, $language) {
	try {
		$data = array();
		$data['success'] = null;
		$query = "select csn.id, csn.name, (select count(*) from cpa_shows_numbers_members where numberid = csn.id) nbofskaters, getTextLabel(csn.label, '$language') label,
										 csn.fees, (select count(*) from cpa_shows_numbers_dates where numberid = csn.id and canceled = 0 and manual = 0) nbofpractices,
										 (select count(*) from cpa_shows_numbers_invites where numberid = csn.id) nbofinvites
						from cpa_shows_numbers csn
						join cpa_shows cs ON cs.id = csn.showid
						where csn.showid = $showid
						and csn.datesgenerated = 1
						order by csn.name";
		$result = $mysqli->query($query);
	while ($row = $result->fetch_assoc()) {
		$row['schedule'] = getShowNumbersSchedule($mysqli, $row['id'], $language);
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
