<?php
/*
Author : Eric Lamoureux
*/
require_once('../../../private/'. $_SERVER['HTTP_HOST'].'/include/config.php');
require_once('../../include/nocache.php');

if (isset($_POST['type']) && !empty(isset($_POST['type']))) {
	$type = $_POST['type'];

	switch ($type) {
		case "insert_privilege":
			insert_privilege($mysqli);
			break;
		case "updateEntirePrivilege":
			updateEntirePrivilege($mysqli);
			break;
		case "delete_privilege":
			delete_privilege($mysqli, $_POST['privilege']);
			break;
		case "getAllPrivileges":
			getAllPrivileges($mysqli);
			break;
		case "getPrivilegeDetails":
			getPrivilegeDetails($mysqli, $_POST['id']);
			break;
		default:
			invalidRequest();
	}
} else {
	invalidRequest();
};

function updateEntirePrivilege($mysqli) {
	try{
		$data = array();
		$id = $mysqli->real_escape_string(isset($_POST['privilege']['id']) ? $_POST['privilege']['id'] : '');

		update_privilege($mysqli);
		$mysqli->close();

		$data['success'] = true;
		$data['message'] = 'Privilege updated successfully.';
		echo json_encode($data);
		exit;
	}catch (Exception $e) {
		$data = array();
		$data['success'] = false;
		$data['message'] = $e->getMessage();
		echo json_encode($data);
		exit;
	}
};

function insert_privilege($mysqli) {
	echo json_encode(update_privilege($mysqli));
	exit;
}

/**
 * This function will handle privilege update functionality
 * @throws Exception
 */
function update_privilege($mysqli) {
	try{
		$data = array();
		$id = 								$mysqli->real_escape_string(isset($_POST['privilege']['id']) 									? $_POST['privilege']['id'] : '');
		$code = 							$mysqli->real_escape_string(isset($_POST['privilege']['code']) 								? $_POST['privilege']['code'] : '');
		$description = 				$mysqli->real_escape_string(isset($_POST['privilege']['description']) 				? $_POST['privilege']['description'] : '');

		if (empty($id)) {
			$data['insert'] = true;
			$query = "INSERT INTO cpa_privileges(id, code, description)
								VALUES (NULL, '$code', '$description')";
			if ($mysqli->query($query)) {
				$data['success'] = true;
				$id = (int) $mysqli->insert_id;
				$data['id'] = $id;
				$query = "INSERT INTO cpa_roles_privileges(id, roleid, privilegeid)
									VALUES (NULL, (select id from cpa_roles where roleid = concat('$','fulladmin')), $id)";
				if ($mysqli->query($query)) {
				} else {
					throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
				}
			} else {
				throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
			}
		} else {
			$query = "UPDATE cpa_privileges
								SET code = '$code', description = '$description'
								WHERE id = $id";
			if ($mysqli->query($query)) {
			} else {
				throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
			}
		}
		return $data;
	} catch (Exception $e) {
		$data = array();
		$data['success'] = false;
		$data['message'] = $e->getMessage();
		return $data;
	}
};

/**
 * This function will handle user deletion
 * @param string $id
 * @throws Exception
 */
function delete_privilege($mysqli, $privilege) {
	try{
		$id = $mysqli->real_escape_string(isset($privilege['id']) 									? $privilege['id'] : '');

		if (empty($id)) throw new Exception("Invalid Privilege.");
		$query = "DELETE FROM cpa_roles_privileges WHERE privilegeid = $id";
		if ($mysqli->query($query)) {
			$query = "DELETE FROM cpa_privileges WHERE id = $id";
			if ($mysqli->query($query)) {
				$data['success'] = true;
				$data['message'] = 'Privilege deleted successfully.';
				echo json_encode($data);
				exit;
			} else {
				throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
			}
		} else {
			throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
		}
	}catch (Exception $e) {
		$data = array();
		$data['success'] = false;
		$data['message'] = $e->getMessage();
		echo json_encode($data);
		exit;
	}
};

/**
 * This function gets list of all privileges from database
 */
function getAllPrivileges($mysqli) {
	try{
		$query = "SELECT id, code, description FROM cpa_privileges order by id";
		$result = $mysqli->query($query);
		$data = array();
		$data['data'] = array();
		while ($row = $result->fetch_assoc()) {
			$row['id'] = (int) $row['id'];
			$data['data'][] = $row;
		}
		$data['success'] = true;
		echo json_encode($data);exit;
	}catch (Exception $e) {
		$data = array();
		$data['success'] = false;
		$data['message'] = $e->getMessage();
		echo json_encode($data);
		exit;
	}
};

/**
 * This function gets the details of one privilege from database
 */
function getPrivilegeDetails($mysqli, $id = '') {
	try{
		if (empty($id)) throw new Exception("Invalid User.");
		$query = "SELECT * FROM cpa_privileges WHERE id = $id";
		$result = $mysqli->query($query);
		$data = array();
		while ($row = $result->fetch_assoc()) {
			$row['id'] = (int) $row['id'];
			$data['data'][] = $row;
		}
		$data['success'] = true;
		echo json_encode($data);exit;
	}catch (Exception $e) {
		$data = array();
		$data['success'] = false;
		$data['message'] = $e->getMessage();
		echo json_encode($data);
		exit;
	}
};

function invalidRequest() {
	$data = array();
	$data['success'] = false;
	$data['message'] = "Invalid request.";
	echo json_encode($data);
	exit;
};

?>
