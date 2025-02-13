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

import { html, LitElement, TemplateResult, nothing } from 'lit';
import { Task } from '@lit/task';
import { customElement, property } from 'lit/decorators';
import { unsafeHTML } from 'lit/directives/unsafe-html';
import { Sizes, States, MarkupIdentifiers } from '../enum/icon-types';
import Icons, { IconStyles } from '../icons';
import '@typo3/backend/element/spinner-element';

/**
 * Module: @typo3/backend/element/icon-element
 *
 * @example
 * <typo3-backend-icon identifier="data-view-page" size="small"></typo3-backend-icon>
 */
@customElement('typo3-backend-icon')
export class IconElement extends LitElement {
  static override styles = IconStyles.getStyles();

  @property({ type: String, reflect: true }) identifier: string;
  @property({ type: String, reflect: true }) size: Sizes = Sizes.default;
  @property({ type: String }) state: States = States.default;
  @property({ type: String }) overlay: string = null;
  @property({ type: String }) markup: MarkupIdentifiers = MarkupIdentifiers.inline;

  /**
   * @internal Usage of `raw` attribute is discouraged due to security implications.
   *
   * The `raw` attribute value will be rendered unescaped into DOM as raw html (.innerHTML = raw).
   * That means it is the responsibility of the callee to ensure the HTML string does not contain
   * user supplied strings.
   * This attribute should therefore only be used to preserve backwards compatibility,
   * and must not be used in new code or with user supplied strings.
   * Use `identifier` attribute if ever possible instead.
   */
  @property({ type: String }) raw?: string = null;

  private readonly iconTask = new Task(this, {
    task: async ([identifier, size, overlay, state, markup]: [string, Sizes, States, string, MarkupIdentifiers], { signal }): Promise<string> => {
      return await Icons.getIcon(identifier, size, overlay, state, markup, signal);
    },
    args: () => [this.identifier, this.size, this.overlay, this.state, this.markup]
  });

  protected override render(): TemplateResult | symbol {
    if (this.raw) {
      return html`${unsafeHTML(this.raw)}`;
    }

    if (!this.identifier) {
      return nothing;
    }

    return this.iconTask.render({
      pending: () => html`<typo3-backend-spinner size=${this.size}></typo3-backend-size>`,
      complete: (markup) => html`${unsafeHTML(markup)}`,
      error: () => html`
        <span class="t3js-icon icon icon-size-${this.size} icon-state-${this.state} icon-default-not-found" data-identifier="default-not-found" aria-hidden="true">
	        <span class="icon-markup">
            <svg xmlns="http://www.w3.org/2000/svg" xml:space="preserve" viewBox="0 0 16 16"><g><path fill="#CD201F" d="m11 12 3-2v6H2v-6l3 2 3-2 3 2z"/><path fill="#212121" d="m8 10.3 2.86 1.91.14.09.14-.09 2.61-1.74v5.28H2.25v-5.28l2.61 1.74.14.09.14-.09L8 10.3m6-.3-3 2-3-2-3 2-3-2v6h12v-6z" opacity=".2"/><path fill="#CD201F" d="M14 4v4l-3 2-3-2-3 2-3-2V0h8l4 4z"/><path fill="#212121" d="M13.75 7.87 11 9.7 8.14 7.79 8 7.7l-.14.09L5 9.7 2.25 7.87V.25H10V0H2v8l3 2 3-2 3 2 3-2V4h-.25z" opacity=".2"/><path fill="#FFF" d="M14 4h-4V0l4 4z" opacity=".3"/><path fill="#212121" d="m14 8-4-4h4v4z" opacity=".3"/></g></svg>
	        </span>
        </span>
      `
    });
  }
}

declare global {
  interface HTMLElementTagNameMap {
    'typo3-backend-icon': IconElement;
  }
}
