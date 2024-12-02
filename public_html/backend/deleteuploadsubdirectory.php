<?php
/*
Author : Eric Lamoureux
*/
include_once(__DIR__.'./getuploaddirectory.php');

/**
 * This function empties and delete an upload subdir
 * 
 * $directorysuffix the last portion of the directory name (must start with /). Cannot be null.
 * 
 * Returns a directory name or null if $directorysuffix is not set
 */
function deleteUploadSubDirectory($directorysuffix) {
    if (isset($directorysuffix) && !empty($directorysuffix)) {
        $dir = getUploadDirectory($directorysuffix);
        if (is_dir($dir)) {
            $objects = scandir($dir);
            foreach ($objects as $object) {
                if ($object != "." && $object != "..") {
                    if (is_dir($dir . "/" . $object)) {
                        deleteUploadSubDirectory($directorysuffix . "/" . $object);
                    } else {
                        unlink($dir . "/" . $object);
                    }
                }
            }
            rmdir($dir);
        }
        return $dir;
    }
    return null;
};
?>
