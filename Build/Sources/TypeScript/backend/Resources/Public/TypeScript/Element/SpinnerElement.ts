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

import {html, css, LitElement, TemplateResult} from 'lit';
import {customElement, property} from 'lit/decorators';
import {Sizes} from '../Enum/IconTypes';

/**
 * Module: TYPO3/CMS/Backend/Element/SpinnerElement
 *
 * @example
 * <typo3-backend-spinner size="small"></typo3-backend-spinner>
 * + attribute size can be one of small, medium, large
 */
@customElement('typo3-backend-spinner')
export class SpinnerElement extends LitElement {
  @property({type: String}) size: Sizes = Sizes.default;

  static styles = css`
    :host {
      font-size: 32px;
      width: 1em;
      height: 1em;
      display: flex;
      justify-content: center;
      align-items: center;
    }
    .spinner {
      display: block;
      border-style: solid;
      border-color: #212121 #bababa #bababa;
      border-radius: 50%;
      width: 0.625em;
      height: 0.625em;
      border-width: 0.0625em;
      animation: spin 1s linear infinite;
    }
    :host([size=small]) .spinner {
      font-size: 16px;
    }
    :host([size=large]) .spinner {
      font-size: 48px;
    }
    :host([size=mega]) .spinner {
      font-size: 64px;
    }
    @keyframes spin {
      0% { transform: rotate(0deg); }
      100% { transform: rotate(360deg); }
    }
  `;

  public render(): TemplateResult {
    return html`<div class="spinner"></div>`
  }
}
