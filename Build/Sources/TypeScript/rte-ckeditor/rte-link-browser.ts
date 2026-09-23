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

import LinkBrowser, { type LinkAttributes } from '@typo3/backend/link-browser';
import Modal from '@typo3/backend/modal';
import { resolveLink } from '@typo3/backend/link-resolver';
import RegularEvent from '@typo3/core/event/regular-event';
import { type Typo3LinkDict, LINK_ALLOWED_ATTRIBUTES, addLinkPrefix } from '@typo3/rte-ckeditor/plugin/typo3-link';
import type * as Core from '@ckeditor/ckeditor5-core';
import type { ModelPosition } from '@ckeditor/ckeditor5-engine';
import '@typo3/backend/element/combobox-element';

/**
 * Module: @typo3/rte-ckeditor/rte-link-browser
 * LinkBrowser communication with parent window
 */
class RteLinkBrowser {
  protected editor: Core.Editor = null;
  protected selectionStartPosition: ModelPosition = null;
  protected selectionEndPosition: ModelPosition = null;

  public initialize(): void {
    this.editor = Modal.currentModal.userData.editor;
    this.selectionStartPosition = Modal.currentModal.userData.selectionStartPosition;
    this.selectionEndPosition = Modal.currentModal.userData.selectionEndPosition;

    const removeLinkElement = document.querySelector('.t3js-removeCurrentLink');
    if (removeLinkElement !== null) {
      new RegularEvent('click', (e: Event): void => {
        e.preventDefault();
        this.restoreSelection();
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
  public async finalizeFunction(link: string): Promise<void> {
    const attributes = LinkBrowser.getLinkAttributeValues();
    const queryParams = attributes.params ? attributes.params : '';
    delete attributes.params;

    const href = this.sanitizeLink(link, queryParams);
    // Only relevant when no text is selected: the link title (if filled) or the resolved
    // title of the target (e.g. the page title) is used as text of the new link.
    const linkText = this.selectionStartPosition.isEqual(this.selectionEndPosition)
      ? (attributes.title || (await resolveLink(href)).title || '')
      : '';
    const linkAttrs = this.convertAttributes(attributes, linkText);

    this.restoreSelection();
    // eslint-disable-next-line @typescript-eslint/ban-ts-comment
    // @ts-ignore
    this.editor.execute('link', href, linkAttrs);

    Modal.dismiss();
  }

  private restoreSelection(): void {
    this.editor.model.change((writer) => {
      const ranges = [writer.createRange(this.selectionStartPosition, this.selectionEndPosition)];
      writer.setSelection(ranges);
    });
  }

  private convertAttributes(attributes: LinkAttributes, text?: string): Typo3LinkDict {
    const linkAttr: { attrs: { [key: string]: string}, linkText?: string} = { attrs: {} };
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
    const linkMatch = link.match(/^([a-z0-9]+:\/\/[^:/?#]+(?:\/?[^?#]*)?)(\??[^#]*)(#?.*)$/);
    if (linkMatch && linkMatch.length > 0) {
      link = linkMatch[1] + linkMatch[2];
      const paramsPrefix = linkMatch[2].length > 0 ? '&' : '?';
      if (queryParams.length > 0) {
        if (queryParams.startsWith('&')) {
          queryParams = queryParams.substr(1);
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
const rteLinkBrowser = new RteLinkBrowser();
export default rteLinkBrowser;
LinkBrowser.finalizeFunction = (link: string): void => { void rteLinkBrowser.finalizeFunction(link); };
