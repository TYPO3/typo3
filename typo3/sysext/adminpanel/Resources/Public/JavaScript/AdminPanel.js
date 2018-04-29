function sendAdminPanelForm(event) {
  event.preventDefault();
  this.innerText = 'Loading...';
  var typo3AdminPanel = document.querySelector('[data-typo3-role=typo3-adminPanel]');
  var formData = new FormData(typo3AdminPanel.querySelector('form'));
  var request = new XMLHttpRequest();
  request.open('POST', typo3AdminPanel.dataset.typo3AjaxUrl);
  request.send(formData);
  request.onload = function () {
    location.reload();
  };
}

function toggleAdminPanelState() {
  var request = new XMLHttpRequest();
  request.open('GET', this.dataset.typo3AjaxUrl);
  request.send();
  request.onload = function () {
    location.reload();
  };
}

function initializeAdminPanel() {
  Array.from(document.querySelectorAll('[data-typo3-role=typo3-adminPanel-saveButton]')).forEach(function (elm) {
    elm.addEventListener('click', sendAdminPanelForm);
  });

  Array.from(document.querySelectorAll('[data-typo3-role=typo3-adminPanel-trigger]')).forEach(function (trigger) {
    trigger.addEventListener('click', toggleAdminPanelState);
  });
}

window.addEventListener('load', initializeAdminPanel, false);
