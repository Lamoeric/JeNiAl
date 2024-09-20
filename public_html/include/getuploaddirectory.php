<?php
/*
Author : Eric Lamoureux
*/

/**
 * This function returns the upload directory for the current CPA
 * 
 * $lastPortion the last portion of the directory name (must start with /)
 * 
 * Returns a directory
 */
function getUploadDirectory($lastPortion=null) {
    $uploads_dir = __DIR__ . '/../../private/'. $_SERVER['HTTP_HOST'].(isset($lastPortion) ? $lastPortion : '');
    return $uploads_dir;
};
?>
