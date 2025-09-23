<?php
/*
Author : Eric Lamoureux
*/

function isStatusEqualNew($object) {
    if (isset($object['status']) and $object['status'] == 'New') {
        return true;
    }
    return false;
}

function isStatusEqualModified($object) {
    if (isset($object['status']) and $object['status'] == 'Modified') {
        return true;
    }
    return false;
}

function isStatusEqualDeleted($object) {
    if (isset($object['status']) and $object['status'] == 'Deleted') {
        return true;
    }
    return false;
}

?>