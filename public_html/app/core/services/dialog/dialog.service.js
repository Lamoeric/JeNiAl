angular.module('core').service('dialogService', function () {

	this.messageFailure = function (msg) {
		alert(msg);
	}

	this.setDlgOk = function () {
		alertify.set({ buttonReverse: false, labels: { ok: "OK", cancel: "No" } });
	};

	this.setDlgYesNo = function () {
		alertify.set({ buttonReverse: true, labels: { ok: "Yes", cancel: "No" } });
	};

	this.setDlgCustomButtonLabels = function (buttonOk, buttonCancel) {
		alertify.set({ buttonReverse: true, labels: { ok: buttonOk, cancel: buttonCancel } });
	};

	this.displayFailure = function (data) {
		if (data) {
			this.setDlgOk();
			alertify.alert(data.message ? data.message : data);
		}
	}

	this.confirmYesNo = function (msg, callBackfunction) {
		this.setDlgYesNo();
		alertify.confirm(msg, callBackfunction);
	}

	this.customDialog = function (msg, callBackfunction) {
		alertify.confirm(msg, callBackfunction);
	}

	this.promptDlg = function (msg, buttonType, functionOk, functionCancel, okParam1, okParam2) {
		alertify.set({ buttonReverse: true });
		switch (buttonType) {
			case "YESNO":
				alertify.set({ labels: { ok: "Yes", cancel: "No" } });
				break;
			default:
				alertify.set({ labels: { ok: "Ok", cancel: "Cancel" } });
				break;
		}
		alertify.prompt(msg, function (e, value) {
			if (e) {
				// user clicked "ok"
				if (functionOk) functionOk(okParam1, okParam2, value);
			} else {
				// user clicked "cancel"
				if (functionCancel) functionCancel();
			}
		}, "");
	}

	this.confirmDlg = function (msg, buttonType, functionOk, functionCancel, okParam1, okParam2) {
		alertify.set({ buttonReverse: true });
		switch (buttonType) {
			case "YESNO":
				alertify.set({ labels: { ok: "Yes", cancel: "No" } });
				break;
			default:
				alertify.set({ labels: { ok: "Ok", cancel: "Cancel" } });
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

	this.alertDlg = function (msg) {
		this.setDlgOk();
		alertify.alert(msg);
	};
});