<?php
/*
Author : Eric Lamoureux
*/

/**
 * This function gets the bill for the current registration
 lamouree 9/05/2023 changed the condition for the paidinfull from == 0 to <=0
 */
function updateBillPaidAmount($mysqli, $billid, $amount){
	try{
		$query = "UPDATE cpa_bills 
							SET paidamount = paidamount + $amount,
							paidinfull = if(totalamount + paidamount <= 0, 1, 0)
							WHERE id = '$billid'";
		if( $mysqli->query( $query ) ){
		} else {
			throw new Exception( $mysqli->sqlstate.' - '. $mysqli->error );
		}
		$data['success'] = true;
		echo json_encode($data);
		exit;
	}catch (Exception $e){
		$data = array();
		$data['success'] = false;
		$data['message'] = $e->getMessage();
		echo json_encode($data);
		exit;
	}
};

?>