<?php
/*
Author : Eric Lamoureux
*/

/**
 * This function updates the column text of a table using the columns ID and LANGUAGE as a primary key
 * 
 * $tableName   table name to update
 * $id          Value of the id column to use for update
 * $language    Value of the id column to use for update
 * 
 * Returns a $data structure
 */
function updateTextTableByIdAndLanguage($mysqli, $tablename, $partialfilename, $id, $language) {
    $data = array();
    $data['success'] = false;
    $data['tablename'] = $tablename;
    $data['fonction'] = 'updateTextTableByIdAndLanguage';
    if (isset($tablename) && isset($partialfilename) && isset($id) && isset($language)) {
        $query = "UPDATE ". $tablename . " SET text = '$partialfilename' WHERE id = $id and language = '$language'";
        if ($mysqli->query($query)) {
            $data['success'] = true;
            $data['message'] = 'Table ' . $tablename . ' updated successfully.';
        } else {
            throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
        }
    }
    return $data;
};
?>
