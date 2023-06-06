<?php
//============================================================+
// File name   : testExternalPermissionForSigning.php
// Begin       : 2017-12-05
// Last Update :
//
// Description : External Test Permission (blank for signing)
//
// Author: Eric Lamoureux
//
//============================================================+

// Include the main TCPDF library (search for installation path).
require_once('../../include/tcpdf_include.php');
require_once('../../../private/'. $_SERVER['HTTP_HOST'].'/include/config.php');
require_once('../../include/nocache.php');
// require_once('customheader.php');
require_once('mypdf_footer.php');
// require_once('createFileName.php');
require_once('getClubNameAndAddress.php');

set_time_limit(500);

$language = 'fr-ca';
if (isset($_POST['language']) && !empty(isset($_POST['language']))) {
	$language = $_POST['language'];
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
// $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

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

// add a page
$pdf->AddPage();
// get the current page break margin
$bMargin = $pdf->getBreakMargin();
// get current auto-page-break mode
$auto_page_break = $pdf->getAutoPageBreak();
// disable auto-page-break
$pdf->SetAutoPageBreak(false, 0);
$img_file = K_PATH_IMAGES.'reports/tests/ext_test/external_test_permission2_'.$language.'.jpg';
// $pdf->Image($img_file, 0, 0, 210, 297, '', '', '', false, 300, '', false, false, 0);
$pdf->Image($img_file, 0, 44, 210, 245, '', '', '', false, 300, '', false, false, 0);

// restore auto-page-break status
$pdf->SetAutoPageBreak($auto_page_break, $bMargin);
// set the starting point for the page content
$pdf->setPageMark();
// set color for background
$pdf->SetFillColor(255, 255, 255);
// set font
$pdf->SetFont('times', '', 10);

// Set filename
$filename = $l['w_title'].".pdf";
$pdf->Output($filename, 'I');

// To create new type of file in temp directory
// $filename = createFileName();
// $pdf->Output($filename, 'F');
// echo $filename;
//============================================================+
// END OF FILE
//============================================================+
function addCustomHeader($mysqli, $pdf, $language) {
  // Get header info from database
  $data = getClubNameAndAddress($mysqli, $language);
  $headerTitle = utf8_decode($data['data'][0]['cpalongname']);
  $headerAddress = utf8_decode($data['data'][0]['cpaaddress']);

  $needles = array("<br>", "&#13;", "<br/>", "\\n");
  $replacement = "\n";
	$headerAddress .= '\\n\\nSign then scan between the header vertical line and the footer vertical line.';
  $headerAddress = str_replace($needles, $replacement, $headerAddress);
  // set default header data
  $pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, $headerTitle, $headerAddress, array(0,0,0), array(0,0,0));

}

?>
