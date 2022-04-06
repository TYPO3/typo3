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
 * Module: @typo3/backend/layout-module/paste
 * Dynamically adds "Paste" Icons in the Page Layout module (Web => Page)
 * and triggers a modal window. which then calls the AjaxDataHandler
 * to execute the action to paste the current clipboard contents.
 */
import $ from 'jquery';
import ResponseInterface from '../ajax-data-handler/response-interface';
import DataHandler from '../ajax-data-handler';
import Modal from '../modal';
import Severity from '../severity';
import '@typo3/backend/element/icon-element';
import {SeverityEnum} from '../enum/severity';

interface Button {
  text: string;
  active?: boolean;
  btnClass: string;
  trigger: () => void;
}

class Paste {
  public itemOnClipboardUid: number = 0;
  public itemOnClipboardTitle: string = '';
  public copyMode: string = '';
  private elementIdentifier: string = '.t3js-page-ce';
  private pasteAfterLinkTemplate: string = '';
  private pasteIntoLinkTemplate: string = '';

  /**
   * @param {JQuery} $element
   * @return number
   */
  private static determineColumn($element: JQuery): number {
    const $columnContainer = $element.closest('[data-colpos]');
    if ($columnContainer.length && $columnContainer.data('colpos') !== 'undefined') {
      return $columnContainer.data('colpos');
    }

    return 0;
  }

  /**
   * initializes paste icons for all content elements on the page
   */
  constructor() {
    $((): void => {
      if ($('.t3js-page-columns').length) {
        this.generateButtonTemplates();
        this.activatePasteIcons();
        this.initializeEvents();
      }
    });
  }

  private initializeEvents(): void
  {
    $(document).on('click', '.t3js-paste', (evt: Event): void => {
      evt.preventDefault();
      this.activatePasteModal($(evt.currentTarget));
    });
  }

  private generateButtonTemplates(): void
  {
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
   * activates the paste into / paste after icons outside of the context menus
   */
  private activatePasteIcons(): void {
    $('.t3-page-ce-wrapper-new-ce').each((index: number, el: HTMLElement): void => {
      if (this.pasteAfterLinkTemplate && this.pasteIntoLinkTemplate) {
        const parent = $(el).parent();
        // append the buttons
        if (parent.data('page')) {
          $(el).append(this.pasteIntoLinkTemplate);
        } else {
          $(el).append(this.pasteAfterLinkTemplate);
        }
      }
    });
  }

  /**
   * generates the paste into / paste after modal
   */
  private activatePasteModal($element: JQuery): void {
    const title = (TYPO3.lang['paste.modal.title.paste'] || 'Paste record') + ': "' + this.itemOnClipboardTitle + '"';
    const content = TYPO3.lang['paste.modal.paste'] || 'Do you want to paste the record to this position?';

    let buttons: Array<Button> = [];
    buttons = [
      {
        text: TYPO3.lang['paste.modal.button.cancel'] || 'Cancel',
        active: true,
        btnClass: 'btn-default',
        trigger: (): void => {
          Modal.currentModal.trigger('modal-dismiss');
        },
      },
      {
        text: TYPO3.lang['paste.modal.button.paste'] || 'Paste',
        btnClass: 'btn-' + Severity.getCssClass(SeverityEnum.warning),
        trigger: (): void => {
          Modal.currentModal.trigger('modal-dismiss');
          this.execute($element);
        },
      },
    ];

    Modal.show(title, content, SeverityEnum.warning, buttons);
  }

  /**
   * Send an AJAX request via the AjaxDataHandler
   *
   * @param {JQuery} $element
   */
  private execute($element: JQuery): void {
    const colPos = Paste.determineColumn($element);
    const closestElement = $element.closest(this.elementIdentifier);
    const targetFound = closestElement.data('uid');
    let targetPid;
    if (typeof targetFound === 'undefined') {
      targetPid = parseInt(closestElement.data('page'), 10);
    } else {
      targetPid = 0 - parseInt(targetFound, 10);
    }
    const language = parseInt($element.closest('[data-language-uid]').data('language-uid'), 10);
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

export default new Paste();
