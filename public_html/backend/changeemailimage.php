<?php
/*
Author : Eric Lamoureux
*/
require_once(__DIR__ . '/../../private/' . $_SERVER['HTTP_HOST'] . '/include/config.php');

$data = array();
$data['success'] = false;
try {
  $destinationFileName = dirname(__FILE__) . '/../../' . $privateimages . '/header.jpg';
  $retVal = move_uploaded_file($_FILES['file']['tmp_name'], $destinationFileName);
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
