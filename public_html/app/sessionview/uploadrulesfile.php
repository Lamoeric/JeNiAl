<?php
/*
Author : Eric Lamoureux
*/
  require_once('../../../private/'. $_SERVER['HTTP_HOST'].'/include/config.php');

  $data = array();
  $data['success'] = false;
  try {
    $mainobj =    $_POST['mainobj'];
    $language =    $_POST['language'];
    // $language = 	$mysqli->real_escape_string(isset($_POST['language']) ? 		$_POST['language'] : 'toto');
    $sessionid =	$mysqli->real_escape_string(isset($mainobj['id']) 	  ?     (int)$mainobj['id'] : 0);
    $query = "DELETE FROM cpa_sessions_rules WHERE sessionid = $sessionid AND language = '$language'";
  	if ($mysqli->query($query)) {
    	$query = "INSERT INTO cpa_sessions_rules (id, sessionid, language, rules) VALUES (null, $sessionid, '$language', '" . $mysqli->real_escape_string(file_get_contents($_FILES['file']['tmp_name'])) . "')";
    	if ($mysqli->query($query)) {
    		$data['success'] = true;
    		$data['message'] = 'Document updated successfully.';
    	} else {
    		throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
    	}
  	} else {
  		throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
  	}
  	echo json_encode($data);
    exit;
  } catch (Exception $e) {
    $data = array();
    $data['success'] = false;
    $data['message'] = $e->getMessage();
    echo json_encode($data);
    exit;
  }
?>
