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

import $ from 'jquery';
import LinkBrowser from '@typo3/recordlist/link-browser';
import Modal from '@typo3/backend/modal';

/**
 * Module: @typo3/rte-ckeditor/rte-link-browser
 * LinkBrowser communication with parent window
 */
class RteLinkBrowser {
  protected plugin: any = null;
  protected CKEditor: CKEDITOR.editor = null;
  protected ranges: CKEDITOR.dom.range[] = [];
  protected siteUrl: string = '';

  /**
   * @param {String} editorId Id of CKEditor
   */
  public initialize(editorId: string): void {
    let editor: CKEDITOR.editor = Modal.currentModal.data('ckeditor');
    if (typeof editor !== 'undefined') {
      this.CKEditor = editor;
    } else {
      let callerWindow;
      if (typeof top.TYPO3.Backend !== 'undefined' && typeof top.TYPO3.Backend.ContentContainer.get() !== 'undefined') {
        callerWindow = top.TYPO3.Backend.ContentContainer.get();
      } else {
        callerWindow = window.parent;
      }

      $.each(callerWindow.CKEDITOR.instances, (name: number, instance: any): void => {
        if (instance.id === editorId) {
          this.CKEditor = instance;
        }
      });
    }

    window.addEventListener('beforeunload', (): void => {
      this.CKEditor.getSelection().selectRanges(this.ranges);
    });

    // Backup all ranges that are active when the Link Browser is requested
    this.ranges = this.CKEditor.getSelection().getRanges();

    // siteUrl etc are added as data attributes to the body tag
    $.extend(RteLinkBrowser, $('body').data());

    $('.t3js-class-selector').on('change', (): void => {
      if ($('option:selected', this).data('linkTitle')) {
        $('.t3js-linkTitle').val($('option:selected', this).data('linkTitle'));
      }
    });

    $('.t3js-removeCurrentLink').on('click', (event: JQueryEventObject): void => {
      event.preventDefault();
      this.CKEditor.execCommand('unlink');
      Modal.dismiss();
    });
  }

  /**
   * Store the final link
   *
   * @param {String} link The select element or anything else which identifies the link (e.g. "page:<pageUid>" or "file:<uid>")
   */
  public finalizeFunction(link: string): void {
    const linkElement = this.CKEditor.document.createElement('a');
    const attributes = LinkBrowser.getLinkAttributeValues();
    let params = attributes.params ? attributes.params : '';

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

    $.each(attributes, (attrName: string, attrValue: string): void => {
      linkElement.setAttribute(attrName, attrValue);
    });

    // Make sure, parameters and anchor are in correct order
    const linkMatch = link.match(/^([a-z0-9]+:\/\/[^:\/?#]+(?:\/?[^?#]*)?)(\??[^#]*)(#?.*)$/)
    if (linkMatch && linkMatch.length > 0) {
      link = linkMatch[1] + linkMatch[2];
      const paramsPrefix = linkMatch[2].length > 0 ? '&' : '?';
      if (params.length > 0) {
        if (params[0] === '&') {
          params = params.substr(1)
        }
        // If params is set, append it
        if (params.length > 0) {
          link += paramsPrefix + params;
        }
      }
      link += linkMatch[3];
    }

    linkElement.setAttribute('href', link);

    const selection = this.CKEditor.getSelection();
    selection.selectRanges(this.ranges);
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

let rteLinkBrowser = new RteLinkBrowser();
export default rteLinkBrowser;
LinkBrowser.finalizeFunction = (link: string): void => { rteLinkBrowser.finalizeFunction(link); };
