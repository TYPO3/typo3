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

import { customElement, property } from 'lit/decorators.js';
import { html, LitElement, nothing, type TemplateResult } from 'lit';
import { classMap, type ClassInfo } from 'lit/directives/class-map.js';

// The first group are the base colours; the second group are semantic states
// that map to one of those base colours in the stylesheet, so a recurring
// domain concept looks identical wherever it appears in the backend. Keep the
// semantic group small and curated rather than introducing new colours.
export type StatusIndicatorState =
  | 'primary' | 'secondary' | 'info' | 'success' | 'warning' | 'danger' | 'notice' | 'default'
  | 'active' | 'online' | 'running' | 'disabled';

// A semantic state may also imply a motion modifier, so a single state carries
// both the colour and the activity animation of the concept it names.
const semanticMotion: Partial<Record<StatusIndicatorState, 'live' | 'loading'>> = {
  online: 'live',
  running: 'loading',
};

/**
 * Module: @typo3/backend/element/status-indicator-element
 *
 * A small dot that flags the state of a record, task or interface element.
 * Add the `live` attribute to signal that an element is active or live, or the
 * `loading` attribute to signal an operation in progress. Provide a `label` to
 * expose an accessible name (rendered as role="img" + aria-label) and a hover
 * tooltip (via title); without it the indicator is purely decorative and hidden
 * from assistive technology.
 *
 * The `state` accepts either a base colour (success, danger, …) or a semantic
 * state (active, online, running, disabled) that resolves to one of those
 * colours and may carry its own motion, so `state="running"` is already
 * animated while `state="active"` is a plain dot.
 *
 * @example
 * <typo3-backend-status-indicator state="success" label="Online"></typo3-backend-status-indicator>
 * <typo3-backend-status-indicator state="success" live label="Live"></typo3-backend-status-indicator>
 * <typo3-backend-status-indicator state="info" loading label="Loading"></typo3-backend-status-indicator>
 * <typo3-backend-status-indicator state="running" label="Running"></typo3-backend-status-indicator>
 */
@customElement('typo3-backend-status-indicator')
export class StatusIndicatorElement extends LitElement {
  @property({ type: String }) state: StatusIndicatorState = 'default';
  @property({ type: Boolean }) live: boolean = false;
  @property({ type: Boolean }) loading: boolean = false;
  @property({ type: String }) label: string = null;

  protected override createRenderRoot(): HTMLElement | ShadowRoot {
    return this;
  }

  protected override render(): TemplateResult {
    const labelled = this.label !== null && this.label !== '';
    return html`
      <span
        class=${classMap(this.getClasses())}
        title=${labelled ? this.label : nothing}
        role=${labelled ? 'img' : nothing}
        aria-label=${labelled ? this.label : nothing}
        aria-hidden=${labelled ? nothing : 'true'}
      ></span>
    `;
  }

  private getClasses(): ClassInfo {
    const motion = semanticMotion[this.state];
    return {
      ['status-indicator']: true,
      ['status-indicator-' + this.state]: true,
      ['status-indicator-live']: this.live || motion === 'live',
      ['status-indicator-loading']: this.loading || motion === 'loading',
    };
  }
}

declare global {
  interface HTMLElementTagNameMap {
    'typo3-backend-status-indicator': StatusIndicatorElement;
  }
}
