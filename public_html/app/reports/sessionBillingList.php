<?php
//============================================================+
// File name   : sessionBillingList.php
// Begin       : 2016-07-07
// Last Update :
//
// Description : session billing list - shows every bill, with details
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

$language = 'fr-ca';
$sessionid = null;
if(isset($_GET['language']) && !empty(isset($_GET['language']))) {
	$language = $_GET['language'];
}
if(isset($_GET['sessionid']) && !empty(isset($_GET['sessionid']))) {
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

// ---------------------------------------------------------

// set default font subsetting mode
$pdf->setFontSubsetting(true);

// Set font
// dejavusans is a UTF-8 Unicode font, if you only need to
// print standard ASCII chars, you can use core fonts like
// helvetica or times to reduce file size.
$pdf->SetFont('times', '', 10, '', true);

$nboflinefirstpage 	= 45;
$nboflinenextpage  	= 45;
$nboflineonpage  		= 0;
$data = getSessionLabel($mysqli, $sessionid, $language);
$sessionlabel = $data['data'][0]['sessionlabel'];

$data = getSessionBillingList($mysqli, $sessionid, $language);
$billingList = $data['data'];

$data = getSessionBillingTotals($mysqli, $sessionid, $language);
$billingSums = $data['data'][0];

// Page header
$pageheader = '<p align="center" style="font-size:20px"><b>'.$l['w_title'].' - '.$sessionlabel.'</h1></b>';

$tableheader = '<table border="1" width="110%"><tr><td width="5%"><b>#</b></td><td width="23%"><b>'.$l['w_name'].'</b></td><td width="15%" align="center"><b>'.$l['w_billingdate'].'</b></td><td width="12%" align="center"><b>'.$l['w_amountdue'].'</b></td><td width="12%" align="center"><b>'.$l['w_amountpaid'].'</b></td><td width="16%" align="center"><b>'.$l['w_paymethod'].'</b></td><td width="10%" align="center"><b>'.$l['w_balance'].'</b></td></tr>';
$nboflineonpage  = 0;
$pageno = 1;
$html = $pageheader.$tableheader;
for ($x = 0; $x < count($billingList); $x++) {
	$bill = $billingList[$x];
	$realamount = calculateTransactionsAmount($bill['transactions']);
	if ($nboflineonpage != 0 && (($pageno == 1 && fmod($nboflineonpage, $nboflinefirstpage) == 0) || ($pageno > 1 && fmod($nboflineonpage, $nboflinenextpage) == 0))) {
		$html = $html .'</table>';
		$pdf->AddPage('P');
		$pdf->writeHTMLCell(0, 0, '', '', utf8_decode($html), 0, 1, 0, true, '', true);
		$pageno++;
		$nboflineonpage  = 0;
		$html = $pageheader.$tableheader;
	}
	$html .= '<tr><td width="5%">'.$bill['id'].'</td><td width="23%">'.$bill['billingname'].'</td><td width="15%" align="right">'.$bill['billingdate'].'</td><td width="12%" align="right">'.$bill['totalamount'].'</td>';

	if ($bill['paidamount'] != $realamount) {
		$html .= '<td width="12%" align="right" style="color:red">'.$bill['paidamount'].' ('.$realamount.')'.'</td>';
	} else {
		$html .= '<td width="12%" align="right">'.$bill['paidamount'].'</td>';
	}
	$paymethod = '';
	if (count($bill['transactions']) == 0) {
		$paymethod = '';
	} else if (count($bill['transactions']) > 1) {
		$paymethod = $l['w_multpaymethod'];
	} else if (count($bill['transactions']) == 1) {
		$paymethod = $bill['transactions'][0]['paymentmethodlabel'];
	}
	$html .= '<td width="16%" align="center">'.$paymethod.'</td>';
	if ($bill['delta'] > 0) {
		$html .= '<td width="10%" align="right" style="color:red"><b>'.$bill['delta'].'</b></td>';
	} else if ($bill['delta'] < 0) {
		$html .= '<td width="10%" align="right" style="color:green"><b>'.$bill['delta'].'</b></td>';
	} else {
		$html .= '<td width="10%" align="right">'.$bill['delta'].'</td>';
	}
	$html .= '</tr>';
	$nboflineonpage++;
}
$billingSums['sumtotalamount'] = number_format($billingSums['sumtotalamount'], 2, ".", " ");
$billingSums['sumpaidamount'] = number_format($billingSums['sumpaidamount'], 2, ".", " ");
$billingSums['sumbalance'] = number_format($billingSums['sumbalance'], 2, ".", " ");

$html .= '<tr><td colspan="3" align="right"><b>'.$l['w_total'].'</b></td><td width="12%" align="right"><b>'.$billingSums['sumtotalamount'].'</b></td><td width="12%" align="right"><b>'.$billingSums['sumpaidamount'].'</b></td><td colspan="2" align="right"><b>'.$billingSums['sumbalance'].'</b></td></tr>';
$html = $html .'</table>';
$pdf->AddPage('P');
$pdf->writeHTMLCell(0, 0, '', '', utf8_decode($html), 0, 1, 0, true, '', true);

// ---------------------------------------------------------
// Close and output PDF document
// This method has several options, check the source code documentation for more information.
$pdf->Output('sessionBillingList.pdf', 'I');

//============================================================+
// END OF FILE
//============================================================+
/**
 * This function calculates the total amount of all transactions for a bill
 */
function calculateTransactionsAmount($transactions) {
	$total = 0.00;
	for ($x = 0; $x < count($transactions); $x++) {
		$transaction = $transactions[$x];
		if ($transaction['iscanceled'] == 0) {
			if ($transaction['transactiontype'] == 'PAYMENT') {
				$total += $transaction['transactionamount']/-1;
			} else {
				$total += $transaction['transactionamount']/1;
			}
		}
	}
	return $total;
}

/**
 * This function gets the transactions for one bill from database
 */
function getBillTransactions($mysqli, $id, $language) {
	$notover = true;
	$data = array();
	$data['data'] = array();
	$data['billlist'] = "'$id'";
	// Loops over all the related bills and creates a list
	while ($notover) {
		$query = "SELECT id FROM cpa_bills WHERE relatednewbillid = '$id'";
		$result = $mysqli->query($query);
		$row = $result->fetch_assoc();
		if ($row) {
			$id = $row['id'];
			$data['billlist'] .= ",'$id'";
		} else {
			$notover = false;
		}
	}
	$billlist = $data['billlist'];
	$query = "SELECT id, billid, transactiontype, transactionamount, transactiondate, paymentmethod, getCodeDescription('transactiontypes', transactiontype, '$language') transactiontypelabel, getCodeDescription('paymentmethods', paymentmethod, '$language') paymentmethodlabel, iscanceled, cancelreason, canceledby, canceleddate
						FROM cpa_bills_transactions
						WHERE billid in ($billlist)";
	$result = $mysqli->query($query);
	while ($row = $result->fetch_assoc()) {
		$row['iscanceled'] = (int)$row['iscanceled'];
		$data['data'][] = $row;
	}
	$data['success'] = true;
	return $data;
};

/**
 * This function gets the all the bills for the session
 */
function getSessionBillingList($mysqli, $sessionid, $language) {
	try{
		if(empty($sessionid)) throw new Exception("Invalid session id.");
		$query = "SELECT DISTINCT cb.id, cb.billingname, cb.billingdate, cb.paymentduedate, cb.totalamount, cb.paidamount, (cb.totalamount + cb.paidamount) delta
							FROM cpa_bills cb
							JOIN cpa_bills_registrations cbr ON cbr.billid = cb.id
							JOIN cpa_registrations csr ON csr.id = cbr.registrationid
							where relatednewbillid is null
							AND csr.sessionid = $sessionid
							order by cb.billingname, billingdate, cb.id";
		$result = $mysqli->query($query);
		$data = array();
		$data['data'] = array();
		while ($row = $result->fetch_assoc()) {
			$row['transactions'] = getBillTransactions($mysqli, $row['id'], $language)['data'];
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
 * This function gets the total of amount du, amount paid and total balance for the session
 */
function getSessionBillingTotals($mysqli, $sessionid, $language) {
	try{
		if(empty($sessionid)) throw new Exception("Invalid session id.");
		$query = "select sum(totalamount) sumtotalamount, sum(paidamount) sumpaidamount, sum(delta) sumbalance from
								(SELECT DISTINCT cb.id, cb.billingname, cb.billingdate, cb.paymentduedate, cb.totalamount, cb.paidamount, (cb.totalamount + cb.paidamount) delta
									FROM cpa_bills cb
									JOIN cpa_bills_registrations cbr ON cbr.billid = cb.id
									JOIN cpa_registrations csr ON csr.id = cbr.registrationid
									where relatednewbillid is null
									AND csr.sessionid = $sessionid
									ORDER BY cb.billingname, billingdate, cb.id) A";
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
