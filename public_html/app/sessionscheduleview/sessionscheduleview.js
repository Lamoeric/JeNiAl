'use strict';

var app = angular.module('cpa_admin', ['ngAnimate','ui.bootstrap','ngResource', 'ngRoute', 'cgBusy','core'])
  .config(function($locationProvider) {
      $locationProvider.html5Mode({
        enabled: true,
        requireBase: false
      });
    });

app.controller('sessionscheduleviewCtrl', function($rootScope, $scope, $uibModal, $http, $sce, $location, dialogService, parseISOdateService) {
  $scope.showToolbar = false;
  // Expect ?language=x&sessionid=x in the url. If not present, active session will be used.
  var search = $location.search();
  $scope.sessionid = search.sessionid;
  $scope.language = search.language;
  if (!$scope.language) $scope.language = 'fr-ca';
  $scope.getDays = function(month, year) {
    var nbDaysFeb = (year % 4 == 0) ? 29 : 28;
    var ar = [31,nbDaysFeb,31,30,31,30,31,31,30,31,30,31];
    return ar[month]
  }

  $scope.getMonthName = function(month, language) {
    // create array to hold name of each month
    var arFrench = ["Janvier", "Février", "Mars", "Avril", "Mai", "Juin", "Juillet", "Août", "Septembre", "Octobre", "Novembre", "Décembre"];
    var arEnglish = ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];
    if (language == "fr-ca") {
      return arFrench[month]
    }
    if (language == "en-ca") {
      return arEnglish[month]
    }
    return null;
  }

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

  $scope.getSessionNumberOfMonth = function() {
    var months;
    if ($scope.currentSession.enddate.getFullYear() == $scope.currentSession.startdate.getFullYear()) { // same year
      months = $scope.currentSession.enddate.getMonth() - $scope.currentSession.startdate.getMonth() + 1;
    } else {
      months = (12 - $scope.currentSession.startdate.getMonth()) + ($scope.currentSession.enddate.getMonth() + 1);
      // if more than one year appart
      if ($scope.currentSession.enddate.getFullYear() - $scope.currentSession.startdate.getFullYear() > 1) {
        months = months + (12 * ($scope.currentSession.enddate.getFullYear() - $scope.currentSession.startdate.getFullYear() -1));
      }
    }
    return months <= 0 ? 0 : months;
  }

  // $scope.getSessionNumberOfMonth = function() {
  //   var months;
  //   months = ($scope.currentSession.enddate.getFullYear() - $scope.currentSession.startdate.getFullYear()) * 12;
  //   if (months == 0) { // same year
  //     months = $scope.currentSession.enddate.getMonth();
  //     months -= $scope.currentSession.startdate.getMonth();
  //     months++;
  //   } else {
  //     months -= $scope.currentSession.startdate.getMonth() + 1;
  //     months += $scope.currentSession.enddate.getMonth();
  //   }
  //   return months <= 0 ? 0 : months;
  // }

  $scope.getMonthAndYear = function(startDate, index, language) {
    var newDate = new Date(startDate.getTime());
    // var newDate = startDate;
    newDate = new Date(newDate.setMonth(startDate.getMonth() + index));
    var newYear = newDate.getYear();
    if (newYear < 1000) {
      newYear+=1900;
    }
    var newMonth = newDate.getMonth();
    var calendarHtml = $sce.trustAsHtml($scope.drawCalendar(newMonth, newYear, $scope.currentSession.specialDatesArr, language));
    return {month:newMonth, year:newYear, html:calendarHtml};
  }

  $scope.createMonthArray = function(language) {
    var nbOfMonths = Math.ceil($scope.currentSession.nbofmonths / 3);
    // var rows = new Array();
    var rows = [];
    // var rows = [ln];
    var monthIndex = 0;
    for (var i = 0; i < nbOfMonths; i++) {
      var cols = new Array(3);
      // rows[i] = cols;
      rows.push(cols);
      for (var y = 0; y < cols.length; y++) {
        var info = $scope.getMonthAndYear($scope.currentSession.startdate, monthIndex, language); //{month:monthIndex, year:2017};
        cols[y] = info;
        monthIndex++;
      }
    }
    return rows;
  }

  // Use all special dates from session and registrations and events too create an array of dates.
  $scope.createSpecialDateArray = function(language) {
    var dateArr = new Array();

    // var newDate = new Date($scope.currentSession.startdate.getTime());
    // dateArr.push({date:newDate, type:'EVENT', label:'Début de la session'});
    // var newDate = new Date($scope.currentSession.enddate.getTime());
    // dateArr.push({date:newDate, type:'EVENT', label:'Fin de la session'});
    var newDate = new Date($scope.currentSession.coursesstartdate.getTime());
    dateArr.push({date:newDate, type:'EVENT', label: (language == 'fr-ca' ? 'Début des cours' : 'Start of courses')});
    var newDate = new Date($scope.currentSession.coursesenddate.getTime());
    dateArr.push({date:newDate, type:'EVENT', label: (language == 'fr-ca' ? 'Fin des cours' : 'End of courses')});

    for (var i = 0; i < $scope.currentSession.registrations.length; i++) {
      $scope.currentSession.registrations[i].date = parseISOdateService.parseDate($scope.currentSession.registrations[i].date + "T00:00:00");
      dateArr.push($scope.currentSession.registrations[i]);
    }

    for (var i = 0; i < $scope.currentSession.events.length; i++) {
      $scope.currentSession.events[i].date = parseISOdateService.parseDate($scope.currentSession.events[i].date + "T00:00:00");
      dateArr.push($scope.currentSession.events[i]);
    }

    return dateArr;
  }

  // This function draws the calendar and mark the special date with a symbol
  $scope.drawCalendar = function(month, year, specialDatesArr, language) {
    // constant table settings - switch to css ?
    var headerHeight = 50; // height of the table's header cell
    var border = 2; // 3D height of table's border
    var cellspacing = 4; // width of table's border
    var headerColor = "midnightblue"; // color of table's header
    var headerSize = "+3"; // size of tables header font
    var colWidth = 60; // width of columns in table
    var dayCellHeight = 25; // height of cells containing days of the week
    var dayColor = "darkblue"; // color of font representing week days
    var cellHeight = 40; // height of cells representing dates in the calendar
    var todayColor = "red"; // color specifying today's date in the calendar
    var timeColor = "purple"; // color of font representing current time
    var commentHeight = 80;

    // Some information
    var firstDayInstance = new Date(year, month, 1);
    var firstDay = firstDayInstance.getDay();
    var lastDate = $scope.getDays(month, year);
    var monthName = $scope.getMonthName(month, $scope.language);
    var date = null;
    var dateComment = [];

    // create basic table structure
    var text = ""; // initialize accumulative variable to empty string
    // text += '<CENTER>'
    text += '<TABLE width="100%" BORDER=' + border + ' CELLSPACING=' + cellspacing + '>'; // table settings
    text += '<TH COLSPAN=7 HEIGHT=' + headerHeight + '>'; // create table header cell
    text += '<FONT COLOR="' + headerColor + '" SIZE=' + headerSize + '>'; // set font for table header
    text += monthName + ' ' + year;
    text += '</FONT>'; // close table header's font settings
    text += '</TH>'; // close header cell

    // variables to hold constant settings
    var openCol = '<TD WIDTH=' + colWidth + ' HEIGHT=' + dayCellHeight + '>';
    openCol += '<FONT COLOR="' + dayColor + '">';
    var closeCol = '</FONT></TD>';

    // create first row of table to set column width and specify week day
    text += '<TR ALIGN="center" VALIGN="center">';
    for (var dayNum = 0; dayNum < 7; ++dayNum) {
      text += openCol + $scope.getDayName(dayNum, language) + closeCol;
    }
    text += '</TR>';

    // declaration and initialization of two variables to help with tables
    var digit = 1;
    var curCell = 0;
    var row;
    for (row = 1; row <= Math.ceil((lastDate + firstDay) / 7); ++row) {
      text += '<TR ALIGN="right" VALIGN="top">';
      for (var col = 1; col <= 7; ++col) {
        if (digit > lastDate)
          break;
        if (curCell < firstDay) {
          text += '<TD></TD>';
          curCell++;
        } else {
          var dateFound = false;
          var dateType = "";
          var symbols = [];
          for (var y = 0; y < specialDatesArr.length; y++) {
            var specialYear =  specialDatesArr[y].date.getYear();
            var specialMonth = specialDatesArr[y].date.getMonth();
            var specialDay =   specialDatesArr[y].date.getDate();
            if (specialYear < 1000) {
              specialYear+=1900;
            }

            if (specialYear == year && specialMonth == month && specialDay == digit) { // current cell represent today's date
              dateFound = true;
              dateType = specialDatesArr[y].type;
              dateComment.push(digit + ' - ' + specialDatesArr[y].label);

              if (dateType == 'EVENT' && symbols.indexOf('*') == -1) {
                symbols.push('*');
              } else if (dateType == 'REGISTRATION' && symbols.indexOf('@') == -1) {
                symbols.push('@');
              } else if (dateType == 'CLOSED' && symbols.indexOf('X') == -1) {
                symbols.push('X');
              } else if (symbols.indexOf('*') == -1) {
                symbols.push('*');
              }


            }
          }
          if (dateFound) {
            text += '<TD HEIGHT=' + cellHeight + '>';
            text += digit;
            text += '<BR>';
            text += '<FONT COLOR="red" SIZE=5>';
            text += '<CENTER>';
            text += symbols.join(' ');
            text += '</CENTER></FONT>';
            text += '</TD>';
          } else {
            text += '<TD HEIGHT=' + cellHeight + '>' + digit + '</TD>';
          }
          digit++;
        }
      }
      text += '</TR>';
    }
    var totalCommentHeight = commentHeight/1 + (row == 7 ? 0 : (cellHeight/1 + cellspacing*2/1)) - dateComment.length * 4;
    text += '<TR  ALIGN="left" VALIGN="top"><TD COLSPAN=7 HEIGHT=' + totalCommentHeight + '>';
    text += '<FONT COLOR="' + headerColor + '" SIZE=2></FONT>';
    text += dateComment.join('<br>');
    text += '</TD></TR>';
    text += '</TABLE>';
    text += '</CENTER>';

    return text;
  }

  $scope.getSessionInfo = function() {
    $http({
       method: 'post',
       url: './sessionschedule.php',
       data: $.param({'sessionid' : $scope.sessionid, 'language' : $scope.language, 'type' : 'getSessionDetails' }),
       headers: {'Content-Type': 'application/x-www-form-urlencoded'}
    }).
    success(function(data, status, headers, config) {
      if (data.success) {
        if (!angular.isUndefined(data.data)) {
          $scope.currentSession = data.data[0];
          $scope.currentSession.startdate = 				parseISOdateService.parseDate($scope.currentSession.startdate + "T00:00:00");
  				$scope.currentSession.enddate = 					parseISOdateService.parseDate($scope.currentSession.enddate + "T00:00:00");
  				$scope.currentSession.coursesstartdate = 	parseISOdateService.parseDate($scope.currentSession.coursesstartdate + "T00:00:00");
  				$scope.currentSession.coursesenddate = 		parseISOdateService.parseDate($scope.currentSession.coursesenddate + "T00:00:00");
  				$scope.currentSession.reimbursementdate = parseISOdateService.parseDate($scope.currentSession.reimbursementdate + "T00:00:00");
  				$scope.currentSession.lastupdateddate = 	parseISOdateService.parseDate($scope.currentSession.lastupdateddate);
          $scope.currentSession.nbofmonths =        $scope.getSessionNumberOfMonth();
          $scope.currentSession.specialDatesArr =   $scope.createSpecialDateArray($scope.language);
          $scope.currentSession.monthArr =          $scope.createMonthArray($scope.language);
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
