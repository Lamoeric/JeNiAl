<?php
/*
* Author : Eric Lamoureux
*
* File contains function to get all info for the navbar and the current page
*
*/
require_once('../../../private/'. $_SERVER['HTTP_HOST'].'/include/config.php');

// $data = array();
// $data['success'] = true;
// echo json_encode($data);
// exit;
getcurrentpageinfo($mysqli, $_POST['pagename'], $_POST['language'], $_POST['costumeid'], $_POST['testsessionid'], $_POST['previewmode']);

function getpagesections($mysqli, $pagename, $language, $previewmode) {
	if ($previewmode) {
	  $query = "SELECT cws.name, cws.label, cws.navbarlabel, cws.title, cws.subtitle, cws.imagefilename, cws.imageind, cws.subtitleind, cws.paragraphind, cws.linkind,
	  								 cwps.pagename, cwps.sectionname, cwps.pagesectionindex, cwps.visiblepreview visible, cwps.visiblenavbarpreview visibleinnavbar, cwps.groupname, 
	  								 getWSTextLabel(cws.navbarlabel, '$language') navbarlabel 
	            FROM cpa_ws_sections cws
	            JOIN cpa_ws_pages_sections cwps ON cwps.sectionname = cws.name
	            WHERE cwps.pagename = '$pagename'
	            AND cwps.visiblepreview = 1
	            AND cwps.visiblenavbarpreview = 1
	            ORDER BY cwps.pagesectionindex";
	} else {
	  $query = "SELECT *, getWSTextLabel(cws.navbarlabel, '$language') navbarlabel
	            FROM cpa_ws_sections cws
	            JOIN cpa_ws_pages_sections cwps ON cwps.sectionname = cws.name
	            WHERE cwps.pagename = '$pagename'
	            AND cwps.visible = 1
	            AND cwps.visibleinnavbar = 1
	            ORDER BY cwps.pagesectionindex";
	}
  $result = $mysqli->query($query);
  $data = array();
  $data['data'] = array();
  while ($row = $result->fetch_assoc()) {
    $data['data'][] = $row;
  }
  $data['success'] = true;
  return $data;
  exit;
}

function getpartnerssection($mysqli, $language, $previewmode) {
  $query = "SELECT *, getWSTextLabel(cwp.imagefilename, '$language') imagefilename, getWSTextLabel(cwp.link, '$language') link
            FROM cpa_ws_partners cwp
            WHERE cwp.publish = 1
            ORDER BY cwp.partnerindex";
  $result = $mysqli->query($query);
  $data = array();
  $data['data'] = array();
  while ($row = $result->fetch_assoc()) {
    $data['data'][] = $row;
  }
  $data['success'] = true;
  return $data;
  exit;
}

function getprogramassistantsection($mysqli, $language, $previewmode) {
  $query = "SELECT firstname, lastname, concat(firstName, ' ', lastname) fullname, imagefilename
            FROM cpa_ws_programassistants
            WHERE publish = 1
            ORDER BY lastname, firstname";
  $result = $mysqli->query($query);
  $data = array();
  $data['data'] = array();
  while ($row = $result->fetch_assoc()) {
    // $row['imagefilename'] = $row['imagefilename'] . '?' . com_create_guid();
    $data['data'][] = $row;
  }
  $data['success'] = true;
  return $data;
  exit;
}

function getboardmembersection($mysqli, $language, $previewmode) {
  $query = "SELECT cwpa.*, getWSTextLabel(cwpa.memberrole, '$language') memberrole, getWSTextLabel(cwpa.description, '$language') description
            FROM cpa_ws_boardmembers cwpa
            WHERE publish = 1
            ORDER BY memberindex";
  $result = $mysqli->query($query);
  $data = array();
  $data['data'] = array();
  while ($row = $result->fetch_assoc()) {
    // $row['imagefilename'] = $row['imagefilename'] . '?' . com_create_guid();
    $data['data'][] = $row;
  }
  $data['success'] = true;
  return $data;
  exit;
}

function getcoachsection($mysqli, $language, $previewmode) {
  $query = "SELECT cwc.*, getWSTextLabel(cwc.availabilitytext, '$language') availabilitytext, 
                  getWSTextLabel(cwc.competitivetext, '$language') competitivetext,
                  if (cwc.starversion=1, getCodeDescription('testlevels', cwc.dancelevel, '$language'), getCodeDescription('testnewlevels', cwc.dancelevel, '$language')) dancelevellabel, 
                  if (cwc.starversion=1, getCodeDescription('testlevels', cwc.skillslevel, '$language'), getCodeDescription('testnewlevels', cwc.skillslevel, '$language')) skillslevellabel,
                  if (cwc.starversion=1, getCodeDescription('testlevels', cwc.freestylelevel, '$language'), getCodeDescription('testnewlevels', cwc.freestylelevel, '$language')) freestylelevellabel,
                  getCodeDescription('testlevels', cwc.interpretativesinglelevel, '$language') interpretativesinglelevellabel,
                  getCodeDescription('testlevels', cwc.interpretativecouplelevel, '$language') interpretativecouplelevellabel,
                  getCodeDescription('testlevels', cwc.competitivesinglelevel, '$language') competitivesinglelevellabel,
                  getCodeDescription('testlevels', cwc.competitivecouplelevel, '$language') competitivecouplelevellabel,
                  getCodeDescription('testlevels', cwc.competitivedancelevel, '$language') competitivedancelevellabel,
                  getCodeDescription('testlevels', cwc.competitivesynchrolevel, '$language') competitivesynchrolevellabel,
                  getCodeDescription('testnewlevels', cwc.artisticlevel, '$language') artisticlevellabel,
                  getCodeDescription('testnewlevels', cwc.synchrolevel, '$language') synchrolevellabel
            FROM cpa_ws_coaches cwc
            WHERE publish = 1
            ORDER BY coachindex";
  $result = $mysqli->query($query);
  $data = array();
  $data['data'] = array();
  $data['maxinfo'] = 0;
  $data['starversion'] = 0;
  while ($row = $result->fetch_assoc()) {
    $row['starversion'] = (int)$row['starversion'];
    if ($row['starversion'] == 1) $data['starversion'] = ($data['starversion'] == 0 ? 1 : ($data['starversion'] == 1 ? 1 : 3));
    if ($row['starversion'] == 2) $data['starversion'] = ($data['starversion'] == 0 ? 2 : ($data['starversion'] == 2 ? 2 : 3));
    if (isset($row['interpretativesinglelevellabel']) && $row['interpretativesinglelevellabel'] != "")  $data['maxinfo'] = ($data['maxinfo'] >= 1 ? $data['maxinfo'] : 1);
    if (isset($row['interpretativecouplelevellabel']) && $row['interpretativecouplelevellabel'] != "")  $data['maxinfo'] = ($data['maxinfo'] >= 2 ? $data['maxinfo'] : 2);
    if (isset($row['competitivesinglelevellabel']) && $row['competitivesinglelevellabel'] != "")        $data['maxinfo'] = ($data['maxinfo'] >= 3 ? $data['maxinfo'] : 3);
    if (isset($row['competitivecouplelevellabel']) && $row['competitivecouplelevellabel'] != "")        $data['maxinfo'] = ($data['maxinfo'] >= 4 ? $data['maxinfo'] : 4);
    if (isset($row['competitivedancelevellabel']) && $row['competitivedancelevellabel'] != "")          $data['maxinfo'] = ($data['maxinfo'] >= 5 ? $data['maxinfo'] : 5);
    if (isset($row['competitivesynchrolevellabel']) && $row['competitivesynchrolevellabel'] != "")      $data['maxinfo'] = ($data['maxinfo'] >= 6 ? $data['maxinfo'] : 6);
    $data['data'][] = $row;
  }
  $data['success'] = true;
  return $data;
  exit;
}

function getsectionparagraphs($mysqli, $sectionname, $language, $previewmode) {
	if ($previewmode) {
	  $query = "SELECT cwsp.id, cwsp.sectionname, cwsp.paragraphindex, cwsp.title, cwsp.subtitle, cwsp.paragraphtext, cwsp.visiblepreview publish,
	  					       getWSTextLabel(cwsp.title, '$language') title, getWSTextLabel(cwsp.subtitle, '$language') subtitle, getWSTextLabel(cwsp.paragraphtext, '$language') paragraphtext
	            FROM cpa_ws_sections_paragraphs cwsp
	            WHERE cwsp.sectionname = '$sectionname'
	            AND cwsp.visiblepreview = 1
	            ORDER BY paragraphindex";
  } else {
	  $query = "SELECT cwsp.*, getWSTextLabel(cwsp.title, '$language') title, getWSTextLabel(cwsp.subtitle, '$language') subtitle, getWSTextLabel(cwsp.paragraphtext, '$language') paragraphtext
	            FROM cpa_ws_sections_paragraphs cwsp
	            WHERE cwsp.sectionname = '$sectionname'
	            AND cwsp.publish = 1
	            ORDER BY paragraphindex";
  }
  $result = $mysqli->query($query);
  $data = array();
  $data['data'] = array();
  while ($row = $result->fetch_assoc()) {
    $data['data'][] = $row;
  }
  $data['success'] = true;
  return $data;
  exit;
}

function getsectiontoplinks($mysqli, $sectionname, $language, $previewmode) {
  $query = "SELECT cwsl.*, getWSTextLabel(cwsl.label, '$language') label, getWSTextLabel(cwd.filename, '$language') filename
            FROM cpa_ws_sections_links cwsl
            LEFT JOIN cpa_ws_documents cwd ON cwd.id = cwsl.linkdocumentid
            WHERE cwsl.sectionname = '$sectionname'
            AND position = 1
            ORDER BY linkindex";
  $result = $mysqli->query($query);
  $data = array();
  $data['data'] = array();
  while ($row = $result->fetch_assoc()) {
    $row['filename'] = isset($row['filename']) ? htmlentities($row['filename']) : null;
    $data['data'][] = $row;
  }
  $data['success'] = true;
  return $data;
  exit;
}

function getsectionbottomlinks($mysqli, $sectionname, $language, $previewmode) {
  $query = "SELECT cwsl.*, getWSTextLabel(cwsl.label, '$language') label, getWSTextLabel(cwd.filename, '$language') filename
            FROM cpa_ws_sections_links cwsl
            LEFT JOIN cpa_ws_documents cwd ON cwd.id = cwsl.linkdocumentid
            WHERE cwsl.sectionname = '$sectionname'
            AND position = 2
            ORDER BY linkindex";
  $result = $mysqli->query($query);
  $data = array();
  $data['data'] = array();
  while ($row = $result->fetch_assoc()) {
    $data['data'][] = $row;
  }
  $data['success'] = true;
  return $data;
  exit;
}

function getnewssummarysection($mysqli, $language, $previewmode) {
  $query = "SELECT id, getWSTextLabel(cwn.title, '$language') title, getWSTextLabel(cwn.shortversion, '$language') shortversion
            FROM cpa_ws_news cwn
            WHERE cwn.publish = 1
            ORDER by cwn.publishdate DESC limit 3";
  $result = $mysqli->query($query);
  $data = array();
  $data['data'] = array();
  while ($row = $result->fetch_assoc()) {
    $data['data'][] = $row;
  }
  $data['success'] = true;
  return $data;
  exit;
}

function getnewstoplinks($mysqli, $newsid, $language, $previewmode) {
  $query = "SELECT cwnl.*, getWSTextLabel(cwnl.label, '$language') label, getWSTextLabel(cwd.filename, '$language') filename
            FROM cpa_ws_news_links cwnl
            LEFT JOIN cpa_ws_documents cwd ON cwd.id = cwnl.linkdocumentid
            WHERE cwnl.newsid = $newsid
            AND position = 1
            ORDER BY linkindex";
  $result = $mysqli->query($query);
  $data = array();
  $data['data'] = array();
  while ($row = $result->fetch_assoc()) {
    $row['filename'] = isset($row['filename']) ? htmlentities($row['filename']) : null;
    $data['data'][] = $row;
  }
  $data['success'] = true;
  return $data;
  exit;
}

function getnewsbottomlinks($mysqli, $newsid, $language, $previewmode) {
  $query = "SELECT cwnl.*, getWSTextLabel(cwnl.label, '$language') label, getWSTextLabel(cwd.filename, '$language') filename
            FROM cpa_ws_news_links cwnl
            LEFT JOIN cpa_ws_documents cwd ON cwd.id = cwnl.linkdocumentid
            WHERE cwnl.newsid = $newsid
            AND position = 2
            ORDER BY linkindex";
  $result = $mysqli->query($query);
  $data = array();
  $data['data'] = array();
  while ($row = $result->fetch_assoc()) {
    $data['data'][] = $row;
  }
  $data['success'] = true;
  return $data;
  exit;
}

function getnewslistsection($mysqli, $language, $previewmode) {
  $query = "SELECT id, getWSTextLabel(cwn.title, '$language') title, getWSTextLabel(cwn.longversion, '$language') longversion, imagefilename, publishdate
            FROM cpa_ws_news cwn
            WHERE cwn.publish = 1
            ORDER by cwn.publishdate DESC";
  $result = $mysqli->query($query);
  $data = array();
  $data['data'] = array();
  while ($row = $result->fetch_assoc()) {
    $row['toplinks'] = getnewstoplinks($mysqli, $row['id'], $language, $previewmode)['data'];
    $row['bottomlinks'] = getnewsbottomlinks($mysqli, $row['id'], $language, $previewmode)['data'];
    $data['data'][] = $row;
  }
  $data['success'] = true;
  return $data;
  exit;
}

function getprogramslistsection($mysqli, $language, $previewmode) {
	if ($previewmode) {
	  $query = "SELECT getWSTextLabel(cws.title, '$language') title, getWSTextLabel(cws.subtitle, '$language') subtitle, cws.name, cws.imagefilename
	            FROM cpa_ws_pages_sections cwps
	            JOIN cpa_ws_sections cws ON cws.name = cwps.sectionname
	            WHERE cwps.visiblepreview = 1
	            AND cwps.groupname = 'programgroup'
	            ORDER BY cwps.pagesectionindex";
	} else {
	  $query = "SELECT getWSTextLabel(cws.title, '$language') title, getWSTextLabel(cws.subtitle, '$language') subtitle, cws.name, cws.imagefilename
	            FROM cpa_ws_pages_sections cwps
	            JOIN cpa_ws_sections cws ON cws.name = cwps.sectionname
	            WHERE cwps.visible = 1
	            AND cwps.groupname = 'programgroup'
	            ORDER BY cwps.pagesectionindex";
	}
  $result = $mysqli->query($query);
  $data = array();
  $data['data'] = array();
  while ($row = $result->fetch_assoc()) {
  	$row['href'] = 'service.html';
  	if ($previewmode) $row['href'] .= '?preview=true';
  	$row['href'] .= '#'.$row['name'];
    $data['data'][] = $row;
  }
  $data['success'] = true;
  return $data;
  exit;
}

function getactivesessionschedules($mysqli, $language, $previewmode) {
  $query = "SELECT csc.coursecode, csc.courselevel, cscd.id, cscd.sessionscoursesid, cscd.arenaid, cscd.iceid, cscd.coursedate, cscd.day, time_format(starttime, '%H:%i') starttime, time_format(endtime, '%H:%i') endtime, cscd.duration, cscd.canceled, cscd.label, cscd.manual,
			       getTextLabel(cscd.label, '$language') datelabel, getTextLabel(csc.label, '$language') courselabel, getCodeDescription('days', cscd.day, '$language') daylabel, WEEK(coursedate) weekno,
			       DATE_ADD(coursedate, INTERVAL - WEEKDAY(coursedate+1) DAY) weekfirstdate, DATE_ADD(coursedate, INTERVAL - WEEKDAY(coursedate+1)+6 DAY) weeklastdate, WEEKDAY(coursedate) weekdayno,
			       getTextLabel((select label from cpa_courses_levels where coursecode = csc.coursecode and code = csc.courselevel), '$language') courselevellabel,
			       concat(getTextLabel((select label from cpa_arenas where id = arenaid), '$language'),' ',
			                          if ((iceid is null or iceid = 0), '',  getTextLabel((select label from cpa_arenas_ices where id = iceid), '$language'))) location,
			       csc.name
            FROM cpa_sessions_courses_dates cscd
            JOIN cpa_sessions_courses csc ON csc.id = cscd.sessionscoursesid
            JOIN cpa_sessions cs ON cs.id = csc.sessionid
            JOIN cpa_courses cc ON cc.code = csc.coursecode
            WHERE cs.active = 1
            AND coursedate >= curdate()
            AND cc.acceptregistrations = 1
				UNION
					 SELECT csnd.numberid, null as courselevel, csnd.id, csnd.numberid, csnd.arenaid, csnd.iceid, csnd.practicedate, csnd.day, time_format(starttime, '%H:%i') starttime, time_format(endtime, '%H:%i') endtime, csnd.duration, csnd.canceled, null as label, csnd.manual,
		         null as datelabel, getTextLabel(csn.label, '$language') courselabel, getCodeDescription('days', csnd.day, '$language') daylabel, WEEK(practicedate) weekno,
		         DATE_ADD(practicedate, INTERVAL - WEEKDAY(practicedate+1) DAY) weekfirstdate, DATE_ADD(practicedate, INTERVAL - WEEKDAY(practicedate+1)+6 DAY) weeklastdate, WEEKDAY(practicedate) weekdayno,
		         null as courselevellabel,
		         concat(getTextLabel((select label from cpa_arenas where id = arenaid), '$language'), ' ',
		                          if ((iceid is null or iceid = 0), '',  getTextLabel((select label from cpa_arenas_ices where id = iceid), '$language'))) location, 
		         csnd.numberid as name
	          FROM cpa_shows_numbers_dates csnd
	          JOIN cpa_shows_numbers csn ON csn.id = csnd.numberid
	          JOIN cpa_shows cs ON cs.id = csn.showid
	          WHERE cs.active = 1
	          AND practicedate >= curdate()
				ORDER BY 7, 9, 1, 2";
  $result = $mysqli->query($query);
  $data = array();
  $data['data'] = array();
  while ($row = $result->fetch_assoc()) {
    $data['data'][] = $row;
  }
  $data['success'] = true;
  return $data;
  exit;
}

function getactivesessionschedulescodes($mysqli, $language, $previewmode) {
  $query = "SELECT csc.name code, getTextLabel(csc.label, '$language') courselabel, 
			       getTextLabel((select label from cpa_courses_levels where coursecode = csc.coursecode and code = csc.courselevel), '$language') courselevellabel
            FROM cpa_sessions_courses csc 
            JOIN cpa_sessions cs ON cs.id = csc.sessionid
            JOIN cpa_courses cc ON cc.code = csc.coursecode
            WHERE cs.active = 1
            AND cc.acceptregistrations = 1
            AND exists (select * from cpa_sessions_courses_dates cscd where cscd.sessionscoursesid = csc.id and coursedate >= curdate())
				UNION
					 SELECT csn.id code, getTextLabel(csn.label, '$language') courselabel, '' as courselevellabel
	          FROM cpa_shows_numbers csn
	          JOIN cpa_shows cs ON cs.id = csn.showid
	          WHERE cs.active = 1
	          AND csn.type = 1
            AND exists (select * from cpa_shows_numbers_dates csnd where csnd.numberid = csn.id and practicedate >= curdate())
				ORDER BY 1";
  $result = $mysqli->query($query);
  $data = array();
  $data['data'] = array();
  while ($row = $result->fetch_assoc()) {
    $data['data'][] = $row;
  }
  $data['success'] = true;
  return $data;
  exit;
}

function getcontactinfosection($mysqli, $language, $previewmode) {
  $query = "SELECT cwc.*, getWSTextLabel(label, '$language') label
            FROM cpa_ws_contactinfo cwc";
  $result = $mysqli->query($query);
  $data = array();
  $data['data'] = array();
  while ($row = $result->fetch_assoc()) {
    $data['data'][] = $row;
  }
  $data['success'] = true;
  return $data;
  exit;
}

function getarenassection($mysqli, $language, $previewmode) {
  $query = "SELECT *, getWSTextLabel(cwa.label, '$language') arenalabel
            FROM cpa_ws_arenas cwa
            WHERE publish = 1
            ORDER BY arenaindex";
  $result = $mysqli->query($query);
  $data = array();
  $data['data'] = array();
  while ($row = $result->fetch_assoc()) {
    $data['data'][] = $row;
  }
  $data['success'] = true;
  return $data;
  exit;
}

function getSessionRegistrationsEvents($mysqli, $sessionid, $language, $previewmode) {
  $query = "SELECT *
                from cpa_sessions_registrations csr
                join cpa_sessions cs ON cs.id = csr.sessionid
                where cs.id = $sessionid
                order by location, registrationdate";
  $result = $mysqli->query($query);
  $data = array();
  $data['data'] = array();
  while ($row = $result->fetch_assoc()) {
    $data['data'][] = $row;
  }
  $data['success'] = true;
  return $data;
  exit;
}

function getSessionCourses($mysqli, $sessionid, $language, $previewmode) {
  $query = "SELECT csc.id, csc.coursecode, csc.courselevel, csc.name, csc.maxnumberskater,
                  getTextLabel((select label from cpa_courses_levels where coursecode = csc.coursecode and code = csc.courselevel), '$language') courselevellabel,
                  (select count(*) from cpa_sessions_courses_members cscm where sessionscoursesid = csc.id and membertype = 3 and (cscm.registrationenddate is null or cscm.registrationenddate > curdate())) nbofskaters,
                  getTextLabel(csc.label, '$language') label,
                  csc.fees,
                  (select count(*) from cpa_sessions_courses_dates where sessionscoursesid = csc.id and canceled = 0 and manual = 0) nbofcourses,
                  (select group_concat(concat(getTextLabel((select label from cpa_arenas where id = arenaid), '$language'),
                																				if ((iceid is null or iceid = 0), ', ', concat(' (' , getTextLabel((select label from cpa_arenas_ices where id = iceid), '$language'), '), ')),
                																				getTextLabel((select description from cpa_codetable where ctname = 'days' and code = day), '$language'),
                																				' - ',
                																				substr(starttime FROM 1 FOR 5),
                																				' - ',
                																				substr(endtime FROM 1 FOR 5))
                																SEPARATOR ', ') schedule
                						from cpa_sessions_courses_schedule
                						where sessionscoursesid = csc.id) schedule
                from cpa_sessions_courses csc
                join cpa_courses cc ON cc.code = csc.coursecode
                join cpa_sessions cs ON cs.id = csc.sessionid
                where cs.id = $sessionid
                and datesgenerated = 1
                and cc.acceptregistrations = 1
                order by coursecode, courselevel, csc.name";
  $result = $mysqli->query($query);
  $data = array();
  $data['data'] = array();
  while ($row = $result->fetch_assoc()) {
    $data['data'][] = $row;
  }
  $data['success'] = true;
  return $data;
  exit;
}

function getsessionsection($mysqli, $language, $previewmode) {
  $query = "SELECT *, getTextLabel(cs.label, '$language') sessionlabel FROM cpa_sessions cs where cs.active = 1";
  $result = $mysqli->query($query);
  $data = array();
  $data['data'] = array();
  $row = $result->fetch_assoc();
  if (isset($row)) {
    $row['courses'] = getSessionCourses($mysqli, $row['id'], $language, $previewmode)['data'];
    $row['registrations'] = getSessionRegistrationsEvents($mysqli, $row['id'], $language, $previewmode)['data'];
    $data['data'] = $row;
    $data['success'] = true;
  } else {
    $data['success'] = false;
  }
  return $data;
  exit;
}

function getEventPictures($mysqli, $eventid, $language, $previewmode) {
  $query = "SELECT * FROM cpa_ws_events_pictures WHERE eventid = $eventid ORDER BY pictureindex";
  $result = $mysqli->query($query);
  $data = array();
  $data['data'] = array();
  while ($row = $result->fetch_assoc()) {
    $data['data'][] = $row;
  }
  $data['success'] = true;
  return $data;
  exit;
}

function geteventlistsection($mysqli, $language, $eventlist, $previewmode) {
  $query = "SELECT *, getWSTextLabel(label, '$language') label
            FROM cpa_ws_events
            WHERE eventlist = $eventlist
            AND publish = 1
            ORDER BY eventdate DESC";
  $result = $mysqli->query($query);
  $data = array();
  $data['data'] = array();
  while ($row = $result->fetch_assoc()) {
    $row['pictures'] = getEventPictures($mysqli, $row['id'], $language, $previewmode)['data'];
    $data['data'][] = $row;
  }
  $data['success'] = true;
  return $data;
  exit;
}

/**
 * This function gets the rules for the active session
 */
function getsessionrules($mysqli, $language, $previewmode) {
  $result = $mysqli->query("SET NAMES utf8");
	$query = "SELECT convert(rules using cp1256) as rules
            FROM cpa_sessions_rules csr
            JOIN cpa_sessions cs ON cs.id = csr.sessionid
            WHERE cs.active = 1
            AND language = '$language'";
	$result = $mysqli->query($query);
	$data = array();
	$data['data'] = array();
	$row = $result->fetch_assoc();
  $data['data'] = $row['rules'];
	// $data['data'] = utf8_decode($row['rules']);
	// $data['data'] = mb_convert_encoding($row['rules'], 'UTF-8');
	$data['success'] = true;
	return $data;
};

/**
 * This function gets the rules paragraph of a session
 */
function getsessionrulesparagraphs($mysqli, $language, $previewmode) {
  if ($previewmode) {
    $query = "SELECT csr.id, csr.paragraphindex, csr.title, csr.subtitle, csr.paragraphtext, csr.visiblepreview publish,
                     getWSTextLabel(csr.title, '$language') title, getWSTextLabel(csr.subtitle, '$language') subtitle, getWSTextLabel(csr.paragraphtext, '$language') paragraphtext
            FROM cpa_sessions_rules2 csr
            JOIN cpa_sessions cs ON cs.id = csr.sessionid
            WHERE cs.active = 1
            AND csr.visiblepreview = 1
            ORDER BY paragraphindex";
  } else {
    $query = "SELECT csr.id, csr.paragraphindex, csr.title, csr.subtitle, csr.paragraphtext, csr.visiblepreview publish,
                      getWSTextLabel(csr.title, '$language') title, getWSTextLabel(csr.subtitle, '$language') subtitle, getWSTextLabel(csr.paragraphtext, '$language') paragraphtext
            FROM cpa_sessions_rules2 csr
            JOIN cpa_sessions cs ON cs.id = csr.sessionid
            WHERE cs.active = 1
            AND csr.publish = 1
            ORDER BY paragraphindex";
  }
  $result = $mysqli->query($query);
  $data = array();
  $data['data'] = array();
  while ($row = $result->fetch_assoc()) {
    $data['data'][] = $row;
  }
  $data['success'] = true;
  return $data;
  exit;
}

/**
 * This function gets the costumes list
 */
function getcostumeslistsection($mysqli, $language, $previewmode) {
	$query = "SELECT id, name, getWSTextLabel(cwc.label, '$language') label, totalamount, priceperunit, imagefilename
            FROM cpa_ws_costumes cwc
            WHERE cwc.publish = 1";
	$result = $mysqli->query($query);
	$data = array();
	$data['data'] = array();
  while ($row = $result->fetch_assoc()) {
    $data['data'][] = $row;
  }
	$data['success'] = true;
	return $data;
};

/**
 * This function gets the good pictures
*/
function getGoodsPictures($mysqli, $goodid, $language, $previewmode) {
  $query = "SELECT * FROM cpa_ws_goods_pictures WHERE goodid = $goodid ORDER BY pictureindex";
  $result = $mysqli->query($query);
  $data = array();
  $data['data'] = array();
  while ($row = $result->fetch_assoc()) {
    $data['data'][] = $row;
  }
  $data['success'] = true;
  return $data;
  exit;
}

/**
 * This function gets the goods list
 */
function getgoodslistsection($mysqli, $language, $previewmode) {
	$query = "SELECT id, name, getWSTextLabel(cwg.label, '$language') label, getWSTextLabel(cwg.description, '$language') description, quantity, priceperunit, imagefilename
            FROM cpa_ws_goods cwg
            WHERE cwg.publish = 1";
	$result = $mysqli->query($query);
	$data = array();
	$data['data'] = array();
  while ($row = $result->fetch_assoc()) {
    $row['pictures'] = getGoodsPictures($mysqli, $row['id'], $language, $previewmode)['data'];
    $data['data'][] = $row;
  }
	$data['success'] = true;
	return $data;
};

/**
 * This function gets the classified add pictures
*/
function getClassifiedaddsPictures($mysqli, $classifiedaddid, $language, $previewmode) {
  $query = "SELECT * FROM cpa_ws_classifiedadds_pictures WHERE classifiedaddid = $classifiedaddid ORDER BY pictureindex";
  $result = $mysqli->query($query);
  $data = array();
  $data['data'] = array();
  while ($row = $result->fetch_assoc()) {
    $data['data'][] = $row;
  }
  $data['success'] = true;
  return $data;
  exit;
}

/**
 * This function gets the classified add list
 */
function getclassifiedaddslistsection($mysqli, $language, $previewmode) {
	$query = "SELECT id, name, getWSTextLabel(cwc.label, '$language') label, getWSTextLabel(cwc.description, '$language') description, price, imagefilename
            FROM cpa_ws_classifiedadds cwc
            WHERE cwc.publish = 1";
	$result = $mysqli->query($query);
	$data = array();
	$data['data'] = array();
  while ($row = $result->fetch_assoc()) {
    $row['pictures'] = getClassifiedaddsPictures($mysqli, $row['id'], $language, $previewmode)['data'];
    $data['data'][] = $row;
  }
	$data['success'] = true;
	return $data;
};

function getCostumePictures($mysqli, $costumeid, $language, $previewmode) {
  $query = "SELECT * FROM cpa_ws_costumes_pictures WHERE costumeid = $costumeid ORDER BY pictureindex";
  $result = $mysqli->query($query);
  $data = array();
  $data['data'] = array();
  while ($row = $result->fetch_assoc()) {
    $data['data'][] = $row;
  }
  $data['success'] = true;
  return $data;
  exit;
}

/**
 * This function gets the costumes list
 */
function getcostumedetailssection($mysqli, $language, $costumeid, $previewmode) {
	$query = "SELECT cwc.*, getWSTextLabel(cwc.label, '$language') label, getWSTextLabel(cwc.girldescription, '$language') girldescription, getWSTextLabel(cwc.boydescription, '$language') boydescription, getWSTextLabel(cwc.solodescription, '$language') solodescription
            FROM cpa_ws_costumes cwc
            WHERE cwc.id = $costumeid";
	$result = $mysqli->query($query);
	$data = array();
	$data['data'] = array();
  while ($row = $result->fetch_assoc()) {
    $row['pictures'] = getCostumePictures($mysqli, $row['id'], $language, $previewmode)['data'];
    $data['data'][] = $row;
  }
	$data['success'] = true;
	return $data;
};

/**
 * This function gets the details of all skaters for a group from database
 */
function getTestsessionGroupsSkaters($mysqli, $testsessionsgroupsid, $language, $previewmode) {
	$query = "SELECT ctsgs.*, ctd.type testtype, ct.summarycode, ctsrt.testsid, ctsr.memberid, ctsrt.partnerid, ctsrt.partnersteps, ctsrt.musicid, ctsrt.comments, cm.firstname, cm.lastname, concat(cmu.song, ' - ', cmu.author) musiclabel, cmpartner.firstname partnerfirstname, cmpartner.lastname partnerlastname
						FROM cpa_tests_sessions_groups_skaters ctsgs
						JOIN cpa_tests_sessions_registrations_tests ctsrt ON ctsrt.id = ctsgs.testssessionsregistrationstestsid
						JOIN cpa_tests_sessions_registrations ctsr ON ctsr.id = ctsrt.testssessionsregistrationsid
						JOIN cpa_members cm ON cm.id = ctsr.memberid
						LEFT JOIN cpa_members cmpartner ON cmpartner.id = ctsrt.partnerid
						JOIN cpa_tests ct ON ct.id = ctsrt.testsid
						JOIN cpa_tests_definitions ctd ON ctd.id = ct.testsdefinitionsid
						LEFT JOIN cpa_musics cmu ON cmu.id = ctsrt.musicid
						WHERE ctsgs.testsessionsgroupsid = $testsessionsgroupsid
						AND ctsrt.canceled != 1
						AND ctd.version = 1
						ORDER BY sequence";
	$result = $mysqli->query($query );
	$data = array();
	$data['data'] = array();
	while ($row = $result->fetch_assoc()) {
		$data['data'][] = $row;
	}
	$data['success'] = true;
	return $data;
	exit;
};

/**
 * This function gets the details of all groups for a testsession period from database
 */
function getTestsessionPeriodGroups($mysqli, $testsessionid, $periodid, $language, $previewmode) {
	$query = "SELECT ctsg.*, getTextLabel(ctsg.label, '$language') grouplabel, ctd.type testtype, getCodeDescription('testtypes', ctd.type, '$language') testtypelabel,
						getTextLabel(ct.label, '$language') testlabel, getCodeDescription('testlevels', ctd.level, '$language') testlevellabel,
						ctd.testduration testdefduration, ctd.warmupduration testdefwarmupduration
						FROM cpa_tests_sessions_groups ctsg
						JOIN cpa_tests_sessions_days_periods ctp ON ctp.id = ctsg.testperiodsid
						JOIN cpa_tests_sessions_days ctsd ON ctsd.id = ctp.testsdaysid
						JOIN cpa_tests ct ON ct.id = ctsg.testid
						JOIN cpa_tests_definitions ctd ON ctd.id = ct.testsdefinitionsid
						WHERE ctsd.testssessionsid = $testsessionid
						AND ctsg.testperiodsid = $periodid
						AND ctd.version = 1
						ORDER BY ctsg.sequence";
	$result = $mysqli->query($query );
	$data = array();
	$data['data'] = array();
	while ($row = $result->fetch_assoc()) {
		$row['skaters'] = getTestsessionGroupsSkaters($mysqli, $row['id'], $language, $previewmode)['data'];
		$data['data'][] = $row;
	}
	$data['success'] = true;
	return $data;
	exit;
};

/**
 * This function gets the details of all periods for a testsession from database
 */
function getTestsessionPeriodDetails($mysqli, $testsessionid, $publishschedule, $language, $previewmode) {
	$query = "SELECT ctsdp.*, ctsdp.id testperiodid, ctsd.testdate,
										concat(ctsd.testdate, ' ', getTextLabel(ca.label, '$language'), ' ', if(ctsdp.iceid != 0, getTextLabel(cai.label, '$language'), ''), ' ', ctsdp.starttime, ' - ', ctsdp.endtime) periodlabel
						FROM cpa_tests_sessions_days_periods ctsdp
						JOIN cpa_tests_sessions_days ctsd ON ctsd.id = ctsdp.testsdaysid
						JOIN cpa_arenas ca ON ca.id = ctsdp.arenaid
						LEFT JOIN cpa_arenas_ices cai ON cai.id = ctsdp.iceid
            WHERE ctsd.testssessionsid = $testsessionid
            ORDER BY ctsd.testdate";
	$result = $mysqli->query($query );
	$data = array();
	$data['data'] = array();
	while ($row = $result->fetch_assoc()) {
    if ($publishschedule) {
      $testperiodid = $row['testperiodid'];
      $row['groups'] = getTestsessionPeriodGroups($mysqli, $testsessionid, $testperiodid, $language, $previewmode)['data'];
    }
    $data['data'][] = $row;
	}
	$data['success'] = true;
  return $data;
	exit;
};

/**
 * This function gets the details of all periods for a testsession from database
 */
function gettestsessionperiods($mysqli, $testssessionsid, $language, $previewmode) {
	$query = "SELECT ctsdp.*, ctsd.testdate, (select getTextLabel(ca.label, '$language') from cpa_arenas ca where ca.id = ctsdp.arenaid) arenalabel,
										(select getTextLabel(cai.label, '$language') from cpa_arenas_ices cai where cai.arenaid = ctsdp.arenaid and cai.id = ctsdp.iceid) icelabel
						FROM cpa_tests_sessions_days_periods ctsdp
						JOIN cpa_tests_sessions_days ctsd ON ctsd.id = ctsdp.testsdaysid
						WHERE ctsd.testssessionsid = $testssessionsid";
	$result = $mysqli->query($query);
	$data = array();
	$data['data'] = array();
	while ($row = $result->fetch_assoc()) {
		$data['data'][] = $row;
	}
	$data['success'] = true;
	return $data;
	exit;
};

/**
 * This function gets the details of all charges for a testsession from database
 */
function gettestsessioncharges($mysqli, $testssessionsid, $language, $previewmode) {
	$query = "SELECT ctsc.*, getTextLabel(cc.label, '$language') chargelabel
						FROM cpa_tests_sessions_charges ctsc
						JOIN cpa_charges cc ON cc.code = ctsc.chargecode
						WHERE ctsc.testssessionsid = $testssessionsid";
	$result = $mysqli->query($query);
	$data = array();
	$data['data'] = array();
	while ($row = $result->fetch_assoc()) {
		$data['data'][] = $row;
	}
	$data['success'] = true;
	return $data;
	exit;
};

/**
 * This function gets the details of all charges for a testsession from database
 */
function getshowperformanceprices($mysqli, $performanceid, $language, $previewmode) {
	$query = "SELECT cspp.*, getCodeDescription('showpricetypes', cspp.pricetype, '$language') pricetypelabel
						FROM cpa_shows_performances_prices cspp
						JOIN cpa_codetable cct ON cct.ctname = 'showpricetypes' AND cct.code = cspp.pricetype
						WHERE cspp.performanceid = $performanceid
						ORDER BY cct.sequence";
	$result = $mysqli->query($query);
	$data = array();
	$data['data'] = array();
	while ($row = $result->fetch_assoc()) {
		$data['data'][] = $row;
	}
	$data['success'] = true;
	return $data;
	exit;
};

/**
 * This function gets the details of all performances for a show
 */
function getshowperformances($mysqli, $showid, $language, $previewmode) {
	$query = "SELECT csp.*, getEnglishTextLabel(csp.label) as label_en, getFrenchTextLabel(csp.label) as label_fr,
									getEnglishTextLabel(csp.websitedesc) as websitedesc_en, getFrenchTextLabel(csp.websitedesc) as websitedesc_fr,
									(select getTextLabel(ca.label, '$language') from cpa_arenas ca where ca.id = csp.arenaid) arenalabel,
									(select getTextLabel(cai.label, '$language') from cpa_arenas_ices cai where cai.arenaid = csp.arenaid and cai.id = csp.iceid) icelabel,
									getCodeDescription('performancetypes', csp.type, '$language') typelabel, getTextLabel(csp.label, '$language') performancelabel
						FROM cpa_shows_performances csp
						WHERE csp.showid = $showid
						ORDER BY csp.perfdate";
	$result = $mysqli->query($query);
	$data = array();
	$data['data'] = array();
	while ($row = $result->fetch_assoc()) {
    $performanceid = $row['id'];
		$row['prices'] = getshowperformanceprices($mysqli, $performanceid, $language, $previewmode)['data'];
		$data['data'][] = $row;
	}
	$data['success'] = true;
	return $data;
	exit;
};

/**
 * This function gets the list of active and published shows
 */
function getshowlistsection($mysqli, $language, $previewmode){
	try{
		$query = "SELECT cs.*, getTextLabel(cs.label, '$language') showlabel 
							FROM cpa_shows cs 
							WHERE publish = 1 AND active = 1
							ORDER BY id desc";
		$result = $mysqli->query($query);
		$data = array();
		$data['data'] = array();
		while ($row = $result->fetch_assoc()) {
      $id = $row['id'];
			$row['paragraphs'] 	 		= getshowparagraphs($mysqli, $id, $language, $previewmode)['data'];
			$row['rulesparagraphs']	= getshowrulesparagraphs($mysqli, $id, $language, $previewmode)['data'];
			$row['performances'] 		= getshowperformances($mysqli, $id, $language, $previewmode)['data'];
			$data['data'][] = $row;
		}
		$data['success'] = true;
    return $data;
  	exit;
	}catch (Exception $e){
		$data = array();
		$data['success'] = false;
		$data['message'] = $e->getMessage();
    return $data;
  	exit;
	}
};

/**
 * This function gets the list of testsession
 */
function gettestsessionlistsection($mysqli, $language, $previewmode){
	try{
		$query = "SELECT cts.*, getTextLabel(cts.label, '$language') label, getCodeDescription('extrafeesoptions', extrafeesoption, '$language') extrafeesoptionlabel
							FROM cpa_tests_sessions cts
              WHERE publish = 1
              ORDER BY cts.registrationstartdate DESC";
		$result = $mysqli->query($query);
		$data = array();
		while ($row = $result->fetch_assoc()) {
      $id = $row['id'];
			$row['periods'] = gettestsessionperiods($mysqli, $id, $language, $previewmode)['data'];
			$row['charges'] = gettestsessioncharges($mysqli, $id, $language, $previewmode)['data'];
			$data['data'][] = $row;
		}
		$data['success'] = true;
    return $data;
  	exit;
	}catch (Exception $e){
		$data = array();
		$data['success'] = false;
		$data['message'] = $e->getMessage();
    return $data;
  	exit;
	}
};

/**
 * This function gets the web paragraph of a show
 */
function getshowparagraphs($mysqli, $showid, $language, $previewmode) {
	if ($previewmode) {
		$query = "SELECT csp.id, csp.paragraphindex, csp.title, csp.subtitle, csp.paragraphtext, csp.visiblepreview publish,
	  					       getWSTextLabel(csp.title, '$language') title, getWSTextLabel(csp.subtitle, '$language') subtitle, getWSTextLabel(csp.paragraphtext, '$language') paragraphtext
						FROM cpa_shows_paragraphs csp
						WHERE csp.showid = $showid
	          AND csp.visiblepreview = 1
						ORDER BY paragraphindex";
  } else {
	  $query = "SELECT csp.id, csp.paragraphindex, csp.title, csp.subtitle, csp.paragraphtext, csp.visiblepreview publish,
	  					       getWSTextLabel(csp.title, '$language') title, getWSTextLabel(csp.subtitle, '$language') subtitle, getWSTextLabel(csp.paragraphtext, '$language') paragraphtext
						FROM cpa_shows_paragraphs csp
						WHERE csp.showid = $showid
						AND csp.publish = 1
						ORDER BY paragraphindex";
  }
  $result = $mysqli->query($query);
  $data = array();
  $data['data'] = array();
  while ($row = $result->fetch_assoc()) {
    $data['data'][] = $row;
  }
  $data['success'] = true;
  return $data;
  exit;
}

/**
 * This function gets the rules paragraph of a show
 */
function getshowrulesparagraphs($mysqli, $showid, $language, $previewmode) {
	if ($previewmode) {
		$query = "SELECT csp.id, csp.paragraphindex, csp.title, csp.subtitle, csp.paragraphtext, csp.visiblepreview publish,
	  					       getWSTextLabel(csp.title, '$language') title, getWSTextLabel(csp.subtitle, '$language') subtitle, getWSTextLabel(csp.paragraphtext, '$language') paragraphtext
						FROM cpa_shows_rules csp
						WHERE csp.showid = $showid
	          AND csp.visiblepreview = 1
						ORDER BY paragraphindex";
  } else {
	  $query = "SELECT csp.id, csp.paragraphindex, csp.title, csp.subtitle, csp.paragraphtext, csp.visiblepreview publish,
	  					       getWSTextLabel(csp.title, '$language') title, getWSTextLabel(csp.subtitle, '$language') subtitle, getWSTextLabel(csp.paragraphtext, '$language') paragraphtext
						FROM cpa_shows_rules csp
						WHERE csp.showid = $showid
						AND csp.publish = 1
						ORDER BY paragraphindex";
  }
  $result = $mysqli->query($query);
  $data = array();
  $data['data'] = array();
  while ($row = $result->fetch_assoc()) {
    $data['data'][] = $row;
  }
  $data['success'] = true;
  return $data;
  exit;
}

/**
 * This function gets the description of a show
 */
function getshowdescriptionsection($mysqli, $language, $previewmode){
	try{
		$query = "SELECT *, getEnglishTextLabel(label) as label_en, getFrenchTextLabel(label) as label_fr,
									  getEnglishTextLabel(websitedesc) as websitedesc_en, getFrenchTextLabel(websitedesc) as websitedesc_fr
							FROM cpa_shows
							WHERE publish = 1 AND active = 1
							ORDER BY id";
		$result = $mysqli->query($query);
		$data = array();
		$data['data'] = array();
		while ($row = $result->fetch_assoc()) {
//			$row['charges'] = gettestsessioncharges($mysqli, $id, $language, $previewmode)['data'];
			$data['data'][] = $row;
		}
		$data['success'] = true;
    return $data;
  	exit;
	}catch (Exception $e){
		$data = array();
		$data['success'] = false;
		$data['message'] = $e->getMessage();
    return $data;
  	exit;
	}
};

/**
 * This function gets the details of a testsession
 */
function gettestsessiondetailssection($mysqli, $id, $language, $previewmode){
	try{
		$query = "SELECT cts.*, getTextLabel(cts.label, '$language') label, getCodeDescription('extrafeesoptions', extrafeesoption, '$language') extrafeesoptionlabel
							FROM cpa_tests_sessions cts
              WHERE cts.id = $id
              AND publish = 1";
		$result = $mysqli->query($query);
		$data = array();
		$data['data'] = array();
		while ($row = $result->fetch_assoc()) {
			$row['periods'] = getTestsessionPeriodDetails($mysqli, $id, $row['publishschedule'], $language, $previewmode)['data'];
			$row['charges'] = gettestsessioncharges($mysqli, $id, $language, $previewmode)['data'];
			$data['data'][] = $row;
		}
		$data['success'] = true;
    return $data;
  	exit;
	}catch (Exception $e){
		$data = array();
		$data['success'] = false;
		$data['message'] = $e->getMessage();
    return $data;
  	exit;
	}
};

function getcurrentpageinfo($mysqli, $pagename, $language, $costumeid, $testsessionid, $previewmode) {
  try {
    $data = array();
    $data['navbar'] = array();
  	if ($previewmode == 'true') {
  		$previewmode = true;
  	} else {
  		$previewmode = false;
  	}
  	// Get all visible pages and their sections for the main menu (nav bar)
  	if ($previewmode) {
	   	$query = "SELECT name, label, pageindex, navbarlabel, canbeinmenubar, navbarvisiblepreview navbarvisible, navbarusesectionpreview navbarusesection,
	   									 getWSTextLabel(cwp.navbarlabel, '$language') navbarlabel
	   						FROM cpa_ws_pages cwp 
	   						WHERE cwp.navbarvisiblepreview = 1 
	   						ORDER BY pageindex";
    } else {
    	$query = "SELECT *, getWSTextLabel(cwp.navbarlabel, '$language') navbarlabel 
    						FROM cpa_ws_pages cwp 
    						WHERE cwp.navbarvisible = 1 
    						ORDER BY pageindex";
    }
    $result = $mysqli->query($query);
    while ($row = $result->fetch_assoc()) {
    	$row['href'] = $row['name'].'.html';
    	if ($previewmode) $row['href'] .= '?preview=true';
      $row['sections'] = getpagesections($mysqli, $row['name'], $language, $previewmode)['data'];
      $data['navbar'][] = $row;
    }
    // Get current page info
  	if ($previewmode) {
	    $query = "SELECT name, label, pageindex, navbarlabel, navbarvisiblepreview navbarvisible, navbarusesectionpreview navbarusesection, 
	                     canbeinmenubar, getWSTextLabel(cwp.navbarlabel, '$language') pagelabel 
	                     FROM cpa_ws_pages cwp 
	                     WHERE cwp.name = '$pagename'";
    } else {
	    $query = "SELECT name, label, pageindex, navbarvisible, navbarlabel, navbarusesection, 
	                     canbeinmenubar, getWSTextLabel(cwp.navbarlabel, '$language') pagelabel 
	                     FROM cpa_ws_pages cwp 
	                     WHERE cwp.name = '$pagename'";
    }
    $result = $mysqli->query($query);
    $data['currentpage'] = $result->fetch_assoc();
    // Get current page sections
  	if ($previewmode) {
	    $query = "SELECT cws.name, cws.label, cws.navbarlabel, cws.title, cws.subtitle, cws.imagefilename, cws.imageind, cws.subtitleind, cws.paragraphind, cws.linkind,
	  								   cwps.pagename, cwps.sectionname, cwps.pagesectionindex, cwps.visiblepreview visible, cwps.visiblenavbarpreview visibleinnavbar, cwps.groupname,  
	    	    			     getWSTextLabel(cws.navbarlabel, '$language') navbarlabel, getWSTextLabel(cws.title, '$language') title, getWSTextLabel(cws.subtitle, '$language') subtitle
	              FROM cpa_ws_sections cws
	              JOIN cpa_ws_pages_sections cwps ON cwps.sectionname = cws.name
	              WHERE cwps.pagename = '$pagename'
	              AND visiblepreview = 1
	              ORDER BY pagesectionindex";
    } else {
	    $query = "SELECT *, getWSTextLabel(cws.navbarlabel, '$language') navbarlabel, getWSTextLabel(cws.title, '$language') title, getWSTextLabel(cws.subtitle, '$language') subtitle
	              FROM cpa_ws_sections cws
	              JOIN cpa_ws_pages_sections cwps ON cwps.sectionname = cws.name
	              WHERE cwps.pagename = '$pagename'
	              AND visible = 1
	              ORDER BY pagesectionindex";
	  }
    $result = $mysqli->query($query);
    while ($row = $result->fetch_assoc()) {
      $sectionname = $row['name'];
      // for each section, get the paragraphs
      $row['paragraphs'] = getsectionparagraphs($mysqli, $sectionname, $language, $previewmode)['data'];
      // for each section, get the top links
      $row['toplinks'] = getsectiontoplinks($mysqli, $sectionname, $language, $previewmode)['data'];
      // for each section, get the bottom links
      $row['bottomlinks'] = getsectionbottomlinks($mysqli, $sectionname, $language, $previewmode)['data'];
      if (isset($row['groupname']) && !empty($row['groupname'])) {
        $groupname = $row['groupname'];
        $data['currentpage']['globalsections'][$groupname][] = $row;
      }
      if ($sectionname == 'partners') {
        $row['icons'] = getpartnerssection($mysqli, $language, $previewmode)['data'];
        $data['currentpage']['globalsections'][$sectionname] = $row;
      } else if ($sectionname == 'boardmembers') {
        $row['boardmembers'] = getboardmembersection($mysqli, $language, $previewmode)['data'];
        $data['currentpage']['globalsections'][$sectionname] = $row;
      } else if ($sectionname == 'costumeslist') {
        $row['costumeslist'] = getcostumeslistsection($mysqli, $language, $previewmode)['data'];
        $data['currentpage']['globalsections'][$sectionname] = $row;
      } else if ($sectionname == 'goodslist') {
        $row['goodslist'] = getgoodslistsection($mysqli, $language, $previewmode)['data'];
        $data['currentpage']['globalsections'][$sectionname] = $row;
      } else if ($sectionname == 'classifiedaddslist') {
        $row['classifiedaddslist'] = getclassifiedaddslistsection($mysqli, $language, $previewmode)['data'];
        $data['currentpage']['globalsections'][$sectionname] = $row;
      } else if ($sectionname == 'programassistants') {
        $row['programassistants'] = getprogramassistantsection($mysqli, $language, $previewmode)['data'];
        $data['currentpage']['globalsections'][$sectionname] = $row;
      } else if ($sectionname == 'coaches') {
        $temp = getcoachsection($mysqli, $language, $previewmode);
        $row['coaches'] = $temp['data'];
        $row['coachmaxinfo'] = $temp['maxinfo'];
        $row['coachstarversion'] = $temp['starversion'];
        // $row['coaches'] = getcoachsection($mysqli, $language, $previewmode)['data'];
        $data['currentpage']['globalsections'][$sectionname] = $row;
      } else if ($sectionname == 'newssummarycarousel') {
        $row['news'] = getnewssummarysection($mysqli, $language, $previewmode)['data'];
        $data['currentpage']['globalsections'][$sectionname] = $row;
      } else if ($sectionname == 'newssummaryfix') {
        $row['news'] = getnewssummarysection($mysqli, $language, $previewmode)['data'];
        $data['currentpage']['globalsections'][$sectionname] = $row;
      } else if ($sectionname == 'newslist') {
        $row['news'] = getnewslistsection($mysqli, $language, $previewmode)['data'];
        $data['currentpage']['globalsections'][$sectionname] = $row;
      } else if ($sectionname == 'weeklyschedule') {
        $row['allschedules'] = getactivesessionschedules($mysqli, $language, $previewmode)['data'];
        $data['currentpage']['globalsections'][$sectionname] = $row;
      } else if ($sectionname == 'allschedules') {
        $row['allschedules'] = getactivesessionschedules($mysqli, $language, $previewmode)['data'];
        $data['currentpage']['globalsections'][$sectionname] = $row;
        $row['coursecodes'] = getactivesessionschedulescodes($mysqli, $language, $previewmode)['data'];
        $data['currentpage']['globalsections'][$sectionname] = $row;
      } else if ($sectionname == 'programslist') {
        $row['programs'] = getprogramslistsection($mysqli, $language, $previewmode)['data'];
        $data['currentpage']['globalsections'][$sectionname] = $row;
      } else if ($sectionname == 'contactinfo') {
        $row['contactinfo'] = getcontactinfosection($mysqli, $language, $previewmode)['data'][0];
        $data['currentpage']['globalsections'][$sectionname] = $row;
      } else if ($sectionname == 'arenas') {
        $row['arenas'] = getarenassection($mysqli, $language, $previewmode)['data'];
        $data['currentpage']['globalsections'][$sectionname] = $row;
      } else if ($sectionname == 'session' || $sectionname == 'rules' || $sectionname == 'schedule' || $sectionname == 'registrations') {
        if (empty($row['activesessioninfo'])) {
          $row['activesessioninfo'] = getsessionsection($mysqli, $language, $previewmode)['data'];
          $row['activesessioninfo']['rulesparagraphs'] = getsessionrulesparagraphs($mysqli, $language, $previewmode)['data'];
          if (empty($row['activesessioninfo']['rulesparagraphs']) || count($row['activesessioninfo']['rulesparagraphs']) == 0) {
            $row['activesessioninfo']['rules'] = getsessionrules($mysqli, $language, $previewmode)['data'];
          }
          if (empty($data['currentpage']['globalsections']['session'])) {
            $data['currentpage']['globalsections']['activesessioninfo'] = $row['activesessioninfo'];
          } 
        }
        $data['currentpage']['globalsections'][$sectionname] = $row;
      } else if ($sectionname == 'eventlist') {
        $row['events'] = geteventlistsection($mysqli, $language, '1', $previewmode)['data'];
        $data['currentpage']['globalsections'][$sectionname] = $row;
      } else if ($sectionname == 'eventlist2') {
        $row['events'] = geteventlistsection($mysqli, $language, '2', $previewmode)['data'];
        $data['currentpage']['globalsections'][$sectionname] = $row;
      } else if ($sectionname == 'costumedetails') {
        $row['onecostume'] = getcostumedetailssection($mysqli, $language, $costumeid, $previewmode)['data'][0];
        $data['currentpage']['globalsections'][$sectionname] = $row;
      } else if ($sectionname == 'testsessionlist') {
        $row['testsessionlist'] = gettestsessionlistsection($mysqli, $language, $previewmode)['data'];
        $data['currentpage']['globalsections'][$sectionname] = $row;
      } else if ($sectionname == 'showlist') {
        $row['showlist'] = getshowlistsection($mysqli, $language, $previewmode)['data'];
        $data['currentpage']['globalsections'][$sectionname] = $row;
      } else if ($sectionname == 'testsessiondetails') {
        $tempData = gettestsessiondetailssection($mysqli, $testsessionid, $language, $previewmode)['data'];
        if (count($tempData) != 0) {
          $row['onetestsession'] = $tempData[0];
          $data['currentpage']['globalsections'][$sectionname] = $row;
        }
      } else if ($sectionname == 'showdescription') {
        $tempData = getshowdescriptionsection($mysqli, $language, $previewmode)['data'];
        if (count($tempData) != 0) {
          $row['oneshow'] = $tempData[0];
          $data['currentpage']['globalsections'][$sectionname] = $row;
        }
      } else if ($sectionname == 'calltoaction' || $sectionname == 'mainslider' || $sectionname == 'mainmessage' || $sectionname == 'permanentnews1' || $sectionname == 'permanentnews2' ||
                 $sectionname == 'club' || $sectionname == 'programs' || 
                 $sectionname == 'testsdescription' || $sectionname == 'competitions' || $sectionname == 'scrollingmessage') {
          $data['currentpage']['globalsections'][$sectionname] = $row;
      } else {
        $data['currentpage']['sections'][] = $row;
      }
    }
    $data['currentpage']['globalsections']['footer'] = getcontactinfosection($mysqli, $language, $previewmode)['data'][0];
    $data['success'] = true;
    echo json_encode($data);
    exit;
  }catch (Exception $e) {
    $data = array();
    $data['success'] = false;
    $data['message'] = $e->getMessage();
    echo json_encode($data);
    exit;
  }
}
