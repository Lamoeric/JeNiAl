<?php
//============================================================+
// File name   : showNumbersInvitesList.php
// Begin       : 2021-11-23
// Last Update :
//
// Description : show numbers invites list - per number, list of invitations.
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
$activeonly = false;
$showid = null;
$showsnumbersid = null;
if (isset($_GET['language']) && !empty(isset($_GET['language']))) {
	$language = $_GET['language'];
}
if (isset($_GET['showid']) && !empty(isset($_GET['showid']))) {
	$showid = $_GET['showid'];
}
if (isset($_GET['showsnumbersid']) && !empty(isset($_GET['showsnumbersid']))) {
	$showsnumbersid = $_GET['showsnumbersid'];
}
if (isset($_GET['activeonly']) && !empty(isset($_GET['activeonly']))) {
	$activeonly = $_GET['activeonly'];
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
$data = getShowNumberList($mysqli, $showid, $showsnumbersid, $language, $activeonly);
$numbersList = $data['data'];
$emailList = array();
$tableheader = '<table border="1"><tr><td width="5%"><b>#</b></td><td width="30%"><b>'.$l['w_firstname'].'</b></td><td width="30%"><b>'.$l['w_lastname'].'</b></td><td width="30%"><b>'.$l['w_email'].'</b></td><td width="10%"><b>'.$l['w_registered'].'</b></td></tr>';

for ($x = 0; $x < count($numbersList); $x++) {
	$emailList = array();
	$pageno = 1;
	$nboflineonpage  = 0;
	// Page header
	$pageheader1  = '<p align="center" style="font-size:20px"><b>'.$numbersList[$x]['showlabel'].' - '. ($activeonly==true ? $l['w_titleactive'] : $l['w_title']) .'</h1></b>';
	$pageheader2  = '<p align="center"><b>'.$numbersList[$x]['numberlabel'].' ('.$numbersList[$x]['name'].')'.'</b></p>';
	$pageheader2b = '<p align="center"><b>'.$numbersList[$x]['numberlabel'].' ('.$numbersList[$x]['name'].')'.' (xpagex)</b></p>';
	$pageheader3  = '<p align="center"><b>'.$numbersList[$x]['schedule'].'</b></p>';
	$html = $pageheader1.$pageheader2.$pageheader3;

	$html .= $tableheader;
	for ($y = 0; $y < count($numbersList[$x]['members']); $y++) {
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
		$member = $numbersList[$x]['members'][$y];
		if (in_array($member['email'], $emailList) == false) {
			array_push($emailList, $member['email']);
		}
		$html .= '<tr><td width="5%">'.$index.'</td><td width="30%">'.$member['firstname'].'</td><td width="30%">'.$member['lastname'].'</td><td width="30%">'.$member['email'].'</td><td width="10%">'.$member['registered'].'</td></tr>';
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
$filename = ($activeonly==true ? $l['w_filenameactive']  : $l['w_filename']);
$filename .= $showlabel . ".pdf";
$pdf->Output($filename, 'I');

//============================================================+
// END OF FILE
//============================================================+

/**
 * This function gets the details of all members for a show number
 */
function getShowNumberMembers($mysqli, $showsnumbersid, $language, $activeonly) {
	try{
		if (empty($showsnumbersid)) throw new Exception("Invalid show number id.");
//		$query = "SELECT csci.*, cm.firstname, cm.lastname, cm.id memberid, cm.email, if(csnm.id is null, 0, 1) as registered
//							FROM cpa_shows_numbers_invites csci
//							JOIN cpa_members cm ON cm.id = csci.groupormemberid
//							JOIN cpa_shows_numbers csn ON csn.id = csci.numberid "
//							. ($activeonly == true ? "JOIN cpa_shows_numbers_members csnm ON csnm.numberid = csci.numberid AND csnm.memberid = csci.groupormemberid " : "LEFT JOIN cpa_shows_numbers_members csnm ON csnm.numberid = csci.numberid AND csnm.memberid = csci.groupormemberid ") .
//							"WHERE csci.numberid = $showsnumbersid "
//							. ($activeonly == true ? "AND csnm.registrationenddate is null ":"") .
//							"ORDER BY cm.lastname";

		$query = "SELECT csci.id, csci.groupormemberid, csci.numberid, csci.showid, csci.type, cm.firstname, cm.lastname, cm.id memberid, cm.email, if(csnm.id is null, 0, 1) as registered
							FROM cpa_shows_numbers_invites csci
							JOIN cpa_members cm ON cm.id = csci.groupormemberid
							JOIN cpa_shows_numbers csn ON csn.id = csci.numberid "
							. ($activeonly == true ? "LEFT JOIN cpa_shows_numbers_members csnm ON csnm.numberid = csci.numberid AND csnm.memberid = csci.groupormemberid ": "LEFT JOIN cpa_shows_numbers_members csnm ON csnm.numberid = csci.numberid AND csnm.memberid = csci.groupormemberid ") .
							"WHERE csci.numberid = $showsnumbersid "
							. ($activeonly == true ? "AND csnm.registrationenddate is null ":"") .
							"UNION
							SELECT csnm.id, csnm.memberid, csnm.numberid, csnm.showid, null, cm.firstname, cm.lastname, cm.id memberid, cm.email, if(csnm.id is null, 0, 1) as registered
							FROM cpa_shows_numbers_members csnm
							JOIN cpa_members cm ON cm.id = csnm.memberid
							WHERE csnm.numberid = $showsnumbersid
							AND (csnm.memberid not in (SELECT csci.groupormemberid FROM cpa_shows_numbers_invites csci WHERE csci.numberid = $showsnumbersid))
							ORDER BY 7,6";

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

/**
 * This function gets all the numbers for the show
 */
function getShowNumberList($mysqli, $showid, $showsnumbersid, $language, $activeonly) {
	try{
		if (empty($showid)) throw new Exception("Invalid show id.");
		if (!isset($showsnumbersid) || $showsnumbersid == '') {
			$query = "SELECT csn.id, csn.name, '-' as maxnumberskater, getTextLabel(csn.label, '$language') numberlabel, '' as courselevellabel, 
								getTextLabel(cs.label, '$language') showlabel, getNumberSchedule(csn.id, '$language') AS schedule,
								(select count(*) from cpa_shows_numbers_members where numberid = csn.id) nbofskater
						FROM cpa_shows_numbers csn
						JOIN cpa_shows cs ON cs.id = csn.showid
						WHERE cs.id = $showid
						AND csn.type = 1
						ORDER BY name";
		} else {
			$query = "SELECT csn.id, csn.name, '-' as maxnumberskater, getTextLabel(csn.label, '$language') numberlabel, '' as courselevellabel, 
								getTextLabel(cs.label, '$language') showlabel, getNumberSchedule(csn.id, '$language') AS schedule,
								(select count(*) from cpa_shows_numbers_members where numberid = csn.id) nbofskater
						FROM cpa_shows_numbers csn
						JOIN cpa_shows cs ON cs.id = csn.showid
						WHERE csn.id = $showsnumbersid
						AND csn.type = 1
						ORDER BY name";
		}
		$result = $mysqli->query($query);
		$data = array();
		$data['data'] = array();
		while ($row = $result->fetch_assoc()) {
			$row['members'] = getShowNumberMembers($mysqli, $row['id'], $language, $activeonly)['data'];
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

