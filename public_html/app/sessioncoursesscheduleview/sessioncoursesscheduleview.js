'use strict';

var app = angular.module('cpa_admin', ['ngAnimate','ui.bootstrap','ngResource', 'ngRoute', 'cgBusy','core'])
  .config(function($locationProvider) {
            $locationProvider.html5Mode({
              enabled: true,
              requireBase: false
            });
    });

app.controller('sessioncoursesscheduleviewCtrl', function($rootScope, $scope, $uibModal, $http, $sce, $location, dialogService) {
  $scope.showToolbar = false;
  // Expect ?language=x&sessionid=x in the url. If not present, active session will be used.
  var search = $location.search();
  $scope.sessionid = search.sessionid;
  $scope.language = search.language;
  if (!$scope.language) $scope.language = 'fr-ca';

  $scope.getDayName = function(day, language) {
    // create array to hold name of each day
    var arFrench = ["Dim", "Lun", "Mar", "Mer", "Jeu", "Ven", "Sam"];
    var arEnglish = ["Sun","Mon","Tues","Wed","Thu","Fri","Sat"];
    if (language == "fr-ca") {
      return arFrench[day]
    }
    if (language == "en-ca") {
      return arEnglish[day]
    }
    return null;
  }

  $scope.getIntervalInCells = function(timestart, endtime) {
    var nbOfCell = 0;

    var startdatetime = new Date("1980/01/01 " + timestart);
    var enddatetime = new Date("1980/01/01 " + endtime);
    var nbOfCell = (enddatetime - startdatetime)/ 1000 / 60 / 5;

    if (nbOfCell < 0) nbOfCell = 0;
    return nbOfCell;
  }

  $scope.checkIfCoursesOverlap = function(course1, course2) {
    if (course1 != null && course2 != null) {
      var startdatetime1 = new Date("1980/01/01 " + course1.starttime);
      var enddatetime1 = new Date("1980/01/01 " + course1.endtime);
      var startdatetime2 = new Date("1980/01/01 " + course2.starttime);

      if (startdatetime2 >= startdatetime1 && startdatetime2 < enddatetime1) {
        return true;
      }
    }
    return false;
  }

  $scope.checkIfCoursesOverlapPerfectly = function(course1, course2) {
    if (course1 != null && course2 != null) {
      // var startdatetime1 = new Date("1980/01/01 " + course1.starttime);
      // var enddatetime1 = new Date("1980/01/01 " + course1.endtime);
      // var startdatetime2 = new Date("1980/01/01 " + course2.starttime);
      // var enddatetime2 = new Date("1980/01/01 " + course2.endtime);
      //
      // if (startdatetime2 == startdatetime1 && enddatetime1 == enddatetime2) {
      //   return true;
      // }
      if (course1.starttime == course2.starttime && course1.endtime == course2.endtime) {
        return true;
      }
    }
    return false;
  }

  $scope.createFilling = function(nbOfCell, cellHeight) {
    var txt = "";
    for (var i = 0; i < nbOfCell; i++) {
      txt += "<tr><td height='" + cellHeight + "'>&nbsp;</td></tr>";
    }
    return txt;
  }

  // Returns the time without the seconds in a string format
  $scope.shaveSecondsFromTime = function(time) {
    var datetime = new Date("1980/01/01 " + time);
    return $scope.shaveSecondsFromDateTime(datetime);
  }

  // Returns the time without the seconds in a string format
  $scope.shaveSecondsFromDateTime = function(datetime) {
    // var datetime = new Date("1980/01/01 " + time);
    return datetime.getHours() + ":" + (datetime.getMinutes() < 10 ? '0' : '') + datetime.getMinutes();
  }

  $scope.createIcetime = function(icetime, cellHeight) {
    var txt = "";
    var finalStyle = "style='background-color:red'";
    var nbOfCell = $scope.getIntervalInCells(icetime.starttime, icetime.endtime);

    // Test - create one row, but with a greater height
    txt += "<tr " + finalStyle + "><td height='" + (cellHeight*nbOfCell) + "'>"+ "&nbsp;</td></tr>";

    // for (var i = 0; i < nbOfCell; i++) {
    //   txt += "<tr " + finalStyle + "><td height='" + cellHeight + "'>"+ "&nbsp;</td></tr>";
    // }
    return txt;
  }

  $scope.getCourseColorCode = function(course) {
    var color = "";

    switch (course.coursecode) {
      case "PP":
        if (course.courselevel == 1) {
          color = "lightblue";
        } else {
          color = "blue";
        }
        break;
      case "STAR":
        if (course.courselevel == "JUN") {
          color = "yellow";
        } else if (course.courselevel == "SEN"){
          color = "orange";
        } else if (course.courselevel == "MIX"){
          color = "darkred";
        }
        break;
      case "PREP":
        color = "pink";
        break;
      case "JT":
      case "SEMIPRIV":
        color = "green";
        break;
      case "POUSSEE":
        color = "violet";
        break;
      default:
        color = "grey";
    }
    return color;
  }

  $scope.createCourse = function(course, cellHeight) {
    var txt = "";
    // var finalStyle = "style='background-color:" + $scope.getCourseColorCode(course) + "'";
    var finalStyle = "style='background-color:" + (course.levelcolor ? course.levelcolor : course.coursecolor) + "'";
    var nbOfCell = $scope.getIntervalInCells(course.starttime, course.endtime);
    txt += "<tr " + finalStyle + "><td height='" + cellHeight + "'><b>" + $scope.shaveSecondsFromTime(course.starttime) + " - " + $scope.shaveSecondsFromTime(course.endtime) + " " + course.courselabel + (course.courselevellabel != null ? ' ' + course.courselevellabel : '') + "</b></td></tr>";
    for (var i = 1; i < nbOfCell - 1; i++) {
      txt += "<tr " + finalStyle + "><td height='" + cellHeight + "'>"+ "&nbsp;</td></tr>";
    }
    txt += "<tr " + finalStyle + "><td height='" + cellHeight + "'></td></tr>";
    return txt;
  }

  $scope.createPerfectlyOverlapedCourse = function(course1, course2, cellHeight) {
    var txt = "";
    // var finalStyle = "style='background-color:" + $scope.getCourseColorCode(course1) + "'";
    var finalStyle = "style='background-color:" + (course1.levelcolor ? course1.levelcolor : course1.coursecolor) + "'";
    var nbOfCell = $scope.getIntervalInCells(course1.starttime, course1.endtime);
    txt += "<tr " + finalStyle + "><td height='" + cellHeight + "'><b>" + $scope.shaveSecondsFromTime(course1.starttime) + " - " + $scope.shaveSecondsFromTime(course1.endtime) + " " + course1.courselabel + (course1.courselevellabel != null ? " " + course1.courselevellabel : "");
    txt += "/" + course2.courselabel + (course2.courselevellabel != null ? ' ' + course2.courselevellabel : '') + "</b></td></tr>";
    for (var i = 1; i < nbOfCell - 1; i++) {
      txt += "<tr " + finalStyle + "><td height='" + cellHeight + "'>"+ "&nbsp;</td></tr>";
    }
    txt += "<tr " + finalStyle + "><td height='" + cellHeight + "'></td></tr>";
    return txt;
  }


  // This function draws the schedule for an arena/ice
  $scope.drawScheduleForArenaIce = function(language, arena) {
    // constant table settings - switch to css ?
    var headerHeight = 50; // height of the table's header cell
    var border = 2; // 3D height of table's border
    var cellspacing = 0; // width of table's border
    var headerColor = "midnightblue"; // color of table's header
    var headerSize = "+3"; // size of tables header font
    var colWidth = 60; // width of columns in table
    var dayCellHeight = 25; // height of cells containing days of the week
    var hourCellHeight = 20; // height of cells containing days of the week
    var dayColor = "darkblue"; // color of font representing week days
    var cellHeight = 40; // height of cells representing dates in the calendar
    var todayColor = "red"; // color specifying today's date in the calendar
    var timeColor = "purple"; // color of font representing current time
    var commentHeight = 80;

    // create basic table structure
    var text = ""; // initialize accumulative variable to empty string
    text += '<center>'
    text += '<table width="90%" border=' + border + ' cellspacing=' + cellspacing + ' cellpadding=0 margin=0 border-collapse="collapse">'; // table settings
    text += '<TH COLSPAN=' + ((arena.days.length *2) + 1) + ' HEIGHT=' + headerHeight + '>'; // create table header cell
    text += '<FONT COLOR="' + headerColor + '" SIZE=' + headerSize + '>'; // set font for table header
    text += '</FONT>'; // close table header's font settings
    text += '</TH>'; // close header cell

    // variables to hold constant settings
    var openCol = '<td colspan="2" width=' + colWidth + ' height=' + dayCellHeight + '>';
    openCol += '<font color="' + dayColor + '">';
    var closeCol = '</font></td>';

    // create first row of table to set column width and specify week day
    text += '<tr align="center" valign="center">';
    text += '<td width="10%" height=' + dayCellHeight + '><font color="' + dayColor + '"></font></td>';
    for (var i = 0; i < arena.days.length; i++) {
      text += openCol + arena.days[i].daylabel + closeCol;
    }
    text += '</TR>';

    // Get number of cell and create insideTable for hours column
    var startdatetime = new Date("1980/01/01 " + arena.minstarttime);

    // Total of cells for all days
    var nbOfCell = $scope.getIntervalInCells(arena.minstarttime, arena.maxendtime)

    // Inside table for the first column, with the time
    var insideTable = '<table width="100%" border=0 cellspacing=0 cellpadding=0 margin=0 border-collapse="collapse"><tr>';
    for (var i = 0; i <= nbOfCell; i++) {
      insideTable += "<tr><td align='right' valign='top' height='" + hourCellHeight + "'>";
      if (i == 0 || startdatetime.getMinutes() == 0 || startdatetime.getMinutes() % 10 == 0) {
        //  insideTable += startdatetime.getHours() + ":" + (startdatetime.getMinutes() < 10 ? '0' : '') + startdatetime.getMinutes();
         insideTable += $scope.shaveSecondsFromDateTime(startdatetime);
      } else {
        insideTable += '&nbsp;'
      }
      insideTable += "</td></tr>";
      startdatetime = new Date(startdatetime.getTime() + 5 * 60000);
    }
    insideTable += '</tr></table>'

    // Put inside table in the time column
    text += '<tr><td valign="top">' + insideTable + '</td>'; //'</tr>';

    // Inside tables for all other columns
    var insideTableArr = [];
    for (var i = 0; i < arena.days.length; i++) {
      var starttime = arena.minstarttime; // minimum start time for the arena
      var insideTable = '<table width="100%" border=0 cellspacing=0 cellpadding=0 margin=0 border-collapse="collapse"><tr>'; // table settings
      var courses = arena.days[i].courses;
      for (var j = 0; j < courses.length; j++) {
        var course = courses[j];
        var nbOfCell = $scope.getIntervalInCells(starttime, course.starttime)
        insideTable += $scope.createFilling(nbOfCell, hourCellHeight);
        // Create course
        // Check for overlap.
        if ($scope.checkIfCoursesOverlap(course, courses[j+1])) {
          // Special treatment, the courses overlap!
          if ($scope.checkIfCoursesOverlapPerfectly(course, courses[j+1])) {
            insideTable += $scope.createPerfectlyOverlapedCourse(course, courses[j+1], hourCellHeight);
          }
          j++;
        } else {
          insideTable += $scope.createCourse(course, hourCellHeight);
        }
        // Change starttime
        starttime = course.endtime;
      }
      insideTable += '</TABLE>';
      insideTableArr[i] = insideTable;
    }

    // Inside tables for all other columns
    var insideTableIceArr = [];
    for (var i = 0; i < arena.days.length; i++) {
      var starttime = arena.minstarttime; // minimum start time for the arena
      var insideTable = '<table width="100%" border=0 cellspacing=0 cellpadding=0 margin=0 border-collapse="collapse"><tr>'; // table settings
      var icetimes = arena.days[i].icetimes;
      for (var j = 0; j < icetimes.length; j++) {
        var icetime = icetimes[j];
        var nbOfCell = $scope.getIntervalInCells(starttime, icetime.starttime)
        insideTable += $scope.createFilling(nbOfCell, hourCellHeight);
        insideTable += $scope.createIcetime(icetime, hourCellHeight);
        // Change starttime
        starttime = icetime.endtime;
        // break;
      }
      insideTable += '</TABLE>';
      insideTableIceArr[i] = insideTable;
    }

    for (var i = 0; i < insideTableArr.length; i++) {
      text += '<td valign="top">' + insideTableArr[i] + '</td>'; //'</tr>';
      // If ice time exists, if not put blank column
      if (insideTableIceArr[i]) {
        text += '<td valign="top">' + insideTableIceArr[i] + '</td>'; //'</tr>';
      } else {
        text += '<td valign="top">&nbsp;</td>'; //'</tr>';
      }
    }
    text += '</tr>';
    text += '</TABLE>';
    text += '</CENTER>';

    return text;
  }

  $scope.getSessionInfo = function() {
    $http({
       method: 'post',
       url: './sessioncoursesschedule.php',
       data: $.param({'sessionid' : $scope.sessionid, 'language' : $scope.language, 'type' : 'getSessionDetails' }),
       headers: {'Content-Type': 'application/x-www-form-urlencoded'}
    }).
    success(function(data, status, headers, config) {
      if (data.success) {
        if (!angular.isUndefined(data.data)) {
          $scope.currentSession = data.data[0];
          for (var i = 0; i < $scope.currentSession.arenas.length; i++) {
            $scope.currentSession.arenas[i].schedule = $sce.trustAsHtml($scope.drawScheduleForArenaIce($scope.language, $scope.currentSession.arenas[i]));
          }
        } else {
          $scope.currentSession = null;
        }
     } else {
       if(!data.success){
         dialogService.displayFailure(data);
       }
     }
    }).
    error(function(data, status, headers, config) {
     dialogService.displayFailure(data);
    });
 };

 $scope.getSessionInfo();

});
