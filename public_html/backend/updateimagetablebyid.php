<?php
/*
Author : Eric Lamoureux
*/

/**
 * This function updates the column imagefilename of a table using the column ID as a primary key
 * 
 * $tableName   table name to update
 * $id          Value of the id column to use for update
 * 
 * Returns a $data structure
 */
function updateImageTableById($mysqli, $tablename, $partialfilename, $id) {
    $data = array();
    $data['success'] = false;
    if (isset($tablename) && isset($partialfilename) && isset($id)) {
        $query = "UPDATE ". $tablename . " SET imagefilename = '$partialfilename' WHERE id = $id";
        if ($mysqli->query($query)) {
            $data['success'] = true;
            $data['message'] = 'Table ' . $tablename . ' updated successfully.';
        } else {
            throw new Exception('updateimagetablebyid - ' . $mysqli->sqlstate.' - '. $mysqli->error);
        }
    }
    return $data;
};
?>
