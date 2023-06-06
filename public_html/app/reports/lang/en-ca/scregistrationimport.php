<?php
//============================================================+
// File name   :
//
// Description : Language file for report
//
// Author			 : Eric lamoureux
//
//============================================================+

// Add basic/general texts
require_once('eng.php');

$l['w_title'] = "Skate Canada Registrations Import Report";
$l['w_title_converted'] = "Members changed";
$l['w_title_converteddesc'] = "Number of members who received a Skate Canada number in JeNiAl : ";
$l['w_title_nonuniqueSCno'] = "Non unique Skate Canada Number";
$l['w_title_nonuniqueSCnodesc'] = "More than one member has the same Skate Canada number. You must correct this situation for the process to fully work.";
$l['w_title_nonuniquemembers'] = "Non unique members";
$l['w_title_nonuniquemembersdesc'] = "More than one member has the same firstname-lastname. You must correct this situation for the process to fully work.";
$l['w_title_nonexistingmembers'] = "Non existing members";
$l['w_title_nonexistingmembersdesc'] = "Members registered at Skate Canada, but not existing in JeNiAl.";
$l['w_title_differentmembers'] = "Different members";
$l['w_title_differentmembersdesc'] = "Members with valid Skate Canada no, but with different firstname or lastname.";
$l['w_name'] = "Name";
$l['w_lastname'] = "Lastname";
$l['w_firstname'] = "Firstname";
$l['w_sclastname'] = "SC Lastname";
$l['w_scfirstname'] = "SC Firstname";
$l['w_skatecanadano'] = "SC No";
$l['w_registrationyear'] = "Year";
$l['w_monthNames'] = ["", "January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];
$l['w_filename'] = "SC_Registrations_Import";

?>
