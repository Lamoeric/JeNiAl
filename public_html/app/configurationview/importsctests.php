<?php
/*
Author : Eric Lamoureux
*/
  require_once('../../../private/'. $_SERVER['HTTP_HOST'].'/include/config.php');

  // $table = array(
  //     'Š'=>'S', 'š'=>'s', 'Đ'=>'Dj', 'đ'=>'dj', 'Ž'=>'Z', 'ž'=>'z', 'Č'=>'C', 'č'=>'c', 'Ć'=>'C', 'ć'=>'c',
  //     'À'=>'A', 'Á'=>'A', 'Â'=>'A', 'Ã'=>'A', 'Ä'=>'A', 'Å'=>'A', 'Æ'=>'A', 'Ç'=>'C', 'È'=>'E', 'É'=>'E',
  //     'Ê'=>'E', 'Ë'=>'E', 'Ì'=>'I', 'Í'=>'I', 'Î'=>'I', 'Ï'=>'I', 'Ñ'=>'N', 'Ò'=>'O', 'Ó'=>'O', 'Ô'=>'O',
  //     'Õ'=>'O', 'Ö'=>'O', 'Ø'=>'O', 'Ù'=>'U', 'Ú'=>'U', 'Û'=>'U', 'Ü'=>'U', 'Ý'=>'Y', 'Þ'=>'B', 'ß'=>'Ss',
  //     'à'=>'a', 'á'=>'a', 'â'=>'a', 'ã'=>'a', 'ä'=>'a', 'å'=>'a', 'æ'=>'a', 'ç'=>'c', 'è'=>'e', 'é'=>'e',
  //     'ê'=>'e', 'ë'=>'e', 'ì'=>'i', 'í'=>'i', 'î'=>'i', 'ï'=>'i', 'ð'=>'o', 'ñ'=>'n', 'ò'=>'o', 'ó'=>'o',
  //     'ô'=>'o', 'õ'=>'o', 'ö'=>'o', 'ø'=>'o', 'ù'=>'u', 'ú'=>'u', 'û'=>'u', 'ý'=>'y', 'ý'=>'y', 'þ'=>'b',
  //     'ÿ'=>'y', 'Ŕ'=>'R', 'ŕ'=>'r',
  // );
  $data = array();
  $data['success'] = false;
  // $data['nonuniquemembers'] = array();
  // $data['nonuniqueSCno'] = array();
  // $data['nonexistingmembers'] = array();
  // $data['matches'] = array();
  // $data['differentmembers'] = array();
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

  	$query = "DELETE FROM cpa_skate_canada_tests";
  	if ($mysqli->query($query)) {
  		$data['success'] = true;
  		$data['cleanup'] = 'Table has been cleaned.';
  	} else {
  		throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
  	}

    $query = "ALTER TABLE cpa_skate_canada_tests AUTO_INCREMENT = 1";
    if (!$mysqli->query($query)) {
  		throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
  	}
    $firstline = true;
    while (($line = fgetcsv($handle, 1000, ";")) !== false) {
      if (!$firstline) {
        // $filesop =  array_map("utf8_encode", $line);
        $filesop =  $line;
        $skatecanadano = $filesop[0];
        $contact = str_replace("'", "\'", $filesop[1]);
        // $firstname = str_replace("'", "\'", $filesop[2]);
        // $lastname = str_replace("'", "\'", $filesop[3]);
        $testname = str_replace("'", "\'", $filesop[2]);;
        $testdate = $filesop[3];
        $teststatus = $filesop[4];

        $query = "INSERT INTO cpa_skate_canada_tests (id, memberid, skatecanadano, name, testname, testdate, teststatus)
                  VALUES (null, null, '$skatecanadano', '$contact', '$testname', '$testdate', '$teststatus')";
      	if (!$mysqli->query($query)) {
      		throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
      	}
      } else {
        $firstline = false;
      }
    }

    // Update cpa_skate_canada_tests, set the memberid (exclude skatecanadano used by more than one members)
    $query = "UPDATE cpa_skate_canada_tests csct
              SET csct.memberid = (SELECT id FROM cpa_members cm
                                   WHERE cm.skatecanadano = csct.skatecanadano AND cm.skatecanadano is not null AND cm.skatecanadano != '' and cm.skatecanadano not in
                                    (SELECT skatecanadano
                                      from cpa_members
                                      WHERE skatecanadano is not null AND skatecanadano != ''
                                      group by skatecanadano
                                      having count(*) > 1)
                                  )
              WHERE csct.memberid is null";
    if (!$mysqli->query($query)) {
  		throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
  	}

    // Update cpa_skate_canada_tests, set the testid
    $query = "UPDATE cpa_skate_canada_tests csct
              SET csct.testid = (SELECT ctse.testsid
                                 FROM cpa_tests_sc_equivalent ctse
                                 WHERE ctse.testname = csct.testname)";
    if (!$mysqli->query($query)) {
  		throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
  	}

    // Update cpa_skate_canada_tests, set the membertestid for the tests that are exactly equal (same test, same date, same result)
    $query = "UPDATE cpa_skate_canada_tests csct
              SET membertestid = (SELECT cmt.id
                                  FROM cpa_members_tests cmt
                                  WHERE cmt.memberid = csct.memberid
                                  AND cmt.testid = csct.testid
                                  AND cmt.testdate = csct.testdate
                                  AND ((teststatus = 'Re-Try' AND success = 2) OR (teststatus = 'Pass' AND (success = 1 OR success = 5)))
                                 )
               WHERE membertestid is null;";
    if (!$mysqli->query($query)) {
  		throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
  	}

    // Update cpa_skate_canada_tests, set importstatus = 1 (perfect match) where membertestid is not null
    $query = "UPDATE cpa_skate_canada_tests csct
              SET importstatus = 1
              WHERE membertestid is not null";
    if (!$mysqli->query($query)) {
  		throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
  	}

    // Update cpa_skate_canada_tests, set the membertestid for the tests that are not exactly equal (same test, same date, DIFFERENT result)
    $query = "UPDATE cpa_skate_canada_tests csct
              SET membertestid = (SELECT cmt.id
                                  FROM cpa_members_tests cmt
                                  WHERE cmt.memberid = csct.memberid
                                  AND cmt.testid = csct.testid
                                  AND cmt.testdate = csct.testdate
                                  AND ((teststatus = 'Re-Try' AND success != 2) OR (teststatus = 'Pass' AND success != 2 AND success != 5))
                                 )
              WHERE membertestid is null;";
    if (!$mysqli->query($query)) {
  		throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
  	}

    // Update cpa_skate_canada_tests, set importstatus = 2 (same test, same date, DIFFERENT result) where membertestid is not null
    $query = "UPDATE cpa_skate_canada_tests csct
              SET importstatus = 2
              WHERE membertestid is not null
              AND importstatus is null";
    if (!$mysqli->query($query)) {
  		throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
  	}

    // Update cpa_skate_canada_tests, set the membertestid for the tests that are not exactly equal (same test, DIFFERENT date, same result - PASS only!)
    // A test can only be passed once, so if it exists in both list with a PASS result, both dates should be the same
    $query = "UPDATE cpa_skate_canada_tests csct
              SET membertestid = (SELECT cmt.id
                                  FROM cpa_members_tests cmt
                                  WHERE cmt.memberid = csct.memberid
                                  AND cmt.testid = csct.testid
                                  AND cmt.testdate != csct.testdate
                                  AND (teststatus = 'Pass' AND (success = 1 OR success = 5))
                                 )
              WHERE membertestid is null;";
    if (!$mysqli->query($query)) {
  		throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
  	}

    // Update cpa_skate_canada_tests, set importstatus = 3 (same test, different date, same result - PASS only) where membertestid is not null
    $query = "UPDATE cpa_skate_canada_tests csct
              SET importstatus = 3
              WHERE membertestid is not null
              AND importstatus is null";
    if (!$mysqli->query($query)) {
  		throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
  	}

    // Update cpa_skate_canada_tests, set importstatus = 4 (tests passed in SC but do not exist in JeNiAl)
    $query = "UPDATE cpa_skate_canada_tests csct
              SET importstatus = 4
              where importstatus is null
              and teststatus = 'Pass'
              and testid is not null
              and membertestid is null";
    if (!$mysqli->query($query)) {
  		throw new Exception($mysqli->sqlstate.' - '. $mysqli->error);
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
