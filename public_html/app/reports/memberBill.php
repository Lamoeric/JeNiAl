<?php
//============================================================+
// File name   : memberBill.php
// Begin       : 2016-08-11
// Last Update :
//
// Description : Bill for a member
//
// Author: Eric Lamoureux
//
//============================================================+

// Include the main TCPDF library (search for installation path).
require_once('../../include/tcpdf_include.php');
require_once('../../../private/'. $_SERVER['HTTP_HOST'].'/include/config.php');
require_once('../../include/nocache.php');
require_once('../core/directives/billing/bills.php');
require_once('customheader.php');
require_once('mypdf_footer.php');
require_once('createFileName.php');

// Input parameters
$billid = null;
$language = 'fr-ca';
$memberid = null;
$output = 'I';
if (isset($_GET['language']) && !empty(isset($_GET['language']))) {
	$language = $_GET['language'];
}
if (isset($_POST['language']) && !empty(isset($_POST['language']))) {
	$language = $_POST['language'];
}
if (isset($_GET['memberid']) && !empty(isset($_GET['memberid']))) {
	$memberid = $_GET['memberid'];
}
if (isset($_POST['memberid']) && !empty(isset($_POST['memberid']))) {
	$memberid = $_POST['memberid'];
}
if (isset($_GET['billid']) && !empty(isset($_GET['billid']))) {
	$billid = $_GET['billid'];
}
if (isset($_POST['billid']) && !empty(isset($_POST['billid']))) {
	$billid = $_POST['billid'];
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
$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

// set image scale factor
$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

// set some language-dependent strings (optional)
if (@file_exists(dirname(__FILE__).'/lang/eng.php')) {
	require_once(dirname(__FILE__).'/lang/eng.php');
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

if ($memberid != null) {
	$data = getMemberBillInt($mysqli, $memberid, $language);
} else {
	$data = getBillInt($mysqli, $billid, $language);
}
$bill = $data['data'][0];
$index = 0;
$pageno = 1;
$primarykey = '';

if ($language == 'en-ca') {
	$title = "Invoice";
	$separator = " to ";
	$titleBillTo = "Bill to:";
	$titleDate = "Date:";
	$titleInvoiceNo = "Invoice No:";
	$titleOldInvoiceNo = "Old Invoice No:";
	$titleHasPaymentAgreement = "Has Payment Agreement:";
	$titlePaymentAgreementNote = "Payment Agreement Note:";
	$titleDetails = "Details";
	$titleAmount = "Amount";
	$titleSubTotal = "Sub Total";
	$titleTotal = "Total";
	$titleTransactions = "Transactions";
	$transactioncanceled = "Transaction Canceled";
	$filename = 'Invoice_'.$bill['billingname'].'_'.$bill['billingdate'].'.pdf';
} else {
	$title = "Facture";
	$separator = " &agrave; ";
	$titleBillTo = "Factur&eacute; &agrave; :";
	$titleDate = "Date :";
	$titleInvoiceNo = "No facture :";
	$titleOldInvoiceNo = "Ancien no facture :";
	$titleHasPaymentAgreement = "Entente de paiement:";
	$titlePaymentAgreementNote = "Note entente de paiement:";
	$titleDetails = "D&eacute;tails";
	$titleAmount = "Montant";
	$titleSubTotal = "Sous Total";
	$titleTotal = "Total";
	$transactioncanceled = "Transaction annul&eacute;e";
	$titleTransactions = "Transactions";
	$filename = 'Facture_'.$bill['billingname'].'_'.$bill['billingdate'].'.pdf';
}

// Page header
$html = '<p align="center" style="font-size:20px"><B>'.$title.'</B></p>
					<table width="100%">
				  	<tr>
				  		<td width="35%"><b>'.$titleBillTo.'</b></td>
				  		<td width="50%">'.$bill['billingname'].'</td>
				  	</tr>
				  	<tr>
				  		<td width="35%">'.$titleDate.'</td>
				  		<td width="50%">'.$bill['billingdate'].'</td>
						</tr>
						<tr>
				  		<td width="35%">'.$titleInvoiceNo.'</td>
				  		<td width="50%">'.$bill['id'].'</td>
						</tr>
						<tr>
				  		<td width="35%">'.$titleOldInvoiceNo.'</td>
				  		<td width="50%">'.$bill['relatedoldbillid'].'</td>
						</tr>
						<tr>
				  		<td width="35%">'.$titleHasPaymentAgreement.'</td>
				  		<td width="50%">'.$bill['haspaymentagreementstr'].'</td>
						</tr>
						<tr>
				  		<td width="35%">'.$titlePaymentAgreementNote.'</td>
				  		<td width="65%">'.$bill['paymentagreementnote'].'</td>
						</tr>
					</table>
					<br>
					<br>
					<table width="100%">
						<tr>
							<th  style="border-bottom:1pt solid black;"><B>'.$titleDetails.'</B></th>
							<th  style="border-bottom:1pt solid black;" align="right"><B>'.$titleAmount.'</B></th>
						</tr>';


$registrations = $bill['registrations'];
for ($x = 0; $x < count($registrations); $x++) {
	$registration = $registrations[$x];
//	$registrationTotal = $registration['subtotal'];
	$html = $html.
					'<tr>
						<td colspan="2">
							<table width="100%">
						  	<tr>
									<td><h3>'.$registration['member']['firstname'].' '.$registration['member']['lastname'].' ('.$registration['member']['skatecanadano'].')'.'</h3></td>
									<td></td>
						  	</tr>';
	$courses = isset($registration['courses']) ? $registration['courses'] : null;
	for ($y = 0; $courses && $y < count($courses); $y++) {
		$course = $courses[$y];
		$html = $html.
						  	'<tr ng-repeat="course in registration.courses">
						  		<td width="90%">'.$course['courselabel'].' '.$course['courselevellabel'].' ('.$course['name'].')</td>
						  		<td width="10%" align="right">$'.$course['amount'].'</td>
						  	</tr>';
	}
	$shownumbers = isset($registration['shownumbers']) ? $registration['shownumbers'] : null;
	for ($y = 0; $shownumbers && $y < count($shownumbers); $y++) {
		$shownumber = $shownumbers[$y];
		$html = $html.
						  	'<tr ng-repeat="shownumber in registration.shownumbers">
						  		<td width="90%">'.$shownumber['numberlabel'].' ('.$shownumber['name'].')</td>
						  		<td width="10%" align="right">$'.$shownumber['amount'].'</td>
						  	</tr>';
	}
	$tests = isset($registration['tests']) ? $registration['tests'] : null;
	for ($y = 0; $tests && $y < count($tests); $y++) {
		$test = $tests[$y];
		$html = $html.
						  	'<tr>
						  		<td width="90%">'.$test['testlabel'].(!empty($test['comments']) ? '<br><small>'.$test['comments'].'</small>' : '') .'</td>
						  		<td width="10%" align="right">$'.$test['amount'].'</td>
						  	</tr>';
	}
	$charges = $registration['charges'];
	for ($y = 0; $y < count($charges); $y++) {
		$charge = $charges[$y];
		$html = $html.
						  	'<tr>
						  		<td width="90%">'.$charge['chargelabel'].' ('.$charge['code'].') <i>'.($charge['nonrefundable'] == '1' ? $charge['nonrefundablelabel'] : '') . '</i>'.(!empty($charge['comments']) ? '<br><small>'.$charge['comments'].'</small>' : '') .'</td>
						  		<td width="10%" align="right">$'.$charge['amount'].'</td>
						  	</tr>';
	}
	$discounts = $registration['discounts'];
	for ($y = 0; $y < count($discounts); $y++) {
		$discount = $discounts[$y];
		$html = $html.
						  	'<tr>
						  		<td width="90%">'.$discount['chargelabel'].' ('.$discount['code'].')'.(!empty($discount['comments']) ? '<br><small>'.$discount['comments'].'</small>' : '').'</td>
						  		<td width="10%" align="right">-$'.$discount['amount'].'</td>
						  	</tr>';
	}
	$html = $html.
					  	'<tr>
					  		<td  style="border-bottom:1pt solid black;" width="90%" align="right"><b>'.$titleSubTotal.'</b></td>
					  		<td  style="border-bottom:1pt solid black;" width="10%" align="right"><b>$'.$registration['subtotal'].'</b></td>
					  	</tr>
				  	</table>
				  </td>
				</tr>';
}
$html = $html.
				'</table><br><br>
				<table width="100%">
			  	<tr>
			  		<td width="90%" align="right"><h3>'.$titleTotal.'</h3></td>
			  		<td width="10%" align="right"><h3>$'.$bill['totalamount'].'</h3></td>
			  	</tr>
				</table>
';
$transactions = $bill['transactions'];
$paymentsubtotal = "0.00";
if (count($transactions) > 0) {
	$html = $html.
				'<br><br>
				<table width="100%"><tr>
						<th style="border-bottom:1pt solid black;"><B>'.$titleTransactions.'</B></th>
						<th  style="border-bottom:1pt solid black;" align="right"><B>'.$titleAmount.'</B></th>
					</tr>
					<tr>
						<td colspan="2">
							<table width="100%">';
	for ($y = 0; $y < count($transactions); $y++) {
		$transaction = $transactions[$y];
		$paymentamount = $transaction['transactionamount'];
		if ($transaction['transactiontype'] == 'PAYMENT' && $transaction['iscanceled'] == 0) {
			$paymentsubtotal += $transaction['transactionamount'];
		} else if ($transaction['transactiontype'] != 'PAYMENT' && $transaction['iscanceled'] == 0) {
			$paymentsubtotal -= $transaction['transactionamount'];
			$paymentamount = $paymentamount * -1;
		}
		if ($paymentamount < 0) {
			$paymentamountstr = '-$'.number_format(($paymentamount*-1), 2);
		} else {
			$paymentamountstr = '$'.number_format($paymentamount, 2);
		}
		if ($transaction['iscanceled'] == 0 || ($transaction['iscanceled'] == 1 && $transaction['cancelreason'] == 1)) {
			$html = $html.
								  	'<tr>
								  		<td width="90%" align="right">'.$transaction['transactiontypelabel'].' '.$transaction['transactiondate'].' ('.$transaction['paymentmethodlabel'].')';
								  		
			if ($transaction['iscanceled'] == 1) {
				$html = $html.'<br><small>'.$transactioncanceled.'</small>';
			}
			$html = $html.'</td>';
			
			if ($transaction['iscanceled'] == 1) {
				$html = $html.
									  		'<td width="10%" align="right"><del>'.$paymentamountstr.'</del></td>';
			} else {
				$html = $html.
									  		'<td width="10%" align="right">'.$paymentamountstr.'</td>';
			}
			$html = $html.'</tr>';
		}
	}
	if ($paymentsubtotal < 0) {
		$paymentsubtotalstr = '-$'.number_format(($paymentsubtotal*-1), 2);
	} else {
		$paymentsubtotalstr = '$'.number_format($paymentsubtotal, 2);
	}
	$html = $html.
						  	'<tr>
						  		<td  style="border-bottom:1pt solid black;" width="90%" align="right"><b>'.$titleSubTotal.'</b></td>
						  		<td  style="border-bottom:1pt solid black;" width="10%" align="right"><b>'.$paymentsubtotalstr.'</b></td>
						  	</tr>
						  </table>
						</td>
					</tr>
				</table>';
	$realtotalamount = $bill['totalamount'] - $paymentsubtotal;
	if ($realtotalamount < 0) {
		$realtotalamount = '-$'.number_format(($realtotalamount*-1), 2);
	} else {
		$realtotalamount = '$'.number_format($realtotalamount, 2);
	}
	$html = $html.
		'<br><br>
		<table width="100%">
	  	<tr>
	  		<td width="90%" align="right"><h3>'.$titleTotal.'</h3></td>
	  		<td width="10%" align="right"><h3>'.$realtotalamount.'</h3></td>
	  	</tr>
		</table>';
}

$pdf->AddPage();
$pageno++;
$pdf->writeHTMLCell(0, 0, '', '', utf8_decode($html), 0, 1, 0, true, '', true);
// ---------------------------------------------------------

// Close and output PDF document
// This method has several options, check the source code documentation for more information.
if ($output == 'F') {
	$filename = createFileName();
	$pdf->Output($filename, 'F');
	echo $filename;
} else if ($output == 'I') {
	ob_end_clean();
	$pdf->Output($filename, 'I');
}

//============================================================+
// END OF FILE
//============================================================+
