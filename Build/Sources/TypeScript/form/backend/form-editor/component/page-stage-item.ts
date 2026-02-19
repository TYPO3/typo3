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
import { customElement, property } from 'lit/decorators.js';
import labels from '~labels/form.form_editor_javascript';

/**
 * Module: @typo3/form/backend/form-editor/component/page-stage-item
 *
 * Functionality for the page stage item element (top-level form elements)
 *
 * @example
 * <typo3-form-page-stage-item
 *   page-title="Step 1">
 * </typo3-form-page-stage-item>
 */
@customElement('typo3-form-page-stage-item')
export class PageStageItem extends LitElement {
  @property({ type: String, attribute: 'page-title' }) pageTitle: string = '';

  protected override createRenderRoot(): HTMLElement | ShadowRoot {
    // Avoid Shadow DOM so global styles apply to the element contents
    return this;
  }

  protected override render(): TemplateResult {
    const displayTitle = this.pageTitle || labels.get('formEditor.step.name.empty');

    return html`
      <h2 class="formeditor-page-title">
        ${displayTitle}
      </h2>
    `;
  }
}

declare global {
  interface HTMLElementTagNameMap {
    'typo3-form-page-stage-item': PageStageItem;
  }
}

