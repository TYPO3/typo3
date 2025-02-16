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

import DocumentService from '@typo3/core/document-service';
/**
 * Module: @typo3/backend/layout-module/paste
 * Dynamically adds "Paste" Icons in the Page Layout module (Web => Page)
 * and triggers a modal window. which then calls the AjaxDataHandler
 * to execute the action to paste the current clipboard contents.
 */
import DataHandler from '../ajax-data-handler';
import { default as Modal, type ModalElement, type Button } from '@typo3/backend/modal';
import Severity from '../severity';
import '@typo3/backend/element/icon-element';
import { SeverityEnum } from '../enum/severity';
import RegularEvent from '@typo3/core/event/regular-event';
import type ResponseInterface from '../ajax-data-handler/response-interface';

type PasteOptions = {
  itemOnClipboardUid: number;
  itemOnClipboardTitle: string;
  copyMode: string;
}

class Paste {
  private readonly itemOnClipboardUid: number = 0;
  private readonly itemOnClipboardTitle: string = '';
  private readonly copyMode: string = '';
  private readonly elementIdentifier: string = '.t3js-page-ce';
  private pasteAfterLinkTemplate: string = '';
  private pasteIntoLinkTemplate: string = '';

  /**
   * initializes paste icons for all content elements on the page
   */
  constructor(args: PasteOptions) {
    this.itemOnClipboardUid = args.itemOnClipboardUid;
    this.itemOnClipboardTitle = args.itemOnClipboardTitle;
    this.copyMode = args.copyMode;

    DocumentService.ready().then((): void => {
      if (document.querySelectorAll('.t3js-page-columns').length > 0) {
        this.generateButtonTemplates();
        this.activatePasteIcons();
        this.initializeEvents();
      }
    });
  }

  private static determineColumn(element: HTMLElement): number {
    const columnContainer = element.closest('[data-colpos]') as HTMLElement|null;
    return parseInt(columnContainer?.dataset?.colpos ?? '0', 10);
  }

  private initializeEvents(): void
  {
    new RegularEvent('click', (evt: Event, target: HTMLElement): void => {
      evt.preventDefault();

      this.activatePasteModal(target);
    }).delegateTo(document, '.t3js-paste');
  }

  private generateButtonTemplates(): void {
    if (!this.itemOnClipboardUid) {
      return;
    }
    this.pasteAfterLinkTemplate = '<button'
      + ' type="button"'
      + ' class="t3js-paste t3js-paste' + (this.copyMode ? '-' + this.copyMode : '') + ' t3js-paste-after btn btn-default btn-sm"'
      + ' title="' + TYPO3.lang?.pasteAfterRecord + '">'
      + '<typo3-backend-icon identifier="actions-document-paste-into" size="small"></typo3-backend-icon>'
      + '</button>';
    this.pasteIntoLinkTemplate = '<button'
      + ' type="button"'
      + ' class="t3js-paste t3js-paste' + (this.copyMode ? '-' + this.copyMode : '') + ' t3js-paste-into btn btn-default btn-sm"'
      + ' title="' + TYPO3.lang?.pasteIntoColumn + '">'
      + '<typo3-backend-icon identifier="actions-document-paste-into" size="small"></typo3-backend-icon>'
      + '</button>';
  }

  /**
   * activates the paste into / paste after icons outside the context menus
   */
  private activatePasteIcons(): void {
    if (this.pasteAfterLinkTemplate && this.pasteIntoLinkTemplate) {
      document.querySelectorAll('.t3js-page-new-ce').forEach((el: HTMLElement): void => {
        const template = el.parentElement.dataset.page ? this.pasteIntoLinkTemplate : this.pasteAfterLinkTemplate;
        el.append(document.createRange().createContextualFragment(template));
      });
    }
  }

  /**
   * generates the paste into / paste after modal
   */
  private activatePasteModal(element: HTMLElement): void {
    const title = (TYPO3.lang['paste.modal.title.paste'] || 'Paste record') + ': "' + this.itemOnClipboardTitle + '"';
    const content = TYPO3.lang['paste.modal.paste'] || 'Do you want to paste the record to this position?';

    let buttons: Array<Button> = [];
    buttons = [
      {
        text: TYPO3.lang['paste.modal.button.cancel'] || 'Cancel',
        active: true,
        btnClass: 'btn-default',
        trigger: (e: Event, modal: ModalElement): void => modal.hideModal(),
      },
      {
        text: TYPO3.lang['paste.modal.button.paste'] || 'Paste',
        btnClass: 'btn-' + Severity.getCssClass(SeverityEnum.warning),
        trigger: (e: Event, modal: ModalElement): void => {
          modal.hideModal();
          this.execute(element);
        },
      },
    ];

    Modal.show(title, content, SeverityEnum.warning, buttons);
  }

  /**
   * Send an AJAX request via the AjaxDataHandler
   */
  private execute(element: HTMLElement): void {
    const colPos = Paste.determineColumn(element);
    const closestElement = element.closest(this.elementIdentifier) as HTMLElement;
    const targetFound = closestElement.dataset.uid;
    let targetPid;
    if (typeof targetFound === 'undefined') {
      targetPid = parseInt(closestElement.dataset.page, 10);
    } else {
      targetPid = 0 - parseInt(targetFound, 10);
    }
    const language = parseInt((element.closest('[data-language-uid]') as HTMLElement).dataset.languageUid, 10);
    const parameters = {
      CB: {
        paste: 'tt_content|' + targetPid,
        pad: 'normal',
        update: {
          colPos: colPos,
          sys_language_uid: language,
        },
      },
    };

    DataHandler.process(parameters).then((result: ResponseInterface): void => {
      if (!result.hasErrors) {
        window.location.reload();
      }
    });
  }
}

export default Paste;
