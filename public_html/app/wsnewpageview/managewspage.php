<?php
/*
Author : Eric Lamoureux
*/
require_once('../../../private/' . $_SERVER['HTTP_HOST'] . '/include/config.php');
require_once('../../include/nocache.php');
require_once('../../backend/invalidrequest.php');
// require_once('../../backend/removefile.php');
require_once('../../backend/getwssupportedlanguages.php');
// require_once('../../backend/getimagefileinfo.php');
// require_once('../../backend/getimagefileName.php');

if (isset($_POST['type']) && !empty(isset($_POST['type']))) {
	$type = $_POST['type'];

	switch ($type) {
		case "updateEntireSite":
			updateEntireSite($mysqli, json_decode($_POST['pages'], true));
			break;
		case "updateEntirePage":
			updateEntirePage($mysqli, $_POST['page']);
			break;
		case "getAllPages":
			getAllPages($mysqli, $_POST['language']);
			break;
		case "getPageDetails":
			getPageDetails($mysqli, $_POST['name'], $_POST['language']);
			break;
		default:
			invalidRequest($type);
	}
} else {
	invalidRequest();
};

/**
 * This function will handle page add, update functionality
 * @throws Exception
 */
function update_page($mysqli, $page)
{
	$data = array();
	$name =						$mysqli->real_escape_string(isset($page['name'])					? $page['name'] : '');
	$navbarlabel =				$mysqli->real_escape_string(isset($page['navbarlabel'])				? (int)$page['navbarlabel'] : 0);
	$pageindex =				$mysqli->real_escape_string(isset($page['pageindex'])				? (int)$page['pageindex'] : 0);
	$navbarvisible =			$mysqli->real_escape_string(isset($page['navbarvisible'])			? (int)$page['navbarvisible'] : 0);
	$navbarusesection =			$mysqli->real_escape_string(isset($page['navbarusesection'])		? (int)$page['navbarusesection'] : 0);
	$navbarvisiblepreview =		$mysqli->real_escape_string(isset($page['navbarvisiblepreview'])	? (int)$page['navbarvisiblepreview'] : 0);
	$navbarusesectionpreview =	$mysqli->real_escape_string(isset($page['navbarusesectionpreview'])	? (int)$page['navbarusesectionpreview'] : 0);
	$navbarlabel_fr =			$mysqli->real_escape_string(isset($page['navbarlabel_fr'])			? $page['navbarlabel_fr'] : '');
	$navbarlabel_en =			$mysqli->real_escape_string(isset($page['navbarlabel_en'])			? $page['navbarlabel_en'] : '');

	if ($name == '') {
		throw new Exception("Required fields missing, Please enter and submit");
	}

	$query = "	UPDATE cpa_ws_pages 
				SET pageindex = $pageindex, navbarvisible = $navbarvisible, navbarusesection = $navbarusesection, 
					navbarvisiblepreview = $navbarvisiblepreview, navbarusesectionpreview = $navbarusesectionpreview 
				WHERE name = '$name'";
	if ($mysqli->query($query)) {
		$mysqli->query("call update_wsText($navbarlabel, '$navbarlabel_en', '$navbarlabel_fr')");
		$data['success'] = true;
		$data['message'] = 'Page updated successfully.';
	} else {
		throw new Exception($mysqli->sqlstate . ' - ' . $mysqli->error);
	}
	return $data;
	exit;
};

/**
 * This function gets the url of the web site
 */
function getWebSiteUrl($mysqli)
{
	$query = "SELECT cwci.websiteurl FROM cpa_ws_contactinfo cwci";
	$result = $mysqli->query($query);
	$data = array();
	$row = $result->fetch_assoc();
	$data['data'][] = $row;

	return $row['websiteurl'];
	exit;
};


/**
 * This function gets list of all pages from database
 */
function getAllPages($mysqli, $language)
{
	try {
		$query = "	SELECT 	*, getWSTextLabel(cws.navbarlabel, 'fr-ca') navbarlabel_fr, getWSTextLabel(cws.navbarlabel, 'en-ca') navbarlabel_en,
							getWSTextLabel(navbarlabel, '$language') navbarlabeltext, getWSTextLabel(label, '$language') labeltext
					FROM cpa_ws_pages cws
					ORDER BY pageindex";
		$result = $mysqli->query($query);
		$data = array();
		while ($row = $result->fetch_assoc()) {
			$row['canbeinmenubar'] = (int)$row['canbeinmenubar'];
			$row['sections'] = getPageSections($mysqli, $row['name'], $language)['data'];
			$data['data'][] = $row;
		}
		$data['websiteurl'] = getWebSiteUrl($mysqli);
		$data['config'] = getWSSupportedLanguages($mysqli)['data'];
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
 * This function gets the details of all links for a section from database
 */
function getSectionLinks($mysqli, $sectionname, $language)
{
	$query = "	SELECT 	cwsl.*, getWSTextLabel(cwsl.label, 'fr-ca') label_fr, getWSTextLabel(cwsl.label, 'en-ca') label_en, getCodeDescription('wslinktypes', cwsl.linktype, '$language') linktypelabel,
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
};

/**
 * This function gets the details of all paragraphs for an section from database
 */
function getSectionParagraphs($mysqli, $sectionname = '')
{
	$query = "	SELECT 	cwsp.*, getWSTextLabel(cwsp.paragraphtext, 'fr-ca') paragraphtext_fr, getWSTextLabel(cwsp.paragraphtext, 'en-ca') paragraphtext_en,
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
};


/**
 * This function gets the details of one section from database
 */
function getSectionDetails($mysqli, $name, $language)
{
	$query = "	SELECT 	*, getWSTextLabel(cws.navbarlabel, 'fr-ca') navbarlabel_fr, getWSTextLabel(cws.navbarlabel, 'en-ca') navbarlabel_en,
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
		$filename = '../../../private/' . $_SERVER['HTTP_HOST'] . '/website/images/sections/' . $row['imagefilename'];
		if (isset($row['imagefilename']) && !empty($row['imagefilename']) && file_exists($filename)) {
			$row['imageinfo'] = getimagesize($filename);
		}
		$data['data'][] = $row;
	}
	$data['success'] = true;
	return $data;
	exit;
};

/**
 * This function gets the details of all sections for a page from database
 */
function getPageSections($mysqli, $pagename, $language)
{
	try {
		$query = "	SELECT cwps.*, getWSTextLabel(cws.navbarlabel, '$language') navbarlabeltext
					FROM cpa_ws_pages_sections cwps
					JOIN cpa_ws_sections cws ON cws.name = cwps.sectionname
					WHERE cwps.pagename = '$pagename'
					ORDER BY pagesectionindex";
		$result = $mysqli->query($query);
		$data = array();
		$data['data'] = array();
		while ($row = $result->fetch_assoc()) {
			$row['pagesectionindex'] = (int)$row['pagesectionindex'];
			$row['section'] = getSectionDetails($mysqli, $row['sectionname'], $language)['data'][0];
			$data['data'][] = $row;
		}
		$data['success'] = true;
		return $data;
		exit;
	} catch (Exception $e) {
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
function getPageDetails($mysqli, $name, $language)
{
	try {
		$query = "	SELECT 	*, getWSTextLabel(cws.navbarlabel, 'fr-ca') navbarlabel_fr, getWSTextLabel(cws.navbarlabel, 'en-ca') navbarlabel_en,
							getWSTextLabel(navbarlabel, '$language') navbarlabeltext, getWSTextLabel(label, '$language') labeltext
					FROM cpa_ws_pages cws
					WHERE cws.name = '$name'";
		$result = $mysqli->query($query);
		$data = array();
		while ($row = $result->fetch_assoc()) {
			$row['pageindex'] = (int)$row['pageindex'];
			$row['canbeinmenubar'] = (int)$row['canbeinmenubar'];
			$row['sections'] = getPageSections($mysqli, $name, $language)['data'];
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

/**
 * This function will handle insert/update/delete of links in DB
 * @throws Exception
 */
function updateEntireLinks($mysqli, $sectionname, $links)
{
	$data = array();
	for ($x = 0; $links && $x < count($links); $x++) {
		$id =				$mysqli->real_escape_string(isset($links[$x]['id'])				? (int)$links[$x]['id'] : '');
		$linkname =			$mysqli->real_escape_string(isset($links[$x]['linkname'])		? $links[$x]['linkname'] : '');
		$label =			$mysqli->real_escape_string(isset($links[$x]['label'])			? (int)$links[$x]['label'] : 0);
		$label_fr =			$mysqli->real_escape_string(isset($links[$x]['label_fr'])		? $links[$x]['label_fr'] : '');
		$label_en =			$mysqli->real_escape_string(isset($links[$x]['label_en'])		? $links[$x]['label_en'] : '');
		$linktype =			$mysqli->real_escape_string(isset($links[$x]['linktype'])		? (int)$links[$x]['linktype'] : 0);
		$linkpage =			$mysqli->real_escape_string(isset($links[$x]['linkpage'])		? $links[$x]['linkpage'] : '');
		$linksection =		$mysqli->real_escape_string(isset($links[$x]['linksection'])	? $links[$x]['linksection'] : '');
		$linkdocumentid =	$mysqli->real_escape_string(isset($links[$x]['linkdocumentid'])	? (int)$links[$x]['linkdocumentid'] : 0);
		$linkexternal =		$mysqli->real_escape_string(isset($links[$x]['linkexternal'])	? $links[$x]['linkexternal'] : '');
		$position =			$mysqli->real_escape_string(isset($links[$x]['position'])		? (int)$links[$x]['position'] : 0);
		$linkindex =		$mysqli->real_escape_string(isset($links[$x]['linkindex'])		? (int)$links[$x]['linkindex'] : 0);

		if ($mysqli->real_escape_string(isset($links[$x]['status'])) and $links[$x]['status'] == 'New') {
			$query = "	INSERT INTO cpa_ws_sections_links (id, sectionname, linkname, label, linktype, linkpage, linksection, 
															linkdocumentid, linkexternal, position, linkindex)
						VALUES (null, '$sectionname', '$linkname', create_wsText('$label_en', '$label_fr'), $linktype, '$linkpage', '$linksection', 
								$linkdocumentid, '$linkexternal', $position, $linkindex)";
			if (!$mysqli->query($query)) {
				throw new Exception($mysqli->sqlstate . ' - ' . $mysqli->error);
			}
		}

		if ($mysqli->real_escape_string(isset($links[$x]['status'])) and $links[$x]['status'] == 'Modified') {
			$query = "	UPDATE cpa_ws_sections_links 
						SET linkname = '$linkname', linktype = $linktype, linkpage = '$linkpage', linksection = '$linksection', 
							linkdocumentid = $linkdocumentid,
							 linkexternal = '$linkexternal', position = $position, linkindex = $linkindex
						WHERE id = $id";
			if ($mysqli->query($query)) {
				$mysqli->query("call update_wsText($label, '$label_en', '$label_fr')");
			} else {
				throw new Exception($mysqli->sqlstate . ' - ' . $mysqli->error);
			}
		}

		if ($mysqli->real_escape_string(isset($links[$x]['status'])) and $links[$x]['status'] == 'Deleted') {
			$query = "DELETE FROM cpa_ws_sections_links WHERE id = $id";
			if ($mysqli->query($query)) {
				$query = "DELETE FROM cpa_ws_text WHERE id = $label";
				if ($mysqli->query($query)) {
				} else {
					throw new Exception($mysqli->sqlstate . ' - ' . $mysqli->error);
				}
			} else {
				throw new Exception($mysqli->sqlstate . ' - ' . $mysqli->error);
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
function updateEntireParagraphs($mysqli, $sectionname, $paragraphs)
{
	$data = array();
	for ($x = 0; $paragraphs && $x < count($paragraphs); $x++) {
		$id =				$mysqli->real_escape_string(isset($paragraphs[$x]['id'])				? (int)$paragraphs[$x]['id'] : '');
		$paragraphindex = 	$mysqli->real_escape_string(isset($paragraphs[$x]['paragraphindex'])	? (int)$paragraphs[$x]['paragraphindex'] : 0);
		$publish =			$mysqli->real_escape_string(isset($paragraphs[$x]['publish'])			? (int)$paragraphs[$x]['publish'] : 0);
		$visiblepreview = 	$mysqli->real_escape_string(isset($paragraphs[$x]['visiblepreview'])	? (int)$paragraphs[$x]['visiblepreview'] : 0);
		$title =			$mysqli->real_escape_string(isset($paragraphs[$x]['title'])				? (int)$paragraphs[$x]['title'] : 0);
		$subtitle =			$mysqli->real_escape_string(isset($paragraphs[$x]['subtitle'])			? (int)$paragraphs[$x]['subtitle'] : 0);
		$paragraphtext =	$mysqli->real_escape_string(isset($paragraphs[$x]['paragraphtext'])		? (int)$paragraphs[$x]['paragraphtext'] : 0);
		$title_en =			$mysqli->real_escape_string(isset($paragraphs[$x]['title_en'])			? $paragraphs[$x]['title_en'] : '');
		$title_fr =			$mysqli->real_escape_string(isset($paragraphs[$x]['title_fr'])			? $paragraphs[$x]['title_fr'] : '');
		$subtitle_en =		$mysqli->real_escape_string(isset($paragraphs[$x]['subtitle_en'])		? $paragraphs[$x]['subtitle_en'] : '');
		$subtitle_fr =		$mysqli->real_escape_string(isset($paragraphs[$x]['subtitle_fr'])		? $paragraphs[$x]['subtitle_fr'] : '');
		$paragraphtext_en = $mysqli->real_escape_string(isset($paragraphs[$x]['paragraphtext_en'])	? $paragraphs[$x]['paragraphtext_en'] : '');
		$paragraphtext_fr = $mysqli->real_escape_string(isset($paragraphs[$x]['paragraphtext_fr']) 	? $paragraphs[$x]['paragraphtext_fr'] : '');

		if ($mysqli->real_escape_string(isset($paragraphs[$x]['status'])) and $paragraphs[$x]['status'] == 'New') {
			$query = "	INSERT INTO cpa_ws_sections_paragraphs (id, sectionname, paragraphindex, publish, visiblepreview, title, 
																subtitle, paragraphtext)
						VALUES (null, '$sectionname', $paragraphindex, $publish, $visiblepreview, create_wsText('$title_en', '$title_fr'), 
								create_wsText('$subtitle_en', '$subtitle_fr'), create_wsText('$paragraphtext_en', '$paragraphtext_fr'))";
			if (!$mysqli->query($query)) {
				throw new Exception($mysqli->sqlstate . ' - ' . $mysqli->error);
			}
		}

		if ($mysqli->real_escape_string(isset($paragraphs[$x]['status'])) and $paragraphs[$x]['status'] == 'Modified') {
			$query = "UPDATE cpa_ws_sections_paragraphs SET publish = $publish, visiblepreview = $visiblepreview WHERE id = $id";
			if ($mysqli->query($query)) {
				$mysqli->query("call update_wsText($title, '$title_en', '$title_fr')");
				$mysqli->query("call update_wsText($subtitle, '$subtitle_en', '$subtitle_fr')");
				$mysqli->query("call update_wsText($paragraphtext, '$paragraphtext_en', '$paragraphtext_fr')");
				$data['success'] = true;
			} else {
				throw new Exception($mysqli->sqlstate . ' - ' . $mysqli->error);
			}
		}

		if ($mysqli->real_escape_string(isset($paragraphs[$x]['status'])) and $paragraphs[$x]['status'] == 'Deleted') {
			$query = "DELETE FROM cpa_ws_sections_paragraphs WHERE id = $id";
			if ($mysqli->query($query)) {
				$query = "DELETE FROM cpa_ws_text WHERE id in($title, $subtitle, $paragraphtext)";
			} else {
				throw new Exception($mysqli->sqlstate . ' - ' . $mysqli->error);
			}
		}
	}
	// We need to reorder everything
	$realIndex = 0;
	for ($x = 0; $paragraphs && $x < count($paragraphs); $x++) {
		$id = $mysqli->real_escape_string(isset($paragraphs[$x]['id']) ? (int)$paragraphs[$x]['id'] : 0);
		if ($mysqli->real_escape_string(!isset($paragraphs[$x]['status'])) or $paragraphs[$x]['status'] != 'Deleted') {
			$query = "UPDATE cpa_ws_sections_paragraphs SET paragraphindex = $realIndex WHERE id = $id";
			if (!$mysqli->query($query)) {
				throw new Exception($mysqli->sqlstate . ' - ' . $mysqli->error);
			}
			$realIndex = $realIndex + 1;
		}
	}
	$data['success'] = true;
	return $data;
};

/**
 * This function will handle section add, update functionality
 * @throws Exception
 */
function update_section($mysqli, $section)
{
	$data = array();
	$name =				$mysqli->real_escape_string(isset($section['name'])				? $section['name'] : '');
	$navbarlabel =		$mysqli->real_escape_string(isset($section['navbarlabel'])		? (int)$section['navbarlabel'] : 0);
	$title =			$mysqli->real_escape_string(isset($section['title'])			? (int)$section['title'] : 0);
	$subtitle =			$mysqli->real_escape_string(isset($section['subtitle'])			? (int)$section['subtitle'] : 0);
	$navbarlabel_fr =	$mysqli->real_escape_string(isset($section['navbarlabel_fr'])	? $section['navbarlabel_fr'] : '');
	$navbarlabel_en =	$mysqli->real_escape_string(isset($section['navbarlabel_en'])	? $section['navbarlabel_en'] : '');
	$title_fr =			$mysqli->real_escape_string(isset($section['title_fr'])			? $section['title_fr'] : '');
	$title_en =			$mysqli->real_escape_string(isset($section['title_en'])			? $section['title_en'] : '');
	$subtitle_fr =		$mysqli->real_escape_string(isset($section['subtitle_fr'])		? $section['subtitle_fr'] : '');
	$subtitle_en =		$mysqli->real_escape_string(isset($section['subtitle_en'])		? $section['subtitle_en'] : '');
	$imagefilename =	$mysqli->real_escape_string(isset($section['imagefilename'])	? $section['imagefilename'] : '');

	if ($name == '') {
		throw new Exception("Required fields missing, Please enter and submit");
	}

	$query = "UPDATE cpa_ws_sections SET imagefilename = '$imagefilename' WHERE name = '$name'";
	if ($mysqli->query($query)) {
		$mysqli->query("call update_wsText($navbarlabel, '$navbarlabel_en', '$navbarlabel_fr')");
		$mysqli->query("call update_wsText($title, '$title_en', '$title_fr')");
		$mysqli->query("call update_wsText($subtitle, '$subtitle_en', '$subtitle_fr')");
		$data['success'] = true;
		$data['message'] = 'Section updated successfully.';
	} else {
		throw new Exception($mysqli->sqlstate . ' - ' . $mysqli->error);
	}
	return $data;
	exit;
};

/**
 * This function will handle insert/update/delete of a pagesection in DB
 * @throws Exception
 */
function updateEntireSections($mysqli, $pagename, $sections)
{
	$data = array();
	for ($x = 0; $sections && $x < count($sections); $x++) {
		$sectionname =			$mysqli->real_escape_string(isset($sections[$x]['sectionname'])				? $sections[$x]['sectionname'] : '');
		$visible =				$mysqli->real_escape_string(isset($sections[$x]['visible'])					? (int)$sections[$x]['visible'] : 0);
		$visibleinnavbar =		$mysqli->real_escape_string(isset($sections[$x]['visibleinnavbar'])			? (int)$sections[$x]['visibleinnavbar'] : 0);
		$visiblepreview =		$mysqli->real_escape_string(isset($sections[$x]['visiblepreview'])			? (int)$sections[$x]['visiblepreview'] : 0);
		$visiblenavbarpreview = $mysqli->real_escape_string(isset($sections[$x]['visiblenavbarpreview'])	? (int)$sections[$x]['visiblenavbarpreview'] : 0);

		if ($mysqli->real_escape_string(isset($sections[$x]['status'])) and $sections[$x]['status'] == 'Modified') {
			$query = "UPDATE cpa_ws_pages_sections SET visible = $visible, visibleinnavbar = $visibleinnavbar, visiblepreview = $visiblepreview, visiblenavbarpreview = $visiblenavbarpreview	WHERE pagename = '$pagename' and sectionname = '$sectionname'";
			if (!$mysqli->query($query)) {
				throw new Exception($mysqli->sqlstate . ' - ' . $mysqli->error);
			}
		}

		// Update related section
		//		$section = $mysqli->real_escape_string(isset($sections[$x]['section']) ? $sections[$x]['section'] : null);
		$section = $sections[$x]['section'];
		if ($mysqli->real_escape_string(isset($section))) {
			$name = $mysqli->real_escape_string(isset($section['name']) ? $section['name'] : '');
			$data['successsection'] = update_section($mysqli, $section);
			if ($mysqli->real_escape_string(isset($section['paragraphs']))) {
				$data['successparagraphs'] = updateEntireParagraphs($mysqli, $name, $section['paragraphs']);
			}
			if ($mysqli->real_escape_string(isset($section['links']))) {
				$data['successlinks'] = updateEntireLinks($mysqli, $name, $section['links']);
			}
		}
	}

	// We need to reorder all sections
	$realIndex = 10;
	for ($x = 0; $sections && $x < count($sections); $x++) {
		$sectionname = $mysqli->real_escape_string(isset($sections[$x]['sectionname']) ? $sections[$x]['sectionname'] : '');
		if ($mysqli->real_escape_string(!isset($sections[$x]['status'])) or $sections[$x]['status'] != 'Deleted') {
			$query = "UPDATE cpa_ws_pages_sections SET pagesectionindex = $realIndex WHERE pagename = '$pagename' and sectionname = '$sectionname'";
			if (!$mysqli->query($query)) {
				throw new Exception($mysqli->sqlstate . ' - ' . $mysqli->error);
			}
			$realIndex = $realIndex + 10;
		}
	}

	$data['success'] = true;
	return $data;
};

function updateEntireSite($mysqli, $pages)
{
	try {
		$data = array();
		for ($x = 0; $pages && $x < count($pages); $x++) {
			$page = $pages[$x];
			$name = $mysqli->real_escape_string(isset($page['name']) ? $page['name'] : '');

			$data['successpage'] = update_page($mysqli, $page);
			if ($mysqli->real_escape_string(isset($page['sections']))) {
				$data['successsections'] = updateEntireSections($mysqli, $name, $page['sections']);
			}
		}

		// We need to reorder all pages
		$realIndex = 10;
		for ($x = 0; $pages && $x < count($pages); $x++) {
			$name = $mysqli->real_escape_string(isset($pages[$x]['name']) ? $pages[$x]['name'] : '');
			if ($mysqli->real_escape_string(!isset($pages[$x]['status'])) or $pages[$x]['status'] != 'Deleted') {
				$query = "UPDATE cpa_ws_pages SET pageindex = $realIndex WHERE name = '$name'";
				if (!$mysqli->query($query)) {
					throw new Exception($mysqli->sqlstate . ' - ' . $mysqli->error);
				}
				$realIndex = $realIndex + 10;
			}
		}

		$mysqli->close();
		$data['success'] = true;
		$data['message'] = 'Site updated successfully.';
		echo json_encode($data);
		exit;
	} catch (Exception $e) {
		$data = array();
		$data['success'] = false;
		$data['message'] = $e->getMessage();
		echo json_encode($data);
		exit;
	}

	$data['success'] = true;
	return $data;
};

function updateEntirePage($mysqli, $page)
{
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
	} catch (Exception $e) {
		$data = array();
		$data['success'] = false;
		$data['message'] = $e->getMessage();
		echo json_encode($data);
		exit;
	}
};
?>
