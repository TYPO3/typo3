/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read theÍ
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

import { html, LitElement, TemplateResult } from 'lit';
import { customElement, property } from 'lit/decorators';
import AjaxRequest from '@typo3/core/ajax/ajax-request';
import '@typo3/backend/element/icon-element';
import type { ColorSchemeUpdateEventData, ColorScheme } from '@typo3/backend/user-settings-manager';

interface ColorSchemeOption {
  label: string,
  icon: string,
  value: ColorScheme,
}

@customElement('typo3-backend-color-scheme-switch')
export class ColorSchemeSwitchElement extends LitElement {
  @property({ type: String }) activeColorScheme: string = null;
  @property({ type: Array }) data: ColorSchemeOption[] = null;

  protected createRenderRoot(): HTMLElement | ShadowRoot {
    return this;
  }

  protected render(): TemplateResult | symbol {
    return html`
      <ul class="dropdown-list">
        ${this.data.map(item => this.renderItem(item))}
      </ul>
    `;
  }

  protected renderItem(colorScheme: ColorSchemeOption): TemplateResult | symbol {
    return html`
      <li>
        <button class="dropdown-item" @click="${() => this.handleClick(colorScheme.value)}" aria-current="${this.activeColorScheme === colorScheme.value ? 'true' : 'false'}">
          <span class="dropdown-item-columns">
            ${this.activeColorScheme === colorScheme.value ? html`
              <span class="text-primary">
                <typo3-backend-icon identifier="actions-dot" size="small"></typo3-backend-icon>
              </span>
            ` : html`
              <typo3-backend-icon identifier="empty-empty" size="small"></typo3-backend-icon>
            `}
            <span class="dropdown-item-column dropdown-item-column-icon" aria-hidden="true">
              <typo3-backend-icon identifier="${colorScheme.icon}" size="small"></typo3-backend-icon>
            </span>
            <span class="dropdown-item-column dropdown-item-column-title">
              ${colorScheme.label}
            </span>
            <slot></slot>
          </span>
        </button>
      </li>
    `;
  }

  private async handleClick(value: ColorScheme): Promise<void> {
    this.triggerSchemeUpdate(value);
    await this.persistSchemeUpdate(value);
  }

  private async persistSchemeUpdate(colorScheme: ColorScheme) {
    const url = new URL(TYPO3.settings.ajaxUrls.color_scheme_update, window.location.origin);

    return await new AjaxRequest(url).post({ colorScheme });
  }

  private triggerSchemeUpdate(colorScheme: ColorScheme): void {
    document.dispatchEvent(new CustomEvent<ColorSchemeUpdateEventData>('typo3:color-scheme:update', { detail: { colorScheme } }));
  }
}

declare global {
  interface HTMLElementTagNameMap {
    'typo3-backend-color-scheme-switch': ColorSchemeSwitchElement;
  }
}
