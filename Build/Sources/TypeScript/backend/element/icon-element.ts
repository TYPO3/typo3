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

import { html, css, unsafeCSS, LitElement, TemplateResult, CSSResult, nothing } from 'lit';
import { customElement, property } from 'lit/decorators';
import { unsafeHTML } from 'lit/directives/unsafe-html';
import { until } from 'lit/directives/until';
import { Sizes, States, MarkupIdentifiers } from '../enum/icon-types';
import Icons from '../icons';
import '@typo3/backend/element/spinner-element';

const iconSize = (identifier: CSSResult) => css`
  :host([size=${identifier}]) .icon-size-${identifier},
  :host([raw]) .icon-size-${identifier} {
    --icon-size: var(--icon-size-${identifier})
  }
  :host([size=${identifier}]) .icon-size-${identifier} .icon-unify,
  :host([raw]) .icon-size-${identifier} .icon-unify {
    line-height: var(--icon-size);
    font-size: calc(var(--icon-size) * var(--icon-unify-modifier))
  }
  :host([size=${identifier}]) .icon-size-${identifier} .icon-overlay .icon-unify,
  :host([raw]) .icon-size-${identifier} .icon-overlay .icon-unify {
    line-height: calc(var(--icon-size) / 1.6);
    font-size: calc((var(--icon-size) / 1.6) * var(--icon-unify-modifier))
  }
`;

/**
 * Module: @typo3/backend/element/icon-element
 *
 * @example
 * <typo3-backend-icon identifier="data-view-page" size="small"></typo3-backend-icon>
 */
@customElement('typo3-backend-icon')
export class IconElement extends LitElement {
  // @todo the css of the @typo3/icons should be included instead
  static styles = [
    css`
      :host {
        --icon-color-primary: currentColor;
        --icon-size-small: 16px;
        --icon-size-medium: 32px;
        --icon-size-large: 48px;
        --icon-size-mega: 64px;
        --icon-unify-modifier: 0.86;
        --icon-opacity-disabled: 0.5

        display: inline-block;
      }

      .icon-wrapper {
        display: flex;
        align-items: center;
        justify-content: center;
      }

      .icon {
        position: relative;
        display: inline-flex;
        overflow: hidden;
        white-space: nowrap;
        height: var(--icon-size, 1em);
        width: var(--icon-size, 1em);
        line-height: var(--icon-size, 1em);
        flex-shrink: 0;
      }

      .icon img, .icon svg {
        display: block;
        height: 100%;
        width: 100%
      }

      .icon * {
        display: block;
        line-height: inherit
      }

      .icon-markup {
        position: absolute;
        display: block;
        text-align: center;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0
      }

      .icon-overlay {
        position: absolute;
        bottom: 0;
        right: 0;
        height: 68.75%;
        width: 68.75%;
        text-align: center
      }

      .icon-color {
        fill: var(--icon-color-primary)
      }

      .icon-spin .icon-markup {
        -webkit-animation: icon-spin 2s infinite linear;
        animation: icon-spin 2s infinite linear
      }

      @keyframes icon-spin {
        0% {
          transform: rotate(0)
        }
        100% {
          transform: rotate(360deg)
        }
      }

      .icon-state-disabled .icon-markup {
        opacity: var(--icon-opacity-disabled)
      }
    `,
    iconSize(unsafeCSS(Sizes.small)),
    iconSize(unsafeCSS(Sizes.default)),
    iconSize(unsafeCSS(Sizes.medium)),
    iconSize(unsafeCSS(Sizes.large)),
    iconSize(unsafeCSS(Sizes.mega)),
  ];

  @property({ type: String }) identifier: string;
  @property({ type: String, reflect: true }) size: Sizes = null;
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

  public render(): TemplateResult | symbol {
    if (this.raw) {
      return html`${unsafeHTML(this.raw)}`;
    }

    if (!this.identifier) {
      return nothing;
    }

    const icon = Icons.getIcon(this.identifier, this.size, this.overlay, this.state, this.markup)
      .then((markup: string) => {
        return html`
          ${unsafeHTML(markup)}
        `;
      });
    return html`<div class="icon-wrapper">${until(icon, html`<typo3-backend-spinner></typo3-backend-spinner>`)}</div>`;
  }
}

declare global {
  interface HTMLElementTagNameMap {
    'typo3-backend-icon': IconElement;
  }
}
