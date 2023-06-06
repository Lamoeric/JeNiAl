<?php
/*
Author : Eric Lamoureux
*/

/**
 * This function gets the contacts of one member from database
 */
function getMemberContacts($mysqli, $memberid, $language){
	try{
		if(empty($memberid)) throw new Exception( "Invalid Member ID." );
		$query = "SELECT cc.*, cmc.id cmcid, cmc.contacttype, cmc.incaseofemergency, getCodeDescription('contacttypes', cmc.contacttype, '$language') relationname
							FROM cpa_contacts cc 
							JOIN cpa_members_contacts cmc ON cmc.contactid = cc.id
							WHERE cmc.memberid = '$memberid'";
		$result = $mysqli->query( $query );
		$data = array();
		$data['data'] = array();
		while ($row = $result->fetch_assoc()) {
			$data['data'][] = $row;
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

/**
 * This function gets list of all contacts from database
 */
function getAllContacts($mysqli){
	try{
		$query = "SELECT id, firstname, lastname FROM cpa_contacts order by lastname, firstname";
		$result = $mysqli->query( $query );
		$data = array();
		while ($row = $result->fetch_assoc()) {
			$data['data'][] = $row;
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

/**
 * This function gets the details of one contact from database
 */
function getContactDetails($mysqli, $id = ''){
	try{
		if(empty($id)) throw new Exception( "Invalid Contact." );
		$query = "SELECT * FROM cpa_contacts WHERE id = '$id'";
		$result = $mysqli->query( $query );
		$data = array();
		while ($row = $result->fetch_assoc()) {
			$data['data'][] = $row;
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

/**
 * This function will handle insert/update/delete of a contact in DB
 * @throws Exception
 */
function updateEntireContacts($mysqli, $memberid, $contacts) {
	$data = array();
	$data['insert'] = 0;
	$data['update'] = 0;
	for($x = 0; $x < count($contacts); $x++) {
		$id = 								$mysqli->real_escape_string(isset($contacts[$x]['id']) 								? $contacts[$x]['id'] : '');
		$cmcid = 							$mysqli->real_escape_string(isset($contacts[$x]['cmcid']) 						? $contacts[$x]['cmcid'] : '');
		$firstname = 					$mysqli->real_escape_string(isset($contacts[$x]['firstname']) 				? $contacts[$x]['firstname'] : '');
		$lastname = 					$mysqli->real_escape_string(isset($contacts[$x]['lastname']) 					? $contacts[$x]['lastname'] : '');
		$homephone = 					$mysqli->real_escape_string(isset($contacts[$x]['homephone']) 				? $contacts[$x]['homephone'] : '');
		$cellphone = 					$mysqli->real_escape_string(isset($contacts[$x]['cellphone']) 				? $contacts[$x]['cellphone'] : '');
		$officephone = 				$mysqli->real_escape_string(isset($contacts[$x]['officephone']) 			? $contacts[$x]['officephone'] : '');
		$officeext = 					$mysqli->real_escape_string(isset($contacts[$x]['officeext']) 				? $contacts[$x]['officeext'] : '');
		$contacttype = 				$mysqli->real_escape_string(isset($contacts[$x]['contacttype']) 			? $contacts[$x]['contacttype'] : '');
		$incaseofemergency = 	$mysqli->real_escape_string(isset($contacts[$x]['incaseofemergency'])	? $contacts[$x]['incaseofemergency'] : 0);
		$email = 							$mysqli->real_escape_string(isset($contacts[$x]['email'])							? $contacts[$x]['email'] : '');
		$status = 						$mysqli->real_escape_string(isset($contacts[$x]['status'])						? $contacts[$x]['status'] : '');

		// We now need to check if contact is new or if only the relation between contact and member is new.
		// If contact is new, contact must be inserted else update it
		if ($id == '') {
			$query = "INSERT into cpa_contacts 
								(firstname, lastname, homephone, cellphone, officephone, officeext, email) 
								VALUES ('$firstname', '$lastname', '$homephone', '$cellphone', '$officephone', '$officeext', '$email')";
			if ($mysqli->query($query)) {
				$id = (int) $mysqli->insert_id;
			} else {
				throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
			}
		} else {
			$query = "update cpa_contacts 
								set firstname = '$firstname', lastname = '$lastname', homephone = '$homephone', cellphone = '$cellphone', 
								officephone = '$officephone', officeext = '$officeext', email = '$email' 
								where id = '$id'";
			if ($mysqli->query($query)) {
			} else {
				throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
			}
		}

		if ($status == 'Modified' or $status == 'New') {
			if ($cmcid == '') {
				$query = "INSERT into cpa_members_contacts 
									(memberid, contactid, contacttype, incaseofemergency) 
									VALUES ($memberid, $id, '$contacttype', '$incaseofemergency')";
				if ($mysqli->query($query)) {
					$data['insert']++;
					$contacts[$x]['cmcid'] = (int) $mysqli->insert_id;
				} else {
					throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
				}
			} else {
				$query = "update cpa_members_contacts 
									set contacttype = '$contacttype', incaseofemergency = '$incaseofemergency'
									where id = $cmcid";
				if ($mysqli->query($query)) {
					$data['update']++;
				} else {
					throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
				}
			}
		}

		// We should ony delete the relation and keep the contact alive
		if ($mysqli->real_escape_string(isset($contacts[$x]['status'])) and $contacts[$x]['status'] == 'Deleted') {
			$query = "DELETE FROM cpa_members_contacts WHERE id = $cmcid";
			if ($mysqli->query($query)) {
			} else {
				throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
			}
		}
	}
	$data['success'] = true;
	return $data;
};

?>