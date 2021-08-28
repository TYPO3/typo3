/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

// Reset the current window name in case it was a preview before
window.name = '';

// Remove window.opener from backend
window.opener = undefined;

/**
 * common storage and global object, could later hold more information about the current user etc.
 */
var TYPO3 = TYPO3 || {};

/**
 * jump the backend to a module
 * @deprecated will be removed in TYPO3 v12.0.
 */
function jump(url, modName, mainModName, pageId) {
  console.warn('Using jump is deprecated, use `typo3-backend-module-router` instead.');
  require(['TYPO3/CMS/Backend/Storage/ModuleStateStorage'], () => {
    // clear information about which entry in nav. tree that might have been highlighted.
    if (!isNaN(pageId)) {
      ModuleStateStorage.updateWithCurrentMount('web', pageId, true);
    }
    const router = document.querySelector('typo3-backend-module-router');
    router.setAttribute('endpoint', url);
    router.setAttribute('module', modName);
  });
}

/**
 * Returns incoming URL (to a module) unless nextLoadModuleUrl is set. If that is the case nextLoadModuleUrl is returned (and cleared)
 * Used by the shortcut frame to set a "intermediate URL"
 * @deprecated will be removed in TYPO3 v12.0.
 */
var nextLoadModuleUrl = "";

/**
 * @deprecated will be removed in TYPO3 v12.0.
 */
var currentSubScript = "";

/**
 * @param {string} modName
 * @param {string} addGetVars
 * @deprecated use `data-dispatch-action=TYPO3.ModuleMenu.showModule` instead, will be removed in TYPO3 v12.0
 */
function goToModule(modName, addGetVars) {
  console.warn('Using goToModule is deprecated, use `data-dispatch-action=TYPO3.ModuleMenu.showModule` instead.');
  TYPO3.ModuleMenu.App.showModule(modName, addGetVars);
}
