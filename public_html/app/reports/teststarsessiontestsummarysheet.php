<?php
//============================================================+
// File name   : testStarSessionTestSummarySheet.php
// Begin       : 2017-10-20
// Last Update :
//
// Description : Summary Test sheet for new STAR 1 - 5 test session
//
// Author: Eric Lamoureux
//
//============================================================+
function testStarSessionTestSummarySheet($pdf, $tests, $testDirectorInfo, $language, $l) {
  $border = 0;	// Set to 1 to display borders of all cells
  $lineYCoord = [52.2,72.7,93.5,114,134.5,155,176,196.5,217,237.7];
  $lineNo = -1;
  $totalPageFees = 0.0;
  $pagesFees = array();

  for ($x = 0; $x < count($tests); $x++) {
    $test = $tests[$x];
    $lineNo++;
    if ($lineNo == 0 || $lineNo == 10) {
      // Reset everything and change page
      $lineNo = 0;
      $totalPageFees = 0.0;
      // add a page
      $pdf->AddPage();
      // get the current page break margin
      $bMargin = $pdf->getBreakMargin();
      // get current auto-page-break mode
      $auto_page_break = $pdf->getAutoPageBreak();
      // disable auto-page-break
      $pdf->SetAutoPageBreak(false, 0);
      // set bacground image
      $img_file = K_PATH_IMAGES.'reports/tests/summary/test_summary_3_'.$language.'.jpg';
      $pdf->Image($img_file, 0, 0, 210, 297, '', '', '', false, 300, '', false, false, 0);
      // restore auto-page-break status
      $pdf->SetAutoPageBreak($auto_page_break, $bMargin);
      // set the starting point for the page content
      $pdf->setPageMark();
      // set color for background
      $pdf->SetFillColor(255, 255, 255);
      // set font
      $pdf->SetFont('times', '', 10);

      // Organization name
    	$html = '<b>'.$test['homeclublabel'].'</b>';
    	// $html = $test['homeclublabel'];
    	$pdf->writeHTMLCell(45, 0, 60, 21, utf8_decode($html), $border, 1, 1, true, 'L', true);

    	// Organization number
    	$html = '<b>'.$test['orgno'].'</b>';
    	$pdf->writeHTMLCell(30, 0, 60, 25.5, utf8_decode($html), $border, 1, 1, true, 'L', true);

    	$html = '<b>'.$testDirectorInfo['skatecanadano'].'</b>';
    	$pdf->writeHTMLCell(35, 0, 158, 26.5, utf8_decode($html), $border, 1, 1, true, 'L', true);

    	$html = '<b>'.$testDirectorInfo['firstname'].' '.$testDirectorInfo['lastname'].'</b>';
    	$pdf->writeHTMLCell(35, 0, 158, 31, utf8_decode($html), $border, 1, 1, true, 'L', true);

    	$html = '<b>'.$testDirectorInfo['homephone'].'</b>';
    	$pdf->writeHTMLCell(35, 0, 158, 35.5, utf8_decode($html), $border, 1, 1, true, 'L', true);

    	$html = '<b>'.$testDirectorInfo['email'].'</b>';
    	$pdf->writeHTMLCell(55, 0, 140, 40, utf8_decode($html), $border, 1, 1, true, 'L', true);
    }
    $totalPageFees += (float) $test['fees'];
    // First line
    // # skate Canada
    $html = '<b>'.$test['skatecanadano'].'</b>';
    $pdf->writeHTMLCell(40, 0, 18.5, $lineYCoord[$lineNo], utf8_decode($html), $border, 1, 1, true, 'L', true);

    // Home Club
    $html = '<b>'.$test['candidatehomeclublabel'].'</b>';
    $pdf->writeHTMLCell(30, 0, 63, $lineYCoord[$lineNo], utf8_decode($html), $border, 1, 1, true, 'L', true);

    // coach skatecanadano
    $html = '<b>'.$test['coaskatecanadano'].'</b>';
    $pdf->writeHTMLCell(20, 0, 103, $lineYCoord[$lineNo], utf8_decode($html), $border, 1, 1, true, 'L', true);

    // Test code
    $html = '<b>'.$test['summarycode'].'</b>';
    $pdf->writeHTMLCell(25, 0, 130, $lineYCoord[$lineNo], utf8_decode($html), $border, 1, 1, true, 'L', true);

    // Test date
    $html = '<b>'.$test['testday'].'/'.$test['testmonth'].'/'.$test['testyear'].'</b>';
    $pdf->writeHTMLCell(20, 0, 170, $lineYCoord[$lineNo], utf8_decode($html), $border, 1, 1, true, 'L', true);

    // Second line
    // Candidate
    $html = '<b>'.$test['canfirstname'].' '.$test['canlastname'].'</b>';
    $pdf->writeHTMLCell(50, 0, 18.5, $lineYCoord[$lineNo] + 8, utf8_decode($html), $border, 1, 1, true, 'L', true);

    // Organization number
    $html = '<b>'.$test['candidateorgno'].'</b>';
    $pdf->writeHTMLCell(25, 0, 70, $lineYCoord[$lineNo] + 8, utf8_decode($html), $border, 1, 1, true, 'L', true);

    // Coach name
    $html = '<b>'.$test['coafirstname'].' '.$test['coalastname'].'</b>';
    $pdf->writeHTMLCell(31, 0, 96, $lineYCoord[$lineNo] + 8, utf8_decode($html), $border, 1, 1, true, 'L', true);

    // Result
    $html = '<b>X</b>';
    if ($language == 'fr-ca') {
      if ($test['result'] == '1') {
        $pdf->writeHTMLCell(5, 0, 145, $lineYCoord[$lineNo] + 5.2, utf8_decode($html), $border, 1, 1, true, 'L', true);
      } else if ($test['result'] == '5') {
        $pdf->writeHTMLCell(5, 0, 153, $lineYCoord[$lineNo] + 8.8, utf8_decode($html), $border, 1, 1, true, 'L', true);
      } else {
        $pdf->writeHTMLCell(5, 0, 160, $lineYCoord[$lineNo] + 5.2, utf8_decode($html), $border, 1, 1, true, 'L', true);
      }
    } else {
      if ($test['result'] == '1') {
        $pdf->writeHTMLCell(5, 0, 144, $lineYCoord[$lineNo] + 5.2, utf8_decode($html), $border, 1, 1, true, 'L', true);
      } else if ($test['result'] == '5') {
        $pdf->writeHTMLCell(5, 0, 153, $lineYCoord[$lineNo] + 8.8, utf8_decode($html), $border, 1, 1, true, 'L', true);
      } else {
        $pdf->writeHTMLCell(5, 0, 156.5, $lineYCoord[$lineNo] + 5.2, utf8_decode($html), $border, 1, 1, true, 'L', true);
      }
    }

    // Fees
    $html = '<b>'.$test['fees'].'</b>';
    $pdf->writeHTMLCell(20, 0, 170, $lineYCoord[$lineNo] + 8, utf8_decode($html), $border, 1, 1, true, 'L', true);

    if ($lineNo == 9 || $x == count($tests)-1) {
      array_push($pagesFees, $totalPageFees);
      //Page is done, write total
      $html = '<b>'.'$'.number_format($totalPageFees, 2).'</b>';
      if ($language == 'fr-ca') {
        $pdf->writeHTMLCell(25, 0, 174, 256.7, utf8_decode($html), $border, 1, 1, true, 'L', true);
      } else {
        $pdf->writeHTMLCell(25, 0, 169.7, 256, utf8_decode($html), $border, 1, 1, true, 'L', true);
      }
      $pdf->SetFont('times', '', 10);
    }
  }
}
