// Arena service
angular.module('core').service('arenaService', ['dialogService', '$http', function(dialogService, $http) {

	// Adds the $scope.arenas array with all the possible arenas. Every arena also contains the ices for the arena.
	this.getAllArenas = function ($scope, language) {
		$http({
	      method: 'post',
	      url: './core/services/arena/getArenas.php',
	      data: $.param({ 'language' : language, 'type' : 'getArenasDetails'}),
	      headers: {'Content-Type': 'application/x-www-form-urlencoded'}
	    }).
	    success(function(data, status, headers, config) {
	    	if(data.success) {
					if (!angular.isUndefined(data.data)) {
	    			$scope.arenas = data.data;
					}
	    	} else {
	    		dialogService.displayFailure(data);
	    	}
	    }).
	    error(function(data, status, headers, config) {
	    	dialogService.displayFailure(data);
	    });
	};

	// Returns the array of ices for the arena.
	this.getArenaIces = function($scope, arenaid) {
  	for (var i = 0; i < $scope.arenas.length; i++) {
  		if ($scope.arenas[i].id == arenaid) {
  			return $scope.arenas[i].ices;
  		}
  	}
  	return [];
  }

	// Convert the ice code to ice desc.
	this.convertArenaIceToDesc = function($scope, arenaid, iceid, language) {
		for (var i = 0; i < $scope.arenas.length; i++) {
			if ($scope.arenas[i].id == arenaid) {
				for (var y = 0; $scope.arenas[i].ices && y < $scope.arenas[i].ices.length; y++) {
					if ($scope.arenas[i].ices[y].id == iceid) {
						if (language == 'fr-ca') return $scope.arenas[i].ices[y].label_fr;
						if (language == 'en-ca') return $scope.arenas[i].ices[y].label_en;
						return $scope.arenas[i].ices[y].label_en;
					}
				}
			}
		}
		return "";
	}

	this.convertArenaIceToCurrentDesc = function($scope, arenaid, iceid) {
		for (var i = 0; i < $scope.arenas.length; i++) {
			if ($scope.arenas[i].id == arenaid) {
				for (var y = 0; $scope.arenas[i].ices && y < $scope.arenas[i].ices.length; y++) {
					if ($scope.arenas[i].ices[y].id == iceid) {
						return $scope.arenas[i].ices[y].label;
					}
				}
			}
		}
		return "";
	}

	this.convertArenaToDesc = function($scope, arenaid, language) {
		for (var i = 0; i < $scope.arenas.length; i++) {
			if ($scope.arenas[i].id == arenaid) {
				if (language == 'fr-ca') return $scope.arenas[i].label_fr;
				if (language == 'en-ca') return $scope.arenas[i].label_en;
				return $scope.arenas[i].label_en;
			}
		}
		return "";
	}

	this.convertArenaToCurrentDesc = function($scope, arenaid) {
		for (var i = 0; i < $scope.arenas.length; i++) {
			if ($scope.arenas[i].id == arenaid) {
				return $scope.arenas[i].label;
			}
		}
		return "";
	}
}]);
