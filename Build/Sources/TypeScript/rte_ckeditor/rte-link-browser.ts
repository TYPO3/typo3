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

import LinkBrowser from '@typo3/backend/link-browser';
import Modal from '@typo3/backend/modal';
import RegularEvent from '@typo3/core/event/regular-event';
import {Engine} from '@typo3/ckeditor5-bundle';
import {Typo3LinkCommand, Typo3LinkDict, LINK_ALLOWED_ATTRIBUTES, addLinkPrefix} from '@typo3/rte-ckeditor/plugin/typo3-link';
import type {EditorWithUI} from '@ckeditor/ckeditor5-core/src/editor/editorwithui';

/**
 * Module: @typo3/rte-ckeditor/rte-link-browser
 * LinkBrowser communication with parent window
 */
class RteLinkBrowser {
  protected editor: EditorWithUI = null;
  protected linkCommand: Typo3LinkCommand;
  protected ranges: Iterable<Engine.Range> = null;

  /**
   * @param {String} editorId Id of CKEditor
   */
  public initialize(editorId: string): void {
    this.editor = Modal.currentModal.userData.ckeditor;
    this.linkCommand = this.editor.commands.get('link') as Typo3LinkCommand;

    // Backup all ranges that are active when the Link Browser is requested
    this.ranges = this.editor.model.document.selection.getRanges();
    window.addEventListener('beforeunload', (): void => {
      this.editor.model.change((writer) => {
        writer.setSelection(this.ranges);
      });
    });

    const removeLinkElement = document.querySelector('.t3js-removeCurrentLink');
    if (removeLinkElement !== null) {
      new RegularEvent('click', (e: Event): void => {
        e.preventDefault();
        this.editor.execute('unlink');
        Modal.dismiss();
      }).bindTo(removeLinkElement);
    }
  }

  /**
   * Store the final link
   *
   * @param {String} link The select element or anything else which identifies the link (e.g. "page:<pageUid>" or "file:<uid>")
   */
  public finalizeFunction(link: string): void {
    const attributes: { [key: string]: string } = LinkBrowser.getLinkAttributeValues();
    const queryParams = attributes.params ? attributes.params : '';
    delete attributes.params;

    const linkText = ''; // @todo future feature: e.g. add page title as link-text (if applicable)
    const linkAttrs = this.convertAttributes(attributes, linkText);

    this.editor.model.change((writer) => writer.setSelection(this.ranges));
    this.linkCommand.execute(this.sanitizeLink(link, queryParams), linkAttrs);

    Modal.dismiss();
  }

  private convertAttributes(attributes: Record<string, string>, text?: string): Typo3LinkDict {
    const linkAttr: any = { attrs: {} };
    for (const [attribute, value] of Object.entries(attributes)) {
      if (LINK_ALLOWED_ATTRIBUTES.includes(attribute)) {
        linkAttr.attrs[addLinkPrefix(attribute)] = value;
      }
    }
    if (typeof text === 'string' && text !== '') {
      linkAttr.linkText = text;
    }
    return linkAttr as Typo3LinkDict;
  }

  private sanitizeLink(link: string, queryParams: string): string {
    // @todo taken from previous code - enhance generation
    // Make sure, parameters and anchor are in correct order
    const linkMatch = link.match(/^([a-z0-9]+:\/\/[^:\/?#]+(?:\/?[^?#]*)?)(\??[^#]*)(#?.*)$/)
    if (linkMatch && linkMatch.length > 0) {
      link = linkMatch[1] + linkMatch[2];
      const paramsPrefix = linkMatch[2].length > 0 ? '&' : '?';
      if (queryParams.length > 0) {
        if (queryParams[0] === '&') {
          queryParams = queryParams.substr(1)
        }
        // If params is set, append it
        if (queryParams.length > 0) {
          link += paramsPrefix + queryParams;
        }
      }
      link += linkMatch[3];
    }
    return link;
  }
}

// @todo check whether this is still required - if, document why/where
let rteLinkBrowser = new RteLinkBrowser();
export default rteLinkBrowser;
LinkBrowser.finalizeFunction = (link: string): void => { rteLinkBrowser.finalizeFunction(link); };
