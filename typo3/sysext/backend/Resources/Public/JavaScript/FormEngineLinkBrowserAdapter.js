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
 * Module: TYPO3/CMS/Backend/FormEngineLinkBrowserAdapter
 * LinkBrowser communication with parent window
 */
define(['jquery', 'TYPO3/CMS/Recordlist/LinkBrowser', 'TYPO3/CMS/Backend/Modal'], function($, LinkBrowser, Modal) {
  'use strict';

  /**
   * @exports TYPO3/CMS/Backend/FormEngineLinkBrowserAdapter
   */
  var FormEngineLinkBrowserAdapter = {
    updateFunctions: null, // those are set in the module initializer function in PHP (`type: raw`, @deprecated)
    onFieldChangeItems: null // those are set in the module initializer function in PHP (`type: items`, structured)
  };

  /**
   * @param {OnFieldChangeItem[]} onFieldChangeItems
   */
  FormEngineLinkBrowserAdapter.setOnFieldChangeItems = function(onFieldChangeItems) {
    FormEngineLinkBrowserAdapter.onFieldChangeItems = onFieldChangeItems;
  };

  /**
   * Return reference to parent's form element
   *
   * @returns {Element}
   */
  FormEngineLinkBrowserAdapter.checkReference = function() {
    var selector = 'form[name="' + LinkBrowser.parameters.formName + '"] [data-formengine-input-name="' + LinkBrowser.parameters.itemName + '"]';
    let opener = FormEngineLinkBrowserAdapter.getParent();

    if (opener && opener.document && opener.document.querySelector(selector)) {
      return opener.document.querySelector(selector);
    } else {
      Modal.dismiss();
    }
  };

  /**
   * Save the current link back to the opener
   *
   * @param {String} input
   */
  LinkBrowser.finalizeFunction = function(input) {
    var field = FormEngineLinkBrowserAdapter.checkReference();
    if (field) {
      var attributeValues = LinkBrowser.getLinkAttributeValues();
      // encode link on server
      attributeValues.url = input;

      $.ajax({
        url: TYPO3.settings.ajaxUrls['link_browser_encodetypolink'],
        data: attributeValues,
        method: 'GET'
      }).done(function(data) {
        if (data.typoLink) {
          field.value = data.typoLink;
          field.dispatchEvent(new Event('change', {bubbles: true, cancelable: true}));

          if (typeof FormEngineLinkBrowserAdapter.updateFunctions === 'function') {
            FormEngineLinkBrowserAdapter.updateFunctions();
          } else if (FormEngineLinkBrowserAdapter.onFieldChangeItems instanceof Array) {
            // @todo us `CustomEvent` or broadcast channel as alternative
            FormEngineLinkBrowserAdapter.getParent()
              .TYPO3.FormEngine.processOnFieldChange(FormEngineLinkBrowserAdapter.onFieldChangeItems);
          }

          Modal.dismiss();
        }
      });
    }
  };

  /**
   * Returns the parent document object
   */
  FormEngineLinkBrowserAdapter.getParent = function() {
    let opener;
    if (
      typeof window.parent !== 'undefined' &&
      typeof window.parent.document.list_frame !== 'undefined' &&
      window.parent.document.list_frame.parent.document.querySelector('.t3js-modal-iframe') !== null
    ) {
      opener = window.parent.document.list_frame;
    } else if (
      typeof window.parent !== 'undefined' &&
      typeof window.parent.frames.list_frame !== 'undefined' &&
      window.parent.frames.list_frame.parent.document.querySelector('.t3js-modal-iframe') !== null
    ) {
      opener = window.parent.frames.list_frame;
    } else if (
      typeof window.frames !== 'undefined' &&
      typeof window.frames.frameElement !== 'undefined' &&
      window.frames.frameElement !== null &&
      window.frames.frameElement.classList.contains('t3js-modal-iframe')
    ) {
      opener = window.frames.frameElement.contentWindow.parent;
    } else if (window.opener) {
      opener = window.opener;
    }

    return opener;
  };

  return FormEngineLinkBrowserAdapter;
});
