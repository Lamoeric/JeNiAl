<?php
//============================================================+
// File name   : testSessionSkillTestSheet.php
// Begin       : 2017-02-24
// Last Update :
//
// Description : Test sheet for skill test
//
// Author: Eric Lamoureux
//
// $secondPage - true to print the second page, false to print the first page.
//============================================================+
function testSessionSkillTestSheet($pdf, $test, $judges, $language, $l, $secondPage) {
  if (!$secondPage) {
    // add a page
  	$pdf->AddPage();
  	// get the current page break margin
  	$bMargin = $pdf->getBreakMargin();
  	// get current auto-page-break mode
  	$auto_page_break = $pdf->getAutoPageBreak();
  	// disable auto-page-break
  	$pdf->SetAutoPageBreak(false, 0);
  	// set bacground image
  	$img_file = K_PATH_IMAGES.'reports/tests/'.$test['reportfilename'].'_page1_'.$language.'.jpg';
  	$pdf->Image($img_file, 0, 0, 210, 297, '', '', '', false, 300, '', false, false, 0);
  	// restore auto-page-break status
  	$pdf->SetAutoPageBreak($auto_page_break, $bMargin);
  	// set the starting point for the page content
  	$pdf->setPageMark();
  	// set color for background
  	$pdf->SetFillColor(255, 255, 255);
    $pdf->SetFont('times', '', 10);
    $border = 0;  // used for debuging purposes.

    // Let's look at the judges. If only one, print the name on the sheet. If more than one, don't write.
    if (count($judges) == 1) {
      $judge = $judges[0];
    } else {
      $judge = null;
    }

  	// Test date
  	$pdf->SetFont('times', '', 12);
  	$html = '<b>'.$test['testday'].'&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'.$test['testmonth'].'&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'.$test['testyear'].'</b>';
  	$pdf->writeHTMLCell(35, 0, 168, 28, utf8_decode($html), $border, 1, 1, true, 'L', true);
  	$pdf->SetFont('times', '', 10);

  	// Home club of the test
  	$html = '<b>'.$test['homeclublabel'].'</b>';
  	$pdf->writeHTMLCell(60, 0, 47, 35.5, utf8_decode($html), $border, 1, 1, true, 'L', true);

  	// Candidate
  	$html = '<b>'.$test['canfirstname'].' '.$test['canlastname'].'</b>';
  	$pdf->writeHTMLCell(80, 0, 30, 42, utf8_decode($html), $border, 1, 1, true, 'L', true);

  	// Home club of the candidate
  	$html = '<b>'.$test['candidatehomeclublabel'].'</b>';
  	$pdf->writeHTMLCell(60, 0, 63, 49, utf8_decode($html), $border, 1, 1, true, 'L', true);

  	// Coach
  	$html = '<b>'.$test['coafirstname'].' '.$test['coalastname'].'</b>';
  	$pdf->writeHTMLCell(60, 0, 145, 42, utf8_decode($html), $border, 1, 1, true, 'L', true);

  	// Evaluator
    if ($judge) {
      $html = '<b>'.$judge['firstname'].' '.$judge['lastname'].'</b>';
      $pdf->writeHTMLCell(60, 0, 145, 49, utf8_decode($html), $border, 1, 1, true, 'L', true);
    }

  	// Bottom part of the sheet
  	// Candidate
  	$html = '<b>'.$test['canfirstname'].' '.$test['canlastname'].'</b>';
  	$pdf->writeHTMLCell(60, 0, 28, 91, utf8_decode($html), $border, 1, 1, true, 'L', true);

  	// Evaluator
    if ($judge) {
      $html = '<b>'.$judge['firstname'].' '.$judge['lastname'].'</b>';
      $pdf->writeHTMLCell(50, 0, 116, 91, utf8_decode($html), $border, 1, 1, true, 'L', true);
    }

  	$pdf->SetFont('times', '', 12);
  	$html = '<b>'.$test['testday'].'&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'.$test['testmonth'].'&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'.$test['testyear'].'</b>';
  	$pdf->writeHTMLCell(35, 0, 168, 90, utf8_decode($html), $border, 1, 1, true, 'L', true);
  } else {
    // This is the second page of the test, with the explanation.
  	// add a page
  	$pdf->AddPage();
  	// get the current page break margin
  	$bMargin = $pdf->getBreakMargin();
  	// get current auto-page-break mode
  	$auto_page_break = $pdf->getAutoPageBreak();
  	// disable auto-page-break
  	$pdf->SetAutoPageBreak(false, 0);
  	// set bacground image
  	$img_file = K_PATH_IMAGES.'reports/tests/'.$test['reportfilename'].'_page2_'.$language.'.jpg';
  	$pdf->Image($img_file, 0, 0, 210, 297, '', '', '', false, 300, '', false, false, 0);
  	// restore auto-page-break status
  	$pdf->SetAutoPageBreak($auto_page_break, $bMargin);
  	// set the starting point for the page content
  	$pdf->setPageMark();
  }
}
