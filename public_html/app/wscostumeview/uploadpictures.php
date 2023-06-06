<?php
/*
Author : Eric Lamoureux
*/
  require_once('../../../private/'. $_SERVER['HTTP_HOST'].'/include/config.php');
  require_once('../../backend/createthumbnail.php');

  $data = array();
  $data['success'] = false;
  try {
    $mainobj = $_POST['mainobj']; // This is the costume
    $id = (int)$mainobj['id'];
    // Set the directory names
    $uploads_dir   = '../../../private/'. $_SERVER['HTTP_HOST'].'/website/images/costumes/costumeid' . $id . '/';
    $thumbnail_dir = '../../../private/'. $_SERVER['HTTP_HOST'].'/website/images/costumes/costumeid' . $id . '/thumbnails'.'/';

    // To create the filename, we need to insert the record in database
    $query = "INSERT INTO cpa_ws_costumes_pictures(costumeid, pictureindex, imagefilename)
              VALUES ($id, 0, '')";
    if ($mysqli->query($query)) {
      if (empty($pictureid)) $pictureid = (int)$mysqli->insert_id;

      $partialfilename = 'picture_'.$pictureid.'.jpg';
      $destinationFileName = $uploads_dir . $partialfilename;
      $retVal = move_uploaded_file($_FILES['file']['tmp_name'], $destinationFileName);
      if ($retVal != 0) {
        // Lets create the thumbnail
        createThumbnail($uploads_dir, $partialfilename, $thumbnail_dir, 100);

      	$query = "UPDATE cpa_ws_costumes_pictures
                  join (select max(cwgp.pictureindex)+1 pictureidx from cpa_ws_costumes_pictures cwgp where cwgp.costumeid = $id) a
                  SET imagefilename = '$partialfilename',
                  pictureindex = a.pictureidx
                  WHERE id = $pictureid";
      	if ($mysqli->query($query)) {
      		$data['success'] = true;
      		$data['message'] = 'picture updated successfully.';
          echo json_encode($data);
          exit;
      	} else {
      		throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
      	}
      } else {
        $data['success'] = false;
        $data['message'] = "Move not done.";
      	echo json_encode($data);
        exit;
      }
    } else {
      throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
    }
  } catch (Exception $e) {
    $data = array();
    $data['success'] = false;
    $data['message'] = $e->getMessage();
    echo json_encode($data);
    exit;
  }
?>
