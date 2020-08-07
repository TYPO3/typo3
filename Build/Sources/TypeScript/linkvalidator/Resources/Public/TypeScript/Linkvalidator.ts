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
import Notification = require('TYPO3/CMS/Backend/Notification');

/**
 * Module: TYPO3/CMS/Linkvalidator/Linkvalidator
 */
class Linkvalidator {
  constructor() {
    this.initializeEvents();
  }

  public toggleActionButton(prefix: string): void {
    let buttonDisable = true;
    $('.' + prefix).each((index: number, element: HTMLInputElement): void => {
      if ($(element).prop('checked')) {
        buttonDisable = false;
      }
    });

    if (prefix === 'check') {
      $('#updateLinkList').prop('disabled', buttonDisable);
    } else {
      $('#refreshLinkList').prop('disabled', buttonDisable);
    }
  }

  /**
   * Registers listeners
   */
  protected initializeEvents(): void {
    $('.refresh').on('click', (): void => {
      this.toggleActionButton('refresh');
    });

    $('.check').on('click', (): void => {
      this.toggleActionButton('check');
    });

    $('.t3js-update-button').on('click', (e: JQueryEventObject): void => {
      const $element = $(e.currentTarget);
      const name = $element.attr('name');
      let message = 'Event triggered';
      if (name === 'refreshLinkList' || name === 'updateLinkList') {
        message = $element.data('notification-message');
      }
      Notification.success(message);
    });
  }
}

export = new Linkvalidator();
