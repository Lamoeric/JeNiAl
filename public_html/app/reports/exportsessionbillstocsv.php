<?php
/*
Author : Eric Lamoureux
*/

require_once('../../../private/'. $_SERVER['HTTP_HOST'].'/include/config.php');
require_once('../core/directives/billing/bills.php');


try {
  $filter = array();
  if (isset($_GET['language']) && !empty(isset($_GET['language']))) {
    $language = $_GET['language'];
  } else {
    echo "Language not set";
    die;
  }
  if (isset($_GET['sessionid']) && !empty(isset($_GET['sessionid']))) {
    $sessionid = $_GET['sessionid'];
  } else {
    echo "Session id not set";
    die;
  }
  $data = getSessionBillingList($mysqli, $sessionid, $language);
  $fp = fopen('php://output', 'w');
  if ($fp && $data['success'] == true) {
    header('Content-Type: text/csv; charset=Windows-1252');
    header('Content-Disposition: attachment; filename="export.csv"; charset=Windows-1252');
    header("Content-Encoding: Windows-1252");
    header("Content-Transfer-Encoding: Windows-1252");
    header('Pragma: no-cache');
    header('Expires: 0');

    fputcsv($fp, ['sep=,']);
    fputcsv($fp, array_map("utf8_decode", $data['headers']));
    for ($i = 0; $i < sizeof($data['data']); $i++) {
      $row = array_map("utf8_decode", $data['data'][$i] ?? '');
      fputcsv($fp, array_values($row));
    }
    die;
  }
  // $fp = fopen('php://output', 'w');
  // if ($fp && $result) {
  //     // header('Content-Type: text/csv');
  //     // header('Content-Type: text/csv; charset=UTF-8');
  //     header('Content-Type: text/csv; charset=Windows-1252');
  //     header('Content-Disposition: attachment; filename="export.csv"; charset=Windows-1252');
  //     header("Content-Encoding: Windows-1252");
  //     // header("Content-Transfer-Encoding: binary");
  //     // header("Content-Transfer-Encoding: binary");
  //     header("Content-Transfer-Encoding: Windows-1252");
  //     header('Pragma: no-cache');
  //     header('Expires: 0');
  //     // fprintf($fp, chr(0xEF).chr(0xBB).chr(0xBF));
  //     // fprintf($fp, chr(255) . chr(254));
  //     // fprintf($fp, 'sep=,\n');
  //     fputcsv($fp, ['sep=,']);
  //     fputcsv($fp, $headers);
  //     while ($row = $result->fetch_assoc()) {
  //       $row = array_map("utf8_decode", $row);
  //       fputcsv($fp, array_values($row));
  //     }
  //     // $fp = mb_convert_encoding($fp, 'Windows-1252', 'UTF-8');
  //     // print "\xEF\xBB\xBF";
  //     // print chr(255) . chr(254) . mb_convert_encoding($fp, 'UTF-16LE', 'UTF-8');
  //     // print chr(255) . chr(254) . mb_convert_encoding($fp, 'Windows-1252', 'UTF-8');
  //     // $fp =  mb_convert_encoding($fp, 'Windows-1252', 'UTF-8');
  //     // mb_convert_encoding($value, 'Windows-1252');
  //     // $fp = iconv(mbq_detect_encoding($fp), 'Windows-1252//TRANSLIT', $fp);
  //     die;
  // }
}catch (Exception $e) {
  $message = $e->getMessage();
  echo $message;
  die;
}

/*
 * This function returns the column's header in the proper language
 * TODO : switch this for a array filled by one SQL command instead of one command per column name
 */
//function convertColumnName($mysqli, $columnname, $language) {
//  $query = "select getcodedescription('exportbillscolheaders', '$columnname', '$language') label FROM dual";
//	$result = $mysqli->query($query);
//  $data = $result->fetch_assoc();
//  return $data['label'] && !empty($data['label']) ? $data['label'] : $columnname;
//}
//
///*
// * This function returns the column's name from the query
// */
//function getColumnName($result, $field_offset) {
//    $properties = mysqli_fetch_field_direct($result, $field_offset);
//    return is_object($properties) ? $properties->name : null;
//}

/**
 * This function gets the all the bills for the session
 */
function getSessionBillingList($mysqli, $sessionid, $language) {
	try{
		if(empty($sessionid)) throw new Exception("Invalid session id.");
		$query = "SELECT DISTINCT cb.id, 'Total' as type, cb.billingname, '' as skatername, '' as paypaltransactionid, cb.billingdate, '' as paymentmethod, '' as registrationsubtotal, '' as transactionamount, cb.totalamount, cb.paidamount, (cb.totalamount + cb.paidamount) delta
							FROM cpa_bills cb
							JOIN cpa_bills_registrations cbr ON cbr.billid = cb.id
							JOIN cpa_registrations csr ON csr.id = cbr.registrationid
							where relatednewbillid is null
							AND csr.sessionid = $sessionid
							order by cb.billingname, billingdate, cb.id";
		$result = $mysqli->query($query);
		$data = array();
		$data['data'] = array();
		$data['headers'][0] = 
		
		$data['headers'][0] = 'id';
		$data['headers'][1] = 'type';
		$data['headers'][2] = 'billingname';
		$data['headers'][3] = 'skatername';
		$data['headers'][4] = 'paypaltransactionid';
		$data['headers'][5] = 'billingdate';
		$data['headers'][6] = 'paymentmethod';
		$data['headers'][7] = 'registrationsubtotal';
		$data['headers'][8] = 'transactionamount';
		$data['headers'][9] = 'totalamount';
		$data['headers'][10] = 'paidamount';
		$data['headers'][11] = 'delta';

		while ($row = $result->fetch_assoc()) {
			$data['data'][] = $row;
			$row['registrations'] = getBillRegistrations($mysqli, $row['id'], $language)['data'];
			$row['transactions']  = getBillTransactions($mysqli, $row['id'], $language)['data'];
			$newRow = [];
    	for ($i = 0; $i < sizeof($row['registrations']); $i++) {
    		$registration = $row['registrations'][$i];
    		$newRow['id'] = $row['id'];
    		$newRow['type'] = 'S-total';
    		$newRow['billingname'] =  $row['billingname'];
    		$newRow['skatername'] =  $registration['member']['firstname'].' '. $registration['member']['lastname'];
    		$newRow['charpaypaltransactionidgecode'] =  '';
    		$newRow['billingdate'] = '';
    		$newRow['paymentmethod'] = '';
    		$newRow['registrationsubtotal'] = $registration['subtotal'];
    		$newRow['transactionamount'] = '';
    		$newRow['totalamount'] = '';
    		$newRow['paidamount'] = '';
    		$newRow['delta'] = '';
				$data['data'][] = $newRow;
    	}
    	for ($i = 0; $i < sizeof($row['transactions']); $i++) {
    		$transactions = $row['transactions'][$i];
    		$newRow['id'] = $row['id'];
    		$newRow['type'] = 'Transaction';
    		$newRow['billingname'] =  $row['billingname'];
    		$newRow['skatername'] =  '';
    		$newRow['paypaltransactionid'] =  $row['paypaltransactionid'];;
    		$newRow['billingdate'] = $transactions['transactiondate'];
    		$newRow['paymentmethod'] = $transactions['paymentmethod']. ' ' . $transactions['cancelreasonlabel'];
    		$newRow['registrationsubtotal'] = '';
    		$newRow['transactionamount'] = $transactions['transactionamount'];
    		$newRow['totalamount'] = '';
    		$newRow['paidamount'] = '';
    		$newRow['delta'] = '';
				$data['data'][] = $newRow;
    	}
			

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

?>
