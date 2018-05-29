function initializeCacheModule() {
	var buttons = Array.from(document.querySelectorAll('[data-typo3-role=clearCacheButton]'));

	buttons.forEach(function (elem) {
		elem.addEventListener('click', clearCache);
	});
}

function clearCache() {
	var url = this.dataset.typo3AjaxUrl;
	var request = new XMLHttpRequest();
	request.open("GET", url);
	request.send();
	request.onload = function () {
		location.reload();
	};
}

window.addEventListener('load', initializeCacheModule, false);
