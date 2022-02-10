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

import {html, css, unsafeCSS, LitElement, TemplateResult, CSSResult} from 'lit';
import {customElement, property} from 'lit/decorators';
import {unsafeHTML} from 'lit/directives/unsafe-html';
import {until} from 'lit/directives/until';
import {Sizes, States, MarkupIdentifiers} from '../enum/icon-types';
import Icons from '../icons';
import '@typo3/backend/element/spinner-element';

const iconUnifyModifier = 0.86;
const iconSize = (identifier: CSSResult, size: number) => css`
  :host([size=${identifier}]),
  :host([raw]) .icon-size-${identifier} {
    font-size: ${size}px;
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
  @property({type: String}) identifier: string;
  @property({type: String}) size: Sizes = Sizes.default;
  @property({type: String}) state: States = States.default;
  @property({type: String}) overlay: string = null;
  @property({type: String}) markup: MarkupIdentifiers = MarkupIdentifiers.inline;

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
  @property({type: String}) raw?: string = null;

  static styles = [
    css`
      :host {
        display: flex;
        width: 1em;
        height: 1em;
        line-height: 0;
      }

      .icon {
        position: relative;
        display: block;
        overflow: hidden;
        white-space: nowrap;
        height: 1em;
        width: 1em;
        line-height: 1;
      }

      .icon svg,
      .icon img {
        display: block;
        height: 1em;
        width: 1em;
        transform: translate3d(0, 0, 0);
      }

      .icon * {
        display: block;
        line-height: inherit;
      }

      .icon-markup {
        position: absolute;
        display: flex;
        justify-content: center;
        align-items: center;
        text-align: center;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
      }

      .icon-overlay {
        position: absolute;
        bottom: 0;
        right: 0;
        font-size: 0.6875em;
        text-align: center;
      }

      .icon-color {
        fill: currentColor;
      }

      .icon-state-disabled .icon-markup {
        opacity: .5;
      }

      .icon-unify {
        font-size: ${iconUnifyModifier}em;
        line-height: ${1 / iconUnifyModifier};
      }

      .icon-spin .icon-markup {
        animation: icon-spin 2s infinite linear;
      }

      @keyframes icon-spin {
        0% {
          transform: rotate(0deg);
        }

        100% {
          transform: rotate(360deg);
        }
      }
    `,
    iconSize(unsafeCSS(Sizes.small), 16),
    iconSize(unsafeCSS(Sizes.default), 32),
    iconSize(unsafeCSS(Sizes.large), 48),
    iconSize(unsafeCSS(Sizes.mega), 64),
  ];

  public render(): TemplateResult {
    if (this.raw) {
      return html`${unsafeHTML(this.raw)}`;
    }

    if (!this.identifier) {
      return html``;
    }

    const icon = Icons.getIcon(this.identifier, this.size, this.overlay, this.state, this.markup)
      .then((markup: string) => {
        return html`
          ${unsafeHTML(markup)}
        `;
      });
    return html`${until(icon, html`<typo3-backend-spinner></typo3-backend-spinner>`)}`;
  }
}
