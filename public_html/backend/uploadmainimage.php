<?php
/*
Author : Eric Lamoureux
*/
require_once(__DIR__ .'/../../private/'. $_SERVER['HTTP_HOST'].'/include/config.php');
require_once(__DIR__ . '/uploadimageandupdatebyid.php');

$data = uploadImageAndUpdateById($mysqli, $_FILES, $_POST['subDirectory'], $_POST['filePrefix'], $_POST['oldFileName'], $_POST['tableName'], $_POST['id']);
echo json_encode($data);
?>