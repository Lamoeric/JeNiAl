<?php
//============================================================+
// File name   : testSessionDanceTestSheet.php
// Begin       : 2017-02-24
// Last Update :
//
// Description : Test sheet for dance test
//
// Author: Eric Lamoureux
//
//============================================================+
function testSessionDanceTestSheet($pdf, $test, $judges, $language, $l) {
  // add a page
  $pdf->AddPage();
  // get the current page break margin
  $bMargin = $pdf->getBreakMargin();
  // get current auto-page-break mode
  $auto_page_break = $pdf->getAutoPageBreak();
  // disable auto-page-break
  $pdf->SetAutoPageBreak(false, 0);
  // set bacground image
  $img_file = K_PATH_IMAGES.'reports/tests/'.$test['reportfilename'].'_'.$language.'.jpg';
  $pdf->Image($img_file, 0, 0, 210, 297, '', '', '', false, 300, '', false, false, 0);
  // restore auto-page-break status
  $pdf->SetAutoPageBreak($auto_page_break, $bMargin);
  // set the starting point for the page content
  $pdf->setPageMark();
  // set color for background
  $pdf->SetFillColor(255, 255, 255);
  // set font
  $pdf->SetFont('times', '', 10);
  $border = 0;  // used for debuging purposes.

  // Let's look at the judges. If only one, print the name on the sheet. If more than one, don't write.
  if (count($judges) == 1) {
    $judge = $judges[0];
  } else {
    $judge = null;
  }

  // let's do a header with the test name
  $html = '<table><tr><td width="33%"></td><td width="33%" align="center"><b>'.$test['testlabel'].'</b></td><td align="right"></td></tr></table>';
  $pdf->writeHTMLCell(0, 0, PDF_MARGIN_RIGHT, 5, utf8_decode($html), $border, 1, 1, true, 'L', true);
  // Test date
  $pdf->SetFont('times', '', 12);
  $html = '<b>'.$test['testday'].'&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'.$test['testmonth'].'&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'.$test['testyear'].'</b>';
  $pdf->writeHTMLCell(35, 0, 168, 28, utf8_decode($html), $border, 1, 1, true, 'L', true);

  // This section changes for the preliminary dances
  if ($test['level'] == 0) {
    // Preliminary dances
    $pdf->SetFont('times', '', 10);
    // Home club of the test
    $html = '<b>'.$test['homeclublabel'].'</b>';
    $pdf->writeHTMLCell(60, 0, 47, 36, utf8_decode($html), $border, 1, 1, true, 'L', true);

    // Candidate
    $html = '<b>'.$test['canfirstname'].' '.$test['canlastname'].' ('.$test['cangender'].')</b>';
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

    // Partner
    $html = '<b>'.$test['parfirstname'].' '.$test['parlastname'].'</b>';
    $pdf->writeHTMLCell(60, 0, 145, 55.5, utf8_decode($html), $border, 1, 1, true, 'L', true);
  } else {
    //Everything except Preliminary dances
    $pdf->SetFont('times', '', 10);
    // Home club of the test
    $html = '<b>'.$test['homeclublabel'].'</b>';
    $pdf->writeHTMLCell(60, 0, 47, 34, utf8_decode($html), $border, 1, 1, true, 'L', true);

    // Candidate
    $html = '<b>'.$test['canfirstname'].' '.$test['canlastname'].' ('.$test['cangender'].')</b>';
    $pdf->writeHTMLCell(80, 0, 30, 40, utf8_decode($html), $border, 1, 1, true, 'L', true);

    // Hide the gender and the steps
    $html = '';
    $pdf->writeHTMLCell(25, 15, 94, 40, utf8_decode($html), $border, 1, 1, true, 'L', true);

    // Steps executed
    $steps = '';
    if ($test['cangender'] == 'F' and $test['partnersteps'] == 1) {
      if ($language == 'fr-ca') {
        $steps = 'HOMME';
      } else {
        $steps = 'MALE';
      }
    } else if ($test['cangender'] == 'M' and $test['partnersteps'] == 1) {
      if ($language == 'fr-ca') {
        $steps = 'FEMME';
      } else {
        $steps = 'FEMALE';
      }
    }
    $html = '<b>'.$steps.'</b>';
    $pdf->writeHTMLCell(60, 0, 55, 45.5, utf8_decode($html), $border, 1, 1, true, 'L', true);

    // Home club of the candidate
    $html = '<b>'.$test['candidatehomeclublabel'].'</b>';
    $pdf->writeHTMLCell(60, 0, 63, 51.5, utf8_decode($html), $border, 1, 1, true, 'L', true);

    // Coach
    $html = '<b>'.$test['coafirstname'].' '.$test['coalastname'].'</b>';
    $pdf->writeHTMLCell(60, 0, 145, 40, utf8_decode($html), $border, 1, 1, true, 'L', true);

    // Evaluator
    if ($judge) {
      $html = '<b>'.$judge['firstname'].' '.$judge['lastname'].'</b>';
      $pdf->writeHTMLCell(60, 0, 145, 45.5, utf8_decode($html), $border, 1, 1, true, 'L', true);
    }

    // Partner
    $html = '<b>'.$test['parfirstname'].' '.$test['parlastname'].'</b>';
    $pdf->writeHTMLCell(60, 0, 145, 51.5, utf8_decode($html), $border, 1, 1, true, 'L', true);
  }
  // End of different section for preliminary

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
  $pdf->writeHTMLCell(35, 0, 168, 86, utf8_decode($html), $border, 1, 1, true, 'L', true);
}
