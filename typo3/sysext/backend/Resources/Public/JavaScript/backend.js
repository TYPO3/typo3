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
 */
function jump(url, modName, mainModName, pageId) {
  if (isNaN(pageId)) {
    pageId = -2;
  }
  // clear information about which entry in nav. tree that might have been highlighted.
  top.fsMod.navFrameHighlightedID = [];
  top.fsMod.recentIds['web'] = pageId;

  top.nextLoadModuleUrl = url;
  top.TYPO3.ModuleMenu.App.showModule(modName);
}

/**
 * Loads a page id for editing in the page edit module:
 */
function loadEditId(id, addGetVars) {	//
  top.fsMod.recentIds.web = id;
  top.fsMod.navFrameHighlightedID.web = '0_' + id; // For highlighting

  if (top.nav_frame && top.nav_frame.refresh_nav) {
    top.nav_frame.refresh_nav();
  }
  if (TYPO3.configuration.pageModule) {
    TYPO3.ModuleMenu.App.showModule(TYPO3.configuration.pageModule, addGetVars);
  }
}

/**
 * Returns incoming URL (to a module) unless nextLoadModuleUrl is set. If that is the case nextLoadModuleUrl is returned (and cleared)
 * Used by the shortcut frame to set a "intermediate URL"
 */
var nextLoadModuleUrl = "";

function getModuleUrl(inUrl) {	//
  var nMU;
  if (top.nextLoadModuleUrl) {
    nMU = top.nextLoadModuleUrl;
    top.nextLoadModuleUrl = "";
    return nMU;
  } else {
    return inUrl;
  }
}

// Used by Frameset Modules
var currentSubScript = "";
