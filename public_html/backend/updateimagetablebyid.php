<?php
/*
Author : Eric Lamoureux
*/

/**
 * This function updates the column imagefilename of a table using a column ID as a primary key
 * 
 * $tableName   table name to update
 * $id          Value of the id column to use for update
 * 
 * Returns a $data structure
 */
function updateimagetablebyid($mysqli, $tableName, $partialfilename, $id) {
    $data = array();
    $data['success'] = false;
    $query = "UPDATE ". $tableName . " SET imagefilename = '$partialfilename' WHERE id = $id";
    if ($mysqli->query($query)) {
        $data['success'] = true;
        $data['message'] = 'Table ' . $tableName . ' updated successfully.';
    } else {
        throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
    }
    return $data;
};
?>
