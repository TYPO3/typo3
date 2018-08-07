function sendAdminPanelForm(event) {
  event.preventDefault();
  var typo3AdminPanel = document.querySelector('[data-typo3-role=typo3-adminPanel]');
  var formData = new FormData(typo3AdminPanel);
  var request = new XMLHttpRequest();
  request.open("POST", typo3AdminPanel.dataset.typo3AjaxUrl);
  request.send(formData);
  request.onload = function () {
    location.reload();
  };
}

function toggleAdminPanelState() {
  var request = new XMLHttpRequest();
  request.open("GET", this.dataset.typo3AjaxUrl);
  request.send();
  request.onload = function () {
    location.reload();
  };
}

function renderBackdrop() {
  var adminPanel = document.querySelector('#TSFE_ADMIN_PANEL_FORM');
  var backdrop = document.createElement('div');
  var body = document.querySelector('body');
  body.classList.add('typo3-adminPanel-noscroll');
  backdrop.classList.add('typo3-adminPanel-backdrop');
  adminPanel.appendChild(backdrop);
  addBackdropListener();
}

function removeBackdrop() {
  var backdrop = document.querySelector('.typo3-adminPanel-backdrop');
  var body = document.querySelector('body');
  body.classList.remove('typo3-adminPanel-noscroll');
  if (backdrop !== null) {
    backdrop.remove();
  }
}

function addBackdropListener() {
  var allBackdrops = Array.from(document.querySelectorAll('.typo3-adminPanel-backdrop'));
  allBackdrops.forEach(function (elm) {
    elm.addEventListener('click', function () {
      removeBackdrop();
      var allModules = Array.from(document.querySelectorAll('[data-typo3-role=typo3-adminPanel-module-trigger]'));
      allModules.forEach(function (innerElm) {
        innerElm.closest('.typo3-adminPanel-module').classList.remove('typo3-adminPanel-module-active');
      });
    });
  });
}

function addModuleListener(allModules) {
  allModules.forEach(function (elm) {
    elm.addEventListener('click', function () {
      var parent = this.closest('.typo3-adminPanel-module');
      if (parent.classList.contains('typo3-adminPanel-module-active')) {
        removeBackdrop();
        parent.classList.remove('typo3-adminPanel-module-active');
      } else {
        allModules.forEach(function (innerElm) {
          removeBackdrop();
          innerElm.closest('.typo3-adminPanel-module').classList.remove('typo3-adminPanel-module-active');
        });
        renderBackdrop();
        parent.classList.add('typo3-adminPanel-module-active');
      }
    });
  });
}

function initializeAdminPanel() {
  var allModules = Array.from(document.querySelectorAll('[data-typo3-role=typo3-adminPanel-module-trigger]'));
  addModuleListener(allModules);
  initializeTabs();


  Array.from(document.querySelectorAll('[data-typo3-role=typo3-adminPanel-saveButton]')).forEach(function (elm) {
    elm.addEventListener('click', sendAdminPanelForm);
  });

  Array.from(document.querySelectorAll('[data-typo3-role=typo3-adminPanel-trigger]')).forEach(function (trigger) {
    trigger.addEventListener('click', toggleAdminPanelState);
  });

  var popupTriggers = Array.from(document.querySelectorAll('[data-typo3-role=typo3-adminPanel-popup-trigger]'));
  popupTriggers.forEach(function (elm) {
    elm.addEventListener('click', function () {
      if (this.classList.contains('active')) {
        this.classList.remove('active');
      } else {
        popupTriggers.forEach(function (innerElm) {
          innerElm.classList.remove('active');
        });
        this.classList.add('active');
      }
    });
  });

  var panelTriggers = Array.from(document.querySelectorAll('[data-typo3-role=typo3-adminPanel-panel-trigger]'));
  panelTriggers.forEach(function (elm) {
    elm.addEventListener('click', function () {
      var target = this.closest('.typo3-adminPanel-panel');
      if (target.classList.contains('active')) {
        target.classList.remove('active');
      } else {
        target.classList.add('active');
      }
    });
  });

  var settingsTriggers = Array.from(document.querySelectorAll('[data-typo3-role=typo3-adminPanel-content-settings]'));
  settingsTriggers.forEach(function (elm) {
    elm.addEventListener('click', function () {
      var target = this.closest('.typo3-adminPanel-content').querySelector('.typo3-adminPanel-content-settings');
      if (target.classList.contains('typo3-adminPanel-content-settings-active')) {
        target.classList.remove('typo3-adminPanel-content-settings-active');
      } else {
        target.classList.add('typo3-adminPanel-content-settings-active');
      }
    });
  });

  var moduleClose = Array.from(document.querySelectorAll('[data-typo3-role=typo3-adminPanel-content-close]'));
  moduleClose.forEach(function (elm) {
    elm.addEventListener('click', function () {
      allModules.forEach(function (innerElm) {
        innerElm.closest('.typo3-adminPanel-module').classList.remove('typo3-adminPanel-module-active');
      });
      removeBackdrop();
    });
  });

  var dataFields = Array.from(document.querySelectorAll('.typo3-adminPanel-table th, .typo3-adminPanel-table td'));
  dataFields.forEach(function (elm) {
    elm.addEventListener('click', function () {
      elm.focus();
      // elm.select();

      try {
        var successful = document.execCommand('copy');
        var msg = successful ? 'successful' : 'unsuccessful';
        console.log('Copying text command was ' + msg);
      } catch (err) {
        console.log('Oops, unable to copy');
      }
    });
  });

  addBackdropListener();
}

/**
 * Tabs
 */
function initializeTabs() {
  var tabs = document.querySelectorAll('[data-typo3-role=typo3-adminPanel-content-tab]');

  function switchTab(event) {
    event.preventDefault();

    var activeTabClass = 'typo3-adminPanel-content-header-item-active';
    var activePaneClass = 'typo3-adminPanel-content-panes-item-active';
    var currentTab = event.currentTarget;
    var currentContent = currentTab.closest('[data-typo3-role=typo3-adminPanel-content]');
    var contentTabs = currentContent.querySelectorAll('[data-typo3-role=typo3-adminPanel-content-tab]');
    var contentPanes = currentContent.querySelectorAll('[data-typo3-role=typo3-adminPanel-content-pane]');

    for (var i = 0; i < contentTabs.length; i++) {
      contentTabs[i].classList.remove(activeTabClass);
    }
    currentTab.classList.add(activeTabClass);
    for (var j = 0; j < contentPanes.length; j++) {
      contentPanes[j].classList.remove(activePaneClass);
    }

    var activePane = document.querySelector('[data-typo3-tab-id=' + currentTab.dataset.typo3TabTarget + ']');
    activePane.classList.add(activePaneClass);
  }

  for (var i = 0; i < tabs.length; i++) {
    tabs[i].addEventListener("click", switchTab)
  }
}

window.addEventListener('load', initializeAdminPanel, false);

/**
 * Zoom
 */
function initializeZooms() {
  var zoomOpenTrigger = document.querySelectorAll('[data-typo3-zoom-target]');
  var zoomCloseTrigger = document.querySelectorAll('[data-typo3-zoom-close]');

  function openZoom(event) {
    event.preventDefault();
    var trigger = event.currentTarget;
    var targetId = trigger.getAttribute('data-typo3-zoom-target');
    var target = document.querySelector('[data-typo3-zoom-id=' + targetId + ']');
    target.classList.add('typo3-adminPanel-zoom-show');
  }

  for (var i = 0; i < zoomOpenTrigger.length; i++) {
    zoomOpenTrigger[i].addEventListener("click", openZoom)
  }

  function closeZoom(event) {
    event.preventDefault();
    var trigger = event.currentTarget;
    var target = trigger.closest('[data-typo3-zoom-id]');
    target.classList.remove('typo3-adminPanel-zoom-show');
  }

  for (var i = 0; i < zoomCloseTrigger.length; i++) {
    zoomCloseTrigger[i].addEventListener("click", closeZoom)
  }
}
window.addEventListener('load', initializeZooms, false);
