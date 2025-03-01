<?php
/*
Author : Eric Lamoureux
*/

/**
 * This function inserts a row in the cpa_audit_trail
 * 
 * $userid      The user id that is doing the action
 * $progname    The program in which the action is taking place
 * $action      The action the user is doing
 * $details     The details of that action
 * 
 * Returns a $data structure
 */
function insertIntoAuditTrail($mysqli, $userid, $progname, $action, $details) {
    $data = array();
    $data['success'] = false;
    $query = "INSERT INTO cpa_audit_trail (userid, progname, action, details) VALUES ('$userid', '$progname', '$action', '$details')";
    if ($mysqli->query($query)) {
        $data['success'] = true;
        $data['message'] = 'Table cpa_audit_trail updated successfully.';
    } else {
        throw new Exception('insertIntoAuditTrail - ' . $mysqli->sqlstate.' - '. $mysqli->error);
    }
    return $data;
};
?>
