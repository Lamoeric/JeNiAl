<?php
/*
Author : Eric Lamoureux
*/
include_once(__DIR__.'./getuploaddirectory.php');

/**
 * This function creates and returns an upload sub directory.
 * 
 * $directorysuffix the last portion of the directory name (must start with /). Cannot be null.
 * 
 * Returns a directory name or null if $directorysuffix is not set
 */
function createUploadSubDirectory($directorysuffix) {
    if (isset($directorysuffix) && !empty($directorysuffix)) {
        $newDir = getUploadDirectory($directorysuffix);
        if (!file_exists($newDir)) {
            mkdir($newDir);
        }
        return $newDir;
    }
    return null;
};
?>
