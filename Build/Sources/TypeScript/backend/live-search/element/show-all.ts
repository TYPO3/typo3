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

import {customElement} from 'lit/decorators';
import {html, LitElement, TemplateResult} from 'lit';
import {lll} from '@typo3/core/lit-helper';
import Modal from '@typo3/backend/modal';

@customElement('typo3-backend-live-search-show-all')
export class ResultItem extends LitElement {
  public connectedCallback() {
    super.connectedCallback();

    this.addEventListener('click', this.dispatchItemChosenEvent);
  }

  public createRenderRoot(): HTMLElement | ShadowRoot {
    // Avoid shadow DOM for Bootstrap CSS to be applied
    return this;
  }

  protected render(): TemplateResult {
    return html`<button class="btn btn-primary">${lll('liveSearch_showAllResults')}</button>`;
  }

  private dispatchItemChosenEvent(e: PointerEvent): void {
    e.preventDefault();

    const searchField = (document.querySelector('typo3-backend-live-search').querySelector('input[type="search"]')) as HTMLInputElement;
    TYPO3.ModuleMenu.App.showModule('web_list', 'id=0&search_levels=-1&searchTerm=' + encodeURIComponent(searchField.value));

    Modal.dismiss();
  }
}
