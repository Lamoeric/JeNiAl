angular.module('core').service('pricingService', ['$uibModal', '$http', '$q', 'anycodesService', 'dateFilter', 'billingService', 'dialogService', 'authenticationService', 'translationService', 'parseISOdateService', function($uibModal, $http, $q, anycodesService, dateFilter, billingService, dialogService, authenticationService, translationService, parseISOdateService) {

	var those = this;

  // Evaluates one multicourse rule for a discount
  this.evaluateMulticourseRule = function(currentRegistration, rule) {
    var retVal = false, count = 0;
    var ruleparameters = angular.fromJson(rule.ruleparameters);
    if (ruleparameters.scope == 'theOrder') { // theOrder is the currentRegistration
    	var tmpArray = currentRegistration.courses ? currentRegistration.courses : currentRegistration.shownumbers;
      for (var i = 0; i < tmpArray.length; i++) {
        if (tmpArray[i].coursecode == ruleparameters.coursecode && (ruleparameters.courselevel == null || currentRegistration.courses[i].courselevel == ruleparameters.courselevel) && tmpArray[i].selected == "1") {
          count++;
        }
      }
      if (count/1 >= ruleparameters.nbofcourses/1) retVal = true;
    } else {
      retVal = false;
    }
    return retVal;
  }

  // Evaluates one family rule for a discount
  this.evaluateFamilyDiscount = function(currentRegistration, rule, deferred) {
    var retVal = false, count = 0;
    var ruleparameters = angular.fromJson(rule.ruleparameters);
    if (ruleparameters.scope == 'theSession') { // theSession is the entire registrations for the session
      if (currentRegistration.familyMemberCount/1+1 == ruleparameters.nbofmember) {
        retVal = true;
      } else {
        retVal = false;
      }
    } else {
      retVal = false;
    }
    return retVal;
  }

  // Evaluates all the rules for a discount
  // Returns true (select charge), false (unselect charge) or null (do not change selected state of charge)
  this.evaluateDiscountRules = function(currentRegistration, rules, deferred) {
    var rule = null, retVal = false;
    for (var y = 0; y < rules.length; y++) {
      rule = rules[y];
      switch (rule.ruletype) {
        case "1":
	        retVal = this.evaluateMulticourseRule(currentRegistration, rule);
	        break;
        case "2":
	        // Family based discount are not to be reevaluated once the registration status is PRESENTED
	        if (currentRegistration.status == 'DRAFT' /*|| currentRegistration.status == 'PRESENTED'*/) {
	          retVal = this.evaluateFamilyDiscount(currentRegistration, rule, deferred);
	        } else {
	          retVal = null;
	        }
	        break;
        default:
        	return (deferred.resolve(false));
      }
      if (!retVal) break;
    }
    return retVal;
  }

  // Evaluate if a automatic discount should or shouldn't be selected.
  // [lamoeric 2019/08/13] Added the startdate and  enddate for discounts
  this.evaluateDiscounts = function(currentRegistration) {
    var deferred = $q.defer();
    var charge = null, rule = null, retVal = false;//, today = new Date();
//    today.setHours(0,0,0,0);
    for (var i = 0; i < currentRegistration.charges.length; i++) {
      charge = currentRegistration.charges[i];
      if (charge.type == 'DISCOUNT') {
      	// If discount stardate <= registrationdate <= enddate (active == 1) OR discount was already given (selected == 1) OR discount was just removed in this quote (selected_old == 1)
      	// Then reevaluate this discount
      	// Note : discounts with family rules are evaluated only if quote status == draft
				if (charge.selected	== "1" || charge.selected_old	== "1" || charge.active == "1") {
					if (charge.rules !=	null &&	charge.rules.length	!= 0)	{
						retVal = this.evaluateDiscountRules(currentRegistration, charge.rules, deferred);
						if (retVal ==	true)	{
							charge.selected	=	"1";
						}	if (retVal ==	false) {
							charge.selected	=	"0";
						}	else { //	retVal ==	null
							// Do	not	change the selected	state	of this	charge
						}
					}
				}
      }
    }
  }

	this.roundTo = function(n, digits) {
	  if (digits === undefined) {
	      digits = 0;
	  }

	  var multiplicator = Math.pow(10, digits);
	  n = parseFloat((n * multiplicator).toFixed(11));
	  return (Math.round(n) / multiplicator).toFixed(2);
	}

	this.applyProrataOption = function(amount, option) {
		var retVal = amount/1;
	  if (option === undefined) {
	      option = -1;
	  }
		switch (option) {
			//Do not modify calculated amount
			case -1:
			break;
			//Round down to previous dollar
			case 0:
			retVal = Math.floor(retVal/1)*1;
			break;
			//Round up to next dollar
			case 1:
			retVal = Math.ceil(retVal/1)*1;
			break;
			//Round up to next 5 dollar
			case 5:
			retVal = Math.ceil(retVal/5)*5;
			break;
			//Round up to next 10 dollar
			case 10:
			retVal = Math.ceil(retVal/10)*10;
			break;
		}
	  return retVal;
	}

  // Calculates the total amount ($) for the courses, based on current and previous selected state.
  // Also calculates the charges and sets the total amount for the order.
  // This function modifies the object it receives.
	// useProrata : true to use prorata prices, false to use full price.
	// roundUpTo : the value to round up to. Use -1 to leave amount untouched, 0 to remove cents, any other to round up to closest value.
  this.applyPricingRules = function(currentRegistration, useProrata, prorataOption) {
    var total = 0.0;
    var prorata = 0.0;
    var courseTmp = null;
    for (var i = 0; currentRegistration.courses && i < currentRegistration.courses.length; i++) {
      courseTmp = currentRegistration.courses[i];
      prorata = this.roundTo(((courseTmp.fees/1)/(courseTmp.nbofcourses/1)  * (courseTmp.nbofcoursesleft/1))/1, 2)/1;
			prorata = this.applyProrataOption(prorata, useProrata ? prorataOption : -1);
			courseTmp.prorata = useProrata ? prorata/1 : courseTmp.fees/1;
      if (courseTmp.selected == '1') {
        // New course for the registration
        if (courseTmp.selected_old == '0') {
          if (courseTmp.fees_old != null && courseTmp.fees_old > 0) {
            // This course was  removed in a previous registration, but it still need to be paid. We then add the new value of this registration
            courseTmp.fees_billing = courseTmp.fees_old/1 + (useProrata ? prorata/1 : courseTmp.fees/1);
          } else {
            // This is a totaly new course for this registration
						if (courseTmp.realpaidamount != null && courseTmp.realpaidamount > 0) {
							courseTmp.fees_billing = courseTmp.realpaidamount/1;
						} else {
							courseTmp.fees_billing = useProrata ? prorata/1 : courseTmp.fees/1;;
						}
          }
          total += courseTmp.fees_billing;
					total = this.roundTo(total, 2)/1;
        }
        // Existing course from old registration
        if (courseTmp.selected_old == '1') {
          courseTmp.fees_billing = courseTmp.fees_old/1;
          total += courseTmp.fees_billing/1;
					total = this.roundTo(total, 2)/1;
        }
      }
      if (courseTmp.selected == '0') {
        if (courseTmp.selected_old == '1') {
          // Course is being removed, we need to calculate the difference between the paid value and the residual value (prorata)
          courseTmp.fees_billing = courseTmp.fees_old/1 - prorata/1;
          total += courseTmp.fees_billing/1;
					total = this.roundTo(total, 2)/1;
        }
        if (courseTmp.selected_old == '0' && courseTmp.fees_old/1 > 0) {
          // Course was removed in another revision, no calculation needed.
          courseTmp.fees_billing = courseTmp.fees_old/1;
          total += courseTmp.fees_billing/1;
					total = this.roundTo(total, 2)/1;
        }
        if (courseTmp.selected_old == '0' && courseTmp.fees_old/1 == 0) {
          // untouched
          courseTmp.fees_billing = null;
          total += courseTmp.fees_billing/1;
					total = this.roundTo(total, 2)/1;
        }
      }
    }

		// For registration to a show, calculate the prices of show numbers
    for (var i = 0; currentRegistration.shownumbers && i < currentRegistration.shownumbers.length; i++) {
      numberTmp = currentRegistration.shownumbers[i];
//      prorata = this.roundTo(((numberTmp.fees/1)/(numberTmp.nbofcourses/1)  * (numberTmp.nbofcoursesleft/1))/1, 2)/1;
//			prorata = this.applyProrataOption(prorata, useProrata ? prorataOption : -1);
			// No prorata for show numbers
			prorata = numberTmp.fees/1;
			numberTmp.prorata = useProrata ? prorata/1 : numberTmp.fees/1;
			
      if (numberTmp.selected == '1') {
        // New course for the registration
        if (numberTmp.selected_old == '0') {
          if (numberTmp.fees_old != null && numberTmp.fees_old > 0) {
            // This show number was  removed in a previous registration, but it still need to be paid. We then add the new value of this registration
            numberTmp.fees_billing = numberTmp.fees_old/1 + (useProrata ? prorata/1 : numberTmp.fees/1);
          } else {
            // This is a totaly new show number for this registration
						if (numberTmp.realpaidamount != null && numberTmp.realpaidamount > 0) {
							numberTmp.fees_billing = numberTmp.realpaidamount/1;
						} else {
							numberTmp.fees_billing = useProrata ? prorata/1 : numberTmp.fees/1;;
						}
          }
          total += numberTmp.fees_billing;
					total = this.roundTo(total, 2)/1;
        }
        // Existing show number from old registration
        if (numberTmp.selected_old == '1') {
          numberTmp.fees_billing = numberTmp.fees_old/1;
          total += numberTmp.fees_billing/1;
					total = this.roundTo(total, 2)/1;
        }
      }
      if (numberTmp.selected == '0') {
        if (numberTmp.selected_old == '1') {
          // Show number is being removed, we need to calculate the difference between the paid value and the residual value (prorata)
          numberTmp.fees_billing = numberTmp.fees_old/1 - prorata/1;
          total += numberTmp.fees_billing/1;
					total = this.roundTo(total, 2)/1;
        }
        if (numberTmp.selected_old == '0' && numberTmp.fees_old/1 > 0) {
          // Show number was removed in another revision, no calculation needed.
          numberTmp.fees_billing = numberTmp.fees_old/1;
          total += numberTmp.fees_billing/1;
					total = this.roundTo(total, 2)/1;
        }
        if (numberTmp.selected_old == '0' && numberTmp.fees_old/1 == 0) {
          // untouched
          numberTmp.fees_billing = null;
          total += numberTmp.fees_billing/1;
					total = this.roundTo(total, 2)/1;
        }
      }
    }

    // Analyse the discounts
    this.evaluateDiscounts(currentRegistration);
    // Analyse the other charges
    for (var i = 0; i < currentRegistration.charges.length; i++) {
      if (currentRegistration.charges[i].selected == '1') {
        if (currentRegistration.charges[i].type == 'CHARGE') {
          total += currentRegistration.charges[i].amount/1;
					total = this.roundTo(total, 2)/1;
        } else if (currentRegistration.charges[i].type == 'DISCOUNT') {
          total -= currentRegistration.charges[i].amount/1;
					total = this.roundTo(total, 2)/1;
        }
      }
    }
		total = this.roundTo(total, 2)/1;
    currentRegistration.totalamount = total/1;
    // return  total/1;
  }

}]);
