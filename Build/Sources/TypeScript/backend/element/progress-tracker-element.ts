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
import { styleMap } from 'lit/directives/style-map';
import { lll } from '@typo3/core/lit-helper';

export type Stage = string;

/**
 * Module: @typo3/backend/element/progress-track-element
 * @internal
 *
 * @example
 * <typo3-backend-progress-tracker stages="[&quot;Stage 1&quot;,&quot;Stage 2&quot;,&quot;Stage 3&quot;,&quot;Stage 4&quot;]"></typo3-backend-progress-tracker>
 */
@customElement('typo3-backend-progress-tracker')
export class ProgressTrackerElement extends LitElement {
  static override styles = css`
    :host {
      --progress-tracker-margin: var(--typo3-spacing);
      --progress-tracker-stage: 0;
      --progress-tracker-stages: 0;
      --progress-tracker-gap: .25rem;
      --progress-tracker-bar-height: 6px;
      --progress-tracker-bar-border-radius: 3px;
      --progress-tracker-bar-bg: light-dark(var(--token-color-neutral-20), var(--token-color-neutral-80));
      --progress-tracker-bar-progress-bg: var(--typo3-state-primary-border-color);

      display: block;
      width: 100%;
      margin-bottom: var(--progress-tracker-margin);
    }

    .tracker {
      position: relative;
      display: grid;
      gap: var(--progress-tracker-gap);
      grid-template-columns: auto min-content;
      grid-template-areas:
        "stage step"
        "bar   bar";
    }

    .tracker-stage {
      grid-area: stage;
    }

    .tracker-step {
      grid-area: step;
      white-space: nowrap;
    }

    .tracker-bar {
      grid-area: bar;
      position: relative;
      width: 100%;
      height: var(--progress-tracker-bar-height);
      border-radius: var(--progress-tracker-bar-border-radius);
      background-color: var(--progress-tracker-bar-bg);
    }

    .tracker-bar:after {
      content: '';
      height: 100%;
      width: calc(100% / var(--progress-tracker-stages) * var(--progress-tracker-stage));
      display: block;
      border-radius: inherit;
      background-color: var(--progress-tracker-bar-progress-bg);
    }

    @media (prefers-reduced-motion: no-preference) {
      .tracker-bar:after {
        transition: width 0.5s ease-in-out;
      }
    }
  `;

  @property({ attribute: 'stages', type: Array }) stages: Stage[] = [];
  @property({ attribute: 'active', type: Number, reflect: true }) activeStage: number = 0;

  protected override render(): TemplateResult | symbol {
    if (this.stages.length < 2) {
      return nothing;
    }
    this.activeStage = Math.min(Math.max(this.activeStage, this.stages.length > 0 ? 1 : 0), this.stages.length);
    const currentStage = this.stages[this.activeStage - 1];

    const styles = styleMap({
      '--progress-tracker-stage': `${this.activeStage}`,
      '--progress-tracker-stages': `${this.stages.length}`,
    });

    return html`
      <div class="tracker" role="group" aria-describedby="tracker-details" style=${styles}>
        <div class="tracker-stage" id="tracker-stage" aria-live="polite">${currentStage}</div>
        <div class="tracker-step" id="tracker-details">${lll('progressTracker.steps', this.activeStage, this.stages.length) || `Step ${this.activeStage} of ${this.stages.length}`}</div>
        <div class="tracker-bar" id="tracker-bar" aria-hidden="true"></div>
      </div>
    `;
  }
}

declare global {
  interface HTMLElementTagNameMap {
    'typo3-backend-progress-tracker': ProgressTrackerElement;
  }
}
