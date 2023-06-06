<?php
//============================================================+
// File name   : testExternalPermission.php
// Begin       : 2017-12-05
// Last Update :
//
// Description : External Test Permission
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
try {
	$result = array();
	// Input parameters
	$testspermissionsid = null;
	$type = null; // normal - if signed sheet exists, use it if not, use unsigned sheet. empty - print empty sheet (not tested). signed - if signed sheet exists, use it if not, exception.
	$language = 'fr-ca';
	$output = 'I';	// I - open in browser, F - save in tmp directory, return the filename

	if (isset($_GET['testpermissionid']) && !empty($_GET['testpermissionid'])) {
		$testspermissionsid = $_GET['testpermissionid'];
	}
	if (isset($_POST['testpermissionid']) && !empty($_POST['testpermissionid'])) {
		$testspermissionsid = $_POST['testpermissionid'];
	}

	if (isset($_GET['type']) && !empty(isset($_GET['type']))) {
		$type = $_GET['type'];
	}
	if (isset($_POST['type']) && !empty(isset($_POST['type']))) {
		$type = $_POST['type'];
	}

	if (isset($_GET['language']) && !empty(isset($_GET['language']))) {
		$language = $_GET['language'];
	}
	if (isset($_POST['language']) && !empty(isset($_POST['language']))) {
		$language = $_POST['language'];
	}

	if (isset($_GET['output']) && !empty(isset($_GET['output']))) {
		$output = $_GET['output'];
	}
	if (isset($_POST['output']) && !empty(isset($_POST['output']))) {
		$output = $_POST['output'];
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

	if ($type == 'normal' || $type == 'signed') {
		$data = getTestPermission($mysqli, $testspermissionsid, $language)['data'][0];
		$testdirectorid = $data['testdirectorid'];
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
	  $pdf->SetFooterMargin(0);
	  // set background image (for empty permission sheet)
	  $img_file = K_PATH_IMAGES.'reports/tests/externaltests/external_test_permission_new_'.$language.'.jpg';
	  $pdf->Image($img_file, 0, 0, 210, 297, '', '', '', false, 300, '', false, false, 0);
		$deltaX = 0;
		$deltaY = 0;
	} else {
		// Default file
		$img_file = K_PATH_IMAGES.'reports/tests/externaltests/external_test_permission_new_'.$language.'.jpg';
		if ($type == 'signed') {
			if (!isset($testdirectorid)) {
				throw new Exception('JeNiAlError1'.$testdirectorid);
				exit;
			} else {
				$img_file = K_PATH_PRIVATEIMAGES.'externaltests/external_test_permission_'.$language.'_'.$testdirectorid.'.jpg';
				if (!file_exists($img_file)) {
					throw new Exception('JeNiAlError2');
					exit;
				}
				$pdf->SetMargins(0, 0, 0, 0);
				$pdf->SetHeaderMargin(0);
				$pdf->SetFooterMargin(0);
				$deltaX = 2;
				$deltaY = 1.5;
				$pdf->Image($img_file, -14, -3, 235, 305, '', '', '', false, 90, '', false, false, 0);
				// $pdf->Image($img_file, 0, 0, 210, 297, '', '', '', false, 300, '', false, false, 0);
			}
		} else {
			if (isset($testdirectorid)) {	// If test director doesn't exist, $img_file stays with the default file
				// set background image (for normal permission sheet with test director id)
				$tmpfile = K_PATH_PRIVATEIMAGES.'externaltests/external_test_permission_'.$language.'_'.$testdirectorid.'.jpg';
				if (file_exists($tmpfile)) {
				  $img_file = $tmpfile;		// If file doesn't exist, $img_file stays with the default file
					$pdf->SetMargins(0, 0, 0, 0);
					$pdf->SetHeaderMargin(0);
					$pdf->SetFooterMargin(0);
					// $deltaX = 2;
					// $deltaY = 1.5;
					$deltaX = 0;
					$deltaY = 0;
					$pdf->Image($img_file, -14, -3, 235, 305, '', '', '', false, 90, '', false, false, 0);
				} else {
					$deltaX = 0;
					$deltaY = 0;
					$pdf->Image($img_file, 0, 44, 210, 245, '', '', '', false, 300, '', false, false, 0);
				}
			}
		}
	}

	// restore auto-page-break status
	$pdf->SetAutoPageBreak($auto_page_break, $bMargin);
	// set the starting point for the page content
	$pdf->setPageMark();
	// set color for background
	$pdf->SetFillColor(255, 255, 255);
	// set font
	$pdf->SetFont('times', '', 12);
	$border = 0;  // used for debuging purposes.

	// Name of skater
	$html = $data['skaterfirstname'] . ' ' . $data['skaterlastname'];
	$pdf->writeHTMLCell(80, 0, $deltaX+73, $deltaY+66.5, utf8_decode($html), $border, 1, 1, true, 'L', true);
	// # skate Canada
	$html = $data['skatecanadano'];
	$pdf->writeHTMLCell(40, 0, $deltaX+80, $deltaY+73.5, utf8_decode($html), $border, 1, 1, true, 'L', true);
	// club name
	$html = $data['homeclubname'];
	$pdf->writeHTMLCell(60, 0, $deltaX+100, $deltaY+89.5, utf8_decode($html), $border, 1, 1, true, 'L', true);
	// club # skate Canada
	$html = $data['homeclubno'];
	$pdf->writeHTMLCell(40, 0, $deltaX+80, $deltaY+96.5, utf8_decode($html), $border, 1, 1, true, 'L', true);
	// Test date
	$html = $data['testdate'];
	$pdf->writeHTMLCell(40, 0, $deltaX+68, $deltaY+112.5, utf8_decode($html), $border, 1, 1, true, 'L', true);
	// Host club
	if ($data['hostclubname'] == '') {
		$html = $data['clubname'];
	} else {
		$html = $data['hostclubname'];
	}
	$pdf->writeHTMLCell(80, 0, $deltaX+73, $deltaY+119.5, utf8_decode($html), $border, 1, 1, true, 'L', true);
	$html = '';
	if (count($data['tests']) > 5) {
		$pdf->SetFont('times', '', 9);
	}
	for ($x = 0; $x < count($data['tests']); $x++) {
		if (!empty($html)) {
			$html .= ', ';
		}
		$html .= $data['tests'][$x]['testtypelabel'] . ' - ' . $data['tests'][$x]['testlabel'];
	}
	// $html .= ', ' . $html; // for testing, double the number of test
	$pdf->writeHTMLCell(120, 16, $deltaX+72, $deltaY+127, utf8_decode($html), $border, 1, 1, true, 'L', true);

	$pdf->SetFont('times', '', 12);

	// Name of test director
	$html = $data['directorfirstname'] . ' ' . $data['directorlastname'];
	$pdf->writeHTMLCell(60, 0, $deltaX+38, $deltaY+142.5, utf8_decode($html), $border, 1, 1, true, 'L', true);

	// Name of skater
	$html = $data['skaterfirstname'] . ' ' . $data['skaterlastname'];
	$pdf->writeHTMLCell(80, 0, $deltaX+127, $deltaY+142.5, utf8_decode($html), $border, 1, 1, true, 'L', true);


	// TODO : should we use today's date or the approbation date?
	// Today's date
	// $date = date('Y-m-d');
	// $html = $date;
	$html = substr($data['approvedon'], 0, 10);
	$pdf->writeHTMLCell(25, 0, $deltaX+44, $deltaY+194, utf8_decode($html), $border, 1, 1, true, 'L', true);

	// Set filename
	$filename = $l['w_title'].".pdf";
	// Close and output PDF document
	// This method has several options, check the source code documentation for more information.
	if ($output == 'F') {
		$filename = createFileName();
		$result['filename'] = $filename;
		$result['success'] = true;
		$pdf->Output($filename, 'F');
	} else if ($output == 'I') {
		$pdf->Output($filename, 'I');
		$result['success'] = true;
	}
	echo json_encode($result);
	exit;
}catch (Exception $e) {
	$result['success'] = false;
	$result['message'] = $e->getMessage();
	echo json_encode($result);
	exit;
}

//============================================================+
// END OF FILE
//============================================================+
/**
 * This function gets the details of a test permission
 */
function getTestPermission($mysqli, $testexternalapprobsid, $language) {
	try{
		$query = "SELECT ctea.*, cm.firstname skaterfirstname, cm.lastname skaterlastname, cm.skatecanadano, cm.homeclub,
										 cc.orgno homeclubno, getTextLabel(cc.label, '$language') homeclubname,
										 getTextLabel(cc2.label, '$language') hostclubname,
										 cm2.firstname directorfirstname, cm2.lastname directorlastname
              FROM cpa_test_external_approbations ctea
              JOIN cpa_members cm ON cm.id = ctea.memberid
							LEFT JOIN cpa_members cm2 ON cm2.id = ctea.testdirectorid
							JOIN cpa_clubs cc ON cc.code = cm.homeclub
							left JOIN cpa_clubs cc2 ON cc2.code = ctea.clubcode
              WHERE ctea.id = $testexternalapprobsid";
		$result = $mysqli->query($query);
		$data = array();
		$data['data'] = array();
		while ($row = $result->fetch_assoc()) {
      $row['tests'] = getTestPermissionTests($mysqli, $testexternalapprobsid, $language)['data'];
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
function getTestPermissionTests($mysqli, $testexternalapprobsid, $language) {
	$query = "SELECT cteat.*, getCodeDescription('testtypes', ctd.type, '$language') testtypelabel, getTextLabel(ct.label, '$language') testlabel
						FROM cpa_test_external_approbations_tests cteat
						JOIN cpa_tests ct ON ct.id = cteat.testsid
						JOIN cpa_tests_definitions ctd ON ctd.id = ct.testsdefinitionsid
            WHERE cteat.testexternalapprobsid = $testexternalapprobsid
						AND cteat.status is null";
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
