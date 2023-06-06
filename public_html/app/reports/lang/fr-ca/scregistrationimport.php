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
require_once('fra.php');

$l['w_title'] = "Rapport d'importation des inscriptions de Patinage Canada";
$l['w_title_converted'] = "Membres changés";
$l['w_title_converteddesc'] = "Nombre de membres qui ont reçus un numéro de Patinage Canada dans JeNiAl: ";
$l['w_title_nonuniqueSCno'] = "No de Patinage Canada non unique";
$l['w_title_nonuniqueSCnodesc'] = "Plus d'un membre se partage le même numéro de Patinage Canada. Vous devez corriger cette situation pour que l'import fonctionne complètement.";
$l['w_title_nonuniquemembers'] = "Nom de membre non unique";
$l['w_title_nonuniquemembersdesc'] = "Plus d'un membre a la même combinaison prénom-nom. Vous devez corriger cette situation pour que l'import fonctionne complètement.";
$l['w_title_nonexistingmembers'] = "Membres inexistants";
$l['w_title_nonexistingmembersdesc'] = "Liste des membres inscrits à Patinage Canada, mais inexistants dans JeNiAl.";
$l['w_title_differentmembers'] = "Membres différents";
$l['w_title_differentmembersdesc'] = "Liste des membres dont le no de Patinage Canada est bon, mais dont le nom ou le prénom diffère.";
$l['w_name'] = "Nom";
$l['w_lastname'] = "Nom";
$l['w_firstname'] = "Pr&eacute;nom";
$l['w_sclastname'] = "Nom PC";
$l['w_scfirstname'] = "Pr&eacute;nom PC";
$l['w_skatecanadano'] = "No PC";
$l['w_registrationyear'] = "Année";
$l['w_monthNames'] = ["", "Janvier", "F&eacute;vrier", "Mars", "Avril", "Mai", "Juin", "Juillet", "Ao&ucirc;t", "Septembre", "Octobre", "Novembre", "D&eacute;cembre"];
$l['w_filename'] = "SC_Registrations_Import";

?>
