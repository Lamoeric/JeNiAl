 <?php
/*
Author : Eric Lamoureux
*/
require_once('../../../private/'. $_SERVER['HTTP_HOST'].'/include/config.php');
require_once('../../include/nocache.php');
require_once('../core/directives/clubcontacts/clubcontacts.php');

if (isset($_POST['type']) && !empty(isset($_POST['type']))) {
	$type = $_POST['type'];

	switch ($type) {
		case "insert_club":
			insert_club($mysqli, $_POST['club']);
			break;
		case "updateEntireClub":
			updateEntireClub($mysqli, $_POST['club']);
			break;
		case "delete_club":
			delete_club($mysqli, $_POST['club']);
			break;
		case "getAllClubs":
			getAllClubs($mysqli, $_POST['language']);
			break;
		case "getClubDetails":
			getClubDetails($mysqli, $_POST['id'], $_POST['language']);
			break;
		default:
			invalidRequest();
	}
} else {
	invalidRequest();
};

function updateEntireClub($mysqli, $club) {
	try{
		$data = array();
		$id = $mysqli->real_escape_string(isset($club['id']) ? $club['id'] : '');

		update_club($mysqli, $club);
    if ($mysqli->real_escape_string(isset($club['contacts']))) {
			$data['successcontacts'] = updateEntireContacts($mysqli, $id, $club['contacts']);
		}
		$mysqli->close();

		$data['success'] = true;
		$data['message'] = 'club updated successfully.';
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

function insert_club($mysqli, $club) {
	echo json_encode(update_club($mysqli, $club));
	exit;
}

/**
 * This function will handle club update functionality
 * @throws Exception
 */
function update_club($mysqli, $club) {
	try{
		$data = array();
		$id 				= $mysqli->real_escape_string(isset($club['id']) 								? $club['id'] : '');
		$code 			= $mysqli->real_escape_string(isset($club['code']) 							? $club['code'] : '');
		$orgno 			= $mysqli->real_escape_string(isset($club['orgno']) 						? $club['orgno'] : '');
		$label 			= $mysqli->real_escape_string(isset($club['label'] )				    ? (int)$club['label'] : '');
		$label_fr 	= $mysqli->real_escape_string(isset($club['label_fr'] ) 		    ? $club['label_fr'] : '');
		$label_en 	= $mysqli->real_escape_string(isset($club['label_en'] ) 		    ? $club['label_en'] : '');
    $friendly 	= $mysqli->real_escape_string(isset($club['friendly'] )				  ? (int)$club['friendly'] : 0);

		if (empty($id)) {
			$data['insert'] = true;
			$query = "INSERT INTO cpa_clubs(id, code, label, orgno, friendly)
								VALUES (NULL, '$code', create_systemText('$label_en', '$label_fr'), '$orgno', $friendly)";
			if ($mysqli->query($query)) {
				$data['success'] = true;
				$id = (int) $mysqli->insert_id;
				$data['id'] = $id;
			} else {
				throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
			}
		} else {
			$query = "UPDATE cpa_clubs SET code = '$code', orgno = '$orgno', friendly = $friendly	WHERE id = $id";
			if ($mysqli->query($query)) {
				if (!$label || $label == '' || $label == 0) {
					$query = "UPDATE cpa_clubs SET label = create_systemText('$label_en', '$label_fr')	WHERE id = $id";
					if ($mysqli->query($query)) {
						$data['success'] = true;
					} else {
						throw new Exception( $mysqli->sqlstate.' - '. $mysqli->error );
					}
				}
				$query = "UPDATE cpa_text set text = '$label_fr' where id = $label and language = 'fr-ca'";
				if( $mysqli->query( $query ) ){
					$data['success'] = true;
					$query = "UPDATE cpa_text set text = '$label_en' where id = $label and language = 'en-ca'";
					if( $mysqli->query( $query ) ){
						$data['success'] = true;
					} else {
						throw new Exception( $mysqli->sqlstate.' - '. $mysqli->error );
					}
				} else {
					throw new Exception( $mysqli->sqlstate.' - '. $mysqli->error );
				}
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
function delete_club($mysqli, $club) {
	try{
		$id = $mysqli->real_escape_string(isset($club['id']) ? $club['id'] : '');

		if (empty($id)) throw new Exception("Invalid club.");
		$query = "DELETE FROM cpa_clubs WHERE id = $id";
		if ($mysqli->query($query)) {
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
 * This function gets list of all clubs from database
 */
function getAllClubs($mysqli, $language) {
	try{
		$query = "SELECT id, code, getTextLabel(label, '$language') text, orgno FROM cpa_clubs order by id";
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
 * This function gets the details of one club from database
 */
function getClubDetails($mysqli, $id, $language) {
	try{
		if (empty($id)) throw new Exception("Invalid User.");
		$query = "SELECT *, getEnglishTextLabel(label) as label_en, getFrenchTextLabel(label) as label_fr, getTextLabel(label, '$language') text FROM cpa_clubs WHERE id = $id";
		$result = $mysqli->query($query);
		$data = array();
		while ($row = $result->fetch_assoc()) {
			$row['id'] = (int) $row['id'];
      $row['contacts'] = getClubContacts($mysqli, $id, $language)['data'];
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
