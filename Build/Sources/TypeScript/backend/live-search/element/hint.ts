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

import { customElement, property } from 'lit/decorators';
import { html, LitElement, nothing, type TemplateResult } from 'lit';
import { markdown } from '@typo3/core/directive/markdown';
import '@typo3/backend/element/icon-element';

@customElement('typo3-backend-live-search-hint')
export class Hint extends LitElement {
  @property({ type: String }) hint: string;

  protected override createRenderRoot(): HTMLElement | ShadowRoot {
    return this;
  }

  protected override render(): TemplateResult | symbol {
    if (this.hint === '') {
      return nothing;
    }
    return html`<typo3-backend-icon identifier="actions-lightbulb-on" size="small"></typo3-backend-icon> ${markdown(this.hint, 'minimal')}`;
  }
}
