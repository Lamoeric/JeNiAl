<?php
/*
Author : Eric Lamoureux
*/
require_once('../../../../../private/' . $_SERVER['HTTP_HOST'] . '/include/config.php');
require_once('../../../../include/nocache.php');
require_once('../../../../backend/invalidrequest.php'); //
require_once('../../directives/billing/bills.php');

if (isset($_POST['type']) && !empty(isset($_POST['type']))) {
	$type = $_POST['type'];

	switch ($type) {
		case "insertTransaction":
			insertTransaction($mysqli, $_POST['transaction']);
			break;
		case "updateTransaction":
			updateTransaction($mysqli, $_POST['transaction']);
			break;
		case "cancelTransaction":
			cancelTransaction($mysqli, $_POST['transaction']);
			break;
		default:
			invalidRequest($type);
	}
} else {
	invalidRequest();
};

/**
 * This function inserts a transaction.
 */
function insertTransaction($mysqli, $transaction) {
	try {
		$data = array();
		$billid 			= $mysqli->real_escape_string(isset($transaction['billid'])					? (int)$transaction['billid'] : 0);
		$transactiontype 	= $mysqli->real_escape_string(isset($transaction['transactiontype'])		? $transaction['transactiontype'] : '');
		$transactionamount	= $mysqli->real_escape_string(isset($transaction['transactionamount'])		? $transaction['transactionamount'] : '');
		$transactiondate 	= $mysqli->real_escape_string(isset($transaction['transactiondatestr'])		? $transaction['transactiondatestr'] : '');
		$paymentmethod 		= $mysqli->real_escape_string(isset($transaction['paymentmethod'])			? $transaction['paymentmethod'] : '');
		$checkno 			= $mysqli->real_escape_string(isset($transaction['checkno'])				? $transaction['checkno'] : 0);
		$receiptno 			= $mysqli->real_escape_string(isset($transaction['receiptno'])				? $transaction['receiptno'] : 0);
		$paperreceiptno 	= $mysqli->real_escape_string(isset($transaction['paperreceiptno'])			? $transaction['paperreceiptno'] : 0);
		$receivedby 		= $mysqli->real_escape_string(isset($transaction['receivedby'])				? $transaction['receivedby'] : '');
		$comments 			= $mysqli->real_escape_string(isset($transaction['comments'])				? $transaction['comments'] : '');

		$query = "INSERT INTO cpa_bills_transactions (id, billid, transactiontype, transactionamount, transactiondate, paymentmethod, checkno, receiptno, paperreceiptno, receivedby, comments) 
				  VALUES (NULL, $billid, '$transactiontype', '$transactionamount', '$transactiondate', '$paymentmethod', $checkno, $receiptno, $paperreceiptno, '$receivedby', '$comments')";
		if ($mysqli->query($query)) {
			$data['id'] = (int) $mysqli->insert_id;
			if ($transactiontype == 'PAYMENT') {
				$transactionamount = $transactionamount * -1;
			}
			updateBillPaidAmountInt($mysqli, $billid, $transactionamount);
		} else {
			throw new Exception($mysqli->sqlstate . ' - ' . $mysqli->error);
		}
		$data['success'] = true;
		echo json_encode($data);
		exit;
	} catch (Exception $e) {
		$data = array();
		$data['success'] = false;
		$data['message'] = $e->getMessage();
		echo json_encode($data);
		exit;
	}
};

/**
 * This function updates a transaction.
 * NOT USED
 */
function updateTransaction($mysqli, $transaction) {
	try {
		$data = array();
		$id 				= $mysqli->real_escape_string(isset($transaction['id'])						? $transaction['id'] : '');
		$transactionamount 	= $mysqli->real_escape_string(isset($transaction['transactionamount'])		? $transaction['transactionamount'] : '');
		$receiptno 			= $mysqli->real_escape_string(isset($transaction['receiptno'])				? $transaction['receiptno'] : 0);
		$paperreceiptno 	= $mysqli->real_escape_string(isset($transaction['paperreceiptno'])			? $transaction['paperreceiptno'] : 0);
		$comments 			= $mysqli->real_escape_string(isset($transaction['comments'])				? $transaction['comments'] : '');

		$query = "UPDATE cpa_bills_transactions
				  SET receiptno = $receiptno, paperreceiptno = $paperreceiptno, comments = '$comments'
				  WHERE id = $id";
		if (!$mysqli->query($query)) {
			throw new Exception($mysqli->sqlstate . ' - ' . $mysqli->error);
		}
		$data['success'] = true;
		echo json_encode($data);
		exit;
	} catch (Exception $e) {
		$data = array();
		$data['success'] = false;
		$data['message'] = $e->getMessage();
		echo json_encode($data);
		exit;
	}
};

/**
 * This function cancels a transaction.
 * Canceling a transaction means we update a few fields, we do not delete the transaction.
 */
function cancelTransaction($mysqli, $transaction) {
	try {
		$data = array();
		$id 				= $mysqli->real_escape_string(isset($transaction['id'])					? $transaction['id'] : '');
		$canceleddate 		= $mysqli->real_escape_string(isset($transaction['canceleddatestr'])	? $transaction['canceleddatestr'] : '');
		$cancelreason 		= $mysqli->real_escape_string(isset($transaction['cancelreason'])		? $transaction['cancelreason'] : '');
		$canceledby 		= $mysqli->real_escape_string(isset($transaction['canceledby'])			? $transaction['canceledby'] : '');
		$billid 			= $mysqli->real_escape_string(isset($transaction['billid'])				? (int)$transaction['billid'] : 0);
		$transactionamount 	= $mysqli->real_escape_string(isset($transaction['transactionamount'])	? $transaction['transactionamount'] : '');
		$transactiontype 	= $mysqli->real_escape_string(isset($transaction['transactiontype'])	? $transaction['transactiontype'] : '');

		$query = "UPDATE cpa_bills_transactions
				  SET canceleddate = '$canceleddate', cancelreason = '$cancelreason', canceledby = '$canceledby', iscanceled = 1
				  WHERE id = $id";
		if ($mysqli->query($query)) {
			if ($transactiontype == 'PAYMENT') {
				$transactionamount = $transactionamount * -1;
			}
			updateBillPaidAmountInt($mysqli, $billid, $transactionamount * -1);
		} else {
			throw new Exception($mysqli->sqlstate . ' - ' . $mysqli->error);
		}
		$data['success'] = true;
		echo json_encode($data);
		exit;
	} catch (Exception $e) {
		$data = array();
		$data['success'] = false;
		$data['message'] = $e->getMessage();
		echo json_encode($data);
		exit;
	}
};
