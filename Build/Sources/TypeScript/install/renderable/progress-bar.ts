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

import Severity from './severity';
import { customElement, property } from 'lit/decorators';
import { html, LitElement, TemplateResult } from 'lit';

/**
 * Module: @typo3/install/module/progress-bar
 */
@customElement('typo3-install-progress-bar')
export class ProgressBar extends LitElement {
  @property({ type: String })
  public label: string = 'Loading...';

  @property({ type: String })
  public progress: string = '100';

  protected createRenderRoot(): HTMLElement | ShadowRoot {
    // @todo Switch to Shadow DOM once Bootstrap CSS style can be applied correctly
    return this;
  }

  protected render(): TemplateResult {
    return html`
      <div class="progress progress-bar-${Severity.getCssClass(Severity.loading)}">
        <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" aria-valuenow="${this.progress}" aria-valuemin="0" aria-valuemax="100" style="width: ${this.progress}%">
         <span>${this.label}</span>
        </div>
      </div>
    `;
  }
}

declare global {
  interface HTMLElementTagNameMap {
    'typo3-install-progress-bar': ProgressBar;
  }
}
