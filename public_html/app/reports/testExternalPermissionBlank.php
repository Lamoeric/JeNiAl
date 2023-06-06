<?php
//============================================================+
// File name   : testExternalPermissionBlank.php
// Begin       : 2017-12-05
// Last Update :
//
// Description : External Test Permission (blank)
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
// require_once('getActiveSessionLabel.php');
require_once('createFileName.php');

set_time_limit(500);

// Input parameters
$parameters = null;
if (isset($_POST['parameters']) && !empty(isset($_POST['parameters']))) {
	$parameters = $_POST['parameters'];
  $testspermissionsid = $parameters['testpermissionid'];
  $type = $parameters['type'];
} else {
	// For testing
	$testspermissionsid = $parameters['testpermissionid'] = 1;
	$type = $parameters['type'] = 'empty';  // empty or normal
}
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
if ($type=='empty') {
  // $pdf->SetFooterMargin(0);
  // set background image (for empty permission sheet)
  $img_file = K_PATH_IMAGES.'reports/tests/externaltests/external_test_permission_'.$language.'.jpg';
  // $pdf->Image($img_file, 0, 0, 210, 297, '', '', '', false, 300, '', false, false, 0);
  $pdf->Image($img_file, 0, 44, 210, 245, '', '', '', false, 300, '', false, false, 0);
	// $pdf->AddPage();
} else {
  // set background image (for normal permission sheet)
  $img_file = K_PATH_IMAGES.'reports/tests/externaltests/external_test_permission2_'.$language.'.jpg';
  $pdf->Image($img_file, 0, 44, 210, 245, '', '', '', false, 300, '', false, false, 0);
}

// restore auto-page-break status
$pdf->SetAutoPageBreak($auto_page_break, $bMargin);
// set the starting point for the page content
$pdf->setPageMark();
// set color for background
$pdf->SetFillColor(255, 255, 255);
// set font
$pdf->SetFont('times', '', 10);
$border = 0;  // used for debuging purposes.

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

/**
 * This function gets the details of a test permission
 */
function getTestPermission($mysqli, $testspermissionsid, $language) {
	try{
		$query = "SELECT cm.firstname skaterfirstname, cm.lastname skaterlastname, cm.skatecanadano
              FROM cpa_tests_permissions ctp
              JOIN cpa_members cm ON cm.id = ctp.memberid
              WHERE ctp.id = $testspermissionsid";
		$result = $mysqli->query($query);
		$data = array();
		$data['data'] = array();
		while ($row = $result->fetch_assoc()) {
      $row['tests'] = getTestPermissionTests($mysqli, $row['id'], $language)['data'];
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
 * This function gets all the tests for a test permission
 */
function getTestPermissionTests($mysqli, $testspermissionsid) {
	$query = "SELECT *
            FROM cpa_tests_permissions_tests ctpt
            WHERE ctpt.testspermissionsid = $testspermissionsid";
	$result = $mysqli->query($query);
	$data = array();
	$data['data'] = array();
	while ($row = $result->fetch_assoc()) {
		$data['data'][] = $row;
	}
	$data['success'] = true;
	return $data;
};
?>
