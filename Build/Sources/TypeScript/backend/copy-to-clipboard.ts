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

import {html, TemplateResult, LitElement} from 'lit';
import {customElement, property} from 'lit/decorators';
import Notification from '@typo3/backend/notification';
import {lll} from '@typo3/core/lit-helper';

/**
 * Module: @typo3/backend/copy-to-clipboard
 *
 * This module can be used to copy a given text to
 * the operating systems' clipboard.
 *
 * @example
 * <typo3-copy-to-clipboard text="some text">
 *   <button>Copy to clipboard</button>
 * </typo3-copy-to-clipboard>
 */
@customElement('typo3-copy-to-clipboard')
class CopyToClipboard extends LitElement {
  @property({type: String}) text: string;

  public constructor() {
    super();
    this.addEventListener('click', (e: Event): void => {
      e.preventDefault();
      this.copyToClipboard()
    });
  }

  protected render(): TemplateResult {
    return html`<slot></slot>`;
  }

  private copyToClipboard(): void {
    if (typeof this.text !== 'string' || !this.text.length) {
      console.warn('No text for copy to clipboard given.')
      Notification.error(lll('copyToClipboard.error'));
      return;
    }
    if (navigator.clipboard) {
      navigator.clipboard.writeText(this.text).then((): void => {
        Notification.success(lll('copyToClipboard.success'), '', 1);
      }).catch((): void => {
        Notification.error(lll('copyToClipboard.error'));
      });
    } else {
      const textarea = document.createElement('textarea');
      textarea.value = this.text;
      document.body.appendChild(textarea);
      textarea.focus();
      textarea.select();
      try {
        document.execCommand('copy')
          ? Notification.success(lll('copyToClipboard.success'), '', 1)
          : Notification.error(lll('copyToClipboard.error'));
      } catch (err) {
        Notification.error(lll('copyToClipboard.error'));
      }
      document.body.removeChild(textarea);
    }
  }
}
