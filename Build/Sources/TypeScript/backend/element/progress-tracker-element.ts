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
      --progress-tracker-stage: 0;
      --progress-tracker-stages: 0;
      --progress-tracker-bar-height: 4px;
      --progress-tracker-bar-border-radius: var(--progress-tracker-bar-height);
      --progress-tracker-bar-progress-bg-color: var(--typo3-state-primary-border-color);
      --progress-tracker-bar-track-bg-color: light-dark(var(--token-color-neutral-20), var(--token-color-neutral-80));
      --progress-tracker-indicator-size: 12px;
      --progress-tracker-indicator-inlet-bg-default: transparent;
      --progress-tracker-indicator-inlet-bg-current: var(--typo3-state-primary-color);
      --progress-tracker-indicator-inlet-bg: var(--progress-tracker-indicator-inlet-bg-default);
      --progress-tracker-indicator-bg-default: light-dark(var(--token-color-neutral-20), var(--token-color-neutral-70));
      --progress-tracker-indicator-bg-complete: var(--typo3-state-primary-border-color);
      --progress-tracker-indicator-bg: var(--progress-tracker-indicator-bg-default);

      display: block;
      width: 100%;
    }

    .track-wrapper {
      position: relative;
      display: flex;
      flex-direction: column;
      align-items: center;
      min-height: var(--progress-tracker-indicator-size);
    }

    .track-bar-wrapper {
      position: absolute;
      top: calc((var(--progress-tracker-indicator-size) - var(--progress-tracker-bar-height)) / 2);
      width: 100%;
      height: var(--progress-tracker-bar-height);
    }

    .track-bar {
      position: absolute;
      height: 100%;
      inset-inline-start: calc(100% / var(--progress-tracker-stages) / 2);
      border-radius: var(--progress-tracker-bar-border-radius);
    }

    .track-bar-track {
      width: calc(100% / var(--progress-tracker-stages) * calc(var(--progress-tracker-stages) - 1));
      background-color: var(--progress-tracker-bar-track-bg-color);
    }

    .track-bar-active {
      width: calc(100% / var(--progress-tracker-stages) * calc(var(--progress-tracker-stage) - 1));
      background-color: var(--progress-tracker-bar-progress-bg-color);
    }

    .stages {
      display: grid;
      grid-template-columns: repeat(var(--progress-tracker-stages), 1fr);
      list-style: none;
      padding: 0;
      margin: 0;
      width: 100%;
      position: relative;
    }

    .stage {
      display: flex;
      flex-direction: column;
      align-items: center;
    }

    .stage-indicator {
      position: relative;
      display: block;
      width: var(--progress-tracker-indicator-size);
      height: var(--progress-tracker-indicator-size);
      background-color: var(--progress-tracker-indicator-bg);
      border-radius: 100%;
    }

    .stage-indicator:after {
      content: '';
      position: absolute;
      top: 50%;
      left: 50%;
      width: calc(100% - 6px);
      height: calc(100% - 6px);
      border-radius: 100%;
      transform: translate(-50%, -50%);
      background-color: var(--progress-tracker-indicator-inlet-bg);
    }

    .stage-indicator-current,
    .stage-indicator-complete {
      --progress-tracker-indicator-bg: var(--progress-tracker-indicator-bg-complete);
    }

    .stage-indicator-current:after {
      --progress-tracker-indicator-inlet-bg: var(--progress-tracker-indicator-inlet-bg-current);
    }

    .stage-label {
      margin-top: .5rem;
      text-align: center;
    }

    @media (prefers-reduced-motion: no-preference) {
      .track-bar-active {
        transition: width 0.5s ease-in-out;
      }

      .stage-indicator {
        transition: background-color .5s ease-in-out;
      }

      .stage-indicator:after {
        transition: background-color .5s ease-in-out;
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

    const styles = styleMap({
      '--progress-tracker-stage': `${this.activeStage}`,
      '--progress-tracker-stages': `${this.stages.length}`,
    });

    return html`
      <div class="track-wrapper" role="group" style=${styles}>
        <div class="track-bar-wrapper">
          <div class="track-bar track-bar-track"></div>
          <div class="track-bar track-bar-active"></div>
        </div>
        <ul class="stages">
          ${this.stages.map((stage: Stage, index: number): TemplateResult => this.renderStage(stage, index + 1))}
        </ul>
      </div>
    `;
  }

  private renderStage(stage: Stage, cycle: number): TemplateResult {
    const classes = classMap({
      'stage-indicator': true,
      'stage-indicator-complete': cycle < this.activeStage,
      'stage-indicator-current': cycle === this.activeStage
    });

    return html`
      <li class="stage" aria-current="${cycle === this.activeStage ? 'step' : 'false'}">
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
