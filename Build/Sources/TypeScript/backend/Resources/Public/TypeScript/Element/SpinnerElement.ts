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

import {html, css, customElement, property, LitElement, TemplateResult, CSSResult} from 'lit-element';

/**
 * Module: TYPO3/CMS/Backend/Element/SpinnerElement
 *
 * @example
 * <typo3-backend-spinner size="small"></typo3-backend-spinner>
 * + attribute size can be one of small, medium, large
 */
@customElement('typo3-backend-spinner')
export class SpinnerElement extends LitElement {
  @property({type: String}) size: string = 'small';

  public static get styles(): CSSResult
  {
    return css`
      :host {
        display: block;
      }
      .spinner {
        display: block;
        margin: 2px;
        border-style: solid;
        border-color: #212121 #bababa #bababa;
        border-radius: 50%;
        animation: spin 1s linear infinite;
      }
      .spinner.small {
        border-width: 2px;
        width: 10px;
        height: 10px;
      }
      .spinner.medium {
        border-width: 3px;
        width: 14px;
        height: 14px;
      }
      .spinner.large {
        border-width: 4px;
        width: 20px;
        height: 20px;
      }
      @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
      }
    `;
  }

  public render(): TemplateResult {
    return html`<div class="spinner ${this.size}"></div>`
  }
}
