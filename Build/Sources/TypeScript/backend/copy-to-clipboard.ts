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

import { html, css, TemplateResult, LitElement } from 'lit';
import { customElement, property } from 'lit/decorators';
import Notification from '@typo3/backend/notification';
import { lll } from '@typo3/core/lit-helper';

export function copyToClipboard(text: string): void {
  if (!text.length) {
    console.warn('No text for copy to clipboard given.');
    Notification.error(lll('copyToClipboard.error'));
    return;
  }
  if (navigator.clipboard) {
    navigator.clipboard.writeText(text).then((): void => {
      Notification.success(lll('copyToClipboard.success'), '', 1);
    }).catch((): void => {
      Notification.error(lll('copyToClipboard.error'));
    });
  } else {
    const textarea = document.createElement('textarea');
    textarea.value = text;
    document.body.appendChild(textarea);
    textarea.focus();
    textarea.select();
    try {
      if (document.execCommand('copy')) {
        Notification.success(lll('copyToClipboard.success'), '', 1);
      } else {
        Notification.error(lll('copyToClipboard.error'));
      }
    } catch {
      Notification.error(lll('copyToClipboard.error'));
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
 * <typo3-copy-to-clipboard text="some text">
 *   Copy to clipboard
 * </typo3-copy-to-clipboard>
 */
@customElement('typo3-copy-to-clipboard')
export class CopyToClipboard extends LitElement {
  static styles = [css`:host { cursor: pointer; appearance: button; }`];
  @property({ type: String }) text: string;

  public constructor() {
    super();
    this.addEventListener('click', (e: Event): void => {
      e.preventDefault();
      this.copyToClipboard();
    });
    this.addEventListener('keydown', (e: KeyboardEvent): void => {
      if (e.key === 'Enter' || e.key === ' ') {
        e.preventDefault();
        this.copyToClipboard();
      }
    });
  }

  public connectedCallback(): void {
    if (!this.hasAttribute('role')) {
      this.setAttribute('role', 'button');
    }
    if (!this.hasAttribute('tabindex')) {
      this.setAttribute('tabindex', '0');
    }
  }

  protected render(): TemplateResult {
    return html`<slot></slot>`;
  }

  private copyToClipboard(): void {
    if (typeof this.text !== 'string') {
      console.warn('No text for copy to clipboard given.');
      Notification.error(lll('copyToClipboard.error'));
      return;
    }
    copyToClipboard(this.text);
  }
}

declare global {
  interface HTMLElementTagNameMap {
    'typo3-copy-to-clipboard': CopyToClipboard;
  }
}
