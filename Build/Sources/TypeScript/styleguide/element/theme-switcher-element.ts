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
import { html, LitElement, type TemplateResult } from 'lit';
import { lll } from '@typo3/core/lit-helper';
import '@typo3/backend/element/icon-element';
import 'bootstrap'; // for data-bs-toggle="dropdown"

type ThemeIdentifier = string;
type Theme = {
  icon: string,
  label: string
};

const selectorConverter = {
  fromAttribute(selector: string) {
    return document.querySelector(selector);
  }
};

/**
 * Module: @typo3/styleguide/element/theme-switcher-element
 *
 * @example
 * <typo3-styleguide-theme-switcher></typo3-styleguide-theme-switcher>
 */
@customElement('typo3-styleguide-theme-switcher')
export class ThemeSwitcherElement extends LitElement {
  @property() private activeTheme: ThemeIdentifier = 'light';
  @property({ converter: selectorConverter }) private readonly example: HTMLElement;

  private readonly themes: Record<ThemeIdentifier, Theme> = {
    auto: {
      icon: 'actions-circle-half',
      label: 'colorScheme.auto',
    },
    light: {
      icon: 'actions-brightness-high',
      label: 'colorScheme.light',
    },
    dark: {
      icon: 'actions-moon',
      label: 'colorScheme.dark',
    }
  };

  protected override createRenderRoot(): HTMLElement | ShadowRoot {
    return this;
  }

  protected override render(): TemplateResult {
    const dropdownActiveIcon = html`<span class="text-primary"><typo3-backend-icon identifier="actions-dot" size="small"></typo3-backend-icon></span>`;
    const dropdownInactiveIcon = html`<typo3-backend-icon identifier="miscellaneous-placeholder" size="small"></typo3-backend-icon>`;
    const themeOptions = [];
    for (const [identifier, theme] of Object.entries(this.themes)) {
      themeOptions.push(html`
        <li>
          <a class="dropdown-item dropdown-item-spaced" href="#" data-theme="${identifier}" @click="${this.setTheme}">
            ${identifier === this.activeTheme ? dropdownActiveIcon : dropdownInactiveIcon}
            ${lll(theme.label)}
          </a>
        </li>
      `);
    }

    return html`
      <div class="colorscheme-switch">
        ${lll('colorScheme.selector.label')}
        <div class="dropdown">
          <button class="btn btn-link dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
            <typo3-backend-icon identifier="${this.themes[this.activeTheme].icon}" size="small"></typo3-backend-icon> ${lll(this.themes[this.activeTheme].label)}
          </button>
          <ul class="dropdown-menu">
            ${themeOptions}
          </ul>
        </div>
      </div>
    `;
  }

  private setTheme(event: PointerEvent): void {
    this.activeTheme = (event.target as HTMLAnchorElement).dataset.theme;
    this.example.dataset.colorScheme = this.activeTheme;
  }
}

declare global {
  interface HTMLElementTagNameMap {
    'typo3-styleguide-theme-switcher': ThemeSwitcherElement;
  }
}
