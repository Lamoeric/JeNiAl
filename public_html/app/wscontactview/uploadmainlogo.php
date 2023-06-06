<?php
/*
Author : Eric Lamoureux
*/
  // require_once('../../../private/'. $_SERVER['HTTP_HOST'].'/include/config.php');

  $data = array();
  $data['success'] = false;
  try {
    $uploads_dir = '../../../private/'. $_SERVER['HTTP_HOST'].'/website/images/';

    $meta = $_POST;
    // $language = $meta['language'];
    $mainobj = $meta['mainobj'];
    $filename = $meta['filename'];
    if (isset($mainobj['logofilename']) && !empty($mainobj['logofilename'])) {
      $oldfilename = $uploads_dir . $mainobj['logofilename'];
      if (file_exists($oldfilename)) {
          unlink($oldfilename);
      }
    }

    if(preg_match('/[.](jpg)$/', $filename)) {
      $partialfilename = 'logo_cpa.jpg';
    } else if (preg_match('/[.](gif)$/', $filename)) {
      $partialfilename = 'logo_cpa.gif';
    } else if (preg_match('/[.](png)$/', $filename)) {
      $partialfilename = 'logo_cpa.png';
    }

    $destinationFileName = $uploads_dir . $partialfilename;
    $retVal = move_uploaded_file( $_FILES['file']['tmp_name'] , $destinationFileName);
    if ($retVal != 0) {
  		$data['success'] = true;
  		$data['message'] = 'Logo updated successfully.';
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
