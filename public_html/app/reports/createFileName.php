<?php

require_once('getbaseurl.php');

/**
 * This function creates a file name for a report
 */
function createFileName() {
  // $tmpfilename = tempnam("../../tmp", "JeN");
  $tmpfilename = tempnam('../../../private/' . $_SERVER['HTTP_HOST'] . '/tmp', "JeNiAl");
  if (strpos($tmpfilename, '.tmp')) {
    $filename = str_replace(".tmp",".pdf", $tmpfilename);
  } else {
    $filename = $tmpfilename . '.pdf';
  }
  rename($tmpfilename, $filename);

	return $filename;
};

function convertFileName($mysqli, $filename) {
  // $baseurl = getBaseUrl($mysqli);
  $partfilename = substr($filename, strpos($filename, "/JeNiAl"));

  // $newfilename = str_replace("C:\\wamp\\www\\", $baseurl, $filename);
  // [lamoeric 2018/09/10] this conversion is used to display a PDF reports in the browser, so the url must be valid.
  // if (strpos($baseurl, "localhost")) {
    $newfilename = 'http://' . $_SERVER['HTTP_HOST'] . '/tmp' . $partfilename;
  // } else {
    // $newfilename = $partfilename;
  // }
  return $newfilename;
}
