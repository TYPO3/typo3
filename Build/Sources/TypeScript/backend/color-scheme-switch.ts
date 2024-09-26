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

import { html, LitElement, nothing, TemplateResult } from 'lit';
import { customElement, property, state } from 'lit/decorators';
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
  @property({ type: String }) activeColorScheme: ColorScheme = null;
  @property({ type: Array }) colorSchemes: ColorSchemeOption[] = null;
  @property({ type: String }) label: string;

  @state() advancedOptionsExpanded: boolean = false;
  @state() autoDetect: ColorScheme|null = null;

  private mql: MediaQueryList|null = null;

  public connectedCallback() {
    super.connectedCallback();
    this.mql = window.matchMedia('(prefers-color-scheme: dark)');
    this.mediaQueryListener(this.mql);
    this.mql.addEventListener('change', this.mediaQueryListener);
  }

  public disconnectedCallback() {
    super.disconnectedCallback();
    this.mql.removeEventListener('change', this.mediaQueryListener);
    this.mql = null;
  }

  protected readonly mediaQueryListener = (mql: MediaQueryList|MediaQueryListEvent) => this.autoDetect = mql.matches ? 'dark' : 'light';

  protected createRenderRoot(): HTMLElement | ShadowRoot {
    return this;
  }

  protected getRealColorScheme(): ColorScheme {
    if (this.activeColorScheme === 'auto') {
      return this.autoDetect ?? 'light';
    }
    return this.activeColorScheme ?? 'light';
  }

  protected render(): TemplateResult | symbol {
    const isDark = this.getRealColorScheme() === 'dark';
    return html`
      <div class="btn-group">
        <button
            type="button"
            class="btn ${isDark ? 'btn-primary' : 'btn-default'}"
            aria-pressed=${isDark ? 'true' : 'false'}
            title=${this.label}
            @click=${(e: Event) => this.toggle(e)}
        >
          <typo3-backend-icon identifier=${this.getIcon(this.activeColorScheme ?? 'auto')} size="small"></typo3-backend-icon>
          ${this.getLabel(this.getRealColorScheme())}
        </button>

        <button
            type="button"
            class="btn btn-default ${this.advancedOptionsExpanded ? 'active' : ''}"
            aria-haspopup="true"
            aria-expanded=${this.advancedOptionsExpanded ? 'true' : 'false'}
            @click=${(e: Event) => { e.stopPropagation(); this.advancedOptionsExpanded = !this.advancedOptionsExpanded }}
            >
          <span class="visually-hidden">Show more options</span>
          <typo3-backend-icon identifier=${this.advancedOptionsExpanded ? 'actions-chevron-up' : 'actions-chevron-down'} size="small"></typo3-backend-icon>
        </button>
      </div>
      ${this.advancedOptionsExpanded === false ? nothing : html`
        <ul class="dropdown-list">
          ${this.colorSchemes.map(item => this.renderItem(item))}
        </ul>
      `}
    `;
  }

  protected getIcon(colorScheme: ColorScheme): string {
    return this.colorSchemes.find(cs => cs.value === colorScheme)?.icon ?? 'auto';
  }

  protected getLabel(colorScheme: ColorScheme): string {
    return this.colorSchemes.find(cs => cs.value === colorScheme)?.label ?? '';
  }

  protected renderItem(colorScheme: ColorSchemeOption): TemplateResult | symbol {
    return html`
      <li>
        <button class="dropdown-item" @click="${(e: Event) => this.handleClick(e, colorScheme.value)}" aria-current="${this.activeColorScheme === colorScheme.value ? 'true' : 'false'}">
          <span class="dropdown-item-columns">
            <span class="dropdown-item-column dropdown-item-column-icon" aria-hidden="true">
              <typo3-backend-icon identifier="${colorScheme.icon}" size="small"></typo3-backend-icon>
            </span>
            <span class="dropdown-item-column dropdown-item-column-title">
              ${colorScheme.label}
              ${colorScheme.value === 'auto' ? html`<span class="dropdown-item-column-title-info">${this.getLabel(this.autoDetect)}</span>` : ''}
            </span>
            ${this.activeColorScheme === colorScheme.value ? html`
              <span class="text-primary">
                <typo3-backend-icon identifier="actions-dot" size="small"></typo3-backend-icon>
              </span>
            ` : html`
              <typo3-backend-icon identifier="empty-empty" size="small"></typo3-backend-icon>
            `}
          </span>
        </button>
      </li>
    `;
  }

  private async toggle(e: Event): Promise<void> {
    e.preventDefault();
    e.stopPropagation();
    const currentColorScheme = this.getRealColorScheme();
    let colorScheme: ColorScheme = currentColorScheme === 'dark' ? 'light' : 'dark'
    if (colorScheme === this.autoDetect) {
      // Set to auto if the user toggled to the
      // OS default, that basically means user wants the color
      // scheme to match the current system theme
      colorScheme = 'auto';
    }
    this.triggerSchemeUpdate(colorScheme);
    await this.persistSchemeUpdate(colorScheme);
  }

  private async handleClick(e: Event, value: ColorScheme): Promise<void> {
    e.preventDefault();
    e.stopPropagation();
    this.triggerSchemeUpdate(value);
    await this.persistSchemeUpdate(value);
    this.advancedOptionsExpanded = false;
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
