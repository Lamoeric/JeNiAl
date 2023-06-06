<?php
require_once('getClubNameAndAddress.php');

function addCustomHeader($mysqli, $pdf, $language) {
  // Get header info from database
  $data = getClubNameAndAddress($mysqli, $language);
  $headerTitle = utf8_decode($data['data'][0]['cpalongname']);
  $headerAddress = utf8_decode($data['data'][0]['cpaaddress']);

  $needles = array("<br>", "&#13;", "<br/>", "\\n");
  $replacement = "\n";
  $headerAddress = str_replace($needles, $replacement, $headerAddress);

  // set default header data
  $pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, $headerTitle, $headerAddress, array(0,0,0), array(0,0,0));

}
