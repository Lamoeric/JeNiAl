<?php
/*
Author : Eric Lamoureux
*/

require_once(__DIR__ . '/createdestinationfilename.php');
require_once(__DIR__ . '/getuploaddirectory.php');
require_once(__DIR__ . '/removefile.php');
require_once(__DIR__ . '/updateimagetablebyname.php');

/**
 * This function upload the image and updates the column imagefilename of a table using a column NAME as a primary key
 * 
 * $mysqli          
 * $files           
 * $directorySuffix 
 * $filenameprefix  
 * $oldfilename     
 * $tableName       Table name to update
 * $name              Value of the id column to use for update
 * 
 * Returns a $data structure
 */
function uploadImageAndUpdateByName($mysqli, $files, $directorySuffix, $filenameprefix, $oldfilename, $tableName, $name)
{
    $data = array();
    $data['success'] = false;
    $data['directorySuffix'] = $directorySuffix;
    $data['filenameprefix'] = $filenameprefix;
    $data['tableName'] = $tableName;
    $data['name'] = $name;

    try {
        $uploads_dir = getUploadDirectory($directorySuffix);
        $filenames = createDestinationFileName($uploads_dir, $filenameprefix);
        $retVal = move_uploaded_file($files['file']['tmp_name'], $filenames['destinationFileName']);
        if ($retVal != 0) {
            $data['update'] = updateImageTableByName($mysqli, $tableName, $filenames['partialFileName'], $name);
            if ($data['update']['success'] == true) {
                // Remove old file if copy and update are done
                $data['removedfilename'] = removeFile($uploads_dir, $oldfilename, true);
                $data['uploads_dir'] = $uploads_dir;
                $data['oldfilename'] = $oldfilename;
                $data['newfilename'] = $filenames['partialFileName'];
            } else {
                $data['success'] = false;
                $data['message'] = "Update not done.";
                return $data;
            }
        } else {
            $data['success'] = false;
            $data['move_returnvalue'] = $retVal;
            $data['message'] = "Move not done.";
            return $data;
        }
        $data['success'] = true;
        $data['message'] = "Image updated";
        return $data;
    } catch (Exception $e) {
        $data = array();
        $data['success'] = false;
        $data['message'] = 'uploadImageAndUpdateByName - ' . $name . ' - ' . $e->getMessage();
        return $data;
    }
    return $data;
};
?>
