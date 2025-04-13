<?php
/*
Author : Eric Lamoureux
*/
require_once('../../../private/' . $_SERVER['HTTP_HOST'] . '/include/config.php');
require_once('../../include/nocache.php');
require_once('../../backend/invalidrequest.php');

if (isset($_POST['type']) && !empty(isset($_POST['type']))) {
	$type = $_POST['type'];

	switch ($type) {
		case "getAuditTrails":
			getAuditTrails($mysqli, $_POST['filters']);
			break;
		default:
			invalidRequest($type);
	}
} else {
	invalidRequest();
};


/**
 * This function gets list of all arenas from database
 */
function getAuditTrails($mysqli, $filters)
{
	try {
		$query = "SELECT * FROM cpa_audit_trail";
		$where = " WHERE 1=1 ";
		$orderby = " ORDER BY id DESC";
		if (!is_null($filters) && isset($filters['userid'])) $where .= " AND userid = '{$filters['userid']}'";
		if (!is_null($filters) && isset($filters['progname'])) $where .= " AND progname = '{$filters['progname']}'";
		if (!is_null($filters) && isset($filters['action'])) $where .= " AND action = '{$filters['action']}'";
		$query .= $where . $orderby;
		$result = $mysqli->query($query);
		$data = array();
		while ($row = $result->fetch_assoc()) {
			$data['data'][] = $row;
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
