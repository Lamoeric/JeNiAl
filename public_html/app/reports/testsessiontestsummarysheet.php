<?php
//============================================================+
// File name   : testSessionTestSummarySheet.php
// Begin       : 2017-02-24
// Last Update :
//
// Description : Summary Test sheet for test session period
//
// Author: Eric Lamoureux
//
//============================================================+
function testSessionTestSummarySheet($pdf, $tests, $judges, $testDirectorInfo, $language, $l) {
  $border = 0;	// Set to 1 to display borders of all cells
  $lineYCoord = [43,64,85,105.5,126.5,147.5,168,189,210,231];
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
      $img_file = K_PATH_IMAGES.'reports/tests/summary/test_summary_'.$language.'.jpg';
      $pdf->Image($img_file, 0, 0, 210, 297, '', '', '', false, 300, '', false, false, 0);
      // restore auto-page-break status
      $pdf->SetAutoPageBreak($auto_page_break, $bMargin);
      // set the starting point for the page content
      $pdf->setPageMark();
      // set color for background
      $pdf->SetFillColor(255, 255, 255);

      // $w_page = isset($l['w_page']) ? $l['w_page'].' ' : '';
      // if (empty($pdf->pagegroups)) {
      //   $pagenumtxt = $w_page.$pdf->getAliasNumPage().' / '.$pdf->getAliasNbPages();
      // } else {
      //   $pagenumtxt = $w_page.$pdf->getPageNumGroupAlias().' / '.$pdfs->getPageGroupAlias();
      // }
      // $html = '<table><tr><td width="33%">JeNiAl</td><td width="33%" align="center"></td><td align="right">'.$pagenumtxt.'</td></tr></table>';
      // Causes an error in the PDF
      // $pdf->setPageMark();

      // set color for background
      $pdf->SetFillColor(255, 255, 255);
    }
    // set font
    $pdf->SetFont('times', '', 10);

    // Instead of a footer, let's do a header
    //  $html = '<table><tr><td width="33%"></td><td width="33%" align="center"></td><td align="right"></td></tr></table>';
    //  $pdf->writeHTMLCell(0, 0, PDF_MARGIN_RIGHT, 5, utf8_decode($html), $border, 1, 1, true, 'L', true);

    $totalPageFees += (float) $test['fees'];
    // First line
    // # skate Canada
    $html = '<b>'.$test['skatecanadano'].'</b>';
    $pdf->writeHTMLCell(40, 0, 12, $lineYCoord[$lineNo], utf8_decode($html), $border, 1, 1, true, 'L', true);

    // Home Club
    $html = '<b>'.$test['candidatehomeclublabel'].'</b>';
    $pdf->writeHTMLCell(40, 0, 59, $lineYCoord[$lineNo], utf8_decode($html), $border, 1, 1, true, 'L', true);

    // Test code
    // Need to check for partner steps code
    // Steps executed
    $steps = '';
    if ($test['type'] == 'DANCE' && $test['partnersteps'] == 1) {
      $html = '<b>'.$test['partnerstepscode'].'</b>';
      $pdf->writeHTMLCell(25, 0, 103, $lineYCoord[$lineNo], utf8_decode($html), $border, 1, 1, true, 'L', true);
    } else {
      $html = '<b>'.$test['summarycode'].'</b>';
      $pdf->writeHTMLCell(25, 0, 103, $lineYCoord[$lineNo], utf8_decode($html), $border, 1, 1, true, 'L', true);
    }

    // If only one judge, write 1 in the proper area, if not, leave this section as-is
    if (count($judges) == 1) {
      // Judge No
      $html = '<b>1</b>';
      $pdf->writeHTMLCell(30, 0, 140, $lineYCoord[$lineNo], utf8_decode($html), $border, 1, 1, true, 'L', true);
    }

    // Test date
    $html = '<b>'.$test['testday'].'/'.$test['testmonth'].'/'.$test['testyear'].'</b>';
    $pdf->writeHTMLCell(20, 0, 178, $lineYCoord[$lineNo], utf8_decode($html), $border, 1, 1, true, 'L', true);

    // Second line
    // Candidate
    $html = '<b>'.$test['canfirstname'].' '.$test['canlastname'].'</b>';
    $pdf->writeHTMLCell(50, 0, 12, $lineYCoord[$lineNo] + 8, utf8_decode($html), $border, 1, 1, true, 'L', true);

    // Organization number
    $html = '<b>'.$test['candidateorgno'].'</b>';
    $pdf->writeHTMLCell(30, 0, 70, $lineYCoord[$lineNo] + 8, utf8_decode($html), $border, 1, 1, true, 'L', true);

    // Fees
    $html = '<b>'.$test['fees'].'</b>';
    $pdf->writeHTMLCell(20, 0, 178, $lineYCoord[$lineNo] + 8, utf8_decode($html), $border, 1, 1, true, 'L', true);

    if ($lineNo == 9 || $x == count($tests)-1) {
      array_push($pagesFees, $totalPageFees);
      //Page is done, write total
      // set font
      $pdf->SetFont('times', '', 12);
      // $html = '<b>'.$totalPageFees.'</b>';
      $html = '<b>'.'$'.number_format($totalPageFees, 2).'</b>';
      $pdf->writeHTMLCell(23, 0, 176, 248, utf8_decode($html), $border, 1, 1, true, 'L', true);
      $pdf->SetFont('times', '', 10);
    }
  }

  // Write test submission information page
  if (count($tests) > 0) {
    $test = $tests[0];
  	// add a page
  	$pdf->AddPage();
  	// get the current page break margin
  	$bMargin = $pdf->getBreakMargin();
  	// get current auto-page-break mode
  	$auto_page_break = $pdf->getAutoPageBreak();
  	// disable auto-page-break
  	$pdf->SetAutoPageBreak(false, 0);
  	// set bacground image
  	$img_file = K_PATH_IMAGES.'reports/tests/summary/test_submission_information_'.$language.'.jpg';
  	$pdf->Image($img_file, 0, 0, 210, 297, '', '', '', false, 300, '', false, false, 0);
  	// restore auto-page-break status
  	$pdf->SetAutoPageBreak($auto_page_break, $bMargin);
  	// set the starting point for the page content
  	$pdf->setPageMark();
  	// set color for background
  	$pdf->SetFillColor(255, 255, 255);

  	// $w_page = isset($l['w_page']) ? $l['w_page'].' ' : '';
  	// if (empty($pdf->pagegroups)) {
  	// 	$pagenumtxt = $w_page.$pdf->getAliasNumPage().' / '.$pdf->getAliasNbPages();
  	// } else {
  	// 	$pagenumtxt = $w_page.$pdf->getPageNumGroupAlias().' / '.$pdfs->getPageGroupAlias();
  	// }
  	// $html = '<table><tr><td width="33%">JeNiAl</td><td width="33%" align="center"></td><td align="right">'.$pagenumtxt.'</td></tr></table>';

  	// Test date
  	$html = '<b>'.$test['testday'].'/'.$test['testmonth'].'/'.$test['testyear'].'</b>';
  	$pdf->writeHTMLCell(32, 0, 165, 32, utf8_decode($html), $border, 1, 1, true, 'L', true);

  	// Organization number
  	$html = '<b>'.$test['orgno'].'</b>';
  	$pdf->writeHTMLCell(30, 0, 60, 32, utf8_decode($html), $border, 1, 1, true, 'L', true);

  	// Organization name
  	$html = '<b>'.$test['homeclublabel'].'</b>';
  	$pdf->writeHTMLCell(45, 0, 60, 38, utf8_decode($html), $border, 1, 1, true, 'L', true);

  	for ($y = 0; $y < count($judges); $y++) {
  		$judge = $judges[$y];
  		// Jugde Skate Canada No
  		$html = '<b>'.$judge['skatecanadano'].'</b>';
  		$pdf->writeHTMLCell(30, 0, 21, 61 + ($y * 5), utf8_decode($html), $border, 1, 1, true, 'L', true);
  		// Jugde name
  		$html = '<b>'.$judge['firstname'].' '.$judge['lastname'].'</b>';
  		$pdf->writeHTMLCell(30, 0, 61, 61 + ($y * 5), utf8_decode($html), $border, 1, 1, true, 'L', true);
  	}
  	// $testDirectorInfo = getTestSessionDirectorInfo($mysqli, $testsessionid, $language)['data'][0];

  	$html = '<b>'.$testDirectorInfo['skatecanadano'].'</b>';
  	$pdf->writeHTMLCell(30, 0, 162, 55.5, utf8_decode($html), $border, 1, 1, true, 'L', true);

  	$html = '<b>'.$testDirectorInfo['firstname'].' '.$testDirectorInfo['lastname'].'</b>';
  	$pdf->writeHTMLCell(35, 0, 162, 60.5, utf8_decode($html), $border, 1, 1, true, 'L', true);

  	$html = '<b>'.$testDirectorInfo['homephone'].'</b>';
  	$pdf->writeHTMLCell(35, 0, 162, 65.5, utf8_decode($html), $border, 1, 1, true, 'L', true);

  	$html = '<b>'.$testDirectorInfo['email'].'</b>';
  	$pdf->writeHTMLCell(35, 0, 162, 71, utf8_decode($html), $border, 1, 1, true, 'L', true);

  	$totalDayFees = 0.0;
  	for ($y = 0; $y < count($pagesFees); $y++) {
  		$fees = $pagesFees[$y];
  		$totalDayFees += $fees;
  		// Jugde Skate Canada No
  		$html = '<b>'.'$'.number_format($fees, 2).'</b>';
  		$pdf->writeHTMLCell(30, 0, 39, 98.5 + ($y * 5.2), utf8_decode($html), $border, 1, 1, true, 'L', true);
  	}
  	$html = '<b>'.'$'.number_format($totalDayFees, 2).'</b>';
  	$pdf->writeHTMLCell(30, 0, 162, 151.5, utf8_decode($html), $border, 1, 1, true, 'L', true);
  }

  // Write judge expense sheet page
  if (count($judges) > 0 && count($tests) > 0) {
    $test = $tests[0];
    $pdf->SetFont('times', '', 12);
    for ($x = 0; $x < count($judges); $x++) {
      $judge = $judges[$x];
    	// add a page
    	$pdf->AddPage();
    	// get the current page break margin
    	$bMargin = $pdf->getBreakMargin();
    	// get current auto-page-break mode
    	$auto_page_break = $pdf->getAutoPageBreak();
    	// disable auto-page-break
    	$pdf->SetAutoPageBreak(false, 0);
    	// set bacground image
    	$img_file = K_PATH_IMAGES.'reports/tests/summary/test_expense_sheet_'.$language.'.jpg';
    	$pdf->Image($img_file, 0, 0, 210, 297, '', '', '', false, 300, '', false, false, 0);
    	// restore auto-page-break status
    	$pdf->SetAutoPageBreak($auto_page_break, $bMargin);
    	// set the starting point for the page content
    	$pdf->setPageMark();
    	// set color for background
    	$pdf->SetFillColor(255, 255, 255);

    	// Test date
    	$html = '<b>'.$test['testday'].'/'.$test['testmonth'].'/'.$test['testyear'].'</b>';
    	$pdf->writeHTMLCell(40, 0, 45, 63, utf8_decode($html), $border, 1, 1, true, 'L', true);

    	// Organization name
    	$html = '<b>'.$test['homeclublabel'].'</b>';
    	$pdf->writeHTMLCell(45, 0, 102, 70, utf8_decode($html), $border, 1, 1, true, 'L', true);

  		$html = '<b>'.$judge['firstname'].' '.$judge['lastname'].'</b>';
  		$pdf->writeHTMLCell(50, 0, 65, 77, utf8_decode($html), $border, 1, 1, true, 'L', true);
    }
  }
}
