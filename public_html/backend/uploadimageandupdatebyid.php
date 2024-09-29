<?php
/*
Author : Eric Lamoureux
*/

require_once(__DIR__ . '/createdestinationfilename.php');
require_once(__DIR__ . '/getuploaddirectory.php');
require_once(__DIR__ . '/removefile.php');
require_once(__DIR__ . '/updateimagetablebyid.php');
require_once(__DIR__ . '/updatetexttablebyidandlanguage.php');

/**
 * This function upload the image and updates the column imagefilename of a table using a column ID as a primary key
 * 
 * $mysqli          
 * $files           
 * $directorySuffix 
 * $filenameprefix  
 * $oldfilename     
 * $tableName       Table name to update
 * $id              Value of the id column to use for update
 * $language        Language for the file, either 'fr-ca' or 'en-ca' or null
 * $pattern         If 1, update the column imagefilename using id, if 2, update the column text using id and language 
 * 
 * Returns a $data structure
 */
function uploadImageAndUpdateById($mysqli, $files, $directorySuffix, $filenameprefix, $oldfilename, $tableName, $id, $language=null, $pattern=1)
{
    $data = array();
    $data['success'] = false;
    $data['directorySuffix'] = $directorySuffix;
    $data['filenameprefix'] = $filenameprefix;
    $data['tableName'] = $tableName;
    $data['id'] = $id;
    $data['language'] = $language;
    $data['pattern'] = $pattern;

    try {
        $uploads_dir = getUploadDirectory($directorySuffix);
        $filenames = createDestinationFileName($uploads_dir, $filenameprefix);
        $retVal = move_uploaded_file($files['file']['tmp_name'], $filenames['destinationFileName']);
        if ($retVal != 0) {
            if ($pattern == 1) {
                $data['update'] = updateImageTableById($mysqli, $tableName, $filenames['partialfilename'], $id);
            } else if ($pattern == 2) {
                $data['update'] = updatetexttablebyidAndLanguage($mysqli, $tableName, $filenames['partialfilename'], $id, $language);
            }
            if ($data['update']['success'] == true) {
                // Remove old file if copy and update are done
                $data['removedfilename'] = removeFile($uploads_dir, $oldfilename, true);
                $data['uploads_dir'] = $uploads_dir;
                $data['oldfilename'] = $oldfilename;
                $data['newfilename'] = $filenames['partialfilename'];
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
        $data['message'] = $e->getMessage();
        return $data;
    }
    return $data;
};
?>
