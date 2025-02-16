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

import { html, LitElement, type TemplateResult } from 'lit';
import { customElement, property } from 'lit/decorators';
import { Sizes } from '../enum/icon-types';
import { IconStyles } from '@typo3/backend/icons';

/**
 * Module: @typo3/backend/element/spinner-element
 *
 * @example
 * <typo3-backend-spinner size="small"></typo3-backend-spinner>
 * + attribute size can be one of small, default, large or mega
 */
@customElement('typo3-backend-spinner')
export class SpinnerElement extends LitElement {
  static override styles = IconStyles.getStyles();

  @property({ type: String }) size: Sizes = Sizes.default;

  protected override render(): TemplateResult {
    return html`
      <span class="icon icon-size-${this.size} icon-state-default icon-spin">
        <span class="icon-markup">
          <svg xmlns="http://www.w3.org/2000/svg" xml:space="preserve" viewBox="0 0 16 16">
            <g fill="currentColor">
              <path d="M8 15c-3.86 0-7-3.141-7-7 0-3.86 3.14-7 7-7 3.859 0 7 3.14 7 7 0 3.859-3.141 7-7 7zM8 3C5.243 3 3 5.243 3 8s2.243 5 5 5 5-2.243 5-5-2.243-5-5-5z" opacity=".3"/><path d="M14 9a1 1 0 0 1-1-1c0-2.757-2.243-5-5-5a1 1 0 0 1 0-2c3.859 0 7 3.14 7 7a1 1 0 0 1-1 1z"/>
            </g>
          </svg>
        </span>
      </span>
    `;
  }
}

declare global {
  interface HTMLElementTagNameMap {
    'typo3-backend-spinner': SpinnerElement;
  }
}
