<?php
//============================================================+
// File name   : showsTickets3X2.php
//
// Description : Show's tickets in a 3 X 2 format
//
// Author: Eric Lamoureux
//
//============================================================+

// Include the main TCPDF library (search for installation path).
require_once('../../include/tcpdf_include.php');
require_once('../../../private/'. $_SERVER['HTTP_HOST'].'/include/config.php');
require_once('../../include/nocache.php');
//require_once('customheader.php');
require_once('mypdf_footer.php');
require_once('getClubNameAndAddress.php');
require_once('getShowLabel.php');

// Input parameters
$showid = null;
$performanceid = null;
if (isset($_GET['showid']) && !empty(isset($_GET['showid']))) {
	$showid = $_GET['showid'];
}
if (isset($_GET['performanceid']) && !empty(isset($_GET['performanceid']))) {
	$performanceid = $_GET['performanceid'];
}

// For testing
//$language = 1;  // 1 = french, 2 = english, 3 = bilingual
//$ticketWithStub  = 0;
//$ticketWriteStubInfo = 0;
//$ticketWithImage = 0;
//$ticketWriteStdInfo = 1;
//$ticketImageFile = K_PATH_IMAGES.'reports/canskate/ticket_test_stub_nowrite.jpg';
//$ticketColor = array(255, 255, 255);
//$stubColor = array(255, 255, 255);
//$ticketNotesFr = 'Ouverture des portes une demie-heure avant le spectacle. <br>Siège réservé - non remboursable';
//$ticketNotesEn = 'Doors open half a hour before the show.<br>Seat reserved - non refundable';

// create new PDF document
$pdf = new MYPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, false, 'UTF-8', false);

// set document information
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor(PDF_AUTHOR);
$pdf->SetTitle('');
$pdf->SetSubject('');
$pdf->SetKeywords('');

// set header and footer fonts
$pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));

// set default monospaced font
$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

// set margins
$pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
$pdf->SetHeaderMargin(0);
$pdf->SetFooterMargin(0);
$pdf->setAutoPageBreak(false);

// Remove header
$pdf->setPrintHeader(false);

// remove default footer
$pdf->setPrintFooter(false);

// set image scale factor
$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

// set some language-dependent strings (optional)
//if (@file_exists(dirname(__FILE__).'/lang/'.$language.'/'.basename(__FILE__))) {
//	require_once(dirname(__FILE__).'/lang/'.$language.'/'.basename(__FILE__));
//	$pdf->setLanguageArray($l);
//}

require_once(dirname(__FILE__).'/lang/fr-ca/'.basename(__FILE__));
$pdf->setLanguageArray($l);

// ---------------------------------------------------------

ResetAllValues($pdf);
$pass = array("Normal", "Reserve");
// Get the values from the BD
$data = getPerformance($mysqli, $showid, $performanceid)['data'][0];
$seating = $data['seating'][0];
$ticket = $data['ticket'][0];
$nbofsections = $seating['sectionnb'];
$nbofrows = $seating['rownb'];
$nbofseats = $seating['seatnb'];
$sectionNo = $seating['sectionfirst'];
$rowNo = $seating['rowfirst'];
$seatNo = $seating['seatfirst'];
$clubData = getClubNameAndAddress($mysqli, 'fr-ca');
$clubTitleFr = utf8_decode($clubData['data'][0]['cpalongname']);
$clubData = getClubNameAndAddress($mysqli, 'en-ca');
$clubTitleEn = utf8_decode($clubData['data'][0]['cpalongname']);
$showData = getShowLabel($mysqli, $showid, 'fr-ca');
$showTitleFr = utf8_decode($showData['data'][0]['showlabel']);
$showData = getShowLabel($mysqli, $showid, 'en-ca');
$showTitleEn = utf8_decode($showData['data'][0]['showlabel']);

$language = $ticket['language'];
$ticketWithStub  = $ticket['showstub'];
$ticketWriteStubInfo = $ticket['showstubinfo'];
$ticketWithImage = $ticket['showimage'];
$ticketWriteStdInfo = $ticket['showstandardinfo'];
$ticketImageFile = K_PATH_WEBSITEIMAGES.'shows/'.$ticket['imagefilename'];
$ticketColor = list($r, $g, $b) = sscanf($ticket['ticketcolor'], "#%02x%02x%02x"); // convert RGB in a array of R,G,B
$stubColor = list($r, $g, $b) = sscanf($ticket['stubcolor'], "#%02x%02x%02x"); // convert RGB in a array of R,G,B
$ticketNotes = utf8_decode($ticket['notes']);
$needles = array("<br>", "&#13;", "<br/>", "\n", "&#13;&#10;", "&#10;", "\r\n");
$replacement = "<br>";
$ticketNotes = str_replace($needles, $replacement, $ticketNotes);

//print_r($ticketColor);
//exit;

// Styles
// stubStyle : a dash line on the left
$stubStyle = array('T' => array('width' => .2, 'cap' => 'round', 'join' => 'round', 'dash' => 0), 'color' => array(0, 0, 0),
                	 'R' => array('width' => .2, 'cap' => 'round', 'join' => 'round', 'dash' => 0), 'color' => array(0, 0, 0),
                	 'B' => array('width' => .2, 'cap' => 'round', 'join' => 'round', 'dash' => 0), 'color' => array(0, 0, 0),
                	 'L' => array('width' => 1, 'cap' => 'round', 'join' => 'round', 'dash' => '10,10', 'color' => array(0, 0, 0)));

// Positions
$stubWidth = 36;
$ticketPositions = array(0=>array('x'=>1, 	'y'=>10, 	'w'=>147, 'h'=>60),
												 1=>array('x'=>149, 'y'=>10, 	'w'=>147, 'h'=>60),
												 2=>array('x'=>1, 	'y'=>72, 	'w'=>147, 'h'=>60),
												 3=>array('x'=>149, 'y'=>72, 	'w'=>147, 'h'=>60),
												 4=>array('x'=>1, 	'y'=>134, 'w'=>147, 'h'=>60),
												 5=>array('x'=>149, 'y'=>134, 'w'=>147, 'h'=>60));

$stubPositions = array(0=>array('x'=>111, 'y'=>10, 	'w'=>$stubWidth, 'h'=>60),
											 1=>array('x'=>260, 'y'=>10, 	'w'=>$stubWidth, 'h'=>60),
											 2=>array('x'=>111, 'y'=>72, 	'w'=>$stubWidth, 'h'=>60),
											 3=>array('x'=>260, 'y'=>72, 	'w'=>$stubWidth, 'h'=>60),
											 4=>array('x'=>111, 'y'=>134, 'w'=>$stubWidth, 'h'=>60),
											 5=>array('x'=>260, 'y'=>134, 'w'=>$stubWidth, 'h'=>60));

$ticketLogo = array(0=>array('x'=>3, 		'y'=>11, 	'w'=>12, 'h'=>0),
										1=>array('x'=>151, 	'y'=>11, 	'w'=>12, 'h'=>0),
										2=>array('x'=>3, 		'y'=>73,	'w'=>12, 'h'=>0),
										3=>array('x'=>151, 	'y'=>73,	'w'=>12, 'h'=>0),
										4=>array('x'=>3, 		'y'=>135, 'w'=>12, 'h'=>0),
										5=>array('x'=>151, 	'y'=>135, 'w'=>12, 'h'=>0));

$ticketClubName = array(0=>array('x'=>15, 	'y'=>12, 	'w'=>130, 'h'=>0),
											  1=>array('x'=>163, 	'y'=>12, 	'w'=>130, 'h'=>0),
												2=>array('x'=>15, 	'y'=>74,	'w'=>130, 'h'=>0),
												3=>array('x'=>163, 	'y'=>74,	'w'=>130, 'h'=>0),
												4=>array('x'=>15, 	'y'=>136, 'w'=>130, 'h'=>0),
												5=>array('x'=>163, 	'y'=>136, 'w'=>130, 'h'=>0));

$ticketSeatingSection = array(0=>array('x'=>8, 		'y'=>64, 	'w'=>12, 'h'=>0),
											 				1=>array('x'=>157, 	'y'=>64, 	'w'=>12, 'h'=>0),
											 				2=>array('x'=>8, 		'y'=>126,	'w'=>12, 'h'=>0),
											 				3=>array('x'=>157, 	'y'=>126,	'w'=>12, 'h'=>0),
											 				4=>array('x'=>8, 		'y'=>188, 'w'=>12, 'h'=>0),
											 				5=>array('x'=>157, 	'y'=>188, 'w'=>12, 'h'=>0));

$ticketSeatingRow = array(0=>array('x'=>30, 	'y'=>64, 	'w'=>17, 'h'=>0),
											 		1=>array('x'=>179, 	'y'=>64, 	'w'=>17, 'h'=>0),
											 		2=>array('x'=>30, 	'y'=>126,	'w'=>17, 'h'=>0),
											 		3=>array('x'=>179, 	'y'=>126,	'w'=>17, 'h'=>0),
											 		4=>array('x'=>30, 	'y'=>188, 'w'=>17, 'h'=>0),
											 		5=>array('x'=>179, 	'y'=>188, 'w'=>17, 'h'=>0));

$ticketSeatingSeat = array(0=>array('x'=>62, 	'y'=>64, 	'w'=>17, 'h'=>0),
											 		 1=>array('x'=>211,	'y'=>64, 	'w'=>17, 'h'=>0),
											 		 2=>array('x'=>62, 	'y'=>126,	'w'=>17, 'h'=>0),
											 		 3=>array('x'=>211,	'y'=>126,	'w'=>17, 'h'=>0),
											 		 4=>array('x'=>62, 	'y'=>188, 'w'=>17, 'h'=>0),
											 		 5=>array('x'=>211,	'y'=>188, 'w'=>17, 'h'=>0));

$ticketSeatingSectionValue = array(0=>array('x'=>20, 		'y'=>64, 	'w'=>15, 'h'=>0),
											 						 1=>array('x'=>169, 	'y'=>64, 	'w'=>15, 'h'=>0),
											 						 2=>array('x'=>20, 		'y'=>126,	'w'=>15, 'h'=>0),
											 						 3=>array('x'=>169, 	'y'=>126,	'w'=>15, 'h'=>0),
											 						 4=>array('x'=>20, 		'y'=>188, 'w'=>15, 'h'=>0),
											 						 5=>array('x'=>169, 	'y'=>188, 'w'=>15, 'h'=>0));

$ticketSeatingRowValue = array(0=>array('x'=>47, 	'y'=>64, 	'w'=>15, 'h'=>0),
											 				 1=>array('x'=>196, 'y'=>64, 	'w'=>15, 'h'=>0),
											 				 2=>array('x'=>47, 	'y'=>126,	'w'=>15, 'h'=>0),
											 				 3=>array('x'=>196, 'y'=>126,	'w'=>15, 'h'=>0),
											 				 4=>array('x'=>47, 	'y'=>188, 'w'=>15, 'h'=>0),
											 				 5=>array('x'=>196, 'y'=>188, 'w'=>15, 'h'=>0));

$ticketSeatingSeatValue = array(0=>array('x'=>79, 	'y'=>64, 	'w'=>15, 'h'=>0),
											 		 			1=>array('x'=>228,	'y'=>64, 	'w'=>15, 'h'=>0),
											 		 			2=>array('x'=>79, 	'y'=>126,	'w'=>15, 'h'=>0),
											 		 			3=>array('x'=>228,	'y'=>126,	'w'=>15, 'h'=>0),
											 		 			4=>array('x'=>79, 	'y'=>188, 'w'=>15, 'h'=>0),
											 		 			5=>array('x'=>228,	'y'=>188, 'w'=>15, 'h'=>0));

$ticketPerfDate = array(0=>array('x'=>3, 		'y'=>60, 	'w'=>40, 'h'=>0),
											  1=>array('x'=>152, 	'y'=>60, 	'w'=>40, 'h'=>0),
											  2=>array('x'=>3, 		'y'=>122,	'w'=>40, 'h'=>0),
											  3=>array('x'=>152, 	'y'=>122,	'w'=>40, 'h'=>0),
											  4=>array('x'=>3, 		'y'=>184, 'w'=>40, 'h'=>0),
											  5=>array('x'=>152, 	'y'=>184, 'w'=>40, 'h'=>0));

$stubPerfDate = array(0=>array('x'=>120, 	'y'=>11, 	'w'=>30, 'h'=>0),
											1=>array('x'=>269, 	'y'=>11, 	'w'=>30, 'h'=>0),
											2=>array('x'=>120, 	'y'=>73,	'w'=>30, 'h'=>0),
											3=>array('x'=>269, 	'y'=>73,	'w'=>30, 'h'=>0),
											4=>array('x'=>120, 	'y'=>135, 'w'=>30, 'h'=>0),
											5=>array('x'=>269, 	'y'=>135, 'w'=>30, 'h'=>0));

$stubSeatingSection = array(0=>array('x'=>115, 	'y'=>26, 	'w'=>12, 'h'=>0),
											 			1=>array('x'=>264, 	'y'=>26, 	'w'=>12, 'h'=>0),
											 			2=>array('x'=>115, 	'y'=>88,	'w'=>12, 'h'=>0),
											 			3=>array('x'=>264, 	'y'=>88,	'w'=>12, 'h'=>0),
											 			4=>array('x'=>115, 	'y'=>150, 'w'=>12, 'h'=>0),
											 			5=>array('x'=>264, 	'y'=>150, 'w'=>12, 'h'=>0));

$stubSeatingRow = array(0=>array('x'=>115, 	'y'=>36, 	'w'=>17, 'h'=>0),
									 			1=>array('x'=>264, 	'y'=>36, 	'w'=>17, 'h'=>0),
									 			2=>array('x'=>115, 	'y'=>98,	'w'=>17, 'h'=>0),
									 			3=>array('x'=>264, 	'y'=>98,	'w'=>17, 'h'=>0),
									 			4=>array('x'=>115, 	'y'=>160, 'w'=>17, 'h'=>0),
									 			5=>array('x'=>264, 	'y'=>160, 'w'=>17, 'h'=>0));

$stubSeatingSeat = array(0=>array('x'=>115, 	'y'=>46, 	'w'=>17, 'h'=>0),
									 			 1=>array('x'=>264, 	'y'=>46, 	'w'=>17, 'h'=>0),
									 			 2=>array('x'=>115, 	'y'=>108,	'w'=>17, 'h'=>0),
									 			 3=>array('x'=>264, 	'y'=>108,	'w'=>17, 'h'=>0),
									 			 4=>array('x'=>115, 	'y'=>170, 'w'=>17, 'h'=>0),
									 			 5=>array('x'=>264, 	'y'=>170, 'w'=>17, 'h'=>0));

$stubSeatingSectionValue = array(0=>array('x'=>115, 	'y'=>29, 	'w'=>15, 'h'=>0),
											 					 1=>array('x'=>264, 	'y'=>29, 	'w'=>15, 'h'=>0),
											 					 2=>array('x'=>115, 	'y'=>91,	'w'=>15, 'h'=>0),
											 					 3=>array('x'=>264, 	'y'=>91,	'w'=>15, 'h'=>0),
											 					 4=>array('x'=>115, 	'y'=>153, 'w'=>15, 'h'=>0),
											 					 5=>array('x'=>264, 	'y'=>153, 'w'=>15, 'h'=>0));

$stubSeatingRowValue = array(0=>array('x'=>115, 	'y'=>39, 	'w'=>15, 'h'=>0),
									 					 1=>array('x'=>264, 	'y'=>39, 	'w'=>15, 'h'=>0),
									 					 2=>array('x'=>115, 	'y'=>101,	'w'=>15, 'h'=>0),
									 					 3=>array('x'=>264, 	'y'=>101,	'w'=>15, 'h'=>0),
									 					 4=>array('x'=>115, 	'y'=>163, 'w'=>15, 'h'=>0),
									 					 5=>array('x'=>264, 	'y'=>163, 'w'=>15, 'h'=>0));

$stubSeatingSeatValue = array(0=>array('x'=>115, 	'y'=>49, 	'w'=>15, 'h'=>0),
									 			 			1=>array('x'=>264, 	'y'=>49, 	'w'=>15, 'h'=>0),
									 			 			2=>array('x'=>115, 	'y'=>111,	'w'=>15, 'h'=>0),
									 			 			3=>array('x'=>264, 	'y'=>111,	'w'=>15, 'h'=>0),
									 			 			4=>array('x'=>115, 	'y'=>173, 'w'=>15, 'h'=>0),
									 			 			5=>array('x'=>264, 	'y'=>173, 'w'=>15, 'h'=>0));

//Init language dependant string
if ($language == 1) {
	$section = $l['section_fr'];
	$row = $l['row_fr'];
	$seat = $l['seat_fr'];
	$present = $l['present_fr'];
	$monthList = $l['monthNames_fr'];
	$showDate = utf8_decode($data['weekdayfrenchlabel']) . ' ' . $data['dayno'] . ' ' . $monthList[$data['monthno']] . ' ' . $data['yearno'] . ' ' . $data['starttimeformattedfr'];
	$clubTitle = $clubTitleFr;
	$showTitle = $showTitleFr;
	$iceLabel = isset($data['icefrenchlabel']) ? utf8_decode($data['icefrenchlabel']) : null;
	if (isset($iceLabel)) {
		$arenaLabel = utf8_decode($data['arenafrenchlabel']) . ', ' . $iceLabel;
	} else {
		$arenaLabel = utf8_decode($data['arenafrenchlabel']);
	}
} else if ($language == 2) {
	$section = $l['section_en'];
	$row = $l['row_en'];
	$seat = $l['seat_en'];
	$present = $l['present_en'];
	$monthList = $l['monthNames_en'];
	$showDate = utf8_decode($data['weekdayenglishlabel']) . ' ' . $monthList[$data['monthno']] . ' ' . $data['dayno'] . ' ' . $data['yearno'] . ' ' . $data['starttimeformatteden'];
	$clubTitle = $clubTitleEn;
	$showTitle = $showTitleEn;
	$iceLabel = isset($data['iceenglishlabel']) ? utf8_decode($data['iceenglishlabel']) : null;
	if (isset($iceLabel)) {
		$arenaLabel = utf8_decode($data['arenaenglishlabel']) . ', ' . $iceLabel;
	} else {
		$arenaLabel = utf8_decode($data['arenaenglishlabel']);
	}
}
// else if ($language == 3) {
//	$section = $l['section_bi'];
//	$row = $l['row_bi'];
//	$seat = $l['seat_bi'];
//	$present = $l['present_bi'];
//	$monthList = $l['monthNames_bi'];
//	$showDate = utf8_decode($data['weekdayfrenchlabel']) . ' ' . $data['dayno'] . ' ' . $monthList[$data['monthno']] . ' ' . $data['yearno'] . ' ' . $data['starttimeformatted'];
//}	

if ($nbofsections == 0 || $nbofrows == 0 || $nbofseats == 0) {
	$pdf->AddPage('L');
	$html = '<b>Aucun billet à imprimer / No ticket to print</b>';
	$pdf->writeHTMLCell(0, 0, 50, 50, $html, 0, 1, 1, true, 'L', true);
}

for ($i = 0; $i < count($pass); $i++) {
	$position = 0;
	$firstTicket = true;
	for ($x = 0; $x < $nbofsections; $x++) {
		$sectionNo = getNextIncrement($seating['sectiontype'], $seating['sectionfirst'], $seating['sectioninc'], $x);
		for ($y = 0; $y < $nbofrows; $y++) {
			$rowNo = getNextIncrement($seating['rowtype'], $seating['rowfirst'], $seating['rowinc'], $y);
			for ($z = 0; $z < $nbofseats; $z++) {
				$seatNo = getNextIncrement($seating['seattype'], $seating['seatfirst'], $seating['seatinc'], $z);
				if (isSeatValid($sectionNo, $rowNo, $seatNo, $seating['exceptions'], $i) == true) {
					if ($firstTicket == true || $position == 6) {
						$firstTicket = false;
						$position = 0;
						// add a page
						$pdf->AddPage('L');
						// set the starting point for the page content
						$pdf->setPageMark();
						// set color for background
						$pdf->SetFillColor(255, 255, 255);
						// Footer
						$w_page = isset($pdf->l['w_page']) ? $pdf->l['w_page'] . ' ' : '';
						$pagenumtxt = $w_page . $pdf->getAliasNumPage() . ' / ' . $pdf->getAliasNbPages();
						$html = '<table><tr><td width="33%">JeNiAl</td><td width="33%" align="center">'.($i==1?'Réservé':'').'</td><td align="right">'.$pagenumtxt.'</td></tr></table>';
						$pdf->writeHTMLCell(0, 0, PDF_MARGIN_RIGHT, 200, $html, 0, 1, 1, true, 'L', true);
						$pdf->setPageMark();
					}
					
					// Ticket rectangle	
					//    Rect($x, $y, $w, $h, $style='', $border_style=array(), $fill_color=array())
					$pdf->Rect($ticketPositions[$position]['x'], $ticketPositions[$position]['y'], $ticketPositions[$position]['w'], $ticketPositions[$position]['h'], 'DF', array(), $ticketColor);
					
					// Stub rectangle
					if ($ticketWithStub == 1) {
						$pdf->Rect($stubPositions[$position]['x'], $stubPositions[$position]['y'], $stubPositions[$position]['w'], $stubPositions[$position]['h'], 'DF', $stubStyle, $stubColor);
					}
					
					// Reset all values
					resetAllValues($pdf);
					
					if ($ticketWithImage == 1) {
						if ($ticketWithStub == 1) {
							//    Image($file, $x='', $y='', $w=0, $h=0, $type='', $link='', $align='', $resize=false, $dpi=300, $palign='', $ismask=false, $imgmask=false, $border=0, $fitbox=false, $hidden=false, $fitonpage=false)
							$pdf->Image($ticketImageFile, $ticketPositions[$position]['x'], $ticketPositions[$position]['y'], $ticketPositions[$position]['w']-$stubWidth, $ticketPositions[$position]['h'], 'JPG', '', '', false, 300, '', false, false, 1, false, false, false);
						} else {
							$pdf->Image($ticketImageFile, $ticketPositions[$position]['x'], $ticketPositions[$position]['y'], $ticketPositions[$position]['w'], $ticketPositions[$position]['h'], 'JPG', '', '', false, 300, '', false, false, 1, false, false, false);
						}
					}

					// Print all standard info on ticket
					if ($ticketWriteStdInfo == 1) {
						$titleWidth = $ticketWithStub == 0 ? $ticketClubName[$position]['w'] : $ticketClubName[$position]['w'] - $stubWidth;
						$pdf->Image(K_PATH_PRIVATEIMAGES.PDF_HEADER_LOGO, $ticketLogo[$position]['x'], $ticketLogo[$position]['y'], $ticketLogo[$position]['w'], $ticketLogo[$position]['h'], 'JPG', '', '', false, 300, '', false, false, 0, false, false, false);
						$pdf->SetFont('times', '', 12);
						$pdf->writeHTMLCell($titleWidth, $ticketClubName[$position]['h'], $ticketClubName[$position]['x'], $ticketClubName[$position]['y'], $clubTitle, 0, 1, 1, true, 'C', true);
						$pdf->writeHTMLCell($titleWidth, $ticketClubName[$position]['h'], $ticketClubName[$position]['x'], $ticketClubName[$position]['y']+5, $present, 0, 1, 1, true, 'C', true);
						$pdf->writeHTMLCell($titleWidth, $ticketClubName[$position]['h'], $ticketClubName[$position]['x'], $ticketClubName[$position]['y']+10, $showTitle, 0, 1, 1, true, 'C', true);
						$pdf->writeHTMLCell($titleWidth, $ticketClubName[$position]['h'], $ticketClubName[$position]['x'], $ticketClubName[$position]['y']+20, $showDate, 0, 1, 1, true, 'C', true);
						$pdf->writeHTMLCell($titleWidth, $ticketClubName[$position]['h'], $ticketClubName[$position]['x'], $ticketClubName[$position]['y']+25, $arenaLabel, 0, 1, 1, true, 'C', true);
						$pdf->SetFont('times', '', 10);
						$pdf->writeHTMLCell($titleWidth, $ticketClubName[$position]['h'], $ticketClubName[$position]['x'], $ticketClubName[$position]['y']+30, utf8_decode($data['arenaaddress']), 0, 1, 1, true, 'C', true);
						$pdf->SetFont('times', '', 8);
						$pdf->writeHTMLCell($titleWidth, $ticketClubName[$position]['h'], $ticketClubName[$position]['x'], $ticketClubName[$position]['y']+40, $ticketNotes, 0, 1, 1, true, 'L', true);
					}

					// Reset all values
					resetAllValues($pdf);

					// Write info on ticket bottom
					// Labels 
					$pdf->setTextColor(128, 128, 128);
					//	  writeHTMLCell($w, $h, $x, $y, $html='', $border=0, $ln=0, $fill=false, $reseth=true, $align='', $autopadding=true)
					$pdf->writeHTMLCell($ticketSeatingSection[$position]['w'], $ticketSeatingSection[$position]['h'], $ticketSeatingSection[$position]['x'], $ticketSeatingSection[$position]['y'], $section, 0, 1, 1, true, 'L', true);
					$pdf->writeHTMLCell($ticketSeatingRow[$position]['w'], $ticketSeatingRow[$position]['h'], $ticketSeatingRow[$position]['x'], $ticketSeatingRow[$position]['y'], $row, 0, 1, 1, true, 'L', true);
					$pdf->writeHTMLCell($ticketSeatingSeat[$position]['w'], $ticketSeatingSeat[$position]['h'], $ticketSeatingSeat[$position]['x'], $ticketSeatingSeat[$position]['y'], $seat, 0, 1, 1, true, 'L', true);

					// Values
					$pdf->setTextColor(0, 0, 0);
					$pdf->SetFont('times', '', 12);
					$pdf->writeHTMLCell($ticketSeatingSectionValue[$position]['w'], $ticketSeatingSectionValue[$position]['h'], $ticketSeatingSectionValue[$position]['x'], $ticketSeatingSectionValue[$position]['y'], $sectionNo, 0, 1, 1, true, 'L', true);
					$pdf->writeHTMLCell($ticketSeatingRowValue[$position]['w'], $ticketSeatingRowValue[$position]['h'], $ticketSeatingRowValue[$position]['x'], $ticketSeatingRowValue[$position]['y'], $rowNo, 0, 1, 1, true, 'L', true);
					$pdf->writeHTMLCell($ticketSeatingSeatValue[$position]['w'], $ticketSeatingSeatValue[$position]['h'], $ticketSeatingSeatValue[$position]['x'], $ticketSeatingSeatValue[$position]['y'], $seatNo, 0, 1, 1, true, 'L', true);

					// Set font
					$pdf->SetFont('times', '', 8);

					// Write info on stub
					if ($ticketWriteStubInfo == 1) {
						// Labels 
						$pdf->setTextColor(128, 128, 128);
						//	  writeHTMLCell($w, $h, $x, $y, $html='', $border=0, $ln=0, $fill=false, $reseth=true, $align='', $autopadding=true)
						$pdf->writeHTMLCell($stubSeatingSection[$position]['w'], $stubSeatingSection[$position]['h'], $stubSeatingSection[$position]['x'], $stubSeatingSection[$position]['y'], $section, 0, 1, 1, true, 'L', true);
						$pdf->writeHTMLCell($stubSeatingRow[$position]['w'], $stubSeatingRow[$position]['h'], $stubSeatingRow[$position]['x'], $stubSeatingRow[$position]['y'], $row, 0, 1, 1, true, 'L', true);
						$pdf->writeHTMLCell($stubSeatingSeat[$position]['w'], $stubSeatingSeat[$position]['h'], $stubSeatingSeat[$position]['x'], $stubSeatingSeat[$position]['y'], $seat, 0, 1, 1, true, 'L', true);

						// Values
						$pdf->setTextColor(0, 0, 0);
						$pdf->SetFont('times', '', 12);
						$pdf->writeHTMLCell($stubSeatingSectionValue[$position]['w'], $stubSeatingSectionValue[$position]['h'], $stubSeatingSectionValue[$position]['x'], $stubSeatingSectionValue[$position]['y'], $sectionNo, 0, 1, 1, true, 'L', true);
						$pdf->writeHTMLCell($stubSeatingRowValue[$position]['w'], $stubSeatingRowValue[$position]['h'], $stubSeatingRowValue[$position]['x'], $stubSeatingRowValue[$position]['y'], $rowNo, 0, 1, 1, true, 'L', true);
						$pdf->writeHTMLCell($stubSeatingSeatValue[$position]['w'], $stubSeatingSeatValue[$position]['h'], $stubSeatingSeatValue[$position]['x'], $stubSeatingSeatValue[$position]['y'], $seatNo, 0, 1, 1, true, 'L', true);
						// Date and time
						$pdf->writeHTMLCell($stubPerfDate[$position]['w'], $stubPerfDate[$position]['h'], $stubPerfDate[$position]['x'], $stubPerfDate[$position]['y'], $data['perfdate'].' '.$data['starttime'], 0, 1, 1, true, 'L', true);
					}
					
					// Reset all values
					resetAllValues($pdf);

					$position++;
				}
			}
		}
	}
}

// ---------------------------------------------------------

//Close and output PDF document
$pdf->Output('sessionCourseCSProgress.pdf', 'I');

//============================================================+

/**
 * Resets all values
 */
function resetAllValues($pdf) {
	// Reset all values
	$pdf->SetFont('times', '', 8);
	$pdf->setDrawColor(0, 0, 0);
	$pdf->setFillColor(255, 255, 255);
	$pdf->setTextColor(0, 0, 0);
	$pdf->SetLineStyle(array('width' => 0.2, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
}

/**
 * This function gets the seating exceptions for the performance
 */
function getPerformanceSeatingExceptions($mysqli, $showid, $performanceid) {
	$data = array();
	$data['data'] = array();
	if (empty($performanceid)) throw new Exception("Invalid performance.");
	$query = "SELECT caie.* 
						FROM cpa_arenas_ices_exceptions caie 
						JOIN cpa_shows_performances csp ON csp.arenaid = caie.arenaid AND (csp.iceid = 0 OR csp.iceid = caie.iceid)
						WHERE csp.id = $performanceid";
	$result = $mysqli->query($query);
	while ($row = $result->fetch_assoc()) {
		$data['data'][] = $row;
	}
	$query = "SELECT cspe.* 
						FROM cpa_shows_performances_exceptions cspe
						WHERE cspe.performanceid = $performanceid";
	$result = $mysqli->query($query);
	while ($row = $result->fetch_assoc()) {
		$data['data'][] = $row;
	}
	$data['success'] = true;
	return $data;
}

/**
 * This function gets the seating details for the current performance
 */
function getPerformanceSeating($mysqli, $showid, $performanceid) {
	try {
		if (empty($performanceid)) throw new Exception("Invalid performance.");
		$query = "SELECT cais.* 
							FROM cpa_arenas_ices_seats cais 
							JOIN cpa_shows_performances csp ON csp.arenaid = cais.arenaid AND (csp.iceid = 0 OR csp.iceid = cais.iceid)
							WHERE csp.id = $performanceid";
		$result = $mysqli->query($query);
		$data = array();
		$data['data'] = array();
		while ($row = $result->fetch_assoc()) {
      $row['exceptions'] = getPerformanceSeatingExceptions($mysqli, $showid, $performanceid)['data'];
			$data['data'][] = $row;
		}
		$data['success'] = true;
		return $data;
	} catch (Exception $e) {
		$data = array();
		$data['success'] = false;
		$data['message'] = $e->getMessage();
		return $data;
	}
};

/**
 * This function gets the ticket details for the current performance
 */
function getPerformanceTicket($mysqli, $showid, $performanceid) {
	try {
		if (empty($performanceid)) throw new Exception("Invalid performance.");
		$query = "SELECT cspt.* 
							FROM cpa_shows_performances_tickets cspt
							WHERE cspt.performanceid = $performanceid";
		$result = $mysqli->query($query);
		$data = array();
		$data['data'] = array();
		while ($row = $result->fetch_assoc()) {
			$row['showid'] = (int)$row['showid'];
			$row['performanceid'] = (int)$row['performanceid'];
			$row['showstub'] = (int)$row['showstub'];
			$row['showstubinfo'] = (int)$row['showstubinfo'];
			$row['showimage'] =	(int)$row['showimage'];
			$row['showstandardinfo'] = (int)$row['showstandardinfo'];
			$data['data'][] = $row;
		}
		$data['success'] = true;
		return $data;
	} catch (Exception $e) {
		$data = array();
		$data['success'] = false;
		$data['message'] = $e->getMessage();
		return $data;
	}
};

/**
 * This function gets the seating details for the current performance
 */
function getPerformance($mysqli, $showid, $performanceid) {
	try {
		if (empty($performanceid)) throw new Exception("Invalid performance.");
		$query = "SELECT csp.*, getTextLabel(ca.label, 'fr-ca') arenafrenchlabel, getTextLabel(ca.label, 'en-ca') arenaenglishlabel,
										 getTextLabel(cai.label, 'fr-ca') icefrenchlabel, getTextLabel(cai.label, 'en-ca') iceenglishlabel, 
										 getCodeDescription('days', weekday(csp.perfdate)+1, 'fr-ca') weekdayfrenchlabel,
										 getCodeDescription('days', weekday(csp.perfdate)+1, 'en-ca') weekdayenglishlabel,
										 day(csp.perfdate) dayno,
										 month(csp.perfdate) monthno,
										 year(csp.perfdate) yearno,
										 ca.address arenaaddress,
										 TIME_FORMAT(csp.starttime, '%H:%i') starttimeformattedfr,
										 TIME_FORMAT(csp.starttime, '%h:%i %p') starttimeformatteden
							FROM cpa_shows_performances csp
							JOIN cpa_arenas ca ON ca.id = csp.arenaid
							LEFT JOIN cpa_arenas_ices cai ON cai.id = csp.iceid
							WHERE csp.id = $performanceid";
		$result = $mysqli->query($query);
		$data = array();
		$data['data'] = array();
		while ($row = $result->fetch_assoc()) {
      $row['seating'] = getPerformanceSeating($mysqli, $showid, $performanceid)['data'];
      $row['ticket'] = getPerformanceTicket($mysqli, $showid, $performanceid)['data'];
			$data['data'][] = $row;
		}
		$data['success'] = true;
		return $data;
	} catch (Exception $e) {
		$data = array();
		$data['success'] = false;
		$data['message'] = $e->getMessage();
		return $data;
	}
};


/**
*	This function validates if the next seat is valid
* $pass		0 is normal, 1 is reserved
*/
function isSeatValid($section, $row, $seat, $exceptions, $pass) {
	$return = $pass == 0 ? true : false;
	for ($x = 0; $x < count($exceptions); $x++) {
		$expSection = $exceptions[$x]['section'];
		$expRow = $exceptions[$x]['row'];
		$expSeat = $exceptions[$x]['seat'];
		$expReason = $exceptions[$x]['reason']/1;
		if ($expSection == $section) {
			if ($expRow == null || $expRow == $row) {
				if ($expSeat == null || $expSeat == $seat) {
					if ($expReason == 2 && $pass == 1) { // if reason is reserved and pass is reserved, return true.
						return true;
					}
					return false;
				}
			}
		}
	}
	return $return;
}


/**
*	This function gets the next increment depending of the type (alpha or numeric)
*/
function getNextIncrement($incrementType, $first, $increment, $index) {
	$return = null;
	
	if ($incrementType == 1) { // Numeric
		$return = $first + ($increment * $index);
	} else {	// Alpha
		$idx = 0;
		foreach(range($first,'Z') as $letter) {
			if ($idx == $index) {
				$return = $letter;
				return $return;
			} else {
				$idx++;
			}
		}  
	}
	return $return;
}