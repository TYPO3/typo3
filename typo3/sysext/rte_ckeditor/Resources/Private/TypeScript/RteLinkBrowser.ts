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
 * Module: TYPO3/CMS/RteCkeditor/RteLinkBrowser
 * LinkBrowser communication with parent window
 */
import $ = require('jquery');
import LinkBrowser = require('TYPO3/CMS/Recordlist/LinkBrowser');
import Modal = require('TYPO3/CMS/Backend/Modal');

class RteLinkBrowser {
  private plugin: any = null;

  private CKEditor: CKEDITOR.editor = null;

  private siteUrl = '';

  /**
   * @param {string} editorId Id of CKEditor
   */
  public initialize(editorId: string): void {
    let callerWindow: Window;
    if (typeof top.TYPO3.Backend !== 'undefined' && typeof top.TYPO3.Backend.ContentContainer.get() !== 'undefined') {
      callerWindow = top.TYPO3.Backend.ContentContainer.get();
    } else {
      callerWindow = window.parent;
    }

    $.each((callerWindow as any).CKEDITOR.instances, (name: string, editor: CKEDITOR.editor) => {
      if (editor.id === editorId) {
        this.CKEditor = editor;
      }
    });

    // siteUrl etc are added as data attributes to the body tag
    $.extend(RteLinkBrowser, $('body').data());

    $('.t3js-removeCurrentLink').on('click', (event: Event) => {
      event.preventDefault();
      this.CKEditor.execCommand('unlink');
      Modal.dismiss();
    });
  }

  /**
   * Store the final link
   *
   * @param {stringify} link The select element or anything else which identifies
   * the link (e.g. "page:<pageUid>" or "file:<uid>")
   */
  public finalizeFunction(link: string): void {
    const linkElement: CKEDITOR.dom.element = this.CKEditor.document.createElement('a');
    const attributes = LinkBrowser.getLinkAttributeValues();
    const params: string = attributes.params ? attributes.params : '';

    if (attributes.target) {
      linkElement.setAttribute('target', attributes.target);
    }
    if (attributes.class) {
      linkElement.setAttribute('class', attributes.class);
    }
    if (attributes.title) {
      linkElement.setAttribute('title', attributes.title);
    }
    delete attributes.title;
    delete attributes.class;
    delete attributes.target;
    delete attributes.params;

    $.each(attributes, (attrName: string, attrValue: string) => {
      linkElement.setAttribute(attrName, attrValue);
    });

    linkElement.setAttribute('href', link + params);

    const selection: CKEDITOR.dom.selection = this.CKEditor.getSelection();
    if (selection && selection.getSelectedText() === '') {
      selection.selectElement(selection.getStartElement());
    }
    if (selection && selection.getSelectedText()) {
      linkElement.setText(selection.getSelectedText());
    } else {
      linkElement.setText(linkElement.getAttribute('href'));
    }
    this.CKEditor.insertElement(linkElement);

    Modal.dismiss();
  }
}

export = new RteLinkBrowser();
