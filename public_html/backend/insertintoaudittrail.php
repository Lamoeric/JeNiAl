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
 * $changedid   The ID of the object that was changed (default null)
 * $details     The details of that action (default null)
 * 
 * Returns a $data structure
 */
function insertIntoAuditTrail($mysqli, $userid, $progname, $action, $changedid = null, $details = null)
{
    $data = array();
    $data['success'] = false;
    $query = "INSERT INTO cpa_audit_trail (userid, progname, action";
    $values = ") VALUES ('$userid', '$progname', '$action'";
    if (!is_null($changedid)) {
        $query .= ", changedid";
        $values .= ", $changedid";
    }
    if (!is_null($details)) {
        $query .= ", details";
        $values .= ", '$details'";
    }
    $query .= $values . ")";
    if ($mysqli->query($query)) {
        $data['success'] = true;
        $data['message'] = 'Table cpa_audit_trail updated successfully.';
    } else {
        throw new Exception('insertIntoAuditTrail - ' . $mysqli->sqlstate . ' - ' . $mysqli->error);
    }
    return $data;
};
