 <?php
/*
Author : Eric Lamoureux
*/
require_once('../../../private/'. $_SERVER['HTTP_HOST'].'/include/config.php');
require_once('../../include/nocache.php');

if (isset($_POST['type']) && !empty(isset($_POST['type']))) {
	$type = $_POST['type'];

	switch ($type) {
		case "insert_task":
			insert_task($mysqli, $_POST['task']);
			break;
		case "updateEntireTask":
			updateEntireTask($mysqli, $_POST['task']);
			break;
		case "delete_task":
			delete_task($mysqli, $_POST['task']);
			break;
		case "getAllTasks":
			getAllTasks($mysqli, $_POST['language']);
			break;
		case "getTaskDetails":
			getTaskDetails($mysqli, $_POST['id'], $_POST['language']);
			break;
		default:
			invalidRequest();
	}
} else {
	invalidRequest();
};

function updateEntireTask($mysqli, $task) {
	try{
		$data = array();
		$id = $mysqli->real_escape_string(isset($task['id']) ? $task['id'] : '');

		update_task($mysqli, $task);
		$mysqli->close();

		$data['success'] = true;
		$data['message'] = 'task updated successfully.';
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

function insert_task($mysqli, $task) {
	echo json_encode(update_task($mysqli, $task));
	exit;
}

/**
 * This function will handle task update functionality
 * @throws Exception
 */
function update_task($mysqli, $task) {
	try{
		$data = array();
		$id 				= $mysqli->real_escape_string(isset($task['id']) 								? $task['id'] : '');
		$category		= $mysqli->real_escape_string(isset($task['category']) 					? $task['category'] : '');
		$active 		= $mysqli->real_escape_string(isset($task['active'] )				    ? (int)$task['active'] : 0);
		$label 			= $mysqli->real_escape_string(isset($task['label'] )				    ? (int)$task['label'] : '');
		$label_fr 	= $mysqli->real_escape_string(isset($task['label_fr'] ) 		    ? $task['label_fr'] : '');
		$label_en 	= $mysqli->real_escape_string(isset($task['label_en'] ) 		    ? $task['label_en'] : '');
		$desc 			= $mysqli->real_escape_string(isset($task['description'] )			? (int)$task['description'] : '');
		$desc_fr 		= $mysqli->real_escape_string(isset($task['desc_fr'] ) 		    	? $task['desc_fr'] : '');
		$desc_en 		= $mysqli->real_escape_string(isset($task['desc_en'] ) 		    	? $task['desc_en'] : '');

		if (empty($id)) {
			$data['insert'] = true;
			$query = "INSERT INTO cpa_shows_tasks(id, category, label, description)
								VALUES (NULL, 1, create_systemText('$label_en', '$label_fr'), create_systemText('$desc_en', '$desc_fr'))";
			if ($mysqli->query($query)) {
				$data['success'] = true;
				$id = (int) $mysqli->insert_id;
				$data['id'] = $id;
			} else {
				throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
			}
		} else {
			if (!$label || $label == '' || $label == 0) {
				$query = "UPDATE cpa_shows_tasks SET label = create_systemText('$label_en', '$label_fr')	WHERE id = $id";
				if ($mysqli->query($query)) {
					$data['success'] = true;
					if (!$desc || $desc == '' || $desc == 0) {
						$query = "UPDATE cpa_shows_tasks SET description = create_systemText('$desc_en', '$desc_fr')	WHERE id = $id";
						if ($mysqli->query($query)) {
						} else {
							throw new Exception( $mysqli->sqlstate.' - '. $mysqli->error );
						}
					}
				} else {
					throw new Exception( $mysqli->sqlstate.' - '. $mysqli->error );
				}
			}
			$query = "UPDATE cpa_shows_tasks set category = '$category', active = '$active'";
			if( $mysqli->query( $query ) ){
				$query = "UPDATE cpa_text set text = '$label_fr' where id = $label and language = 'fr-ca'";
				if( $mysqli->query( $query ) ){
					$data['success'] = true;
					$query = "UPDATE cpa_text set text = '$label_en' where id = $label and language = 'en-ca'";
					if( $mysqli->query( $query ) ){
						$data['success'] = true;
						$query = "UPDATE cpa_text set text = '$desc_fr' where id = $desc and language = 'fr-ca'";
						if( $mysqli->query( $query ) ){
							$data['success'] = true;
							$query = "UPDATE cpa_text set text = '$desc_en' where id = $desc and language = 'en-ca'";
							if( $mysqli->query( $query ) ){
								$data['success'] = true;
							} else {
								throw new Exception( $mysqli->sqlstate.' - '. $mysqli->error );
							}
						} else {
							throw new Exception( $mysqli->sqlstate.' - '. $mysqli->error );
						}
					} else {
						throw new Exception( $mysqli->sqlstate.' - '. $mysqli->error );
					}
				} else {
					throw new Exception( $mysqli->sqlstate.' - '. $mysqli->error );
				}
			} else {
				throw new Exception( $mysqli->sqlstate.' - '. $mysqli->error );
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
 * This function will handle task deletion
 * @param string $id
 * @throws Exception
 */
function delete_task($mysqli, $task) {
	try{
		$data = array();
		$id 		= $mysqli->real_escape_string(isset($task['id']) 						? $task['id'] : '');
		$label 	= $mysqli->real_escape_string(isset($task['label'] )				? (int)$task['label'] : '');
		$desc 	= $mysqli->real_escape_string(isset($task['description'] )	? (int)$task['description'] : '');

		if (empty($id)) throw new Exception("Invalid task.");
		$query = "DELETE FROM cpa_text WHERE id = $label";
		if ($mysqli->query($query)) {
			$query = "DELETE FROM cpa_text WHERE id = $desc";
			if ($mysqli->query($query)) {
				$query = "DELETE FROM cpa_shows_tasks WHERE id = $id";
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
 * This function gets list of all tasks from database
 */
function getAllTasks($mysqli, $language) {
	try{
		$query = "SELECT id, getTextLabel(label, '$language') text FROM cpa_shows_tasks order by id";
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
 * This function gets the details of one task from database
 */
function getTaskDetails($mysqli, $id, $language) {
	try{
		if (empty($id)) throw new Exception("Invalid User.");
		$query = "SELECT *, getEnglishTextLabel(label) as label_en, getFrenchTextLabel(label) as label_fr, getTextLabel(label, '$language') text,
												getEnglishTextLabel(description) as desc_en, getFrenchTextLabel(description) as desc_fr 
							FROM cpa_shows_tasks 
							WHERE id = $id";
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
