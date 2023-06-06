<?php
/*
Author : Eric Lamoureux
*/
  require_once('../../../private/'. $_SERVER['HTTP_HOST'].'/include/config.php');

  $data = array();
  $data['success'] = false;
  $data['nonuniquemembers'] = array();
  $data['nonuniqueSCno'] = array();
  $data['nonexistingmembers'] = array();
  $data['matches'] = array();
  $data['differentmembers'] = array();
  try {
    $ok = true;
    $file = $_FILES['file']['tmp_name'];
    if ($file == NULL) {
      $data['success'] = false;
      $data['message'] = "Please select a file to import";
      echo json_encode($data);
      exit;
    } else {
      $data['file'] = "ok";
    }
    $handle = fopen($file, "r");
    $data['handle'] = "ok";

  	$query = "DELETE FROM cpa_skate_canada_registrations";
  	if ($mysqli->query($query)) {
  		$data['success'] = true;
  		$data['cleanup'] = 'Table has been cleaned.';
  	} else {
  		throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
  	}

    $query = "ALTER TABLE cpa_skate_canada_registrations AUTO_INCREMENT = 1";
    if (!$mysqli->query($query)) {
  		throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
  	}
    $firstline = true;
    while (($line = fgetcsv($handle, 1000, ";")) !== false) {
      if (!$firstline) {
        // $filesop =  array_map("utf8_encode", $line);
        $filesop =  $line;
        $skatecanadano = $filesop[0];
        $contact = $filesop[1];
        $firstname = str_replace("'", "\'", $filesop[2]);
        $lastname = str_replace("'", "\'", $filesop[3]);
        $registrationyear = $filesop[4];
        $email = $filesop[5];
        $orderno = $filesop[6];

        $query = "INSERT INTO cpa_skate_canada_registrations (id, memberid, skatecanadano, firstname, lastname, registrationyear, orderno)
                  VALUES (null, null, '$skatecanadano', '$firstname', '$lastname', '$registrationyear', '$orderno')";
      	if (!$mysqli->query($query)) {
      		throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
      	}
      } else {
        $firstline = false;
      }
    }

    // TODO : we need to analyse the file and find all skaters and create a report
    // 0 - check if there are skate canada numbers that are used by more than one member, if so, eliminate them from the update.
    $query = "SELECT skatecanadano, count(*) FROM cpa_members WHERE skatecanadano != '' GROUP BY skatecanadano HAVING count(*) > 1";
    $result = $mysqli->query($query);
    while ($row = $result->fetch_assoc()) {
      $data['nonuniqueSCno'][] = $row;
    }

    // 1 - connect members by skate Canada number
    $query = "UPDATE cpa_skate_canada_registrations cscr
              SET cscr.memberid = (SELECT id FROM cpa_members cm
                                   WHERE cm.skatecanadano = cscr.skatecanadano AND skatecanadano is not null AND skatecanadano != '' and cm.skatecanadano not in
                                    (SELECT skatecanadano
                                      from cpa_members
                                      WHERE skatecanadano is not null AND skatecanadano != ''
                                      group by skatecanadano
                                      having count(*) > 1)
                                  )
              WHERE cscr.memberid is null";
    if (!$mysqli->query($query)) {
  		throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
  	}

    // 1a - find all mebers with same SC no but with different fisrtname or lastname
    $query = "SELECT distinct cscr.skatecanadano, cm.firstname, cm.lastname, cscr.firstname sc_firstname, cscr.lastname sc_lastname
              FROM cpa_members cm
              JOIN cpa_skate_canada_registrations cscr ON cscr.skatecanadano = cm.skatecanadano
              WHERE cm.firstname != cscr.firstname OR cm.lastname != cscr.lastname";
    $result = $mysqli->query($query);
		while ($row = $result->fetch_assoc()) {
      $data['differentmembers'][] = $row;
    }

    // 2 - for all members without a memberid, try
    //      to match based on firstname-lastname, if match, put in match return array (id, skatecanadano, firstname, lastname, possiblememberid)
    //          careful with members registered in several years, don't put twice in array.
    //      if not, put in reject array
    $query = "SELECT id, firstname, lastname, registrationyear, skatecanadano FROM cpa_skate_canada_registrations WHERE memberid is null ORDER BY registrationyear DESC, lastname, firstname";
		$result = $mysqli->query($query);
		while ($row = $result->fetch_assoc()) {
      $sc_firstname = str_replace("'", "\'", $row['firstname']);
      $sc_lastname = str_replace("'", "\'", $row['lastname']);
      $registrationyear = str_replace("'", "\'", $row['registrationyear']);
      $skatecanadano = str_replace("'", "\'", $row['skatecanadano']);
      // Make sure this combination firstname-lastname is unique
      $query = "SELECT count(*) cnt FROM cpa_members WHERE firstname = '$sc_firstname' and lastname = '$sc_lastname'";
      $result2 = $mysqli->query($query);
      $row2 = $result2->fetch_assoc();
      // this combination firstname-lastname is unique
      if ((int)$row2['cnt'] == 1) {
        $query = "SELECT id, firstname, lastname FROM cpa_members WHERE firstname = '$sc_firstname' and lastname = '$sc_lastname' LIMIT 1";
        $result2 = $mysqli->query($query);
        $row2 = $result2->fetch_assoc();
        if (isset($row2['id'])) {
          $proposedmemberid = $row2['id'];
          $row['proposedmemberid'] = $row2['id'];
          $data['matches'][] = $row;
          $query = "UPDATE cpa_members SET skatecanadano = '$skatecanadano' WHERE id = $proposedmemberid";
          if (!$mysqli->query($query)) {
        		throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
        	}
        }
      } else if ((int)$row2['cnt'] == 0) {
        $row2['firstname'] = $sc_firstname;
        $row2['lastname'] = $sc_lastname;
        $row2['registrationyear'] = $registrationyear;
        $row2['skatecanadano'] = $skatecanadano;
        $data['nonexistingmembers'][] = $row2;
      } else {
        $row2['firstname'] = $sc_firstname;
        $row2['lastname'] = $sc_lastname;
        $data['nonuniquemembers'][] = $row2;
      }
		}
    $data['success'] = true;
  	echo json_encode($data);
    exit;
  } catch (Exception $e) {
    $data = array();
    $data['success'] = false;
    $data['message'] = $e->getMessage();
    echo json_encode($data);
    exit;
  }
?>
