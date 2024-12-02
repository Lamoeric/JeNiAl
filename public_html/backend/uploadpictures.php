<?php
/*
Author : Eric Lamoureux
*/
require_once('../../private/' . $_SERVER['HTTP_HOST'] . '/include/config.php');
require_once(__DIR__ . '/createthumbnail.php');
require_once(__DIR__ . '/createdestinationfilename.php');
require_once(__DIR__ . '/getuploaddirectory.php');

$data = array();
$data['success'] = false;
try {
  $directorySuffix = $_POST['subDirectory'];
  $filenameprefix = $_POST['filePrefix'];
  $tablename = $_POST['tableName'];
  $idcolumnname = $_POST['idcolumnname'];
  $id = $_POST['id'];

  $uploads_dir = getUploadDirectory($directorySuffix);
  $thumbnail_dir = getUploadDirectory($directorySuffix . '/thumbnails' . '/');

  $data['uploads_dir'] = $uploads_dir;
  $data['thumbnail_dir'] = $uploads_dir;

  $filenames = createDestinationFileName($uploads_dir, $filenameprefix);
  $data['destinationFileName'] = $filenames['destinationFileName'];
  $data['partialFileName'] = $filenames['partialFileName'];

  $data['sourceFileName'] = $_FILES['file']['tmp_name'];

  $retVal = move_uploaded_file($_FILES['file']['tmp_name'], $filenames['destinationFileName']);
  if ($retVal != 0) {
    $partialfilename = $filenames['partialFileName'];
    // Lets create the thumbnail
    createThumbnail($uploads_dir, $partialfilename, $thumbnail_dir, 100);
    // To create the filename, we need to insert the record in the database
    $query = "INSERT INTO " . $tablename . "(" . $idcolumnname . ", pictureindex, imagefilename) 
              VALUES ($id, (SELECT count(pictureindex)+1 from " . $tablename . " cwep where cwep." . $idcolumnname . " = $id), '$partialfilename')";
    if ($mysqli->query($query)) {
      if (empty($pictureid)) $data['id'] = (int)$mysqli->insert_id;
      $data['success'] = true;
      echo json_encode($data);
      exit;
    } else {
      throw new Exception($mysqli->sqlstate . ' - ' . $mysqli->error);
    }
  } else {
    $data['retVal'] = $retVal;
    $data['error'] = $_FILES['file']['error'];
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
