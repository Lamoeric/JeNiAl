// Directive for the left pane.
// Object list must be "leftobjs" and each object is called "leftobj".
angular.module('core').directive('leftpane', [function() {
  return {
    restrict: 'E',
    template: ''+
    '<form class="form-horizontal" role="form" name="leftpane">' +
      '<div class="form-group row">' +
      	'<div class="grid">' +
          '<div class="visible-xs visible-sm visible-md"><h5>{{translationObj.leftPane.maintitle}} ({{results.length}})</h5></div>' +
          '<div class="visible-lg"><h3>{{translationObj.leftPane.maintitle}} ({{results.length}})</h3></div>' +
          '<div class="row">' +
            '<div class="col-xs-11"' +
          		'<div class="input-group ">' +
            		'<input id="searchinput" type="search" class="form-control" ng-model="searchFilter" placeholder="{{translationObj.leftPane.mainfilter}}" aria-label="{{translationObj.leftPane.mainfilter}}">' +
                '<span id="searchclear" class="glyphicon glyphicon-remove-circle" clear-search-button></span>' +
          		'</div>' +
        		'</div>' +
        		'<button class="btn btn-primary glyphicon glyphicon-refresh" main-refresh-button callback="refreshAll()"></button>' +
        		'<button class="btn btn-primary glyphicon glyphicon-filter" main-filter-button callback="mainFilter()" ng-if="newFilter.filterApplied==false"></button>' +
        		'<button class="btn btn-primary glyphicon glyphicon-filter" main-filter-button callback="mainFilter()" ng-if="newFilter.filterApplied==true" style="color:red"></button>' +
        		'<br><br>' +
          '</div>' +
        '</div>' +
      '</div>' +
    '</form>' +
		'<div class="row leftpane">' +
		  '	<div ng-repeat="leftobj in leftobjs | filter:searchFilter as results">' +
		  '		<div class="callout" ng-class="{ \'selected-class-name\': (selectedIndex && $index == selectedIndex) || (selectedLeftObj && leftobj === selectedLeftObj)}" ng-click="this.setCurrent(leftobj, $index)"><div ng-include="leftpanetemplatefullpath"></div></div>' +
			' </div>' +
		'</div>' +
		'<li class="animate-repeat" ng-if="results.length == 0">' +
		'	<strong>{{translationObj.leftPane.noresult}}</strong>' +
		'</li>'

  };
}]);
