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

import { css, html, LitElement, nothing, TemplateResult } from 'lit';
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
  static styles = css`
    @keyframes progress-indeterminate {
      0% {
        inset-inline-start: -33%;
      }

      100% {
        inset-inline-start: 100%;
      }
    }

    :host {
      --progress-bar-height: 3px;
      --progress-track-bg-color: light-dark(var(--bs-gray-300), var(--bs-gray-800));
      display: block;
      width: 100%;
      border-radius: var(--typo3-component-border-radius);
    }

    .progress {
      position: relative;
      overflow: hidden;
      height: var(--progress-bar-height);
      border-radius: var(--typo3-component-border-radius);
    }

    .track {
      background: var(--progress-track-bg-color);
      inset: 0;
    }

    .bar {
      --progress-bar-bg-color: var(--typo3-component-primary-color);
      background: var(--progress-bar-bg-color);
      transition: width 0.5s ease-in-out;

      &.bar-success {
        --progress-bar-bg-color: var(--bs-success);
      }

      &.bar-warning {
        --progress-bar-bg-color: var(--bs-warning);
      }

      &.bar-danger {
        --progress-bar-bg-color: var(--bs-danger);
      }

      &.indeterminate {
        animation-name: progress-indeterminate;
        animation-duration: 3s;
        animation-iteration-count: infinite;
        animation-timing-function: linear;
        width: 33%;
        background-image: linear-gradient(to right, var(--progress-track-bg-color) 0%, transparent 50%, var(--progress-track-bg-color) 100%)
      }
    }

    .track,
    .bar {
      position: absolute;
      height: var(--progress-bar-height);
      border-radius: var(--typo3-component-border-radius);
    }

    .label {
      margin-top: 2px;
    }
  `;

  @property({ type: Number, reflect: true }) value: number|undefined = undefined;
  @property({ type: Number, reflect: true }) max: number = 100;
  @property({ type: Number, reflect: true }) severity: SeverityEnum = SeverityEnum.info;
  @property({ type: String, reflect: true }) label: string|undefined;

  protected render(): TemplateResult {
    const labelIdentifier = 'progress-label-' + (Math.random() + 1).toString(36).substring(2);
    const isLabelDefined = this.label !== undefined && this.label;
    const isIndeterminate = isNaN(this.value);
    const severityClassName = 'bar-' + Severity.getCssClass(this.severity);
    const classes = classMap({
      bar: true,
      [severityClassName]: !isIndeterminate,
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
          <div class=${classes} style=${styles}></div>
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
