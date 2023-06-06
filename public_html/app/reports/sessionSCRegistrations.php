<?php
//============================================================+
// File name   : sessionSCRegistrations.php
// Begin       : 2018-11-04
// Last Update :
//
// Description : session Skate Canada registrations list - list all skaters registered to this session that are not regsitered in Skate Canada
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
require_once('getSessionLabel.php');

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

$nboflinefirstpage = 43;
$nboflinenextpage  = 49;
$nboflineonpage  = 0;
$sessionlabel = getSessionLabel($mysqli, $sessionid, $language)['data'][0]['sessionlabel'];
$SCRegistrationYear = getSessionSCRegistrationYear($mysqli, $sessionid);
$members = getUnregisteredMemberList($mysqli, $sessionid, $SCRegistrationYear)['data'];

$pageheader  = '<p align="center" style="font-size:20px"><b>' . $l['w_title'] . '<br>' . $sessionlabel . '</h1></b><p align="center"><b>' . $l['w_title2'] . $SCRegistrationYear . '</b></p>';
$tableheader = '<table border="1"><tr><td width="5%"><b>#</b></td><td width="25%"><b>'.$l['w_skatecanadano'].'</b></td><td width="25%"><b>'.$l['w_firstname'].'</b></td><td width="25%"><b>'.$l['w_lastname'].'</b></td></tr>';
$pageno = 1;
$nboflineonpage  = 0;

$html = $pageheader . $tableheader;

for ($y = 0; $y < count($members); $y++) {
	$index = $y + 1;
	if ($nboflineonpage != 0 && (($pageno == 1 && fmod($nboflineonpage, $nboflinefirstpage) == 0) || ($pageno > 1 && fmod($nboflineonpage, $nboflinenextpage) == 0))) {
		$html = $html .'</table>';
		$pdf->AddPage('P');
		$pdf->writeHTMLCell(0, 0, '', '', utf8_decode($html), 0, 1, 0, true, '', true);
		$pageno++;
		$nboflineonpage  = 0;
		$html = /*$pageheader.*/$tableheader;
	}
	$nboflineonpage++;
	$member = $members[$y];
	$html .= '<tr><td width="5%">' . $index . '</td><td width="25%">' . $member['skatecanadano'] . '</td><td width="25%">' . $member['firstname'] . '</td><td width="25%">' . $member['lastname'] . '</td></tr>';

}
$html = $html .'</table>';
$pdf->AddPage('P');
$pdf->writeHTMLCell(0, 0, '', '', utf8_decode($html), 0, 1, 0, true, '', true);

// ---------------------------------------------------------

// Close and output PDF document
// This method has several options, check the source code documentation for more information.
$filename = $l['w_filename'] . $sessionlabel . ".pdf";
$pdf->Output($filename, 'I');

//============================================================+
// END OF FILE
//============================================================+

/**
 * This function returns the estimated SC registration year
 */
function getSessionSCRegistrationYear($mysqli, $sessionid) {
	try{
		if (empty($sessionid)) throw new Exception("Invalid session id.");
		$query = "SELECT concat(year(startdate), '/',  year(startdate)+1) scregistrationyear FROM cpa_sessions WHERE id = $sessionid";
		$result = $mysqli->query($query);
		$row = $result->fetch_assoc();
		return $row['scregistrationyear'];
	}catch (Exception $e) {
		$data = array();
		$data['success'] = false;
		$data['message'] = $e->getMessage();
		return $data;
	}
};

/**
 * This function gets the list of members, registered for the session, but whose SC no doesn't appears in the SC registration table
 */
function getUnregisteredMemberList($mysqli, $sessionsid, $scregistrationyear) {
	try{
		if (empty($sessionsid)) throw new Exception("Invalid session id.");
		if (empty($scregistrationyear)) throw new Exception("Invalid SC registration year.");
		$query = "SELECT cm.skatecanadano, cm.firstname, cm.lastname
							FROM cpa_members cm
							WHERE cm.id IN (SELECT DISTINCT cscm.memberid
							                FROM cpa_sessions_courses_members cscm
							                JOIN cpa_sessions_courses csc ON csc.id = cscm.sessionscoursesid
															JOIN cpa_sessions cs ON cs.id = csc.sessionid
                              WHERE cs.id = $sessionsid)
							AND (cm.skatecanadano NOT IN (SELECT cscr.skatecanadano
							                             FROM cpa_skate_canada_registrations cscr
							                             WHERE cscr.registrationyear = '$scregistrationyear')
							OR cm.skatecanadano is null)
							ORDER BY cm.lastname, cm.firstname";
		$result = $mysqli->query($query);
		$data = array();
		$data['data'] = array();
		while ($row = $result->fetch_assoc()) {
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
