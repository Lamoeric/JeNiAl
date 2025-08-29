<?php
require_once('getClubNameAndAddress.php');

function addCustomHeader($mysqli, $pdf, $language) {
  // Get header info from database
  $data = getClubNameAndAddress($mysqli, $language);
  $headerTitle = mb_convert_encoding($data['data'][0]['cpalongname'], 'Windows-1252', 'UTF-8');
  $headerAddress = mb_convert_encoding($data['data'][0]['cpaaddress'], 'Windows-1252', 'UTF-8');

  $needles = array("<br>", "&#13;", "<br/>", "\\n");
  $replacement = "\n";
  $headerAddress = str_replace($needles, $replacement, $headerAddress);
  // $headerAddress = K_PATH_PRIVATEIMAGES; // for testing
  
  // set default header data
  $pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, $headerTitle, $headerAddress, array(0,0,0), array(0,0,0));

}
