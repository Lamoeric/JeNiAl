<?php
/*
Author : Eric Lamoureux
*/
require_once('../../../private/'. $_SERVER['HTTP_HOST'].'/include/config.php');
require_once('../../include/nocache.php');

if (isset($_POST['type']) && !empty(isset($_POST['type']))) {
	$type = $_POST['type'];

	switch ($type) {
		// case "insert_section":
		// 	insert_section($mysqli);
		// 	break;
		case "updateEntireSection":
			updateEntireSection($mysqli, $_POST['section']);
			break;
		// case "delete_section":
		// 	delete_section($mysqli, $_POST['section']);
		// 	break;
		case "getAllSections":
			getAllSections($mysqli, $_POST['language']);
			break;
		case "getSectionDetails":
			getSectionDetails($mysqli, $_POST['name'], $_POST['language']);
			break;
		default:
			invalidRequest();
	}
} else {
	invalidRequest();
};

/**
 * This function will handle section add, update functionality
 * @throws Exception
 */
// function insert_section($mysqli) {
// 	try {
// 		$data = array();
// 		$id =					$mysqli->real_escape_string(isset($_POST['section']['id	=']) 				? $_POST['section']['id'] : '');
// 		$name =				$mysqli->real_escape_string(isset($_POST['section']['name']) 				? $_POST['section']['name'] : '');
// 		$label =			$mysqli->real_escape_string(isset($_POST['section']['label']) 			? $_POST['section']['label'] : '');
// 		$label_fr =		$mysqli->real_escape_string(isset($_POST['section']['label_fr']) 		? $_POST['section']['label_fr'] : '');
// 		$label_en =		$mysqli->real_escape_string(isset($_POST['section']['label_en']) 		? $_POST['section']['label_en'] : '');
// 		$address =		$mysqli->real_escape_string(isset($_POST['section']['address']) 		? $_POST['section']['address'] : '');
// 		$active =			$mysqli->real_escape_string(isset($_POST['section']['active']) 			? (int)$_POST['section']['active'] : 0);
//
// 		if ($name == '') {
// 			throw new Exception("Required fields missing, Please enter and submit");
// 		}
//
// 		$query = "INSERT INTO cpa_ws_sections (name, label, address, active)
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
 * This function will handle section add, update functionality
 * @throws Exception
 */
function update_section($mysqli, $section) {
	$data = array();
	$name =						$mysqli->real_escape_string(isset($section['name']) 						? $section['name'] : '');
	$navbarlabel =		$mysqli->real_escape_string(isset($section['navbarlabel']) 			? (int)$section['navbarlabel'] : 0);
	$title =					$mysqli->real_escape_string(isset($section['title']) 						? (int)$section['title'] : 0);
	$subtitle =				$mysqli->real_escape_string(isset($section['subtitle']) 				? (int)$section['subtitle'] : 0);
	$navbarlabel_fr =	$mysqli->real_escape_string(isset($section['navbarlabel_fr']) 	? $section['navbarlabel_fr'] : '');
	$navbarlabel_en =	$mysqli->real_escape_string(isset($section['navbarlabel_en']) 	? $section['navbarlabel_en'] : '');
	$title_fr =				$mysqli->real_escape_string(isset($section['title_fr']) 				? $section['title_fr'] : '');
	$title_en =				$mysqli->real_escape_string(isset($section['title_en']) 				? $section['title_en'] : '');
	$subtitle_fr =		$mysqli->real_escape_string(isset($section['subtitle_fr']) 			? $section['subtitle_fr'] : '');
	$subtitle_en =		$mysqli->real_escape_string(isset($section['subtitle_en']) 			? $section['subtitle_en'] : '');
	$imagefilename =	$mysqli->real_escape_string(isset($section['imagefilename']) 		? $section['imagefilename'] : '');

	if ($name == '') {
		throw new Exception("Required fields missing, Please enter and submit");
	}

	$query = "UPDATE cpa_ws_sections SET imagefilename = '$imagefilename' WHERE name = '$name'";
	if ($mysqli->query($query)) {
		$query = "UPDATE cpa_ws_text set text = '$navbarlabel_fr' where id = $navbarlabel and language = 'fr-ca'";
		if ($mysqli->query($query)) {
			$query = "UPDATE cpa_ws_text set text = '$navbarlabel_en' where id = $navbarlabel and language = 'en-ca'";
			if ($mysqli->query($query)) {
				$query = "UPDATE cpa_ws_text set text = '$title_fr' where id = $title and language = 'fr-ca'";
				if ($mysqli->query($query)) {
					$query = "UPDATE cpa_ws_text set text = '$title_en' where id = $title and language = 'en-ca'";
					if ($mysqli->query($query)) {
						$query = "UPDATE cpa_ws_text set text = '$subtitle_fr' where id = $subtitle and language = 'fr-ca'";
						if ($mysqli->query($query)) {
							$query = "UPDATE cpa_ws_text set text = '$subtitle_en' where id = $subtitle and language = 'en-ca'";
							if ($mysqli->query($query)) {
								$data['success'] = true;
								$data['message'] = 'Section updated successfully.';
							} else {
								throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
							}
						} else {
							throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
						}
					} else {
						throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
					}
				} else {
					throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
				}
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
// function delete_section($mysqli, $section) {
// 	try {
// 		$id = 		$mysqli->real_escape_string(isset($section['id']) 		? (int)$section['id'] : '');
// 		$label = 	$mysqli->real_escape_string(isset($section['label']) 	? (int)$section['label'] : '');
//
// 		if (empty($id)) throw new Exception("Invalid section id.");
// 		$query = "DELETE FROM cpa_ws_sections WHERE id = $id";
// 		if ($mysqli->query($query)) {
// 			$query = "DELETE FROM cpa_ws_text WHERE id = $label";
// 			if ($mysqli->query($query)) {
// 				$data['success'] = true;
// 				$data['message'] = 'Section deleted successfully.';
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
 * This function gets list of all sections from database
 */
function getAllSections($mysqli, $language) {
	try {
		$query = "SELECT name, getWSTextLabel(navbarlabel, '$language') navbarlabeltext FROM cpa_ws_sections ORDER BY name";
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
 * This function gets the details of all paragraphs for an section from database
 */
function getSectionParagraphs($mysqli, $sectionname = '') {
	try {
		$query = "SELECT cwsp.*, getWSTextLabel(cwsp.paragraphtext, 'fr-ca') paragraphtext_fr, getWSTextLabel(cwsp.paragraphtext, 'en-ca') paragraphtext_en,
										 getWSTextLabel(cwsp.title, 'fr-ca') title_fr, getWSTextLabel(cwsp.title, 'en-ca') title_en,
										 getWSTextLabel(cwsp.subtitle, 'fr-ca') subtitle_fr, getWSTextLabel(cwsp.subtitle, 'en-ca') subtitle_en
							FROM cpa_ws_sections_paragraphs cwsp
							WHERE cwsp.sectionname = '$sectionname'
							-- AND cwsp.publish = 1
							ORDER BY paragraphindex";
		$result = $mysqli->query($query);
		$data = array();
		$data['data'] = array();
		while ($row = $result->fetch_assoc()) {
			$row['paragraphindex'] = (int)$row['paragraphindex'];
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
 * This function gets the details of all links for a section from database
 */
function getSectionLinks($mysqli, $sectionname, $language) {
	try {
		$query = "SELECT cwsl.*, getWSTextLabel(cwsl.label, 'fr-ca') label_fr, getWSTextLabel(cwsl.label, 'en-ca') label_en, getCodeDescription('wslinktypes', cwsl.linktype, '$language') linktypelabel,
										 getCodeDescription('wslinkpositions', cwsl.position, '$language') linkpositionlabel
							FROM cpa_ws_sections_links cwsl
							WHERE cwsl.sectionname = '$sectionname'
							ORDER BY position, linkindex";
		$result = $mysqli->query($query);
		$data = array();
		$data['data'] = array();
		while ($row = $result->fetch_assoc()) {
			$row['linkindex'] = (int)$row['linkindex'];
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
 * This function gets the details of one section from database
 */
function getSectionDetails($mysqli, $name, $language) {
	try {
		$query = "SELECT *, getWSTextLabel(cws.navbarlabel, 'fr-ca') navbarlabel_fr, getWSTextLabel(cws.navbarlabel, 'en-ca') navbarlabel_en,
										 getWSTextLabel(cws.title, 'fr-ca') title_fr, getWSTextLabel(cws.title, 'en-ca') title_en,
										 getWSTextLabel(cws.subtitle, 'fr-ca') subtitle_fr, getWSTextLabel(cws.subtitle, 'en-ca') subtitle_en,
										 getWSTextLabel(cws.navbarlabel, '$language') navbarlabeltext, getWSTextLabel(cws.label, '$language') labeltext
							FROM cpa_ws_sections cws
							WHERE cws.name = '$name'";
		$result = $mysqli->query($query);
		$data = array();
		while ($row = $result->fetch_assoc()) {
			$row['paragraphs'] = getSectionParagraphs($mysqli, $name)['data'];
			$row['links'] = getSectionLinks($mysqli, $name, $language)['data'];
			$data['data'][] = $row;
			$filename = '../../../private/'. $_SERVER['HTTP_HOST'].'/website/images/sections/'.$row['imagefilename'];
			if (isset($row['imagefilename']) && !empty($row['imagefilename']) && file_exists($filename)) {
				$data['imageinfo'] = getimagesize($filename);
			}
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
 * This function will handle insert/update/delete of links in DB
 * @throws Exception
 */
function updateEntireLinks($mysqli, $sectionname, $links) {
	$data = array();
	for ($x = 0; $links && $x < count($links); $x++) {
		$id = 							$mysqli->real_escape_string(isset($links[$x]['id'])								? (int)$links[$x]['id'] : '');
		$linkname = 				$mysqli->real_escape_string(isset($links[$x]['linkname'])					? $links[$x]['linkname'] : '');
		$label = 						$mysqli->real_escape_string(isset($links[$x]['label'])						? (int)$links[$x]['label'] : 0);
		$label_fr = 				$mysqli->real_escape_string(isset($links[$x]['label_fr'])					? $links[$x]['label_fr'] : '');
		$label_en = 				$mysqli->real_escape_string(isset($links[$x]['label_en'])					? $links[$x]['label_en'] : '');
		$linktype = 				$mysqli->real_escape_string(isset($links[$x]['linktype'])					? (int)$links[$x]['linktype'] : 0);
		$linkpage = 				$mysqli->real_escape_string(isset($links[$x]['linkpage'])					? $links[$x]['linkpage'] : '');
		$linksection = 			$mysqli->real_escape_string(isset($links[$x]['linksection'])			? $links[$x]['linksection'] : '');
		$linkdocumentid = 	$mysqli->real_escape_string(isset($links[$x]['linkdocumentid'])		? (int)$links[$x]['linkdocumentid'] : 0);
		$linkexternal = 		$mysqli->real_escape_string(isset($links[$x]['linkexternal'])			? $links[$x]['linkexternal'] : '');
		$position = 				$mysqli->real_escape_string(isset($links[$x]['position'])					? (int)$links[$x]['position'] : 0);
		$linkindex = 				$mysqli->real_escape_string(isset($links[$x]['linkindex'])				? (int)$links[$x]['linkindex'] : 0);

		if ($mysqli->real_escape_string(isset($links[$x]['status'])) and $links[$x]['status'] == 'New') {
			$query = "INSERT INTO cpa_ws_sections_links (id, sectionname, linkname, label, linktype, linkpage, linksection, linkdocumentid, linkexternal, position, linkindex)
								VALUES (null, '$sectionname', '$linkname', create_wsText('$label_en', '$label_fr'), $linktype, '$linkpage', '$linksection', $linkdocumentid, '$linkexternal', $position, $linkindex)";
			if (!$mysqli->query($query)) {
				throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
			}
		}

		if ($mysqli->real_escape_string(isset($links[$x]['status'])) and $links[$x]['status'] == 'Modified') {
			$query = "UPDATE cpa_ws_sections_links SET linkname = '$linkname', linktype = $linktype, linkpage = '$linkpage', linksection = '$linksection', linkdocumentid = $linkdocumentid,
							  linkexternal = '$linkexternal', position = $position, linkindex = $linkindex	WHERE id = $id";
			if ($mysqli->query($query)) {
				$query = "UPDATE cpa_ws_text SET text = '$label_fr' WHERE id = $label AND language = 'fr-ca'";
				if ($mysqli->query($query)) {
					$query = "UPDATE cpa_ws_text SET text = '$label_en' WHERE id = $label AND language = 'en-ca'";
					if ($mysqli->query($query)) {
					} else {
						throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
					}
				} else {
					throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
				}
			} else {
				throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
			}
		}

		if ($mysqli->real_escape_string(isset($links[$x]['status'])) and $links[$x]['status'] == 'Deleted') {
			$query = "DELETE FROM cpa_ws_sections_links WHERE id = $id";
			if ($mysqli->query($query)) {
				$query = "DELETE FROM cpa_ws_text WHERE id = $label";
				if ($mysqli->query($query)) {
				} else {
					throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
				}
			} else {
				throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
			}
		}
	}
	$data['success'] = true;
	return $data;
};

/**
 * This function will handle insert/update/delete of paragraphs in DB
 * @throws Exception
 */
function updateEntireParagraphs($mysqli, $sectionname, $paragraphs) {
	$data = array();
	for ($x = 0; $paragraphs && $x < count($paragraphs); $x++) {
		$id = 							$mysqli->real_escape_string(isset($paragraphs[$x]['id'])								? (int)$paragraphs[$x]['id'] : '');
		$paragraphindex = 	$mysqli->real_escape_string(isset($paragraphs[$x]['paragraphindex'])		? (int)$paragraphs[$x]['paragraphindex'] : 0);
		$publish = 					$mysqli->real_escape_string(isset($paragraphs[$x]['publish'])						? (int)$paragraphs[$x]['publish'] : 0);
		$title = 						$mysqli->real_escape_string(isset($paragraphs[$x]['title']) 						? (int)$paragraphs[$x]['title'] : 0);
		$subtitle = 				$mysqli->real_escape_string(isset($paragraphs[$x]['subtitle']) 					? (int)$paragraphs[$x]['subtitle'] : 0);
		$paragraphtext = 		$mysqli->real_escape_string(isset($paragraphs[$x]['paragraphtext']) 		? (int)$paragraphs[$x]['paragraphtext'] : 0);
		$title_en =	 				$mysqli->real_escape_string(isset($paragraphs[$x]['title_en']) 					? $paragraphs[$x]['title_en'] : '');
		$title_fr = 				$mysqli->real_escape_string(isset($paragraphs[$x]['title_fr']) 					? $paragraphs[$x]['title_fr'] : '');
		$subtitle_en = 			$mysqli->real_escape_string(isset($paragraphs[$x]['subtitle_en']) 			? $paragraphs[$x]['subtitle_en'] : '');
		$subtitle_fr = 			$mysqli->real_escape_string(isset($paragraphs[$x]['subtitle_fr']) 			? $paragraphs[$x]['subtitle_fr'] : '');
		$paragraphtext_en = $mysqli->real_escape_string(isset($paragraphs[$x]['paragraphtext_en'])	? $paragraphs[$x]['paragraphtext_en'] : '');
		$paragraphtext_fr = $mysqli->real_escape_string(isset($paragraphs[$x]['paragraphtext_fr']) 	? $paragraphs[$x]['paragraphtext_fr'] : '');

		if ($mysqli->real_escape_string(isset($paragraphs[$x]['status'])) and $paragraphs[$x]['status'] == 'New') {
			$query = "INSERT INTO cpa_ws_sections_paragraphs (id, sectionname, paragraphindex, publish, title, subtitle, paragraphtext)
								VALUES (null, '$sectionname', $paragraphindex, $publish, create_wsText('$title_en', '$title_fr'), create_wsText('$subtitle_en', '$subtitle_fr'),
												create_wsText('$paragraphtext_en', '$paragraphtext_fr'))";
			if (!$mysqli->query($query)) {
				throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
			}
		}

		if ($mysqli->real_escape_string(isset($paragraphs[$x]['status'])) and $paragraphs[$x]['status'] == 'Modified') {
			$query = "UPDATE cpa_ws_sections_paragraphs SET paragraphindex = $paragraphindex, publish = $publish	WHERE id = $id";
			if ($mysqli->query($query)) {
				$query = "UPDATE cpa_ws_text SET text = '$title_fr' WHERE id = $title AND language = 'fr-ca'";
				if ($mysqli->query($query)) {
					$query = "UPDATE cpa_ws_text SET text = '$title_en' WHERE id = $title AND language = 'en-ca'";
					if ($mysqli->query($query)) {
						$query = "UPDATE cpa_ws_text SET text = '$subtitle_fr' WHERE id = $subtitle AND language = 'fr-ca'";
						if ($mysqli->query($query)) {
							$query = "UPDATE cpa_ws_text SET text = '$subtitle_en' WHERE id = $subtitle AND language = 'en-ca'";
							if ($mysqli->query($query)) {
								$query = "UPDATE cpa_ws_text SET text = '$paragraphtext_fr' WHERE id = $paragraphtext AND language = 'fr-ca'";
								if ($mysqli->query($query)) {
									$query = "UPDATE cpa_ws_text SET text = '$paragraphtext_en' WHERE id = $paragraphtext AND language = 'en-ca'";
									if ($mysqli->query($query)) {
										$data['success'] = true;
									} else {
										throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
									}
								} else {
									throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
								}
							} else {
								throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
							}
						} else {
							throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
						}
					} else {
						throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
					}
				} else {
					throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
				}
			} else {
				throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
			}
		}

		if ($mysqli->real_escape_string(isset($paragraphs[$x]['status'])) and $paragraphs[$x]['status'] == 'Deleted') {
			$query = "DELETE FROM cpa_ws_sections_paragraphs WHERE id = $id";
			if ($mysqli->query($query)) {
				$query = "DELETE FROM cpa_ws_text WHERE id = $title";
				if ($mysqli->query($query)) {
					$query = "DELETE FROM cpa_ws_text WHERE id = $subtitle";
					if ($mysqli->query($query)) {
						$query = "DELETE FROM cpa_ws_text WHERE id = $paragraphtext";
						if ($mysqli->query($query)) {
						} else {
							throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
						}
					} else {
						throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
					}
				} else {
					throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
				}
			} else {
				throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
			}
		}
	}
	$data['success'] = true;
	return $data;
};

function updateEntireSection($mysqli, $section) {
	try {
		$data = array();
		$name = $mysqli->real_escape_string(isset($section['name']) ? $section['name'] : '');

		$data['successsection'] = update_section($mysqli, $section);
		if ($mysqli->real_escape_string(isset($section['paragraphs']))) {
			$data['successparagraphs'] = updateEntireParagraphs($mysqli, $name, $section['paragraphs']);
		}
		if ($mysqli->real_escape_string(isset($section['links']))) {
			$data['successlinks'] = updateEntireLinks($mysqli, $name, $section['links']);
		}
		$mysqli->close();

		$data['success'] = true;
		$data['message'] = 'Section updated successfully.';
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
