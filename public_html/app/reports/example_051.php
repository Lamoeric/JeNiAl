<?php
//============================================================+
// File name   : example_051.php
//
//============================================================+

require_once('../../../private/'. $_SERVER['HTTP_HOST'].'/include/config.php');
require_once('../../include/nocache.php');
require_once('../../include/tcpdf_include.php');
require_once('createfilename.php');

// create new PDF document
//$pdf = new MYPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, false, 'ISO-8859-1', false);

// set document information
$pdf->SetCreator(PDF_CREATOR);
// $pdf->SetAuthor('Nicola Asuni');
// $pdf->SetTitle('TCPDF Example 051');
// $pdf->SetSubject('TCPDF Tutorial');
// $pdf->SetKeywords('TCPDF, PDF, example, test, guide');

// set header and footer fonts
$pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));

// set default monospaced font
$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

// set margins
$pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
$pdf->SetHeaderMargin(0);
$pdf->SetFooterMargin(0);

// remove default footer
$pdf->setPrintFooter(false);

// set auto page breaks
$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

// set image scale factor
$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

// set some language-dependent strings (optional)
if (@file_exists(dirname(__FILE__).'/lang/eng.php')) {
	require_once(dirname(__FILE__).'/lang/eng.php');
	$pdf->setLanguageArray($l);
}

// $pdf_file_link    = 'storage/tcpdf_example.pdf';
// $pdf_file_name    = getcwd() . DIRECTORY_SEPARATOR . $pdf_file_link;

// ---------------------------------------------------------

// set font
$pdf->SetFont('times', '', 8);

/*
// add a page
$pdf->AddPage();

// Print a text
$html = '<span style="background-color:yellow;color:blue;">&nbsp;PAGE 1&nbsp;</span>
<p stroke="0.2" fill="true" strokecolor="yellow" color="blue" style="font-family:helvetica;font-weight:bold;font-size:26pt;">You can set a full page background.</p>';
$pdf->writeHTML($html, true, false, true, false, '');


// add a page
$pdf->AddPage();

// Print a text
$html = '<span style="background-color:yellow;color:blue;">&nbsp;PAGE 2&nbsp;</span>';
$pdf->writeHTML($html, true, false, true, false, '');

// --- example with background set on page ---

// remove default header
$pdf->setPrintHeader(false);
*/

// add a page
$pdf->AddPage();


// -- set new background ---

// get the current page break margin
$bMargin = $pdf->getBreakMargin();
// get current auto-page-break mode
$auto_page_break = $pdf->getAutoPageBreak();
// disable auto-page-break
$pdf->SetAutoPageBreak(false, 0);
// set bacground image
// $img_file = K_PATH_IMAGES.'image_demo2.jpg';
// $pdf->Image($img_file, 0, 0, 210, 297, '', '', '', false, 300, '', false, false, 0);
$img_file = K_PATH_IMAGES.'/reports/tests/interpretives/01_interpretive_singles_fr-ca.jpg';
$pdf->Image($img_file, 0, 0, 210, 297, '', '', '', false, 300, '', false, false, 0);
// $pdf->Image($img_file, 5, 5, 190, 287, '', '', '', true, 300, '', false, false, 0);
// restore auto-page-break status
$pdf->SetAutoPageBreak($auto_page_break, $bMargin);
// set the starting point for the page content
$pdf->setPageMark();

// set color for background
$pdf->SetFillColor(255, 255, 255);

// set color for text
$pdf->SetTextColor(0, 0, 0);

$html = '';
$html = '<table>
					<tr>
						<td width="50%"><B>Club Holding Test</B></td>
						<td></td>
					</tr>
					<tr>
						<td width="50%"></td>
						<td></td>
					</tr>
					<tr>
						<td width="50%"><B>Candidate</B></td>
						<td><B>Coach</B></td>
					</tr>
					<tr>
						<td width="50%"></td>
						<td></td>
					</tr>
					<tr>
						<td width="50%"><B>Home Club of Candidate</B></td>
						<td><B>Evaluator</B></td>
					</tr>
					<tr>
						<td width="50%"></td>
						<td></td>
					</tr>
					<tr>
						<td width="50%"><B>Signature of Evaluator</B>__________________________________________________</td>
						<td></td>
					</tr>
				</table>';

//		writeHTMLCell($w, $h, $x, $y, $html='', $border=0, $ln=0, $fill=false, $reseth=true, $align='', $autopadding=true) {
$pdf->writeHTMLCell(199, 0, 14.5, 37.5, $html, 0, 1, 1, true, 'L', true);


// Print a text
//$html = '<span style="color:white;text-align:center;font-weight:bold;font-size:80pt;">PAGE 3</span>';
//$pdf->writeHTML($html, true, false, true, false, '');

// ---------------------------------------------------------

//Close and output PDF document
// $pdf->Output('example_051.pdf', 'I');
// $pdf->Output($pdf_file_name, 'F');
// $tmp_file_name = tempnam(sys_get_temp_dir(), "JeNiAl");

// $tmp_file_name = tempnam("../../tmp", "JeNiAl");
// $pdf_file_name = str_replace(".tmp",".pdf", $tmp_file_name);
// rename($tmp_file_name, $pdf_file_name);
$pdf_file_name = createFileName();
// Save to file
$pdf->Output($pdf_file_name, 'F');

// PRESENT A CLICKABLE LINK SO WE CAN D/L AND PRINT THE PDF
// echo '<a target="my_PDF" href="' . $pdf_file_link . '"><strong>Print the PDF</strong></a>';
 echo $pdf_file_name;
//============================================================+
// END OF FILE
//============================================================+
