<?php
  /*
  Author : Eric Lamoureux
  Deletes a file in the [private website images]/[subdir] directory
  [lamouree 2010/02/17] Changed SET imagefilename = null for SET imagefilename = '' to avoid the not null bug
  */
  require_once('../../../../../private/'. $_SERVER['HTTP_HOST'].'/include/config.php');

  $maindir = '../../../../../private/'. $_SERVER['HTTP_HOST'].'/website/images/';
  $subdir = $_POST['subdir'];
  $dirsuffix = $_POST['dirsuffix'];
  $context = $_POST['context'];
  $obj = $_POST['obj'];
  $id = $_POST['id'];
  $imagefilename = $_POST['imagefilename'];
  $imagefilename = !empty($imagefilename) ? $imagefilename : $obj['imagefilename'];
  $id = !empty($id) ? $id : (isset($obj['id']) ? $obj['id'] : null);
  $name = isset($obj['name']) ? $obj['name'] : null;
  $data['success'] = false;
  try {
    if ($imagefilename && !empty($imagefilename)) {
      $completefilename = $maindir . $subdir . '/' . ($dirsuffix && !empty($dirsuffix) ? $dirsuffix . 'id' . $id . '/' : '') . $imagefilename;
      if (file_exists($completefilename)) {
          unlink($completefilename);
          if ($id && !empty($id)) {
            $query = "UPDATE cpa_" . $context . " SET imagefilename = '' WHERE id = $id";
          } else if ($name && !empty($name)) {
            $query = "UPDATE cpa_" . $context . " SET imagefilename = '' WHERE name = '$name'";
          }
        	if ($mysqli->query($query)) {
            $data['success'] = true;
            $data['message'] = 'File deleted successfully.';
        	} else {
        		throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
        	}
      } else {
        $data['success'] = false;
        $data['message'] = 'File ' . $completefilename . ' not deleted successfully.';
      }
    } else {
      $data['success'] = false;
      $data['message'] = 'File name empty. File not deleted successfully.';
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
