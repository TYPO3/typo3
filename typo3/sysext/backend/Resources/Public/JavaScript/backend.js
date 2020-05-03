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
 * Function similar to PHPs  rawurlencode();
 */
function rawurlencode(str) {
  var output = encodeURIComponent(str);
  output = str_replace("*", "%2A", output);
  output = str_replace("+", "%2B", output);
  output = str_replace("/", "%2F", output);
  output = str_replace("@", "%40", output);
  return output;
}


/**
 * String-replace function
 */
function str_replace(match, replace, string) {	//
  var input = "" + string;
  var matchStr = "" + match;
  if (!matchStr) {
    return string;
  }
  var output = "";
  var pointer = 0;
  var pos = input.indexOf(matchStr);
  while (pos !== -1) {
    output += "" + input.substr(pointer, pos - pointer) + replace;
    pointer = pos + matchStr.length;
    pos = input.indexOf(match, pos + 1);
  }
  output += "" + input.substr(pointer);
  return output;
}


/**
 * Launcing information window for records/files (fileref as "table" argument)
 */
function launchView(table, uid) {
  console.warn('Calling launchView() has been deprecated in v9 and will be removed in v10.0');
  var thePreviewWindow = window.open(TYPO3.settings.ShowItem.moduleUrl + '&table=' + encodeURIComponent(table) + "&uid=" + encodeURIComponent(uid),
    "ShowItem" + Math.random().toString(16).slice(2),
    "width=650,height=600,status=0,menubar=0,resizable=0,location=0,directories=0,scrollbars=1,toolbar=0");
  if (thePreviewWindow && thePreviewWindow.focus) {
    thePreviewWindow.focus();
  }
}

/**
 * Opens plain window with url
 */
function openUrlInWindow(url, windowName) {	//
  regularWindow = window.open(
    url,
    windowName,
    "status=1,menubar=1,resizable=1,location=1,directories=0,scrollbars=1,toolbar=1");
  regularWindow.focus();
  return false;
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
    top.goToModule(TYPO3.configuration.pageModule, 0, addGetVars ? addGetVars : "");
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
