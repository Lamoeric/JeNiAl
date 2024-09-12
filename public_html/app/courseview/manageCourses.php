<?php
/*
Author : Eric Lamoureux
*/
require_once('../../../private/'. $_SERVER['HTTP_HOST'].'/include/config.php');
require_once('../../include/nocache.php');

if (isset($_POST['type']) && !empty(isset($_POST['type']))) {
	$type = $_POST['type'];

	switch ($type) {
		case "insert_course":
			insert_course($mysqli);
			break;
		case "updateEntireCourse":
			updateEntireCourse($mysqli, $_POST['course']);
			break;
		case "delete_course":
			delete_course($mysqli);
			break;
		case "getAllCourses":
			getAllCourses($mysqli, $_POST['language']);
			break;
		case "getCourseDetails":
			getCourseDetails($mysqli, $_POST['code']);
			break;
		default:
			invalidRequest();
	}
} else {
	invalidRequest();
};

/**
 * This function will handle course add, update functionality
 * @throws Exception
 */
function insert_course($mysqli) {
	try{
		$data = array();
		$code 								= $mysqli->real_escape_string(isset($_POST['course']['code']) 								? $_POST['course']['code'] : '');
		$name 								= $mysqli->real_escape_string(isset($_POST['course']['name']) 								? $_POST['course']['name'] : '');
		$label 								= $mysqli->real_escape_string(isset($_POST['course']['label']) 								? $_POST['course']['label'] : '');
		$label_fr 						= $mysqli->real_escape_string(isset($_POST['course']['label_fr']) 						? $_POST['course']['label_fr'] : '');
		$label_en 						= $mysqli->real_escape_string(isset($_POST['course']['label_en']) 						? $_POST['course']['label_en'] : '');
		$shortdesc 						= $mysqli->real_escape_string(isset($_POST['course']['shortdesc']) 						? $_POST['course']['shortdesc'] : '');
		$shortdesc_fr 				=	$mysqli->real_escape_string(isset($_POST['course']['shortdesc_fr']) 				? $_POST['course']['shortdesc_fr'] : '');
		$shortdesc_en 				=	$mysqli->real_escape_string(isset($_POST['course']['shortdesc_en']) 				? $_POST['course']['shortdesc_en'] : '');
		$acceptregistrations 	=	$mysqli->real_escape_string(isset($_POST['course']['acceptregistrations'])	? (int)$_POST['course']['acceptregistrations'] : 1);
		$active 							= $mysqli->real_escape_string(isset($_POST['course']['active']) 							? (int)$_POST['course']['active'] : 0);
		$schedulecolor 				=	$mysqli->real_escape_string(isset($_POST['course']['schedulecolor']) 				? $_POST['course']['schedulecolor'] : '');

		if ($name == '' || $code == '') {
			throw new Exception("Required fields missing, Please enter and submit");
		}

		$query = "INSERT INTO cpa_courses (name, code, label, shortdesc, acceptregistrations, active, schedulecolor)
							VALUES ('$name', '$code', create_systemText('$name', '$name'), create_systemText('$name', '$name'), $acceptregistrations, $active, '$schedulecolor')";

		if ($mysqli->query($query)) {
			$data['success'] = true;
		} else {
			throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
		}
		$mysqli->close();
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
 * This function will handle course add, update functionality
 * @throws Exception
 */
function update_course($mysqli, $course) {
	try{
		$data = array();
		$code 								= $mysqli->real_escape_string(isset($course['code']) 								? $course['code'] : '');
		$name 								= $mysqli->real_escape_string(isset($course['name']) 								? $course['name'] : '');
		$label 								= $mysqli->real_escape_string(isset($course['label']) 							? (int)$course['label'] : '');
		$label_fr 						= $mysqli->real_escape_string(isset($course['label_fr']) 						? $course['label_fr'] : '');
		$label_en 						= $mysqli->real_escape_string(isset($course['label_en']) 						? $course['label_en'] : '');
		$shortdesc 						= $mysqli->real_escape_string(isset($course['shortdesc']) 					? (int)$course['shortdesc'] : '');
		$shortdesc_fr 				=	$mysqli->real_escape_string(isset($course['shortdesc_fr']) 				? $course['shortdesc_fr'] : '');
		$shortdesc_en					=	$mysqli->real_escape_string(isset($course['shortdesc_en']) 				? $course['shortdesc_en'] : '');
		$acceptregistrations 	= $mysqli->real_escape_string(isset($course['acceptregistrations'])	? (int)$course['acceptregistrations'] : 1);
		$active 							= $mysqli->real_escape_string(isset($course['active']) 							? (int)$course['active'] : 0);
		$schedulecolor 				=	$mysqli->real_escape_string(isset($_POST['course']['schedulecolor']) 				? $_POST['course']['schedulecolor'] : '');

		if ($name == '' || $code == '') {
			throw new Exception("Required fields missing, Please enter and submit");
		}

		$query = "UPDATE cpa_courses SET name = '$name', acceptregistrations = $acceptregistrations, active = '$active', schedulecolor = '$schedulecolor' WHERE code = '$code'";

		if ($mysqli->query($query)) {
			$query = "UPDATE cpa_text set text = '$shortdesc_fr' where id = $shortdesc and language = 'fr-ca'";
			if ($mysqli->query($query)) {
				$data['success'] = true;
				$query = "UPDATE cpa_text set text = '$shortdesc_en' where id = $shortdesc and language = 'en-ca'";
				if ($mysqli->query($query)) {
					$query = "UPDATE cpa_text set text = '$label_fr' where id = $label and language = 'fr-ca'";
					if ($mysqli->query($query)) {
						$data['success'] = true;
						$query = "UPDATE cpa_text set text = '$label_en' where id = $label and language = 'en-ca'";
						if ($mysqli->query($query)) {
							$data['success'] = true;
							$data['message'] = 'Course updated successfully.';
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
	} catch(Exception $e) {
		$data = array();
		$data['success'] = false;
		$data['message'] = $e->getMessage();
		return $data;
		exit;
	}
};

/**
 * This function will handle course deletion
 * @throws Exception
 */
function delete_course($mysqli) {
	try{
		$code = 			$mysqli->real_escape_string(isset($_POST['course']['code']) 			? $_POST['course']['code'] : '');
		$label = 			$mysqli->real_escape_string(isset($_POST['course']['label']) 			? (int)$_POST['course']['label'] : '');
		$shortdesc = 	$mysqli->real_escape_string(isset($_POST['course']['shortdesc']) 	? (int)$_POST['course']['shortdesc'] : '');
		if (empty($code)) throw new Exception("Invalid Course.");
		$query = "DELETE FROM cpa_courses WHERE code = '$code'";
		if ($mysqli->query($query)) {
			$query = "DELETE FROM cpa_text WHERE id = $label";
			if ($mysqli->query($query)) {
				$query = "DELETE FROM cpa_text WHERE id = $shortdesc";
				if ($mysqli->query($query)) {
					$data['success'] = true;
					$data['message'] = 'Course deleted successfully.';
					echo json_encode($data);
					exit;
				} else {
					throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
				}
			} else {
				throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
			}
		} else {
			throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
		}
	} catch(Exception $e) {
		$data = array();
		$data['success'] = false;
		$data['message'] = $e->getMessage();
		echo json_encode($data);
		exit;
	}
};

/**
 * This function gets list of all courses from database
 */
function getAllCourses($mysqli, $language) {
	try{
		$query = "SELECT code, name, getTextLabel(label, '$language') label FROM cpa_courses order by code";
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
 * This function gets the details of all levels for a course from database
 */
function getCourseLevels($mysqli, $coursecode = '') {
	$query = "SELECT *, getEnglishTextLabel(label) as label_en, getFrenchTextLabel(label) as label_fr, schedulecolor
						FROM cpa_courses_levels
						WHERE coursecode = '$coursecode'";
	$result = $mysqli->query($query);
	$data = array();
	$data['data'] = array();
	while ($row = $result->fetch_assoc()) {
		$data['data'][] = $row;
	}
	$data['success'] = true;
	return $data;
	exit;
};

/**
 * This function gets the details of one course from database
 */
function getCourseDetails($mysqli, $code = '') {
	try{
		$query = "SELECT *, getEnglishTextLabel(label) as label_en, getFrenchTextLabel(label) as label_fr,
										getEnglishTextLabel(shortdesc) as shortdesc_en, getFrenchTextLabel(shortdesc) as shortdesc_fr,
										(SELECT COUNT(*) FROM cpa_sessions_courses csc WHERE csc.coursecode = cc.code) as isused
							FROM cpa_courses cc
							WHERE code = '$code'";
		$result = $mysqli->query($query);
		$data = array();
		while ($row = $result->fetch_assoc()) {
			$row['levels'] = getCourseLevels($mysqli, $code)['data'];
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
 * This function will handle insert/update/delete of a level in DB
 * @throws Exception
 */
function updateEntireLevels($mysqli, $coursecode, $levels) {
	$data = array();
	for($x = 0; $x < count($levels); $x++) {
		$code = 					$mysqli->real_escape_string(isset($levels[$x]['code'])					? $levels[$x]['code'] : '');
		$label = 					$mysqli->real_escape_string(isset($levels[$x]['label']) 				? $levels[$x]['label'] : '');
		$label_en = 			$mysqli->real_escape_string(isset($levels[$x]['label_en']) 			? $levels[$x]['label_en'] : '');
		$label_fr = 			$mysqli->real_escape_string(isset($levels[$x]['label_fr']) 			? $levels[$x]['label_fr'] : '');
		$schedulecolor =	$mysqli->real_escape_string(isset($levels[$x]['schedulecolor'])	? $levels[$x]['schedulecolor'] : '');

		if ($mysqli->real_escape_string(isset($levels[$x]['status'])) and $levels[$x]['status'] == 'New') {
			$query = "INSERT INTO cpa_courses_levels (coursecode, code, label, schedulecolor)
								VALUES ('$coursecode', '$code', create_systemText('$label_en', '$label_fr'), '$schedulecolor')";
			if (!$mysqli->query($query)) {
				throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
			}
		}

		if ($mysqli->real_escape_string(isset($levels[$x]['status'])) and $levels[$x]['status'] == 'Modified') {
			$query = "UPDATE cpa_courses_levels	SET schedulecolor = '$schedulecolor'	WHERE code = '$code' AND coursecode = '$coursecode'";
			if ($mysqli->query($query)) {
				$query = "UPDATE cpa_text set text = '$label_fr' where id = $label and language = 'fr-ca'";
				if ($mysqli->query($query)) {
					$data['success'] = true;
					$query = "UPDATE cpa_text set text = '$label_en' where id = $label and language = 'en-ca'";
					if ($mysqli->query($query)) {
						$data['success'] = true;
						$data['message'] = 'Levels updated successfully.';
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

		if ($mysqli->real_escape_string(isset($levels[$x]['status'])) and $levels[$x]['status'] == 'Deleted') {
			$query = "DELETE FROM cpa_courses_levels WHERE code = '$code'";
			if (!$mysqli->query($query)) {
				throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
			}
		}
	}
	$data['success'] = true;
	return $data;
};

function updateEntireCourse($mysqli, $course) {
	try{
		$data = array();
		$code = $mysqli->real_escape_string(isset($course['code']) ? $course['code'] : '');

		update_course($mysqli, $course);
		if ($mysqli->real_escape_string(isset($course['levels']))) {
			$data['successlevels'] = updateEntireLevels($mysqli, $code, $course['levels']);
		}
		$mysqli->close();

		$data['success'] = true;
		$data['message'] = 'Course updated successfully.';
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
