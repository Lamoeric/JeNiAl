<?php
/*
Author : Eric Lamoureux
*/
require_once('../../../private/' . $_SERVER['HTTP_HOST'] . '/include/config.php');
require_once('../../include/nocache.php');
require_once('../../backend/invalidrequest.php');
require_once('../../backend/removefile.php');
require_once('../../backend/getwssupportedlanguages.php');
require_once('../../backend/getimagefileinfo.php');
require_once('../../backend/getimagefileName.php');

if (isset($_POST['type']) && !empty(isset($_POST['type']))) {
	$type = $_POST['type'];

	switch ($type) {
		case "insert_news":
			insert_news($mysqli, $_POST['news']);
			break;
		case "updateEntireNews":
			updateEntireNews($mysqli, $_POST['news']);
			break;
		case "delete_news":
			delete_news($mysqli, $_POST['news']);
			break;
		case "getAllNewss":
			getAllNewss($mysqli, $_POST['language']);
			break;
		case "getNewsDetails":
			getNewsDetails($mysqli, $_POST['id'], $_POST['language']);
			break;
		default:
			invalidRequest($type);
	}
} else {
	invalidRequest();
};

/**
 * This function will handle news add functionality
 * @throws Exception
 */
function insert_news($mysqli, $news)
{
	try {
		$data = array();
		$name =				$mysqli->real_escape_string(isset($news['name']) 				? $news['name'] : '');
		$title =			$mysqli->real_escape_string(isset($news['title']) 				? (int)$news['title'] : 0);
		$title_fr =			$mysqli->real_escape_string(isset($news['title_fr']) 			? $news['title_fr'] : '');
		$title_en =			$mysqli->real_escape_string(isset($news['title_en']) 			? $news['title_en'] : '');
		$shortversion =		$mysqli->real_escape_string(isset($news['shortversion']) 		? (int)$news['shortversion'] : 0);
		$shortversion_fr =	$mysqli->real_escape_string(isset($news['shortversion_fr']) 	? $news['shortversion_fr'] : '');
		$shortversion_en =	$mysqli->real_escape_string(isset($news['shortversion_en']) 	? $news['shortversion_en'] : '');
		$longversion =		$mysqli->real_escape_string(isset($news['longversion']) 		? (int)$news['longversion'] : 0);
		$longversion_fr =	$mysqli->real_escape_string(isset($news['longversion_frf']) 	? $news['longversion_fr'] : '');
		$longversion_en =	$mysqli->real_escape_string(isset($news['longversion_en']) 		? $news['longversion_en'] : '');
		$imagefilename =	$mysqli->real_escape_string(isset($news['imagefilename']) 		? $news['imagefilename'] : '');
		$publish =			$mysqli->real_escape_string(isset($news['publish']) 			? (int)$news['publish'] : 0);
		$publishdate =		$mysqli->real_escape_string(isset($news['publishdate']) 		? $news['publishdate'] : '');

		$query = "	INSERT INTO cpa_ws_news (name, title, shortversion, longversion, imagefilename, publish, publishdate)
					VALUES ('$name', create_wsText('$name','$name'), create_wsText('',''), create_wsText('',''), '$imagefilename', $publish, curdate())";
		if ($mysqli->query($query)) {
			$data['success'] = true;
			if (empty($id)) $data['id'] = (int) $mysqli->insert_id;
		} else {
			throw new Exception($mysqli->sqlstate . ' - ' . $mysqli->error);
		}
		$mysqli->close();
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
 * This function will handle news update functionality
 * @throws Exception
 */
function update_news($mysqli, $news)
{
	$data = array();
	$id =				$mysqli->real_escape_string(isset($news['id']) 					? (int)$news['id'] : 0);
	$name =				$mysqli->real_escape_string(isset($news['name']) 				? $news['name'] : '');
	$title =			$mysqli->real_escape_string(isset($news['title']) 				? (int)$news['title'] : 0);
	$title_fr =			$mysqli->real_escape_string(isset($news['title_fr']) 			? $news['title_fr'] : '');
	$title_en =			$mysqli->real_escape_string(isset($news['title_en']) 			? $news['title_en'] : '');
	$shortversion =		$mysqli->real_escape_string(isset($news['shortversion']) 		? (int)$news['shortversion'] : 0);
	$shortversion_fr =	$mysqli->real_escape_string(isset($news['shortversion_fr']) 	? $news['shortversion_fr'] : '');
	$shortversion_en =	$mysqli->real_escape_string(isset($news['shortversion_en']) 	? $news['shortversion_en'] : '');
	$longversion =		$mysqli->real_escape_string(isset($news['longversion']) 		? (int)$news['longversion'] : 0);
	$longversion_fr =	$mysqli->real_escape_string(isset($news['longversion_fr']) 		? $news['longversion_fr'] : '');
	$longversion_en =	$mysqli->real_escape_string(isset($news['longversion_en']) 		? $news['longversion_en'] : '');
	$imagefilename =	$mysqli->real_escape_string(isset($news['imagefilename']) 		? $news['imagefilename'] : '');
	$publish =			$mysqli->real_escape_string(isset($news['publish']) 			? (int)$news['publish'] : 0);
	$publishdate =		$mysqli->real_escape_string(isset($news['publishdatestr']) 		? $news['publishdatestr'] : '');

	$query = "UPDATE cpa_ws_news SET name = '$name', publish = $publish, publishdate = '$publishdate' WHERE id = $id";
	if ($mysqli->query($query)) {
		$query = "UPDATE cpa_ws_text SET text = '$title_fr' WHERE id = $title AND language = 'fr-ca'";
		if ($mysqli->query($query)) {
			$query = "UPDATE cpa_ws_text SET text = '$title_en' WHERE id = $title AND language = 'en-ca'";
			if ($mysqli->query($query)) {
				$query = "UPDATE cpa_ws_text SET text = '$shortversion_fr' WHERE id = $shortversion AND language = 'fr-ca'";
				if ($mysqli->query($query)) {
					$query = "UPDATE cpa_ws_text SET text = '$shortversion_en' WHERE id = $shortversion AND language = 'en-ca'";
					if ($mysqli->query($query)) {
						$query = "UPDATE cpa_ws_text SET text = '$longversion_fr' WHERE id = $longversion AND language = 'fr-ca'";
						if ($mysqli->query($query)) {
							$query = "UPDATE cpa_ws_text SET text = '$longversion_en' WHERE id = $longversion AND language = 'en-ca'";
							if ($mysqli->query($query)) {
								$data['success'] = true;
								$data['message'] = 'News updated successfully.';
							} else {
								throw new Exception($mysqli->sqlstate . ' - ' . $mysqli->error);
							}
						} else {
							throw new Exception($mysqli->sqlstate . ' - ' . $mysqli->error);
						}
					} else {
						throw new Exception($mysqli->sqlstate . ' - ' . $mysqli->error);
					}
				} else {
					throw new Exception($mysqli->sqlstate . ' - ' . $mysqli->error);
				}
			} else {
				throw new Exception($mysqli->sqlstate . ' - ' . $mysqli->error);
			}
		} else {
			throw new Exception($mysqli->sqlstate . ' - ' . $mysqli->error);
		}
	} else {
		throw new Exception($mysqli->sqlstate . ' - ' . $mysqli->error);
	}
	return $data;
	exit;
};

/**
 * This function will handle user deletion
 * @throws Exception
 */
function delete_news($mysqli, $news)
{
	try {
		$id = 				$mysqli->real_escape_string(isset($news['id']) 				? (int)$news['id'] : 0);
		$title =			$mysqli->real_escape_string(isset($news['title']) 			? (int)$news['title'] : 0);
		$shortversion =		$mysqli->real_escape_string(isset($news['shortversion']) 	? (int)$news['shortversion'] : 0);
		$longversion =		$mysqli->real_escape_string(isset($news['longversion']) 	? (int)$news['longversion'] : 0);
		$imagefilename =	$mysqli->real_escape_string(isset($news['imagefilename']) 	? $news['imagefilename'] : '');

		if (empty($id)) throw new Exception("Invalid news id.");
		$query = "DELETE FROM cpa_ws_news WHERE id = $id";
		if ($mysqli->query($query)) {
			$query = "DELETE FROM cpa_ws_text WHERE id IN ($title, $shortversion, $longversion)";
			if ($mysqli->query($query)) {
				$mysqli->close();
				// Delete the filename related to the object
				$filename = removeFile('/website/images/news/', $imagefilename, false);
				$data['filename'] = $filename;
				$data['success'] = true;
				$data['message'] = 'News deleted successfully.';
				echo json_encode($data);
				exit;
			} else {
				throw new Exception($mysqli->sqlstate . ' - ' . $mysqli->error);
			}
		} else {
			throw new Exception($mysqli->sqlstate . ' - ' . $mysqli->error);
		}
	} catch (Exception $e) {
		$data = array();
		$data['success'] = false;
		$data['message'] = $e->getMessage();
		echo json_encode($data);
		exit;
	}
};

/**
 * This function gets list of all news from database
 */
function getAllNewss($mysqli, $language)
{
	try {
		$query = "	SELECT 	id, getWSTextLabel(title, '$language') title, publishdate, publish, 
							getCodeDescription('YESNO',publish, '$language') ispublish,  
							getCodeDescription('YESNO', if (imagefilename is not null and imagefilename!='', 1, 0), '$language') isimage  
					FROM cpa_ws_news 
					ORDER BY publishdate DESC";
		$result = $mysqli->query($query);
		$data = array();
		while ($row = $result->fetch_assoc()) {
			$data['data'][] = $row;
		}
		$data['config'] = getWSSupportedLanguages($mysqli)['data'];
		$mysqli->close();
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
 * This function gets the details of all links for a news from database
 */
function getNewsLinks($mysqli, $newsid, $language)
{
	try {
		$query = "	SELECT 	cwnl.*, getWSTextLabel(cwnl.label, 'fr-ca') label_fr, getWSTextLabel(cwnl.label, 'en-ca') label_en, 
							getCodeDescription('wslinktypes', cwnl.linktype, '$language') linktypelabel,
							getCodeDescription('wslinkpositions', cwnl.position, '$language') linkpositionlabel
					FROM cpa_ws_news_links cwnl
					WHERE cwnl.newsid = $newsid
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
	} catch (Exception $e) {
		$data = array();
		$data['success'] = false;
		$data['message'] = $e->getMessage();
		return $data;
		exit;
	}
};

/**
 * This function gets the details of one news from database
 */
function getNewsDetails($mysqli, $id, $language)
{
	try {
		$query = "	SELECT 	cwp.*, getWSTextLabel(cwp.title, 'fr-ca') title_fr, getWSTextLabel(cwp.title, 'en-ca') title_en,
							getWSTextLabel(cwp.shortversion, 'fr-ca') shortversion_fr, getWSTextLabel(cwp.shortversion, 'en-ca') shortversion_en,
							getWSTextLabel(cwp.longversion, 'fr-ca') longversion_fr, getWSTextLabel(cwp.longversion, 'en-ca') longversion_en
					FROM cpa_ws_news cwp
					WHERE cwp.id = $id";
		$result = $mysqli->query($query);
		$data = array();
		$data['imageinfo'] = null;
		while ($row = $result->fetch_assoc()) {
			$row['links'] = getNewsLinks($mysqli, $id, $language)['data'];
			$data['data'][] = $row;
			$filename = getImageFileName('/website/images/news/', $row['imagefilename']);
			$data['imageinfo'] = getImageFileInfo($filename);
		}
		$mysqli->close();
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
function updateEntireLinks($mysqli, $newsid, $links)
{
	$data = array();
	for ($x = 0; $links && $x < count($links); $x++) {
		$id = 				$mysqli->real_escape_string(isset($links[$x]['id'])					? (int)$links[$x]['id'] : '');
		$linkname = 		$mysqli->real_escape_string(isset($links[$x]['linkname'])			? $links[$x]['linkname'] : '');
		$label = 			$mysqli->real_escape_string(isset($links[$x]['label'])				? (int)$links[$x]['label'] : 0);
		$label_fr = 		$mysqli->real_escape_string(isset($links[$x]['label_fr'])			? $links[$x]['label_fr'] : '');
		$label_en = 		$mysqli->real_escape_string(isset($links[$x]['label_en'])			? $links[$x]['label_en'] : '');
		$linktype = 		$mysqli->real_escape_string(isset($links[$x]['linktype'])			? (int)$links[$x]['linktype'] : 0);
		$linkpage = 		$mysqli->real_escape_string(isset($links[$x]['linkpage'])			? $links[$x]['linkpage'] : '');
		$linksection = 		$mysqli->real_escape_string(isset($links[$x]['linksection'])		? $links[$x]['linksection'] : '');
		$linkdocumentid = 	$mysqli->real_escape_string(isset($links[$x]['linkdocumentid'])		? (int)$links[$x]['linkdocumentid'] : 0);
		$linkexternal = 	$mysqli->real_escape_string(isset($links[$x]['linkexternal'])		? $links[$x]['linkexternal'] : '');
		$position = 		$mysqli->real_escape_string(isset($links[$x]['position'])			? (int)$links[$x]['position'] : 0);
		$linkindex = 		$mysqli->real_escape_string(isset($links[$x]['linkindex'])			? (int)$links[$x]['linkindex'] : 0);

		if ($mysqli->real_escape_string(isset($links[$x]['status'])) and $links[$x]['status'] == 'New') {
			$query = "	INSERT INTO cpa_ws_news_links (id, newsid, linkname, label, linktype, linkpage, linksection, linkdocumentid, linkexternal, position, linkindex)
						VALUES (null, $newsid, '$linkname', create_wsText('$label_en', '$label_fr'), $linktype, '$linkpage', '$linksection', $linkdocumentid, '$linkexternal', $position, $linkindex)";
			if (!$mysqli->query($query)) {
				throw new Exception($mysqli->sqlstate . ' - ' . $mysqli->error);
			}
		}

		if ($mysqli->real_escape_string(isset($links[$x]['status'])) and $links[$x]['status'] == 'Modified') {
			$query = "	UPDATE cpa_ws_news_links 
						SET linkname = '$linkname', linktype = $linktype, linkpage = '$linkpage', linksection = '$linksection', 
							linkdocumentid = $linkdocumentid,
							linkexternal = '$linkexternal', position = $position, linkindex = $linkindex
						WHERE id = $id";
			if ($mysqli->query($query)) {
				$query = "UPDATE cpa_ws_text SET text = '$label_fr' WHERE id = $label AND language = 'fr-ca'";
				if ($mysqli->query($query)) {
					$query = "UPDATE cpa_ws_text SET text = '$label_en' WHERE id = $label AND language = 'en-ca'";
					if ($mysqli->query($query)) {
					} else {
						throw new Exception($mysqli->sqlstate . ' - ' . $mysqli->error);
					}
				} else {
					throw new Exception($mysqli->sqlstate . ' - ' . $mysqli->error);
				}
			} else {
				throw new Exception($mysqli->sqlstate . ' - ' . $mysqli->error);
			}
		}

		if ($mysqli->real_escape_string(isset($links[$x]['status'])) and $links[$x]['status'] == 'Deleted') {
			$query = "DELETE FROM cpa_ws_news_links WHERE id = $id";
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

function updateEntireNews($mysqli, $news)
{
	try {
		$data = array();
		$id = $mysqli->real_escape_string(isset($news['id']) ? (int)$news['id'] : 0);

		$data['successnews'] = update_news($mysqli, $news);
		if ($mysqli->real_escape_string(isset($news['links']))) {
			$data['successlinks'] = updateEntireLinks($mysqli, $id, $news['links']);
		}
		$mysqli->close();

		$data['success'] = true;
		$data['message'] = 'News updated successfully.';
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
