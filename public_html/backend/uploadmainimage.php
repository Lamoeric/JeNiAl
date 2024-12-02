<?php
/*
Author : Eric Lamoureux
*/
require_once(__DIR__ .'/../../private/'. $_SERVER['HTTP_HOST'].'/include/config.php');
require_once(__DIR__ . '/uploadimageandupdatebyid.php');
require_once(__DIR__ . '/uploadimageandupdatebyname.php');

$id = $_POST['id'];
if (!isset($id) || empty($id)) {
    $data = uploadImageAndUpdateByName($mysqli, $_FILES, $_POST['subDirectory'], $_POST['filePrefix'], $_POST['oldFileName'], $_POST['tableName'], $_POST['name']);
} else {
    $data = uploadImageAndUpdateById($mysqli, $_FILES, $_POST['subDirectory'], $_POST['filePrefix'], $_POST['oldFileName'], $_POST['tableName'], $_POST['id'], $_POST['language'], $_POST['pattern']);
}
echo json_encode($data);
?>