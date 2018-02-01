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

/**
 * Module: TYPO3/CMS/Recordlist/ElementBrowser
 * ElementBrowser communication with parent windows
 */
define(['jquery'], function($) {
  'use strict';

  /**
   *
   * @type {{thisScriptUrl: string, mode: string, formFieldName: string, hasActionMultipleCode: boolean, fieldReference: string, fieldReferenceSlashed: string, rte: {parameters: string, configuration: string}, irre: {objectId: number, checkUniqueAction: string, addAction: string, insertAction: string}}}
   * @exports TYPO3/CMS/Recordlist/ElementBrowser
   */
  var ElementBrowser = {
    thisScriptUrl: '',
    mode: '',
    formFieldName: '',
    hasActionMultipleCode: false,
    fieldReference: '',
    fieldReferenceSlashed: '',
    rte: {
      parameters: '',
      configuration: ''
    },
    irre: {
      objectId: 0,
      checkUniqueAction: '',
      addAction: '',
      insertAction: ''
    }
  };

  /**
   *
   * @returns {Boolean}
   */
  ElementBrowser.setReferences = function() {
    if (
      window.opener && window.opener.content && window.opener.content.document.editform
      && window.opener.content.document.editform[ElementBrowser.formFieldName]
    ) {
      ElementBrowser.targetDoc = window.opener.content.document;
      ElementBrowser.elRef = ElementBrowser.targetDoc.editform[ElementBrowser.formFieldName];
      return true;
    } else {
      return false;
    }
  };

  /**
   * Dynamically calls a function on a given context object.
   *
   * @param {String} functionName e.g. "inline.somefunc"
   * @param {Object} context e.g. window
   * @returns {*}
   */
  ElementBrowser.executeFunctionByName = function(functionName, context /*, args */) {
    var args = Array.prototype.slice.call(arguments, 2);
    var namespaces = functionName.split(".");
    var func = namespaces.pop();
    for (var i = 0; i < namespaces.length; i++) {
      context = context[namespaces[i]];
    }
    return context[func].apply(context, args);
  };

  /**
   *
   * @param {String} table
   * @param {Number} uid
   * @param {String} type
   * @param {String} filename
   * @param {String} fp
   * @param {String} filetype
   * @param {String} imagefile
   * @param {String} action
   * @param {String} close
   * @returns {Boolean}
   */
  ElementBrowser.insertElement = function(table, uid, type, filename, fp, filetype, imagefile, action, close) {
    var performAction = true;
    // Call a check function in the opener window (e.g. for uniqueness handling):
    if (ElementBrowser.irre.objectId && ElementBrowser.irre.checkUniqueAction) {
      if (window.opener) {
        var res = ElementBrowser.executeFunctionByName(ElementBrowser.irre.checkUniqueAction, window.opener, ElementBrowser.irre.objectId, table, uid, type);
        if (!res.passed) {
          if (res.message) {
            alert(res.message);
          }
          performAction = false;
        }
      } else {
        alert("Error - reference to main window is not set properly!");
        close();
      }
    }
    // Call performing function and finish this action:
    if (performAction) {
      // Call helper function to manage data in the opener window:
      if (ElementBrowser.irre.objectId && ElementBrowser.irre.addAction) {
        if (window.opener) {
          ElementBrowser.executeFunctionByName(ElementBrowser.irre.addAction, window.opener, ElementBrowser.irre.objectId, table, uid, type, ElementBrowser.fieldReferenceSlashed);
        } else {
          alert("Error - reference to main window is not set properly!");
          close();
        }
      }
      if (ElementBrowser.irre.objectId && ElementBrowser.irre.insertAction) {
        if (window.opener) {
          ElementBrowser.executeFunctionByName(ElementBrowser.irre.insertAction, window.opener, ElementBrowser.irre.objectId, table, uid, type);
          if (close) {
            ElementBrowser.focusOpenerAndClose();
          }
        } else {
          alert("Error - reference to main window is not set properly!");
          if (close) {
            close();
          }
        }
      } else if (ElementBrowser.fieldReference && !ElementBrowser.rte.parameters && !ElementBrowser.rte.configuration) {
        ElementBrowser.addElement(filename, table + "_" + uid, fp, close);
      } else {
        if (
          window.opener && window.opener.content && window.opener.content.document.editform
          && window.opener.content.document.editform[ElementBrowser.formFieldName]
        ) {
          window.opener.group_change(
            "add",
            ElementBrowser.fieldReference,
            ElementBrowser.rte.parameters,
            ElementBrowser.rte.configuration,
            ElementBrowser.targetDoc.editform[ElementBrowser.formFieldName],
            ElementBrowser.window.opener.content.document
          );
        } else {
          alert("Error - reference to main window is not set properly!");
        }
        if (close) {
          ElementBrowser.focusOpenerAndClose();
        }
      }
    }
    return false;
  };

  /**
   *
   * @param {String} table
   * @param {Number} uid
   * @returns {Boolean}
   */
  ElementBrowser.insertMultiple = function(table, uid) {
    var type = "";
    if (ElementBrowser.irre.objectId && ElementBrowser.irre.insertAction) {
      // Call helper function to manage data in the opener window:
      if (window.opener) {
        ElementBrowser.executeFunctionByName(ElementBrowser.irre.insertAction + 'Multiple', window.opener, ElementBrowser.irre.objectId, table, uid, type, ElementBrowser.fieldReference);
      } else {
        alert("Error - reference to main window is not set properly!");
        close();
      }
    }
    return false;
  };

  /**
   *
   * @param {String} elName
   * @param {String} elValue
   * @param {String} altElValue
   * @param {String} close
   */
  ElementBrowser.addElement = function(elName, elValue, altElValue, close) {
    if (window.opener && window.opener.setFormValueFromBrowseWin) {
      window.opener.setFormValueFromBrowseWin(ElementBrowser.fieldReference, altElValue ? altElValue : elValue, elName);
      if (close) {
        ElementBrowser.focusOpenerAndClose();
      }
    } else {
      alert("Error - reference to main window is not set properly!");
      close();
    }
  };

  /**
   *
   */
  ElementBrowser.focusOpenerAndClose = function() {
    window.opener.focus();
    close();
  };

  $(function() {
    var data = $('body').data();

    ElementBrowser.thisScriptUrl = data.thisScriptUrl;
    ElementBrowser.mode = data.mode;
    ElementBrowser.formFieldName = data.formFieldName;
    ElementBrowser.fieldReference = data.fieldReference;
    ElementBrowser.fieldReferenceSlashed = data.fieldReferenceSlashed;
    ElementBrowser.rte.parameters = data.rteParameters;
    ElementBrowser.rte.configuration = data.rteConfiguration;
    ElementBrowser.irre.checkUniqueAction = data.irreCheckUniqueAction;
    ElementBrowser.irre.addAction = data.irreAddAction;
    ElementBrowser.irre.insertAction = data.irreInsertAction;
    ElementBrowser.irre.objectId = data.irreObjectId;
    ElementBrowser.hasActionMultipleCode = ElementBrowser.irre.objectId && ElementBrowser.irre.insertAction;
  });

  /**
   * Global jumpTo function
   *
   * Used by tree implementation
   *
   * @param {String} URL
   * @param {String} anchor
   * @returns {Boolean}
   */
  window.jumpToUrl = function(URL, anchor) {
    if (URL.charAt(0) === '?') {
      URL = ElementBrowser.thisScriptUrl + URL.substring(1);
    }
    var add_mode = URL.indexOf("mode=") === -1 ? '&mode=' + encodeURIComponent(ElementBrowser.mode) : "";
    window.location.href = URL + add_mode + (typeof(anchor) === "string" ? anchor : '');
    return false;
  };

  return ElementBrowser;
});
