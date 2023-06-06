<?php
//============================================================+
// File name   : showTaskList.php
// Begin       : 2023-03-10
// Last Update :
//
// Description : show task list - per performance
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
require_once('getShowLabel.php');

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

$nboflinefirstpage = 41;
$nboflinenextpage  = 47;
$nboflinenextpageemail = 38;
$nboflineonpage  = 0;
$data = getShowLabel($mysqli, $showid, $language);
$showlabel = $data['data'][0]['showlabel'];
$data = getPerformanceTaskList($mysqli, $showid, $language);
$perfList = $data['data'];
$emailList = array();
$tableheader = '<table border="1"><tr><td width="35%"><b>'.$l['w_tasklabel'].'</b></td><td width="25%"><b>'.$l['w_fullname'].'</b></td><td width="25%"><b>'.$l['w_email'].'</b></td><td width="15%"><b>'.$l['w_phone'].'</b></td></tr>';

for ($x = 0; $x < count($perfList); $x++) {
	$emailList = array();
	$pageno = 1;
	$nboflineonpage  = 0;
	// Page header
	$pageheader1  = '<p align="center" style="font-size:20px"><b>'.$perfList[$x]['showlabel'].' - '. $l['w_title'] .'</h1></b>';
	$pageheader2  = '<p align="center"><b>'.$perfList[$x]['perflabel'].'</b></p>';
	$pageheader2b = '<p align="center"><b>'.$perfList[$x]['perflabel'].' (xpagex)</b></p>';
	$pageheader3  = '<p align="center"><b>'.$perfList[$x]['schedule'].'</b></p>';
	$html = $pageheader1.$pageheader2.$pageheader3;

	$html .= $tableheader;
	for ($y = 0; $y < count($perfList[$x]['contacts']); $y++) {
		$index = $y + 1;
		if ($nboflineonpage != 0 && (($pageno == 1 && fmod($nboflineonpage, $nboflinefirstpage) == 0) || ($pageno > 1 && fmod($nboflineonpage, $nboflinenextpage) == 0))) {
			$html = $html .'</table>';
			$pdf->AddPage('P');
			$pdf->writeHTMLCell(0, 0, '', '', utf8_decode($html), 0, 1, 0, true, '', true);
			$pageno++;
			$nboflineonpage  = 0;
			$html = str_replace("xpagex", $pageno, $pageheader2b).$tableheader;
		}
		$nboflineonpage++;
		$member = $perfList[$x]['contacts'][$y];
		if (in_array($member['email'], $emailList) == false) {
			array_push($emailList, $member['email']);
		}
		$html .= '<tr><td width="35%">'.$member['tasklabel'].'</td><td width="25%">'.$member['fullname'].'</td><td width="25%">'.$member['email'].'</td><td width="15%">'.$member['cellphone'].'</td></tr>';
	}
	$html = $html .'</table>';
	if ($nboflineonpage > $nboflinenextpageemail) {
		$pdf->AddPage('P');
		$pdf->writeHTMLCell(0, 0, '', '', utf8_decode($html), 0, 1, 0, true, '', true);
		$pageno++;
		$nboflineonpage  = 0;
		$html = str_replace("xpagex", $pageno, $pageheader2b);
	}
	$html .= '<p>'.implode(";", $emailList).'</p>';
	$pdf->AddPage('P');
	$pdf->writeHTMLCell(0, 0, '', '', utf8_decode($html), 0, 1, 0, true, '', true);
}

// ---------------------------------------------------------

// Close and output PDF document
// This method has several options, check the source code documentation for more information.
$filename = $l['w_filename'];
$filename .= $showlabel . ".pdf";
$pdf->Output($filename, 'I');

//============================================================+
// END OF FILE
//============================================================+

/**
 * This function gets the details of all tasks for a performance
 */
function getPerformanceTaskContacts($mysqli, $showsid, $perfid, $language) {
		if (empty($perfid)) throw new Exception("Invalid show performance id.");
		$query = "SELECT cspa.*, cc.*, cst.*, concat(getTextLabel(cst.label, '$language'), ' ', addinfo) tasklabel, concat(cc.firstname, ' ', cc.lastname) fullname
							FROM cpa_shows_performances_assigns cspa
							JOIN cpa_contacts cc ON cc.id = cspa.contactid
							JOIN cpa_shows_tasks cst ON cst.id = cspa.taskid
							WHERE performanceid = $perfid
							ORDER BY category";

		$result = $mysqli->query($query);
		$data = array();
		$data['data'] = array();
		while ($row = $result->fetch_assoc()) {
			$data['data'][] = $row;
		}
		$data['success'] = true;
		return $data;
};

/**
 * This function gets all the numbers for the show
 */
function getPerformanceTaskList($mysqli, $showid, $language) {
	try{
		if (empty($showid)) throw new Exception("Invalid show id.");
		$query = "SELECT csp.*, getTextLabel(csp.label, '$language') perflabel, getTextLabel(cs.label, '$language') showlabel, concat(perfdate, ' ', starttime) schedule
							FROM cpa_shows_performances csp 
							JOIN cpa_shows cs ON cs.id = csp.showid
							WHERE csp.showid = $showid 
							ORDER BY csp.perfdate";
		$result = $mysqli->query($query);
		$data = array();
		$data['data'] = array();
		while ($row = $result->fetch_assoc()) {
			$row['contacts'] = getPerformanceTaskContacts($mysqli, $showid, $row['id'], $language)['data'];
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

