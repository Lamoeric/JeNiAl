<?php
//============================================================+
// File name   : scregistrationimport.php
// Begin       : 2018-10-30
// Last Update :
//
// Description : report of the skate Canada registrations import
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
require_once('createFileName.php');

set_time_limit(500);

// Input parameters
$language = 'fr-ca';
$output = 'I';
if (isset($_POST['parameters']) && !empty(isset($_POST['parameters']))) {
	$parameters = json_decode($_POST['parameters'], true);
	$data = $parameters['data'];
	$language = $parameters['language'];
} else {
	exit;
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
// addCustomHeader($mysqli, $pdf, $language);

// set header and footer fonts
$pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
$pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));

// set default monospaced font
$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

// set margins
// $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
$pdf->SetMargins(PDF_MARGIN_LEFT, 10, PDF_MARGIN_RIGHT);
// $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
$pdf->SetHeaderMargin(0);
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

$pdf->AddPage('P');
$html = '<p align="center" style="font-size:20px">' . $l['w_title'] . '</p>';
$pdf->writeHTMLCell(0, 0, '', '', utf8_decode($html), 0, 1, 0, true, '', true);

// Number of member that received a SC number
$html = '<br><h2>' . $l['w_title_converted'] . '</h2>';
$html .= '<p>' . $l['w_title_converteddesc'] . sizeof($data['matches']) . '</p>';
$pdf->writeHTMLCell(0, 0, '', '', utf8_decode($html), 0, 1, 0, true, '', true);

// Non unique SC number.
$newdata = array();
for ($x = 0; $x < count($data['nonuniqueSCno']); $x++) {
	$newdata[] = $data['nonuniqueSCno'][$x]['skatecanadano'];
}
if (count($newdata) != 0) {
	$html = '<br><h2>' . $l['w_title_nonuniqueSCno'] . '</h2>';
	$html .= '<p>' . $l['w_title_nonuniqueSCnodesc'] . '</p>';
	$html .= '<p>' . join(", ", $newdata) . '</p>';
	$pdf->writeHTMLCell(0, 0, '', '', utf8_decode($html), 0, 1, 0, true, '', true);
}

// Non unique members (same firstname and lastname)
$newdata = array();
for ($x = 0; $x < count($data['nonuniquemembers']); $x++) {
	$newdata[] = $data['nonuniquemembers'][$x]['firstname'] . ' ' . $data['nonuniquemembers'][$x]['lastname'];
}
if (count($newdata) != 0) {
	$html  = '<br><h2>' . $l['w_title_nonuniquemembers'] . '</h2>';
	$html .= '<p>' . $l['w_title_nonuniquemembersdesc'] . '</p>';
	$html .= '<p>'. join(", ", $newdata) . '</p>';
	$pdf->writeHTMLCell(0, 0, '', '', utf8_decode($html), 0, 1, 0, true, '', true);
}

// Different members (different firstname or lastname from JeNiAl to SC)
if (count($data['differentmembers']) != 0) {
	$html  = '<br><h2>' . $l['w_title_differentmembers'] . '</h2>';
	$html .= '<p>' . $l['w_title_differentmembersdesc'] . '</p>';
	$pdf->writeHTMLCell(0, 0, '', '', utf8_decode($html), 0, 1, 0, true, '', true);
}

$tableheader = '<table border="1"><tr>';
$tableheader .= '<td width="15%"><b>' . $l['w_skatecanadano'] . '</b></td><td width="20%"><b>'.$l['w_firstname'].'</b></td><td width="20%"><b>' . $l['w_lastname'] . '</b></td>';
$tableheader .= '<td width="20%"><b>'.$l['w_scfirstname'].'</b></td><td width="20%"><b>' . $l['w_sclastname'] . '</b></td></tr>';
$html = $tableheader;
for ($x = 0; $x < count($data['differentmembers']); $x++) {
	$member = $data['differentmembers'][$x];
	$x++;
	$html .= '<tr><td width="15%">' . $member['skatecanadano'] . '</td><td width="20%">' . $member['firstname'] . '</td><td width="20%">' . $member['lastname'] . '</td>';
	$html .=     '<td width="20%">' . $member['sc_firstname'] . '</td><td width="20%">' . $member['sc_lastname'] . '</td></tr>';
}
$html .= '</table>';
$pdf->writeHTMLCell(0, 0, '', '', utf8_decode($html), 0, 1, 0, true, '', true);

// non existing members (registered at Skate Canada, but not existing in JeNiAl)
if (count($data['nonexistingmembers']) != 0) {
	$html  = '<br><h2>' . $l['w_title_nonexistingmembers'] . '</h2>';
	$html .= '<p>' . $l['w_title_nonexistingmembersdesc'] . '</p>';
	$pdf->writeHTMLCell(0, 0, '', '', utf8_decode($html), 0, 1, 0, true, '', true);
}

$tableheader = '<table border="1"><tr>';
$tableheader .= '<td width="15%"><b>' . $l['w_skatecanadano'] . '</b></td><td width="25%"><b>'.$l['w_name'].'</b></td><td width="10%"><b>' . $l['w_registrationyear'] . '</b></td>';
$tableheader .= '<td width="15%"><b>' . $l['w_skatecanadano'] . '</b></td><td width="25%"><b>'.$l['w_name'].'</b></td><td width="10%"><b>' . $l['w_registrationyear'] . '</b></td></tr>';
$html = $tableheader;
for ($x = 0; $x < count($data['nonexistingmembers']); $x++) {
	$member1 = $data['nonexistingmembers'][$x];
	if ($x+1 < count($data['nonexistingmembers'])) {
		$member2 = $data['nonexistingmembers'][$x+1];
	} else {
		$member2 = array();
		$member2['skatecanadano'] = '';
		$member2['firstname'] = '';
		$member2['lastname'] = '';
		$member2['registrationyear'] = '';
	}
	$x++;
	$html .= '<tr><td width="15%">' . $member1['skatecanadano'] . '</td><td width="25%">' . $member1['firstname'] . ' ' . $member1['lastname'] . '</td><td width="10%">' . $member1['registrationyear'] . '</td>';
	$html .=     '<td width="15%">' . $member2['skatecanadano'] . '</td><td width="25%">' . $member2['firstname'] . ' ' . $member2['lastname'] . '</td><td width="10%">' . $member2['registrationyear'] . '</td></tr>';
}
$html .= '</table>';
$pdf->writeHTMLCell(0, 0, '', '', utf8_decode($html), 0, 1, 0, true, '', true);

// ---------------------------------------------------------

// Close and output PDF document
// This method has several options, check the source code documentation for more information.
$filename = createFileName();
$pdf->Output($filename, 'F');
$filename = convertFileName($mysqli, $filename);
echo $filename;

//============================================================+
// END OF FILE
//============================================================+
