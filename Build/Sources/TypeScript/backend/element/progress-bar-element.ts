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

import { css, html, LitElement, nothing, type TemplateResult } from 'lit';
import { customElement, property } from 'lit/decorators';
import { classMap } from 'lit/directives/class-map';
import { styleMap } from 'lit/directives/style-map.js';
import Severity from '@typo3/backend/severity';
import { SeverityEnum } from '@typo3/backend/enum/severity';

/**
 * Module: @typo3/backend/element/progress-bar-element
 * @internal
 *
 * @example
 * <typo3-backend-progress-bar></typo3-backend-progress-bar>
 * + `value` can be any positive number value between and 0 and `max` or `undefined`. The latter will render an indeterminate state.
 * + `max` represents the upper value range
 * + `severity` defines the visual severity of the progress bar, ignored in indeterminate state
 * + `label` is an optional label describing the progress bar
 */
@customElement('typo3-backend-progress-bar')
export class ProgressBarElement extends LitElement {
  static override styles = css`
    @keyframes progress-indeterminate {
      0% {
        inset-inline-start: -33%;
      }

      100% {
        inset-inline-start: 100%;
      }
    }

    :host {
      --progress-bar-height: 4px;
      --progress-bar-color-primary: var(--typo3-state-primary-border-color);
      --progress-bar-color-success: var(--typo3-state-success-border-color);
      --progress-bar-color-warning: var(--typo3-state-warning-border-color);
      --progress-bar-color-danger: var(--typo3-state-danger-border-color);
      --progress-bar-color-info: var(--typo3-state-info-border-color);
      --progress-bar-color: var(--progress-bar-color-primary);
      --progress-track-color: light-dark(var(--token-color-neutral-20), var(--token-color-neutral-80));
      --progress-border-radius: var(--typo3-component-border-radius);
      display: block;
      width: 100%;
      border-radius: var(--progress-border-radius);
    }

    .progress {
      position: relative;
      overflow: hidden;
      height: var(--progress-bar-height);
      border-radius: var(--progress-border-radius);
    }

    .value {
      display: block;
      width: 1px !important;
      height: 1px !important;
      padding: 0 !important;
      margin: -1px !important;
      overflow: hidden !important;
      clip: rect(0, 0, 0, 0) !important;
      white-space: nowrap !important;
      border: 0 !important;
    }

    .track {
      background: var(--progress-track-color);
      inset: 0;
    }

    .bar {
      background: var(--progress-bar-color);
      transition: width 0.5s ease-in-out;

      &.bar-success {
        --progress-bar-color: var(--progress-bar-color-success);
      }

      &.bar-warning {
        --progress-bar-color: var(--progress-bar-color-warning);
      }

      &.bar-danger {
        --progress-bar-color: var(--progress-bar-color-danger);
      }

      &.bar-info {
        --progress-bar-color: var(--progress-bar-color-info);
      }

      &.indeterminate {
        animation-name: progress-indeterminate;
        animation-duration: 3s;
        animation-iteration-count: infinite;
        animation-timing-function: linear;
        width: 33%;
        background-image: linear-gradient(to right, var(--progress-track-color) 0%, transparent 50%, var(--progress-track-color) 100%)
      }
    }

    .track,
    .bar {
      position: absolute;
      height: var(--progress-bar-height);
    }

    .label {
      margin-top: .5rem;
    }
  `;

  @property({ type: Number, reflect: true }) value: number|undefined = undefined;
  @property({ type: Number, reflect: true }) max: number = 100;
  @property({ type: Number, reflect: true }) severity: SeverityEnum|undefined = undefined;
  @property({ type: String, reflect: true }) label: string|undefined;

  protected override render(): TemplateResult {
    const labelIdentifier = 'progress-label-' + (Math.random() + 1).toString(36).substring(2);
    const isLabelDefined = this.label !== undefined && this.label;
    const isIndeterminate = isNaN(this.value);
    const classes = classMap({
      bar: true,
      ['bar-' + Severity.getCssClass(this.severity)]: !isIndeterminate && this.severity !== undefined,
      indeterminate: isIndeterminate,
    });
    const styles = isIndeterminate ? nothing : styleMap({
      width: (this.clamp(this.value, 0, this.max) / this.max * 100).toString() + '%',
    });

    return html`
      <div class="progress-wrapper">
        <div
          role="progressbar"
          class="progress"
          aria-valuenow=${!isIndeterminate ? this.value : nothing}
          aria-valuemin="0"
          aria-valuemax=${this.max}
          aria-describedby=${isLabelDefined ? labelIdentifier : nothing}
        >
          <div class="track"></div>
          <div class=${classes} style=${styles}>
            ${!isIndeterminate ? html`<span class="value">${this.value}%</span>` : nothing}
          </div>
        </div>
        ${isLabelDefined ? html`<div class="label" id=${labelIdentifier}>${this.label}</div>` : nothing}
      </div>
    `;
  }

  private clamp(value: number, min: number, max: number): number {
    return Math.min(max, Math.max(min, value));
  }
}

declare global {
  interface HTMLElementTagNameMap {
    'typo3-backend-progress-bar': ProgressBarElement;
  }
}
