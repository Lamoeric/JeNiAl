<?php
/*
Author : Eric Lamoureux
*/
require_once('../../../private/'. $_SERVER['HTTP_HOST'].'/include/config.php');
require_once('../../include/nocache.php');
require_once('../core/directives/billing/bills.php');

if( isset($_POST['type']) && !empty( isset($_POST['type']) ) ){
	$type = $_POST['type'];

	switch ($type) {
		case "insert_bill":
			// insert_bill($mysqli);
			break;
		case "updateEntireBill":
			// updateEntireBill($mysqli, $_POST['bill']);
			break;
		case "delete_bill":
			// delete_bill($mysqli);
			break;
		case "getAllBills":
			getAllBillsExt($mysqli, $_POST['filter']);
			break;
		case "getBillDetails":
			getBillDetailsExt($mysqli, $_POST['id'], $_POST['language']);
			break;
		default:
			invalidRequest();
	}
} else {
	invalidRequest();
};

/**
 * This function will handle bill add, update functionality
 * @throws Exception
 */
function insert_bill($mysqli){
	try{
		$data = array();
		$id =					$mysqli->real_escape_string(isset( $_POST['bill']['id	='] ) 			? $_POST['bill']['id'] : '');
		$name =				$mysqli->real_escape_string(isset( $_POST['bill']['name'] ) 				? $_POST['bill']['name'] : '');
		$label =			$mysqli->real_escape_string(isset( $_POST['bill']['label'] ) 			? $_POST['bill']['label'] : '');
		$label_fr =		$mysqli->real_escape_string(isset( $_POST['bill']['label_fr'] ) 		? $_POST['bill']['label_fr'] : '');
		$label_en =		$mysqli->real_escape_string(isset( $_POST['bill']['label_en'] ) 		? $_POST['bill']['label_en'] : '');
		$address =		$mysqli->real_escape_string(isset( $_POST['bill']['address'] ) 		? $_POST['bill']['address'] : '');
		$nbrofice =		$mysqli->real_escape_string(isset( $_POST['bill']['nbrofice'] ) 		? (int)$_POST['bill']['nbrofice'] : 1);
		$ices =				$mysqli->real_escape_string(isset( $_POST['bill']['JSONices'] ) 		? $_POST['course']['JSONices'] : '[]');
		$active =			$mysqli->real_escape_string(isset( $_POST['bill']['active'] ) 			? (int)$_POST['bill']['active'] : 0);

		if($name == ''){
			throw new Exception( "Required fields missing, Please enter and submit" );
		}

		$query = "INSERT INTO cpa_bills (name, label, address, active, ices)
							VALUES ('$name', create_systemText('$label_en', '$label_fr'), '$address', $active, '$ices')";
		if( $mysqli->query( $query ) ){
			$data['success'] = true;
			if(empty($id))$data['id'] = (int) $mysqli->insert_id;
		} else {
			throw new Exception( $mysqli->sqlstate.' - '. $mysqli->error );
		}
		$mysqli->close();
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

/**
 * This function will handle bill add, update functionality
 * @throws Exception
 */
function update_bill($mysqli, $bill){
	try{
		$data = array();
		$id =						$mysqli->real_escape_string(isset($bill['id'] ) 					? $bill['id'] : '');
		$name =					$mysqli->real_escape_string(isset($bill['name'] ) 				? $bill['name'] : '');
		$label =				$mysqli->real_escape_string(isset($bill['label'] ) 			? $bill['label'] : '');
		$label_fr =			$mysqli->real_escape_string(isset($bill['label_fr'] ) 		? $bill['label_fr'] : '');
		$label_en =			$mysqli->real_escape_string(isset($bill['label_en'] ) 		? $bill['label_en'] : '');
		$address =			$mysqli->real_escape_string(isset($bill['address'] ) 		? $bill['address'] : '');
		$nbrofice =			$mysqli->real_escape_string(isset($bill['nbrofice'] ) 		? (int)$bill['nbrofice'] : 1);
//		$ices =					$mysqli->real_escape_string(isset($bill['JSONices'] ) 		? $bill['JSONices'] : '[]');
		$active =				$mysqli->real_escape_string(isset($bill['active'] ) 			? (int)$bill['active'] : 0);

		if($name == '' || $id == ''){
			throw new Exception( "Required fields missing, Please enter and submit" );
		}

		$query = "UPDATE cpa_bills SET name = '$name', address = '$address', active = '$active' WHERE id = '$id'";

		if( $mysqli->query( $query ) ){
			$query = "UPDATE cpa_text set text = '$label_fr' where id = $label and language = 'fr-ca'";
			if( $mysqli->query( $query ) ){
				$data['success'] = true;
				$query = "UPDATE cpa_text set text = '$label_en' where id = $label and language = 'en-ca'";
				if( $mysqli->query( $query ) ){
					$data['success'] = true;
					$data['message'] = 'Bill updated successfully.';
				} else {
					throw new Exception( $mysqli->sqlstate.' - '. $mysqli->error );
				}
			} else {
				throw new Exception( $mysqli->sqlstate.' - '. $mysqli->error );
			}
		} else {
			throw new Exception( $mysqli->sqlstate.' - '. $mysqli->error );
		}
		return $data;
		exit;
	}catch (Exception $e){
		$data = array();
		$data['success'] = false;
		$data['message'] = $e->getMessage();
		return $data;
		exit;
	}
};

/**
 * This function will handle user deletion
 * @param string $id
 * @throws Exception
 */
function delete_bill($mysqli){
	try{
		$id = $mysqli->real_escape_string(isset( $_POST['bill']['id'] ) ? $_POST['bill']['id'] : '');
		$label = $mysqli->real_escape_string(isset( $_POST['bill']['label'] ) ? $_POST['bill']['label'] : '');
		if(empty($id)) throw new Exception( "Invalid User." );
		$query = "DELETE FROM cpa_text WHERE id = '$label'";
		if($mysqli->query( $query )){
			$query = "DELETE FROM cpa_bills WHERE id = '$id'";
			if($mysqli->query( $query )){
				$data['success'] = true;
				$data['message'] = 'Bill deleted successfully.';
				echo json_encode($data);
				exit;
			} else {
				throw new Exception( $mysqli->sqlstate.' - '. $mysqli->error );
			}
		} else {
			throw new Exception( $mysqli->sqlstate.' - '. $mysqli->error );
		}
	}catch (Exception $e){
		$data = array();
		$data['success'] = false;
		$data['message'] = $e->getMessage();
		echo json_encode($data);
		exit;
	}
};

/**
 * This function gets the list of all bills from database
 */
function getAllBillsExt($mysqli, $filter){
	try{
		$data = array();
		$whereclause = " where 1=1 ";
		$billingname = '';
		if (!empty($filter['firstname'])) {	
			$billingname = $filter['firstname'] . '%';
		}
		if (!empty($filter['lastname'])) {
			$billingname .= '%' . $filter['lastname'];
		}
		if (!empty($billingname)) {
			$whereclause .= " and billingname like '" . $billingname . "'";
		}
		if (isset($filter['billpaid'])) {
			$billpaid = $filter['billpaid'];
			if ($billpaid == '0') {
				$whereclause .= " and (paidinfull = '1' and totalamount + paidamount = 0) ";
			} else if ($billpaid == '1') {
				$whereclause .= " and totalamount + paidamount < 0 ";
			} else if ($billpaid == '2') {
				$whereclause .= " and totalamount + paidamount > 0 ";
			}
		}

		if (!empty($filter['registration']) && $filter['registration'] == 'REGISTERED') {
			$whereclause .= " and id in (select billid from cpa_bills_registrations where registrationid in (select id from cpa_registrations where sessionid = (select id from cpa_sessions where active = '1') and memberid in (select memberid from cpa_sessions_courses_members where sessionscoursesid in (select id from cpa_sessions_courses where sessionid = (select id from cpa_sessions where active = '1')))))" ;
		}
		if (!empty($filter['registration']) && $filter['registration'] == 'NOTREGISTERED') {
			$whereclause .= " and id not in (select billid from cpa_bills_registrations where registrationid in (select id from cpa_registrations where sessionid = (select id from cpa_sessions where active = '1') and memberid in (select memberid from cpa_sessions_courses_members where sessionscoursesid in (select id from cpa_sessions_courses where sessionid = (select id from cpa_sessions where active = '1')))))" ;
		}
		if (!empty($filter['onlyopenedbills']) && $filter['onlyopenedbills'] == '1') {
			$whereclause .= " and relatednewbillid is null ";
		}
		$data['where'] = $whereclause;
		$query = "	SELECT id, billingname, billingdate, totalamount, totalamount+paidamount as amountleft, relatednewbillid, splitfrombillid, relatedoldbillid 
					FROM cpa_bills ". $whereclause ."
					order by billingdate desc, id desc";
		$result = $mysqli->query( $query );
		while ($row = $result->fetch_assoc()) {
			$data['data'][] = $row;
		}
		$data['success'] = true;
		echo json_encode($data);exit;
	}catch (Exception $e){
		$data = array();
		$data['success'] = false;
		$data['message'] = $e->getMessage();
		echo json_encode($data);
		exit;
	}
};

/**
 * This function gets the list of all contacts for this bill
 */
function getAllBillContacts($mysqli, $billid){
	$data = array();
	$query = "	SELECT distinct cc.* 
				FROM cpa_bills cb
				JOIN cpa_bills_registrations cbr ON cbr.billid = cb.id
				JOIN cpa_registrations cr ON cr.id = cbr.registrationid
				JOIN cpa_members cm ON cm.id = cr.memberid
				JOIN cpa_members_contacts cmc ON cmc.memberid = cm.id
				JOIN cpa_contacts cc ON cc.id = cmc.contactid
				WHERE cb.id = $billid";
	$result = $mysqli->query( $query );
	while ($row = $result->fetch_assoc()) {
		$data['data'][] = $row;
	}
	$data['success'] = true;
	return $data;
};

/**
 * This function gets the details of one bill from database
 */

function getBillDetailsExt($mysqli, $id, $language){
	try{
		$data = getBillInt($mysqli, $id, $language);
		$data['contacts'] = getAllBillContacts($mysqli, $id);
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

/**
 * This function will handle insert/update/delete of a ice in DB
 * @throws Exception
 */
function updateEntireIces($mysqli, $billid, $ices){
	try{
		$data = array();
		$data['inserted'] = 0;
		$data['updated'] 	= 0;
		$data['deleted'] 	= 0;
		for($x = 0; $x < count($ices); $x++) {
			$id = 				$mysqli->real_escape_string(isset( $ices[$x]['id'] )				? $ices[$x]['id'] : '');
			$code = 			$mysqli->real_escape_string(isset( $ices[$x]['code'] )			? $ices[$x]['code'] : '');
			$label = 			$mysqli->real_escape_string(isset( $ices[$x]['label'] ) 		? $ices[$x]['label'] : '');
			$label_en = 	$mysqli->real_escape_string(isset( $ices[$x]['label_en'] ) 	? $ices[$x]['label_en'] : '');
			$label_fr = 	$mysqli->real_escape_string(isset( $ices[$x]['label_fr'] ) 	? $ices[$x]['label_fr'] : '');

			if ($mysqli->real_escape_string(isset($ices[$x]['status'])) and $ices[$x]['status'] == 'New') {
				$query = "INSERT INTO cpa_bills_ices (id, billid, code, label)
									VALUES (null, '$billid', '$code', create_systemText('$label_en', '$label_fr'))";
				if( $mysqli->query( $query ) ){
					$data['inserted']++;
				} else {
					throw new Exception( $mysqli->sqlstate.' - '. $mysqli->error );
				}
			}

			if ($mysqli->real_escape_string(isset($ices[$x]['status'])) and $ices[$x]['status'] == 'Modified') {
				$query = "update cpa_bills_ices
									set code = '$code'
									where id = '$id'";
				if( $mysqli->query( $query ) ){
					$query = "UPDATE cpa_text set text = '$label_fr' where id = $label and language = 'fr-ca'";
					if( $mysqli->query( $query ) ){
						$data['success'] = true;
						$query = "UPDATE cpa_text set text = '$label_en' where id = $label and language = 'en-ca'";
						if( $mysqli->query( $query ) ){
							$data['updated']++;
						} else {
							throw new Exception( $mysqli->sqlstate.' - '. $mysqli->error );
						}
					} else {
						throw new Exception( $mysqli->sqlstate.' - '. $mysqli->error );
					}
				} else {
					throw new Exception( $mysqli->sqlstate.' - '. $mysqli->error );
				}
			}

			if ($mysqli->real_escape_string(isset($ices[$x]['status'])) and $ices[$x]['status'] == 'Deleted') {
				$query = "DELETE FROM cpa_text WHERE id = '$label'";
				if($mysqli->query( $query )){
					$query = "DELETE FROM cpa_bills_ices WHERE id = '$id'";
					if( $mysqli->query( $query ) ){
						$data['deleted']++;
					} else {
						throw new Exception( $mysqli->sqlstate.' - '. $mysqli->error );
					}
				} else {
					throw new Exception( $mysqli->sqlstate.' - '. $mysqli->error );
				}
			}
		}
		$data['success'] = true;
		return $data;
	}catch (Exception $e){
		$data = array();
		$data['success'] = false;
		$data['message'] = $e->getMessage();
		return $data;
	}
};

function updateEntireBill($mysqli, $bill){
	try{
		$data = array();
		$id = $mysqli->real_escape_string(isset( $bill['id'] ) ? $bill['id'] : '');

		$data['successbill'] = update_bill($mysqli, $bill);
		if ($mysqli->real_escape_string(isset( $bill['ices']))) {
			$data['successices'] = updateEntireIces($mysqli, $id, $bill['ices']);
		}
		$mysqli->close();

		$data['success'] = true;
		$data['message'] = 'Bill updated successfully.';
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


function invalidRequest(){
	$data = array();
	$data['success'] = false;
	$data['message'] = "Invalid request.";
	echo json_encode($data);
	exit;
};

?>
