<?php
/*
Author : Eric Lamoureux
*/

function createThumbnail($path_to_image_directory, $filename, $path_to_thumbs_directory, $final_width_of_image) {

  // require 'config.php';

  if(preg_match('/[.](jpg)$/', $filename)) {
    $im = imagecreatefromjpeg($path_to_image_directory . $filename);
  } else if (preg_match('/[.](gif)$/', $filename)) {
    $im = imagecreatefromgif($path_to_image_directory . $filename);
  } else if (preg_match('/[.](png)$/', $filename)) {
    $im = imagecreatefrompng($path_to_image_directory . $filename);
  }

  $ox = imagesx($im);
  $oy = imagesy($im);

  $nx = $final_width_of_image;
  $ny = floor($oy * ($final_width_of_image / $ox));

  $nm = imagecreatetruecolor($nx, $ny);

  imagecopyresized($nm, $im, 0,0,0,0,$nx,$ny,$ox,$oy);

  if(!file_exists($path_to_thumbs_directory)) {
    if(!mkdir($path_to_thumbs_directory)) {
      die("There was a problem. Please try again!");
    }
  }

  imagejpeg($nm, $path_to_thumbs_directory . $filename);
  // $tn = '<img src="' . $path_to_thumbs_directory . $filename . '" alt="image" />';
  // $tn .= '<br />Congratulations. Your file has been successfully uploaded, and a      thumbnail has been created.';
  return true;
}
?>
