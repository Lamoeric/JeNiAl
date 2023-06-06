<?php
/*
Author : Eric Lamoureux
*/
  // require_once('../../../private/'. $_SERVER['HTTP_HOST'].'/include/config.php');

  $data = array();
  $data['success'] = false;
  try {
    $uploads_dir = '../../../private/'. $_SERVER['HTTP_HOST'].'/website/images/';
    $mainobj = $_POST['mainobj'];
    $filename = $_POST['filename'];
    if (isset($mainobj['sliderfilename']) && !empty($mainobj['sliderfilename'])) {
      $oldfilename = $uploads_dir . $mainobj['sliderfilename'];
      if (file_exists($oldfilename)) {
          unlink($oldfilename);
      }
    }

    if(preg_match('/[.](jpg)$/', $filename)) {
      $partialfilename = 'slider.jpg';
    } else if (preg_match('/[.](gif)$/', $filename)) {
      $partialfilename = 'slider.gif';
    } else if (preg_match('/[.](png)$/', $filename)) {
      $partialfilename = 'slider.png';
    }

    $destinationFileName = $uploads_dir . $partialfilename;
    $retVal = move_uploaded_file( $_FILES['file']['tmp_name'] , $destinationFileName);
    if ($retVal != 0) {
  		$data['success'] = true;
  		$data['message'] = 'Slider updated successfully.';
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
