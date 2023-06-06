<?php
/*
Author : Eric Lamoureux
*/
require_once('../../../private/'. $_SERVER['HTTP_HOST'].'/include/config.php');
require_once('../../include/nocache.php');

if (isset($_POST['type']) && !empty(isset($_POST['type']))) {
	$type = $_POST['type'];

	switch ($type) {
		// case "insert_page":
		// 	insert_page($mysqli);
		// 	break;
		case "updateEntirePage":
			updateEntirePage($mysqli, $_POST['page']);
			break;
		// case "delete_page":
		// 	delete_page($mysqli, $_POST['page']);
		// 	break;
		case "getAllPages":
			getAllPages($mysqli, $_POST['language']);
			break;
		case "getPageDetails":
			getPageDetails($mysqli, $_POST['name'], $_POST['language']);
			break;
		default:
			invalidRequest();
	}
} else {
	invalidRequest();
};

/**
 * This function will handle page add, update functionality
 * @throws Exception
 */
// function insert_page($mysqli) {
// 	try {
// 		$data = array();
// 		$id =					$mysqli->real_escape_string(isset($_POST['page']['id	=']) 				? $_POST['page']['id'] : '');
// 		$name =				$mysqli->real_escape_string(isset($_POST['page']['name']) 				? $_POST['page']['name'] : '');
// 		$label =			$mysqli->real_escape_string(isset($_POST['page']['label']) 			? $_POST['page']['label'] : '');
// 		$label_fr =		$mysqli->real_escape_string(isset($_POST['page']['label_fr']) 		? $_POST['page']['label_fr'] : '');
// 		$label_en =		$mysqli->real_escape_string(isset($_POST['page']['label_en']) 		? $_POST['page']['label_en'] : '');
// 		$address =		$mysqli->real_escape_string(isset($_POST['page']['address']) 		? $_POST['page']['address'] : '');
// 		$active =			$mysqli->real_escape_string(isset($_POST['page']['active']) 			? (int)$_POST['page']['active'] : 0);
//
// 		if ($name == '') {
// 			throw new Exception("Required fields missing, Please enter and submit");
// 		}
//
// 		$query = "INSERT INTO cpa_ws_pages (name, label, address, active)
// 							VALUES ('$name', create_wsText('$label_en', '$label_fr'), '$address', $active)";
// 		if ($mysqli->query($query)) {
// 			$data['success'] = true;
// 			if (empty($id))$data['id'] = (int) $mysqli->insert_id;
// 		} else {
// 			throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
// 		}
// 		$mysqli->close();
// 		echo json_encode($data);
// 		exit;
// 	} catch(Exception $e) {
// 		$data = array();
// 		$data['success'] = false;
// 		$data['message'] = $e->getMessage();
// 		echo json_encode($data);
// 		exit;
// 	}
// };

/**
 * This function will handle page add, update functionality
 * @throws Exception
 */
function update_page($mysqli, $page) {
	$data = array();
	$name =							$mysqli->real_escape_string(isset($page['name']) 							? $page['name'] : '');
	$navbarlabel =			$mysqli->real_escape_string(isset($page['navbarlabel']) 			? (int)$page['navbarlabel'] : 0);
	$pageindex =				$mysqli->real_escape_string(isset($page['pageindex']) 				? (int)$page['pageindex'] : 0);
	$navbarvisible =		$mysqli->real_escape_string(isset($page['navbarvisible']) 		? (int)$page['navbarvisible'] : 0);
	$navbarusesection =	$mysqli->real_escape_string(isset($page['navbarusesection'])	? (int)$page['navbarusesection'] : 0);
	$navbarlabel_fr =		$mysqli->real_escape_string(isset($page['navbarlabel_fr']) 		? $page['navbarlabel_fr'] : '');
	$navbarlabel_en =		$mysqli->real_escape_string(isset($page['navbarlabel_en']) 		? $page['navbarlabel_en'] : '');

	if ($name == '') {
		throw new Exception("Required fields missing, Please enter and submit");
	}

	$query = "UPDATE cpa_ws_pages SET pageindex = $pageindex, navbarvisible = $navbarvisible, navbarusesection = $navbarusesection WHERE name = '$name'";
	if ($mysqli->query($query)) {
		$query = "UPDATE cpa_ws_text set text = '$navbarlabel_fr' where id = $navbarlabel and language = 'fr-ca'";
		if ($mysqli->query($query)) {
			$query = "UPDATE cpa_ws_text set text = '$navbarlabel_en' where id = $navbarlabel and language = 'en-ca'";
			if ($mysqli->query($query)) {
				$data['success'] = true;
				$data['message'] = 'Page updated successfully.';
			} else {
				throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
			}
		} else {
			throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
		}
	} else {
		throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
	}
	return $data;
	exit;
};

/**
 * This function will handle user deletion
 * @throws Exception
 */
// function delete_page($mysqli, $page) {
// 	try {
// 		$id = 		$mysqli->real_escape_string(isset($page['id']) 		? (int)$page['id'] : '');
// 		$label = 	$mysqli->real_escape_string(isset($page['label']) 	? (int)$page['label'] : '');
//
// 		if (empty($id)) throw new Exception("Invalid page id.");
// 		$query = "DELETE FROM cpa_ws_pages WHERE id = $id";
// 		if ($mysqli->query($query)) {
// 			$query = "DELETE FROM cpa_ws_text WHERE id = $label";
// 			if ($mysqli->query($query)) {
// 				$data['success'] = true;
// 				$data['message'] = 'Page deleted successfully.';
// 				echo json_encode($data);
// 				exit;
// 			} else {
// 				throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
// 			}
// 		} else {
// 			throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
// 		}
// 	} catch(Exception $e) {
// 		$data = array();
// 		$data['success'] = false;
// 		$data['message'] = $e->getMessage();
// 		echo json_encode($data);
// 		exit;
// 	}
// };

/**
 * This function gets list of all pages from database
 */
function getAllPages($mysqli, $language) {
	try {
		$query = "SELECT name, getWSTextLabel(navbarlabel, '$language') navbarlabeltext FROM cpa_ws_pages ORDER BY pageindex";
		$result = $mysqli->query($query);
		$data = array();
		while ($row = $result->fetch_assoc()) {
			$data['data'][] = $row;
		}
		$data['success'] = true;
		echo json_encode($data);exit;
	} catch(Exception $e) {
		$data = array();
		$data['success'] = false;
		$data['message'] = $e->getMessage();
		echo json_encode($data);
		exit;
	}
};

/**
 * This function gets the details of all sections for an page from database
 */
function getPageSections($mysqli, $pagename = '') {
	try {
		$query = "SELECT cwsp.*
							FROM cpa_ws_pages_sections cwsp
							WHERE cwsp.pagename = '$pagename'
							ORDER BY pagesectionindex";
		$result = $mysqli->query($query);
		$data = array();
		$data['data'] = array();
		while ($row = $result->fetch_assoc()) {
			$row['pagesectionindex'] = (int)$row['pagesectionindex'];
			$data['data'][] = $row;
		}
		$data['success'] = true;
		return $data;
		exit;
	} catch(Exception $e) {
		$data = array();
		$data['success'] = false;
		$data['message'] = $e->getMessage();
		return $data;
		exit;
	}
};

/**
 * This function gets the details of one page from database
 */
function getPageDetails($mysqli, $name, $language) {
	try {
		$query = "SELECT *, getWSTextLabel(cws.navbarlabel, 'fr-ca') navbarlabel_fr, getWSTextLabel(cws.navbarlabel, 'en-ca') navbarlabel_en,
											getWSTextLabel(navbarlabel, '$language') navbarlabeltext, getWSTextLabel(label, '$language') labeltext
							FROM cpa_ws_pages cws
							WHERE cws.name = '$name'";
		$result = $mysqli->query($query);
		$data = array();
		while ($row = $result->fetch_assoc()) {
			$row['pageindex'] = (int)$row['pageindex'];
			$row['canbeinmenubar'] = (int)$row['canbeinmenubar'];
			$row['sections'] = getPageSections($mysqli, $name)['data'];
			$data['data'][] = $row;
		}
		$data['success'] = true;
		echo json_encode($data);
		exit;
	} catch(Exception $e) {
		$data = array();
		$data['success'] = false;
		$data['message'] = $e->getMessage();
		echo json_encode($data);
		exit;
	}
};

/**
 * This function will handle insert/update/delete of a pagesection in DB
 * @throws Exception
 */
function updateEntireSections($mysqli, $pagename, $sections) {
	$data = array();
	for ($x = 0; $sections && $x < count($sections); $x++) {
		$sectionname = 			$mysqli->real_escape_string(isset($sections[$x]['sectionname'])				? $sections[$x]['sectionname'] : '');
		$pagesectionindex = $mysqli->real_escape_string(isset($sections[$x]['pagesectionindex'])	? (int)$sections[$x]['pagesectionindex'] : 0);
		$visible = 					$mysqli->real_escape_string(isset($sections[$x]['visible'])						? (int)$sections[$x]['visible'] : 0);
		$visibleinnavbar = 	$mysqli->real_escape_string(isset($sections[$x]['visibleinnavbar']) 	? (int)$sections[$x]['visibleinnavbar'] : 0);

		if ($mysqli->real_escape_string(isset($sections[$x]['status'])) and $sections[$x]['status'] == 'New') {
			$query = "INSERT INTO cpa_ws_pages_sections (pagename, sectionname, pagesectionindex, visible, visibleinnavbar)
								VALUES ('$pagename', '$sectionname' $pagesectionindex, $visible, $visibleinnavbar)";
			if (!$mysqli->query($query)) {
				throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
			}
		}

		if ($mysqli->real_escape_string(isset($sections[$x]['status'])) and $sections[$x]['status'] == 'Modified') {
			$query = "UPDATE cpa_ws_pages_sections SET pagesectionindex = $pagesectionindex, visible = $visible, visibleinnavbar = $visibleinnavbar	WHERE pagename = '$pagename' and sectionname = '$sectionname'";
			if (!$mysqli->query($query)) {
				throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
			}
		}

		if ($mysqli->real_escape_string(isset($sections[$x]['status'])) and $sections[$x]['status'] == 'Deleted') {
			$query = "DELETE FROM cpa_ws_pages_sections WHERE pagename = '$pagename' and sectionname = '$sectionname'";
			if (!$mysqli->query($query)) {
				throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
			}
		}
	}
	$data['success'] = true;
	return $data;
};

function updateEntirePage($mysqli, $page) {
	try {
		$data = array();
		$name = $mysqli->real_escape_string(isset($page['name']) ? $page['name'] : '');

		$data['successpage'] = update_page($mysqli, $page);
		if ($mysqli->real_escape_string(isset($page['sections']))) {
			$data['successsections'] = updateEntireSections($mysqli, $name, $page['sections']);
		}
		$mysqli->close();

		$data['success'] = true;
		$data['message'] = 'Page updated successfully.';
		echo json_encode($data);
		exit;
	} catch(Exception $e) {
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
