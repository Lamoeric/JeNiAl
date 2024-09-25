<?php
/*
Author : Eric Lamoureux
*/

/**
 * This function returns the image file name based on the upload directory + directory suffix + filename
 * 
 * $directorysuffix the last part of the directory to concatenate with the upload directory
 * $filename The file name of the file
 * 
 * Returns the complete name of the file or null if $filename is not set or file does not exists
 */
require_once(__DIR__.'./getuploaddirectory.php');

function getImageFileName($directorysuffix, $filename) {
    if (isset($filename) && !empty($filename)) {
        $finalFileName = getUploadDirectory($directorysuffix). $filename;
        if (file_exists($finalFileName)) {
            return $finalFileName;
        }
    }
    return null;
};
?>
