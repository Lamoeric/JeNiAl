var app = angular.module('cpa_ws_admin', []);

app.controller('websiteCtrl', function($scope, $http, $sce, $timeout, $anchorScroll, dialogService, parseISOdateService) {
	$scope.remarkable = new Remarkable({
		  html:         false,        // Enable HTML tags in source
		  xhtmlOut:     false,        // Use '/' to close single tags (<br />)
		  breaks:       false         // Convert '\n' in paragraphs into <br>
//		  langPrefix:   'language-',  // CSS language prefix for fenced blocks
//		  typographer:  false,				// Enable some language-neutral replacement + quotes beautification
//		  quotes: '“”‘’',							// Double + single quotes replacement pairs, when typographer enabled, and smartquotes on. Set doubles to '«»' for Russian, '„“' for German.
//		  highlight: function (/*str, lang*/) { return ''; } // Highlighter function. Should return escaped HTML, or '' if the source string is not changed
	});
	
  $scope.pagename 		 =	null;
  $scope.language 		 = null;
  $scope.costumeid 		 = null;
  $scope.testsessionid = null;
  $scope.previewMode 	 = false;
	
  $scope.getcurrentpageinfo = function () {
		return $http({
	      method: 'post',
	      url: '../php/getcurrentpageinfo.php',					// For local dev only
//	      url: '../../../php/getcurrentpageinfo.php',
	      data: $.param({'pagename' : $scope.pagename, 'costumeid' : $scope.costumeid, 'testsessionid' : $scope.testsessionid, 'previewmode' : $scope.previewMode, 'language' : $scope.language}),
	      headers: {'Content-Type': 'application/x-www-form-urlencoded'}
    }).
    success(function(data, status, headers, config) {
      if (data.success) {
        if (!angular.isUndefined(data.navbar)) {
          $scope.navbar = data.navbar;
          $scope.currentpage = data.currentpage;
          $scope.today = new Date();

          // Newssummary section needs to be sanitized
          if ($scope.currentpage.globalsections.newssummarycarousel && $scope.currentpage.globalsections.newssummarycarousel.news) {
            var news = $scope.currentpage.globalsections.newssummarycarousel.news;
            for (var i = 0; i < news.length; i++) {
              news[i].shortversion = news[i].shortversion.replace(/(?:\r\n|\r|\n)/g, '<br />');
              news[i].shortversion = $sce.trustAsHtml(news[i].shortversion);
            }
          }

          // Newssummary section needs to be sanitized
          if ($scope.currentpage.globalsections.newssummaryfix && $scope.currentpage.globalsections.newssummaryfix.news) {
            var news = $scope.currentpage.globalsections.newssummaryfix.news;
            for (var i = 0; i < news.length; i++) {
              news[i].shortversion = news[i].shortversion.replace(/(?:\r\n|\r|\n)/g, '<br />');
              news[i].shortversion = $sce.trustAsHtml(news[i].shortversion);
            }
          }

          // Newslist section needs to be sanitized
          if ($scope.currentpage.globalsections.newslist && $scope.currentpage.globalsections.newslist.news) {
            var news = $scope.currentpage.globalsections.newslist.news;
            for (var i = 0; i < news.length; i++) {
              news[i].longversion = news[i].longversion.replace(/(?:\r\n|\r|\n)/g, '<br />');
              news[i].longversion = $sce.trustAsHtml(news[i].longversion);
            }
          }

          // permanentnews1 section needs to be sanitized
          if ($scope.currentpage.globalsections.permanentnews1 && $scope.currentpage.globalsections.permanentnews1.paragraphs) {
            var paragraphs = $scope.currentpage.globalsections.permanentnews1.paragraphs;
            for (var i = 0; i < paragraphs.length; i++) {
              paragraphs[i].paragraphtext = paragraphs[i].paragraphtext .replace(/(?:\r\n|\r|\n)/g, '<br />');
              paragraphs[i].paragraphtext  = $sce.trustAsHtml(paragraphs[i].paragraphtext );
            }
          }

          // permanentnews2 section needs to be sanitized
          if ($scope.currentpage.globalsections.permanentnews2 && $scope.currentpage.globalsections.permanentnews2.paragraphs) {
            var paragraphs = $scope.currentpage.globalsections.permanentnews2.paragraphs;
            for (var i = 0; i < paragraphs.length; i++) {
              paragraphs[i].paragraphtext = paragraphs[i].paragraphtext.replace(/(?:\r\n|\r|\n)/g, '<br />');
              paragraphs[i].paragraphtext  = $sce.trustAsHtml(paragraphs[i].paragraphtext);
            }
          }

          // costumedetails section needs to be sanitized
          if ($scope.currentpage.globalsections.costumedetails && $scope.currentpage.globalsections.costumedetails.onecostume) {
            var onecostume = $scope.currentpage.globalsections.costumedetails.onecostume;
            onecostume.girldescription = onecostume.girldescription.replace(/(?:\r\n|\r|\n)/g, '<br />');
            onecostume.girldescription = $sce.trustAsHtml(onecostume.girldescription);
            onecostume.boydescription = onecostume.boydescription.replace(/(?:\r\n|\r|\n)/g, '<br />');
            onecostume.boydescription = $sce.trustAsHtml(onecostume.boydescription);
            onecostume.solodescription = onecostume.solodescription.replace(/(?:\r\n|\r|\n)/g, '<br />');
            onecostume.solodescription = $sce.trustAsHtml(onecostume.solodescription);
          }

          // goodslist section needs to be sanitized
          if ($scope.currentpage.globalsections.goodslist && $scope.currentpage.globalsections.goodslist.goodslist) {
            var goods = $scope.currentpage.globalsections.goodslist.goodslist;
            for (var i = 0; i < goods.length; i++) {
              goods[i].description = goods[i].description.replace(/(?:\r\n|\r|\n)/g, '<br />');
              goods[i].description  = $sce.trustAsHtml(goods[i].description);
            }
          }

          // classifiedaddslist section needs to be sanitized
          if ($scope.currentpage.globalsections.classifiedaddslist && $scope.currentpage.globalsections.classifiedaddslist.classifiedaddslist) {
            var adds = $scope.currentpage.globalsections.classifiedaddslist.classifiedaddslist;
            for (var i = 0; i < adds.length; i++) {
              adds[i].description = adds[i].description.replace(/(?:\r\n|\r|\n)/g, '<br />');
              adds[i].description  = $sce.trustAsHtml(adds[i].description);
            }
          }

          // Schedule section needs to be reorganized by week
          if ($scope.currentpage.globalsections.weeklyschedule && $scope.currentpage.globalsections.weeklyschedule.allschedules) {
            var tempschedules = $scope.currentpage.globalsections.weeklyschedule.allschedules;
            $scope.currentpage.globalsections.weeklyschedule.allschedules.schedules = [];
            var weekSchedule = [];
            var realIndex = 0;
            for (var i = 0; i < tempschedules.length; i++) {
              var schedule = tempschedules[i];
              var previousSchedule = (i != 0 ? tempschedules[i-1] : null);
              if (previousSchedule != null && schedule.weekno !=  tempschedules[i-1].weekno) {
                $scope.currentpage.globalsections.weeklyschedule.allschedules.schedules.push(weekSchedule);
                var weekSchedule = [];
              }
              weekSchedule.push(schedule);
            }
            // At the end of the loop, add the last weekSchedule
            $scope.currentpage.globalsections.weeklyschedule.allschedules.schedules.push(weekSchedule);
          } else {
            if ($scope.currentpage.globalsections.weeklyschedule) {
              $scope.currentpage.globalsections.weeklyschedule.allschedules.schedules = [];
            }
          }

          // Schedule section needs to be reorganized by week
          if ($scope.currentpage.globalsections.allschedules && $scope.currentpage.globalsections.allschedules.allschedules) {
            var tempschedules = $scope.currentpage.globalsections.allschedules.allschedules;
            $scope.currentpage.globalsections.allschedules.allschedules.schedules = [];
            var weekSchedule = [];
            var realIndex = 0;
            for (var i = 0; i < tempschedules.length; i++) {
              var schedule = tempschedules[i];
              var previousSchedule = (i != 0 ? tempschedules[i-1] : null);
              if (previousSchedule != null && schedule.weekno !=  tempschedules[i-1].weekno) {
                $scope.currentpage.globalsections.allschedules.allschedules.schedules.push(weekSchedule);
                var weekSchedule = [];
              }
              weekSchedule.push(schedule);
            }
            // At the end of the loop, add the last weekSchedule
            $scope.currentpage.globalsections.allschedules.allschedules.schedules.push(weekSchedule);
          } else {
            if ($scope.currentpage.globalsections.allschedules) {
              $scope.currentpage.globalsections.allschedules.allschedules.schedules = [];
            }
          }

          // Arenas section needs to be sanitized
          if ($scope.currentpage.globalsections.arenas && $scope.currentpage.globalsections.arenas.arenas) {
            for (var i = 0; i < $scope.currentpage.globalsections.arenas.arenas.length; i++) {
              $scope.currentpage.globalsections.arenas.arenas[i].link = $sce.trustAsResourceUrl($scope.currentpage.globalsections.arenas.arenas[i].link);
            }
          }

          // Boardmembers section needs to be sanitized
          if ($scope.currentpage.globalsections.boardmembers && $scope.currentpage.globalsections.boardmembers.boardmembers) {
            for (var i = 0; i < $scope.currentpage.globalsections.boardmembers.boardmembers.length; i++) {
              var member = $scope.currentpage.globalsections.boardmembers.boardmembers[i];
              var desc = (member.memberrole && member.memberrole != '' ? member.memberrole : '&nbsp;') + '<br>' +
                         (member.email && member.email != '' ? member.email : '&nbsp;')  + '<br>' +
                         (member.phone && member.phone != '' ? member.phone : '&nbsp;')  + '<br>' +
                         (member.description && member.description != '' ? member.description : '&nbsp;');
              member.desc = $sce.trustAsHtml(desc);
            }
          }

          // Coaches section needs to be sanitized
          // TODO : we need a better solution for languages
          if ($scope.currentpage.globalsections.coaches && $scope.currentpage.globalsections.coaches.coaches) {
            for (var i = 0; i < $scope.currentpage.globalsections.coaches.coaches.length; i++) {
              var coach = $scope.currentpage.globalsections.coaches.coaches[i];
              var desc = null;
              if ($scope.language == 'fr-ca') {
                desc = "<b>Courriel&nbsp;:&nbsp;</b>" + (coach.email && coach.email != '' ? coach.email : '&nbsp;')  + '<br>';
                desc += "<b>Téléphone&nbsp;:&nbsp;</b>" + (coach.phone && coach.phone != '' ? coach.phone : '&nbsp;')  + '<br>';
                desc += "<b>Enseigne depuis&nbsp;:&nbsp;</b>" + (coach.coachsince && coach.coachsince != '' ? coach.coachsince : '&nbsp;')  + '<br>';
                desc += "<b>Niveau&nbsp;:&nbsp;</b>" + (coach.coachlevel && coach.coachlevel != '' ? coach.coachlevel : '&nbsp;')  + '<br>';
                desc += "<b>Danse&nbsp;:&nbsp;</b>" + (coach.dancelevellabel && coach.dancelevellabel != '' ? coach.dancelevellabel : '&nbsp;')  + '<br>';
                desc += "<b>Habiletés&nbsp;:&nbsp;</b>" + (coach.skillslevellabel && coach.skillslevellabel != '' ? coach.skillslevellabel : '&nbsp;')  + '<br>';
                desc += "<b>Style libre&nbsp;:&nbsp;</b>" + (coach.freestylelevellabel && coach.freestylelevellabel != '' ? coach.freestylelevellabel : '&nbsp;')  + '<br>';
                // Optional section
                desc += (coach.interpretativesinglelevellabel && coach.interpretativesinglelevellabel != '' ? "<b>Interprétation simple&nbsp;:&nbsp;</b>" + coach.interpretativesinglelevellabel : '&nbsp;')  + '<br>';
                desc += (coach.interpretativecouplelevellabel && coach.interpretativecouplelevellabel != '' ? "<b>Interprétation couple&nbsp;:&nbsp;</b>" + coach.interpretativecouplelevellabel : '&nbsp;')  + '<br>';
                desc += (coach.competitivesinglelevellabel && coach.competitivesinglelevellabel != '' ? "<b>Compétition simple&nbsp;:&nbsp;</b>" + coach.competitivesinglelevellabel : '&nbsp;')  + '<br>';
                desc += (coach.competitivecouplelevellabel && coach.competitivecouplelevellabel != '' ? "<b>Compétition couple&nbsp;:&nbsp;</b>" + coach.competitivecouplelevellabel : '&nbsp;')  + '<br>';
                desc += (coach.competitivedancelevellabel && coach.competitivedancelevellabel != '' ? "<b>Compétition danse&nbsp;:&nbsp;</b>" + coach.competitivedancelevellabel : '&nbsp;')  + '<br>';
                desc += (coach.competitivesynchrolevellabel && coach.competitivesynchrolevellabel != '' ? "<b>Compétition synchro&nbsp;:&nbsp;</b>" + coach.competitivesynchrolevellabel : '&nbsp;')  + '<br><br>';
                desc += (coach.competitivetext && coach.competitivetext != '' ? "<b>Expérience niveau compétition&nbsp;:&nbsp;</b><br>" + coach.competitivetext : '<br><br><br><br><br>') ;
              } else if ($scope.language == 'en-ca') {
                desc = "<b>Email&nbsp;:&nbsp;</b>" + (coach.email && coach.email != '' ? coach.email : '&nbsp;')  + '<br>';
                desc += "<b>Phone&nbsp;:&nbsp;</b>" + (coach.phone && coach.phone != '' ? coach.phone : '&nbsp;')  + '<br>';
                desc += "<b>Coaches since&nbsp;:&nbsp;</b>" + (coach.coachsince && coach.coachsince != '' ? coach.coachsince : '&nbsp;')  + '<br>';
                desc += "<b>Level&nbsp;:&nbsp;</b>" + (coach.coachlevel && coach.coachlevel != '' ? coach.coachlevel : '&nbsp;')  + '<br>';
                desc += "<b>Dance&nbsp;:&nbsp;</b>" + (coach.dancelevellabel && coach.dancelevellabel != '' ? coach.dancelevellabel : '&nbsp;')  + '<br>';
                desc += "<b>Skills&nbsp;:&nbsp;</b>" + (coach.skillslevellabel && coach.skillslevellabel != '' ? coach.skillslevellabel : '&nbsp;')  + '<br>';
                desc += "<b>Free Style&nbsp;:&nbsp;</b>" + (coach.freestylelevellabel && coach.freestylelevellabel != '' ? coach.freestylelevellabel : '&nbsp;')  + '<br>';
                // Optional section
                desc += (coach.interpretativesinglelevellabel && coach.interpretativesinglelevellabel != '' ? "<b>Interpretative Single&nbsp;:&nbsp;</b>" + coach.interpretativesinglelevellabel : '&nbsp;')  + '<br>';
                desc += (coach.interpretativecouplelevellabel && coach.interpretativecouplelevellabel != '' ? "<b>Interpretative Couple&nbsp;:&nbsp;</b>" + coach.interpretativecouplelevellabel : '&nbsp;')  + '<br>';
                desc += (coach.competitivesinglelevellabel && coach.competitivesinglelevellabel != '' ? "<b>Competitive Single&nbsp;:&nbsp;</b>" + coach.competitivesinglelevellabel : '&nbsp;')  + '<br>';
                desc += (coach.competitivecouplelevellabel && coach.competitivecouplelevellabel != '' ? "<b>Competitive Couple&nbsp;:&nbsp;</b>" + coach.competitivecouplelevellabel : '&nbsp;')  + '<br>';
                desc += (coach.competitivedancelevellabel && coach.competitivedancelevellabel != '' ? "<b>Competitive Dance&nbsp;:&nbsp;</b>" + coach.competitivedancelevellabel : '&nbsp;')  + '<br>';
                desc += (coach.competitivesynchrolevellabel && coach.competitivesynchrolevellabel != '' ? "<b>Competitive Synchro&nbsp;:&nbsp;</b>" + coach.competitivesynchrolevellabel : '&nbsp;')  + '<br><br>';
                desc += (coach.competitivetext && coach.competitivetext != '' ? "<b>Experience Competitive Level&nbsp;:&nbsp;</b><br>" + coach.competitivetext : '<br><br><br><br><br>') ;
              }
              coach.desc = $sce.trustAsHtml(desc);
            }
          }
          // Partners section needs to figure out the number of column to try to center the partners
          // TODO : use this ?
          if ($scope.currentpage.globalsections.partners && $scope.currentpage.globalsections.partners.icons) {
            $scope.partnerclass = "col-md-2"; // use ng-class="[partnerclass]" in html
            if ($scope.currentpage.globalsections.partners.icons.length < 12) {
              var temp = 12 / $scope.currentpage.globalsections.partners.icons.length;
              if (temp < 2) temp = 2;
              $scope.partnerclass = "col-md-" + temp.toString();
            }
          }

          // Permanent news section : needs to figure out the number of column
          if ($scope.currentpage.globalsections.newssummaryfix) {
            $scope.newscolumn = "col-xs-12 col-md-12";
            if ($scope.currentpage.globalsections.permanentnews1 && $scope.currentpage.globalsections.permanentnews2) {
              // Both sections are present
              $scope.newscolumn = "col-xs-12 col-md-4";
            } else if ($scope.currentpage.globalsections.permanentnews1 || $scope.currentpage.globalsections.permanentnews2) {
              // One of the section is present
              $scope.newscolumn = "col-xs-12 col-md-6";
            }
          }

          // Rules section needs to be sanitized
          if ($scope.currentpage.globalsections.activesessioninfo && $scope.currentpage.globalsections.activesessioninfo.rules) {
            $scope.currentpage.globalsections.activesessioninfo.rules = $sce.trustAsHtml($scope.currentpage.globalsections.activesessioninfo.rules);
          }

          // links section needs to be sanitized for every section of the page
          for (var i in $scope.currentpage.globalsections) {
            if ($scope.currentpage.globalsections[i] != null) {
              for (var x = 0; $scope.currentpage.globalsections[i].bottomlinks && x < $scope.currentpage.globalsections[i].bottomlinks.length; x++) {
                $scope.currentpage.globalsections[i].bottomlinks[x].linkexternal = $sce.trustAsResourceUrl($scope.currentpage.globalsections[i].bottomlinks[x].linkexternal);
              }
              for (var x = 0; $scope.currentpage.globalsections[i].toplinks && x < $scope.currentpage.globalsections[i].toplinks.length; x++) {
                $scope.currentpage.globalsections[i].toplinks[x].linkexternal = $sce.trustAsResourceUrl($scope.currentpage.globalsections[i].toplinks[x].linkexternal);
              }
            }
          }

          // paragraphs section needs to be sanitized for every section of the page
          for (var i in $scope.currentpage.globalsections) {
            if ($scope.currentpage.globalsections[i] != null && $scope.currentpage.globalsections[i].paragraphs != null) {
            	var len = $scope.currentpage.globalsections[i].paragraphs.length;
              for (var x = 0;  x < len; x++) {
                $scope.convertParagraph($scope.currentpage.globalsections[i].paragraphs[x]);
              }
            } else if ($scope.currentpage.globalsections[i] != null && (i == 'programgroup' || i == 'sessiongroup')) {
		          for (var y in $scope.currentpage.globalsections[i]) {
		            if ($scope.currentpage.globalsections[i][y] != null && $scope.currentpage.globalsections[i][y].paragraphs != null) {
		            	var len = $scope.currentpage.globalsections[i][y].paragraphs.length;
		              for (var x = 0;  x < len; x++) {
		                $scope.convertParagraph($scope.currentpage.globalsections[i][y].paragraphs[x]);
		              }
	              }
              }
            }
          }
          if ($scope.currentpage.globalsections['showlist'] != null) {
	          for (var y = 0; y < $scope.currentpage.globalsections['showlist'].showlist.length; y++) {
            	var len = $scope.currentpage.globalsections['showlist'].showlist[y].paragraphs.length;
              for (var x = 0;  x < len; x++) {
                $scope.convertParagraph($scope.currentpage.globalsections['showlist'].showlist[y].paragraphs[x]);
              }
            	var len = $scope.currentpage.globalsections['showlist'].showlist[y].rulesparagraphs.length;
              for (var x = 0;  x < len; x++) {
                $scope.convertParagraph($scope.currentpage.globalsections['showlist'].showlist[y].rulesparagraphs[x]);
              }
          	}
          }

          // Depending on indicator for motion, initialize or not the WOW library.
          if ($scope.currentpage.globalsections.footer.supportmotion == 1) {
            new WOW().init();
          }

          if ($scope.currentpage.globalsections.testsessionlist && $scope.currentpage.globalsections.testsessionlist.testsessionlist.length != 0) {
            for (var i = 0; i < $scope.currentpage.globalsections.testsessionlist.testsessionlist.length; i++) {
              $scope.currentpage.globalsections.testsessionlist.testsessionlist[i].closedate = null;
      				if ($scope.currentpage.globalsections.testsessionlist.testsessionlist[i].periods && $scope.currentpage.globalsections.testsessionlist.testsessionlist[i].periods.length != 0) {
      					$scope.currentpage.globalsections.testsessionlist.testsessionlist[i].closedate = parseISOdateService.parseDate($scope.currentpage.globalsections.testsessionlist.testsessionlist[i].periods[$scope.currentpage.globalsections.testsessionlist.testsessionlist[i].periods.length-1].testdate + " 23:59:59");
      				}
      				$scope.currentpage.globalsections.testsessionlist.testsessionlist[i].registrationstartdate2 = parseISOdateService.parseDateWithoutTime($scope.currentpage.globalsections.testsessionlist.testsessionlist[i].registrationstartdate);
      				$scope.currentpage.globalsections.testsessionlist.testsessionlist[i].registrationenddate2   = parseISOdateService.parseDate($scope.currentpage.globalsections.testsessionlist.testsessionlist[i].registrationenddate + " 23:59:59");
      				$scope.currentpage.globalsections.testsessionlist.testsessionlist[i].cancellationenddate2   = parseISOdateService.parseDate($scope.currentpage.globalsections.testsessionlist.testsessionlist[i].cancellationenddate + " 23:59:59");
      				$scope.currentpage.globalsections.testsessionlist.testsessionlist[i].isClosed 							= false;		// Session is closed, i.e. today is passed the day of the last period
      				$scope.currentpage.globalsections.testsessionlist.testsessionlist[i].canRegister 					= false;		// User can register test in the session
      				$scope.currentpage.globalsections.testsessionlist.testsessionlist[i].canUnregister 				= false;		// User can delete the test registration
      				$scope.currentpage.globalsections.testsessionlist.testsessionlist[i].canCancelRegistration = false;		// User can only cancel the test registration, not delete it
      				$scope.today = new Date();
      				if ($scope.currentpage.globalsections.testsessionlist.testsessionlist[i].closedate && $scope.today > $scope.currentpage.globalsections.testsessionlist.testsessionlist[i].closedate) {
      					$scope.currentpage.globalsections.testsessionlist.testsessionlist[i].isClosed = true;
      				}
      				if ($scope.today >= $scope.currentpage.globalsections.testsessionlist.testsessionlist[i].registrationstartdate2 && $scope.today <= $scope.currentpage.globalsections.testsessionlist.testsessionlist[i].registrationenddate2) {
      					$scope.currentpage.globalsections.testsessionlist.testsessionlist[i].canRegister = true;
      				}
      				if ($scope.today > $scope.currentpage.globalsections.testsessionlist.testsessionlist[i].registrationstartdate2 && $scope.today <= $scope.currentpage.globalsections.testsessionlist.testsessionlist[i].cancellationenddate) {
      					$scope.currentpage.globalsections.testsessionlist.testsessionlist[i].canUnregister = true;
      				}
      				if ($scope.today > $scope.currentpage.globalsections.testsessionlist.testsessionlist[i].cancellationenddate2 && ($scope.currentpage.globalsections.testsessionlist.testsessionlist[i].closedate && $scope.today <= $scope.currentpage.globalsections.testsessionlist.testsessionlist[i].closedate)) {
      					$scope.currentpage.globalsections.testsessionlist.testsessionlist[i].canCancelRegistration = true;
      				}
            }
          }

          if ($scope.currentpage.globalsections.testsessiondetails && $scope.currentpage.globalsections.testsessiondetails.onetestsession) {
            $scope.currentpage.globalsections.testsessiondetails.onetestsession.closedate = null;
    				if ($scope.currentpage.globalsections.testsessiondetails.onetestsession.periods && $scope.currentpage.globalsections.testsessiondetails.onetestsession.periods.length != 0) {
    					$scope.currentpage.globalsections.testsessiondetails.onetestsession.closedate = parseISOdateService.parseDate($scope.currentpage.globalsections.testsessiondetails.onetestsession.periods[$scope.currentpage.globalsections.testsessiondetails.onetestsession.periods.length-1].testdate + " 23:59:59");
    				}
    				$scope.currentpage.globalsections.testsessiondetails.onetestsession.registrationstartdate2 = parseISOdateService.parseDateWithoutTime($scope.currentpage.globalsections.testsessiondetails.onetestsession.registrationstartdate);
    				$scope.currentpage.globalsections.testsessiondetails.onetestsession.registrationenddate2   = parseISOdateService.parseDate($scope.currentpage.globalsections.testsessiondetails.onetestsession.registrationenddate + " 23:59:59");
    				$scope.currentpage.globalsections.testsessiondetails.onetestsession.cancellationenddate2   = parseISOdateService.parseDate($scope.currentpage.globalsections.testsessiondetails.onetestsession.cancellationenddate + " 23:59:59");
    				$scope.currentpage.globalsections.testsessiondetails.onetestsession.isClosed 							= false;		// Session is closed, i.e. today is passed the day of the last period
    				$scope.currentpage.globalsections.testsessiondetails.onetestsession.canRegister 					= false;		// User can register test in the session
    				$scope.currentpage.globalsections.testsessiondetails.onetestsession.canUnregister 				= false;		// User can delete the test registration
    				$scope.currentpage.globalsections.testsessiondetails.onetestsession.canCancelRegistration = false;		// User can only cancel the test registration, not delete it
    				$scope.today = new Date();
    				if ($scope.currentpage.globalsections.testsessiondetails.onetestsession.closedate && $scope.today > $scope.currentpage.globalsections.testsessiondetails.onetestsession.closedate) {
    					$scope.currentpage.globalsections.testsessiondetails.onetestsession.isClosed = true;
    				}
    				if ($scope.today >= $scope.currentpage.globalsections.testsessiondetails.onetestsession.registrationstartdate2 && $scope.today <= $scope.currentpage.globalsections.testsessiondetails.onetestsession.registrationenddate2) {
    					$scope.currentpage.globalsections.testsessiondetails.onetestsession.canRegister = true;
    				}
    				if ($scope.today > $scope.currentpage.globalsections.testsessiondetails.onetestsession.registrationstartdate2 && $scope.today <= $scope.currentpage.globalsections.testsessiondetails.onetestsession.cancellationenddate) {
    					$scope.currentpage.globalsections.testsessiondetails.onetestsession.canUnregister = true;
    				}
    				if ($scope.today > $scope.currentpage.globalsections.testsessiondetails.onetestsession.cancellationenddate2 && ($scope.currentpage.globalsections.testsessiondetails.onetestsession.closedate && $scope.today <= $scope.currentpage.globalsections.testsessiondetails.onetestsession.closedate)) {
    					$scope.currentpage.globalsections.testsessiondetails.onetestsession.canCancelRegistration = true;
    				}
          }
        } else {
          $scope.navbar = [];
          $scope.currentpage = null;
        }
    	}else{
    		if (!data.success) {
    			dialogService.displayFailure(data);
    		}
    	}
    }).
    error(function(data, status, headers, config) {
    	dialogService.displayFailure(data);
    });
	};

  // $timeout(function() {
  //   if (window.location.href.indexOf('#') != -1) {
  //     window.location.href = window.location.href;
  //     scrollBy(0, -110);
  //   }
  // }, 1000);

  $timeout(function() {
    if (window.location.href.indexOf('#') != -1) {
      // $anchorScroll('session5');
      $anchorScroll(window.location.hash.substr(2));
      // window.location.href = window.location.href;
      scrollBy(0, -110);
    }
  }, 1000);


  $scope.getURLParameter = function(name) {
    return decodeURIComponent((new RegExp('[?|&]' + name + '=' + '([^&;]+?)(&|#|;|$)').exec(location.search) || [null, ''])[1].replace(/\+/g, '%20')) || null;
  }

  $scope.init = function(pagename, language, param1) {
    $scope.pagename 		 = pagename;
    $scope.language 		 = language;
    $scope.costumeid 		 = $scope.getURLParameter("costumeid");
    $scope.testsessionid = $scope.getURLParameter("testsessionid");
    $scope.previewMode 	 = $scope.getURLParameter("preview");
    if ($scope.previewMode=="true") {
    	$scope.previewMode = true;
    } else {
    	$scope.previewMode = false;
    }
    $scope.getcurrentpageinfo();
  }

	// Converts the paragraph using remarkable to convert markdown text and sanitizes it
	$scope.convertParagraph = function(paragraph) {
		paragraph.markdownmsg =  "<H3>" + (paragraph.title!=null && paragraph.title!='' ? paragraph.title : '') + "</H3>" +
		        "<H4>" + (paragraph.subtitle!=null && paragraph.subtitle!='' ? paragraph.subtitle : '') + "</H4>" +
		        "<p>" + (paragraph.paragraphtext!=null && paragraph.paragraphtext!='' ? $scope.remarkable.render(paragraph.paragraphtext) : '') + "</p>";
		paragraph.markdownmsg =  $sce.trustAsHtml(paragraph.markdownmsg);
	}
	
	$scope.filterCourses = function(item) {
		if ($scope.currentpage.currentCourseCode && item.name != $scope.currentpage.currentCourseCode.code) {
			return false;
		}
		return true;
	}

});

app.service('dialogService', function() {

	this.messageFailure = function (msg){
		alert(msg);
	}

	this.setDlgOk = function() {
		alertify.set({ buttonReverse: false, labels: {ok: "OK",cancel : "No"} });
	};

	this.setDlgYesNo = function() {
		alertify.set({ buttonReverse: true, labels: {ok: "Yes",cancel : "No"} });
	};

	this.setDlgCustomButtonLabels = function(buttonOk, buttonCancel) {
		alertify.set({ buttonReverse: true, labels: {ok: buttonOk,cancel : buttonCancel} });
	};

	this.displayFailure = function (data){
		this.setDlgOk();
		if (data.message) {
			alertify.alert(data.message);
		} else {
			alertify.alert(data);
		}
	}

	this.confirmYesNo = function(msg, callBackfunction) {
		this.setDlgYesNo();
		alertify.confirm(msg, callBackfunction);
	}

	this.customDialog = function(msg, callBackfunction) {
		alertify.confirm(msg, callBackfunction);
	}

  this.confirmDlg = function(msg, buttonType, functionOk, functionCancel, okParam1, okParam2) {
  	alertify.set({ buttonReverse: true });
  	switch(buttonType) {
  		case "YESNO":
				alertify.set({ labels: {ok: "Yes",cancel : "No"} });
  			break;
  		default:
				alertify.set({ labels: {ok: "Ok",cancel : "Cancel"} });
				break;
  	}
		alertify.confirm(msg, function (e) {
			if (e) {
				// user clicked "ok"
				if (functionOk) functionOk(okParam1, okParam2);
			} else {
				// user clicked "cancel"
				if (functionCancel) functionCancel();
			}
		});
  }

  this.alertDlg = function(msg) {
  	this.setDlgOk();
		alertify.alert(msg);
  };

});

// Adds the $scope[propname] array with all the possible codes for the codename
app.service('parseISOdateService', function() {

	this.parseDate = function(datestring) {
		if (datestring) {
			var b = datestring.split(/\D/);
			return new Date(b[0], b[1]-1, b[2], (b[3]||0),
										 (b[4]||0), (b[5]||0), (b[6]||0));
		}
		return null;
	};

	this.parseDateWithoutTime = function(dateString) {
		if (dateString && dateString != "0000-00-00" && dateString != "1899-11-30") {
			var newDateString = dateString + "T00:00:00";
			var b = newDateString.split(/\D/);
			return new Date(b[0], b[1]-1, b[2], (b[3]||0),
										 (b[4]||0), (b[5]||0), (b[6]||0));
		}
		return null;
	};

});
