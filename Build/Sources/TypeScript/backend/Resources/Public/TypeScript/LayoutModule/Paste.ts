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
 * Module: TYPO3/CMS/Backend/LayoutModule/Paste
 * this JS code does the paste logic for the Layout module (Web => Page)
 * based on jQuery UI
 */
import $ from 'jquery';
import ResponseInterface from '../AjaxDataHandler/ResponseInterface';
import DataHandler = require('../AjaxDataHandler');
import Modal = require('../Modal');
import Severity = require('../Severity');

interface Button {
  text: string;
  active?: boolean;
  btnClass: string;
  trigger: () => void;
}

class Paste {
  private elementIdentifier: string = '.t3js-page-ce';

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
        this.activatePasteIcons();
      }
    });
  }

  /**
   * activates the paste into / paste after icons outside of the context menus
   */
  private activatePasteIcons(): void {
    const me = this;

    $('.t3-page-ce-wrapper-new-ce').each((index: number, el: HTMLElement): void => {
      if (!$(el).find('.t3js-toggle-new-content-element-wizard').length) {
        return;
      }
      $('.t3js-page-lang-column .t3-page-ce > .t3-page-ce').removeClass('t3js-page-ce');
      if (top.pasteAfterLinkTemplate && top.pasteIntoLinkTemplate) {
        const parent = $(el).parent();
        if (parent.data('page')) {
          $(el).append(top.pasteIntoLinkTemplate);
        } else {
          $(el).append(top.pasteAfterLinkTemplate);
        }
        $(el).find('.t3js-paste').on('click', (evt: Event): void => {
          evt.preventDefault();
          me.activatePasteModal($(evt.currentTarget));
        });
      }
    });
  }

  /**
   * generates the paste into / paste after modal
   */
  private activatePasteModal(element: JQuery): void {
    const me = this;
    const $element = $(element);
    const url = $element.data('url') || null;
    const title = (TYPO3.lang['paste.modal.title.paste'] || 'Paste record') + ': "' + $element.data('title') + '"';
    const content = TYPO3.lang['paste.modal.paste'] || 'Do you want to paste the record to this position?';
    const severity = (typeof top.TYPO3.Severity[$element.data('severity')] !== 'undefined') ?
      top.TYPO3.Severity[$element.data('severity')] :
      top.TYPO3.Severity.info;

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
        btnClass: 'btn-' + Severity.getCssClass(severity),
        trigger: (): void => {
          Modal.currentModal.trigger('modal-dismiss');
          me.execute($element);
        },
      },
    ];
    if (url !== null) {
      const separator = url.contains('?') ? '&' : '?';
      const params = $.param({data: $element.data()});
      Modal.loadUrl(title, severity, buttons, url + separator + params);
    } else {
      Modal.show(title, content, severity, buttons);
    }
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
        update: {
          colPos: colPos,
          sys_language_uid: language,
        },
      },
    };

    DataHandler.process(parameters).then((result: ResponseInterface): void => {
      if (result.hasErrors) {
        return;
      }

      window.location.reload();
    });
  }
}

export = new Paste();
