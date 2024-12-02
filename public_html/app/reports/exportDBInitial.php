<?php
//============================================================+
// File name   : exportBDInitial.php
// Begin       : 2017-09-15
// Last Update :
//
// Description : exports the DB for an initial installation
//
// Author: Eric Lamoureux
//
//============================================================+

// Include the main TCPDF library (search for installation path).
require_once('../../include/tcpdf_include.php');
require_once('../../../private/'. $_SERVER['HTTP_HOST'].'/include/config.php');
require_once('../../include/nocache.php');
// require_once('customheader.php');
// require_once('mypdf_footer.php');
// require_once('getActiveSessionLabel.php');
// require_once('createFileName.php');

set_time_limit(500);

$language = 'fr-ca';

// create new PDF document
// $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, false, 'ISO-8859-1', false);

// set document information
// $pdf->SetCreator(PDF_CREATOR);
// $pdf->SetAuthor(PDF_AUTHOR);
// $pdf->SetTitle('');
// $pdf->SetSubject('');
// $pdf->SetKeywords('');

// set header and footer fonts
// $pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
// $pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));

// set default monospaced font
// $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

// set margins
// $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
// $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
// $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

// set auto page breaks
// $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

// set image scale factor
// $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

// set some language-dependent strings (optional)
// if (@file_exists(dirname(__FILE__).'/lang/'.$language.'/'.basename(__FILE__))) {
// 	require_once(dirname(__FILE__).'/lang/'.$language.'/'.basename(__FILE__));
// 	$pdf->setLanguageArray($l);
// }

// set default font subsetting mode
// $pdf->setFontSubsetting(true);

// Set font
// dejavusans is a UTF-8 Unicode font, if you only need to
// print standard ASCII chars, you can use core fonts like
// helvetica or times to reduce file size.
// $pdf->SetFont('times', '', 10, '', true);

// $nboflinefirstpage = 37;
// $nboflinenextpage  = 39;
// $pageno = 1;
// $nboflineonpage  = 0;
// $html = getCodeTableData($mysqli);
$html = "";
$html .= getGenericTableData($mysqli, "cpa_configuration", "", "id", ["cpalongname", "cpashortname", "cpaaddress"]);
$html .= getGenericTableData($mysqli, "cpa_users", " userid = 'fulladmin' ", "id", []);
$html .= getGenericTableData($mysqli, "cpa_privileges", "", "id", []);
$html .= getGenericTableData($mysqli, "cpa_roles", "", "id", []);
$html .= getGenericTableData($mysqli, "cpa_roles_privileges", "", "id", []);
$html .= getGenericTableData($mysqli, "cpa_users_roles", " userid = (SELECT cu.id FROM cpa_users cu WHERE cu.userid = 'fulladmin')", "id", []);
$html .= getGenericTableData($mysqli, "cpa_codetable", "", "ctname", ["description"]);
$html .= getGenericTableData($mysqli, "cpa_canskate", "", "id", []);
$html .= getGenericTableData($mysqli, "cpa_canskate_tests", "", "id", ["label"]);
$html .= getGenericTableData($mysqli, "cpa_musics", "", "id", []);
$html .= getGenericTableData($mysqli, "cpa_tests_definitions", "", "id", []);
$html .= getGenericTableData($mysqli, "cpa_tests", "", "id", ["label"]);
$html .= getGenericTableData($mysqli, "cpa_tests_musics", "", "id", []);
$html .= getGenericTableData($mysqli, "cpa_ws_pages", "", "name", ["navbarlabel"], "create_wstext");
$html .= getGenericTableData($mysqli, "cpa_ws_sections", "", "name", ["navbarlabel","title","subtitle"], "create_wstext");
$html .= getGenericTableData($mysqli, "cpa_ws_pages_sections", "", "pagename, sectionname", [], "create_wstext");
$html .= getGenericTableData($mysqli, "cpa_ws_contactinfo", "", "fscname", ["label"], "create_wstext");
echo $html;
// $pdf->AddPage('P');
// $pdf->writeHTMLCell(0, 0, '', '', $html, 0, 1, 0, true, '', true);

// ---------------------------------------------------------

// Close and output PDF document
// This method has several options, check the source code documentation for more information.
 // $pdf->Output('toto', 'I');

//============================================================+
// END OF FILE
//============================================================+


/**
 * This function gets the english and french label for a translation code
 */
function getTextForCode($mysqli, $id, $tableName = 'cpa_text') {
	$data = array();
	$data['english'] = "";
	$data['french'] = "";
	$query = "SELECT text FROM ". $tableName . " WHERE id = $id and language = 'en-ca'";
	$result = $mysqli->query($query);
	while ($row = $result->fetch_assoc()) {
		$data['english'] = $row['text'];
		$data['english'] = str_replace("'", "''", $data['english']);
	}

	$query = "SELECT text FROM ". $tableName . " WHERE id = $id and language = 'fr-ca'";
	$result = $mysqli->query($query);
	while ($row = $result->fetch_assoc()) {
		$data['french'] = $row['text'];
		$data['french'] = str_replace("'", "''", $data['french']);
	}
	return $data;
}

function getGenericTableData($mysqli, $tableName, $whereclause, $orderby, $labelColumnList, $labelFunctionName = 'create_systemtext') {
	$columArr = array();
	$valuelist = "";
	$sqlCmd = "INSERT INTO &tableName& (&collist&) VALUES (&valuelist&);";

	// Get the column list
	$query = "SELECT * FROM " . $tableName . (empty($whereclause) ? "" : " WHERE " . $whereclause) . " ORDER BY " . $orderby;
	$result = $mysqli->query($query);
	$values = $result->fetch_all(MYSQLI_ASSOC);
	if(!empty($values)){
		$columArr = array_keys($values[0]);
	}

	// Get the values
	$query = "SELECT * FROM " . $tableName . (empty($whereclause) ? "" : " WHERE " . $whereclause) . " ORDER BY " . $orderby;
	$result = $mysqli->query($query);
	while ($row = $result->fetch_assoc()) {
		$valuelist = "";
		for ($x = 0; $x < count($row); $x++) {
			if (!empty($valuelist)) {
				$valuelist .= ', ';
			}
			if (in_array($columArr[$x], $labelColumnList)) {
				if ($labelFunctionName == 'create_systemtext') {
					$data = getTextForCode($mysqli, $row[$columArr[$x]], "cpa_text");
				} else {
					$data = getTextForCode($mysqli, $row[$columArr[$x]], "cpa_ws_text");
				}
				$valuelist = $valuelist . $labelFunctionName . "('" . $data['english'] . "', '" . $data['french'] . "')";
			} else {
				if (is_null($row[$columArr[$x]])) {
					// echo $tableName. ' ' . $columArr[$x] . 'is null';
					$valuelist = $valuelist . 'null';	
				} else {
					$valuelist = $valuelist . "'" . str_replace("'", "''", $row[$columArr[$x]]) . "'";
					// $valuelist = $valuelist . "'" . $row[$columArr[$x]] . "'";
				}
			}
		}
		// $sqlCommands[] = $sqlCmd . $sqlCmdValues . $valuelist . ")";
		$temp = str_replace("&tableName&", $tableName, $sqlCmd);
		$temp = str_replace("&collist&", implode(", ", array_unique($columArr)), $temp);
		$temp = str_replace("&valuelist&", $valuelist, $temp);
		$sqlCommands[] = $temp;
	}
	return implode("<br>", $sqlCommands) . "<br><br>";
}


/**
 * This function gets the SQL command for one table
 */
function getCodeTableData($mysqli) {
	try{
		$sqlCmd = "INSERT INTO cpa_codetable (";
		$sqlCmdValues = " VALUES (";
		$sqlCommands = array();
		$columlist = "";
		$valuelist = "";
		$data = array();
		$data['data'] = array();
		$query = "SELECT * FROM cpa_codetable order by ctname";
		$result = $mysqli->query($query);

		$values = $result->fetch_all(MYSQLI_ASSOC);
		$columArr = array();

		if(!empty($values)){
			$columArr = array_keys($values[0]);
		}
		$columlist = implode(", ", array_unique($columArr));
		$sqlCmd .= $columlist . ")";
		$result = $mysqli->query($query);
		while ($row = $result->fetch_assoc()) {
			$valuelist = "";
			for ($x = 0; $x < count($row); $x++) {
				if (!empty($valuelist)) {
					$valuelist .= ', ';
				}
				if ($columArr[$x] == 'description') {
					$data = getTextForCode($mysqli, $row[$columArr[$x]]);
					$valuelist = $valuelist . "create_systemtext('" . $data['english'] . "', '" . $data['french'] . "')";
				} else {
					$valuelist = $valuelist . "'" . $row[$columArr[$x]] . "'";
				}
			}
			$sqlCommands[] = $sqlCmd . $sqlCmdValues . $valuelist . ")";
			// break;
		}
		return implode(";<br> ", $sqlCommands) . ";<br> ";
	}catch (Exception $e) {
		$data = array();
		$data['success'] = false;
		$data['message'] = $e->getMessage();
		return $data;
	}
};
