<?php
/*
Author : Eric Lamoureux
*/
require_once('../../../private/'. $_SERVER['HTTP_HOST'].'/include/config.php');
require_once('../../include/nocache.php');
require_once('../../backend/invalidrequest.php'); //

if (isset($_POST['type']) && !empty(isset($_POST['type']))) {
	$type = $_POST['type'];

	switch ($type) {
		case "insert_role":
			insert_role($mysqli, $_POST['role']);
			break;
		case "updateEntireRole":
			updateEntireRole($mysqli, $_POST['role']);
			break;
		case "delete_role":
			delete_role($mysqli, $_POST['role']);
			break;
		case "getAllRoles":
			getAllRoles($mysqli);
			break;
		case "getRoleDetails":
			getRoleDetails($mysqli, $_POST['id']);
			break;
		default:
			invalidRequest($type);
	}
} else {
	invalidRequest();
};

/**
 * This function will handle insert/update/delete of all role/privileges in the DB
 * @param string $roleid	the role id
 * @param array $privileges list of privileges for the role
 * @throws Exception
 */
function updateEntirePrivileges($mysqli, $roleid, $privileges){
	$data = array();
	for ($x = 0; $x < count($privileges); $x++) {
		$id = 			$mysqli->real_escape_string(isset($privileges[$x]['id'])		  ? (int)$privileges[$x]['id'] : '');
		$privilegeid =	$mysqli->real_escape_string(isset($privileges[$x]['privilegeid']) ? (int)$privileges[$x]['privilegeid'] : '');

		if ($mysqli->real_escape_string(isset($privileges[$x]['status'])) and $privileges[$x]['status'] == 'New') {
			$query = "INSERT INTO cpa_roles_privileges(id, roleid, privilegeid) VALUES (null, $roleid, $privilegeid)";
			if (!$mysqli->query($query)){
				throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
			}
		}

		if ($mysqli->real_escape_string(isset($privileges[$x]['status'])) and $privileges[$x]['status'] == 'Modified') {
			$query = "update cpa_roles_privileges set privilegeid = $privilegeid where id = $id";
			if (!$mysqli->query($query)){
				throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
			}
		}

		if ($mysqli->real_escape_string(isset($privileges[$x]['status'])) and $privileges[$x]['status'] == 'Deleted') {
			$query = "DELETE FROM cpa_roles_privileges WHERE id = $id";
			if (!$mysqli->query($query)){
				throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
			}
		}
	}
	$data['success'] = true;
	return $data;
};

/**
 * This function will update the role in the DB
 * @param object $role	the role to update
 */
function updateEntireRole($mysqli, $role) {
	try {
		$data = array();
		$id = $mysqli->real_escape_string(isset($role['id']) ? (int)$role['id'] : '');

		update_role($mysqli, $role);
		if ($mysqli->real_escape_string(isset($role['privileges']))) {
			$data['successprivileges'] = updateEntirePrivileges($mysqli, $id, $role['privileges']);
		}
		$mysqli->close();

		$data['success'] = true;
		$data['message'] = 'Role updated successfully.';
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

/**
 * This function will insert the role in the DB
 * @param object $role	the role to insert
 */
function insert_role($mysqli, $role) {
	echo json_encode(update_role($mysqli, $role));
	exit;
}

/**
 * This function will handle role update functionality
 * @param object $role	the role to update
 * @throws Exception
 */
function update_role($mysqli, $role) {
	try {
		$data = array();
		$id =		$mysqli->real_escape_string(isset($role['id'])			? (int)$role['id'] : '');
		$roleid = 	$mysqli->real_escape_string(isset($role['roleid'])		? $role['roleid'] : '');
		$rolename = $mysqli->real_escape_string(isset($role['rolename'])	? $role['rolename'] : '');

		if (empty($id)) {
			$data['insert'] = true;
			$query = "INSERT INTO cpa_roles(id, roleid, rolename)
					  VALUES (NULL, '$roleid', '$rolename')";
			if ($mysqli->query($query)) {
				$data['success'] = true;
				$id = (int) $mysqli->insert_id;
				$data['id'] = $id;
			} else {
				throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
			}
		} else {
			$query = "UPDATE cpa_roles
					  SET roleid = '$roleid', rolename = '$rolename'
					  WHERE id = $id";
			if (!$mysqli->query($query)) {
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
 * This function will handle role deletion
 * @param object $role
 * @throws Exception
 */
function delete_role($mysqli, $role) {
	try {
		$data = array();
		$id = $mysqli->real_escape_string(isset($role['id']) ? (int)$role['id'] : '');

		if (empty($id)) throw new Exception("Invalid Role.");
		$query = "DELETE FROM cpa_roles WHERE id = $id";
		if ($mysqli->query($query)) {
			$data['success'] = true;
			$data['message'] = 'Role deleted successfully.';
			echo json_encode($data);
			exit;
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
 * This function gets the list of all roles from database
 */
function getAllRoles($mysqli) {
	try {
		$query = "SELECT id, roleid, rolename FROM cpa_roles order by id";
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
 * This function will get all the privileges for a role
 * @param string $roleid	the role id
 */
function getRolePrivileges($mysqli, $roleid = ''){
	try {
		$query = "SELECT * FROM cpa_roles_privileges WHERE roleid = $roleid";
		$result = $mysqli->query($query);
		$data = array();
		$data['data'] = array();
		while ($row = $result->fetch_assoc()) {
			$data['data'][] = $row;
		}
		$data['success'] = true;
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
 * This function gets the details of one role from database
 * @param string $id	the role id
 */
function getRoleDetails($mysqli, $id = '') {
	try {
		if (empty($id)) throw new Exception("Invalid User.");
		$query = "SELECT *, (SELECT count(*) FROM cpa_users_roles cur WHERE cur.roleid = cr.id) as isused
				  FROM cpa_roles cr
				  WHERE id = $id";
		$result = $mysqli->query($query);
		$data = array();
		while ($row = $result->fetch_assoc()) {
			$row['id'] = (int) $row['id'];
			$row['privileges'] = getRolePrivileges($mysqli, $id)['data'];
			$data['data'][] = $row;
		}
		$data['success'] = true;
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

?>
