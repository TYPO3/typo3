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

import { customElement, property } from 'lit/decorators.js';
import { PseudoButtonLitElement } from '@typo3/backend/element/pseudo-button';
import Notification from '@typo3/backend/notification';
import { lll } from '@typo3/core/lit-helper';

export function copyToClipboard(text: string, silent: boolean = false): void {
  if (!text.length) {
    console.warn('No text for copy to clipboard given.');
    if (!silent) {
      Notification.error(lll('copyToClipboard.error'));
    }
    return;
  }
  if (navigator.clipboard) {
    navigator.clipboard.writeText(text).then((): void => {
      document.dispatchEvent(new CustomEvent('copy-to-clipboard-success'));
      if (!silent) {
        Notification.success(lll('copyToClipboard.success'), '', 1);
      }
    }).catch((): void => {
      document.dispatchEvent(new CustomEvent('copy-to-clipboard-error'));
      if (!silent) {
        Notification.error(lll('copyToClipboard.error'));
      }
    });
  } else {
    const textarea = document.createElement('textarea');
    textarea.value = text;
    document.body.appendChild(textarea);
    textarea.focus();
    textarea.select();
    try {
      if (document.execCommand('copy')) {
        document.dispatchEvent(new CustomEvent('copy-to-clipboard-success'));
        if (!silent) {
          Notification.success(lll('copyToClipboard.success'), '', 1);
        }
      } else if (!silent) {
        document.dispatchEvent(new CustomEvent('copy-to-clipboard-error'));
        Notification.error(lll('copyToClipboard.error'));
      }
    } catch {
      if (!silent) {
        document.dispatchEvent(new CustomEvent('copy-to-clipboard-error'));
        Notification.error(lll('copyToClipboard.error'));
      }
    }
    document.body.removeChild(textarea);
  }
}

/**
 * Module: @typo3/backend/copy-to-clipboard
 *
 * This module can be used to copy a given text to
 * the operating systems' clipboard.
 *
 * @example
 * <typo3-copy-to-clipboard text="some text" silent>
 *   Copy to clipboard
 * </typo3-copy-to-clipboard>
 */
@customElement('typo3-copy-to-clipboard')
export class CopyToClipboard extends PseudoButtonLitElement {
  @property({ type: String }) text: string;
  @property({ type: Boolean }) silent: boolean = false;

  protected override buttonActivated(): void {
    if (typeof this.text !== 'string') {
      console.warn('No text for copy to clipboard given.');
      if (!this.silent) {
        document.dispatchEvent(new CustomEvent('copy-to-clipboard-error'));
        Notification.error(lll('copyToClipboard.error'));
      }
      return;
    }
    copyToClipboard(this.text, this.silent);
  }
}

declare global {
  interface HTMLElementTagNameMap {
    'typo3-copy-to-clipboard': CopyToClipboard;
  }
}
