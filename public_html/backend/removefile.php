<?php
/*
Author : Eric Lamoureux
*/

require_once(__DIR__ . '/getuploaddirectory.php');

/**
 * This function removes a file by name
 * 
 * $uploads_dir     The directory where to find the file. If null, nothing is done.
 * $filename        The file to remove. If null, nothing is done.
 * $fulldir         True if $directory is the full directory, false if it's only the last portion
 * 
 */
function removeFile($directory, $filename, $fulldir) {
    if (isset($directory) && !empty($directory) && isset($filename) && !empty($filename)) {
        if ($fulldir == false) {
            $directory = getUploadDirectory($directory);
        }
        $oldfilename = $directory . $filename;
        if (file_exists($oldfilename)) {
            unlink($oldfilename);
            return $oldfilename;
        }
    }
    return null;
};
?>
