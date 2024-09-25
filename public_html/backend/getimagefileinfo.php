<?php
/*
Author : Eric Lamoureux
*/

/**
 * This function returns the image file info for the specified image
 * 
 * $filename The file name of the file
 * 
 * Returns the data structure from the getImageSize php function if file exists or null otherwise
 */
function getImageFileInfo($filename) {
    if (isset($filename) && !empty($filename) && file_exists($filename)) {
        return getimagesize($filename);
    }
    return null;
};
?>
