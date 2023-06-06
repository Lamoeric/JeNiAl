/* Directive for the right pane.
*  NOT USED
*/
angular.module('core').directive('newrightpane', ['$sce', function($sce) {
  return {
    restrict: 'E',
    templateUrl: './core/directives/leftpane/newrightpane.template.html',
    // template:''+
    // '<section id="intro" class="intro" cg-busy="{promise:promise,templateUrl:templateUrl,message:message,backdrop:backdrop,delay:delay,minDuration:minDuration}">' +
    // '	<row>' +
    // '		<div id="maincolumnleft" class="col-xs-5 col-sm-2 maincolumnleft">' +
    // '			<div id="leftformgroup" class="row leftformgroup">' +
    // '				<div class="visible-xs visible-sm visible-md"><h5>{{translationObj.leftPane.maintitle}} ({{results.length}})</h5></div>' +
    // '				<div class="visible-lg"><h3>{{translationObj.leftPane.maintitle}} ({{results.length}})</h3></div>' +
    // '				<div class="row">' +
    // '          <div class="col-xs-11">' +
    // '        		<input id="searchinput" type="search" class="form-control" ng-model="searchFilter" placeholder="{{translationObj.leftPane.mainfilter}}" aria-label="{{translationObj.leftPane.mainfilter}}">' +
    // '            <span id="searchclear" class="glyphicon glyphicon-remove-circle" clear-search-button></span>' +
    // '      		</div>' +
    // '      	</div>' +
    // '    		<button class="btn btn-primary glyphicon glyphicon-refresh" main-refresh-button callback="refreshAll()"></button>' +
    // '    		<button class="btn btn-primary glyphicon glyphicon-filter" main-filter-button callback="mainFilter()" ng-if="newFilter.filterApplied==false"></button>' +
    // '    		<button class="btn btn-primary glyphicon glyphicon-filter" main-filter-button callback="mainFilter()" ng-if="newFilter.filterApplied==true" style="color:red"></button>' +
    // '				<div id="leftselector" class="row leftselectorrow">' +
    // '					<div ng-repeat="leftobj in leftobjs | filter:searchFilter as results">' +
    // '						<div class="callout" ng-class="{ \'selected-class-name\': (selectedIndex && $index == selectedIndex) || (selectedLeftObj && leftobj === selectedLeftObj)}" ng-click="this.setCurrent(leftobj, $index)"><div ng-include="leftpanetemplatefullpath"></div></div>' +
    // '					</div>' +
    // '				</div>' +
    // '				<li class="animate-repeat" ng-if="results.length == 0">' +
    // '					<strong>{{translationObj.leftPane.noresult}}</strong>' +
    // '				</li>' +
    // '			</div>' +
    // '		</div>' +
    // '		<div id="maincolumnright" class="col-xs-7 col-sm-10 maincolumnright">' +
    // '			<div id="mainrowright" class="row mainrowright">' +
    // '				<div class="grid">' +
    // '					<!-- TODO : transform into directives -->' +
    // '					<div class="alert alert-danger" id="mainglobalerrormessage" style="display:none;">' +
    // '						<li ng-repeat="error in globalErrorMessage">{{error}}</li>' +
    // '					</div>' +
    //
    // '					<div class="alert alert-warning" id="mainglobalwarningmessage" style="display:none;">' +
    // '						<li ng-repeat="warning in globalWarningMessage">{{warning}}</li>' +
    // '					</div>' +
    //
    // '					<!-- TODO : transform into directive - mainTitleDisplay, parameters : object, defaulttitle, specifictitle -->' +
    // '					<div ng-if="currentArena!=null"><h2>{{currentArena.name}}</h2></div>' +
    // '					<div ng-if="currentArena==null"><h2>{{translationObj.main.formtitlearenadetails}}</h2></div>' +
    //
    // '					<main-new-button new-callback="createNew()"></main-new-button>' +
    // '					<main-save-button save-callback="saveToDB()" is-form-pristine="isFormPristine && !detailsForm.$dirty"></main-save-button>' +
    // '					<main-delete-button delete-callback="deleteFromDB()" is-form-pristine="currentArena && currentArena.isused > 0"></main-delete-button>' +
    //
    // '						<ng-include src="'+ maintemplate + '"></ng-include>' +
    //
    // '				</div>' +
    // '		</div>' +
    // '	</row>' +
    // '</section>',

    link: function( scope, element, attrs, formCtrl) {
      // scope.leftpanetemplatefullpath = attrs.leftpanetemplatefullpath;
      // scope.maintemplate = $sce.getTrustedResourceUrl(attrs.maintemplate);
      // scope.globalErrorMessage = [];
      // scope.globalWarningMessage = [];

      // For delay display
      // scope.delay = 0;
      // scope.minDuration = 0;
      // scope.message = 'Please Wait...';
      // scope.backdrop = true;
      // scope.promise = null;
      // return;

      // scope.getMainTemplate = function() {
      // function getMainTemplate() {
      //   return;
      // }
    }
  };
}]);
