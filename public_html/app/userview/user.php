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
		case "insert_user":
			insert_user($mysqli, $_POST['user']);
			break;
		case "updateEntireUser":
			updateEntireUser($mysqli, $_POST['user']);
			break;
		case "delete_user":
			delete_user($mysqli, $_POST['user']);
			break;
		case "getAllUsers":
			getAllUsers($mysqli, $_POST['filter']);
			break;
		case "getUserDetails":
			getUserDetails($mysqli, $_POST['id']);
			break;
		default:
			invalidRequest($type);
	}
} else {
	invalidRequest();
};

/**
 * This function will handle insert/update/delete of a user/roles in DB
 * @throws Exception
 */
function updateEntireRoles($mysqli, $userid, $roles) {
	$data = array();
	for ($x = 0; $x < count($roles); $x++) {
		$id = 		$mysqli->real_escape_string(isset($roles[$x]['id'])	  ? (int)$roles[$x]['id'] : '');
		$roleid = 	$mysqli->real_escape_string(isset($roles[$x]['roleid']) ? (int)$roles[$x]['roleid'] : '');

		if ($mysqli->real_escape_string(isset($roles[$x]['status'])) and $roles[$x]['status'] == 'New') {
			$query = "INSERT INTO cpa_users_roles(id, userid, roleid) VALUES (null, $userid, $roleid)";
			if (!$mysqli->query($query)) {
				throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
			}
		}

		if ($mysqli->real_escape_string(isset($roles[$x]['status'])) and $roles[$x]['status'] == 'Modified') {
			$query = "UPDATE cpa_users_roles set roleid = $roleid where id = $id";
			if (!$mysqli->query($query)) {
				throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
			}
		}

		if ($mysqli->real_escape_string(isset($roles[$x]['status'])) and $roles[$x]['status'] == 'Deleted') {
			$query = "DELETE FROM cpa_users_roles WHERE id = $id";
			if (!$mysqli->query($query)) {
				throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
			}
		}
	}
	$data['success'] = true;
	return $data;
};

/**
 * Updates the user in the DB
 * @param object $user the user to update
 */
function updateEntireUser($mysqli, $user) {
	try {
		$data = array();
		$id = $mysqli->real_escape_string(isset($user['id']) ? $user['id'] : '');

		update_user($mysqli, $user);
		if ($mysqli->real_escape_string(isset($user['roles']))) {
			$data['successroles'] = updateEntireRoles($mysqli, $id, $user['roles']);
		}
		$mysqli->close();

		$data['success'] = true;
		$data['message'] = 'User updated successfully.';
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
 * Inserts the user in the DB
 * @param object $user the user to insert
 */
function insert_user($mysqli, $user) {
	echo json_encode(update_user($mysqli, $user));
	exit;
}

/**
 * This function will handle user update functionality
 * @param object $user the user to insert
 * @throws Exception
 */
function update_user($mysqli, $user) {
	try {
		$data = array();
		$id = 				$mysqli->real_escape_string(isset($user['id']) 					? $user['id'] : '');
		$userid = 			$mysqli->real_escape_string(isset($user['userid']) 				? $user['userid'] : '');
		$fullname = 		$mysqli->real_escape_string(isset($user['fullname']) 			? $user['fullname'] : '');
		$preferedlanguage =	$mysqli->real_escape_string(isset($user['preferedlanguage'])	? $user['preferedlanguage'] : '');
		$password = 		$mysqli->real_escape_string(isset($user['password']) 			? $user['password'] : '');
		$email = 			$mysqli->real_escape_string(isset($user['email']) 				? $user['email'] : '');
		$passwordexpired = 	$mysqli->real_escape_string(isset($user['passwordexpired']) 	? (int) $user['passwordexpired'] : 0);
		$active = 			$mysqli->real_escape_string(isset($user['active']) 				? (int) $user['active'] : 0);
		$contactid = 		$mysqli->real_escape_string(isset($user['contactid']) 			? (int) $user['contactid'] : 0);

		if (empty($id)) {
			$data['insert'] = true;
			$query = "INSERT INTO cpa_users(id, userid, fullname, preferedlanguage, password, email, passwordexpired, active, contactid)
					  VALUES (NULL, '$userid', '$fullname', '$preferedlanguage', '$password', '$email', $passwordexpired, $active, $contactid)";
			if ($mysqli->query($query)) {
				$data['success'] = true;
				$id = (int) $mysqli->insert_id;
				$data['id'] = $id;
			} else {
				throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
			}
		} else {
			$query = "UPDATE cpa_users
					  SET userid = '$userid', fullname = '$fullname', preferedlanguage = '$preferedlanguage', password = '$password', email = '$email', passwordexpired = $passwordexpired, active = $active, contactid = $contactid
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
 * This function will handle user deletion
 * @param object $user
 * @throws Exception
 */
function delete_user($mysqli, $user) {
	try {
		$data = array();
		$id = $mysqli->real_escape_string(isset($user['id']) ? (int)$user['id'] : '');

		if (empty($id)) throw new Exception("Invalid User.");
		$query = "DELETE FROM cpa_users WHERE id = $id";
		if (!$mysqli->query($query)) {
			throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
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

/**
 * This function gets list of all users from database
 */
function getAllUsers($mysqli, $filter) {
	try {
		$data = array();
		$data['data'] = array();
		$whereclause = " WHERE userid not in ('superuser') ";
		$orderclause = " ORDER by userid";
		$firstname = empty($filter['firstname']) ? null : $mysqli->real_escape_string($filter['firstname']);
		$lastname = empty($filter['lastname']) ? null : $mysqli->real_escape_string($filter['lastname']);
		$role = empty($filter['role']) ? null : $mysqli->real_escape_string($filter['role']);
		$onlyexpiredpassword = empty($filter['onlyexpiredpassword']) || $filter['onlyexpiredpassword'] == 0 ? null : 1;
		$nbmonthsinceloggin = empty($filter['nbmonthsinceloggin']) ? null : (int) $mysqli->real_escape_string($filter['nbmonthsinceloggin']);

		$whereclause .= !is_null($nbmonthsinceloggin) ? " AND ((SELECT max(cat.creationdate) FROM cpa_audit_trail cat WHERE cat.userid = cu.userid and cat.action = 'LOGGING') is null OR
    														   (SELECT max(cat.creationdate) FROM cpa_audit_trail cat WHERE cat.userid = cu.userid and cat.action = 'LOGGING') < DATE_SUB(NOW(), INTERVAL $nbmonthsinceloggin MONTH) )" : '';


		$whereclause .= !is_null($firstname) ? " AND cu.fullname LIKE '$firstname%'" : '';
		$whereclause .= !is_null($lastname) ? " AND cu.fullname LIKE '%$lastname'" : '';
		$whereclause .= !is_null($onlyexpiredpassword) ? " AND cu.passwordexpired = 1": '';
		$whereclause .= !is_null($role) ? " AND EXISTS (SELECT cur.id FROM cpa_users_roles cur WHERE cur.userid = cu.id AND cur.roleid = $role)" : '';
		$query = "SELECT id, userid, fullname FROM cpa_users cu " . $whereclause . $orderclause;
		$result = $mysqli->query($query);
		while ($row = $result->fetch_assoc()) {
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

/**
 * This function gets all the roles for a user
 * @param string $userid
 */
function getUserRoles($mysqli, $userid = '') {
	try {
		$query = "SELECT * FROM cpa_users_roles WHERE userid = $userid";
		$result = $mysqli->query($query);
		$data = array();
		$data['data'] = array();
		while ($row = $result->fetch_assoc()) {
			$data['data'][] = $row;
		}
		$data['success'] = true;
		return $data;
		exit;
	}catch (Exception $e) {
		$data = array();
		$data['success'] = false;
		$data['message'] = $e->getMessage();
		return $data;
		exit;
	}
};

/**
 * This function gets the details of one user from database
 * @param string $id
 * @throws Exception
 */
function getUserDetails($mysqli, $id = '') {
	try {
		if (empty($id)) throw new Exception("Invalid User.");
		$query = "SELECT cu.*, concat(cc.firstname, ' ', cc.lastname) contactfullname,
						(SELECT max(cat.creationdate) FROM cpa_audit_trail cat WHERE cat.userid = cu.userid and cat.action = 'LOGGING') lastlogindate
				  FROM cpa_users cu
				  LEFT JOIN cpa_contacts cc ON cc.id = cu.contactid
				  WHERE cu.id = $id";
		$result = $mysqli->query($query);
		$data = array();
		while ($row = $result->fetch_assoc()) {
			$row['roles'] = getUserRoles($mysqli, $id)['data'];
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

?>
