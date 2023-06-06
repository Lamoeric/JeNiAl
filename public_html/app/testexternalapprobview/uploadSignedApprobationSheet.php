<?php
/*
Author : Eric Lamoureux
*/
  $data = array();
  $data['success'] = false;
  try {
    $uploads_dir = "../../../private/". $_SERVER['HTTP_HOST']."/images/externaltests/";
    // $uploads_dir = '../images';
    // $filename = $_FILES['file']['name'];
    $meta = $_POST;
    $language = $meta['language'];
    $destinationFileName = $uploads_dir . 'external_test_permission' . '_' . $language .'_' . $meta['testdirectorid'] . '.jpg';
    // $name = basename($_FILES["file"]["name"]);
    // $destination = "/uploads/$filename";
    $retVal = move_uploaded_file( $_FILES['file']['tmp_name'] , $destinationFileName);
    if ($retVal != 0) {
      $data['success'] = true;
      $data['message'] = "Move done.";
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
