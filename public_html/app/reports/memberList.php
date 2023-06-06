<?php
//============================================================+
// File name   : memberList.php
// Begin       : 2016-07-07
// Last Update :
//
// Description : Member list
//
// Author: Eric Lamoureux
//
//============================================================+

// Include the main TCPDF library (search for installation path).
require_once('../../include/tcpdf_include.php');
require_once('../../../private/'. $_SERVER['HTTP_HOST'].'/include/config.php');
require_once('../../include/nocache.php');
require_once('customheader.php');

if( isset($_GET['language']) && !empty( isset($_GET['language']) ) ){
	$language = $_GET['language'];
}

// create new PDF document
$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, false, 'ISO-8859-1', false);

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

$data = getAllMembers($mysqli);
$members = $data['data'];
$index = 0;
$pageno = 1;
$nboflines = 27;

while ($index < count($members)) {
	if ($pageno > 1) {
		$nboflines = 33;
	}
	$lastindex = $index + $nboflines;
	$indextoprint = $index + 1;
	if ($lastindex + 1 < count($members)) {
		$lastindextoprint = $lastindex + 1;
	} else {
		$lastindextoprint = count($members);
	}

	// Set some content to print
	$html = '<h1>Members (' . $indextoprint . ' - ' . $lastindextoprint . ')</h1><br>
	<table border="1" cellspacing="3" cellpadding="4">
		<tr>
			<th><b>id</b></th>
			<th><b>Firstname</b></th>
			<th><b>Lastname</b></th>
			<th><b>Skate Canada No</b></th>
		</tr>
	';

//	$members = $data['data'];
	for($x = $index; $x < $lastindex && $x < count($members); $x++) {
		$html .= '<tr><td>'.$members[$x]['id'].'</td><td>'.$members[$x]['firstname'].'</td><td>'.$members[$x]['lastname'].'</td><td>'.$members[$x]['skatecanadano'].'</td></tr>';
	}
	$html = $html .'</table>';
	if ($pageno > 1) {
		$pdf->setPrintHeader(false);
		$pdf->setPrintFooter(false);
		$pdf->SetMargins(PDF_MARGIN_LEFT, 5, PDF_MARGIN_RIGHT);
//		$pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
	}
	$pdf->AddPage();
	$pageno++;
	$pdf->writeHTMLCell(0, 0, '', '', utf8_decode($html), 0, 1, 0, true, '', true);
	$index = $lastindex;
}
//$html = $html .count($data['data']);
$html = count($data['data']);

// Print text using writeHTMLCell()
$pdf->writeHTMLCell(0, 0, '', '', $html, 0, 1, 0, true, '', true);

// ---------------------------------------------------------

// Close and output PDF document
// This method has several options, check the source code documentation for more information.
$pdf->Output('memberList.pdf', 'I');

//============================================================+
// END OF FILE
//============================================================+

/**
 * This function gets the list of all members from database
 */
function getAllMembers($mysqli){
	try{
		$data = array();
		$data['success'] = null;
		$query = "SELECT id, lastname, firstname, skatecanadano FROM cpa_members order by lastname, firstname";
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
