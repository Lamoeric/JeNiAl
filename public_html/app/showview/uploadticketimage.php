<?php
/*
Author : Eric Lamoureux
*/
  require_once('../../../private/'. $_SERVER['HTTP_HOST'].'/include/config.php');
  // require_once('../../backend/createthumbnail.php');

  $table = array(
      'Š'=>'S', 'š'=>'s', 'Đ'=>'Dj', 'đ'=>'dj', 'Ž'=>'Z', 'ž'=>'z', 'Č'=>'C', 'č'=>'c', 'Ć'=>'C', 'ć'=>'c',
      'À'=>'A', 'Á'=>'A', 'Â'=>'A', 'Ã'=>'A', 'Ä'=>'A', 'Å'=>'A', 'Æ'=>'A', 'Ç'=>'C', 'È'=>'E', 'É'=>'E',
      'Ê'=>'E', 'Ë'=>'E', 'Ì'=>'I', 'Í'=>'I', 'Î'=>'I', 'Ï'=>'I', 'Ñ'=>'N', 'Ò'=>'O', 'Ó'=>'O', 'Ô'=>'O',
      'Õ'=>'O', 'Ö'=>'O', 'Ø'=>'O', 'Ù'=>'U', 'Ú'=>'U', 'Û'=>'U', 'Ü'=>'U', 'Ý'=>'Y', 'Þ'=>'B', 'ß'=>'Ss',
      'à'=>'a', 'á'=>'a', 'â'=>'a', 'ã'=>'a', 'ä'=>'a', 'å'=>'a', 'æ'=>'a', 'ç'=>'c', 'è'=>'e', 'é'=>'e',
      'ê'=>'e', 'ë'=>'e', 'ì'=>'i', 'í'=>'i', 'î'=>'i', 'ï'=>'i', 'ð'=>'o', 'ñ'=>'n', 'ò'=>'o', 'ó'=>'o',
      'ô'=>'o', 'õ'=>'o', 'ö'=>'o', 'ø'=>'o', 'ù'=>'u', 'ú'=>'u', 'û'=>'u', 'ý'=>'y', 'ý'=>'y', 'þ'=>'b',
      'ÿ'=>'y', 'Ŕ'=>'R', 'ŕ'=>'r', ' '=>'_', '#'=>'_',
  );
  $data = array();
  $data['success'] = false;
  try {
    $uploads_dir = '../../../private/'. $_SERVER['HTTP_HOST'].'/website/images/shows/';
    $meta = $_POST;
    // $language = $meta['language'];
    $mainobj = $meta['mainobj']; // mainobj is the curent performance
    if (isset($mainobj['imagefilename']) && !empty($mainobj['imagefilename'])) {
    	$data['imagefilename'] = $mainobj['imagefilename'];
      $oldfilename = $uploads_dir . $mainobj['imagefilename'];
      if (file_exists($oldfilename)) {
          unlink($oldfilename);
      }
    }
    $id = $mainobj['id'];
  	$data['id'] = $id;
    $partialfilename = 'perf_' . $id . '_' . getGUID() . '.jpg';
    // $partialfilename = $name.'.jpg';
    $partialfilename = strtr($partialfilename, $table);
    $destinationFileName = $uploads_dir . $partialfilename;
  	$data['destinationFileName'] = $destinationFileName;
  	$data['sourceFileName'] = $_FILES['file']['tmp_name'];
    $retVal = move_uploaded_file( $_FILES['file']['tmp_name'] , $destinationFileName);
    if ($retVal != 0) {
    	$query = "UPDATE cpa_shows_performances_tickets SET imagefilename = '$partialfilename' WHERE performanceid = $id";
    	if ($mysqli->query($query)) {
    		$data['success'] = true;
    		$data['message'] = 'Performance updated successfully.';
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

  function getGUID(){
    if (function_exists('com_create_guid')){
        return com_create_guid();
    }
    else {
        mt_srand((double)microtime()*10000);//optional for php 4.2.0 and up.
        $charid = strtoupper(md5(uniqid(rand(), true)));
        $hyphen = chr(45);// "-"
        $uuid = chr(123)// "{"
            .substr($charid, 0, 8).$hyphen
            .substr($charid, 8, 4).$hyphen
            .substr($charid,12, 4).$hyphen
            .substr($charid,16, 4).$hyphen
            .substr($charid,20,12)
            .chr(125);// "}"
        return $uuid;
    }
  }
?>
