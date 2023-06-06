<?php
//============================================================+
// File name   : sessionMemberEmailList.php
// Begin       : 2017-09-15
// Last Update :
//
// Description : session member list with email
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
require_once('getActiveSessionLabel.php');
require_once('createFileName.php');

set_time_limit(500);

// Input parameters
$parameters = null;
$sessionid = 8;
if (isset($_POST['parameters']) && !empty(isset($_POST['parameters']))) {
	$parameters = $_POST['parameters'];
  // $sessionid = $parameters['sessionid'];
} else {
	// For testing
	$parameters['registration'] = "REGISTERED";
	$parameters['courses'] = array();
	$parameters['courses'][0] = "47";
	$parameters['courses'][1] = "51";
	$test = implode(",", $parameters['courses']);
	//  echo $test;
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

$nboflinefirstpage = 37;
$nboflinenextpage  = 39;
$pageno = 1;
$nboflineonpage  = 0;

$data = getActiveSessionLabel($mysqli, $language);
$sessionlabel = $data['data'][0]['sessionlabel'];
$data = getSessionMembersList($mysqli, $parameters, $language);
$membersList = $data['data'];
$emailList = array();

// Page header
$pageheader1  = '<p align="center" style="font-size:20px"><b>'.$sessionlabel.' - '.$l['w_title'].'</h1></b>';
$tableheader = '<table border="1 "cellpadding="2"><tr><td width="5%"><b>#</b></td><td width="25%"><b>'.$l['w_firstname'].'</b></td><td width="25%"><b>'.$l['w_lastname'].'</b></td><td width="50%"><b>'.$l['w_email'].'</b></td></tr>';
$html = $pageheader1;
$html .= $tableheader;
for ($x = 0; $x < count($membersList); $x++) {
	$index = $x + 1;
	if ($nboflineonpage != 0 && (($pageno == 1 && fmod($nboflineonpage, $nboflinefirstpage) == 0) || ($pageno > 1 && fmod($nboflineonpage, $nboflinenextpage) == 0))) {
		$html = $html .'</table>';
		$pdf->AddPage('P');
		$pdf->writeHTMLCell(0, 0, '', '', utf8_decode($html), 0, 1, 0, true, '', true);
		$pageno++;
		$nboflineonpage  = 0;
		$html = $tableheader;
	}
	$nboflineonpage++;
	$member = $membersList[$x];
	$html .= '<tr><td width="5%">'.$index.'</td><td width="25%">'.$member['skaterfirstname'].'</td><td width="25%">'.$member['skaterlastname'].'</td><td width="50%">'.$member['email'].'</td></tr>';
	array_push($emailList, $member['email']);
  for ($y = 0; $y < count($member['contacts']); $y++) {
		if ($nboflineonpage != 0 && (($pageno == 1 && fmod($nboflineonpage, $nboflinefirstpage) == 0) || ($pageno > 1 && fmod($nboflineonpage, $nboflinenextpage) == 0))) {
			$html = $html .'</table>';
			$pdf->AddPage('P');
			$pdf->writeHTMLCell(0, 0, '', '', utf8_decode($html), 0, 1, 0, true, '', true);
			$pageno++;
			$nboflineonpage  = 0;
			$html = $tableheader;
		}
		$nboflineonpage++;
		$contact = $member['contacts'][$y];
		$html .= '<tr><td colspan="3" align="right">'.$contact['contactfirstname'].' '.$contact['contactlastname'].' ('.$contact['contacttypelabel'].')'.'</td><td width="50%">'.$contact['email'].'</td></tr>';
		array_push($emailList, $contact['email']);
  }
}
$html = $html .'</table>';
$pdf->AddPage('P');
$pdf->writeHTMLCell(0, 0, '', '', utf8_decode($html), 0, 1, 0, true, '', true);
// array_unique($emailList);
// $html = '<pre>' . implode("; ", array_unique($emailList)) . '</pre>';
$html = implode("; ", array_unique($emailList));
// $html = "toto";
$pdf->AddPage('P');
$pdf->writeHTMLCell(0, 0, '', '', utf8_decode($html), 0, 1, 0, true, '', true);

// ---------------------------------------------------------

// Close and output PDF document
// This method has several options, check the source code documentation for more information.
// $filename =  $l['w_filename'];
// $filename .= $sessionlabel . ".pdf";
// $pdf->Output($filename, 'I');

$filename = createFileName();
$pdf->Output($filename, 'F');
$filename = convertFileName($mysqli, $filename);
echo $filename;
//============================================================+
// END OF FILE
//============================================================+

/**
 * This function gets the details of all members
 */
function getSessionMembersList($mysqli, $parameters, $language) {
	try{
		$where = " WHERE 1=1";
		if ($parameters['registration'] == "ALL") {

		} else if ($parameters['registration'] == "REGISTERED") {
			$where .= " and cm.id in (select memberid from cpa_sessions_courses_members where sessionscoursesid in (select id from cpa_sessions_courses where sessionid = (select id from cpa_sessions where active = '1')))";
		} else if ($parameters['registration'] == "NOTREGISTERED") {
			$where .= " and cm.id not in (select memberid from cpa_sessions_courses_members where sessionscoursesid in (select id from cpa_sessions_courses where sessionid = (select id from cpa_sessions where active = '1')))" ;
		} else if ($parameters['registration'] == "PERCOURSE") {
			$test = implode(",", $parameters['selectedCourses']);
			$where .= " and cm.id in (select memberid from cpa_sessions_courses_members where sessionscoursesid in (" . $test  . "))";
		}
		if (!empty($parameters['selectedQualifications'])) {
			$where .= " and (";
			$tempWhere = "";
			for($x = 0; $x < count($parameters['selectedQualifications']); $x++) {
				if (!empty($tempWhere)) $tempWhere .= " and ";
				$tempWhere .= " cm.qualifications like '%" .  $parameters['selectedQualifications'][$x]  . "%'";
			}
			$where .= $tempWhere . ")";
		}
		$query = "SELECT cm.id, cm.firstname skaterfirstname, cm.lastname skaterlastname, cm.email
              FROM cpa_members cm " . $where . " ORDER BY cm.lastname, cm.firstname";
              // WHERE id <= 100";
		$result = $mysqli->query($query);
		$data = array();
		$data['data'] = array();
		while ($row = $result->fetch_assoc()) {
      $row['contacts'] = getMemberContactList($mysqli, $row['id'], $language)['data'];
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
 * This function gets the contacts of a member
 */
function getMemberContactList($mysqli, $memberid) {
	$query = "SELECT cc.firstname contactfirstname, cc.lastname contactlastname, cc.email, cmc.contacttype, getCodeDescription('contacttypes', cmc.contacttype, 'fr-ca') contacttypelabel
            FROM cpa_contacts cc
            JOIN cpa_members_contacts cmc ON cmc.contactid = cc.id
            WHERE cmc.memberid = $memberid";
	$result = $mysqli->query($query);
	$data = array();
	$data['data'] = array();
	while ($row = $result->fetch_assoc()) {
		$data['data'][] = $row;
	}
	$data['success'] = true;
	return $data;
};
