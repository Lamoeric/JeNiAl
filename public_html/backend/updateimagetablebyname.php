<?php
/*
Author : Eric Lamoureux
*/

/**
 * This function updates the column imagefilename of a table using the column ID as a primary key
 * 
 * $tableName   table name to update
 * $name          Value of the id column to use for update
 * 
 * Returns a $data structure
 */
function updateImageTableByName($mysqli, $tablename, $partialfilename, $name) {
    $data = array();
    $data['success'] = false;
    if (isset($tablename) && isset($partialfilename) && isset($name)) {
        $query = "UPDATE ". $tablename . " SET imagefilename = '$partialfilename' WHERE name = '$name'";
        if ($mysqli->query($query)) {
            $data['success'] = true;
            $data['message'] = 'Table ' . $tablename . ' updated successfully.';
        } else {
            throw new Exception('updateImageTableByName - ' . $mysqli->sqlstate.' - '. $mysqli->error);
        }
    }
    return $data;
};
?>
