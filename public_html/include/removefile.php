<?php
/*
Author : Eric Lamoureux
*/

/**
 * This function removes a file by name
 * 
 * $uploads_dir     The directory where to find the file. If null, nothing is done.
 * $filename        The file to remove. If null, nothing is done.
 * 
 */
function removeFile($directory, $filename) {
    if (isset($directory) && !empty($directory) && isset($filename) && !empty($filename)) {
        $oldfilename = $directory . $filename;
        if (file_exists($oldfilename)) {
            unlink($oldfilename);
        }
    }
};
?>
