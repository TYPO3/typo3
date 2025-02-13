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
import { styleMap } from 'lit/directives/style-map';
import { classMap } from 'lit/directives/class-map';

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
      --progress-stage-active: 0;
      --progress-stages-columns: 1;

      /* Progress bar skeleton */
      --progress-tracker-bar-height: 4px;
      --progress-tracker-bar-border-radius: var(--progress-tracker-bar-height);
      --progress-tracker-bar-progress-bg-color: var(--typo3-component-primary-color);
      --progress-tracker-bar-rail-bg-color: light-dark(var(--token-color-neutral-20), var(--token-color-neutral-70));

      /* Indicator skeleton */
      --progress-tracker-indicator-width: 12px;
      --progress-tracker-indicator-height: 12px;
      --progress-tracker-indicator-border-radius: 100%;
      --progress-tracker-indicator-border-height: 1px;

      /* Indicator background colors */
      --progress-tracker-indicator-bg-color-default: light-dark(var(--token-color-neutral-20), var(--token-color-neutral-70));
      --progress-tracker-indicator-bg-color-complete: var(--typo3-component-primary-color);
      --progress-tracker-indicator-bg-color-current: light-dark(var(--token-color-neutral-0), var(--token-color-neutral-90));

      /* Indicator border colors */
      --progress-tracker-indicator-border-color-complete: var(--typo3-component-primary-color);
      --progress-tracker-indicator-border-color-current: var(--typo3-component-primary-color);

      /* Indicator inlet */
      --progress-tracker-indicator-inlet-scale-complete: 1;
      --progress-tracker-indicator-inlet-scale-current: 0.6;
      --progress-tracker-indicator-inlet-bg-color: var(--progress-tracker-indicator-border-color-current);
      --progress-tracker-indicator-inlet-border-radius: 100%;

      display: block;
      width: 100%;
    }

    .track-wrapper {
      position: relative;
      display: flex;
      flex-direction: column;
      align-items: center;
    }

    .track-bar-wrapper {
      position: relative;
      top: calc((var(--progress-tracker-indicator-height) - var(--progress-tracker-bar-height)) / 2 + var(--progress-tracker-indicator-border-height));
      width: 100%;
      height: var(--progress-tracker-bar-height);
    }

    .track-bar {
      --progress-tracker-bar-inset: calc(calc(100% / var(--progress-stages-columns) / 2) - var(--progress-tracker-indicator-width) / 2);

      position: absolute;
      height: 100%;
      inset-inline-start: var(--progress-tracker-bar-inset);
      border-radius: var(--progress-tracker-bar-border-radius);
    }

    .track-bar-rail {
      --progress-tracker-rail-width: calc(calc(100% / var(--progress-stages-columns) * calc(var(--progress-stages-columns) - 1)) + calc(var(--progress-tracker-indicator-width)));

      width: var(--progress-tracker-rail-width);
      background-color: var(--progress-tracker-bar-rail-bg-color);
    }

    .track-bar-active {
      --progress-tracker-bar-width: calc(calc(100% / var(--progress-stages-columns) * var(--progress-stage-active)) + calc(var(--progress-tracker-indicator-width)));

      width: var(--progress-tracker-bar-width, 0);
      background-color: var(--progress-tracker-bar-progress-bg-color);
    }

    .stages {
      display: grid;
      grid-template-columns: repeat(var(--progress-stages-columns), 1fr);
      list-style: none;
      padding: 0;
      margin: 0;
      width: 100%;
      position: relative;
      top: calc(var(--progress-tracker-bar-height) * -1);
    }

    .stage {
      display: flex;
      flex-direction: column;
      align-items: center;
    }

    .stage-indicator {
      display: block;
      width: var(--progress-tracker-indicator-width);
      height: var(--progress-tracker-indicator-height);
      background-color: var(--progress-tracker-indicator-bg-color-default);
      border-radius: var(--progress-tracker-indicator-border-radius);
      border: var(--progress-tracker-indicator-border-height) solid transparent;
    }

    .stage-indicator:after {
      display: block;
      content: "";
      width: var(--progress-tracker-indicator-width);
      height: var(--progress-tracker-indicator-height);
      background-color: var(--progress-tracker-indicator-inlet-bg-color);
      transform: scale(0);
      border-radius: var(--progress-tracker-indicator-inlet-border-radius);
    }

    .stage-indicator-complete {
      border-color: var(--progress-tracker-indicator-border-color-complete);
      background-color: var(--progress-tracker-indicator-bg-color-complete);
    }

    .stage-indicator-complete:after {
      transform: scale(var(--progress-tracker-indicator-inlet-scale-complete));
    }

    .stage-indicator-current {
      border-color: var(--progress-tracker-indicator-border-color-current);
      background-color: var(--progress-tracker-indicator-bg-color-current);
    }

    .stage-indicator-current:after {
      transform: scale(var(--progress-tracker-indicator-inlet-scale-current));
    }

    .stage-indicator-open:after {
      background-color: transparent;
    }

    .stage-label {
      margin-top: 2px;
    }

    @media (prefers-reduced-motion: no-preference) {
      .track-bar-active {
        transition: width 0.5s ease-in-out;
      }

      .stage-indicator {
        transition: background-color .5s ease-in-out, border-color .5s ease-in-out;
      }

      .stage-indicator:after {
        transition: transform .5s ease-in-out, background-color .5s ease-in-out;
      }
    }
  `;

  @property({ attribute: 'stages', type: Array }) stages: Stage[] = [];
  @property({ attribute: 'active', type: Number, reflect: true }) activeStage: number = 0;

  protected override render(): TemplateResult | symbol {
    if (this.stages.length < 2) {
      return nothing;
    }
    this.activeStage = Math.min(this.activeStage, this.stages.length - 1);

    const styles = styleMap({
      '--progress-stage-active': `${this.activeStage}`,
      '--progress-stages-columns': `${this.stages.length}`,
    });

    return html`
      <div class="track-wrapper" role="group" style=${styles}>
        <div class="track-bar-wrapper">
          <div class="track-bar track-bar-rail"></div>
          <div class="track-bar track-bar-active"></div>
        </div>
        <ul class="stages">
          ${this.stages.map((stage: Stage, index: number): TemplateResult => this.renderStage(stage, index))}
        </ul>
      </div>
    `;
  }

  private renderStage(stage: Stage, index: number): TemplateResult {
    const classes = classMap({
      'stage-indicator': true,
      'stage-indicator-complete': index < this.activeStage,
      'stage-indicator-current': index === this.activeStage,
      'stage-indicator-open': index > this.activeStage,
    });

    return html`
      <li class="stage" aria-current="${index === this.activeStage ? 'step' : 'false'}">
        <span class=${classes}></span>
        <span class="stage-label">${stage}</span>
      </li>
    `;
  }
}

declare global {
  interface HTMLElementTagNameMap {
    'typo3-backend-progress-tracker': ProgressTrackerElement;
  }
}
