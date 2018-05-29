function editModuleOnClickHandler(event) {
	event.preventDefault();
	var element = event.target;
	if (parent.opener && parent.opener.top) {
		parent.opener.top.fsMod.recentIds['web'] = element.getAttribute('data-pageUid');
		if (parent.opener.top && parent.opener.top.nav_frame && parent.opener.top.nav_frame.refresh_nav) {
			parent.opener.top.nav_frame.refresh_nav();
		}
		parent.opener.top.goToModule(element.getAttribute('data-pageModule'));
		parent.opener.top.focus();
	} else {
		var vHWin = window.open(element.getAttribute('data-backendScript'), element.getAttribute('data-t3BeSitenameMd5'));
		vHWin.focus();
	}
	return false;
}

function initializeEditModule() {
	var editModuleBtnOpenBackend = document.querySelector('.typo3-adminPanel-btn-openBackend');
	editModuleBtnOpenBackend.addEventListener('click', editModuleOnClickHandler);
}


window.addEventListener('load', initializeEditModule, false);
