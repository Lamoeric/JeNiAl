<?php
/*
Author : Eric Lamoureux
*/
  require_once('../../../private/'. $_SERVER['HTTP_HOST'].'/include/config.php');

  $table = array(
      'Š'=>'S', 'š'=>'s', 'Đ'=>'Dj', 'đ'=>'dj', 'Ž'=>'Z', 'ž'=>'z', 'Č'=>'C', 'č'=>'c', 'Ć'=>'C', 'ć'=>'c',
      'À'=>'A', 'Á'=>'A', 'Â'=>'A', 'Ã'=>'A', 'Ä'=>'A', 'Å'=>'A', 'Æ'=>'A', 'Ç'=>'C', 'È'=>'E', 'É'=>'E',
      'Ê'=>'E', 'Ë'=>'E', 'Ì'=>'I', 'Í'=>'I', 'Î'=>'I', 'Ï'=>'I', 'Ñ'=>'N', 'Ò'=>'O', 'Ó'=>'O', 'Ô'=>'O',
      'Õ'=>'O', 'Ö'=>'O', 'Ø'=>'O', 'Ù'=>'U', 'Ú'=>'U', 'Û'=>'U', 'Ü'=>'U', 'Ý'=>'Y', 'Þ'=>'B', 'ß'=>'Ss',
      'à'=>'a', 'á'=>'a', 'â'=>'a', 'ã'=>'a', 'ä'=>'a', 'å'=>'a', 'æ'=>'a', 'ç'=>'c', 'è'=>'e', 'é'=>'e',
      'ê'=>'e', 'ë'=>'e', 'ì'=>'i', 'í'=>'i', 'î'=>'i', 'ï'=>'i', 'ð'=>'o', 'ñ'=>'n', 'ò'=>'o', 'ó'=>'o',
      'ô'=>'o', 'õ'=>'o', 'ö'=>'o', 'ø'=>'o', 'ù'=>'u', 'ú'=>'u', 'û'=>'u', 'ý'=>'y', 'ý'=>'y', 'þ'=>'b',
      'ÿ'=>'y', 'Ŕ'=>'R', 'ŕ'=>'r', ' '=>'_', '\''=>'_',
  );
  $data = array();
  $data['success'] = false;
  try {
    $mainobj = $_POST['mainobj'];
    $language = $_POST['language'];
    $filename = $_POST['filename'];
    $uploads_dir = '../../../private/'. $_SERVER['HTTP_HOST'].'/website/documents/' . $language . "/";
    if ($language == 'fr-ca' && isset($mainobj['filename_fr']) && !empty($mainobj['filename_fr'])) {
      $oldfilename = $uploads_dir . $mainobj['filename_fr'];
      if (file_exists($oldfilename)) {
        unlink($oldfilename);
      }
    }
    if ($language == 'en-ca' && isset($mainobj['filename_en']) && !empty($mainobj['filename_en'])) {
      $oldfilename = $uploads_dir . $mainobj['filename_en'];
      if (file_exists($oldfilename)) {
        unlink($oldfilename);
      }
    }
    $filename = strtr($filename, $table);
    $destinationFileName = $uploads_dir . $filename;
    $retVal = move_uploaded_file($_FILES['file']['tmp_name'], $destinationFileName);
    if ($retVal != 0) {
      $id =	$mysqli->real_escape_string($mainobj['filename'] 	? (int)$mainobj['filename'] : 0);
//    	$query = "UPDATE cpa_ws_text SET text = '$filename' WHERE id = $id and language = '$language'";
    	if ($mysqli->query($query)) {
    		$data['success'] = true;
    		$data['message'] = 'Email template updated successfully.';
    	} else {
    		throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
    	}
    	echo json_encode($data);
      exit;
    } else {
      $data['success'] = false;
      $data['message'] = "Move not done.";
    	echo json_encode($data);
      exit;
    }
  } catch (Exception $e) {
    $data = array();
    $data['success'] = false;
    $data['message'] = $e->getMessage();
    echo json_encode($data);
    exit;
  }
?>
